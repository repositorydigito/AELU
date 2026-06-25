<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('instructor_payments', function (Blueprint $table) {
            $table->foreignId('instructor_payment_receipt_id')
                ->nullable()
                ->after('payment_status')
                ->constrained('instructor_payment_receipts')
                ->onDelete('set null');

            $table->dropColumn(['document_number', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::table('instructor_payments', function (Blueprint $table) {
            $table->dropForeign(['instructor_payment_receipt_id']);
            $table->dropColumn('instructor_payment_receipt_id');

            $table->string('document_number')->nullable()->after('payment_date');
            $table->date('payment_date')->nullable()->after('payment_status');
        });
    }
};
