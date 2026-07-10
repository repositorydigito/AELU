<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('origin_student_enrollment_id')->constrained('student_enrollments')->onDelete('cascade');
            $table->foreignId('origin_monthly_period_id')->constrained('monthly_periods');
            $table->foreignId('valid_through_period_id')->constrained('monthly_periods');
            $table->unsignedTinyInteger('classes_count');
            $table->decimal('amount', 8, 2);
            $table->enum('origin', ['inasistencia', 'feriado', 'mixto']);
            $table->enum('status', ['available', 'consumed', 'expired'])->default('available');
            $table->timestamp('consumed_at')->nullable();
            $table->foreignId('consumed_student_enrollment_id')->nullable()->constrained('student_enrollments')->onDelete('set null');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_credits');
    }
};
