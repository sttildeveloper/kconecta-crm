<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use RuntimeException;
use Throwable;

class BackfillProviderDefaultPhotos extends Command
{
    protected $signature = 'providers:backfill-default-photos
        {--apply : Copia el logo y asigna una foto por defecto a cada proveedor sin foto}';

    protected $description = 'Audita y completa fotos vacias de proveedores con copias individuales del logo Kconecta.';

    public function handle(): int
    {
        $sourcePath = public_path('img/kconecta_icon.webp');
        $targetDirectory = public_path('img/photo_profile');

        if (! File::isFile($sourcePath) || ! File::isReadable($sourcePath)) {
            $this->error('No se encuentra el logo oficial legible: '.$sourcePath);

            return self::FAILURE;
        }

        $missingPhotoQuery = User::query()
            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
            ->where(function ($query) {
                $query->whereNull('photo')->orWhereRaw("TRIM(photo) = ''");
            });

        $providerCount = User::query()
            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
            ->count();
        $missingPhotoCount = (clone $missingPhotoQuery)->count();

        $this->table(
            ['Metrica', 'Total'],
            [
                ['Proveedores', $providerCount],
                ['Con foto asignada', $providerCount - $missingPhotoCount],
                ['Sin foto', $missingPhotoCount],
                ['Copias requeridas', $missingPhotoCount],
            ]
        );

        if (! $this->option('apply')) {
            $this->comment('Simulacion completada. Ejecuta con --apply para copiar y asignar las fotos.');

            return self::SUCCESS;
        }

        File::ensureDirectoryExists($targetDirectory);
        if (! File::isWritable($targetDirectory)) {
            $this->error('El directorio de fotos no permite escritura: '.$targetDirectory);

            return self::FAILURE;
        }

        $sourceHash = hash_file('sha256', $sourcePath);
        $createdFiles = [];
        $updatedProviders = 0;

        DB::beginTransaction();

        try {
            (clone $missingPhotoQuery)
                ->orderBy('id')
                ->select(['id'])
                ->chunkById(200, function ($providers) use (
                    $sourcePath,
                    $sourceHash,
                    $targetDirectory,
                    &$createdFiles,
                    &$updatedProviders
                ): void {
                    foreach ($providers as $provider) {
                        $filename = 'provider_'.$provider->id.'_kconecta_default.webp';
                        $targetPath = $targetDirectory.DIRECTORY_SEPARATOR.$filename;
                        $fileAlreadyExisted = File::exists($targetPath);

                        if ($fileAlreadyExisted && hash_file('sha256', $targetPath) !== $sourceHash) {
                            throw new RuntimeException('Ya existe un archivo distinto para el proveedor '.$provider->id.'.');
                        }

                        if (! $fileAlreadyExisted) {
                            if (! File::copy($sourcePath, $targetPath)) {
                                throw new RuntimeException('No se pudo copiar el logo para el proveedor '.$provider->id.'.');
                            }
                            $createdFiles[] = $targetPath;
                        }

                        $updated = User::query()
                            ->where('id', $provider->id)
                            ->where('user_level_id', User::LEVEL_SERVICE_PROVIDER)
                            ->where(function ($query) {
                                $query->whereNull('photo')->orWhereRaw("TRIM(photo) = ''");
                            })
                            ->update(['photo' => $filename]);

                        if ($updated === 0 && ! $fileAlreadyExisted) {
                            File::delete($targetPath);
                            array_pop($createdFiles);

                            continue;
                        }

                        $updatedProviders += $updated;
                    }
                });

            DB::commit();
        } catch (Throwable $exception) {
            DB::rollBack();
            File::delete($createdFiles);
            $this->error('No se aplicaron cambios: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Proceso completado: '.$updatedProviders.' proveedores actualizados y '.count($createdFiles).' fotos creadas.');

        return self::SUCCESS;
    }
}
