<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Affidavit extends Model
{
    protected $fillable = [
        'student_id',
        'instructor_id',
        'digital_signature_and_fingerprint_path',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }
}
