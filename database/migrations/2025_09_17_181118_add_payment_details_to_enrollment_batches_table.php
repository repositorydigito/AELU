<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{    
    public function up(): void
    {
        Schema::table('enrollment_batches', function (Blueprint $table) {
            $table->decimal('amount_paid', 8, 2)->nullable()->after('total_amount');
            $table->decimal('change_amount', 8, 2)->default(0)->after('amount_paid');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_batches', function (Blueprint $table) {
            $table->dropColumn(['amount_paid', 'change_amount']);
        });
    }
};
