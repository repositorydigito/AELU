<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenance_periods', function (Blueprint $table) {
            $table->id();
            $table->integer('year');
            $table->tinyInteger('month');
            $table->string('name');
            $table->timestamps();

            $table->unique(['year', 'month'], 'unique_maintenance_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_periods');
    }
};
