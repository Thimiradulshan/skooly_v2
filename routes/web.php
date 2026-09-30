<?php

use App\Http\Controllers\Web\FamilyController;
use App\Http\Controllers\Web\StudentRegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/families', [FamilyController::class, 'index'])->name('families.index');
Route::get('/families/create', [FamilyController::class, 'create'])->name('families.create');
Route::post('/families', [FamilyController::class, 'store'])->name('families.store');
Route::get('/families/{family}', [FamilyController::class, 'show'])->name('families.show');
Route::get('/families/{family}/edit', [FamilyController::class, 'edit'])->name('families.edit');
Route::match(['put', 'patch'], '/families/{family}', [FamilyController::class, 'update'])->name('families.update');

Route::get('/families/{family}/students/create', [StudentRegistrationController::class, 'create'])
    ->name('families.students.create');
Route::post('/families/{family}/students', [StudentRegistrationController::class, 'store'])
    ->name('families.students.store');
