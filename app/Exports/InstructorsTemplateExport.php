<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection; 
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Support\Collection; 

class InstructorsTemplateExport implements FromCollection, WithHeadings
{
    /**
    * @return \Illuminate\Support\Collection
    * Este método devuelve una colección vacía, ya que solo necesitamos los encabezados para la plantilla.
    */
    public function collection()
    {        
        return new Collection([
            ['JUAN', 'PEREZ', 'DNI', '12345678', '2000-01-01', 'Peruana', 'PROFE001', 'VOLUNTARIO', '987654321', '01234567', 'Lima', 'Av. Siempre Viva 123']
        ]);
    }

    /**
    * @return array
    * Este método define los encabezados de las columnas para tu plantilla de Excel.
    * ¡Asegúrate de que coincidan exactamente con los nombres que esperas en tu importador!
    */
    public function headings(): array
    {
        return [
            'nombres',
            'apellidos',
            'tipo_documento',
            'nro_documento',
            'fecha_nacimiento',
            'nacionalidad',
            'codigo_profesor',
            'tipo_profesor',
            'celular',
            'telefono',
            'distrito',
            'direccion',
        ];
    }
}