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
        $products = Product::where('is_available', true)
            ->select('id', 'name', 'price', 'category_id', 'is_available')
            ->get();
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

        $validated = $request->merge([
            'items' => $filteredItems,
            'tables' => $selectedTables,
        ])->validate([
            'customer_name' => 'nullable|string',
            'comment' => 'nullable|string',
            'type' => 'required|in:mesa,llevar',
            'tables' => 'required_if:type,mesa|array|min:1',
            'tables.*' => 'integer|min:1|max:' . max($tableCount, 1),
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
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

        return DB::transaction(function () use ($request, $validated) {
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
