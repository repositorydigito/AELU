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
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->date('payment_due_date')->nullable()->after('payment_method');
            $table->date('payment_date')->nullable()->after('payment_due_date');
            $table->string('payment_document')->nullable()->after('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropColumn(['payment_due_date', 'payment_date', 'payment_document']);
        });
    }
};