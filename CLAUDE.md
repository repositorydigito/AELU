# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

AELU is a workshop enrollment and management system for PAMA (Programa del Adulto Mayor Activo), an educational institution for adult learners. The system manages students, instructors, workshops, enrollments, payments, and attendance tracking with sophisticated pricing rules and automated monthly processes.

**Tech Stack:**
- Laravel 12.0 (PHP 8.2+)
- Filament 3.3 (admin panel framework)
- MySQL database
- Vite + TailwindCSS 4.0
- Spatie Laravel Permission (role-based access control)

## Development Commands

### Setup & Installation
```bash
# Install PHP dependencies
composer install

# Install Node dependencies
npm install

# Setup environment file (copy .env.example to .env and configure)
cp .env.example .env

# Generate application key
php artisan key:generate

# Run migrations
php artisan migrate

# Seed database (if seeders exist)
php artisan db:seed
```

### Development Server
```bash
# Run full development stack (server + queue + logs + vite)
composer dev

# Or run individual services:
php artisan serve              # Development server at http://localhost:8000
php artisan queue:listen       # Queue worker
php artisan pail               # Log viewer
npm run dev                    # Vite dev server for assets
```

### Testing
```bash
# Run all tests
composer test
# Or: php artisan test

# Run specific test file
php artisan test tests/Feature/ExampleTest.php

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

### Code Quality
```bash
# Format code with Laravel Pint
./vendor/bin/pint

# Check specific files
./vendor/bin/pint app/Models/Student.php
```

### Asset Building
```bash
# Build for production
npm run build

# Development mode with hot reload
npm run dev
```

### Scheduled Tasks (Cron)
The system requires Laravel's scheduler to run. In production, add this cron entry:
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Active scheduled commands:
- `enrollments:auto-cancel` - Auto-cancels pending enrollments on configured day
- `workshops:auto-replicate` - Replicates workshops to next month
- `enrollments:auto-generate` - (Currently disabled) Auto-generates enrollments

### Useful Artisan Commands
```bash
# Clear application cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Regenerate Filament Shield permissions
php artisan shield:generate

# Sync enrollment payment status (one-time migration command)
php artisan enrollments:sync-payment-status

# View application logs in real-time
php artisan pail
```

## Architecture Overview

### Core Domain Models & Relationships

**Monthly Period System (Central Organizing Principle)**
- Everything is scoped by `MonthlyPeriod` (year/month combinations)
- Workshops, enrollments, classes, and instructor payments are all period-specific
- Each period has renewal windows (`renewal_start_date`/`renewal_end_date`)
- Model: `app/Models/MonthlyPeriod.php`

**Student Enrollment Flow (Three-Level Structure)**
1. **EnrollmentBatch** (`app/Models/EnrollmentBatch.php`)
   - Groups multiple workshop enrollments for one student in one period
   - Tracks overall payment status: `pending`, `to_pay`, `completed`, `credit_favor`, `refunded`
   - Has unique `batch_code` for identification
   - Supports partial payments via `EnrollmentPayment`

2. **StudentEnrollment** (`app/Models/StudentEnrollment.php`)
   - Individual workshop enrollment within a batch
   - Links: `student_id` → `instructor_workshop_id` → `monthly_period_id`
   - Two types: `full_month` (4 classes) or `specific_classes` (recuperation)
   - Stores pricing: `number_of_classes`, `price_per_quantity`, `total_amount`
   - Tracks renewal chain via `previous_enrollment_id`

3. **EnrollmentClass** (`app/Models/EnrollmentClass.php`)
   - Links specific `WorkshopClass` dates to a `StudentEnrollment`
   - Tracks per-class fees if needed

**Workshop Structure**
- **Workshop** (`app/Models/Workshop.php`): Template for a class (yoga, painting, etc.)
  - Has base pricing: `standard_monthly_fee`, `pricing_surcharge_percentage`
  - Schedule: `day_of_week` (array), `start_time`, `duration`, `capacity`
  - Belongs to a `MonthlyPeriod`

- **WorkshopClass** (`app/Models/WorkshopClass.php`): Specific class instances (dates/times)
  - Created when workshops are replicated monthly
  - Status: `scheduled`, `completed`, `cancelled`

- **InstructorWorkshop** (`app/Models/InstructorWorkshop.php`): Links instructors to workshops
  - Payment type: `volunteer` (percentage of revenue) or `hourly` (fixed rate)
  - Has `custom_volunteer_percentage` to override defaults

**Student Management**
- **Student** (`app/Models/Student.php`): Core student info with medical records
  - Has `maintenance_period_id` tracking membership dues status
  - **Category-based pricing multipliers**:
    - PRE PAMA 50+: 2.0x (200% of base price)
    - PRE PAMA 55+: 1.5x (150% of base price)
    - Exempted categories: Vitalicios, Hijo de Fundador, Transitorio Mayor de 75
  - Must be "current" with maintenance (within 2 months grace period) to enroll

**Payment Systems**
- **EnrollmentPayment**: Partial/full payments linked to batches
- **Ticket**: Payment receipts with unique codes, can be cancelled/refunded
- **InstructorPayment** (`app/Models/InstructorPayment.php`): Monthly payments to instructors
  - **Volunteer calculation**: `monthly_revenue * volunteer_percentage`
  - **Hourly calculation**: `total_hours * hourly_rate`
  - Uses `MonthlyInstructorRate` for default percentages

**Attendance Tracking**
- **ClassAttendance**: Boolean `is_present` per student per class
- Linked: `WorkshopClass` → `StudentEnrollment` → attendance record

### Key Business Rules

1. **Pricing Calculation** (in `Student.php`):
   - Base price from `WorkshopPricing` or `standard_monthly_fee`
   - Multiply by student's category pricing multiplier
   - PRE PAMA categories pay 150% or 200% of base price

2. **Maintenance Enforcement**:
   - Students need `maintenance_period_id` ≥ (current_month - 2) to enroll
   - 2-month grace period before restricting enrollments
   - Exempted categories bypass this check

3. **Workshop Capacity Management**:
   - Methods: `getAvailableSpotsForPeriod()`, `isFullForPeriod()`
   - Counts active enrollments (completed + pending)

4. **Instructor Payment Types**:
   - **Volunteer**: Percentage of workshop revenue (typically 30-40%)
   - **Hourly**: Fixed rate per hour taught
   - Custom percentages can override monthly defaults

5. **Auto-Cancellation** (SystemSettings configurable):
   - Default day: 28th of each month
   - Cancels ALL pending enrollment batches (any period)
   - Changes status to `refunded`
   - Records "Sistema" as `cancelled_by`

### Automated Monthly Processes

**1. Workshop Replication** (`ReplicateWorkshopsForNextMonth.php`)
- Service: `app/Services/WorkshopReplicationService.php`
- Runs: Every minute (checks for configured day/time via SystemSettings)
- Clones workshops from current period to next month
- Generates `WorkshopClass` instances based on schedule
- Creates classes for all dates matching `day_of_week` within period

**2. Auto-Generate Enrollments** (Currently disabled)
- Command: `AutoGenerateNextMonthEnrollments.php`
- Service: `app/Services/WorkshopAutoCreationService.php`
- Finds all `completed` batches from current month
- For students with current maintenance, creates new batches for next month
- Replicates workshop selections automatically

**3. Auto-Cancel Pending Enrollments** (`AutoCancelPendingEnrollments.php`)
- Runs: Every minute (checks for configured day/time)
- Cancels ALL pending batches (across all months)
- Marks as `refunded`, cancels tickets
- Configurable via SystemSettings

### Filament Resources & Pages

**Main Resources** (in `app/Filament/Resources/`):
- `EnrollmentBatchResource` - Primary enrollment management interface
  - Custom action: `RegisterPaymentAction` for processing payments
  - Can cancel/refund batches, view tickets
  - Edit redirects to create page with pre-filled data
- `StudentRegisterResource` - Student CRUD
- `WorkshopResource` - Workshop management with nested InstructorWorkshops
- `InstructorResource` - Instructor management with import functionality
- `InstructorPaymentResource` - Payment tracking with widgets
- `IncomeResource` / `ExpenseResource` - Financial tracking

**Custom Pages** (in `app/Filament/Pages/`):
- **Reports**: `AllUsersEnrollmentReport`, `CashiersEnrollmentReport`, `EnrollmentsReport1/2`, `InstructorKardexReport`, `MonthlyInstructorReport`, `ScheduleEnrollmentReport`
- **Management**: `AttendanceManagement`, `SystemSettings`

**Authorization**: Uses Filament Shield + Spatie Permissions
- Permission format: `view_any_enrollment::batch`, `create_student`
- Policies in `app/Policies/`
- Custom pages use `HasPageShield` trait

### Export/Import Functionality

**Exports** (Maatwebsite Excel - in `app/Exports/`):
- `EnrollmentBatchExport` - Enrollment data
- `InstructorPaymentExport` - Payment data
- `IncomeExport` / `EgresoExport` - Financial reports
- `InstructorsTemplateExport` - Template for bulk import

**Imports** (in `app/Imports/`):
- `InstructorsImport` - Bulk import instructors from Excel
- `StudentsImport` - Bulk import students from Excel

### Services Layer

Key business logic services (in `app/Services/`):
- `InstructorPaymentService.php` - Calculate instructor payments
- `WorkshopReplicationService.php` - Clone workshops monthly
- `WorkshopAutoCreationService.php` - Auto-generate enrollments
- `EnrollmentBatchService.php` - Enrollment batch operations

### Controllers & Routes

**Web Routes** (`routes/web.php`):
- `/` - Redirects to `/admin/login` (Filament panel)
- `/generate-affidavit/{student}` - Generate student affidavit PDF
- `/generate-affidavit-instructor/{instructor}` - Generate instructor affidavit PDF
- `/instructors/download-template` - Download import template
- `/inscription/{enrollmentId}/ticket` - Generate enrollment ticket
- `/inscription-batch/{batchId}/ticket` - Generate batch ticket
- `/ticket/{ticket}/pdf` - Generate ticket PDF (auth required)

**Controllers** (in `app/Http/Controllers/`):
- `AffidavitController` - PDF generation for affidavits
- `InstructorController` - Template downloads
- `StudentEnrollmentController` - Ticket generation

### Database Conventions

**Temporal Organization**:
- All major entities scoped by `monthly_period_id`
- Workshops, classes, enrollments all period-specific

**Payment Tracking**:
- Multi-level: `EnrollmentBatch` → `EnrollmentPayment` → `EnrollmentPaymentItem`
- Supports partial payments and refunds

**Audit Trail**:
- User tracking: `created_by`, `updated_by`, `payment_registered_by_user_id`
- Auto-assigned via model boot methods using `Auth::id()`
- Cancellation tracking: `cancelled_by`, `cancellation_reason`

**Status Management**:
- Status enums rather than soft deletes
- Common statuses: `pending`, `completed`, `refunded`, `cancelled`

## Important Development Notes

### When Working with Enrollments

1. **Always consider the three-level structure**: Batch → StudentEnrollment → EnrollmentClass
2. **Payment status flows from batch to enrollments**: Update batch status, enrollments inherit
3. **Pricing is calculated at enrollment creation time**: Changes to workshop prices don't affect existing enrollments
4. **Previous enrollment tracking**: Chain renewals using `previous_enrollment_id`

### When Working with Workshops

1. **Workshops are period-specific**: Clone for new periods, don't reuse
2. **Classes auto-generate**: Based on `day_of_week`, `start_time`, and period boundaries
3. **Capacity checks**: Always check `isFullForPeriod()` before creating enrollments
4. **Instructor assignments**: Use `InstructorWorkshop` junction, not direct relationships

### When Working with Payments

1. **Instructor payment calculation**: Use `InstructorPaymentService`, don't calculate manually
2. **Partial payments**: Use `EnrollmentPayment` linked via `EnrollmentPaymentItem`
3. **Tickets must be generated**: After payment registration
4. **Refunds**: Update batch status to `refunded`, cancel related tickets

### When Working with Students

1. **Category pricing**: Implemented in `Student.php` methods, not in database
2. **Maintenance check**: Always validate before enrolling
3. **Medical records**: Separate model (`MedicalRecord`), one-to-one relationship
4. **Import functionality**: Use provided import classes, don't create custom

### Testing Considerations

- PHPUnit configured for in-memory SQLite
- Test database reset between tests
- Factory patterns for model creation
- Feature tests should cover enrollment workflows
- Unit tests for pricing calculations

### Performance Considerations

1. **Eager loading**: Always eager load relationships to avoid N+1 queries
   - Example: `EnrollmentBatch::with(['studentEnrollments.instructorWorkshop.workshop', 'student'])`
2. **Period scoping**: Index and filter by `monthly_period_id` for better query performance
3. **Batch operations**: Use bulk inserts for creating multiple enrollments/classes
4. **Scheduled commands**: Run in background, use `withoutOverlapping()` lock

### Common Pitfalls

1. **Don't bypass batch structure**: Always create enrollments through batches
2. **Don't modify workshop prices retroactively**: Will affect active enrollments incorrectly
3. **Don't forget SystemSettings checks**: Automated processes need configuration
4. **Don't hardcode volunteer percentages**: Use `MonthlyInstructorRate` or custom values
5. **Don't assume single payment**: Batches support multiple partial payments

## Project-Specific Patterns

### User Tracking Pattern
Models auto-track creating/updating users:
```php
protected static function boot()
{
    parent::boot();
    static::creating(function ($model) {
        $model->created_by = Auth::id();
    });
}
```

### Period Scoping Pattern
Always filter by period when querying temporal data:
```php
Workshop::where('monthly_period_id', $periodId)->get();
```

### Capacity Checking Pattern
```php
if ($workshop->isFullForPeriod($periodId)) {
    // Handle full workshop
}
```

### Payment Registration Pattern
1. Create `EnrollmentPayment` record
2. Link via `EnrollmentPaymentItem` pivot
3. Update batch status to `completed`
4. Generate `Ticket` receipt
5. Record `payment_registered_by_user_id`

## Git Workflow

- Main branch: `main`
- Current branch: `richard`
- Use descriptive commit messages
- Feature branches for new functionality
- Test locally before pushing

## Additional Resources

- Filament Documentation: https://filamentphp.com/docs
- Laravel Documentation: https://laravel.com/docs/12.x
- Spatie Permissions: https://spatie.be/docs/laravel-permission
