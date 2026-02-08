<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;
use App\Services\PrinterService;

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

    public function printOrder(Request $request, Order $order)
    {
        try {
            app(PrinterService::class)->printKitchenOrder($order);
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            \Log::error('Fallo impresion de cocina ' . $order->id . ': ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error al imprimir en cocina'], 500);
        }
    }
}
