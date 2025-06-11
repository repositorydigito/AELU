<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AffidavitController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-affidavit/{student}', [AffidavitController::class, 'generatePdf'])->name('generate.affidavit.pdf');
Route::get('/generate-affidavit-instructor/{instructor}', [AffidavitController::class, 'generateInstructorPdf'])->name('generate.affidavit.instructor.pdf');
