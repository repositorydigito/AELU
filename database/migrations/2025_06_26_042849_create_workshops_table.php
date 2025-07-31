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
            $table->decimal('pricing_surcharge_percentage', 5, 2)->default(20.00);
            $table->unsignedBigInteger('instructor_id')->nullable();
            $table->string('day_of_week')->nullable();
            $table->string('start_time')->nullable();
            $table->integer('duration')->nullable();
            $table->integer('capacity')->nullable();
            $table->integer('number_of_classes')->nullable();
            $table->decimal('monthly_fee', 8, 2)->nullable();

            $table->foreign('instructor_id')->references('id')->on('instructors')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshops');
    }
};
