<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_RESOLVED = 'resolved';
    public const STATUS_CLOSED = 'closed';

    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';

    protected $table = 'tickets';

    protected $fillable = [
        'user_id',
        'property_id',
        'subject',
        'description',
        'status',
        'priority',
    ];

    protected $casts = [
        'user_id' => 'integer',
        'property_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con el usuario creador del ticket.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Relación opcional con la propiedad/caso asociado.
     */
    public function property()
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    /**
     * Relación con los mensajes del hilo del ticket.
     */
    public function messages()
    {
        return $this->hasMany(TicketMessage::class, 'ticket_id');
    }
}
