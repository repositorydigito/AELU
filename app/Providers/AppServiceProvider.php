<?php

namespace App\Providers;

use App\Models\EnrollmentBatch;
use App\Models\InstructorPayment;
use App\Models\InstructorWorkshop;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Workshop;
use App\Observers\EnrollmentBatchObserver;
use App\Observers\InstructorPaymentObserver;
use App\Observers\InstructorWorkshopObserver;
use App\Observers\StudentEnrollmentObserver;
use App\Observers\StudentObserver;
use App\Observers\WorkshopObserver;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\ServiceProvider;
use TomatoPHP\FilamentUsers\Resources\UserResource\Form\UserForm;
use TomatoPHP\FilamentUsers\Resources\UserResource\Table\UserTable;

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
        EnrollmentBatch::observe(EnrollmentBatchObserver::class);
        Workshop::observe(WorkshopObserver::class);
        StudentEnrollment::observe(StudentEnrollmentObserver::class);
        Student::observe(StudentObserver::class);
        InstructorPayment::observe(InstructorPaymentObserver::class);
        InstructorWorkshop::observe(InstructorWorkshopObserver::class);

        UserTable::register(
            TextColumn::make('enrollment_code')
                ->label('Código')
                ->badge()
                ->sortable()
                ->searchable()
                ->toggleable()
                ->placeholder('—')
        );

        UserForm::register(
            TextInput::make('enrollment_code')
                ->label('Código de inscripción')
                ->maxLength(3)
                ->disabled()
                ->dehydrated(false)
                ->placeholder('Auto-generado')
                ->helperText('Asignado automáticamente al crear el usuario.')
        );
    }
}
