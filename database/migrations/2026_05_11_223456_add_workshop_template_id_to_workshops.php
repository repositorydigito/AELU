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
            $table->foreignId('workshop_template_id')
                ->nullable()
                ->after('monthly_period_id')
                ->constrained('workshop_templates')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('workshops', function (Blueprint $table) {
            $table->dropForeign(['workshop_template_id']);
            $table->dropColumn('workshop_template_id');
        });
    }
};
