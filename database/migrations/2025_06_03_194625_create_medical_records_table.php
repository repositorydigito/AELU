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
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->decimal('weight', 5, 2)->nullable(); 
            $table->decimal('height', 5, 2)->nullable(); 
            $table->string('gender')->nullable(); 
            $table->string('smokes')->nullable(); 
            $table->integer('cigarettes_per_day')->nullable(); 
            $table->string('health_insurance')->nullable(); 
            $table->json('medical_conditions')->nullable(); 
            $table->json('allergies')->nullable(); 
            $table->text('allergy_details')->nullable(); 
            $table->json('surgical_operations')->nullable(); 
            $table->string('surgical_operation_details')->nullable(); 

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
