<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('workshop_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workshop_id'); 
            $table->date('class_date'); 
            $table->time('start_time');
            $table->time('end_time');   
            $table->boolean('is_holiday')->default(false); 
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['workshop_id', 'class_date']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('workshop_classes');
    }
};
