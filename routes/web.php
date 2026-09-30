<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\DueGenerationController;
use App\Http\Controllers\Web\DuesDashboardController;
use App\Http\Controllers\Web\FamilyController;
use App\Http\Controllers\Web\FeeCategoryController;
use App\Http\Controllers\Web\FeeStructureController;
use App\Http\Controllers\Web\StudentDiscountController;
use App\Http\Controllers\Web\StudentFeeSubscriptionController;
use App\Http\Controllers\Web\StudentRegistrationController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth', 'role:'.Role::ADMIN])->group(function (): void {
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

    Route::get('/fee-categories', [FeeCategoryController::class, 'index'])->name('fee-categories.index');
    Route::get('/fee-categories/create', [FeeCategoryController::class, 'create'])->name('fee-categories.create');
    Route::post('/fee-categories', [FeeCategoryController::class, 'store'])->name('fee-categories.store');
    Route::get('/fee-categories/{feeCategory}/edit', [FeeCategoryController::class, 'edit'])
        ->name('fee-categories.edit');
    Route::match(['put', 'patch'], '/fee-categories/{feeCategory}', [FeeCategoryController::class, 'update'])
        ->name('fee-categories.update');

    Route::get('/fee-structures', [FeeStructureController::class, 'index'])->name('fee-structures.index');
    Route::get('/fee-structures/create', [FeeStructureController::class, 'create'])->name('fee-structures.create');
    Route::post('/fee-structures', [FeeStructureController::class, 'store'])->name('fee-structures.store');

    Route::get('/students/{student}/discounts/create', [StudentDiscountController::class, 'create'])
        ->name('students.discounts.create');
    Route::post('/students/{student}/discounts', [StudentDiscountController::class, 'store'])
        ->name('students.discounts.store');

    Route::get('/students/{student}/fee-subscriptions/create', [StudentFeeSubscriptionController::class, 'create'])
        ->name('students.fee-subscriptions.create');
    Route::post('/students/{student}/fee-subscriptions', [StudentFeeSubscriptionController::class, 'store'])
        ->name('students.fee-subscriptions.store');

    Route::get('/due-generation/recurring', [DueGenerationController::class, 'recurringCreate'])
        ->name('due-generation.recurring.create');
    Route::post('/due-generation/recurring', [DueGenerationController::class, 'recurringStore'])
        ->name('due-generation.recurring.store');
    Route::get('/due-generation/events', [DueGenerationController::class, 'eventCreate'])
        ->name('due-generation.events.create');
    Route::post('/due-generation/events', [DueGenerationController::class, 'eventStore'])
        ->name('due-generation.events.store');

    Route::get('/dues-dashboard', [DuesDashboardController::class, 'index'])->name('dues-dashboard.index');
});
