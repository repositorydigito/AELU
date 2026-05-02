<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class HolidaySeeder extends Seeder
{
    public function run(): void
    {
        $year = 2026;

        $holidays = [
            [
                'name'             => 'Año Nuevo',
                'date'             => "{$year}-01-01",
                'description'      => 'Celebración del inicio del nuevo año.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Jueves Santo',
                'date'             => "{$year}-04-02",
                'description'      => 'Festividad religiosa cristiana que conmemora la última cena de Jesús.',
                'is_recurring'     => false,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Viernes Santo',
                'date'             => "{$year}-04-03",
                'description'      => 'Conmemoración religiosa de la pasión y muerte de Jesucristo.',
                'is_recurring'     => false,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Día del Trabajo',
                'date'             => "{$year}-05-01",
                'description'      => 'Homenaje a la lucha por los derechos laborales y los trabajadores.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Batalla de Arica y Día de la Bandera',
                'date'             => "{$year}-06-07",
                'description'      => 'Conmemoración de la batalla contra las fuerzas chilenas en 1880 y homenaje al símbolo patrio.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'San Pedro y San Pablo',
                'date'             => "{$year}-06-29",
                'description'      => 'Festividad religiosa en honor a los apóstoles Pedro y Pablo, patronos de los pescadores.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Día de la Fuerza Aérea del Perú',
                'date'             => "{$year}-07-23",
                'description'      => 'Conmemoración del sacrificio del capitán José Abelardo Quiñones Gonzales.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Fiestas Patrias - Declaración de la Independencia',
                'date'             => "{$year}-07-28",
                'description'      => 'Celebración nacional por la independencia del Perú de la corona española.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Fiestas Patrias - Día de las Glorias Patrias',
                'date'             => "{$year}-07-29",
                'description'      => 'Continuación de las festividades por la independencia y homenaje a las instituciones militares.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Batalla de Junín',
                'date'             => "{$year}-08-06",
                'description'      => 'Conmemoración del triunfo de las fuerzas patriotas en la Pampa de Junín en 1824.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Santa Rosa de Lima',
                'date'             => "{$year}-08-30",
                'description'      => 'Festividad en honor a la primera santa de América y patrona de la Policía Nacional.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Combate de Angamos',
                'date'             => "{$year}-10-08",
                'description'      => 'Homenaje al sacrificio del almirante Miguel Grau y la Marina de Guerra del Perú.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Día de Todos los Santos',
                'date'             => "{$year}-11-01",
                'description'      => 'Tradición religiosa dedicada a honrar a todos los santos y recordar a los difuntos.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Inmaculada Concepción',
                'date'             => "{$year}-12-08",
                'description'      => 'Festividad religiosa católica en honor a la Virgen María.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Batalla de Ayacucho',
                'date'             => "{$year}-12-09",
                'description'      => 'Conmemoración de la batalla de 1824 que selló la independencia del Perú y América.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
            [
                'name'             => 'Navidad',
                'date'             => "{$year}-12-25",
                'description'      => 'Festividad religiosa que celebra el nacimiento de Jesucristo.',
                'is_recurring'     => true,
                'affects_classes'  => true,
            ],
        ];

        foreach ($holidays as $holiday) {
            Holiday::firstOrCreate(
                ['date' => $holiday['date'], 'name' => $holiday['name']],
                [
                    'description'     => $holiday['description'],
                    'is_recurring'    => $holiday['is_recurring'],
                    'affects_classes' => $holiday['affects_classes'],
                ]
            );
        }
    }
}
