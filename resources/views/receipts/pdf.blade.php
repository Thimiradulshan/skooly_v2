<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Receipt {{ $receipt->receipt_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111827; }
        h1 { font-size: 20px; margin: 0; }
        .header, .parties { width: 100%; }
        .header td, .parties td { vertical-align: top; }
        .receipt-no { text-align: right; font-size: 14px; font-weight: bold; }
        .label { color: #4b5563; font-size: 9px; text-transform: uppercase; }
        .parties { margin: 20px 0; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #d1d5db; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #f3f4f6; }
        .num { text-align: right; }
        .total { margin-top: 18px; font-size: 14px; font-weight: bold; text-align: right; }
        .foot { color: #4b5563; margin-top: 24px; }
    </style>
</head>
<body>
    <table class="header">
        <tr>
            <td><h1>Skooly payment receipt</h1><div>Stored payment snapshot</div></td>
            <td class="receipt-no">{{ $receipt->receipt_no }}<br><span class="label">Issued {{ $receipt->issued_at->toDateString() }}</span></td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td><span class="label">Family</span><br>{{ $receipt->family_snapshot['family_code'] ?? '—' }}</td>
            <td><span class="label">Method</span><br>{{ $receipt->payment_snapshot['method'] ?? '—' }}</td>
            <td><span class="label">Reference</span><br>{{ $receipt->payment_snapshot['payment_reference'] ?? '—' }}</td>
        </tr>
    </table>

    <table>
        <thead>
        <tr><th>Description</th><th class="num">Original</th><th class="num">Discount</th><th class="num">Allocated</th><th class="num">Balance after</th></tr>
        </thead>
        <tbody>
        @forelse ($receipt->allocation_snapshot as $allocation)
            <tr>
                <td>{{ $allocation['description'] }}</td>
                <td class="num">{{ $allocation['original_amount'] }}</td>
                <td class="num">{{ $allocation['discount_amount'] }}</td>
                <td class="num">{{ $allocation['allocation_amount'] }}</td>
                <td class="num">{{ $allocation['balance_amount'] }}</td>
            </tr>
        @empty
            <tr><td colspan="5">No stored due item snapshot.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="total">Total received: {{ $receipt->total_amount }}</div>
    <p class="foot">This document renders only the receipt snapshot captured at payment time. It is not recalculated from live due items.</p>
</body>
</html>
