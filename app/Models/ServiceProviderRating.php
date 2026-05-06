<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceProviderRating extends Model
{
    protected $fillable = [
        'provider_user_id',
        'client_user_id',
        'stars',
    ];

    protected $casts = [
        'provider_user_id' => 'integer',
        'client_user_id' => 'integer',
        'stars' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function provider()
    {
        return $this->belongsTo(User::class, 'provider_user_id');
    }

    public function client()
    {
        return $this->belongsTo(User::class, 'client_user_id');
    }
}
