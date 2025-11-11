<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Workshop extends Model
{
    protected $fillable = [
        'name',
        'description',
        'standard_monthly_fee',
        'pricing_surcharge_percentage',
        'instructor_id',
        'delegate_user_id',
        'day_of_week',
        'start_time',
        'duration',
        'capacity',
        'number_of_classes',
        'place',
        'modality',
        'monthly_period_id',
        'additional_comments',
    ];

    protected $casts = [
        'standard_monthly_fee' => 'decimal:2',
        'pricing_surcharge_percentage' => 'decimal:2',
        'duration' => 'integer',
        'capacity' => 'integer',
        'number_of_classes' => 'integer',
        'day_of_week' => 'array',
    ];

    public function instructorWorkshops()
    {
        return $this->hasMany(InstructorWorkshop::class);
    }

    public function instructor()
    {
        return $this->belongsTo(Instructor::class);
    }

    public function delegate()
    {
        return $this->belongsTo(User::class, 'delegate_user_id');
    }

    public function workshopPricings()
    {
        return $this->hasMany(WorkshopPricing::class);
    }

    public function enrollments()
    {
        return $this->hasManyThrough(StudentEnrollment::class, InstructorWorkshop::class);
    }

    public function workshopClasses()
    {
        return $this->hasMany(WorkshopClass::class);
    }

    public function monthlyPeriod()
    {
        return $this->belongsTo(MonthlyPeriod::class);
    }

    /**
     * Obtiene el multiplicador de recargo
     */
    public function getSurchargeMultiplierAttribute(): float
    {
        return 1 + ($this->pricing_surcharge_percentage / 100);
    }

    /**
     * Obtiene el precio base por clase (sin recargo)
     */
    public function getBasePerClassAttribute(): float
    {
        $numberOfClasses = $this->number_of_classes ?? 4;
        return $this->standard_monthly_fee / $numberOfClasses;
    }

    /**
     * Obtiene el precio por clase con recargo aplicado
     */
    public function getPricePerClassWithSurchargeAttribute(): float
    {
        return $this->base_per_class * $this->surcharge_multiplier;
    }

    /**
     * Verifica si el taller tiene tarifas generadas
     */
    public function hasPricingsGenerated(): bool
    {
        return $this->workshopPricings()->count() > 0;
    }

    /**
     * Obtiene las tarifas para instructores voluntarios
     */
    public function getVolunteerPricings()
    {
        return $this->workshopPricings()->where('for_volunteer_workshop', true)->orderBy('number_of_classes')->get();
    }

    /**
     * Obtiene las tarifas para instructores no voluntarios
     */
    public function getNonVolunteerPricings()
    {
        return $this->workshopPricings()->where('for_volunteer_workshop', false)->orderBy('number_of_classes')->get();
    }

    /**
     * Obtiene la tarifa por cantidad de clases y tipo de instructor
     */
    public function getPricingForClasses(int $numberOfClasses, bool $isVolunteerWorkshop): ?WorkshopPricing
    {
        return $this->workshopPricings()
            ->where('number_of_classes', $numberOfClasses)
            ->where('for_volunteer_workshop', $isVolunteerWorkshop)
            ->first();
    }

    protected function endTime(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (! $this->start_time || ! $this->duration) {
                    return null;
                }

                $startTime = Carbon::parse($this->start_time);

                return $startTime->addMinutes($this->duration)->format('H:i:s');
            }
        );
    }

    public function hasEnrollments(): bool
    {
        return $this->instructorWorkshops()
            ->whereHas('enrollments')
            ->exists();
    }

    /**
     * Calcula los cupos disponibles para un período específico
     */
    public function getAvailableSpotsForPeriod($monthlyPeriodId): int
    {
        $totalCapacity = $this->capacity;

        // Contar inscripciones activas para este período
        $currentEnrollments = $this->enrollments()
            ->where('monthly_period_id', $monthlyPeriodId)
            ->whereIn('payment_status', ['completed', 'pending'])
            ->distinct('student_id')
            ->count();

        return max(0, $totalCapacity - $currentEnrollments);
    }

    /**
     * Verifica si el taller está lleno para un período específico
     */
    public function isFullForPeriod($monthlyPeriodId): bool
    {
        return $this->getAvailableSpotsForPeriod($monthlyPeriodId) <= 0;
    }

    /**
     * Obtiene información completa de cupos para un período específico
     */
    public function getCapacityInfoForPeriod($monthlyPeriodId): array
    {
        $totalCapacity = $this->capacity;
        $currentEnrollments = $this->enrollments()
            ->where('monthly_period_id', $monthlyPeriodId)
            ->whereIn('payment_status', ['completed', 'pending'])
            ->distinct('student_id')
            ->count();

        $availableSpots = max(0, $totalCapacity - $currentEnrollments);

        return [
            'total_capacity' => $totalCapacity,
            'current_enrollments' => $currentEnrollments,
            'available_spots' => $availableSpots,
            'is_full' => $availableSpots <= 0,
            'is_almost_full' => $availableSpots <= 3 && $availableSpots > 0,
        ];
    }
}
