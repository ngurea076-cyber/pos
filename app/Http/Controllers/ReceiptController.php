<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;

class ReceiptController extends Controller
{
    public function download(Order $order): Response
    {
        $order->loadMissing(['items', 'attendant']);

        return Pdf::loadView('receipts.order', compact('order'))
            ->setPaper('a4')
            ->download('receipt-'.$order->order_number.'.pdf');
    }
}
