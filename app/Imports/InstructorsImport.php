<?php

namespace App\Imports;

use App\Models\Instructor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation; 

class InstructorsImport implements ToModel, WithHeadingRow
{    
    public function model(array $row)
    {
        return new Instructor([
            'first_names' => $row['nombres'],
            'last_names' => $row['apellidos'],
            'document_type' => $row['tipo_documento'],
            'document_number' => (string) $row['nro_documento'],
            'birth_date' => $row['fecha_nacimiento'],
            'nationality' => $row['nacionalidad'],
            'instructor_type' => $row['tipo_profesor'],
            'cell_phone' => $row['celular'] ?? null,
            'home_phone' => $row['telefono'] ?? null,
            'district' => $row['distrito'] ?? null,
            'address' => $row['direccion'] ?? null, 
        ]);
    }
    
    /* public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],            
            'tipo_documento' => ['required', 'in:DNI,PASAPORTE,Carné de Extranjería'],
            'nro_documento' => ['required', 'string', 'max:20', 'unique:instructors,document_number'],
            'fecha_nacimiento' => ['required', 'date'],
            'nacionalidad' => ['required', 'string', 'max:255'],
            'tipo_profesor' => ['required', 'in:VOLUNTARIO,POR HORAS'],
            'celular' => ['nullable', 'string', 'max:15'],
            'telefono' => ['nullable', 'string', 'max:15'],
            'distrito' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'], 
        ];
    } */
}