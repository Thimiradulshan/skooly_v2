<?php

use App\Models\AcademicYear;
use App\Models\AuditLog;
use App\Models\Discount;
use App\Models\Event;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\PaymentReminder;
use App\Models\PromotionBatch;
use App\Models\Role;
use App\Models\Section;
use App\Models\SectionYearAssignment;
use App\Models\Student;
use App\Models\StudentFeeSubscription;
use App\Models\Subject;
use App\Models\TeacherAssignment;
use App\Models\Term;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

it('filters each supported admin list by its relevant text', function () {
    $admin = adminUser();
    $academicYear = AcademicYear::factory()->create(['name' => 'Search Academic Year']);
    $targetAcademicYear = AcademicYear::factory()->create(['name' => 'Search Target Year']);
    $grade = Grade::factory()->create(['name' => 'Search Grade']);
    $section = Section::factory()->for($grade)->create(['name' => 'Search Section']);
    $feeCategory = FeeCategory::factory()->create(['name' => 'Search Fee Category']);
    $family = Family::factory()->create(['family_code' => 'SEARCH-FAMILY']);
    $subject = Subject::factory()->create(['code' => 'SRCH', 'name' => 'Search Subject']);
    $teacher = userWithRole(Role::TEACHER);
    $teacher->update(['name' => 'Search Teacher']);
    $teacher->qualifiedSubjects()->attach($subject);

    FeeStructure::factory()->create([
        'fee_category_id' => $feeCategory->id,
        'grade_id' => $grade->id,
        'academic_year_id' => $academicYear->id,
    ]);
    Term::factory()->for($academicYear)->create(['name' => 'Search Term']);
    Event::factory()->create([
        'academic_year_id' => $academicYear->id,
        'fee_category_id' => $feeCategory->id,
        'name' => 'Search Event',
    ]);
    TeacherAssignment::factory()->create([
        'academic_year_id' => $academicYear->id,
        'section_id' => $section->id,
        'teacher_id' => $teacher->id,
        'subject_id' => $subject->id,
    ]);
    SectionYearAssignment::factory()->create([
        'academic_year_id' => $academicYear->id,
        'section_id' => $section->id,
        'class_in_charge_id' => $teacher->id,
    ]);
    PromotionBatch::factory()->create([
        'source_academic_year_id' => $academicYear->id,
        'target_academic_year_id' => $targetAcademicYear->id,
    ]);
    $searches = [
        ['academic-years.index', 'Search Academic Year'],
        ['fee-categories.index', 'Search Fee Category'],
        ['fee-structures.index', 'Search Fee Category'],
        ['families.index', 'SEARCH-FAMILY'],
        ['events.index', 'Search Event'],
        ['sections.index', 'Search Section'],
        ['grades.index', 'Search Grade'],
        ['terms.index', 'Search Term'],
        ['subjects.index', 'SRCH'],
        ['teacher-qualifications.index', 'Search Teacher'],
        ['teacher-assignments.index', 'Search Teacher'],
        ['section-year-assignments.index', 'Search Teacher'],
        ['promotion-batches.index', 'Search Target Year'],
    ];

    foreach ($searches as [$route, $search]) {
        $this->actingAs($admin)
            ->get(route($route, ['search' => $search]))
            ->assertOk()
            ->assertSee($search);
    }

    $this->actingAs($admin)
        ->get(route('users.index', ['search' => 'Search Teacher']))
        ->assertOk()
        ->assertSee('Search Teacher');
});

it('paginates search results and keeps the search on the next page', function () {
    foreach (range(1, 21) as $number) {
        Family::factory()->create(['family_code' => 'PAGED-FAMILY-'.$number]);
    }

    $response = $this->actingAs(adminUser())
        ->get(route('families.index', [
            'search' => 'PAGED-FAMILY',
            'sort' => 'family_code',
            'direction' => 'desc',
        ]));

    $response->assertOk()
        ->assertViewHas('families', fn ($families) => $families->perPage() === 20 && $families->total() === 21)
        ->assertSee('search=PAGED-FAMILY&amp;sort=family_code&amp;direction=desc&amp;page=2', false);

    $this->actingAs(adminUser())
        ->get(route('families.index', [
            'search' => 'PAGED-FAMILY',
            'sort' => 'family_code',
            'direction' => 'desc',
            'page' => 2,
        ]))
        ->assertOk()
        ->assertViewHas('families', fn ($families) => $families->currentPage() === 2 && $families->count() === 1);
});

it('sorts an admin list by an allowed option', function () {
    Family::factory()->create(['family_code' => 'SORTED-FAMILY-A']);
    Family::factory()->create(['family_code' => 'SORTED-FAMILY-Z']);

    $response = $this->actingAs(adminUser())
        ->get(route('families.index', [
            'search' => 'SORTED-FAMILY',
            'sort' => 'family_code',
            'direction' => 'desc',
        ]));

    $response->assertOk()
        ->assertSeeInOrder(['SORTED-FAMILY-Z', 'SORTED-FAMILY-A']);
});

it('paginates reminder filters and audit logs without adding audit filtering', function () {
    $family = Family::factory()->create(['family_code' => 'REMINDER-PAGED']);

    PaymentReminder::factory()->count(21)->create(['family_id' => $family->id]);
    AuditLog::factory()->count(21)->create();

    $this->actingAs(adminUser())
        ->get(route('payment-reminders.index', ['family_id' => $family->id]))
        ->assertOk()
        ->assertViewHas('reminders', fn ($reminders) => $reminders->perPage() === 20 && $reminders->total() === 21)
        ->assertSee('family_id='.$family->id.'&amp;page=2', false);

    $this->actingAs(adminUser())
        ->get(route('audit-logs.index'))
        ->assertOk()
        ->assertViewHas('auditLogs', fn ($auditLogs) => $auditLogs->perPage() === 20 && $auditLogs->total() === 21)
        ->assertDontSee('name="search"', false);
});

it('paginates discounts and subscriptions within one student only', function () {
    $student = Student::factory()->for(Family::factory())->create();

    Discount::factory()->count(21)->create(['student_id' => $student->id]);
    StudentFeeSubscription::factory()->count(21)->create(['student_id' => $student->id]);

    $this->actingAs(adminUser())
        ->get(route('students.discounts.index', $student))
        ->assertOk()
        ->assertViewHas('discounts', fn ($discounts) => $discounts->perPage() === 20 && $discounts->total() === 21)
        ->assertDontSee('name="search"', false);

    $this->actingAs(adminUser())
        ->get(route('students.fee-subscriptions.index', $student))
        ->assertOk()
        ->assertViewHas('subscriptions', fn ($subscriptions) => $subscriptions->perPage() === 20 && $subscriptions->total() === 21)
        ->assertDontSee('name="search"', false);
});

it('rejects invalid search input on admin list pages', function () {
    $this->actingAs(adminUser())
        ->get(route('families.index', ['search' => str_repeat('x', 101)]))
        ->assertSessionHasErrors('search');
});

it('rejects sort injection on admin list pages', function () {
    $this->actingAs(adminUser())
        ->get(route('families.index', ['sort' => 'family_code; drop table families']))
        ->assertSessionHasErrors('sort');
});
