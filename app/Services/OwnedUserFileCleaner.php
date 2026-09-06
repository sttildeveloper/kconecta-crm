<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class OwnedUserFileCleaner
{
    /** @var array<int, array{original:string, staged:string}> */
    private array $staged = [];

    public function stageProfilePhoto(User $user): void
    {
        $name = trim((string) $user->photo);
        if ($name === '' || basename($name) !== $name) {
            return;
        }

        if (DB::table('user')->where('photo', $name)->where('id', '<>', $user->id)->exists()) {
            return;
        }

        $this->stage(public_path('img/photo_profile'), $name);
    }

    /** @param array<int, string> $paths */
    public function stageProviderImages(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if ($this->isExclusivelyReferencedByDeletionSet($path, ['cover_image', 'more_images'])) {
                $this->stage(public_path('img/uploads'), $path);
            }
        }
    }

    /** @param array<int, string> $paths */
    public function stageProviderVideos(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if ($this->isExclusivelyReferencedByDeletionSet($path, ['video'])) {
                $this->stage(public_path('video/uploads'), $path);
            }
        }
    }

    public function commit(): void
    {
        foreach ($this->staged as $file) {
            File::delete($file['staged']);
        }
        $this->cleanupStagingDirectory();
        $this->staged = [];
    }

    public function rollback(): void
    {
        foreach (array_reverse($this->staged) as $file) {
            if (File::exists($file['staged']) && ! File::exists($file['original'])) {
                File::ensureDirectoryExists(dirname($file['original']));
                File::move($file['staged'], $file['original']);
            }
        }
        $this->cleanupStagingDirectory();
        $this->staged = [];
    }

    private function stage(string $root, string $relativePath): void
    {
        $relativePath = str_replace('\\', '/', trim($relativePath));
        if ($relativePath === '' || str_starts_with($relativePath, '/') || str_contains($relativePath, '../')) {
            return;
        }

        $root = rtrim(str_replace('\\', '/', $root), '/');
        $original = str_replace('\\', '/', $root.'/'.$relativePath);
        $realRoot = realpath($root);
        $realFile = realpath($original);
        if ($realRoot === false || $realFile === false || ! is_file($realFile)) {
            return;
        }

        $prefix = rtrim(str_replace('\\', '/', $realRoot), '/').'/';
        if (! str_starts_with(str_replace('\\', '/', $realFile), $prefix)) {
            Log::warning('account_deletion_rejected_unsafe_file', ['path' => $relativePath]);
            return;
        }

        $stagingDirectory = storage_path('app/account-deletion-staging/'.Str::uuid());
        File::ensureDirectoryExists($stagingDirectory);
        $staged = $stagingDirectory.'/'.basename($realFile);
        File::move($realFile, $staged);
        $this->staged[] = ['original' => $realFile, 'staged' => $staged];
    }

    /**
     * The caller only supplies paths from records that it is about to delete.
     * A reference in another media table means the physical file is shared and
     * must be preserved.
     *
     * @param array<int, string> $tablesBeingDeleted
     */
    private function isExclusivelyReferencedByDeletionSet(string $path, array $tablesBeingDeleted): bool
    {
        $references = 0;
        foreach (['cover_image', 'more_images', 'video'] as $table) {
            if (! Schema::hasTable($table)) continue;
            if (! in_array($table, $tablesBeingDeleted, true) && DB::table($table)->where('url', $path)->exists()) {
                return false;
            }
            if (in_array($table, $tablesBeingDeleted, true)) {
                $references += DB::table($table)->where('url', $path)->count();
            }
        }

        // A duplicated path may belong to another user. Prefer an orphan file
        // over deleting content whose ownership is not exclusive.
        return $references === 1;
    }

    private function cleanupStagingDirectory(): void
    {
        foreach ($this->staged as $file) {
            $directory = dirname($file['staged']);
            if (is_dir($directory)) {
                @rmdir($directory);
            }
        }
    }
}
