<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Contador global de correlativo de tickets, compartido por TODOS los cajeros
     * (RN-C3 corregida: la secuencia no es por cajero, es única para todo el sistema).
     * Se guarda como fila en `system_settings` (key = 'global_ticket_seq') y se
     * siembra con el correlativo máximo ya emitido en toda la tabla `tickets`
     * (parte numérica de 6 dígitos del ticket_code: {enrollment_code}-{6seq}[-{voucher}]).
     */
    public function up(): void
    {
        $maxSeq = DB::table('tickets')
            ->pluck('ticket_code')
            ->map(function ($code) {
                $parts = explode('-', $code);

                // Solo confiar en parts[1] si calza EXACTO con el formato del
                // generador (6 dígitos numéricos). Tickets link legacy de 2 partes
                // ({enrollment_code}-{voucher}) pueden tener un voucher puramente
                // numérico de otra longitud (ej. '01061705') que NO es un correlativo
                // real — tratarlo como tal corrompe el contador global.
                if (! isset($parts[1]) || strlen($parts[1]) !== 6 || ! ctype_digit($parts[1])) {
                    return 0;
                }

                return intval($parts[1]);
            })
            ->max();

        DB::table('system_settings')->updateOrInsert(
            ['key' => 'global_ticket_seq'],
            [
                'value' => (string) ($maxSeq ?? 0),
                'type' => 'integer',
                'description' => 'Último correlativo de ticket emitido (secuencia global, compartida por todos los cajeros)',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('system_settings')->where('key', 'global_ticket_seq')->delete();
    }
};
