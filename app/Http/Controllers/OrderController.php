<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Table;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $query = Order::with(['user', 'table'])->orderBy('created_at', 'desc');

        // En caja se prioriza ver pagos; mantenemos pendiente visibles para poder cobrarlos
        if (auth()->check() && auth()->user()->role->name === 'cajero') {
            $query->orderByRaw("FIELD(status, 'pendiente', 'pagado', 'cancelado')");
        }

        $orders = $query->get();
        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        $products = Product::where('is_available', true)->get();
        $tables = Table::where('status', 'libre')->get();
        return view('orders.create', compact('products', 'tables'));
    }

    public function store(Request $request)
    {
        // Filtrar items con cantidad > 0 para evitar validaciones con ceros
        $filteredItems = collect($request->input('items', []))
            ->filter(fn ($item) => isset($item['quantity']) && (int) $item['quantity'] > 0)
            ->values()
            ->all();

        $validated = $request->merge(['items' => $filteredItems])->validate([
            'customer_name' => 'nullable|string',
            'type' => 'required|in:mesa,llevar',
            'table_id' => 'required_if:type,mesa|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($request, $validated) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'table_id' => $validated['type'] === 'mesa' ? $validated['table_id'] : null,
                'customer_name' => $validated['customer_name'],
                'type' => $validated['type'],
                'total' => 0,
            ]);

            if ($order->type === 'mesa' && $order->table) {
                $order->table->update(['status' => 'ocupada']);
            }

            $total = 0;
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (! $product) {
                    continue;
                }

                $subtotal = $product->price * $item['quantity'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);

                $total += $subtotal;
            }

            $order->update(['total' => $total]);

            // Redireccionar según el rol para evitar 403 si la ruta 'orders.show' solo es para cajeros
            $route = $request->user()->role->name === 'mozo' ? 'mozo.orders.show' : 'orders.show';
            return redirect()->route($route, $order)->with('success', 'Pedido registrado.');
        });
    }

    public function pay(Request $request, Order $order)
    {
        if ($order->status === 'pagado') {
            return redirect()->route('orders.show', $order)->with('success', 'El pedido ya está pagado.');
        }

        $order->update(['status' => 'pagado']);

        if ($order->table) {
            $order->table->update(['status' => 'libre']);
        }

        return redirect()
            ->route('orders.show', $order)
            ->with([
                'success' => 'Pago registrado correctamente.',
                'paid' => true,
            ]);
    }

    public function show(Order $order)
    {
        $order->load('items.product');
        return view('orders.show', compact('order'));
    }
}
