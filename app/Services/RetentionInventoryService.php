<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RetentionInventoryService
{
    public function inventory(): array
    {
        return [
            'metrics' => $this->group(['service_profile_visits', 'service_contact_clicks'], 'metrics_days'),
            'ip_addresses' => $this->ipInventory(),
            'tickets' => $this->group(['tickets'], 'tickets_days'),
            'audits' => $this->group(['account_deletion_audits'], 'audits_days'),
            'backups' => [
                'enabled' => false,
                'note' => 'Solo inventario documental; este comando nunca accede ni elimina backups.',
                'policy' => config('compliance.retention.backups'),
            ],
        ];
    }

    public function prune(): array
    {
        $results = [];
        $results['metrics'] = $this->deleteGroup(['service_profile_visits', 'service_contact_clicks'], 'metrics_days');
        $results['tickets'] = $this->deleteTickets();
        $results['audits'] = $this->deleteGroup(['account_deletion_audits'], 'audits_days');
        $results['ip_addresses'] = $this->redactIps();
        $results['backups'] = 0;

        return $results;
    }

    private function group(array $tables, string $configKey): array
    {
        $days = $this->days($configKey);
        $counts = [];
        foreach ($tables as $table) {
            $counts[$table] = $days && Schema::hasTable($table)
                ? DB::table($table)->where('created_at', '<', now()->subDays($days))->count()
                : 0;
        }
        return ['enabled' => $days !== null, 'days' => $days, 'eligible' => $counts];
    }

    private function ipInventory(): array
    {
        $days = $this->days('ip_days');
        $counts = [];
        foreach (['service_profile_visits', 'service_contact_clicks', 'legal_acceptances', 'account_deletion_audits'] as $table) {
            $column = $table === 'account_deletion_audits' ? 'requested_ip' : 'ip_address';
            $counts[$table] = $days && Schema::hasTable($table) && Schema::hasColumn($table, $column)
                ? DB::table($table)->whereNotNull($column)->where('created_at', '<', now()->subDays($days))->count()
                : 0;
        }
        return ['enabled' => $days !== null, 'days' => $days, 'eligible' => $counts];
    }

    private function deleteGroup(array $tables, string $key): int
    {
        $days = $this->days($key);
        if (! $days) return 0;
        $count = 0;
        foreach ($tables as $table) {
            if (Schema::hasTable($table)) $count += DB::table($table)->where('created_at', '<', now()->subDays($days))->delete();
        }
        return $count;
    }

    private function deleteTickets(): int
    {
        $days = $this->days('tickets_days');
        if (! $days || ! Schema::hasTable('tickets')) return 0;
        $ids = DB::table('tickets')->where('created_at', '<', now()->subDays($days))->pluck('id');
        if ($ids->isNotEmpty() && Schema::hasTable('ticket_messages')) DB::table('ticket_messages')->whereIn('ticket_id', $ids)->delete();
        return DB::table('tickets')->whereIn('id', $ids)->delete();
    }

    private function redactIps(): int
    {
        $days = $this->days('ip_days');
        if (! $days) return 0;
        $count = 0;
        foreach (['service_profile_visits', 'service_contact_clicks', 'legal_acceptances', 'account_deletion_audits'] as $table) {
            $column = $table === 'account_deletion_audits' ? 'requested_ip' : 'ip_address';
            if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
                $count += DB::table($table)->whereNotNull($column)->where('created_at', '<', now()->subDays($days))->update([$column => null]);
            }
        }
        return $count;
    }

    private function days(string $key): ?int
    {
        $value = config('compliance.retention.'.$key);
        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }
}
