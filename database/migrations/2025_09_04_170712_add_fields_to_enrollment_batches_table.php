<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('enrollment_batches', function (Blueprint $table) {
            $table->foreignId('payment_registered_by_user_id')
                ->nullable()
                ->after('payment_status')
                ->constrained('users')
                ->onDelete('set null');

            $table->timestamp('payment_registered_at')
                ->nullable()
                ->after('payment_registered_by_user_id');
        });
    }

    public function down(): void
    {
        Schema::table('enrollment_batches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('payment_registered_by_user_id');
            $table->dropColumn('payment_registered_at');
        });
    }
};
