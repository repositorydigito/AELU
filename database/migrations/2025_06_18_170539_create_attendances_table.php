<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id');
            $table->date('attendance_date');
            $table->boolean('is_present')->default(false);
            $table->text('notes')->nullable();
            $table->unique(['enrollment_id', 'attendance_date']);
            $table->timestamps();
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
