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
                'hourly_rate' => null,
            ],
            [
                'name' => 'Baile',
                'description' => null,
                'standard_monthly_fee' => 20,
                'hourly_rate' => null,
            ],            
            [
                'name' => 'Baile Ondo',
                'description' => null,
                'standard_monthly_fee' => 20,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Gimnasia aeróbica',	
                'description' => null,
                'standard_monthly_fee' => 20,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Happy Taiso',
                'description' => null,
                'standard_monthly_fee' => 20,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Karaoke - En español',
                'description' => null,
                'standard_monthly_fee' => 20,
                'hourly_rate' => null,
            ],
            [
                'name' => 'NIHONGO - Básico',
                'description' => null,
                'standard_monthly_fee' => 20,
                'hourly_rate' => null,                
            ],
            [
                'name' => 'Pulseras MACRAMÉ',
                'description' => null,
                'standard_monthly_fee' => 20,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Tai Chi',
                'description' => null,
                'standard_monthly_fee' => 20,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Tenis de mesa',
                'description' => null,
                'standard_monthly_fee' => 20,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Terapia musical Yura',
                'description' => null,
                'standard_monthly_fee' => 20,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Baile Odori',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Bijouteria',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Cocina',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Computación - Básico',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Gateball',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Karaoke - Música variada',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Taiko',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Tejido a crochet',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Karaoke - En japonés',
                'description' => null,
                'standard_monthly_fee' => 35,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Ejercicios terapéuticos',
                'description' => null,
                'standard_monthly_fee' => 50,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Sanshin',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Yoga en silla - Virtual',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Uso de laptop - Básico',
                'description' => null,
                'standard_monthly_fee' => 40,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Uso de Smart - Inicial',
                'description' => null,
                'standard_monthly_fee' => 40,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Diseño Gráfico',
                'description' => null,
                'standard_monthly_fee' => 45,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Amigurumi',
                'description' => null,
                'standard_monthly_fee' => 40,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Agilidad Mental',
                'description' => null,
                'standard_monthly_fee' => 50,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Cardio Dance',
                'description' => null,
                'standard_monthly_fee' => 25,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Dibujo y Pintura',
                'description' => null,
                'standard_monthly_fee' => 50,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Ejercicios en el agua',
                'description' => null,
                'standard_monthly_fee' => 50,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Danzas Peruanas',
                'description' => null,
                'standard_monthly_fee' => 35,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Marinera Norteña',
                'description' => null,
                'standard_monthly_fee' => 35,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Percusión',
                'description' => null,
                'standard_monthly_fee' => 60,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Meditación Guiada',
                'description' => null,
                'standard_monthly_fee' => 35,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Pilates',
                'description' => null,
                'standard_monthly_fee' => 40,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Estimulación Cognitiva',	
                'description' => null,
                'standard_monthly_fee' => 50,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Memoria Activa',
                'description' => null,
                'standard_monthly_fee' => 50,
                'hourly_rate' => null,
            ],            
            [
                'name' => 'Yoga en Silla',
                'description' => null,
                'standard_monthly_fee' => 35,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Yoga en Mat',
                'description' => null,
                'standard_monthly_fee' => 40,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Música y Canto',
                'description' => null,
                'standard_monthly_fee' => 60,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Teclado',
                'description' => null,
                'standard_monthly_fee' => 60,
                'hourly_rate' => null,
            ],
            [
                'name' => 'Técnicas de Karaoke',
                'description' => null,
                'standard_monthly_fee' => 60,
                'hourly_rate' => null,
            ],
        ];

        foreach ($workshops as $workshop) {
            Workshop::create($workshop);
        }
    }
}
