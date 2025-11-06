<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ticket_student_enrollment', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->onDelete('cascade');
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['ticket_id', 'student_enrollment_id'], 'unique_ticket_enrollment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ticket_student_enrollment');
    }
};
