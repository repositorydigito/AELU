<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollment_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_batch_id')->constrained('enrollment_batches')->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'link']);
            $table->date('payment_date');
            $table->string('status')->nullable();
            $table->foreignId('registered_by_user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->datetime('registered_at')->nullable();
            $table->string('payment_document')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollment_payments');
    }
};
