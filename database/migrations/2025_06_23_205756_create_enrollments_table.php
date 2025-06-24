<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id');
            $table->foreignId('workshop_id');
            $table->date('enrollment_date');
            $table->string('status');
            $table->decimal('total_amount', 10, 2); 
            $table->decimal('amount_paid', 10, 2)->default(0.00); 
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['student_id', 'workshop_id']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
