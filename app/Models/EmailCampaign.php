<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailCampaign extends Model
{
    protected $primaryKey = 'campaign_id';
    
    protected $fillable = [
        'name',
        'type',
        'target_audience',
        'start_date',
        'end_date',
        'status'
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime'
    ];

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'campaign_id');
    }

    public function reminderSchedules(): HasMany
    {
        return $this->hasMany(ReminderSchedule::class, 'campaign_id');
    }
}
