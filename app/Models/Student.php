<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'last_names',
        'first_names',
        'photo',
        'document_type',
        'document_number',
        'birth_date',
        'nationality',
        'student_code',
        'category_partner',
        'cell_phone',
        'home_phone',
        'district',
        'address',
        'emergency_contact_name',
        'emergency_contact_relationship',
        'emergency_contact_phone',
        'maintenance_period_id',
    ];

    protected $casts = [
        'birth_date' => 'date',
    ];

    public function medicalRecord()
    {
        return $this->hasOne(MedicalRecord::class);
    }

    public function maintenancePeriod()
    {
        return $this->belongsTo(MaintenancePeriod::class);
    }

    public function medications()
    {
        return $this->hasManyThrough(StudentMedication::class, MedicalRecord::class);
    }

    public function affidavit()
    {
        return $this->hasOne(Affidavit::class);
    }

    public function studentEnrollments()
    {
        return $this->hasMany(StudentEnrollment::class);
    }

    public function workshops(): BelongsToMany
    {
        return $this->belongsToMany(Workshop::class, 'student_enrollments', 'student_id', 'workshop_id');
    }

    public function getFullNameAttribute(): string
    {
        return $this->last_names.' '.$this->first_names;
    }

    public function getFullNameWithCodeAttribute(): string
    {
        return "{$this->full_name} - {$this->student_code}";
    }

    public function getAgeAttribute(): int
    {
        return $this->birth_date ? $this->birth_date->age : 0;
    }

    public function isExemptFromMaintenance(): bool
    {
        return in_array($this->category_partner, [
            'Vitalicios',
            'Hijo de Fundador',
            'Transitorio Mayor de 75',
        ]);
    }

    public function isMaintenanceCurrent(): bool
    {
        // Si es categoría exonerada, siempre está al día
        if ($this->isExemptFromMaintenance()) {
            return true;
        }

        // Si no tiene período asignado, considerarlo como no al día
        if (! $this->maintenancePeriod) {
            return false;
        }

        $currentPeriod = MaintenancePeriod::getCurrentPeriod();

        // Si no existe el período actual, return false por seguridad
        if (! $currentPeriod) {
            return false;
        }

        return $this->maintenancePeriod->isEqualOrAfter($currentPeriod);
    }

    public function getMaintenanceStatusText(): string
    {
        if ($this->isExemptFromMaintenance()) {
            return 'Exonerado';
        }

        if (! $this->maintenancePeriod) {
            return 'Sin asignar';
        }

        return $this->maintenancePeriod->name;
    }

    public function getIsMaintenanceCurrentAttribute(): bool
    {
        return $this->isMaintenanceCurrent();
    }

    public function getInscriptionMultiplierAttribute(): float
    {
        return match ($this->category_partner) {
            'PRE PAMA 50+' => 2.0,  // 100% adicional = pagan el doble
            'PRE PAMA 55+' => 1.5,  // 50% adicional
            default => 1.0          // Tarifa normal para todas las demás categorías
        };
    }

    // Método para verificar si es PRE-PAMA
    public function getIsPrePamaAttribute(): bool
    {
        return in_array($this->category_partner, ['PRE PAMA 50+', 'PRE PAMA 55+']);
    }

    // Calcula el multiplicador de precio según la categoría y edad
    public function getPricingMultiplierAttribute(): float
    {
        // Calcular automáticamente según categoría y edad
        return $this->calculatePricingMultiplier();
    }

    // Calcula automáticamente el multiplicador de precio
    public function calculatePricingMultiplier(): float
    {
        $category = $this->category_partner;

        if ($category === 'PRE PAMA 50+') {
            return 2.0;
        }
        if ($category === 'PRE PAMA 55+') {
            return 1.5;
        }

        return 1.00;
    }

    // Calcula el precio final para una cantidad de clases específica
    public function calculateFinalPrice(float $basePrice): float
    {
        $multiplier = $this->pricing_multiplier;

        return round($basePrice * $multiplier, 2);
    }

    // Obtiene la descripción de la tarifa aplicada
    public function getPricingDescriptionAttribute(): string
    {
        $multiplier = $this->pricing_multiplier;
        $age = $this->age;

        if ($multiplier == 0.00) {
            return 'Exonerado de pago';
        }

        if ($multiplier == 1.50) {
            return 'Tarifa + 50% (PRE-PAMA)';
        }

        return 'Tarifa normal';
    }

    // Verifica si el estudiante debería actualizar su categoría automáticamente
    public function shouldUpdateCategory(): bool
    {
        $age = $this->age;
        $category = $this->category_partner;

        // Si tiene menos de 60 y no es PRE-PAMA
        if ($age < 60 && $category !== 'Individual PRE-PAMA') {
            return true;
        }

        // Si tiene 60-64 y no es Individual
        if ($age >= 60 && $age < 65 && $category !== 'Individual') {
            return true;
        }

        // Si tiene 65+ y no es Transitorio
        if ($age >= 65 && ! in_array($category, ['Transitorio Individual', 'Transitorio Mayor de 75'])) {
            return true;
        }

        return false;
    }

    // Actualiza automáticamente los campos de tarifa según la categoría
    public function updatePricingFields(): void
    {
        // Este método ahora solo calcula valores dinámicamente
        // No guarda campos que no existen en la base de datos
    }
}
