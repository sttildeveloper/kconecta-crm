<?php

namespace App\Console\Commands;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class BackfillProviderServiceTypeMedia extends Command
{
    protected $signature = 'providers:backfill-service-type-media
        {--source-dir= : Directorio con archivos WEBP cuyo nombre comienza por el ID del tipo de servicio}
        {--fallback-image= : Imagen WEBP general para proveedores sin especialidades}
        {--covers-only : Crea o reemplaza solo portadas y no modifica galerias}
        {--apply : Reemplaza multimedia generica y copia los archivos por proveedor}';

    protected $description = 'Asigna multimedia generica propia a cada proveedor segun sus tipos de servicio.';

    public function handle(): int
    {
        if (! $this->schemaIsReady()) {
            $this->error('Faltan las tablas o columnas canonicas de especialidades y multimedia.');

            return self::FAILURE;
        }

        $sourceDirectory = $this->sourceDirectory();
        try {
            $sources = $this->loadSources($sourceDirectory);
            $fallbackSource = $this->loadFallbackSource();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $providers = User::query()
            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
            ->orderBy('id')
            ->get(['id']);
        $providerIds = $providers->pluck('id')->map(fn ($id) => (int) $id)->all();

        $typeIdsByProvider = DB::table('provider_services')
            ->whereIn('provider_id', $providerIds)
            ->orderBy('service_type_id')
            ->get(['provider_id', 'service_type_id'])
            ->groupBy(fn ($row) => (int) $row->provider_id)
            ->map(fn (Collection $rows) => $rows
                ->pluck('service_type_id')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->sort()
                ->values()
                ->all());

        $coversByProvider = CoverImage::query()
            ->whereIn('provider_user_id', $providerIds)
            ->orderBy('id')
            ->get()
            ->groupBy(fn (CoverImage $image) => (int) $image->provider_user_id);
        $galleryByProvider = MoreImage::query()
            ->whereIn('provider_user_id', $providerIds)
            ->orderBy('id')
            ->get()
            ->groupBy(fn (MoreImage $image) => (int) $image->provider_user_id);

        $plans = [];
        $withoutSpecialties = 0;
        $missingSourceProviders = 0;
        $realCoversPreserved = 0;
        $realGalleriesPreserved = 0;
        $coverRowsToReplace = 0;
        $galleryRowsToReplace = 0;
        $desiredFiles = 0;
        $requiredBytes = 0;
        $coversOnly = (bool) $this->option('covers-only');

        foreach ($providers as $provider) {
            $providerId = (int) $provider->id;
            $typeIds = $typeIdsByProvider->get($providerId, []);
            if ($typeIds === []) {
                $withoutSpecialties++;
            }

            $missingTypeIds = collect($typeIds)->reject(fn (int $typeId) => $sources->has($typeId))->values()->all();
            if ($missingTypeIds !== []) {
                $missingSourceProviders++;

                continue;
            }

            $providerCovers = $coversByProvider->get($providerId, collect());
            $providerGallery = $galleryByProvider->get($providerId, collect());
            $realCover = $providerCovers->first(fn (CoverImage $image) => ! (bool) $image->is_provider_default);
            $realGallery = $providerGallery->filter(fn (MoreImage $image) => ! (bool) $image->is_provider_default);
            $defaultCovers = $providerCovers->filter(fn (CoverImage $image) => (bool) $image->is_provider_default)->values();
            $defaultGallery = $providerGallery->filter(fn (MoreImage $image) => (bool) $image->is_provider_default)->values();

            if ($realCover) {
                $realCoversPreserved++;
            }
            if ($realGallery->isNotEmpty()) {
                $realGalleriesPreserved++;
            }

            $coverSource = $realCover
                ? null
                : ($typeIds === [] ? $fallbackSource : $sources->get($typeIds[0]));
            $galleryTypeIds = ($coversOnly || $typeIds === [] || $realGallery->isNotEmpty())
                ? []
                : ($realCover ? $typeIds : array_slice($typeIds, 1));
            $gallerySources = collect($galleryTypeIds)
                ->map(fn (int $typeId) => $sources->get($typeId))
                ->values();

            $desiredCoverUrl = $coverSource
                ? $this->relativeTarget($providerId, 'cover', $coverSource)
                : null;
            $desiredGalleryUrls = $gallerySources
                ->map(fn (array $source) => $this->relativeTarget($providerId, 'gallery', $source))
                ->sort()
                ->values()
                ->all();
            $currentCoverUrls = $defaultCovers->pluck('url')->map(fn ($url) => (string) $url)->sort()->values()->all();
            $currentGalleryUrls = $defaultGallery->pluck('url')->map(fn ($url) => (string) $url)->sort()->values()->all();
            $desiredCoverUrls = $desiredCoverUrl ? [$desiredCoverUrl] : [];

            $coverNeedsChange = $currentCoverUrls !== $desiredCoverUrls;
            $galleryNeedsChange = ! $coversOnly
                && $typeIds !== []
                && $currentGalleryUrls !== $desiredGalleryUrls;
            if (! $coverNeedsChange && ! $galleryNeedsChange) {
                continue;
            }

            $coverRowsToReplace += $coverNeedsChange ? $defaultCovers->count() : 0;
            $galleryRowsToReplace += $galleryNeedsChange ? $defaultGallery->count() : 0;
            if ($coverNeedsChange && $coverSource) {
                $desiredFiles++;
                $requiredBytes += $coverSource['bytes'];
            }
            if ($galleryNeedsChange) {
                $desiredFiles += $gallerySources->count();
                $requiredBytes += $gallerySources->sum('bytes');
            }

            $plans[] = [
                'provider_id' => $providerId,
                'cover_source' => $coverSource,
                'gallery_sources' => $gallerySources,
                'default_covers' => $defaultCovers,
                'default_gallery' => $defaultGallery,
                'cover_needs_change' => $coverNeedsChange,
                'gallery_needs_change' => $galleryNeedsChange,
            ];
        }

        $this->table(['Metrica', 'Total'], [
            ['Proveedores', $providers->count()],
            ['Alcance', $coversOnly ? 'Solo portadas' : 'Portadas y galerias'],
            ['Con especialidades', $providers->count() - $withoutSpecialties],
            ['Sin especialidades (portada general)', $withoutSpecialties],
            ['Sin imagen para alguna especialidad', $missingSourceProviders],
            ['Portadas reales preservadas', $realCoversPreserved],
            ['Galerias reales preservadas', $realGalleriesPreserved],
            ['Proveedores con cambios', count($plans)],
            ['Portadas genericas anteriores a retirar', $coverRowsToReplace],
            ['Imagenes genericas anteriores a retirar', $galleryRowsToReplace],
            ['Copias de destino requeridas', $desiredFiles],
            ['Espacio maximo estimado', $this->formatBytes($requiredBytes)],
        ]);

        if ($missingSourceProviders > 0) {
            $this->error('Hay proveedores cuyas especialidades no tienen una imagen WEBP asociada. No se aplicaron cambios.');

            return self::FAILURE;
        }

        if (! $this->option('apply')) {
            $this->comment('Simulacion completada. Ejecuta con --apply para reemplazar solo la multimedia generica.');

            return self::SUCCESS;
        }

        $createdFiles = [];
        $obsoleteFiles = [];
        $desiredPaths = [];
        $createdCovers = 0;
        $createdGallery = 0;
        DB::beginTransaction();

        try {
            foreach ($plans as $plan) {
                $providerId = $plan['provider_id'];

                if ($plan['cover_needs_change']) {
                    foreach ($plan['default_covers'] as $image) {
                        $obsoleteFiles[] = $this->absoluteStoredFile((string) $image->url);
                        $image->delete();
                    }

                    if ($plan['cover_source']) {
                        $url = $this->copyForProvider($providerId, 'cover', $plan['cover_source'], $createdFiles);
                        $desiredPaths[$this->absoluteStoredFile($url)] = true;
                        CoverImage::query()->create([
                            'url' => $url,
                            'property_id' => null,
                            'provider_user_id' => $providerId,
                            'is_provider_default' => true,
                            'source_provider_user_id' => null,
                            'service_id' => null,
                        ]);
                        $createdCovers++;
                    }
                }

                if ($plan['gallery_needs_change']) {
                    foreach ($plan['default_gallery'] as $image) {
                        $obsoleteFiles[] = $this->absoluteStoredFile((string) $image->url);
                        $image->delete();
                    }

                    foreach ($plan['gallery_sources'] as $source) {
                        $url = $this->copyForProvider($providerId, 'gallery', $source, $createdFiles);
                        $desiredPaths[$this->absoluteStoredFile($url)] = true;
                        MoreImage::query()->create([
                            'url' => $url,
                            'property_id' => null,
                            'provider_user_id' => $providerId,
                            'is_provider_default' => true,
                            'source_provider_user_id' => null,
                            'service_id' => null,
                        ]);
                        $createdGallery++;
                    }
                }
            }

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            File::delete($createdFiles);
            $this->error('No se aplicaron cambios: '.$exception->getMessage());

            return self::FAILURE;
        }

        $obsoleteFiles = collect($obsoleteFiles)
            ->filter()
            ->unique()
            ->reject(fn (string $path) => isset($desiredPaths[$path]))
            ->values()
            ->all();
        File::delete($obsoleteFiles);

        $this->info(
            'Proceso completado: '.$createdCovers.' portadas, '.$createdGallery
            .' imagenes de galeria, '.count($createdFiles).' archivos nuevos y '
            .count($obsoleteFiles).' archivos genericos anteriores retirados.'
        );

        return self::SUCCESS;
    }

    private function schemaIsReady(): bool
    {
        if (! Schema::hasTable('provider_services')) {
            return false;
        }

        foreach (['cover_image', 'more_images'] as $table) {
            foreach (['provider_user_id', 'is_provider_default', 'source_provider_user_id'] as $column) {
                if (! Schema::hasColumn($table, $column)) {
                    return false;
                }
            }
        }

        return true;
    }

    private function sourceDirectory(): string
    {
        $configured = trim((string) $this->option('source-dir'));

        return $configured !== ''
            ? $configured
            : public_path('img/service-types/WEBP');
    }

    private function loadSources(string $directory): Collection
    {
        if (! File::isDirectory($directory) || ! File::isReadable($directory)) {
            throw new RuntimeException('No se puede leer el directorio de imagenes: '.$directory);
        }

        $sources = collect();
        foreach (File::files($directory) as $file) {
            if (! preg_match('/^(\d+)-.+\.webp$/i', $file->getFilename(), $matches)) {
                continue;
            }

            $typeId = (int) $matches[1];
            if ($sources->has($typeId)) {
                throw new RuntimeException('Hay mas de una imagen para el tipo de servicio '.$typeId.'.');
            }
            if ($file->getSize() <= 0 || ! $file->isReadable()) {
                throw new RuntimeException('La imagen no es legible: '.$file->getPathname());
            }

            $sources->put($typeId, [
                'type_id' => $typeId,
                'path' => $file->getPathname(),
                'bytes' => $file->getSize(),
                'hash' => hash_file('sha256', $file->getPathname()),
            ]);
        }

        if ($sources->isEmpty()) {
            throw new RuntimeException('No se encontraron imagenes con el formato {id}-{nombre}.webp en '.$directory.'.');
        }

        return $sources;
    }

    private function loadFallbackSource(): array
    {
        $configured = trim((string) $this->option('fallback-image'));
        $path = $configured !== ''
            ? $configured
            : public_path('img/hero-bg.webp');

        if (! File::isFile($path) || ! File::isReadable($path) || File::size($path) <= 0) {
            throw new RuntimeException('No se puede leer la imagen general para proveedores sin especialidades: '.$path);
        }

        return [
            'type_id' => 'general',
            'path' => $path,
            'bytes' => File::size($path),
            'hash' => hash_file('sha256', $path),
        ];
    }

    private function relativeTarget(int $providerId, string $kind, array $source): string
    {
        return 'providers/'.$providerId.'/default-'.$kind.'-'.$source['type_id'].'-'.substr($source['hash'], 0, 12).'.webp';
    }

    private function copyForProvider(int $providerId, string $kind, array $source, array &$createdFiles): string
    {
        $relativePath = $this->relativeTarget($providerId, $kind, $source);
        $targetPath = $this->absoluteStoredFile($relativePath);
        File::ensureDirectoryExists(dirname($targetPath));

        if (File::exists($targetPath)) {
            if (hash_file('sha256', $targetPath) !== $source['hash']) {
                throw new RuntimeException('Existe un archivo diferente en '.$targetPath.'.');
            }

            return $relativePath;
        }

        if (! File::copy($source['path'], $targetPath)) {
            throw new RuntimeException('No se pudo copiar multimedia para el proveedor '.$providerId.'.');
        }

        $createdFiles[] = $targetPath;

        return $relativePath;
    }

    private function absoluteStoredFile(string $relativePath): string
    {
        $normalized = str_replace('\\', '/', trim($relativePath));
        if ($normalized === '' || str_starts_with($normalized, '/') || preg_match('#(^|/)\.\.(/|$)#', $normalized)) {
            throw new RuntimeException('Ruta multimedia no valida: '.$relativePath);
        }

        return public_path('img/uploads/'.ltrim($normalized, '/'));
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $units = ['KiB', 'MiB', 'GiB', 'TiB'];
        $value = $bytes / 1024;
        foreach ($units as $unit) {
            if ($value < 1024 || $unit === 'TiB') {
                return number_format($value, 2, ',', '.').' '.$unit;
            }
            $value /= 1024;
        }

        return $bytes.' B';
    }
}
