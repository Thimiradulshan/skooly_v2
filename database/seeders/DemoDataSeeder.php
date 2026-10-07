<?php

namespace Database\Seeders;

use App\Actions\Events\GenerateEventDueItems;
use App\Actions\Fees\GenerateRecurringDueItems;
use App\Actions\Notifications\GeneratePaymentReminders;
use App\Actions\Promotion\CreatePromotionBatch;
use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Event;
use App\Models\EventCharge;
use App\Models\EventParticipation;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\FeeStructure;
use App\Models\Grade;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PromotionBatch;
use App\Models\Receipt;
use App\Models\Role;
use App\Models\SchoolSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\StudentFeeSubscription;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Local and testing demo data.
 *
 * Never runs in production. Safe to run more than once because every record is
 * created with a deterministic key and the generation steps reuse the existing
 * actions, which are themselves idempotent.
 */
class DemoDataSeeder extends Seeder
{
    private const ADMIN_EMAIL = 'admin@skooly.test';

    private const RECEIPT_NO = 'DEMO-REC-001';

    public function run(): void
    {
        if (app()->environment('production')) {
            $this->command?->warn('Demo data is skipped in the production environment.');

            return;
        }

        $roles = $this->roles();
        $admin = $this->users($roles);

        $currentYear = $this->currentAcademicYear();
        $nextYear = $this->nextAcademicYear();
        $grades = $this->grades();
        $sections = $this->sections($grades);

        $categories = $this->feeCategories();
        $this->feeStructures($categories, $grades, $currentYear);

        $students = $this->students($grades, $sections, $currentYear);
        $chloe = $students['chloe'];

        $this->discount($chloe, $categories);
        $this->feeSubscription($chloe, $categories, $currentYear);

        $this->generateRecurringDues($currentYear);
        $this->recordPayment($chloe);
        $this->event($categories, $grades, $chloe, $currentYear);
        $this->promotionBatch($currentYear, $nextYear, $sections);
        $this->reminders($currentYear);

        $this->command?->info('Demo data ready. Sign in with '.self::ADMIN_EMAIL.' / password');
    }

    /**
     * @return array<string, Role>
     */
    private function roles(): array
    {
        $roles = [];

        foreach ([Role::SUPERADMIN, Role::ADMIN, Role::ACCOUNTANT, Role::TEACHER] as $name) {
            $roles[$name] = Role::query()->firstOrCreate(['name' => $name]);
        }

        return $roles;
    }

    /**
     * @param  array<string, Role>  $roles
     */
    private function users(array $roles): User
    {
        $admin = User::query()->firstOrCreate(
            ['email' => self::ADMIN_EMAIL],
            ['name' => 'Demo Admin', 'password' => 'password'],
        );

        $admin->roles()->syncWithoutDetaching([$roles[Role::ADMIN]->id]);

        User::query()->firstOrCreate(
            ['email' => 'accountant@skooly.test'],
            ['name' => 'Demo Accountant', 'password' => 'password'],
        )->roles()->syncWithoutDetaching([$roles[Role::ACCOUNTANT]->id]);

        User::query()->firstOrCreate(
            ['email' => 'teacher@skooly.test'],
            ['name' => 'Demo Teacher', 'password' => 'password'],
        )->roles()->syncWithoutDetaching([$roles[Role::TEACHER]->id]);

        return $admin;
    }

    private function currentAcademicYear(): AcademicYear
    {
        $year = AcademicYear::query()->firstOrCreate(['name' => '2026/2027'], [
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
        ]);

        SchoolSetting::query()->firstOrCreate(
            ['id' => 1],
            ['active_academic_year_id' => $year->id],
        );

        return $year;
    }

    private function nextAcademicYear(): AcademicYear
    {
        return AcademicYear::query()->firstOrCreate(['name' => '2027/2028'], [
            'start_date' => '2027-01-01',
            'end_date' => '2027-12-31',
        ]);
    }

    /**
     * @return array<string, Grade>
     */
    private function grades(): array
    {
        return [
            'grade_1' => Grade::query()->firstOrCreate(['name' => 'Grade 1'], ['sequence_order' => 1]),
            'grade_2' => Grade::query()->firstOrCreate(['name' => 'Grade 2'], ['sequence_order' => 2]),
        ];
    }

    /**
     * @param  array<string, Grade>  $grades
     * @return array<string, Section>
     */
    private function sections(array $grades): array
    {
        return [
            'grade_1_a' => Section::query()->firstOrCreate(
                ['grade_id' => $grades['grade_1']->id, 'name' => 'A'],
                ['capacity' => 30],
            ),
            'grade_2_a' => Section::query()->firstOrCreate(
                ['grade_id' => $grades['grade_2']->id, 'name' => 'A'],
                ['capacity' => 30],
            ),
        ];
    }

    /**
     * @return array<string, FeeCategory>
     */
    private function feeCategories(): array
    {
        return [
            'tuition' => FeeCategory::query()->firstOrCreate(
                ['name' => 'Tuition'],
                ['is_recurring' => true, 'is_opt_in' => false],
            ),
            'transport' => FeeCategory::query()->firstOrCreate(
                ['name' => 'Transport'],
                ['is_recurring' => true, 'is_opt_in' => true],
            ),
            'event' => FeeCategory::query()->firstOrCreate(
                ['name' => 'Event Fees'],
                ['is_recurring' => false, 'is_opt_in' => false],
            ),
        ];
    }

    /**
     * @param  array<string, FeeCategory>  $categories
     * @param  array<string, Grade>  $grades
     */
    private function feeStructures(array $categories, array $grades, AcademicYear $academicYear): void
    {
        $structures = [
            [$categories['tuition'], $grades['grade_1'], '100.00'],
            [$categories['tuition'], $grades['grade_2'], '120.00'],
            [$categories['transport'], $grades['grade_1'], '40.00'],
        ];

        foreach ($structures as [$category, $grade, $amount]) {
            FeeStructure::query()->firstOrCreate([
                'fee_category_id' => $category->id,
                'grade_id' => $grade->id,
                'academic_year_id' => $academicYear->id,
                'frequency' => 'monthly',
            ], ['amount' => $amount]);
        }
    }

    /**
     * @param  array<string, Grade>  $grades
     * @param  array<string, Section>  $sections
     * @return array<string, Student>
     */
    private function students(array $grades, array $sections, AcademicYear $academicYear): array
    {
        $firstFamily = Family::query()->firstOrCreate(['family_code' => 'FAM-DEMO-01'], [
            'address' => '1 Demo Road',
            'home_contact_no' => '0770000001',
            'combined_billing_enabled' => true,
        ]);

        $secondFamily = Family::query()->firstOrCreate(['family_code' => 'FAM-DEMO-02'], [
            'address' => '2 Demo Road',
            'home_contact_no' => '0770000002',
            'combined_billing_enabled' => false,
        ]);

        $alice = Guardian::query()->firstOrCreate(
            ['family_id' => $firstFamily->id, 'name' => 'Alice Demo'],
            ['relationship' => 'mother', 'email' => 'alice@skooly.test', 'contact_no' => '0771110001'],
        );

        $bob = Guardian::query()->firstOrCreate(
            ['family_id' => $firstFamily->id, 'name' => 'Bob Demo'],
            ['relationship' => 'father', 'email' => 'bob@skooly.test', 'contact_no' => '0771110002'],
        );

        $carol = Guardian::query()->firstOrCreate(
            ['family_id' => $secondFamily->id, 'name' => 'Carol Demo'],
            ['relationship' => 'mother', 'email' => 'carol@skooly.test', 'contact_no' => '0771110003'],
        );

        $chloe = Student::query()->firstOrCreate(['admission_no' => 'ADM-DEMO-001'], [
            'family_id' => $firstFamily->id,
            'name' => 'Chloe Demo',
            'dob' => '2015-04-10',
            'gender' => 'female',
            'status' => Student::STATUS_ACTIVE,
        ]);

        $liam = Student::query()->firstOrCreate(['admission_no' => 'ADM-DEMO-002'], [
            'family_id' => $firstFamily->id,
            'name' => 'Liam Demo',
            'dob' => '2015-08-22',
            'gender' => 'male',
            'status' => Student::STATUS_ACTIVE,
        ]);

        $mia = Student::query()->firstOrCreate(['admission_no' => 'ADM-DEMO-003'], [
            'family_id' => $secondFamily->id,
            'name' => 'Mia Demo',
            'dob' => '2014-01-15',
            'gender' => 'female',
            'status' => Student::STATUS_PENDING_REGISTRATION,
        ]);

        Enrollment::query()->firstOrCreate([
            'student_id' => $chloe->id,
            'academic_year_id' => $academicYear->id,
        ], ['grade_id' => $grades['grade_1']->id, 'section_id' => $sections['grade_1_a']->id]);

        Enrollment::query()->firstOrCreate([
            'student_id' => $liam->id,
            'academic_year_id' => $academicYear->id,
        ], ['grade_id' => $grades['grade_1']->id, 'section_id' => $sections['grade_1_a']->id]);

        Enrollment::query()->firstOrCreate([
            'student_id' => $mia->id,
            'academic_year_id' => $academicYear->id,
        ], ['grade_id' => $grades['grade_2']->id, 'section_id' => $sections['grade_2_a']->id]);

        // Alice is linked to both children, Bob only to Liam, so guardian privacy is demonstrable.
        $alice->students()->syncWithoutDetaching([$chloe->id, $liam->id]);
        $bob->students()->syncWithoutDetaching([$liam->id]);
        $carol->students()->syncWithoutDetaching([$mia->id]);

        return ['chloe' => $chloe, 'liam' => $liam, 'mia' => $mia];
    }

    /**
     * @param  array<string, FeeCategory>  $categories
     */
    private function discount(Student $student, array $categories): Discount
    {
        return Discount::query()->firstOrCreate([
            'student_id' => $student->id,
            'applies_to_fee_category_id' => $categories['tuition']->id,
            'type' => 'demo_scholarship',
        ], ['value' => '10.00', 'value_type' => 'amount', 'is_active' => true]);
    }

    /**
     * @param  array<string, FeeCategory>  $categories
     */
    private function feeSubscription(Student $student, array $categories, AcademicYear $academicYear): StudentFeeSubscription
    {
        return StudentFeeSubscription::query()->firstOrCreate([
            'student_id' => $student->id,
            'fee_category_id' => $categories['transport']->id,
            'academic_year_id' => $academicYear->id,
        ], ['is_active' => true]);
    }

    private function generateRecurringDues(AcademicYear $academicYear): void
    {
        app(GenerateRecurringDueItems::class)->handle($academicYear, '2026-10-10', '2026-10');
    }

    private function recordPayment(Student $student): void
    {
        if (Receipt::query()->where('receipt_no', self::RECEIPT_NO)->exists()) {
            return;
        }

        $dueItem = StudentDueItem::query()
            ->where('student_id', $student->id)
            ->where('balance_amount', '>', 0)
            ->orderBy('id')
            ->first();

        if ($dueItem === null) {
            return;
        }

        $amount = min((float) $dueItem->balance_amount, 50.0);

        Payment::recordManual(
            $student->family,
            self::RECEIPT_NO,
            'cash',
            number_format($amount, 2, '.', ''),
            [['student_due_item_id' => $dueItem->id, 'amount' => number_format($amount, 2, '.', '')]],
        );
    }

    /**
     * @param  array<string, FeeCategory>  $categories
     * @param  array<string, Grade>  $grades
     */
    private function event(array $categories, array $grades, Student $student, AcademicYear $academicYear): void
    {
        $event = Event::query()->firstOrCreate([
            'academic_year_id' => $academicYear->id,
            'name' => 'Demo Sports Day',
        ], [
            'fee_category_id' => $categories['event']->id,
            'event_date' => '2026-11-05',
            'description' => 'Demo annual sports day for Grade 1.',
            'is_mandatory' => true,
        ]);

        EventCharge::query()->firstOrCreate([
            'event_id' => $event->id,
            'grade_id' => $grades['grade_1']->id,
        ], ['amount' => '25.00']);

        EventParticipation::query()->firstOrCreate([
            'event_id' => $event->id,
            'student_id' => $student->id,
        ], ['status' => EventParticipation::STATUS_OPTED_IN]);

        app(GenerateEventDueItems::class)->handle($event);
    }

    /**
     * @param  array<string, Section>  $sections
     */
    private function promotionBatch(
        AcademicYear $currentYear,
        AcademicYear $nextYear,
        array $sections,
    ): void {
        $exists = PromotionBatch::query()
            ->where('source_academic_year_id', $currentYear->id)
            ->where('target_academic_year_id', $nextYear->id)
            ->where('status', PromotionBatch::STATUS_DRAFT)
            ->exists();

        if ($exists) {
            return;
        }

        app(CreatePromotionBatch::class)->handle(
            $currentYear,
            $nextYear,
            [$sections['grade_1_a']->id],
        );
    }

    private function reminders(AcademicYear $academicYear): void
    {
        app(GeneratePaymentReminders::class)->handle('2026-11-01', 7, $academicYear->id);
    }
}
