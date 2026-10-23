<?php

namespace App\Http\Controllers\Web;

use App\Academic\ActiveAcademicYear;
use App\Actions\Promotion\ConfirmPromotionBatch;
use App\Actions\Promotion\CreatePromotionBatch;
use App\Actions\Promotion\DiscardPromotionBatch;
use App\Actions\Promotion\UpdatePromotionBatchItem;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ConfirmPromotionBatchRequest;
use App\Http\Requests\Web\DiscardPromotionBatchRequest;
use App\Http\Requests\Web\ListSearchRequest;
use App\Http\Requests\Web\StorePromotionBatchRequest;
use App\Http\Requests\Web\UpdatePromotionBatchItemRequest;
use App\Models\AcademicYear;
use App\Models\Grade;
use App\Models\PromotionBatch;
use App\Models\PromotionBatchItem;
use App\Models\Section;
use Dompdf\Dompdf;
use Illuminate\Http\Response;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PromotionBatchController extends Controller
{
    public function index(ListSearchRequest $request)
    {
        $search = $request->string('search')->trim()->toString();
        $sortOptions = ['status' => 'status', 'confirmed_at' => 'confirmed_at'];
        $sort = $request->sort($sortOptions);
        $direction = $request->direction('desc');

        $promotionBatches = PromotionBatch::query()
            ->with(['sourceAcademicYear', 'targetAcademicYear'])
            ->withCount('items')
            ->when($search !== '', function ($query) use ($search): void {
                $query->whereHas('sourceAcademicYear', fn ($academicYear) => $academicYear->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('targetAcademicYear', fn ($academicYear) => $academicYear->where('name', 'like', "%{$search}%"));
            });

        if ($sort === null) {
            $promotionBatches->orderByDesc('id');
        } else {
            $promotionBatches->orderBy($sortOptions[$sort], $direction)->orderBy('id');
        }

        return view('promotion-batches.index', [
            'promotionBatches' => $promotionBatches
                ->paginate(20)
                ->withQueryString(),
            'search' => $search,
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(ActiveAcademicYear $activeAcademicYear)
    {
        return view('promotion-batches.create', [
            'sourceAcademicYears' => AcademicYear::query()->orderBy('id')->get(),
            'targetAcademicYear' => $activeAcademicYear->current(),
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

        return view('promotion-batches.show', [
            'promotionBatch' => $promotionBatch,
            'grades' => Grade::query()->active()->orderBy('sequence_order')->get(),
            'sections' => Section::query()->active()->with('grade')->orderBy('grade_id')->orderBy('name')->get(),
        ]);
    }

    public function exportCsv(PromotionBatch $promotionBatch): StreamedResponse
    {
        abort_unless($promotionBatch->status === PromotionBatch::STATUS_CONFIRMED, 404);
        $promotionBatch->load(['sourceAcademicYear', 'targetAcademicYear']);
        $items = PromotionBatchItem::query()->where('promotion_batch_id', $promotionBatch->id)->with(['student', 'sourceSection.grade', 'targetGrade', 'targetSection'])->get();

        return response()->streamDownload(function () use ($promotionBatch, $items): void {
            $stream = fopen('php://output', 'w');
            fputcsv($stream, ['Student', 'Admission number', 'Source year', 'Source section', 'Target year', 'Target grade', 'Target section', 'Outcome']);
            foreach ($items as $item) {
                fputcsv($stream, array_map(fn (?string $value): string => $this->csvValue($value), [
                    $item->student->getAttribute('name'), $item->student->getAttribute('admission_no'), $promotionBatch->sourceAcademicYear->getAttribute('name'),
                    $item->sourceSection->grade->getAttribute('name').' '.$item->sourceSection->getAttribute('name'), $promotionBatch->targetAcademicYear->getAttribute('name'),
                    $item->targetGrade?->getAttribute('name'), $item->targetSection?->getAttribute('name'), $item->action,
                ]));
            }
            fclose($stream);
        }, 'promotion-batch-'.$promotionBatch->id.'.csv', ['Content-Type' => 'text/csv']);
    }

    public function exportPdf(PromotionBatch $promotionBatch): Response
    {
        abort_unless($promotionBatch->status === PromotionBatch::STATUS_CONFIRMED, 404);
        $promotionBatch->load(['sourceAcademicYear', 'targetAcademicYear', 'items.student', 'items.sourceSection.grade', 'items.targetGrade', 'items.targetSection']);
        $dompdf = new Dompdf;
        $dompdf->loadHtml(view('promotion-batches.pdf', compact('promotionBatch'))->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, ['Content-Type' => 'application/pdf', 'Content-Disposition' => 'attachment; filename="promotion-batch-'.$promotionBatch->id.'.pdf"']);
    }

    private function csvValue(?string $value): string
    {
        $value ??= '';

        return in_array($value[0] ?? '', ['=', '+', '-', '@'], true) ? "'".$value : $value;
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

    public function updateItem(
        UpdatePromotionBatchItemRequest $request,
        PromotionBatch $promotionBatch,
        PromotionBatchItem $promotionBatchItem,
        UpdatePromotionBatchItem $updatePromotionBatchItem,
    ) {
        try {
            $updatePromotionBatchItem->handle(
                $promotionBatch,
                $promotionBatchItem,
                $request->string('action')->toString(),
                $request->integer('target_grade_id') ?: null,
                $request->integer('target_section_id') ?: null,
                $request->user(),
            );
        } catch (InvalidArgumentException|RuntimeException $exception) {
            return back()->withErrors(['promotion_batch_item' => $exception->getMessage()]);
        }

        return redirect()
            ->route('promotion-batches.show', $promotionBatch)
            ->with('status', 'Promotion item updated. No enrollment has been changed.');
    }
}
