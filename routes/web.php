<?php

use App\Http\Controllers\AffidavitController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\StudentEnrollmentController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect('/admin/login');
});

Route::get('/generate-affidavit/{student}', [AffidavitController::class, 'generatePdf'])->name('generate.affidavit.pdf');
Route::get('/generate-affidavit-instructor/{instructor}', [AffidavitController::class, 'generateInstructorPdf'])->name('generate.affidavit.instructor.pdf');
Route::get('/instructors/download-template', [InstructorController::class, 'downloadInstructorsTemplate'])->name('instructors.download-template');
Route::get('/inscription/{enrollmentId}/ticket', [StudentEnrollmentController::class, 'generateTicket'])->name('enrollment.ticket');
Route::get('/inscription-batch/{batchId}/ticket', [StudentEnrollmentController::class, 'generateBatchTicket'])->name('enrollment.batch.ticket');
