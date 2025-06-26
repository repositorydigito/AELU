<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('instructor_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            $table->foreignId('instructor_workshop_id')->constrained()->onDelete('cascade');
            $table->foreignId('monthly_period_id')->constrained()->onDelete('cascade');
            $table->enum('payment_type', ['volunteer', 'hourly']);
            
            // Para voluntarios
            $table->integer('total_students')->nullable()->comment('Total de estudiantes inscritos (solo voluntarios)');
            $table->decimal('monthly_revenue', 10, 2)->nullable()->comment('Ingresos totales del taller (solo voluntarios)');
            $table->decimal('volunteer_percentage', 5, 4)->default(0.5000)->comment('Porcentaje para voluntarios (default 50%)');
            
            // Para por horas
            $table->decimal('total_hours', 8, 2)->nullable()->comment('Total de horas dictadas (solo por horas)');
            $table->decimal('hourly_rate', 8, 2)->nullable()->comment('Tarifa por hora (solo por horas)');
            
            // Resultado final
            $table->decimal('calculated_amount', 10, 2)->comment('Monto calculado a pagar');
            $table->enum('payment_status', ['pending', 'paid'])->default('pending');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->unique(['instructor_workshop_id', 'monthly_period_id'], 'unique_instructor_payment');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('instructor_payments');
    }
};
