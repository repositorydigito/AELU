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
            $table->tinyInteger('day_of_week');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('max_capacity');
            $table->string('place')->nullable();
            $table->boolean('is_volunteer')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['instructor_id', 'workshop_id', 'day_of_week', 'start_time'], 'unique_schedule');            
        });
    }
   
    public function down(): void
    {
        Schema::dropIfExists('instructor_workshops');
    }
};
