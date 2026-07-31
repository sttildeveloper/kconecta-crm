<?php

namespace App\Console\Commands;

use App\Services\ProviderCsvImportService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ImportProviderCsv extends Command
{
    protected $signature = 'providers:import-csv
        {file : Ruta absoluta o relativa del CSV}
        {--commit : Ejecuta la importacion real. Sin esta opcion solo hace dry-run}
        {--update-existing : Actualiza proveedores existentes encontrados por nombre o telefono}';

    protected $description = 'Importa proveedores desde CSV al modelo canonico user + user_address + provider_services.';

    public function handle(ProviderCsvImportService $providerCsvImportService): int
    {
        $filePath = $this->argument('file');
        $isCommit = (bool) $this->option('commit');
        $updateExisting = (bool) $this->option('update-existing');

        $result = $providerCsvImportService->analyzeFile($filePath, $isCommit, $updateExisting);
        $summary = $result['summary'];
        $report = $result['report'];

        $this->newLine();
        $this->info('Importacion de proveedores');
        $this->line('Archivo: ' . $filePath);
        $this->line('Modo: ' . ($isCommit ? 'commit' : 'dry-run'));
        $this->line('Actualiza existentes: ' . ($updateExisting ? 'si' : 'no'));
        $this->newLine();

        $this->table(
            ['Total filas', 'Create/Update', 'Saltadas', 'Conflictos', 'Sin mapear', 'Errores'],
            [[
                $summary['rows'],
                $summary['created'] + $summary['updated'],
                $summary['skipped'],
                $summary['conflicts'],
                $summary['unmapped'],
                $summary['errors'],
            ]]
        );

        $this->newLine();
        $this->table(
            ['Linea', 'Empresa', 'Accion', 'Resultado', 'User ID', 'Type IDs', 'Observaciones'],
            array_map(fn ($row) => [
                $row['linea'],
                Str::limit($row['empresa'], 32, '...'),
                $row['accion'],
                $row['resultado'],
                $row['user_id'],
                $row['especialidades'],
                Str::limit($row['observaciones'], 80, '...'),
            ], $report)
        );

        return self::SUCCESS;
    }
}
