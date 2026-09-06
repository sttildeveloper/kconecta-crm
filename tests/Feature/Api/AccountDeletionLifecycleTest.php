<?php

namespace Tests\Feature\Api;

use App\Models\CoverImage;
use App\Models\MoreImage;
use App\Models\ProviderService;
use App\Models\Service;
use App\Models\User;
use App\Models\UserAddress;
use App\Models\Video;
use App\Services\AccountDeletionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use RuntimeException;
use Tests\TestCase;

class AccountDeletionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_deletion_removes_public_relations_and_owned_files(): void
    {
        $user = $this->user(User::LEVEL_SERVICE_PROVIDER, 'provider-delete@test.dev');
        UserAddress::query()->create(['user_id' => $user->id, 'city' => 'Barcelona']);
        $service = Service::query()->create(['user_id' => $user->id, 'title' => 'Servicio']);
        ProviderService::query()->create(['provider_id' => $user->id, 'service_type_id' => 1]);
        DB::table('service_profile_visits')->insert(['provider_user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('service_contact_clicks')->insert(['provider_user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);

        $photo = 'user_'.$user->id.'_delete.webp';
        $cover = 'providers/'.$user->id.'/cover-delete.webp';
        $gallery = 'providers/'.$user->id.'/gallery-delete.webp';
        $video = 'providers/'.$user->id.'/video-delete.mp4';
        File::ensureDirectoryExists(public_path('img/uploads/providers/'.$user->id));
        File::ensureDirectoryExists(public_path('video/uploads/providers/'.$user->id));
        File::put(public_path('img/photo_profile/'.$photo), 'photo');
        File::put(public_path('img/uploads/'.$cover), 'cover');
        File::put(public_path('img/uploads/'.$gallery), 'gallery');
        File::put(public_path('video/uploads/'.$video), 'video');
        $user->update(['photo' => $photo]);
        CoverImage::query()->create(['provider_user_id' => $user->id, 'service_id' => null, 'url' => $cover]);
        MoreImage::query()->create(['provider_user_id' => $user->id, 'service_id' => null, 'url' => $gallery]);
        Video::query()->create(['provider_user_id' => $user->id, 'service_id' => null, 'url' => $video]);

        Sanctum::actingAs($user);
        $this->deleteJson('/api/me', ['password' => 'DeletePassword123!'])->assertOk();

        $this->assertDatabaseMissing('provider_services', ['provider_id' => $user->id]);
        $this->assertDatabaseMissing('service', ['id' => $service->id]);
        $this->assertDatabaseMissing('user_address', ['user_id' => $user->id]);
        $this->assertDatabaseMissing('cover_image', ['provider_user_id' => $user->id]);
        $this->assertDatabaseMissing('more_images', ['provider_user_id' => $user->id]);
        $this->assertDatabaseMissing('video', ['provider_user_id' => $user->id]);
        $this->assertFileDoesNotExist(public_path('img/photo_profile/'.$photo));
        $this->assertFileDoesNotExist(public_path('img/uploads/'.$cover));
        $this->assertFileDoesNotExist(public_path('img/uploads/'.$gallery));
        $this->assertFileDoesNotExist(public_path('video/uploads/'.$video));
        $this->getJson('/api/providers/'.$user->id)->assertNotFound();
        $this->getJson('/api/service-ratings/provider/'.$user->id)->assertNotFound();
    }

    public function test_client_deletion_is_supported_and_pending_records_are_retained_by_default(): void
    {
        $client = $this->user(User::LEVEL_FINAL_CLIENT, 'client-delete@test.dev');
        $provider = $this->user(User::LEVEL_SERVICE_PROVIDER, 'rating-provider@test.dev');
        DB::table('service_provider_ratings')->insert(['provider_user_id' => $provider->id, 'client_user_id' => $client->id, 'stars' => 5, 'created_at' => now(), 'updated_at' => now()]);
        Sanctum::actingAs($client);
        $this->deleteJson('/api/me', ['password' => 'DeletePassword123!'])->assertOk();
        $this->assertDatabaseHas('service_provider_ratings', ['client_user_id' => $client->id]);
        $this->assertStringStartsWith('deleted+', User::query()->findOrFail($client->id)->email);
    }

    public function test_database_failure_rolls_back_database_and_restores_staged_file(): void
    {
        $user = $this->user(User::LEVEL_SERVICE_PROVIDER, 'rollback-delete@test.dev');
        $photo = 'user_'.$user->id.'_rollback.webp';
        File::put(public_path('img/photo_profile/'.$photo), 'photo');
        $user->update(['photo' => $photo]);
        $request = Request::create('/api/me', 'DELETE', ['reason' => 'rollback']);

        try {
            app(AccountDeletionService::class)->delete($user, $request, fn () => throw new RuntimeException('test rollback'));
            $this->fail('The deletion should have failed.');
        } catch (RuntimeException $exception) {
            $this->assertSame('test rollback', $exception->getMessage());
        }

        $this->assertSame('rollback-delete@test.dev', User::query()->findOrFail($user->id)->email);
        $this->assertFileExists(public_path('img/photo_profile/'.$photo));
        File::delete(public_path('img/photo_profile/'.$photo));
    }

    public function test_service_is_idempotent_for_repeated_requests(): void
    {
        $user = $this->user(User::LEVEL_FINAL_CLIENT, 'repeat-delete@test.dev');
        $request = Request::create('/api/me', 'DELETE');
        $service = app(AccountDeletionService::class);
        $this->assertTrue($service->delete($user, $request));
        $this->assertFalse($service->delete($user->fresh(), $request));
        $this->assertDatabaseCount('account_deletion_audits', 1);
    }

    private function user(int $level, string $email): User
    {
        return User::query()->create(['first_name' => 'Test', 'user_name' => $email, 'email' => $email, 'password' => Hash::make('DeletePassword123!'), 'user_level_id' => $level]);
    }
}
