<?php

namespace App\Models;

class MoreImage extends LegacyModel
{
    protected $table = 'more_images';

    protected $casts = [
        'position' => 'integer',
        'is_provider_default' => 'boolean',
    ];
}
