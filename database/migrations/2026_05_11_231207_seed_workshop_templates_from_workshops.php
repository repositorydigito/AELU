<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $mayPeriodId = DB::table('monthly_periods')
            ->where('year', 2026)
            ->where('month', 5)
            ->value('id');

        $workshops = DB::table('workshops')
            ->select('name', 'instructor_id', 'day_of_week', 'start_time', 'duration', 'capacity', 'modality', 'place', 'standard_monthly_fee', 'pricing_surcharge_percentage', 'number_of_classes', 'additional_comments')
            ->whereNotNull('name')
            ->where('monthly_period_id', $mayPeriodId)
            ->orderByDesc('id')
            ->get();

        $now = now();

        foreach ($workshops as $workshop) {
            DB::table('workshop_templates')->insert([
                'name'                         => $workshop->name,
                'instructor_id'                => $workshop->instructor_id,
                'day_of_week'                  => $workshop->day_of_week,
                'start_time'                   => $workshop->start_time,
                'duration'                     => $workshop->duration,
                'capacity'                     => $workshop->capacity,
                'modality'                     => $workshop->modality,
                'place'                        => $workshop->place,
                'description'                  => null,
                'additional_comments'          => $workshop->additional_comments,
                'standard_monthly_fee'         => $workshop->standard_monthly_fee,
                'number_of_classes'            => $workshop->number_of_classes,
                'pricing_surcharge_percentage' => $workshop->pricing_surcharge_percentage,
                'is_active'                    => true,
                'created_at'                   => $now,
                'updated_at'                   => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('workshop_templates')->truncate();
    }
};
