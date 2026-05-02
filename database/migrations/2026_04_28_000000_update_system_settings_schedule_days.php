<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Día 19 — auto-cancel corre antes de replicar talleres
        DB::table('system_settings')
            ->where('key', 'auto_cancel_day')
            ->update([
                'value' => '14',
                'description' => 'Día del mes para anular inscripciones pendientes (debe correr antes de workshops:auto-replicate)',
            ]);

        DB::table('system_settings')
            ->where('key', 'auto_cancel_time')
            ->update(['value' => '23:59:59']);

        // Día 20 — workshops:auto-replicate ya tenía el valor correcto, solo se actualiza la descripción
        DB::table('system_settings')
            ->where('key', 'auto_generate_day')
            ->update([
                'value' => '15',
                'description' => 'Día del mes para replicar talleres al siguiente período (workshops:auto-replicate)',
            ]);

        DB::table('system_settings')
            ->where('key', 'auto_generate_time')
            ->update(['value' => '23:59:59']);

        // Día 22 — enrollments:auto-generate corre 2 días después para dar tiempo al admin
        DB::table('system_settings')
            ->where('key', 'auto_replicate_enrollments_day')
            ->update([
                'value' => '20',
                'description' => 'Día del mes para replicar inscripciones al siguiente período. Debe correr después de que el admin cancele feriados en workshop_classes.',
            ]);

        DB::table('system_settings')
            ->where('key', 'auto_replicate_enrollments_time')
            ->update(['value' => '23:59:59']);

    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'auto_cancel_day')->update(['value' => '28']);
        DB::table('system_settings')->where('key', 'auto_cancel_time')->update(['value' => '23:59:59']);
        DB::table('system_settings')->where('key', 'auto_generate_time')->update(['value' => '23:59:59']);
        DB::table('system_settings')->where('key', 'auto_replicate_enrollments_day')->update(['value' => '25']);
        DB::table('system_settings')->where('key', 'auto_replicate_enrollments_time')->update(['value' => '23:59:59']);
    }
};
