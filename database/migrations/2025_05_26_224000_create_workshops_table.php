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
            $table->foreignId('instructor_id');
            $table->string('name');
            $table->date('start_date');
            $table->date('end_date');
            $table->string('weekday');
            $table->time('start_time'); 
            $table->time('end_time');   
            $table->string('place')->nullable(); 
            $table->integer('max_students'); 
            $table->integer('class_count');
            $table->decimal('monthly_fee', 8, 2); 
            $table->decimal('final_monthly_fee', 8, 2)->nullable();
            $table->decimal('surcharge_percentage')->default(20.00);
            $table->string('icon')->nullable();            
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};
