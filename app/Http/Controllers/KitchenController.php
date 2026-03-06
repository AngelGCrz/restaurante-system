<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Services\PrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class KitchenController extends Controller
{
    public function __construct(protected PrinterService $printer) {}

    public function index()
    {
        $orders = Order::where('status', 'pendiente')
            ->whereNull('kitchen_printed_at')
            ->with('items.product')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('kitchen.index', compact('orders'));
    }

    public function poll()
    {
        $orders = Order::where('status', 'pendiente')
            ->whereNull('kitchen_printed_at')
            ->with('items.product')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(fn ($order) => [
                'id'              => $order->id,
                'created_at'      => $order->created_at->format('H:i'),
                'customer_name'   => $order->customer_name ?? 'N/A',
                'table_label'     => $order->table_label,
                'comment'         => $order->comment,
                'origin_order_id' => $order->origin_order_id,
                'items'           => $order->items->map(fn ($item) => [
                    'quantity'     => $item->quantity,
                    'product_name' => $item->product->name ?? '?',
                    'comment'      => $item->comment,
                ]),
            ]);

        return response()->json([
            'orders' => $orders,
            'hash'   => md5($orders->pluck('id')->sort()->values()->join(',')),
        ]);
    }

    public function printOrder(Request $request, Order $order)
    {
        $order->load('items.product', 'user');

        return response()->json([
            'success' => true,
            'order'   => $order,
        ]);
    }

    /**
     * Marca la orden como impresa en cocina.
     * Se llama desde el dashboard de caja tras confirmar que el WS envió el ticket.
     */
    public function markPrinted(Request $request, Order $order)
    {
        try {
            $order->kitchen_printed_at = now();
            $order->save();

            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            Log::error('Error marcando orden #' . $order->id . ' como impresa: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error interno.'], 500);
        }
    }

    public function prepare(Request $request, Order $order)
    {
        $order->update(['status' => 'preparando']);
        return response()->json(['success' => true]);
    }

    public function ready(Request $request, Order $order)
    {
        $order->update(['status' => 'listo']);
        return response()->json(['success' => true]);
    }

    public function show(Order $order)
    {
        $order->load('items.product');
        return view('kitchen.show', compact('order'));
    }
}
