<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id',
        'created_by',
        'instructor_workshop_id',
        'monthly_period_id',
        'previous_enrollment_id',
        'enrollment_batch_id',
        'enrollment_type',
        'number_of_classes',
        'price_per_quantity',
        'total_amount',
        'pricing_notes',
        'payment_status',
        'payment_method',
        'payment_due_date',
        'payment_date',
        'payment_document',
        'enrollment_date',
        'renewal_status',
        'renewal_deadline',
        'is_renewal',
    ];

    protected $casts = [
        'number_of_classes' => 'integer',
        'price_per_quantity' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'enrollment_date' => 'date',
        'payment_due_date' => 'date',
        'payment_date' => 'date',
        'renewal_deadline' => 'date',
        'is_renewal' => 'boolean',
        'previous_enrollment_id' => 'integer',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($enrollment) {
            // Guardar el usuario que está creando la inscripción individual
            if (Auth::check() && empty($enrollment->created_by)) {
                $enrollment->created_by = Auth::id();
            }
        });
    }

    // Relaciones
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function instructorWorkshop()
    {
        return $this->belongsTo(InstructorWorkshop::class);
    }
    public function monthlyPeriod()
    {
        return $this->belongsTo(MonthlyPeriod::class);
    }
    public function enrollmentClasses()
    {
        return $this->hasMany(EnrollmentClass::class);
    }
    public function previousEnrollment()
    {
        return $this->belongsTo(StudentEnrollment::class, 'previous_enrollment_id');
    }
    public function nextEnrollment()
    {
        return $this->hasOne(StudentEnrollment::class, 'previous_enrollment_id');
    }    
    public function enrollmentBatch()
    {
        return $this->belongsTo(EnrollmentBatch::class);
    }       

    public function getCreatedByNameAttribute()
    {
        if ($this->creator) {
            return $this->creator->name;
        }
        
        return 'Sistema';
    }
}
