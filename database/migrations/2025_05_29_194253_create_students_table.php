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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('last_names'); 
            $table->string('first_names'); 
            $table->string('photo')->nullable(); 
            $table->string('document_type'); 
            $table->string('document_number')->unique(); 
            $table->date('birth_date'); 
            $table->string('nationality'); 
            $table->string('student_code')->nullable(); 
            $table->string('category_partner')->nullable(); 
            $table->string('cell_phone')->nullable(); 
            $table->string('home_phone')->nullable(); 
            $table->string('district')->nullable(); 
            $table->string('address')->nullable(); 

            // Contacto de emergencia
            $table->string('emergency_contact_name'); 
            $table->string('emergency_contact_relationship'); 
            $table->string('emergency_contact_phone'); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
