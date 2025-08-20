<?php

namespace App\Observers;

use App\Models\Student;

class StudentObserver
{
    public function creating(Student $student): void
    {
        $this->calculatePricingFields($student);
        $this->setMaintenanceStatus($student);
    }

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
        $category = $student->category_partner;        

        if ($category === 'PRE PAMA 50+') {
            return 2.0; 
        }        
        if ($category === 'PRE PAMA 55+') {
            return 1.5; 
        }

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

        // Si es una categoría exonerada, limpiar el período de mantenimiento
        if (in_array($student->category_partner, $exemptCategories)) {
            $student->maintenance_period_id = null;
        }
    }
}
