<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('enrollment_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_enrollment_id')->constrained()->onDelete('cascade');
            $table->foreignId('workshop_class_id')->constrained()->onDelete('cascade');
            $table->decimal('class_fee', 8, 2);
            $table->enum('attendance_status', ['enrolled', 'attended', 'absent', 'cancelled'])->default('enrolled');
            $table->timestamps();
            
            $table->unique(['student_enrollment_id', 'workshop_class_id'], 'unique_class_enrollment');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('enrollment_classes');
    }
};
