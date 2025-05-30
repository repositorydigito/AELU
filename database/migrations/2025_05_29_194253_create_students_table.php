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
            $table->string('document_type')->nullable();
            $table->string('document_number')->unique();
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('student_code')->unique();
            $table->string('cell_phone')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('district')->nullable();
            $table->string('address')->nullable();
            $table->string('photo')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
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
