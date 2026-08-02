<?php

namespace App\Console\Commands;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class BackfillProviderDefaultMedia extends Command
{
    protected $signature = 'providers:backfill-default-media
        {--source-provider=67 : Proveedor cuya portada y galeria se usaran como contenido generico}
        {--apply : Copia archivos y crea las asociaciones por provider_user_id}';

    protected $description = 'Completa portada y galeria vacias de proveedores con copias genericas trazables.';

    public function handle(): int
    {
        if (! $this->schemaIsReady()) {
            $this->error('Faltan las columnas provider_user_id/is_provider_default. Ejecuta primero las migraciones.');

            return self::FAILURE;
        }

        $sourceProviderId = max(0, (int) $this->option('source-provider'));
        $sourceProvider = User::query()
            ->where('id', $sourceProviderId)
            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
            ->first();
        if (! $sourceProvider) {
            $this->error('No existe el proveedor fuente indicado: '.$sourceProviderId);

            return self::FAILURE;
        }

        $sourceCover = CoverImage::query()
            ->where('provider_user_id', $sourceProviderId)
            ->where('is_provider_default', false)
            ->latest('id')
            ->first();
        $sourceGallery = MoreImage::query()
            ->where('provider_user_id', $sourceProviderId)
            ->where('is_provider_default', false)
            ->orderBy('id')
            ->get();

        if (! $sourceCover || $sourceGallery->isEmpty()) {
            $this->error('El proveedor fuente debe tener una portada real y al menos una imagen de galeria.');

            return self::FAILURE;
        }

        try {
            $coverSource = $this->sourceFile((string) $sourceCover->url);
            $gallerySources = $sourceGallery
                ->map(fn (MoreImage $image) => [
                    'row' => $image,
                    'path' => $this->sourceFile((string) $image->url),
                ])
                ->values();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $missingCoverQuery = $this->providersMissingMedia('cover_image');
        $missingGalleryQuery = $this->providersMissingMedia('more_images');
        $providerCount = User::query()->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)->count();
        $missingCoverCount = (clone $missingCoverQuery)->count();
        $missingGalleryCount = (clone $missingGalleryQuery)->count();
        $galleryBytes = $gallerySources->sum(fn (array $source) => File::size($source['path']));
        $requiredBytes = (File::size($coverSource) * $missingCoverCount) + ($galleryBytes * $missingGalleryCount);

        $this->table(['Metrica', 'Total'], [
            ['Proveedor fuente', $sourceProvider->user_name.' (#'.$sourceProviderId.')'],
            ['Proveedores', $providerCount],
            ['Sin portada', $missingCoverCount],
            ['Sin galeria', $missingGalleryCount],
            ['Imagenes por galeria', $gallerySources->count()],
            ['Archivos requeridos', $missingCoverCount + ($missingGalleryCount * $gallerySources->count())],
            ['Espacio estimado', $this->formatBytes($requiredBytes)],
        ]);

        if (! $this->option('apply')) {
            $this->comment('Simulacion completada. Ejecuta con --apply para copiar y asociar la multimedia.');

            return self::SUCCESS;
        }

        $targetDirectory = public_path('img/uploads');
        File::ensureDirectoryExists($targetDirectory);
        if (! File::isWritable($targetDirectory)) {
            $this->error('El directorio de multimedia no permite escritura: '.$targetDirectory);

            return self::FAILURE;
        }

        $createdFiles = [];
        $createdCovers = 0;
        $createdGalleryRows = 0;
        DB::beginTransaction();

        try {
            (clone $missingCoverQuery)
                ->orderBy('id')
                ->select('id')
                ->chunkById(200, function ($providers) use (
                    $coverSource,
                    $sourceProviderId,
                    $targetDirectory,
                    &$createdFiles,
                    &$createdCovers
                ): void {
                    foreach ($providers as $provider) {
                        $filename = $this->copyForProvider(
                            $coverSource,
                            $targetDirectory,
                            (int) $provider->id,
                            'cover',
                            1,
                            $createdFiles
                        );

                        CoverImage::query()->create([
                            'url' => $filename,
                            'property_id' => null,
                            'provider_user_id' => (int) $provider->id,
                            'is_provider_default' => true,
                            'source_provider_user_id' => $sourceProviderId,
                            'service_id' => null,
                        ]);
                        $createdCovers++;
                    }
                });

            (clone $missingGalleryQuery)
                ->orderBy('id')
                ->select('id')
                ->chunkById(100, function ($providers) use (
                    $gallerySources,
                    $sourceProviderId,
                    $targetDirectory,
                    &$createdFiles,
                    &$createdGalleryRows
                ): void {
                    foreach ($providers as $provider) {
                        foreach ($gallerySources as $index => $source) {
                            $filename = $this->copyForProvider(
                                $source['path'],
                                $targetDirectory,
                                (int) $provider->id,
                                'gallery',
                                $index + 1,
                                $createdFiles
                            );

                            MoreImage::query()->create([
                                'url' => $filename,
                                'property_id' => null,
                                'provider_user_id' => (int) $provider->id,
                                'is_provider_default' => true,
                                'source_provider_user_id' => $sourceProviderId,
                                'service_id' => null,
                            ]);
                            $createdGalleryRows++;
                        }
                    }
                });

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            File::delete($createdFiles);
            $this->error('No se aplicaron cambios: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info(
            'Proceso completado: '.$createdCovers.' portadas, '.$createdGalleryRows
            .' imagenes de galeria y '.count($createdFiles).' archivos creados.'
        );

        return self::SUCCESS;
    }

    private function schemaIsReady(): bool
    {
        foreach (['cover_image', 'more_images'] as $table) {
            if (
                ! Schema::hasColumn($table, 'provider_user_id')
                || ! Schema::hasColumn($table, 'is_provider_default')
                || ! Schema::hasColumn($table, 'source_provider_user_id')
            ) {
                return false;
            }
        }

        return true;
    }

    private function providersMissingMedia(string $table): Builder
    {
        return User::query()
            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
            ->whereNotExists(function ($query) use ($table): void {
                $query->selectRaw('1')
                    ->from($table)
                    ->whereColumn($table.'.provider_user_id', 'user.id');
            });
    }

    private function sourceFile(string $filename): string
    {
        $safeName = basename(str_replace('\\', '/', trim($filename)));
        $path = public_path('img/uploads/'.$safeName);
        if ($safeName === '' || ! File::isFile($path) || ! File::isReadable($path)) {
            throw new RuntimeException('No se encuentra el archivo fuente legible: '.$safeName);
        }

        return $path;
    }

    private function copyForProvider(
        string $sourcePath,
        string $targetDirectory,
        int $providerId,
        string $kind,
        int $position,
        array &$createdFiles
    ): string {
        $extension = strtolower(pathinfo($sourcePath, PATHINFO_EXTENSION)) ?: 'webp';
        $sourceHash = hash_file('sha256', $sourcePath);
        $filename = 'provider_'.$providerId.'_default_'.$kind.'_'.$position.'_'.substr($sourceHash, 0, 12).'.'.$extension;
        $targetPath = $targetDirectory.DIRECTORY_SEPARATOR.$filename;

        if (File::exists($targetPath)) {
            if (hash_file('sha256', $targetPath) !== $sourceHash) {
                throw new RuntimeException('Existe un archivo diferente en '.$targetPath);
            }

            return $filename;
        }

        if (! File::copy($sourcePath, $targetPath)) {
            throw new RuntimeException('No se pudo copiar multimedia para el proveedor '.$providerId.'.');
        }

        $createdFiles[] = $targetPath;

        return $filename;
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
