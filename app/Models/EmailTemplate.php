<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EmailTemplate extends Model
{
    protected $primaryKey = 'template_id';
    
    protected $fillable = [
        'name',
        'subject',
        'content',
        'description',
        'placeholders',
        'type',
        'language',
        'is_active'
    ];

    protected $casts = [
        'placeholders' => 'array',
        'is_active' => 'boolean'
    ];

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class, 'template_id');
    }

    public function reminderSchedules(): HasMany
    {
        return $this->hasMany(ReminderSchedule::class, 'template_id');
    }
}
