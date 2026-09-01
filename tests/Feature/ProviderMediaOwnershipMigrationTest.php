<?php

namespace Tests\Feature;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\Service;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProviderMediaOwnershipMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_backfill_moves_only_provider_media_away_from_service_id(): void
    {
        $provider = $this->makeUser(User::LEVEL_SERVICE_PROVIDER, 'provider-backfill@test.dev');
        $providerService = Service::query()->create([
            'title' => 'Perfil legacy',
            'description' => 'Descripcion legacy',
            'availability' => '24/7',
            'user_id' => (int) $provider->id,
        ]);
        $cover = CoverImage::query()->create([
            'service_id' => (int) $providerService->id,
            'url' => 'legacy-cover.webp',
        ]);
        $gallery = MoreImage::query()->create([
            'service_id' => (int) $providerService->id,
            'url' => 'legacy-gallery.webp',
        ]);
        $video = Video::query()->create([
            'service_id' => (int) $providerService->id,
            'url' => 'legacy-video.mp4',
        ]);

        $agent = $this->makeUser(User::LEVEL_AGENT, 'agent-backfill@test.dev');
        $agentService = Service::query()->create([
            'title' => 'Registro no proveedor',
            'user_id' => (int) $agent->id,
        ]);
        $unrelatedCover = CoverImage::query()->create([
            'service_id' => (int) $agentService->id,
            'url' => 'unrelated.webp',
        ]);

        $migration = require database_path('migrations/2026_08_02_170000_link_provider_media_to_users.php');
        $migration->up();

        foreach ([$cover->fresh(), $gallery->fresh(), $video->fresh()] as $media) {
            $this->assertSame((int) $provider->id, (int) $media->provider_user_id);
            $this->assertNull($media->service_id);
        }

        $this->assertNull($unrelatedCover->fresh()->provider_user_id);
        $this->assertSame((int) $agentService->id, (int) $unrelatedCover->fresh()->service_id);
        $this->assertDatabaseHas('provider_media_legacy_links', [
            'media_table' => 'cover_image',
            'media_id' => (int) $cover->id,
            'provider_user_id' => (int) $provider->id,
            'service_id' => (int) $providerService->id,
        ]);
        $this->assertDatabaseHas('service', ['id' => (int) $providerService->id]);

        $positionMigration = require database_path('migrations/2026_09_02_120000_add_position_to_more_images_table.php');
        $positionMigration->down();
        $migration->down();

        $this->assertSame((int) $providerService->id, (int) $cover->fresh()->service_id);
        $this->assertSame((int) $providerService->id, (int) $gallery->fresh()->service_id);
        $this->assertSame((int) $providerService->id, (int) $video->fresh()->service_id);
        $this->assertFalse(Schema::hasColumn('cover_image', 'provider_user_id'));
        $this->assertFalse(Schema::hasTable('provider_media_legacy_links'));

        $migration->up();
        $positionMigration->up();
    }

    private function makeUser(int $levelId, string $email): User
    {
        return User::query()->create([
            'first_name' => 'Test',
            'last_name' => 'User',
            'user_name' => 'user-'.md5($email),
            'email' => $email,
            'phone' => '600000000',
            'password' => Hash::make('password'),
            'user_level_id' => $levelId,
            'email_verified_at' => now(),
        ]);
    }
}
