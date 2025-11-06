<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_code')->unique();
            $table->foreignId('enrollment_batch_id')->constrained('enrollment_batches')->onDelete('cascade');
            $table->foreignId('enrollment_payment_id')->constrained('enrollment_payments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->decimal('total_amount', 10, 2);
            $table->enum('ticket_type', ['enrollment'])->default('enrollment');
            $table->enum('status', ['active', 'cancelled', 'refunded'])->default('active');
            $table->datetime('issued_at');
            $table->foreignId('issued_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->datetime('cancelled_at')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->text('cancellation_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
