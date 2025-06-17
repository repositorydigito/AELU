<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AffidavitController;
use App\Http\Controllers\InstructorController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/generate-affidavit/{student}', [AffidavitController::class, 'generatePdf'])->name('generate.affidavit.pdf');
Route::get('/instructors/download-template', [InstructorController::class, 'downloadInstructorsTemplate'])->name('instructors.download-template');
     
