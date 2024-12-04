<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    use HasFactory;

    protected $primaryKey = 'company_id';

    protected $fillable = [
        'user_id',
        'company_name',
        'contact_name',
        'contact_surname',
        'industry',
        'address'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}