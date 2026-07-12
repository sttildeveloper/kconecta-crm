<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('user')) {
            Schema::table('user', function (Blueprint $table) {
                if (! Schema::hasColumn('user', 'provider_title')) {
                    $table->string('provider_title')->nullable()->after('photo');
                }
                if (! Schema::hasColumn('user', 'provider_description')) {
                    $table->text('provider_description')->nullable()->after('provider_title');
                }
                if (! Schema::hasColumn('user', 'provider_page_url')) {
                    $table->string('provider_page_url')->nullable()->after('provider_description');
                }
                if (! Schema::hasColumn('user', 'provider_availability')) {
                    $table->string('provider_availability', 100)->nullable()->after('provider_page_url');
                }
            });
        }

        foreach (['cover_image', 'more_images', 'video', 'service_types'] as $tableName) {
            if (! Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'user_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->integer('user_id')->nullable()->after('service_id');
                $table->index('user_id');
            });
        }

        if (Schema::hasTable('service') && Schema::hasTable('user')) {
            $providerSeedRows = DB::table('service as s')
                ->join('user as u', 'u.id', '=', 's.user_id')
                ->where('u.user_level_id', 4)
                ->select(
                    's.id',
                    's.user_id',
                    's.title',
                    's.description',
                    's.page_url',
                    's.availability',
                    'u.provider_title',
                    'u.provider_description',
                    'u.provider_page_url',
                    'u.provider_availability'
                )
                ->orderBy('s.id')
                ->get();

            $seededUsers = [];
            foreach ($providerSeedRows as $row) {
                $userId = (int) $row->user_id;
                if (in_array($userId, $seededUsers, true)) {
                    continue;
                }

                $payload = [];
                if (empty($row->provider_title) && ! empty($row->title)) {
                    $payload['provider_title'] = $row->title;
                }
                if (empty($row->provider_description) && ! empty($row->description)) {
                    $payload['provider_description'] = $row->description;
                }
                if (empty($row->provider_page_url) && ! empty($row->page_url)) {
                    $payload['provider_page_url'] = $row->page_url;
                }
                if (empty($row->provider_availability) && ! empty($row->availability)) {
                    $payload['provider_availability'] = $row->availability;
                }

                if (! empty($payload)) {
                    DB::table('user')->where('id', $userId)->update($payload);
                }

                $seededUsers[] = $userId;
            }

            foreach (['cover_image', 'more_images', 'video', 'service_types'] as $tableName) {
                if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'user_id')) {
                    continue;
                }

                DB::table($tableName)
                    ->select('id', 'service_id')
                    ->whereNull('user_id')
                    ->whereNotNull('service_id')
                    ->orderBy('id')
                    ->chunk(500, function ($rows) use ($tableName): void {
                        $serviceIds = collect($rows)
                            ->pluck('service_id')
                            ->filter()
                            ->map(fn ($id) => (int) $id)
                            ->unique()
                            ->values()
                            ->all();

                        if (empty($serviceIds)) {
                            return;
                        }

                        $serviceUsers = DB::table('service')
                            ->whereIn('id', $serviceIds)
                            ->pluck('user_id', 'id');

                        foreach ($rows as $row) {
                            $serviceId = (int) $row->service_id;
                            $userId = $serviceUsers[$serviceId] ?? null;
                            if ($userId === null) {
                                continue;
                            }

                            DB::table($tableName)
                                ->where('id', (int) $row->id)
                                ->update(['user_id' => (int) $userId]);
                        }
                    });
            }
        }
    }

    public function down(): void
    {
        foreach (['cover_image', 'more_images', 'video', 'service_types'] as $tableName) {
            if (! Schema::hasTable($tableName) || ! Schema::hasColumn($tableName, 'user_id')) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) {
                $table->dropIndex(['user_id']);
                $table->dropColumn('user_id');
            });
        }

        if (Schema::hasTable('user')) {
            Schema::table('user', function (Blueprint $table) {
                foreach (['provider_title', 'provider_description', 'provider_page_url', 'provider_availability'] as $column) {
                    if (Schema::hasColumn('user', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
