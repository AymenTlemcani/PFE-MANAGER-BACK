<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use HasFactory;

    protected $primaryKey = 'student_id';

    protected $fillable = [
        'user_id',
        'name',
        'surname',
        'master_option',
        'overall_average',
        'admission_year'
    ];

    protected $casts = [
        'overall_average' => 'decimal:2',
        'admission_year' => 'integer'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function pairs()
    {
        return $this->hasMany(StudentPair::class, 'student1_id')
            ->orWhere('student2_id', $this->student_id);
    }
}