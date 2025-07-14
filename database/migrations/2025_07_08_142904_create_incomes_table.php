<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id();
            $table->string('concept');
            $table->date('date');
            $table->string('razon_social');
            $table->decimal('amount', 10, 2);
            $table->string('document_number');
            $table->string('notes')->nullable();
            $table->string('vale_code')->nullable();
            $table->string('voucher_path')->nullable();
            $table->boolean('is_income')->default(true);
            // Falta agregar la relación con el pago de profesores
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};
