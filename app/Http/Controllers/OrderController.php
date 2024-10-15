<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function showInvoice($record)
    {
        $order = Order::with('orderItems.product')->findOrFail($record);

        return view('order.invoice', compact('order'));
    }
}
