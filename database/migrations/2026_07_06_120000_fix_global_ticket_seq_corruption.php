<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige `system_settings.global_ticket_seq`: la migración anterior
     * (2026_07_03_000000_seed_global_ticket_sequence) confundió el voucher
     * numérico de un ticket link legacy de 2 partes ({enrollment_code}-{voucher},
     * ej. '006-01061705') con un correlativo real de 6 dígitos, sembrando el
     * contador en ~1,061,705 en vez de su valor real (~513). Esto causó
     * colisiones (SQLSTATE 23000) al reintentar desde un número absurdamente
     * inflado en un caso y saltos gigantes de numeración en otro.
     * Recalcula el máximo real usando el mismo criterio estricto ya corregido
     * en la migración original (6 dígitos numéricos exactos).
     */
    public function up(): void
    {
        $maxSeq = DB::table('tickets')
            ->pluck('ticket_code')
            ->map(function ($code) {
                $parts = explode('-', $code);

                if (! isset($parts[1]) || strlen($parts[1]) !== 6 || ! ctype_digit($parts[1])) {
                    return 0;
                }

                return intval($parts[1]);
            })
            ->max();

        DB::table('system_settings')
            ->where('key', 'global_ticket_seq')
            ->update([
                'value' => (string) ($maxSeq ?? 0),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // Irreversible: no se puede reconstruir el valor corrupto anterior ni tiene sentido hacerlo.
    }
};
