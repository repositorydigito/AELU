<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\Workshop;
use App\Observers\WorkshopObserver;
use App\Models\StudentEnrollment;
use App\Observers\StudentEnrollmentObserver;
use App\Models\Student;
use App\Observers\StudentObserver;
use App\Models\InstructorPayment;
use App\Observers\InstructorPaymentObserver;
use App\Models\InstructorWorkshop;
use App\Observers\InstructorWorkshopObserver;

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
