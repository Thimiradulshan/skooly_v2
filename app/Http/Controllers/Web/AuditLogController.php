<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\FilterAuditLogsRequest;
use App\Models\AuditLog;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    public function index(FilterAuditLogsRequest $request)
    {
        $filters = $request->validated();

        return view('audit-logs.index', [
            'auditLogs' => $this->filteredAuditLogs($filters)
                ->with('actor')
                ->paginate(20)
                ->withQueryString(),
            'actors' => User::query()
                ->whereIn('id', AuditLog::query()->whereNotNull('actor_user_id')->select('actor_user_id'))
                ->orderBy('name')
                ->get(),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
            'auditableTypes' => AuditLog::query()->whereNotNull('auditable_type')->distinct()->orderBy('auditable_type')->pluck('auditable_type'),
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('actor');

        return view('audit-logs.show', ['auditLog' => $auditLog]);
    }

    public function exportCsv(FilterAuditLogsRequest $request): StreamedResponse
    {
        $filters = $request->validated();

        return response()->streamDownload(function () use ($filters): void {
            $stream = fopen('php://output', 'w');

            fputcsv($stream, ['Filters']);
            foreach ($this->filterSummary($filters) as $label => $value) {
                fputcsv($stream, [$label, $this->csvValue($value)]);
            }
            fputcsv($stream, []);
            fputcsv($stream, ['ID', 'Occurred at', 'Action', 'Actor', 'Record type', 'Record ID', 'Metadata']);

            $this->filteredAuditLogs($filters)->with('actor')->lazy(200)->each(function (AuditLog $auditLog) use ($stream): void {
                fputcsv($stream, [
                    $auditLog->id,
                    $auditLog->occurred_at->toDateTimeString(),
                    $this->csvValue($auditLog->action),
                    $this->csvValue($this->actorName($auditLog)),
                    $this->csvValue($auditLog->auditable_type ? class_basename($auditLog->auditable_type) : ''),
                    $auditLog->auditable_id,
                    $this->csvValue(json_encode($auditLog->metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
                ]);
            });

            fclose($stream);
        }, 'audit-logs.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(FilterAuditLogsRequest $request)
    {
        $filters = $request->validated();
        $dompdf = new Dompdf;
        $dompdf->loadHtml(view('audit-logs.exports.pdf', [
            'auditLogs' => $this->filteredAuditLogs($filters)->with('actor')->get(),
            'filters' => $this->filterSummary($filters),
        ])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="audit-logs.pdf"',
        ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Builder<AuditLog>
     */
    private function filteredAuditLogs(array $filters): Builder
    {
        return AuditLog::query()
            ->when($filters['action'] ?? null, fn (Builder $query, string $action): Builder => $query->where('action', $action))
            ->when($filters['actor_user_id'] ?? null, fn (Builder $query, int $actorUserId): Builder => $query->where('actor_user_id', $actorUserId))
            ->when($filters['auditable_type'] ?? null, fn (Builder $query, string $auditableType): Builder => $query->where('auditable_type', $auditableType))
            ->when($filters['occurred_at_from'] ?? null, fn (Builder $query, string $date): Builder => $query->where('occurred_at', '>=', Carbon::parse($date)->startOfDay()))
            ->when($filters['occurred_at_to'] ?? null, fn (Builder $query, string $date): Builder => $query->where('occurred_at', '<=', Carbon::parse($date)->endOfDay()))
            ->latest('occurred_at')
            ->latest('id');
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, string>
     */
    private function filterSummary(array $filters): array
    {
        return [
            'Action' => $filters['action'] ?? 'All actions',
            'Actor' => isset($filters['actor_user_id']) ? "User #{$filters['actor_user_id']}" : 'All actors',
            'Record type' => isset($filters['auditable_type']) ? class_basename($filters['auditable_type']) : 'All record types',
            'Occurred from' => $filters['occurred_at_from'] ?? 'Any date',
            'Occurred to' => $filters['occurred_at_to'] ?? 'Any date',
        ];
    }

    private function csvValue(string $value): string
    {
        return preg_match('/^[=+\-@]/', $value) === 1 ? "'{$value}" : $value;
    }

    private function actorName(AuditLog $auditLog): string
    {
        $actor = $auditLog->getRelation('actor');

        return $actor instanceof User ? $actor->name : 'System';
    }
}
