<?php

namespace App\Http\Controllers\Web;

use App\Actions\Reports\BuildDuesDashboardReport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\DuesDashboardFilterRequest;
use App\Models\AcademicYear;
use App\Models\Family;
use App\Models\FeeCategory;
use App\Models\Grade;
use App\Models\Section;

class DuesDashboardController extends Controller
{
    public function index(DuesDashboardFilterRequest $request, BuildDuesDashboardReport $buildReport)
    {
        $report = $buildReport->handle(array_filter($request->validated()));

        return view('dues-dashboard.index', [
            'report' => $report,
            'academicYears' => AcademicYear::query()->orderBy('id')->get(),
            'grades' => Grade::query()->orderBy('sequence_order')->get(),
            'sections' => Section::query()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
            'feeCategories' => FeeCategory::query()->orderBy('name')->get(),
            'families' => Family::query()->orderBy('family_code')->get(),
        ]);
    }
}
