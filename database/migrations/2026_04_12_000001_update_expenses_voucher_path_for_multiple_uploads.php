<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->longText('voucher_path')->nullable()->change();
        });

        DB::table('expenses')
            ->select(['id', 'voucher_path'])
            ->whereNotNull('voucher_path')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $value = trim((string) $row->voucher_path);

                    if ($value === '') {
                        DB::table('expenses')
                            ->where('id', $row->id)
                            ->update(['voucher_path' => null]);
                        continue;
                    }

                    $decoded = json_decode($value, true);

                    if (is_array($decoded)) {
                        continue;
                    }

                    DB::table('expenses')
                        ->where('id', $row->id)
                        ->update([
                            'voucher_path' => json_encode([$value], JSON_UNESCAPED_SLASHES),
                        ]);
                }
            });
    }

    public function down(): void
    {
        DB::table('expenses')
            ->select(['id', 'voucher_path'])
            ->whereNotNull('voucher_path')
            ->orderBy('id')
            ->chunkById(100, function ($rows): void {
                foreach ($rows as $row) {
                    $decoded = json_decode((string) $row->voucher_path, true);

                    if (! is_array($decoded)) {
                        continue;
                    }

                    DB::table('expenses')
                        ->where('id', $row->id)
                        ->update([
                            'voucher_path' => $decoded[0] ?? null,
                        ]);
                }
            });

        Schema::table('expenses', function (Blueprint $table) {
            $table->string('voucher_path')->nullable()->change();
        });
    }
};
