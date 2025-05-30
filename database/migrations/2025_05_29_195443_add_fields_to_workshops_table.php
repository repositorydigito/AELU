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
        Schema::table('workshops', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
            $table->integer('duration_hours')->nullable()->after('description');
            $table->decimal('price', 8, 2)->nullable()->after('duration_hours');
            $table->integer('max_students')->nullable()->after('price');
            $table->enum('status', ['active', 'inactive', 'completed'])->default('active')->after('max_students');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->dropColumn(['description', 'duration_hours', 'price', 'max_students', 'status']);
        });
    }
};
