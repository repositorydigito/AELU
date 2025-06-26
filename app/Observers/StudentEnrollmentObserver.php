<?php

namespace App\Observers;

use App\Models\StudentEnrollment;

class StudentEnrollmentObserver
{    
    public function created(StudentEnrollment $enrollment)
    {
        // Ya no se crean EnrollmentClass aquí. Toda la lógica está en el formulario.
    }    
}
