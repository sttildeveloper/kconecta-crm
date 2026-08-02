<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const MEDIA_TABLES = ['cover_image', 'more_images', 'video'];

    public function up(): void
    {
        if (! Schema::hasTable('provider_media_legacy_links')) {
            Schema::create('provider_media_legacy_links', function (Blueprint $table): void {
                $table->id();
                $table->string('media_table', 40);
                $table->integer('media_id');
                $table->integer('provider_user_id');
                $table->integer('service_id');
                $table->timestamps();

                $table->unique(['media_table', 'media_id'], 'provider_media_legacy_unique');
                $table->index('provider_user_id', 'provider_media_legacy_provider_idx');
            });
        }

        foreach (self::MEDIA_TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'provider_user_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->integer('provider_user_id')->nullable()->after('property_id');
                $blueprint->index('provider_user_id', $table.'_provider_user_idx');
            });
        }

        $this->backfillProviderProfileFields();
        $this->backfillProviderAddresses();
        $this->backfillProviderMedia();

        foreach (['service_profile_visits', 'service_contact_clicks'] as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'provider_user_id')) {
                continue;
            }

            DB::table($table)
                ->whereNotNull('provider_user_id')
                ->update(['service_id' => null]);
        }
    }

    public function down(): void
    {
        foreach (self::MEDIA_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'provider_user_id')) {
                continue;
            }

            if (Schema::hasTable('provider_media_legacy_links')) {
                DB::table('provider_media_legacy_links')
                    ->where('media_table', $table)
                    ->orderBy('id')
                    ->each(function ($legacyLink) use ($table): void {
                        DB::table($table)
                            ->where('id', $legacyLink->media_id)
                            ->update(['service_id' => $legacyLink->service_id]);
                    });
            }

            Schema::table($table, function (Blueprint $blueprint) use ($table): void {
                $blueprint->dropIndex($table.'_provider_user_idx');
                $blueprint->dropColumn('provider_user_id');
            });
        }

        Schema::dropIfExists('provider_media_legacy_links');
    }

    private function backfillProviderProfileFields(): void
    {
        if (! Schema::hasTable('service') || ! Schema::hasTable('user')) {
            return;
        }

        $services = DB::table('service as service')
            ->join('user as provider', 'provider.id', '=', 'service.user_id')
            ->where('provider.user_level_id', 4)
            ->orderByDesc('service.id')
            ->get([
                'service.user_id',
                'service.title',
                'service.description',
                'service.availability',
                'service.page_url',
            ])
            ->unique('user_id');

        foreach ($services as $service) {
            $provider = DB::table('user')->where('id', $service->user_id)->first();
            if (! $provider) {
                continue;
            }

            $updates = [];
            $fieldMap = [
                'provider_title' => 'title',
                'provider_description' => 'description',
                'provider_availability' => 'availability',
                'provider_page_url' => 'page_url',
            ];

            foreach ($fieldMap as $providerField => $serviceField) {
                if (! Schema::hasColumn('user', $providerField)) {
                    continue;
                }

                $currentValue = trim((string) ($provider->{$providerField} ?? ''));
                $legacyValue = trim((string) ($service->{$serviceField} ?? ''));
                if ($currentValue === '' && $legacyValue !== '') {
                    $updates[$providerField] = $legacyValue;
                }
            }

            if ($updates !== []) {
                DB::table('user')->where('id', $service->user_id)->update($updates);
            }
        }
    }

    private function backfillProviderAddresses(): void
    {
        if (! Schema::hasTable('service_address') || ! Schema::hasTable('user_address')) {
            return;
        }

        $addresses = DB::table('service_address as address')
            ->join('service as service', 'service.id', '=', 'address.service_id')
            ->join('user as provider', 'provider.id', '=', 'service.user_id')
            ->where('provider.user_level_id', 4)
            ->orderByDesc('address.id')
            ->get(['address.*', 'service.user_id'])
            ->unique('user_id');

        $fields = [
            'address',
            'street_name',
            'street_number',
            'neighborhood',
            'city',
            'province',
            'postal_code',
            'state',
            'country',
            'latitude',
            'longitude',
            'additional_info',
        ];

        foreach ($addresses as $legacyAddress) {
            $existing = DB::table('user_address')->where('user_id', $legacyAddress->user_id)->first();
            $payload = [];

            foreach ($fields as $field) {
                $currentValue = trim((string) ($existing->{$field} ?? ''));
                $legacyValue = trim((string) ($legacyAddress->{$field} ?? ''));
                if ($currentValue === '' && $legacyValue !== '') {
                    $payload[$field] = $legacyValue;
                }
            }

            if ($existing && $payload !== []) {
                DB::table('user_address')->where('id', $existing->id)->update($payload);
            } elseif (! $existing && $payload !== []) {
                DB::table('user_address')->insert(array_merge($payload, [
                    'user_id' => $legacyAddress->user_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }

    private function backfillProviderMedia(): void
    {
        if (! Schema::hasTable('service') || ! Schema::hasTable('user')) {
            return;
        }

        $providerByServiceId = DB::table('service as service')
            ->join('user as provider', 'provider.id', '=', 'service.user_id')
            ->where('provider.user_level_id', 4)
            ->pluck('service.user_id', 'service.id')
            ->mapWithKeys(fn ($providerId, $serviceId) => [(int) $serviceId => (int) $providerId])
            ->all();

        foreach (self::MEDIA_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'provider_user_id')) {
                continue;
            }

            foreach ($providerByServiceId as $serviceId => $providerId) {
                $mediaIds = DB::table($table)
                    ->where('service_id', $serviceId)
                    ->whereNull('property_id')
                    ->pluck('id');

                foreach ($mediaIds as $mediaId) {
                    DB::table('provider_media_legacy_links')->updateOrInsert(
                        ['media_table' => $table, 'media_id' => (int) $mediaId],
                        [
                            'provider_user_id' => $providerId,
                            'service_id' => $serviceId,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]
                    );
                }

                DB::table($table)
                    ->where('service_id', $serviceId)
                    ->whereNull('property_id')
                    ->update([
                        'provider_user_id' => $providerId,
                        'service_id' => null,
                    ]);
            }
        }
    }
};
