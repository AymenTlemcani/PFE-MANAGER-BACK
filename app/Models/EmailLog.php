<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailLog extends Model
{
    protected $primaryKey = 'log_id';
    
    protected $fillable = [
        'campaign_id',
        'template_id',
        'recipient_email',
        'user_id',
        'sent_at',
        'status',
        'error_message',
        'template_data'
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'template_data' => 'array'
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
