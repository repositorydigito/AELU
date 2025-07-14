<?php

namespace App\Imports;

use App\Models\Student;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentsImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        return new Student([
            'last_names'      => $row['last_names'],
            'first_names'     => $row['first_names'],
            'document_type'   => $row['document_type'],
            'document_number' => $row['document_number'],
            'birth_date'      => $row['birth_date'],
            'nationality'     => $row['nationality'],
            'student_code'    => $row['student_code'],
            'category_partner'=> $row['category_partner'],
            'cell_phone'      => $row['cell_phone'],
            'home_phone'      => $row['home_phone'],
            'district'        => $row['district'],
            'address'         => $row['address'],
        ]);
    }
}
