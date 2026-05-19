<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $existing = DB::table('workshop_templates')
            ->pluck('name')
            ->map(fn ($n) => strtolower($n))
            ->toArray();

        $workshops = DB::table('workshops')
            ->select('name', 'instructor_id', 'day_of_week', 'start_time', 'duration', 'capacity', 'modality', 'place')
            ->whereNotNull('name')
            ->orderByDesc('id')
            ->get()
            ->unique(fn ($w) => strtolower(trim($w->name)));

        $now = now();

        foreach ($workshops as $workshop) {
            if (in_array(strtolower($workshop->name), $existing)) {
                continue;
            }

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
                'additional_comments'          => null,
                'standard_monthly_fee'         => 60,
                'number_of_classes'            => 4,
                'pricing_surcharge_percentage' => 20,
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
