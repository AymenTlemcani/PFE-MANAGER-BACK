<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailPeriodTemplate extends Model
{
    use HasFactory;

    protected $primaryKey = 'template_id';

    protected $fillable = [
        'period_id',
        'template_type',
        'template_content',
        'subject',
        'language',
    ];

    public function emailPeriod()
    {
        return $this->belongsTo(EmailPeriod::class, 'period_id');
    }
}