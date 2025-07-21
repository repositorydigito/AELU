<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->unsignedBigInteger('instructor_id')->nullable()->after('id');
            $table->string('icon')->nullable()->after('description');
            $table->string('day_of_week')->nullable()->after('icon');
            $table->string('start_time')->nullable()->after('day_of_week');
            $table->integer('duration')->nullable()->after('start_time');
            $table->integer('capacity')->nullable()->after('duration');
            $table->integer('number_of_classes')->nullable()->after('capacity');
            $table->decimal('monthly_fee', 8, 2)->nullable()->after('number_of_classes');

            $table->foreign('instructor_id')->references('id')->on('instructors')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->dropForeign(['instructor_id']);
            $table->dropColumn([
                'instructor_id',
                'icon',
                'day_of_week',
                'start_time',
                'duration',
                'capacity',
                'number_of_classes',
                'monthly_fee',
            ]);
        });
    }
};
