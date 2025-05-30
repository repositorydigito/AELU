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
        Schema::create('treasuries', function (Blueprint $table) {
            $table->id();
            $table->enum('transaction_type', ['income', 'expense', 'payment', 'refund']);
            $table->decimal('amount', 10, 2);
            $table->string('description');
            $table->date('transaction_date');
            $table->foreignId('payment_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('student_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('instructor_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('workshop_id')->nullable()->constrained()->onDelete('set null');
            $table->string('reference_number')->unique()->nullable();
            $table->enum('status', ['pending', 'confirmed', 'cancelled'])->default('confirmed');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('treasuries');
    }
};
