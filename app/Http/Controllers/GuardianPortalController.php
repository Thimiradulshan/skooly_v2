<?php

namespace App\Http\Controllers;

use App\Actions\Privacy\AuthorizeGuardianStudentAccess;
use App\Actions\Privacy\ListGuardianVisibleDueItems;
use App\Actions\Privacy\ListGuardianVisibleStudents;
use App\Models\AcademicYear;
use App\Models\PaymentAllocation;
use App\Models\Student;
use App\Models\StudentDueItem;
use App\Models\User;
use Illuminate\Http\Request;

class GuardianPortalController extends Controller
{
    public function index(Request $request, ListGuardianVisibleStudents $students, ListGuardianVisibleDueItems $dueItems)
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->is_active && $user->guardian_id !== null, 403);

        $guardian = $user->guardian;

        return view('guardian-portal.dashboard', [
            'guardian' => $guardian,
            'students' => $students->handle($guardian),
            'dueItems' => $dueItems->handle($guardian)->where('balance_amount', '>', 0),
        ]);
    }

    public function showStudent(Request $request, Student $student, AuthorizeGuardianStudentAccess $authorize)
    {
        $user = $request->user();

        abort_unless($user instanceof User && $user->is_active && $user->guardian_id !== null, 403);
        $guardian = $user->guardian;

        abort_unless($authorize->handle($guardian, $student), 404);

        $student->load(['enrollments.academicYear', 'enrollments.grade', 'enrollments.section', 'enrollments.placements.grade', 'enrollments.placements.section']);

        return view('guardian-portal.student', ['student' => $student]);
    }

    public function dues(Request $request, ListGuardianVisibleStudents $students)
    {
        $guardian = $this->guardian($request);
        $visibleStudents = $students->handle($guardian);
        $query = StudentDueItem::query()->with(['student', 'academicYear', 'feeCategory'])->whereIn('student_id', $visibleStudents->pluck('id'));
        if ($request->integer('student_id')) {
            $query->where('student_id', $request->integer('student_id'));
        }
        if ($request->integer('academic_year_id')) {
            $query->where('academic_year_id', $request->integer('academic_year_id'));
        }
        if ($request->input('status')) {
            $query->where('status', $request->input('status'));
        }

        $academicYears = AcademicYear::query()->whereIn('id', StudentDueItem::query()->whereIn('student_id', $visibleStudents->pluck('id'))->pluck('academic_year_id')->unique())->orderByDesc('start_date')->get();

        return view('guardian-portal.dues', ['students' => $visibleStudents, 'academicYears' => $academicYears, 'dueItems' => $query->orderByDesc('id')->paginate(20)->withQueryString()]);
    }

    public function payments(Request $request, ListGuardianVisibleStudents $students)
    {
        $guardian = $this->guardian($request);
        $studentIds = $students->handle($guardian)->pluck('id');

        return view('guardian-portal.payments', [
            'allocations' => PaymentAllocation::query()->with(['payment', 'studentDueItem.student'])->whereHas('studentDueItem', fn ($query) => $query->whereIn('student_id', $studentIds))->orderByDesc('id')->paginate(20)->withQueryString(),
        ]);
    }

    private function guardian(Request $request)
    {
        $user = $request->user();
        abort_unless($user instanceof User && $user->is_active && $user->guardian_id !== null, 403);

        return $user->guardian;
    }
}
