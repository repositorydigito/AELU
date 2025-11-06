<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_payment_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_payment_id')->constrained('enrollment_payments')->onDelete('cascade');
            $table->foreignId('student_enrollment_id')->constrained('student_enrollments')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamps();

            $table->unique(['enrollment_payment_id', 'student_enrollment_id'], 'unique_enrollment_payment_student_enrollment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_payment_items');
    }
};
