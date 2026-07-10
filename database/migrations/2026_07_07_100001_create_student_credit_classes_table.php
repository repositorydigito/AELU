<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_credit_classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_credit_id')->constrained()->onDelete('cascade');
            $table->foreignId('enrollment_class_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['student_credit_id', 'enrollment_class_id'], 'unique_credit_class');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_credit_classes');
    }
};
