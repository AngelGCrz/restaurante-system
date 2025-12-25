<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;

class KitchenController extends Controller
{
    public function index()
    {
        // En un escenario real, esto se actualizaría vía WebSockets (Reverb) o Polling
        $orders = Order::where('status', 'pendiente')
            ->with('items.product')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('kitchen.index', compact('orders'));
    }
}
