<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentReport extends Model
{
    protected $fillable = [
        'reporter_user_id', 'reported_user_id', 'content_type', 'content_id',
        'reason', 'details', 'active_fingerprint', 'status', 'moderator_user_id', 'resolution_note', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function reporter() { return $this->belongsTo(User::class, 'reporter_user_id'); }
    public function reportedUser() { return $this->belongsTo(User::class, 'reported_user_id'); }
    public function moderator() { return $this->belongsTo(User::class, 'moderator_user_id'); }
}
