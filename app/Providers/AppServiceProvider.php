<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\InstructorWorkshop;
use App\Models\Workshop;
use App\Observers\WorkshopObserver;
use App\Observers\InstructorWorkshopObserver;
use App\Models\StudentEnrollment;
use App\Observers\StudentEnrollmentObserver;
use App\Models\Student;
use App\Observers\StudentObserver;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        InstructorWorkshop::observe(InstructorWorkshopObserver::class);
        Workshop::observe(WorkshopObserver::class);
        StudentEnrollment::observe(StudentEnrollmentObserver::class);   
        Student::observe(StudentObserver::class);     
    }
}
