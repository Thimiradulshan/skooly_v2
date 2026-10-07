<?php

namespace App\Http\Controllers\Web;

use App\Actions\Promotion\ConfirmPromotionBatch;
use App\Actions\Promotion\CreatePromotionBatch;
use App\Actions\Promotion\DiscardPromotionBatch;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ConfirmPromotionBatchRequest;
use App\Http\Requests\Web\DiscardPromotionBatchRequest;
use App\Http\Requests\Web\StorePromotionBatchRequest;
use App\Models\AcademicYear;
use App\Models\PromotionBatch;
use App\Models\Section;
use InvalidArgumentException;
use RuntimeException;

class PromotionBatchController extends Controller
{
    public function index()
    {
        return view('promotion-batches.index', [
            'promotionBatches' => PromotionBatch::query()
                ->with(['sourceAcademicYear', 'targetAcademicYear'])
                ->withCount('items')
                ->orderByDesc('id')
                ->get(),
        ]);
    }

    public function create()
    {
        return view('promotion-batches.create', [
            'academicYears' => AcademicYear::query()->orderBy('id')->get(),
            'sections' => Section::query()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
        ]);
    }

    public function store(StorePromotionBatchRequest $request, CreatePromotionBatch $createPromotionBatch)
    {
        $batch = $createPromotionBatch->handle(
            AcademicYear::query()->findOrFail($request->integer('source_academic_year_id')),
            AcademicYear::query()->findOrFail($request->integer('target_academic_year_id')),
            array_map('intval', $request->input('source_section_ids')),
            $request->user(),
        );

        return redirect()
            ->route('promotion-batches.show', $batch)
            ->with('status', 'Promotion batch created as draft.');
    }

    public function show(PromotionBatch $promotionBatch)
    {
        $promotionBatch->load([
            'sourceAcademicYear',
            'targetAcademicYear',
            'sections.sourceSection.grade',
            'items.student',
            'items.sourceSection.grade',
            'items.targetGrade',
            'items.targetSection',
            'items.appliedEnrollment',
        ]);

        return view('promotion-batches.show', ['promotionBatch' => $promotionBatch]);
    }

    public function confirm(
        ConfirmPromotionBatchRequest $request,
        PromotionBatch $promotionBatch,
        ConfirmPromotionBatch $confirmPromotionBatch,
    ) {
        try {
            $confirmPromotionBatch->handle($promotionBatch, $request->user());
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['promotion_batch' => $exception->getMessage()]);
        }

        return redirect()
            ->route('promotion-batches.show', $promotionBatch)
            ->with('status', 'Promotion batch confirmed.');
    }

    public function discard(
        DiscardPromotionBatchRequest $request,
        PromotionBatch $promotionBatch,
        DiscardPromotionBatch $discardPromotionBatch,
    ) {
        try {
            $discardPromotionBatch->handle($promotionBatch);
        } catch (RuntimeException $exception) {
            return back()->withErrors(['promotion_batch' => $exception->getMessage()]);
        }

        return redirect()
            ->route('promotion-batches.show', $promotionBatch)
            ->with('status', 'Promotion batch discarded. No student enrollments were changed.');
    }
}
