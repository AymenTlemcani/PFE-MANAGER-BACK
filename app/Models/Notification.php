<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $primaryKey = 'notification_id';

    protected $fillable = [
        'user_id',
        'message',
        'notification_type',
        'sent_date',
        'is_read',
        'related_entity_type',
        'related_entity_id'
    ];

    protected $casts = [
        'sent_date' => 'datetime',
        'is_read' => 'boolean'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}