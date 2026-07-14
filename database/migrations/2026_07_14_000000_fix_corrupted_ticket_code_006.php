<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Corrige el ticket_code corrupto '006-1061706' (id 2997, cajero "ionaga").
     *
     * Causa: getNextSequential() escaneaba todos los tickets del cajero e interpretaba
     * el voucher del ticket legacy '006-01061705' (id 1106, formato antiguo de 2 partes
     * {enrollment_code}-{voucher}, voucher puramente numérico de 8 dígitos) como si fuera
     * un correlativo real de 6 dígitos, inflando el máximo. El siguiente pago real del
     * cajero (11/07/2026) generó '006-1061706' en vez de continuar la secuencia real
     * desde '006-000497' (08/07/2026) → correspondía '006-000498'.
     *
     * El ticket legacy 006-01061705 NO se toca (formato anterior, no se renumera —
     * RN-C4). Solo se corrige el ticket recién generado por el bug.
     */
    public function up(): void
    {
        $oldCode = '006-1061706';
        $newCode = '006-000498';

        $ticket = DB::table('tickets')->where('ticket_code', $oldCode)->first();

        if (! $ticket) {
            return;
        }

        if (DB::table('tickets')->where('ticket_code', $newCode)->exists()) {
            // Ya corregido o el código destino fue tomado por otro ticket real — no pisar nada.
            return;
        }

        DB::table('tickets')
            ->where('id', $ticket->id)
            ->update(['ticket_code' => $newCode]);
    }

    public function down(): void
    {
        $newCode = '006-000498';
        $oldCode = '006-1061706';

        DB::table('tickets')
            ->where('ticket_code', $newCode)
            ->update(['ticket_code' => $oldCode]);
    }
};
