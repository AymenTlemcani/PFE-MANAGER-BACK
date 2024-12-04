<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserImportLog extends Model
{
    use HasFactory;

    protected $primaryKey = 'import_log_id';

    protected $fillable = [
        'imported_by',
        'import_type',
        'total_records_imported',
        'successful_imports',
        'failed_imports',
        'import_date',
        'import_file_name',
        'import_status'
    ];

    protected $casts = [
        'import_date' => 'datetime'
    ];

    public function importedByUser()
    {
        return $this->belongsTo(User::class, 'imported_by');
    }
}