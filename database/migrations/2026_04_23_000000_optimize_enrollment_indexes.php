<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            // Eliminar índice duplicado: foreignId('enrollment_batch_id')->constrained()
            // ya crea el índice FK automáticamente; este index() es redundante.
            // $table->dropIndex('student_enrollments_enrollment_batch_id_index');

            // Compuesto para la query de cancel_pending_enrollments:
            // WHERE enrollment_batch_id = ? AND payment_status = 'pending' AND cancelled_at IS NULL
            $table->index(['enrollment_batch_id', 'payment_status'], 'idx_se_batch_status');
        });

        Schema::table('enrollment_batches', function (Blueprint $table) {
            // Compuesto para la vista principal: filtro por status + sort por updated_at DESC
            $table->index(['payment_status', 'updated_at'], 'idx_eb_status_updated');
        });
    }

    public function down(): void
    {
        Schema::table('student_enrollments', function (Blueprint $table) {
            $table->dropIndex('idx_se_batch_status');
            $table->index('enrollment_batch_id'); // restaurar el índice duplicado
        });

        Schema::table('enrollment_batches', function (Blueprint $table) {
            $table->dropIndex('idx_eb_status_updated');
        });
    }
};
