<?php

namespace App\Support;

class ProviderGalleryRules
{
    public static function maximum(): int
    {
        return max(1, (int) config('uploads.provider_gallery_max_images', 5));
    }

    public static function projectedCount(
        iterable $existingImages,
        array $deletionIds,
        int $newImageCount,
        bool $replaceDefaultImages
    ): int {
        $deletionIds = collect($deletionIds)
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->flip();

        $remainingImages = collect($existingImages)->filter(function ($image) use ($deletionIds, $replaceDefaultImages): bool {
            if ($deletionIds->has((int) data_get($image, 'id'))) {
                return false;
            }

            return ! ($replaceDefaultImages && (bool) data_get($image, 'is_provider_default'));
        });

        return $remainingImages->count() + max(0, $newImageCount);
    }

    public static function limitMessage(): string
    {
        return 'La galeria admite un maximo de '.self::maximum().' imagenes.';
    }
}
