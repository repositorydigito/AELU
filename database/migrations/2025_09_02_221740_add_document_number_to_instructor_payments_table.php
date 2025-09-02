<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_payments', function (Blueprint $table) {
            $table->string('document_number')->nullable()->after('payment_date');
        });
    }

    public function down(): void
    {
        Schema::table('instructor_payments', function (Blueprint $table) {
            $table->dropColumn('document_number');
        });
    }
};
