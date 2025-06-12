<?php

namespace App\Imports;

use App\Models\Instructor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation; // Añade esto

class InstructorsImport implements ToModel, WithHeadingRow, WithValidation
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        //dd($row);
        // Accede a las claves del array $row en MAYÚSCULAS para que coincidan con los encabezados del Excel
        return new Instructor([
            'first_names' => $row['nombres'],
            'last_names' => $row['apellidos'],
            'document_type' => $row['tipo_documento'],
            'document_number' => (string) $row['nro_documento'],
            'birth_date' => $row['fecha_nacimiento'],
            'nationality' => $row['nacionalidad'],
            'instructor_code' => $row['codigo_profesor'],
            'instructor_type' => $row['tipo_profesor'],
            'cell_phone' => $row['celular'] ?? null,
            'home_phone' => $row['telefono'] ?? null,
            'district' => $row['distrito'] ?? null,
            'address' => $row['direccion'] ?? null, 
        ]);
    }

    /**
     * Define las reglas de validación. Las claves aquí también deben coincidir
     * con los encabezados en MAYÚSCULAS de tu archivo Excel.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'nombres' => ['required', 'string', 'max:255'],
            'apellidos' => ['required', 'string', 'max:255'],            
            'tipo_documento' => ['required', 'in:DNI,PASAPORTE,Carné de Extranjería'],
            'nro_documento' => ['required', 'string', 'max:20', 'unique:instructors,document_number'],
            'fecha_nacimiento' => ['required', 'date'],
            'nacionalidad' => ['required', 'string', 'max:255'],
            'codigo_profesor' => ['required', 'string', 'max:255', 'unique:instructors,instructor_code'],            
            'tipo_profesor' => ['required', 'in:VOLUNTARIO,POR HORAS'],
            'celular' => ['nullable', 'string', 'max:15'],
            'telefono' => ['nullable', 'string', 'max:15'],
            'distrito' => ['nullable', 'string', 'max:255'],
            'direccion' => ['nullable', 'string', 'max:255'], 
        ];
    }
}