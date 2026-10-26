<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UpdateSchoolSettingRequest;
use App\Models\AcademicYear;
use App\Models\PromotionBatch;
use App\Models\SchoolSetting;
use Illuminate\Validation\ValidationException;

class SchoolSettingController extends Controller
{
    public function edit()
    {
        $schoolSetting = SchoolSetting::query()->first();

        return view('school-settings.edit', [
            'schoolSetting' => $schoolSetting,
            'academicYears' => AcademicYear::query()->active()->orderByDesc('start_date')->get(),
            'incompletePromotionBatchCount' => $schoolSetting === null
                ? 0
                : $this->incompletePromotionBatchCount($schoolSetting),
        ]);
    }

    public function update(UpdateSchoolSettingRequest $request)
    {
        $schoolSetting = SchoolSetting::query()->first();

        if (
            $schoolSetting !== null
            && $request->integer('active_academic_year_id') !== $schoolSetting->active_academic_year_id
            && $this->incompletePromotionBatchCount($schoolSetting) > 0
            && ! $request->boolean('acknowledge_incomplete_promotions')
        ) {
            throw ValidationException::withMessages([
                'acknowledge_incomplete_promotions' => 'Review or explicitly acknowledge the incomplete promotion batches before changing the active academic year.',
            ]);
        }

        SchoolSetting::query()->updateOrCreate(
            ['id' => 1],
            $request->safe()->only(['active_academic_year_id']),
        );

        return redirect()->route('school-settings.edit')->with('status', 'School settings updated.');
    }

    private function incompletePromotionBatchCount(SchoolSetting $schoolSetting): int
    {
        return PromotionBatch::query()
            ->where('status', PromotionBatch::STATUS_DRAFT)
            ->where(function ($query) use ($schoolSetting): void {
                $query->where('source_academic_year_id', $schoolSetting->active_academic_year_id)
                    ->orWhere('target_academic_year_id', $schoolSetting->active_academic_year_id);
            })
            ->count();
    }
}
