<?php

namespace App\Http\Controllers\Waiter;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\AddItemsRequest;
use App\Http\Requests\Order\ChangeTableRequest;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(protected OrderService $orderService) {}

    // ─── Listado del mozo ─────────────────────────────────────────────────────

    public function index()
    {
        $orders = Order::with(['user', 'items', 'childOrders.items'])
            ->where('user_id', auth()->id())
            ->pending()
            ->whereNull('origin_order_id')
            ->orderByDesc('created_at')
            ->get();

        $ordersByTable = $this->orderService->groupOrdersByTableForWaiter($orders);

        return view('orders.mozo-index', compact('ordersByTable'));
    }

    // ─── Selección de mesas ───────────────────────────────────────────────────

    public function selectTables(Request $request)
    {
        $tableCount    = (int) (Setting::getValue('total_tables', 0) ?? 0);
        $tableNumbers  = $tableCount > 0 ? range(1, $tableCount) : [];
        $busyTables    = $this->orderService->busyTables();
        $selectedTables = $this->sanitizeTables($request->input('tables', []), $tableCount);

        return view('orders.select-tables', compact('tableCount', 'tableNumbers', 'selectedTables', 'busyTables'));
    }

    // ─── Crear pedido ─────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $tableCount    = (int) (Setting::getValue('total_tables', 0) ?? 0);
        $tableNumbers  = $tableCount > 0 ? range(1, $tableCount) : [];
        $selectedTables = $this->sanitizeTables($request->input('tables', []), $tableCount);
        [$products, $categories] = $this->loadProductsAndCategories();

        return view('orders.create', compact('products', 'categories', 'tableCount', 'tableNumbers', 'selectedTables'));
    }

    public function store(StoreOrderRequest $request)
    {
        $validated  = $request->validated();
        $tableCount = (int) (Setting::getValue('total_tables', 0) ?? 0);
        $isMesa     = $validated['type'] === 'mesa';

        if ($isMesa && $tableCount === 0) {
            return back()->withErrors(['tables' => 'Configura la cantidad total de mesas antes de registrar pedidos en mesa.'])->withInput();
        }

        $selectedTables = $isMesa
            ? $this->sanitizeTables($validated['tables'] ?? [], $tableCount)
            : [];

        if ($isMesa) {
            // Redirigir si ya existe un pedido activo de este mozo en esa mesa
            $existing = Order::pending()
                ->where('user_id', $request->user()->id)
                ->where(fn ($q) => collect($selectedTables)->each(fn ($t) => $q->orWhereJsonContains('table_numbers', $t)))
                ->first();

            if ($existing) {
                return redirect()->route('mozo.orders.add-items', $existing)
                    ->with('info', 'Ya existe un pedido activo en esta mesa. Agrega productos aquí.');
            }

            // Verificar conflicto con otros mozos
            $conflicts = array_values(array_intersect(
                $this->orderService->busyTablesByOtherWaiters($request->user()->id),
                $selectedTables
            ));

            if (! empty($conflicts)) {
                return back()->withErrors(['tables' => 'Las mesas '.implode(' + ', $conflicts).' ya están ocupadas por otro mozo.'])->withInput();
            }
        }

        $items = $this->filterItems($validated['items'] ?? []);

        try {
            $order = $this->orderService->createOrder([
                'user_id'       => $request->user()->id,
                'customer_name' => $validated['customer_name'] ?? null,
                'comment'       => $validated['comment'] ?? null,
                'type'          => $validated['type'],
                'table_numbers' => $selectedTables,
                'items'         => $items,
            ]);

            $this->orderService->printKitchenSafely($order);

            return redirect()->route('mozo.orders.show', $order)->with('success', 'Pedido registrado.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }
    }

    // ─── Ver pedido ───────────────────────────────────────────────────────────

    public function show(Order $order)
    {
        $order->load('items.product');
        $categories = Category::with('products')->get();

        return view('orders.show', compact('order', 'categories'));
    }

    // ─── Agregar items ────────────────────────────────────────────────────────

    public function addItemsForm(Order $order)
    {
        $this->authorizeOrderAccess($order);

        if ($order->status !== 'pendiente') {
            return redirect()->route('mozo.orders.show', $order)
                ->withErrors(['order' => 'Solo puede agregarse productos a pedidos pendientes.']);
        }

        [$products, $categories] = $this->loadProductsAndCategories();

        return view('orders.add-items', compact('order', 'products', 'categories'));
    }

    public function addItemsStore(AddItemsRequest $request, Order $order)
    {
        $this->authorizeOrderAccess($order);

        if ($order->status !== 'pendiente') {
            return redirect()->route('mozo.orders.show', $order)
                ->withErrors(['order' => 'Solo puede agregarse productos a pedidos pendientes.']);
        }

        $items = $this->filterItems($request->validated()['items'] ?? []);

        try {
            $child = $this->orderService->addItemsToOrder(
                $order,
                ['items' => $items, 'takeaway' => $request->boolean('takeaway')],
                $request->user()->id
            );

            $this->orderService->printKitchenSafely($child);

            return redirect()->route('mozo.orders.show', $child)
                ->with('success', 'Productos agregados. Se creó la orden #'.$child->id.' para cocina.');
        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => $e->getMessage()])->withInput();
        }
    }

    // ─── Cambiar mesa ─────────────────────────────────────────────────────────

    public function changeTableForm(Order $order)
    {
        $this->authorizeOrderAccess($order);

        if ($order->status !== 'pendiente') {
            return redirect()->route('mozo.orders.index')
                ->withErrors(['order' => 'Solo se pueden mover pedidos pendientes.']);
        }

        $tableCount      = (int) (Setting::getValue('total_tables', 0) ?? 0);
        $tableNumbers    = $tableCount > 0 ? range(1, $tableCount) : [];
        $busyTables      = $this->orderService->busyTables($order->id);
        $availableTables = array_values(array_filter($tableNumbers, fn ($t) => ! in_array($t, $busyTables)));

        return view('orders.change-table', compact('order', 'tableNumbers', 'availableTables', 'busyTables', 'tableCount'));
    }

    public function changeTableStore(ChangeTableRequest $request, Order $order)
    {
        $this->authorizeOrderAccess($order);

        if ($order->status !== 'pendiente') {
            return redirect()->route('mozo.orders.index')
                ->withErrors(['order' => 'Solo se pueden mover pedidos pendientes.']);
        }

        $tableCount = (int) (Setting::getValue('total_tables', 0) ?? 0);
        $selected   = $this->sanitizeTables($request->validated()['tables'], $tableCount);
        $conflicts  = array_values(array_intersect(
            $this->orderService->busyTables($order->id),
            $selected
        ));

        if (! empty($conflicts)) {
            return back()->withErrors(['tables' => 'Las mesas '.implode(' + ', $conflicts).' ya están ocupadas por otro mozo.'])->withInput();
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($order, $selected) {
            $order->table_numbers = $selected;
            $order->save();
            $order->childOrders()->update(['table_numbers' => $selected]);
        });

        return redirect()->route('mozo.orders.index')->with('success', 'Mesa cambiada correctamente.');
    }

    // ─── JSON: mesa pendiente ─────────────────────────────────────────────────

    public function pendingByTable(int $table)
    {
        $order = Order::pending()->whereJsonContains('table_numbers', $table)->first();

        return $order
            ? response()->json(['id' => $order->id, 'user_id' => $order->user_id])
            : response()->json(null);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function authorizeOrderAccess(Order $order): void
    {
        if (auth()->id() !== $order->user_id && ! auth()->user()->isMozo()) {
            abort(403);
        }
    }

    private function sanitizeTables(array $tables, int $tableCount): array
    {
        return collect($tables)
            ->map(fn ($t) => (int) $t)
            ->filter(fn ($t) => $t > 0 && ($tableCount === 0 || $t <= $tableCount))
            ->unique()
            ->values()
            ->all();
    }

    private function filterItems(array $items): array
    {
        return collect($items)
            ->filter(fn ($item) => isset($item['quantity']) && (int) $item['quantity'] > 0)
            ->values()
            ->all();
    }

    private function loadProductsAndCategories(): array
    {
        $stockEnabled       = (bool) Setting::getValue('stock_enabled', false);
        $stockMinimum       = Setting::getValue('stock_minimum_threshold', null);
        $stockAllowNegative = (bool) Setting::getValue('stock_allow_negative', false);

        $products = Product::where('is_available', true)
            ->select('id', 'name', 'price', 'category_id', 'is_available', 'stock')
            ->get()
            ->map(fn ($p) => [
                'id'             => $p->id,
                'name'           => $p->name,
                'price'          => $p->price,
                'category_id'    => $p->category_id,
                'is_available'   => (bool) $p->is_available,
                'stock'          => (int) $p->stock,
                'low_stock'      => $stockEnabled && $stockMinimum !== null && (int) $p->stock <= $stockMinimum && (int) $p->stock > 0,
                'sold_out'       => $stockEnabled && (int) $p->stock <= 0,
                'allow_negative' => $stockAllowNegative,
            ])
            ->values();

        $categories = Category::select('id', 'name')->orderByRaw('FIELD(id, 4, 1, 2, 3, 5)')->get();

        return [$products, $categories];
    }
}
