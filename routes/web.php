<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Web\AcademicYearController;
use App\Http\Controllers\Web\AdminDashboardController;
use App\Http\Controllers\Web\AuditLogController;
use App\Http\Controllers\Web\DueGenerationController;
use App\Http\Controllers\Web\DuesDashboardController;
use App\Http\Controllers\Web\EnrollmentPlacementController;
use App\Http\Controllers\Web\EventChargeController;
use App\Http\Controllers\Web\EventController;
use App\Http\Controllers\Web\EventParticipationController;
use App\Http\Controllers\Web\FamilyController;
use App\Http\Controllers\Web\FeeCategoryController;
use App\Http\Controllers\Web\FeeStructureController;
use App\Http\Controllers\Web\GradeController;
use App\Http\Controllers\Web\GuardianController;
use App\Http\Controllers\Web\PaymentCollectionController;
use App\Http\Controllers\Web\PaymentReminderController;
use App\Http\Controllers\Web\PaymentReversalController;
use App\Http\Controllers\Web\PromotionBatchController;
use App\Http\Controllers\Web\ReceiptController;
use App\Http\Controllers\Web\SchoolSettingController;
use App\Http\Controllers\Web\SectionController;
use App\Http\Controllers\Web\SectionYearAssignmentController;
use App\Http\Controllers\Web\StudentController;
use App\Http\Controllers\Web\StudentDiscountController;
use App\Http\Controllers\Web\StudentFeeSubscriptionController;
use App\Http\Controllers\Web\StudentRegistrationController;
use App\Http\Controllers\Web\SubjectController;
use App\Http\Controllers\Web\TeacherAssignmentController;
use App\Http\Controllers\Web\TeacherQualificationController;
use App\Http\Controllers\Web\TermController;
use App\Http\Controllers\Web\UserController;
use App\Models\Payment;
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

Route::middleware(['auth', 'role:'.Role::ADMIN.','.Role::SUPERADMIN])->group(function (): void {
    Route::resource('users', UserController::class)->except('destroy');
});

Route::middleware(['auth', 'role:'.Role::ADMIN])->group(function (): void {
    Route::get('/admin', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('audit-logs.index');
    Route::get('/audit-logs/export/csv', [AuditLogController::class, 'exportCsv'])->name('audit-logs.export.csv');
    Route::get('/audit-logs/export/pdf', [AuditLogController::class, 'exportPdf'])->name('audit-logs.export.pdf');
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('audit-logs.show');

    Route::resource('academic-years', AcademicYearController::class)->except('destroy');
    Route::resource('terms', TermController::class)->except('destroy');
    Route::resource('grades', GradeController::class)->except('destroy');
    Route::resource('sections', SectionController::class)->except('destroy');
    Route::post('/academic-years/{academicYear}/archive', [AcademicYearController::class, 'archive'])->name('academic-years.archive');
    Route::post('/academic-years/{academicYear}/restore', [AcademicYearController::class, 'restore'])->name('academic-years.restore');
    Route::post('/terms/{term}/archive', [TermController::class, 'archive'])->name('terms.archive');
    Route::post('/terms/{term}/restore', [TermController::class, 'restore'])->name('terms.restore');
    Route::post('/grades/{grade}/archive', [GradeController::class, 'archive'])->name('grades.archive');
    Route::post('/grades/{grade}/restore', [GradeController::class, 'restore'])->name('grades.restore');
    Route::post('/sections/{section}/archive', [SectionController::class, 'archive'])->name('sections.archive');
    Route::post('/sections/{section}/restore', [SectionController::class, 'restore'])->name('sections.restore');
    Route::resource('subjects', SubjectController::class);
    Route::get('/teacher-qualifications', [TeacherQualificationController::class, 'index'])->name('teacher-qualifications.index');
    Route::get('/teacher-qualifications/create', [TeacherQualificationController::class, 'create'])->name('teacher-qualifications.create');
    Route::post('/teacher-qualifications', [TeacherQualificationController::class, 'store'])->name('teacher-qualifications.store');
    Route::get('/teacher-qualifications/{teacher}', [TeacherQualificationController::class, 'show'])->name('teacher-qualifications.show');
    Route::get('/teacher-assignments', [TeacherAssignmentController::class, 'index'])->name('teacher-assignments.index');
    Route::get('/teacher-assignments/create', [TeacherAssignmentController::class, 'create'])->name('teacher-assignments.create');
    Route::post('/teacher-assignments', [TeacherAssignmentController::class, 'store'])->name('teacher-assignments.store');
    Route::get('/teacher-assignments/{teacherAssignment}', [TeacherAssignmentController::class, 'show'])->name('teacher-assignments.show');
    Route::get('/section-year-assignments', [SectionYearAssignmentController::class, 'index'])->name('section-year-assignments.index');
    Route::get('/section-year-assignments/create', [SectionYearAssignmentController::class, 'create'])->name('section-year-assignments.create');
    Route::post('/section-year-assignments', [SectionYearAssignmentController::class, 'store'])->name('section-year-assignments.store');
    Route::get('/section-year-assignments/{sectionYearAssignment}', [SectionYearAssignmentController::class, 'show'])->name('section-year-assignments.show');
    Route::get('/school-settings', [SchoolSettingController::class, 'edit'])->name('school-settings.edit');
    Route::match(['put', 'patch'], '/school-settings', [SchoolSettingController::class, 'update'])
        ->name('school-settings.update');

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
    Route::get('/families/{family}/guardians/create', [GuardianController::class, 'create'])
        ->name('families.guardians.create');
    Route::post('/families/{family}/guardians', [GuardianController::class, 'store'])
        ->name('families.guardians.store');
    Route::get('/guardians/{guardian}', [GuardianController::class, 'show'])->name('guardians.show');
    Route::get('/guardians/{guardian}/edit', [GuardianController::class, 'edit'])->name('guardians.edit');
    Route::match(['put', 'patch'], '/guardians/{guardian}', [GuardianController::class, 'update'])->name('guardians.update');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::get('/students/{student}/edit', [StudentController::class, 'edit'])->name('students.edit');
    Route::match(['put', 'patch'], '/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::post('/students/{student}/guardians', [StudentController::class, 'linkGuardian'])->name('students.guardians.store');
    Route::delete('/students/{student}/guardians/{guardian}', [StudentController::class, 'unlinkGuardian'])->name('students.guardians.destroy');
    Route::get('/enrollments/{enrollment}/placements/create', [EnrollmentPlacementController::class, 'create'])->name('enrollments.placements.create');
    Route::post('/enrollments/{enrollment}/placements', [EnrollmentPlacementController::class, 'store'])->name('enrollments.placements.store');

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
    Route::get('/fee-structures/{feeStructure}/edit', [FeeStructureController::class, 'edit'])->name('fee-structures.edit');
    Route::match(['put', 'patch'], '/fee-structures/{feeStructure}', [FeeStructureController::class, 'update'])
        ->name('fee-structures.update');

    Route::get('/students/{student}/discounts/create', [StudentDiscountController::class, 'create'])
        ->name('students.discounts.create');
    Route::get('/students/{student}/discounts', [StudentDiscountController::class, 'index'])
        ->name('students.discounts.index');
    Route::post('/students/{student}/discounts', [StudentDiscountController::class, 'store'])
        ->name('students.discounts.store');
    Route::post('/discounts/{discount}/deactivate', [StudentDiscountController::class, 'deactivate'])
        ->name('discounts.deactivate');

    Route::get('/students/{student}/fee-subscriptions/create', [StudentFeeSubscriptionController::class, 'create'])
        ->name('students.fee-subscriptions.create');
    Route::get('/students/{student}/fee-subscriptions', [StudentFeeSubscriptionController::class, 'index'])
        ->name('students.fee-subscriptions.index');
    Route::post('/students/{student}/fee-subscriptions', [StudentFeeSubscriptionController::class, 'store'])
        ->name('students.fee-subscriptions.store');
    Route::post('/student-fee-subscriptions/{studentFeeSubscription}/end', [StudentFeeSubscriptionController::class, 'end'])
        ->name('student-fee-subscriptions.end');

    Route::get('/due-generation/recurring', [DueGenerationController::class, 'recurringCreate'])
        ->name('due-generation.recurring.create');
    Route::post('/due-generation/recurring', [DueGenerationController::class, 'recurringStore'])
        ->name('due-generation.recurring.store');
    Route::get('/due-generation/events', [DueGenerationController::class, 'eventCreate'])
        ->name('due-generation.events.create');
    Route::post('/due-generation/events', [DueGenerationController::class, 'eventStore'])
        ->name('due-generation.events.store');

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
    Route::get('/events/{event}/charges/{charge}/edit', [EventChargeController::class, 'edit'])
        ->scopeBindings()
        ->name('events.charges.edit');
    Route::match(['put', 'patch'], '/events/{event}/charges/{charge}', [EventChargeController::class, 'update'])
        ->scopeBindings()
        ->name('events.charges.update');

    Route::get('/events/{event}/participation/create', [EventParticipationController::class, 'create'])
        ->name('events.participation.create');
    Route::post('/events/{event}/participation', [EventParticipationController::class, 'store'])
        ->name('events.participation.store');

    Route::get('/receipts/{receipt}/pdf', [ReceiptController::class, 'downloadPdf'])->name('receipts.pdf');
    Route::get('/payment-reminders/generate', [PaymentReminderController::class, 'create'])
        ->name('payment-reminders.create');
    Route::post('/payment-reminders/generate', [PaymentReminderController::class, 'store'])
        ->name('payment-reminders.store');
    Route::post('/payment-reminders/{paymentReminder}/cancel', [PaymentReminderController::class, 'cancel'])
        ->name('payment-reminders.cancel');

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
    Route::post('/promotion-batches/{promotionBatch}/discard', [PromotionBatchController::class, 'discard'])
        ->name('promotion-batches.discard');
    Route::put('/promotion-batches/{promotionBatch}/items/{promotionBatchItem}', [PromotionBatchController::class, 'updateItem'])
        ->name('promotion-batches.items.update');
});

Route::middleware(['auth', 'role:'.Role::ADMIN.','.Role::ACCOUNTANT])->group(function (): void {
    Route::get('/dues-dashboard', [DuesDashboardController::class, 'index'])->name('dues-dashboard.index');
    Route::get('/payments/collect', [PaymentCollectionController::class, 'collect'])
        ->can('create', Payment::class)
        ->name('payments.collect');
    Route::get('/families/{family}/payments/create', [PaymentCollectionController::class, 'create'])
        ->can('create', Payment::class)
        ->name('families.payments.create');
    Route::post('/families/{family}/payments', [PaymentCollectionController::class, 'store'])
        ->can('create', Payment::class)
        ->name('families.payments.store');
    Route::get('/payments', [PaymentCollectionController::class, 'index'])->name('payments.index');
    Route::get('/payments/{payment}', [PaymentCollectionController::class, 'show'])->name('payments.show');
    Route::get('/receipts', [ReceiptController::class, 'index'])->name('receipts.index');
    Route::get('/receipts/{receipt}', [ReceiptController::class, 'show'])->name('receipts.show');
    Route::get('/payment-reversals', [PaymentReversalController::class, 'index'])->name('payment-reversals.index');
    Route::get('/payment-reversals/{paymentReversal}', [PaymentReversalController::class, 'show'])
        ->name('payment-reversals.show');
    Route::get('/payment-reminders', [PaymentReminderController::class, 'index'])
        ->name('payment-reminders.index');
    Route::get('/payment-reminders/{paymentReminder}', [PaymentReminderController::class, 'show'])
        ->name('payment-reminders.show');
});

Route::middleware(['auth', 'role:'.Role::ACCOUNTANT])->group(function (): void {
    Route::get('/payments/{payment}/reversals/create', [PaymentReversalController::class, 'create'])
        ->name('payments.reversals.create');
    Route::post('/payments/{payment}/reversals', [PaymentReversalController::class, 'store'])
        ->name('payments.reversals.store');
});

Route::middleware(['auth', 'role:'.Role::ADMIN])->group(function (): void {
    Route::post('/payment-reversals/{paymentReversal}/approve', [PaymentReversalController::class, 'approve'])
        ->name('payment-reversals.approve');
});
