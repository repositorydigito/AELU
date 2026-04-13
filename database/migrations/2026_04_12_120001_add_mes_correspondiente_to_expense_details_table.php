<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expense_details', function (Blueprint $table) {
            $table->string('mes_correspondiente')->nullable()->after('date');
        });
    }

    public function down(): void
    {
        Schema::table('expense_details', function (Blueprint $table) {
            $table->dropColumn('mes_correspondiente');
        });
    }
};
