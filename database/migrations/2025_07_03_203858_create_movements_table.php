<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_category_id')
                  ->constrained('movement_categories')
                  ->onDelete('restrict'); // No permitir eliminar una categoría si tiene movimientos asociados

            $table->date('date');
            $table->decimal('amount', 10, 2);

            // Campo de concepto ahora es una descripción, puede ser manual o auto-generada
            $table->string('concept')->nullable();

            $table->text('notes')->nullable()->comment('Observaciones o detalles adicionales');

            // Esto creará `movable_id` (BIGINT) y `movable_type` (VARCHAR)
            // Será nullable porque 'Otros ingresos' y 'Otros egresos' no tendrán un modelo asociado.
            $table->nullableMorphs('movable');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};
