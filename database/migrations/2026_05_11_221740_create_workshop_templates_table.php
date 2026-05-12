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
        Schema::create('workshop_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('standard_monthly_fee', 8, 2);
            $table->decimal('pricing_surcharge_percentage', 5, 2)->default(20);
            $table->json('day_of_week');
            $table->time('start_time');
            $table->integer('duration');
            $table->integer('capacity');
            $table->integer('number_of_classes')->default(4);
            $table->string('place')->nullable();
            $table->string('modality')->nullable();
            $table->text('additional_comments')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('workshop_templates');
    }
};
