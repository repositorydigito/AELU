<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('monthly_instructor_rates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('monthly_period_id')->constrained()->onDelete('cascade');
            $table->decimal('volunteer_percentage', 5, 4)->default(0.5000)->comment('Porcentaje para instructores voluntarios (ej: 0.5000 = 50%)');
            $table->boolean('is_active')->default(true);            
            
            $table->unique(['monthly_period_id'], 'unique_monthly_rate');
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('monthly_instructor_rates');
    }
};
