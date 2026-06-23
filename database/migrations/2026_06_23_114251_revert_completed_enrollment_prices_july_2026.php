<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Revert price corrections applied by fix_july_2026_enrollment_prices_double_discount
     * for enrollments whose batch is already 'completed' (fully paid).
     *
     * Those batches were paid at the original (wrong) price before the fix ran.
     * Keeping the corrected price creates a discrepancy between what was charged
     * and what the system records. Revert them to the price that was actually collected.
     *
     * Pending and to_pay batches keep the corrected price — they haven't been fully paid yet.
     */
    public function up(): void
    {
        $julyPeriodId = DB::table('monthly_periods')
            ->where('year', 2026)
            ->where('month', 7)
            ->value('id');

        if (! $julyPeriodId) {
            return;
        }

        // Find only the enrollments that:
        // 1. Were modified by the previous migration (identified by pricing_notes marker)
        // 2. Belong to a batch that was already completed (paid) before the fix ran
        $enrollments = DB::table('student_enrollments as se')
            ->join('enrollment_batches as eb', 'eb.id', '=', 'se.enrollment_batch_id')
            ->join('instructor_workshops as iw', 'iw.id', '=', 'se.instructor_workshop_id')
            ->join('workshops as w', 'w.id', '=', 'iw.workshop_id')
            ->leftJoin('workshop_templates as wt', 'wt.id', '=', 'w.workshop_template_id')
            ->join('students as s', 's.id', '=', 'se.student_id')
            ->where('se.monthly_period_id', $julyPeriodId)
            ->whereNotNull('se.previous_enrollment_id')
            ->where('se.pricing_notes', 'like', '%fix doble descuento 2026-06-23%')
            ->where('eb.payment_status', 'completed')
            ->whereRaw('w.number_of_classes < COALESCE(wt.number_of_classes, w.number_of_classes)')
            ->select([
                'se.id as enrollment_id',
                'se.enrollment_batch_id',
                'se.total_amount as current_total',
                'se.number_of_classes as num_classes',
                'w.standard_monthly_fee',
                'w.pricing_surcharge_percentage',
                'w.number_of_classes as actual_classes',
                DB::raw('COALESCE(wt.number_of_classes, w.number_of_classes) as template_classes'),
                's.category_partner',
            ])
            ->get();

        $batchDeltas = [];

        foreach ($enrollments as $row) {
            $multiplier = match ($row->category_partner) {
                'PRE PAMA 50+' => 2.0,
                'PRE PAMA 55+' => 1.5,
                default        => 1.0,
            };

            $surchargeMultiplier = 1 + ((float) $row->pricing_surcharge_percentage / 100);

            // Recalculate the original (wrong) price that was actually charged/paid
            $originalPrice = round(
                ((float) $row->standard_monthly_fee / $row->template_classes)
                    * $surchargeMultiplier
                    * $row->actual_classes
                    * $multiplier,
                2
            );

            $originalPerClass = $row->num_classes > 0
                ? round($originalPrice / $row->num_classes, 4)
                : 0;

            $currentTotal = (float) $row->current_total;

            if (abs($currentTotal - $originalPrice) < 0.01) {
                continue; // already at original price (shouldn't happen but be safe)
            }

            DB::table('student_enrollments')->where('id', $row->enrollment_id)->update([
                'total_amount'       => $originalPrice,
                'price_per_quantity' => $originalPerClass,
                'pricing_notes'      => 'Replicación automática - precio recalculado (revertido 2026-06-23: lote ya pagado)',
                'updated_at'         => now(),
            ]);

            // Delta is negative (we're reducing back to original)
            $delta = $originalPrice - $currentTotal;
            $batchDeltas[$row->enrollment_batch_id] =
                ($batchDeltas[$row->enrollment_batch_id] ?? 0) + $delta;
        }

        foreach ($batchDeltas as $batchId => $delta) {
            DB::table('enrollment_batches')
                ->where('id', $batchId)
                ->update([
                    'total_amount' => DB::raw('ROUND(total_amount + ' . $delta . ', 2)'),
                    'updated_at'   => now(),
                ]);
        }
    }

    public function down(): void
    {
        // Not reversible — would require the exact intermediate values.
    }
};
