<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Order;
use App\Services\PrinterService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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

    /**
     * Endpoint JSON para polling de cocina.
     * Devuelve los pedidos pendientes con sus datos completos.
     */
    public function poll()
    {
        $orders = Order::where('status', 'pendiente')
            ->with('items.product')
            ->orderBy('created_at', 'asc')
            ->get()
            ->map(function ($order) {
                return [
                    'id'              => $order->id,
                    'created_at'      => $order->created_at->format('H:i'),
                    'customer_name'   => $order->customer_name ?? 'N/A',
                    'table_label'     => $order->table_label,
                    'comment'         => $order->comment,
                    'origin_order_id' => $order->origin_order_id,
                    'items'           => $order->items->map(fn($item) => [
                        'quantity'     => $item->quantity,
                        'product_name' => $item->product->name ?? '?',
                        'comment'      => $item->comment,
                    ]),
                ];
            });

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
    
//     try {
//         $agentUrl   = env('PRINT_AGENT_URL');   // http://192.168.18.53:3000
//         $agentToken = env('PRINT_AGENT_TOKEN');

//         $order->load('items.product', 'user');

//         $raw    = app(PrinterService::class)->getRawKitchenESCPOS($order);
//         $base64 = base64_encode($raw);

//         // ===============================
//         // TRY PRINT AGENT (WEB)
//         // ===============================
//         if (!empty($agentUrl) && !empty($agentToken)) {
//             try {
//                 $resp = \Illuminate\Support\Facades\Http::withToken($agentToken)
//                     ->timeout(5)
//                     ->post(rtrim($agentUrl, '/') . '/print', [
//                         'printer' => env('PRINT_AGENT_PRINTER'),
//                         'data'    => $base64,
//                         'source'  => 'WEB', // 👈 AQUÍ ESTÁ LA CLAVE
//                     ]);

//                 if ($resp->ok() && $resp->json('ok') === true) {
//                     return response()->json([
//                         'success' => true,
//                         'via'     => 'agent',
//                     ]);
//                 }

//                 \Log::warning('Print agent error', [
//                     'order' => $order->id,
//                     'resp'  => $resp->body(),
//                 ]);

//             } catch (\Throwable $ex) {
//                 \Log::warning('Print agent unreachable', [
//                     'order' => $order->id,
//                     'error' => $ex->getMessage(),
//                 ]);
//             }
//         }

//         // ===============================
//         // FALLBACK: LOCAL SERVER PRINT
//         // ===============================
//         app(PrinterService::class)->printKitchenOrder($order);

//         return response()->json([
//             'success' => true,
//             'via'     => 'server',
//         ]);

//     } catch (\Throwable $e) {
//         \Log::error('Fallo impresion de cocina ' . $order->id . ': ' . $e->getMessage());

//         return response()->json([
//             'success' => false,
//             'message' => 'Error al imprimir en cocina',
//         ], 500);
//     }
 }

}
