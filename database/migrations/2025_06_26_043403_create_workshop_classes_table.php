<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('workshop_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_workshop_id')->constrained()->onDelete('cascade');
            $table->foreignId('monthly_period_id')->constrained()->onDelete('cascade');
            $table->date('class_date');
            $table->time('start_time');
            $table->time('end_time');
            $table->enum('status', ['scheduled', 'completed', 'cancelled'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['instructor_workshop_id', 'class_date', 'start_time'], 'unique_class');            
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('workshop_classes');
    }
};
