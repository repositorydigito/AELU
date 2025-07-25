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
        Schema::create('enrollment_batches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->onDelete('cascade');
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('batch_code')->unique(); // Código único del lote
            $table->decimal('total_amount', 10, 2); // Monto total de todas las inscripciones
            $table->enum('payment_status', ['pending', 'to_pay', 'completed','credit_favor', 'refunded'])->default('pending');
            $table->string('payment_method'); // cash, link
            $table->date('payment_due_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('payment_document')->nullable();
            $table->date('enrollment_date'); // Fecha de la inscripción
            $table->text('notes')->nullable(); // Notas generales
            $table->timestamps();

            $table->index(['student_id', 'enrollment_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_batches');
    }
};
