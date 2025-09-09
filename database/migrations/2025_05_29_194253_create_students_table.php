<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('last_names');
            $table->string('first_names');
            $table->string('photo')->nullable();
            $table->string('document_type')->nullable();
            $table->string('document_number')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('nationality')->nullable();
            $table->string('student_code')->nullable();
            $table->string('category_partner')->nullable();
            $table->string('cell_phone')->nullable();
            $table->string('home_phone')->nullable();
            $table->string('district')->nullable();
            $table->string('address')->nullable();

            // Contacto de emergencia
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->timestamps();

            // Tarifas
            $table->boolean('has_payment_exemption')->default(false);
            $table->decimal('pricing_multiplier', 5, 2)->default(1.00);

            $table->foreignId('maintenance_period_id')->nullable()->constrained('maintenance_periods');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
