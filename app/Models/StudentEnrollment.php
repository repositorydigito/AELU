<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StudentEnrollment extends Model
{
    protected $fillable = [
        'student_id',
        'instructor_workshop_id',
        'monthly_period_id',
        'previous_enrollment_id',
        'enrollment_type',
        'number_of_classes',
        'price_per_quantity',
        'total_amount',
        'payment_status',
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
        'renewal_deadline' => 'date',
        'is_renewal' => 'boolean',
        'previous_enrollment_id' => 'integer',
    ];

    // Relaciones
    public function student()
    {
        return $this->belongsTo(Student::class);
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


    // Relaciones indirectas
    public function instructor()
    {
        return $this->hasOneThrough(
            Instructor::class,
            InstructorWorkshop::class,
            'id',
            'id', 
            'instructor_workshop_id',
            'instructor_id'
        );
    }
    public function workshop()
    {
        return $this->hasOneThrough(
            Workshop::class,
            InstructorWorkshop::class,
            'id',
            'id',
            'instructor_workshop_id', 
            'workshop_id'
        );
    }

    // Métodos
    public function createRenewalForNextMonth()
    {
        $nextPeriod = $this->monthlyPeriod->getNextPeriod();
        if (!$nextPeriod) {
            return false;
        }

        // Verificar si ya existe una renovación
        if ($this->nextEnrollment) {
            return $this->nextEnrollment;
        }

        // Verificar cupos disponibles
        $capacity = WorkshopCapacity::where('instructor_workshop_id', $this->instructor_workshop_id)
                                   ->where('monthly_period_id', $nextPeriod->id)
                                   ->first();

        if (!$capacity || $capacity->current_enrolled >= $capacity->max_capacity) {
            return false; // No hay cupos disponibles
        }

        // Crear la renovación
        $renewal = static::create([
            'student_id' => $this->student_id,
            'instructor_workshop_id' => $this->instructor_workshop_id,
            'monthly_period_id' => $nextPeriod->id,
            'enrollment_type' => $this->enrollment_type,
            'number_of_classes' => $this->number_of_classes,
            'price_per_quantity' => $this->price_per_quantity,
            'total_amount' => $this->total_amount,
            'payment_status' => 'pending',
            'enrollment_date' => now(),
            'renewal_status' => 'pending',
            'renewal_deadline' => $this->monthlyPeriod->renewal_end_date,
            'is_renewal' => true,
            'previous_enrollment_id' => $this->id
        ]);

        // Actualizar contador de renovaciones pendientes
        $capacity->increment('pending_renewals');

        return $renewal;
    }

    public function confirmRenewal()
    {
        if ($this->renewal_status !== 'pending' || !$this->is_renewal) {
            return false;
        }

        $this->update([
            'renewal_status' => 'confirmed',
            'payment_status' => 'completed'
        ]);

        // Actualizar contadores de capacidad
        $capacity = WorkshopCapacity::where('instructor_workshop_id', $this->instructor_workshop_id)
                                   ->where('monthly_period_id', $this->monthly_period_id)
                                   ->first();

        if ($capacity) {
            $capacity->decrement('pending_renewals');
            $capacity->increment('current_enrolled');
        }

        return true;
    }

    public function cancelRenewal()
    {
        if ($this->renewal_status !== 'pending' || !$this->is_renewal) {
            return false;
        }

        $this->update([
            'renewal_status' => 'cancelled'
        ]);

        // Liberar el cupo
        $capacity = WorkshopCapacity::where('instructor_workshop_id', $this->instructor_workshop_id)
                                   ->where('monthly_period_id', $this->monthly_period_id)
                                   ->first();

        if ($capacity) {
            $capacity->decrement('pending_renewals');
        }

        return true;
    }
}
