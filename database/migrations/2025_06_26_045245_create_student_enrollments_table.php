<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_workshop_id')->constrained()->onDelete('cascade');
            $table->foreignId('monthly_period_id')->constrained()->onDelete('cascade');
            $table->enum('enrollment_type', ['full_month', 'specific_classes'])->default('full_month');
            $table->tinyInteger('number_of_classes');
            $table->decimal('price_per_quantity', 8, 2);
            $table->decimal('total_amount', 8, 2);
            $table->text('pricing_notes')->nullable();
            $table->enum('payment_status', ['pending', 'to_pay', 'completed','credit_favor', 'refunded'])->default('pending');
            $table->string('payment_method');
            $table->date('payment_due_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_document')->nullable();
            $table->date('enrollment_date');
            $table->enum('renewal_status', ['pending', 'confirmed', 'cancelled', 'not_applicable'])->default('not_applicable');
            $table->date('renewal_deadline')->nullable();
            $table->boolean('is_renewal')->default(false);
            $table->foreignId('previous_enrollment_id')->nullable()->constrained('student_enrollments');
            $table->foreignId('enrollment_batch_id')->nullable()->constrained()->onDelete('cascade');
            $table->index('enrollment_batch_id');

            $table->timestamps();

            $table->unique(['student_id', 'instructor_workshop_id', 'monthly_period_id'], 'unique_enrollment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrollments');
    }
};
