<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Instructor;
use Carbon\Carbon;

class InstructorSeeder extends Seeder
{
    public function run(): void
    {
        $instructors = [
            [
                'last_names' => 'Shiroma',
                'first_names' => 'Nancy',
                'document_type' => 'DNI',
                'document_number' => '96350183',
            ],
            [
                'last_names' => 'Shiroma',
                'first_names' => 'Roxana',
                'document_type' => 'DNI',
                'document_number' => '40138373',
            ],
            [
                'last_names' => 'T. de Sakihama',
                'first_names' => 'Toyo',
                'document_type' => 'DNI',
                'document_number' => '28150660',
            ],
            [
                'last_names' => 'Ana A. de Shiroma',
                'first_names' => 'Nancy',
                'document_type' => 'DNI',
                'document_number' => '13015803',
            ],
            [
                'last_names' => 'Inamine',
                'first_names' => 'Amparo',
                'document_type' => 'DNI',
                'document_number' => '20961065',
            ],
            [
                'last_names' => 'Zúñiga',
                'first_names' => 'Rocío',
                'document_type' => 'DNI',
                'document_number' => '66413628',
            ],
            [
                'last_names' => 'Tamashiro',
                'first_names' => 'Ana',
                'document_type' => 'DNI',
                'document_number' => '19716597',
            ],
            [
                'last_names' => 'Malca',
                'first_names' => 'Patricia',
                'document_type' => 'DNI',
                'document_number' => '68365812',
            ],
            [
                'last_names' => 'Arakaki',
                'first_names' => 'Delia',
                'document_type' => 'DNI',
                'document_number' => '75449213',
            ],
            [
                'last_names' => 'H. de Morisaki',
                'first_names' => 'Margarita',
                'document_type' => 'DNI',
                'document_number' => '79598731',
            ],
            [
                'last_names' => 'A. de Moromi',
                'first_names' => 'Rosa',
                'document_type' => 'DNI',
                'document_number' => '32486461',
            ],
            [
                'last_names' => 'Watanabe',
                'first_names' => 'Felicita',
                'document_type' => 'DNI',
                'document_number' => '25132141',
            ],
            [
                'last_names' => 'Kiyan',
                'first_names' => 'Alfredo',
                'document_type' => 'DNI',
                'document_number' => '60038835',
            ],
            [
                'last_names' => 'Kuwae',
                'first_names' => 'Julio',
                'document_type' => 'DNI',
                'document_number' => '38078775',
            ],
            [
                'last_names' => 'Kanashiro',
                'first_names' => 'César',
                'document_type' => 'DNI',
                'document_number' => '95746639',
            ],
            [
                'last_names' => 'Seminario',
                'first_names' => 'Fabiola',
                'document_type' => 'DNI',
                'document_number' => '38526555',
            ],
            [
                'last_names' => 'Nakahodo',
                'first_names' => 'Lidia',
                'document_type' => 'DNI',
                'document_number' => '13686893',
            ],
            [
                'last_names' => 'B. de Koga',
                'first_names' => 'Sonia',
                'document_type' => 'DNI',
                'document_number' => '27230642',
            ],
            [
                'last_names' => 'Terukina',
                'first_names' => 'Luis',
                'document_type' => 'DNI',
                'document_number' => '41351192',
            ],
            [
                'last_names' => 'Ganaja',
                'first_names' => 'Manuel',
                'document_type' => 'DNI',
                'document_number' => '63491521',
            ],
            [
                'last_names' => 'Igei',
                'first_names' => 'Héctor',
                'document_type' => 'DNI',
                'document_number' => '32486561',
            ],
            [
                'last_names' => 'Salas',
                'first_names' => 'Gloria',
                'document_type' => 'DNI',
                'document_number' => '71601299',
            ],
            [
                'last_names' => 'Romero',
                'first_names' => 'Wilbert',
                'document_type' => 'DNI',
                'document_number' => '16832917',
            ],
            [
                'last_names' => 'Kian',
                'first_names' => 'Pamela',
                'document_type' => 'DNI',
                'document_number' => '15071773',
            ],
            [
                'last_names' => 'Fernández',
                'first_names' => 'Benjamin',
                'document_type' => 'DNI',
                'document_number' => '67650095',
            ],
            [
                'last_names' => 'Del Valle',
                'first_names' => 'Aldo',
                'document_type' => 'DNI',
                'document_number' => '71677487',
            ],
            [
                'last_names' => 'Rondán',
                'first_names' => 'José Neysser',
                'document_type' => 'DNI',
                'document_number' => '36811909',
            ],
            [
                'last_names' => 'Osores',
                'first_names' => 'Mariana',
                'document_type' => 'DNI',
                'document_number' => '51956610',
            ],
            [
                'last_names' => 'Jaime',
                'first_names' => 'Rafael',
                'document_type' => 'DNI',
                'document_number' => '65294093',
            ],
            [
                'last_names' => 'Díaz',
                'first_names' => 'Juan Pablo',
                'document_type' => 'DNI',
                'document_number' => '50918288',
            ],
            [
                'last_names' => 'García',
                'first_names' => 'Iris',
                'document_type' => 'DNI',
                'document_number' => '74792028',
            ],
            [
                'last_names' => 'Dávila',
                'first_names' => 'Aldo',
                'document_type' => 'DNI',
                'document_number' => '17430728',
            ],
            [
                'last_names' => 'Hurtado',
                'first_names' => 'Abelardo',
                'document_type' => 'DNI',
                'document_number' => '77526822',
            ],
            [
                'last_names' => 'Carrión',
                'first_names' => 'Juan Carlos',
                'document_type' => 'DNI',
                'document_number' => '21240613',
            ],

        ];

        foreach ($instructors as $instructor) {
            Instructor::create($instructor);
        }
    }
}
