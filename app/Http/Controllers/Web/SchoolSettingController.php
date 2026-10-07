<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\UpdateSchoolSettingRequest;
use App\Models\AcademicYear;
use App\Models\SchoolSetting;

class SchoolSettingController extends Controller
{
    public function edit()
    {
        return view('school-settings.edit', [
            'schoolSetting' => SchoolSetting::query()->firstOrFail(),
            'academicYears' => AcademicYear::query()->active()->orderByDesc('start_date')->get(),
        ]);
    }

    public function update(UpdateSchoolSettingRequest $request)
    {
        $schoolSetting = SchoolSetting::query()->firstOrFail();
        $schoolSetting->update($request->validated());

        return redirect()->route('school-settings.edit')->with('status', 'School settings updated.');
    }
}
