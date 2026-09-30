<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Receipt;

class ReceiptController extends Controller
{
    public function show(Receipt $receipt)
    {
        return view('receipts.show', ['receipt' => $receipt]);
    }
}
