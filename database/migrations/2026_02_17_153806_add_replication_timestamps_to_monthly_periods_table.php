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
        Schema::table('monthly_periods', function (Blueprint $table) {
            $table->timestamp('workshops_replicated_at')->nullable()->after('auto_generate_classes');
            $table->timestamp('enrollments_replicated_at')->nullable()->after('workshops_replicated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('monthly_periods', function (Blueprint $table) {
            $table->dropColumn(['workshops_replicated_at', 'enrollments_replicated_at']);
        });
    }
};
