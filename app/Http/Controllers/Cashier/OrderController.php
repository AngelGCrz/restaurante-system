<?php

namespace App\Http\Controllers\Cashier;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\PayOrderRequest;
use App\Http\Requests\Order\PayTableRequest;
use App\Models\Category;
use App\Models\Order;
use App\Services\OrderService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    // ─── Listado (vista caja) ─────────────────────────────────────────────────

    public function index()
    {
        $orders = Order::with(['user', 'items', 'childOrders.items'])
            ->today()
            ->pending()
            ->orderByRaw("FIELD(status, 'pendiente', 'pagado', 'cancelado')")
            ->get();

        $ordersByTable = $this->orderService->groupOrdersByTableForCashier($orders);

        return view('orders.index', compact('ordersByTable'));
    }

    // ─── Ver pedido ───────────────────────────────────────────────────────────

    public function show(Order $order)
    {
        $order->load('items.product');
        $categories = Category::with('products')->get();

        return view('orders.show', compact('order', 'categories'));
    }

    // ─── Cobrar pedido individual ─────────────────────────────────────────────

    public function pay(PayOrderRequest $request, Order $order)
    {
        $validated = $request->validated();
        $this->orderService->payOrder($order, $validated['payment_method'], $validated['receipt_type']);

        return redirect()->route('orders.show', $order)->with('paid', true);
    }

    // ─── Cobrar toda la mesa ──────────────────────────────────────────────────

    public function payTable(PayTableRequest $request)
    {
        $validated = $request->validated();

        try {
            $paid = $this->orderService->payTable(
                $validated['table_key'],
                $validated['payment_method'],
                $validated['receipt_type']
            );

            $total    = $paid->sum('total');
            $cantidad = $paid->count();

            return redirect()->route('orders.index')
                ->with('success', "Mesa cobrada. {$cantidad} pedido(s) | Total: S/ ".number_format($total, 2));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['table' => $e->getMessage()]);
        }
    }

    // ─── Cancelar pedido ──────────────────────────────────────────────────────

    public function cancel(Request $request, Order $order)
    {
        if ($order->status === 'cancelado') {
            return redirect()->route('orders.show', $order)->with('info', 'El pedido ya está cancelado.');
        }

        try {
            $this->orderService->cancelOrder($order);
        } catch (\RuntimeException $e) {
            return redirect()->route('orders.show', $order)->withErrors(['order' => $e->getMessage()]);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Pedido cancelado.');
    }

    // ─── Historial de pagos ───────────────────────────────────────────────────

    public function payments(Request $request)
    {
        $status = $request->input('status', 'all');
        $from   = $request->input('from');
        $to     = $request->input('to');
        $search = $request->input('search');

        $base = Order::query();

        if ($from) {
            $base->whereDate('updated_at', '>=', $from);
        }
        if ($to) {
            $base->whereDate('updated_at', '<=', $to);
        }
        if ($search) {
            $base->where(fn ($q) => $q->where('customer_name', 'like', "%{$search}%")->orWhere('id', $search));
        }

        $countPagado   = (clone $base)->where('status', 'pagado')->count();
        $totalPagado   = (clone $base)->where('status', 'pagado')->sum('total');
        $countCancelado = (clone $base)->where('status', 'cancelado')->count();

        $listQ = clone $base;
        match ($status) {
            'pagado'    => $listQ->where('status', 'pagado'),
            'cancelado' => $listQ->where('status', 'cancelado'),
            default     => $listQ->whereIn('status', ['pagado', 'cancelado']),
        };

        $orders = $listQ->orderByDesc('updated_at')->paginate(20)->withQueryString();

        return view('orders.payments', compact(
            'orders', 'countPagado', 'totalPagado', 'countCancelado',
            'status', 'from', 'to', 'search'
        ));
    }

    // ─── Polling de caja (JSON) ───────────────────────────────────────────────

    public function pollCaja()
    {
        $orders = Order::pending()
            ->today()
            ->orderByDesc('created_at')
            ->get(['id', 'status', 'total', 'updated_at']);

        $hash = md5($orders->map(fn ($o) => $o->id.$o->status.$o->updated_at)->join(','));

        return response()->json([
            'hash'          => $hash,
            'pending_count' => $orders->count(),
        ]);
    }
}
