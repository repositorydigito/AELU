<?php

namespace App\Http\Controllers;

use App\Exports\InstructorsTemplateExport;
use Maatwebsite\Excel\Facades\Excel;

class InstructorController extends Controller
{
    public function downloadInstructorsTemplate()
    {
        return Excel::download(new InstructorsTemplateExport, 'plantilla_profesores.xlsx');
    }
}
