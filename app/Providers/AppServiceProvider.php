<?php

namespace App\Providers;

use App\Models\InstructorPayment;
use App\Models\InstructorWorkshop;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Workshop;
use App\Observers\InstructorPaymentObserver;
use App\Observers\InstructorWorkshopObserver;
use App\Observers\StudentEnrollmentObserver;
use App\Observers\StudentObserver;
use App\Observers\WorkshopObserver;
use Illuminate\Support\ServiceProvider;

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
        Workshop::observe(WorkshopObserver::class);
        StudentEnrollment::observe(StudentEnrollmentObserver::class);
        Student::observe(StudentObserver::class);
        InstructorPayment::observe(InstructorPaymentObserver::class);
        InstructorWorkshop::observe(InstructorWorkshopObserver::class);
    }
}
