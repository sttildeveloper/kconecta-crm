<?php

namespace App\Console\Commands;

use App\Services\RetentionInventoryService;
use Illuminate\Console\Command;

class ComplianceRetentionInventory extends Command
{
    protected $signature = 'compliance:retention-inventory';
    protected $description = 'Muestra políticas y registros candidatos sin modificar datos ni backups.';

    public function handle(RetentionInventoryService $service): int
    {
        $this->line(json_encode($service->inventory(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return self::SUCCESS;
    }
}
