<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix July 2026 enrollment prices created by EnrollmentReplicationService with double discount.
     *
     * Bug: when workshop.number_of_classes < workshopTemplate.number_of_classes, the service
     * divided standard_monthly_fee by templateClasses and applied pricing_surcharge_percentage
     * on top of a fee the admin had already manually reduced for the holiday month.
     *
     * Example: ACTIVIDAD FISICA — admin set fee=18.75 for 3 classes.
     * Wrong calc: 18.75 / 4 × 1.2 × 3 = 16.88
     * Correct:    18.75 × 1.0           = 18.75
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

        $enrollments = DB::table('student_enrollments as se')
            ->join('instructor_workshops as iw', 'iw.id', '=', 'se.instructor_workshop_id')
            ->join('workshops as w', 'w.id', '=', 'iw.workshop_id')
            ->leftJoin('workshop_templates as wt', 'wt.id', '=', 'w.workshop_template_id')
            ->join('students as s', 's.id', '=', 'se.student_id')
            ->where('se.monthly_period_id', $julyPeriodId)
            ->whereNotNull('se.previous_enrollment_id')
            ->whereRaw('w.number_of_classes < COALESCE(wt.number_of_classes, w.number_of_classes)')
            ->select([
                'se.id as enrollment_id',
                'se.enrollment_batch_id',
                'se.total_amount as old_total',
                'se.number_of_classes as num_classes',
                'w.standard_monthly_fee',
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

            $correctTotal    = round((float) $row->standard_monthly_fee * $multiplier, 2);
            $correctPerClass = $row->num_classes > 0
                ? round($correctTotal / $row->num_classes, 4)
                : 0;

            $oldTotal = (float) $row->old_total;

            if (abs($oldTotal - $correctTotal) < 0.01) {
                continue;
            }

            DB::table('student_enrollments')->where('id', $row->enrollment_id)->update([
                'total_amount'       => $correctTotal,
                'price_per_quantity' => $correctPerClass,
                'pricing_notes'      => 'Replicación automática - precio recalculado (fix doble descuento 2026-06-23)',
                'updated_at'         => now(),
            ]);

            $batchDeltas[$row->enrollment_batch_id] =
                ($batchDeltas[$row->enrollment_batch_id] ?? 0) + ($correctTotal - $oldTotal);
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
        // Not reversible — original wrong values are not stored.
    }
};
