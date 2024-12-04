<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPair extends Model
{
    use HasFactory;

    protected $primaryKey = 'pair_id';

    protected $fillable = [
        'student1_id',
        'student2_id',
        'status',
        'proposed_date',
        'updated_date'
    ];

    protected $casts = [
        'proposed_date' => 'datetime',
        'updated_date' => 'datetime'
    ];

    public function student1()
    {
        return $this->belongsTo(Student::class, 'student1_id');
    }

    public function student2()
    {
        return $this->belongsTo(Student::class, 'student2_id');
    }
}