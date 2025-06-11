<?php

namespace App\Imports;

use App\Models\Instructor;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class InstructorsImport implements ToModel, WithHeadingRow
{        
    public function model(array $row)
    {                 
        return new Instructor([
            'first_names' => $row['nombres'],
            'last_names' => $row['apellidos'],
            'document_type' => $row['tipo_documento'],
            'document_number' => $row['nro_documento'],
            'birth_date' => $row['fecha_nacimiento'] ?? null,
            'nationality' => $row['nacionalidad'] ?? null,
            'instructor_code' => $row['codigo_profesor'],
            'instructor_type' => $row['tipo_profesor'],
            'cell_phone' => $row['celular'] ?? null,
            'home_phone' => $row['telefono'] ?? null,
            'district' => $row['distrito'] ?? null,
            'address' => $row['direccion'] ?? null, 
        ]);
        
    }     
}