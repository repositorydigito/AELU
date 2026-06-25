<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_payment_receipts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('instructor_id')->constrained()->onDelete('cascade');
            $table->foreignId('monthly_period_id')->constrained()->onDelete('cascade');
            $table->enum('payment_type', ['volunteer', 'hourly']);
            $table->string('document_number', 50);
            $table->date('payment_date');
            $table->decimal('total_amount', 10, 2);
            $table->foreignId('registered_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['instructor_id', 'monthly_period_id', 'payment_type'], 'unique_instructor_receipt');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_payment_receipts');
    }
};
