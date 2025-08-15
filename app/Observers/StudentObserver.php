<?php

namespace App\Observers;

use App\Models\Student;

class StudentObserver
{
    /**
     * Handle the Student "creating" event.
     */
    public function creating(Student $student): void
    {
        $this->calculatePricingFields($student);
        $this->setMaintenanceStatus($student);
    }

    /**
     * Handle the Student "updating" event.
     */
    public function updating(Student $student): void
    {
        // Solo recalcular si cambiaron campos relevantes
        if ($student->isDirty(['category_partner', 'birth_date'])) {
            $this->calculatePricingFields($student);
            $this->setMaintenanceStatus($student);
        }
    }

    /**
     * Calcula automáticamente los campos de tarifa según la categoría y edad
     */
    protected function calculatePricingFields(Student $student): void
    {
        // Los campos de tarifa ahora se calculan dinámicamente en el modelo
        // No necesitamos guardar nada en la base de datos
    }
    /**
     * Calcula el multiplicador de precio según la categoría y edad
     */
    protected function calculatePricingMultiplier(Student $student): float
    {
        $age = $student->birth_date ? $student->birth_date->age : 0;
        $category = $student->category_partner;

        // Categorías exentas de pago
        if (in_array($category, ['Hijo de Fundador', 'Vitalicios', 'Transitorio Mayor de 75'])) {
            return 0.00;
        }

        // Individual PRE-PAMA: Menores de 60 años pagan 50% adicional
        if ($category === 'Individual PRE-PAMA' || ($category === 'Individual' && $age < 60)) {
            return 1.50; // 150% = tarifa normal + 50%
        }

        // Todas las demás categorías pagan tarifa normal
        return 1.00;
    }
    /**
     * Establece automáticamente el estado de mantenimiento según la categoría
     */
    protected function setMaintenanceStatus(Student $student): void
    {
        $exemptCategories = [
            'Transitorio Mayor de 75',
            'Hijo de Fundador',
            'Vitalicios'
        ];

        // Si es una categoría exonerada, marcar como exonerado
        if (in_array($student->category_partner, $exemptCategories)) {
            $student->monthly_maintenance_status = 'exonerado';
        }
        // Si no tiene estado definido y no es exonerado, poner como no pagado
        elseif (empty($student->monthly_maintenance_status)) {
            $student->monthly_maintenance_status = 'no_pagado';
        }
    }
}
