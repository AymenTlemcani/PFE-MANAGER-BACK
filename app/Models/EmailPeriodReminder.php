<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailPeriodReminder extends Model
{
    use HasFactory;

    protected $primaryKey = 'reminder_id';

    protected $fillable = [
        'period_id',
        'reminder_date',
        'reminder_number',
        'status',
    ];

    protected $casts = [
        'reminder_date' => 'datetime',
    ];

    public function emailPeriod()
    {
        return $this->belongsTo(EmailPeriod::class, 'period_id');
    }
}