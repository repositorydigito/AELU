<?php

namespace Database\Seeders;

use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

/* ->options([
    'Individual PRE-PAMA' => 'Individual PRE-PAMA (< 60 años)',
    'Individual' => 'Individual (60-64 años)',
    'Transitorio Individual' => 'Transitorio Individual (65+ años)',
    'Transitorio Exonerado' => 'Transitorio Exonerado (65+ años - Sin pago)',
    'Hijo de Fundador' => 'Hijo de Fundador (Sin pago)',
    'Vitalicios' => 'Vitalicios (Sin pago)',
]) */

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $students = [
            [
                'last_names' => 'Chacón de Nakayoshi',
                'first_names' => 'Julia Elena',
                'document_type' => 'DNI',
                'document_number' => '12345679',
                'birth_date' => Carbon::create('1952', '01', '17'),
                'student_code' => '00912505',
                'category_partner' => 'Vitalicios',
            ],
            [
                'last_names' => 'Chinen Kian',
                'first_names' => 'Olga',
                'document_type' => 'DNI',
                'document_number' => '12345678',
                'birth_date' => Carbon::create('1947', '12', '12'),
                'student_code' => 'H0039100',
                'category_partner' => 'Hijo de Fundador',
            ],
            [
                'last_names' => 'Delgado Peralta',
                'first_names' => 'Teresa Lucía',
                'document_type' => 'DNI',
                'document_number' => '12345677',
                'birth_date' => Carbon::create('1945', '08', '20'),
                'student_code' => '00145501',
                'category_partner' => 'Vitalicios',
            ],
            [
                'last_names' => 'Isa Asato de Nakama',
                'first_names' => 'Violeta',
                'document_type' => 'DNI',
                'document_number' => '12345676',
                'birth_date' => Carbon::create('1945', '03', '18'),
                'student_code' => '00104400',
                'category_partner' => 'Vitalicios',
            ],
            [
                'last_names' => 'Kiyan Arakaki',
                'first_names' => 'Juana Rosa',
                'document_type' => 'DNI',
                'document_number' => '12345675',
                'birth_date' => Carbon::create('1951', '10', '21'),
                'student_code' => '01441706',
                'category_partner' => null,
            ],
            [
                'last_names' => 'Kiyan de Fukuhara',
                'first_names' => 'Juana',
                'document_type' => 'DNI',
                'document_number' => '12345674',
                'birth_date' => Carbon::create('1936', '01', '12'),
                'student_code' => 'T0039700',
                'category_partner' => 'Transitorio Mayor de 75',
            ],
            [
                'last_names' => 'Matsukawa de Tomioka',
                'first_names' => 'Tamico Teresa',
                'document_type' => 'DNI',
                'document_number' => '12345673',
                'birth_date' => Carbon::create('1945', '03', '23'),
                'student_code' => 'T0035600',
                'category_partner' => 'Transitorio Mayor de 75',
            ],
            [
                'last_names' => 'Miyagi Chibana de Tamashiro',
                'first_names' => 'Juana',
                'document_type' => 'DNI',
                'document_number' => '12345672',
                'birth_date' => Carbon::create('1947', '01', '17'),
                'student_code' => '00023501',
                'category_partner' => 'Vitalicios',
            ],
            [
                'last_names' => 'Nagahama Nakamura',
                'first_names' => 'Yolanda',
                'document_type' => 'DNI',
                'document_number' => '12345671',
                'birth_date' => Carbon::create('1939', '07', '18'),
                'student_code' => 'H0028600',
                'category_partner' => 'Hijo de Fundador',
            ],
            [
                'last_names' => 'Nakama de Fukuhara',
                'first_names' => 'Teresa',
                'document_type' => 'DNI',
                'document_number' => '12345670',
                'birth_date' => Carbon::create('1943', '06', '30'),
                'student_code' => '00480001',
                'category_partner' => 'Vitalicios',
            ],

            // Agregar más estudiantes aquí si es necesario
        ];

        foreach ($students as $student) {
            Student::create($student);
        }

    }
}
