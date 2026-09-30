<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\DueGenerationController;
use App\Http\Controllers\Web\DuesDashboardController;
use App\Http\Controllers\Web\EventChargeController;
use App\Http\Controllers\Web\EventController;
use App\Http\Controllers\Web\EventParticipationController;
use App\Http\Controllers\Web\FamilyController;
use App\Http\Controllers\Web\FeeCategoryController;
use App\Http\Controllers\Web\FeeStructureController;
use App\Http\Controllers\Web\PaymentCollectionController;
use App\Http\Controllers\Web\PaymentReminderController;
use App\Http\Controllers\Web\PromotionBatchController;
use App\Http\Controllers\Web\ReceiptController;
use App\Http\Controllers\Web\StudentDiscountController;
use App\Http\Controllers\Web\StudentFeeSubscriptionController;
use App\Http\Controllers\Web\StudentRegistrationController;
use App\Models\Role;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('login');
});

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store'])->name('login.store');
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth', 'role:'.Role::ADMIN])->group(function (): void {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

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

    Route::get('/events', [EventController::class, 'index'])->name('events.index');
    Route::get('/events/create', [EventController::class, 'create'])->name('events.create');
    Route::post('/events', [EventController::class, 'store'])->name('events.store');
    Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
    Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
    Route::match(['put', 'patch'], '/events/{event}', [EventController::class, 'update'])->name('events.update');

    Route::get('/events/{event}/charges/create', [EventChargeController::class, 'create'])
        ->name('events.charges.create');
    Route::post('/events/{event}/charges', [EventChargeController::class, 'store'])
        ->name('events.charges.store');

    Route::get('/events/{event}/participation/create', [EventParticipationController::class, 'create'])
        ->name('events.participation.create');
    Route::post('/events/{event}/participation', [EventParticipationController::class, 'store'])
        ->name('events.participation.store');

    Route::get('/families/{family}/payments/create', [PaymentCollectionController::class, 'create'])
        ->name('families.payments.create');
    Route::post('/families/{family}/payments', [PaymentCollectionController::class, 'store'])
        ->name('families.payments.store');
    Route::get('/payments/{payment}', [PaymentCollectionController::class, 'show'])->name('payments.show');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');

    Route::get('/payment-reminders', [PaymentReminderController::class, 'index'])
        ->name('payment-reminders.index');
    Route::get('/payment-reminders/generate', [PaymentReminderController::class, 'create'])
        ->name('payment-reminders.create');
    Route::post('/payment-reminders/generate', [PaymentReminderController::class, 'store'])
        ->name('payment-reminders.store');
    Route::get('/payment-reminders/{paymentReminder}', [PaymentReminderController::class, 'show'])
        ->name('payment-reminders.show');

    Route::get('/promotion-batches', [PromotionBatchController::class, 'index'])
        ->name('promotion-batches.index');
    Route::get('/promotion-batches/create', [PromotionBatchController::class, 'create'])
        ->name('promotion-batches.create');
    Route::post('/promotion-batches', [PromotionBatchController::class, 'store'])
        ->name('promotion-batches.store');
    Route::get('/promotion-batches/{promotionBatch}', [PromotionBatchController::class, 'show'])
        ->name('promotion-batches.show');
    Route::post('/promotion-batches/{promotionBatch}/confirm', [PromotionBatchController::class, 'confirm'])
        ->name('promotion-batches.confirm');
});
