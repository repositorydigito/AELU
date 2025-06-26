<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('workshops', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('standard_monthly_fee', 8, 2);
            $table->decimal('hourly_rate', 8, 2)->nullable();
            $table->integer('duration_minutes')->default(60);
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};
