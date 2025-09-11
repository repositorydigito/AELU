<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('class_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_class_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_enrollment_id')->constrained()->onDelete('cascade');
            $table->boolean('is_present')->default(false);
            $table->text('comments')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            // Evitar duplicados para la misma clase y estudiante
            $table->unique(['workshop_class_id', 'student_enrollment_id'], 'unique_attendance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('class_attendances');
    }
};
