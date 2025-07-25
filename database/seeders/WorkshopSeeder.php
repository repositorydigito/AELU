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
                'name' => 'Actividad Física',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'Baile',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'Baile Ondo',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'Gimnasia aeróbica',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'Happy Taiso',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'Karaoke - En español',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'NIHONGO - Básico',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'Pulseras MACRAMÉ',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'Tai Chi',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'Tenis de mesa',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'Terapia musical Yura',
                'description' => null,
                'standard_monthly_fee' => 20,
            ],
            [
                'name' => 'Baile Odori',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Bijouteria',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Cocina',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Computación - Básico',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Gateball',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Karaoke - Música variada',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Taiko',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Tejido a crochet',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Karaoke - En japonés',
                'description' => null,
                'standard_monthly_fee' => 35,
            ],
            [
                'name' => 'Ejercicios terapéuticos',
                'description' => null,
                'standard_monthly_fee' => 50,
            ],
            [
                'name' => 'Sanshin',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Yoga en silla - Virtual',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Uso de laptop - Básico',
                'description' => null,
                'standard_monthly_fee' => 40,
            ],
            [
                'name' => 'Uso de Smart - Inicial',
                'description' => null,
                'standard_monthly_fee' => 40,
            ],
            [
                'name' => 'Diseño Gráfico',
                'description' => null,
                'standard_monthly_fee' => 45,
            ],
            [
                'name' => 'Amigurumi',
                'description' => null,
                'standard_monthly_fee' => 40,
            ],
            [
                'name' => 'Agilidad Mental',
                'description' => null,
                'standard_monthly_fee' => 50,
            ],
            [
                'name' => 'Cardio Dance',
                'description' => null,
                'standard_monthly_fee' => 25,
            ],
            [
                'name' => 'Dibujo y Pintura',
                'description' => null,
                'standard_monthly_fee' => 50,
            ],
            [
                'name' => 'Ejercicios en el agua',
                'description' => null,
                'standard_monthly_fee' => 50,
            ],
            [
                'name' => 'Danzas Peruanas',
                'description' => null,
                'standard_monthly_fee' => 35,
            ],
            [
                'name' => 'Marinera Norteña',
                'description' => null,
                'standard_monthly_fee' => 35,
            ],
            [
                'name' => 'Percusión',
                'description' => null,
                'standard_monthly_fee' => 60,
            ],
            [
                'name' => 'Meditación Guiada',
                'description' => null,
                'standard_monthly_fee' => 35,
            ],
            [
                'name' => 'Pilates',
                'description' => null,
                'standard_monthly_fee' => 40,
            ],
            [
                'name' => 'Estimulación Cognitiva',
                'description' => null,
                'standard_monthly_fee' => 50,
            ],
            [
                'name' => 'Memoria Activa',
                'description' => null,
                'standard_monthly_fee' => 50,
            ],
            [
                'name' => 'Yoga en Silla',
                'description' => null,
                'standard_monthly_fee' => 35,
            ],
            [
                'name' => 'Yoga en Mat',
                'description' => null,
                'standard_monthly_fee' => 40,
            ],
            [
                'name' => 'Música y Canto',
                'description' => null,
                'standard_monthly_fee' => 60,
            ],
            [
                'name' => 'Teclado',
                'description' => null,
                'standard_monthly_fee' => 60,
            ],
            [
                'name' => 'Técnicas de Karaoke',
                'description' => null,
                'standard_monthly_fee' => 60,
            ],
        ];

        foreach ($workshops as $workshop) {
            Workshop::create($workshop);
        }
    }
}
