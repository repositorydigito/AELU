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
        Schema::create('instructor_workshop', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id');
            $table->foreignId('workshop_id');
            $table->string('day');
            $table->time('time');
            $table->integer('class_count'); 
            $table->decimal('rate', 8, 2); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('instructor_workshop');
    }
};
