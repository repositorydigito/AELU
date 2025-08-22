<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('monthly_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->tinyInteger('month');
            $table->date('start_date');
            $table->date('end_date');
            $table->boolean('is_active')->default(true);
            $table->date('renewal_start_date')->nullable();
            $table->date('renewal_end_date')->nullable();
            $table->boolean('auto_generate_classes')->default(true);
            $table->timestamps();
            
            $table->unique(['year', 'month'], 'unique_period');
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('monthly_periods');
    }
};
