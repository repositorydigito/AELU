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
        Schema::create('instructor_workshops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id');
            $table->foreignId('workshop_id');
            $table->string('day_of_week'); 
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('class_count')->nullable(); 
            $table->decimal('class_rate', 8, 2)->nullable(); 
            $table->timestamps();
            // Para asegurar que un instructor no dicte el mismo taller en el mismo día y hora
            // $table->unique(['instructor_id', 'workshop_id', 'day_of_week', 'start_time'], 'unique_instructor_workshop_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_workshops');
    }
};
