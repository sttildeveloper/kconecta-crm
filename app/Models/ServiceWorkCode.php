<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceWorkCode extends Model
{
    protected $fillable = [
        'provider_user_id',
        'code',
        'is_used',
        'used_by_user_id',
        'used_at',
    ];

    protected $casts = [
        'provider_user_id' => 'integer',
        'is_used' => 'boolean',
        'used_by_user_id' => 'integer',
        'used_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_user_id');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by_user_id');
    }
}
