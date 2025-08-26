<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('instructor_workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            $table->foreignId('workshop_id')->constrained()->onDelete('cascade');
            $table->foreignId('initial_monthly_period_id')->nullable()->constrained('monthly_periods')->onDelete('set null');
            $table->tinyInteger('day_of_week')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->integer('max_capacity')->nullable();
            $table->string('place')->nullable();
            $table->boolean('is_active')->default(true);
            $table->enum('payment_type', ['volunteer', 'hourly']);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->decimal('duration_hours', 4, 2)->nullable();
            $table->decimal('custom_volunteer_percentage', 5, 2)->nullable();
            $table->timestamps();
            
            $table->unique(['instructor_id', 'workshop_id', 'day_of_week', 'start_time'], 'unique_schedule');            
        });
    }
   
    public function down(): void
    {
        Schema::dropIfExists('instructor_workshops');
    }
};
