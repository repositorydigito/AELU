<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Actualizar valores existentes de 'partial' a 'to_pay'
        DB::table('student_enrollments')
            ->where('payment_status', 'partial')
            ->update(['payment_status' => 'to_pay']);
            
        DB::table('enrollment_batches')
            ->where('payment_status', 'partial')
            ->update(['payment_status' => 'to_pay']);

        // Modificar las columnas para incluir los nuevos valores enum
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'to_pay', 'completed', 'credit_favor', 'refunded'])
                ->default('pending')
                ->change();
        });

        Schema::table('enrollment_batches', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'to_pay', 'completed', 'credit_favor', 'refunded'])
                ->default('pending')
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revertir valores de 'to_pay' a 'partial'
        DB::table('student_enrollments')
            ->where('payment_status', 'to_pay')
            ->update(['payment_status' => 'partial']);
            
        DB::table('enrollment_batches')
            ->where('payment_status', 'to_pay')
            ->update(['payment_status' => 'partial']);

        // Revertir las columnas al enum original
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'partial', 'completed'])
                ->default('pending')
                ->change();
        });

        Schema::table('enrollment_batches', function (Blueprint $table) {
            $table->enum('payment_status', ['pending', 'partial', 'completed'])
                ->default('pending')
                ->change();
        });
    }
};