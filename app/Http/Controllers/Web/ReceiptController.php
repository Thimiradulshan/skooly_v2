<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\ListReceiptsRequest;
use App\Models\Receipt;
use Dompdf\Dompdf;

class ReceiptController extends Controller
{
    public function index(ListReceiptsRequest $request)
    {
        $sort = $request->input('sort', 'issued_at');
        $direction = $request->input('direction', 'desc');
        $search = $request->string('search')->trim()->toString();

        $receipts = Receipt::query()
            ->with('payment.family')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where('receipt_no', 'like', "%{$search}%")
                    ->orWhereHas('payment', function ($payment) use ($search): void {
                        $payment->where('payment_reference', 'like', "%{$search}%")
                            ->orWhereHas('family', fn ($family) => $family->where('family_code', 'like', "%{$search}%"));
                    });
            })
            ->orderBy($sort, $direction)
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('receipts.index', compact('receipts', 'search', 'sort', 'direction'));
    }

    public function show(Receipt $receipt)
    {
        return view('receipts.show', ['receipt' => $receipt]);
    }

    public function downloadPdf(Receipt $receipt)
    {
        $dompdf = new Dompdf;
        $dompdf->loadHtml(view('receipts.pdf', ['receipt' => $receipt])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$receipt->receipt_no.'.pdf"',
        ]);
    }
}
