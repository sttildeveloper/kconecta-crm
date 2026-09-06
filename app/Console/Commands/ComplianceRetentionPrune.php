<?php

namespace App\Console\Commands;

use App\Services\RetentionInventoryService;
use Illuminate\Console\Command;

class ComplianceRetentionPrune extends Command
{
    protected $signature = 'compliance:retention-prune {--apply : Aplica exclusivamente las políticas configuradas y habilitadas}';
    protected $description = 'Previsualiza la retención; requiere --apply para modificar datos y nunca elimina backups.';

    public function handle(RetentionInventoryService $service): int
    {
        $this->line(json_encode($service->inventory(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        if (! $this->option('apply')) {
            $this->warn('DRY-RUN: no se modificó ningún dato. Usa --apply de forma explícita para aplicar políticas habilitadas.');
            return self::SUCCESS;
        }
        $this->warn('APPLY habilitado. Los backups están excluidos siempre de este comando.');
        $this->line(json_encode($service->prune(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return self::SUCCESS;
    }
}
