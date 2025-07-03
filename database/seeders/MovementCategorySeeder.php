<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MovementCategorySeeder extends Seeder
{
    public function run(): void
    {
        DB::table('movement_categories')->insert([
            [
                'name' => 'Pago a profesor',
                'type' => 'expense',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Cobro de taller',
                'type' => 'income',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Compra materiales',
                'type' => 'expense',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Otros ingresos',
                'type' => 'income',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Otros egresos',
                'type' => 'expense',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}
