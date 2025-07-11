<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Workshop extends Model
{
    protected $fillable = [
        'name',
        'description',
        'standard_monthly_fee',
        'pricing_surcharge_percentage',
    ];

    protected $casts = [
        'standard_monthly_fee' => 'decimal:2',
        'pricing_surcharge_percentage' => 'decimal:2',
    ];

    public function instructorWorkshops()
    {
        return $this->hasMany(InstructorWorkshop::class);
    }
    public function instructors()
    {
        return $this->belongsToMany(Instructor::class, 'instructor_workshops')
                    ->withPivot('day_of_week', 'start_time', 'end_time', 'is_active')
                    ->withTimestamps();
    }
    public function workshopPricings()
    {
        return $this->hasMany(WorkshopPricing::class);
    }
    public function enrollments()
    {
        return $this->hasManyThrough(StudentEnrollment::class, InstructorWorkshop::class);
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
        return $this->standard_monthly_fee / 4;
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
     * Regenera las tarifas del taller
     */
    public function regeneratePricings(): void
    {
        // Disparar el observer manualmente
        $observer = new \App\Observers\WorkshopObserver();
        $observer->syncPricing($this);
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
}
