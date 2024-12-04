<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailPeriod extends Model
{
    use HasFactory;

    protected $primaryKey = 'period_id';

    protected $fillable = [
        'period_name',
        'target_audience',
        'start_date',
        'closing_date',
        'status',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'closing_date' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function reminders()
    {
        return $this->hasMany(EmailPeriodReminder::class, 'period_id');
    }

    public function templates()
    {
        return $this->hasMany(EmailPeriodTemplate::class, 'period_id');
    }
}