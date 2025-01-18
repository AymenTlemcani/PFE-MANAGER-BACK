<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReminderSchedule extends Model
{
    protected $primaryKey = 'schedule_id';
    
    protected $fillable = [
        'campaign_id',
        'template_id',
        'days_before_deadline',
        'send_time',
        'is_active'
    ];

    protected $casts = [
        'send_time' => 'datetime',
        'is_active' => 'boolean'
    ];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(EmailCampaign::class, 'campaign_id');
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(EmailTemplate::class, 'template_id');
    }
}
