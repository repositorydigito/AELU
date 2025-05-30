<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Workshop;

class WorkshopSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $workshops = [
            [
                'name' => 'Programación Web con PHP',
                'description' => 'Aprende a desarrollar aplicaciones web modernas con PHP y Laravel',
                'duration_hours' => 40,
                'price' => 350.00,
                'max_students' => 20,
                'status' => 'active',
            ],
            [
                'name' => 'Diseño Gráfico Digital',
                'description' => 'Domina las herramientas de diseño gráfico como Photoshop e Illustrator',
                'duration_hours' => 30,
                'price' => 280.00,
                'max_students' => 15,
                'status' => 'active',
            ],
            [
                'name' => 'Marketing Digital',
                'description' => 'Estrategias de marketing en redes sociales y publicidad online',
                'duration_hours' => 25,
                'price' => 220.00,
                'max_students' => 25,
                'status' => 'active',
            ],
            [
                'name' => 'Excel Avanzado',
                'description' => 'Funciones avanzadas, macros y análisis de datos en Excel',
                'duration_hours' => 20,
                'price' => 180.00,
                'max_students' => 30,
                'status' => 'active',
            ],
            [
                'name' => 'Inglés Básico',
                'description' => 'Curso básico de inglés para principiantes',
                'duration_hours' => 50,
                'price' => 300.00,
                'max_students' => 18,
                'status' => 'active',
            ],
        ];

        foreach ($workshops as $workshop) {
            Workshop::create($workshop);
        }
    }
}
