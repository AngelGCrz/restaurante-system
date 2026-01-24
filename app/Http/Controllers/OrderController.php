<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        
        $query = Order::with(['user'])->orderBy('created_at', 'desc');

        // En caja se prioriza ver pagos; mantenemos pendiente visibles para poder cobrarlos
        if (auth()->check() && auth()->user()->role->name === 'cajero') {
            $query->orderByRaw("FIELD(status, 'pendiente', 'pagado', 'cancelado')");
        }

        $orders = $query->get();
        return view('orders.index', compact('orders'));
    }

    public function create()
    {
        // Load products for waiter view (show sold-out as disabled badge instead of hiding)
        $products = Product::where('is_available', true)
            ->select('id', 'name', 'price', 'category_id', 'is_available', 'stock')
            ->get();
        // stock settings configured by admin
        $stockEnabled = (bool) Setting::getValue('stock_enabled', false);
        $stockMinimum = Setting::getValue('stock_minimum_threshold', null);
        $stockAllowNegative = (bool) Setting::getValue('stock_allow_negative', false);

        $products = $products->map(function ($p) use ($stockEnabled, $stockMinimum, $stockAllowNegative) {
            $stock = (int) $p->stock;
            return [
                'id' => $p->id,
                'name' => $p->name,
                'price' => $p->price,
                'category_id' => $p->category_id,
                'is_available' => (bool) $p->is_available,
                'stock' => $stock,
                'low_stock' => $stockEnabled && $stockMinimum !== null && $stock <= $stockMinimum && $stock > 0,
                'sold_out' => $stockEnabled && $stock <= 0,
                'allow_negative' => $stockAllowNegative,
            ];
        })->values();
        $categories = Category::select('id', 'name')->orderBy('name')->get();
        $tableCount = (int) (Setting::getValue('total_tables', 0) ?? 0);
        $tableNumbers = $tableCount > 0 ? range(1, $tableCount) : [];

        $selectedTables = collect(request()->input('tables', []))
            ->map(fn ($table) => (int) $table)
            ->filter(fn ($table) => $table > 0 && ($tableCount === 0 || $table <= $tableCount))
            ->unique()
            ->values()
            ->all();

        return view('orders.create', compact('products', 'categories', 'tableCount', 'tableNumbers', 'selectedTables'));
    }

    public function selectTables()
    {
        $tableCount = (int) (Setting::getValue('total_tables', 0) ?? 0);
        $tableNumbers = $tableCount > 0 ? range(1, $tableCount) : [];

        $busyTables = Order::where('status', 'pendiente')
            ->pluck('table_numbers')
            ->flatten()
            ->map(fn ($t) => (int) $t)
            ->filter(fn ($t) => $t > 0)
            ->unique()
            ->values()
            ->all();

        $selectedTables = collect(request()->input('tables', []))
            ->map(fn ($table) => (int) $table)
            ->filter(fn ($table) => $table > 0 && ($tableCount === 0 || $table <= $tableCount))
            ->unique()
            ->values()
            ->all();

        return view('orders.select-tables', compact('tableCount', 'tableNumbers', 'selectedTables', 'busyTables'));
    }

    public function store(Request $request)
    {
        
        // Filtrar items con cantidad > 0 para evitar validaciones con ceros
        $filteredItems = collect($request->input('items', []))
            ->filter(fn ($item) => isset($item['quantity']) && (int) $item['quantity'] > 0)
            ->values()
            ->all();

        $tableCount = (int) (Setting::getValue('total_tables', 0) ?? 0);

        $selectedTables = collect($request->input('tables', []))
            ->map(fn ($table) => (int) $table)
            ->filter(fn ($table) => $table > 0 && ($tableCount === 0 || $table <= $tableCount))
            ->unique()
            ->values()
            ->all();

        $isMesa = $request->input('type') === 'mesa';

        $validated = $request->merge([
            'items' => $filteredItems,
            'tables' => $isMesa ? $selectedTables : [],
        ])->validate([
            'customer_name' => 'nullable|string',
            'comment' => 'nullable|string',
            'type' => 'required|in:mesa,llevar',
            'tables' => $isMesa ? 'required|array|min:1' : 'nullable|array',
            'tables.*' => 'integer|min:1|max:' . max($tableCount, 1),
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.comment' => 'nullable|string',

        ]);

        if ($validated['type'] === 'mesa' && $tableCount === 0) {
            return back()->withErrors([
                'tables' => 'Configura la cantidad total de mesas antes de registrar pedidos en mesa.',
            ])->withInput();
        }

        if ($validated['type'] === 'mesa') {
            $busyTables = Order::where('status', 'pendiente')
                ->pluck('table_numbers')
                ->flatten()
                ->map(fn ($t) => (int) $t)
                ->filter(fn ($t) => $t > 0)
                ->unique()
                ->values()
                ->all();

            $conflicts = array_values(array_intersect($busyTables, $validated['tables']));
            if (! empty($conflicts)) {
                return back()->withErrors([
                    'tables' => 'Las mesas ' . implode(' + ', $conflicts) . ' ya están ocupadas en otro pedido.',
                ])->withInput();
            }
        }

        // Stock settings
        $stockEnabled = (bool) Setting::getValue('stock_enabled', false);
        $stockAllowNegative = (bool) Setting::getValue('stock_allow_negative', false);

        // If stock tracking is enabled, validate all items have enough stock
        if ($stockEnabled) {
            $insufficient = [];
            foreach ($validated['items'] as $item) {
                $product = Product::find($item['product_id']);
                if (! $product) {
                    continue;
                }

                // If stock tracking is enabled and product has zero or less, it's sold out and cannot be ordered
                if ($product->stock <= 0) {
                    $insufficient[] = $product->name . ' (agotado)';
                    continue;
                }

                if (! $product->hasStockFor((int) $item['quantity'], $stockAllowNegative)) {
                    $insufficient[] = $product->name . ' (disponible: ' . $product->stock . ')';
                }
            }

            if (! empty($insufficient)) {
                return back()->withErrors([
                    'items' => 'Stock insuficiente para: ' . implode(', ', $insufficient),
                ])->withInput();
            }
        }

        try {
            $order = DB::transaction(function () use ($request, $validated, $stockEnabled, $stockAllowNegative) {
                // Re-check stock with row locking to avoid race conditions
                $insufficient = [];
                foreach ($validated['items'] as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);
                    if (! $product) {
                        continue;
                    }

                    if ($stockEnabled) {
                        if ($product->stock <= 0) {
                            $insufficient[] = $product->name . ' (agotado)';
                            continue;
                        }

                        if (! $product->hasStockFor((int) $item['quantity'], $stockAllowNegative)) {
                            $insufficient[] = $product->name . ' (disponible: ' . $product->stock . ')';
                        }
                    }
                }

                if (! empty($insufficient)) {
                    throw new \RuntimeException(implode(', ', $insufficient));
                }

                $order = Order::create([
                    'user_id' => $request->user()->id,
                    'customer_name' => $validated['customer_name'],
                    'comment' => $validated['comment'] ?? null,
                    'type' => $validated['type'],
                    'table_numbers' => $validated['type'] === 'mesa' ? $validated['tables'] : [],
                    'total' => 0,
                ]);

                $total = 0;
                foreach ($validated['items'] as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);
                    if (! $product) {
                        continue;
                    }

                    $subtotal = $product->price * $item['quantity'];

                    $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                    'comment' => $item['comment'] ?? null,
                ]);


                    // Decrement stock if enabled
                    if ($stockEnabled) {
                        $product->decreaseStock((int) $item['quantity'], $stockAllowNegative);
                    }

                    $total += $subtotal;
                }

                $order->update(['total' => $total]);

                return $order;
            });

            // Redireccionar según el rol para evitar 403 si la ruta 'orders.show' solo es para cajeros
            $route = $request->user()->role->name === 'mozo' ? 'mozo.orders.show' : 'orders.show';
            return redirect()->route($route, $order)->with('success', 'Pedido registrado.');
        } catch (\RuntimeException $e) {
            return back()->withErrors([
                'items' => 'Stock insuficiente para: ' . $e->getMessage(),
            ])->withInput();
        }
    }

    public function pay(Request $request, Order $order)
    {
        if ($order->status === 'pagado') {
            return redirect()->route('orders.show', $order)->with('success', 'El pedido ya está pagado.');
        }

        $order->update(['status' => 'pagado']);

        return redirect()
            ->route('orders.show', $order)
            ->with([
                'success' => 'Pago registrado correctamente.',
                'paid' => true,
            ]);
    }

    /**
     * Cancel an order (mark as 'cancelado'). Only allowed when pending.
     */
    public function cancel(Request $request, Order $order)
    {
        if ($order->status === 'cancelado') {
            return redirect()->route('orders.show', $order)->with('info', 'El pedido ya está cancelado.');
        }

        if ($order->status === 'pagado') {
            return redirect()->route('orders.show', $order)->withErrors(['order' => 'No se puede cancelar un pedido ya cobrado.']);
        }

        $order->update(['status' => 'cancelado']);

        return redirect()->route('orders.show', $order)->with('success', 'Pedido cancelado.');
    }

    public function show(Order $order)
    {
        $order->load('items.product');
        return view('orders.show', compact('order'));
    }
}
