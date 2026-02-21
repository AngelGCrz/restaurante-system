<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\Setting;
use App\Services\PrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $query = Order::with(['user', 'items', 'childOrders.items'])->orderBy('created_at', 'desc');

    if (auth()->check() && auth()->user()->role->name === 'cajero') {
        $query->orderByRaw("FIELD(status, 'pendiente', 'pagado', 'cancelado')");
    }

    $orders = $query->get();

    if (auth()->check() && auth()->user()->role->name === 'cajero') {
        
        // 1️⃣ Agrupar todos los pedidos por mesa.
        // Los pedidos de tipo 'llevar' que además tengan `table_numbers`
        // deben aparecer tanto en el bloque 'llevar' como en el bloque de la(s) mesa(s).
        $grouped = collect();

        foreach ($orders as $order) {
            $keys = [];

            // Si el pedido tiene mesas asociadas, agrupar SOLO por la(s) mesa(s).
            // Esto evita mostrar dos veces un pedido 'llevar' que además tenga mesas.
            $tables = $order->table_numbers ?? [];
            if (!empty($tables)) {
                $tableKey = implode(',', $tables);
                $keys[] = $tableKey;
            } else {
                // Si no tiene mesas y es 'llevar', agrupar en el bloque 'llevar'
                if ($order->type === 'llevar') {
                    $keys[] = 'llevar';
                } else {
                    // Pedidos sin mesa y no 'llevar' (edge-case)
                    $keys[] = '';
                }
            }

            foreach (array_unique($keys) as $key) {
                if (! $grouped->has($key)) {
                    $grouped->put($key, collect());
                }
                $grouped->get($key)->push($order);
            }
        }

        // 2️⃣ Separar por sesión dentro de cada mesa
        $ordersByTable = collect();

        foreach ($grouped as $tableKey => $tableOrders) {
            
            // Ordenar por fecha ascendente para procesar cronológicamente
            $sorted = $tableOrders->sortBy('created_at');
            
            $session = collect();
            $sessionIndex = 0;

            foreach ($sorted as $order) {
                $session->push($order);

                // Si el pedido es padre (sin origin_order_id) y ya fue cobrado/cancelado,
                // verificar si el siguiente pedido es una nueva sesión
                // Una sesión termina cuando NO hay ningún pedido pendiente en ella
                $hasPending = $session->where('status', 'pendiente')->count() > 0;

                // Si esta es la última orden del grupo, siempre guardar la sesión
            }

            // 3️⃣ Separar sesiones: nueva sesión = nuevo pedido padre sin origin_order_id
            //    después de que todos los anteriores están cerrados
            $sessions = collect();
            $currentSession = collect();

            foreach ($sorted as $order) {
                // Si es un pedido padre (origen) y la sesión actual ya no tiene pendientes
                $isParent = is_null($order->origin_order_id);
                $currentHasPending = $currentSession->where('status', 'pendiente')->count() > 0;
                $currentHasOrders = $currentSession->count() > 0;
                $allClosed = $currentHasOrders && !$currentHasPending;

                if ($isParent && $allClosed) {
                    // Guardar sesión anterior y empezar una nueva
                    $sessions->push([
                        'tableKey' => $tableKey,
                        'orders' => $currentSession,
                        'isCurrent' => false, // Sesión histórica
                    ]);
                    $currentSession = collect();
                }

                $currentSession->push($order);
            }

            // Guardar la última sesión (la activa)
            if ($currentSession->count() > 0) {
                $sessions->push([
                    'tableKey' => $tableKey,
                    'orders' => $currentSession,
                    'isCurrent' => true, // Sesión actual
                ]);
            }

            foreach ($sessions as $session) {
                $ordersByTable->push($session);
            }
        }

        // 4️⃣ Ordenar: sesiones activas primero
        $ordersByTable = $ordersByTable->sortByDesc('isCurrent');

        return view('orders.index', compact('ordersByTable'));
    }

    return view('orders.index', compact('orders'));
}

    /**
     * Listado de pedidos creados por el mozo autenticado.
     */
    public function mozoIndex()
{ 
        // Solo pedidos creados por el mozo y que sean pedidos padre (sin origin)
        $orders = Order::with(['user', 'items', 'childOrders.items'])
            ->where('user_id', auth()->id())
            ->where('status', 'pendiente') // Solo mostrar pedidos activos
            ->whereNull('origin_order_id') // Mostrar únicamente pedidos padre
            ->orderBy('created_at', 'desc')
            ->get();

        // Agrupar pedidos por mesa o en 'llevar' cuando no tengan mesas
        $ordersByTable = $orders->groupBy(function($order) {
            $tables = $order->table_numbers ?? [];

            // Si es 'llevar' y NO tiene mesas, va al bloque 'llevar'
            if ($order->type === 'llevar' && empty($tables)) {
                return 'llevar';
            }

            // Si tiene mesas, agrupar por esas mesas (como '1' o '1,2')
            if (!empty($tables)) {
                return implode(',', $tables);
            }

            // Fallback para pedidos sin mesa y no 'llevar'
            return '';
        });

        return view('orders.mozo-index', compact('ordersByTable'));
}



    // public function mozoIndex()
    // {
    //     $query = Order::with(['user'])->where('user_id', auth()->id())->orderBy('created_at', 'desc');
    //     $orders = $query->get();
    //     return view('orders.mozo-index', compact('orders'));
    // }

    /**
     * Devuelve si existe un pedido pendiente para una mesa dada (JSON).
     */
    public function pendingByTable($table)
    {
        $table = (int) $table;
        $order = Order::where('status', 'pendiente')
            ->whereJsonContains('table_numbers', $table)
            ->first();

        if (! $order) {
            return response()->json(null);
        }

        return response()->json([
            'id' => $order->id,
            'user_id' => $order->user_id,
        ]);
    }

    /**
     * Mostrar formulario para agregar productos a un pedido (mozo).
     */
    public function addItemsForm(Order $order)
    {
        if (! (auth()->check() && (auth()->user()->role->name === 'mozo' || auth()->id() === $order->user_id))) {
            abort(403);
        }

        if ($order->status !== 'pendiente') {
            return redirect()->route('mozo.orders.show', $order)->withErrors(['order' => 'Solo puede agregarse productos a pedidos pendientes.']);
        }

        $categories = Category::with(['products' => function ($q) {
            $q->where('is_available', true)->orderBy('name');
        }])->orderBy('name')->get();

        return view('orders.add-items', compact('order', 'categories'));
    }

    /**
     * Mostrar formulario para cambiar la(s) mesa(s) de un pedido (mozo).
     */
    public function changeTableForm(Order $order)
    {
        if (! (auth()->check() && (auth()->user()->role->name === 'mozo' || auth()->id() === $order->user_id))) {
            abort(403);
        }

        if ($order->status !== 'pendiente') {
            return redirect()->route('mozo.orders.index')->withErrors(['order' => 'Solo se pueden mover pedidos pendientes.']);
        }

        $tableCount = (int) (Setting::getValue('total_tables', 0) ?? 0);
        $tableNumbers = $tableCount > 0 ? range(1, $tableCount) : [];

        $busyTables = Order::where('status', 'pendiente')
            ->where('id', '!=', $order->id)
            ->pluck('table_numbers')
            ->flatten()
            ->map(fn ($t) => (int) $t)
            ->filter(fn ($t) => $t > 0)
            ->unique()
            ->values()
            ->all();

        $availableTables = array_values(array_filter($tableNumbers, fn($t) => !in_array($t, $busyTables)));

        return view('orders.change-table', compact('order', 'tableNumbers', 'availableTables', 'busyTables', 'tableCount'));
    }

    /**
     * Procesar el traslado de mesa para un pedido.
     */
    public function changeTableStore(Request $request, Order $order)
    {
        if (! (auth()->check() && (auth()->user()->role->name === 'mozo' || auth()->id() === $order->user_id))) {
            abort(403);
        }

        if ($order->status !== 'pendiente') {
            return redirect()->route('mozo.orders.index')->withErrors(['order' => 'Solo se pueden mover pedidos pendientes.']);
        }

        $tableCount = (int) (Setting::getValue('total_tables', 0) ?? 0);

        $validated = $request->validate([
            'tables' => 'required|array|min:1',
            'tables.*' => 'integer|min:1|max:' . max($tableCount, 1),
        ]);

        $selected = collect($validated['tables'])->map(fn($t) => (int) $t)->filter(fn($t) => $t > 0)->unique()->values()->all();

        $busyTables = Order::where('status', 'pendiente')
            ->where('id', '!=', $order->id)
            ->pluck('table_numbers')
            ->flatten()
            ->map(fn ($t) => (int) $t)
            ->filter(fn ($t) => $t > 0)
            ->unique()
            ->values()
            ->all();

        $conflicts = array_values(array_intersect($busyTables, $selected));
        if (!empty($conflicts)) {
            return back()->withErrors(['tables' => 'Las mesas ' . implode(' + ', $conflicts) . ' ya están ocupadas por otro mozo.'])->withInput();
        }

        DB::transaction(function() use ($order, $selected) {
            $order->table_numbers = $selected;
            $order->save();

            // Actualizar también las órdenes hijas para mantener consistencia
            $order->childOrders()->update(['table_numbers' => $selected]);
        });

        return redirect()->route('mozo.orders.index')->with('success', 'Mesa cambiada correctamente.');
    }

    /**
     * Procesar y guardar productos añadidos al pedido por el mozo.
     */
    public function addItemsStore(Request $request, Order $order)
    {
        if (! (auth()->check() && (auth()->user()->role->name === 'mozo' || auth()->id() === $order->user_id))) {
            abort(403);
        }

        if ($order->status !== 'pendiente') {
            return redirect()->route('mozo.orders.show', $order)->withErrors(['order' => 'Solo puede agregarse productos a pedidos pendientes.']);
        }

        $filteredItems = collect($request->input('items', []))
            ->filter(fn ($item) => isset($item['quantity']) && (int) $item['quantity'] > 0)
            ->values()
            ->all();

        $validated = $request->merge(['items' => $filteredItems])->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.comment' => 'nullable|string',
        ]);

        $stockEnabled = (bool) Setting::getValue('stock_enabled', false);
        $stockAllowNegative = (bool) Setting::getValue('stock_allow_negative', false);

            try {
                $order = DB::transaction(function () use ($order, $validated, $stockEnabled, $stockAllowNegative, $request) {

        $childOrder = Order::create([
            'user_id' => auth()->id(),
            'customer_name' => $order->customer_name,
            'comment' => 'Agregado a la orden #' . $order->id,
            'type' => $request->input('takeaway') ? 'llevar' : $order->type,
            'table_numbers' => $order->table_numbers,
            'total' => 0,
            'status' => 'pendiente',
            'origin_order_id' => $order->id,
        ]);

    $childTotal = 0; // ✅ Solo cuenta los items nuevos

    foreach ($validated['items'] as $item) {
        $product = Product::lockForUpdate()->find($item['product_id']);
        if (!$product) continue;

        if ($stockEnabled) {
            if ($product->stock <= 0) {
                throw new \RuntimeException($product->name . ' (agotado)');
            }
            if (!$product->hasStockFor((int) $item['quantity'], $stockAllowNegative)) {
                throw new \RuntimeException($product->name . ' (disponible: ' . $product->stock . ')');
            }
        }

        $price = $product->price;
        if ($childOrder->type === 'llevar' && $price > 9) {
            $price += 1;
        }

        $subtotal = $price * $item['quantity'];

        $childOrder->items()->create([
            'product_id' => $product->id,
            'quantity' => $item['quantity'],
            'price' => $price,
            'comment' => $item['comment'] ?? null,
        ]);

        if ($stockEnabled) {
            $product->decreaseStock((int) $item['quantity'], $stockAllowNegative);
        }

        $childTotal += $subtotal; // ✅ Acumula solo los nuevos items
    }

    // ✅ El hijo solo tiene su propio total (S/6.50)
    $childOrder->update(['total' => $childTotal]);
    
    // ✅ solo incrementar si la hija es del mismo tipo que el padre
    if ($childOrder->type === $order->type) {
    $order->increment('total', $childTotal);
}
    // Devolver el pedido padre para que la variable $order fuera de la
    // transacción no sea nula (evita "Attempt to read property 'id' on null").
    return $order;
});

            // Enviar a cocina la nueva orden hija que contiene solo los items añadidos
            try {
                $childOrder = Order::where('origin_order_id', $order->id)->orderBy('created_at', 'desc')->first();
                if ($childOrder) {
                    app(PrinterService::class)->printKitchenOrder($childOrder);
                } else {
                    app(PrinterService::class)->printKitchenOrder($order);
                }
            } catch (\Throwable $e) {
                \Log::error('Automatic kitchen print failed when adding items to order ' . $order->id . ': ' . $e->getMessage());
            }

            $childOrder = Order::where('origin_order_id', $order->id)->orderBy('created_at', 'desc')->first();
            $msg = 'Productos agregados al pedido.';
            if ($childOrder) {
                $msg .= ' Se creó la orden #' . $childOrder->id . ' para cocina.';
            }

            // Si se creó una orden hija, mostrar su detalle; sino mostrar la orden padre
            if ($childOrder) {
                return redirect()->route('mozo.orders.show', $childOrder)->with('success', $msg);
            }

            return redirect()->route('mozo.orders.show', $order)->with('success', $msg);

        } catch (\RuntimeException $e) {
            return back()->withErrors(['items' => 'Stock insuficiente para: ' . $e->getMessage()])->withInput();
        }
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

    // 🔍 NUEVA LÓGICA: Buscar pedido pendiente existente en la(s) mesa(s) seleccionada(s)
    if ($validated['type'] === 'mesa') {
        $existingOrder = Order::where('status', 'pendiente')
            ->where('user_id', $request->user()->id) // Del mismo mozo
            ->where(function($query) use ($validated) {
                foreach ($validated['tables'] as $table) {
                    $query->orWhereJsonContains('table_numbers', $table);
                }
            })
            ->first();

        // ✅ Si existe un pedido activo, redirigir para agregar items
        if ($existingOrder) {
            return redirect()
                ->route('mozo.orders.add-items', $existingOrder)
                ->with('info', 'Ya existe un pedido activo en esta mesa. Agrega productos aquí.');
        }

        // ⚠️ Validar que la mesa no esté ocupada por otro mozo
        $busyTables = Order::where('status', 'pendiente')
            ->where('user_id', '!=', $request->user()->id) // De OTRO mozo
            ->pluck('table_numbers')
            ->flatten()
            ->map(fn ($t) => (int) $t)
            ->filter(fn ($t) => $t > 0)
            ->unique()
            ->values()
            ->all();

        $conflicts = array_values(array_intersect($busyTables, $validated['tables']));
        if (!empty($conflicts)) {
            return back()->withErrors([
                'tables' => 'Las mesas ' . implode(' + ', $conflicts) . ' ya están ocupadas por otro mozo.',
            ])->withInput();
        }
    }

    // Stock settings
    $stockEnabled = (bool) Setting::getValue('stock_enabled', false);
    $stockAllowNegative = (bool) Setting::getValue('stock_allow_negative', false);

    // Validación previa de stock
    if ($stockEnabled) {
        $insufficient = [];
        foreach ($validated['items'] as $item) {
            $product = Product::find($item['product_id']);
            if (!$product) {
                continue;
            }

            if ($product->stock <= 0) {
                $insufficient[] = $product->name . ' (agotado)';
                continue;
            }

            if (!$product->hasStockFor((int) $item['quantity'], $stockAllowNegative)) {
                $insufficient[] = $product->name . ' (disponible: ' . $product->stock . ')';
            }
        }

        if (!empty($insufficient)) {
            return back()->withErrors([
                'items' => 'Stock insuficiente para: ' . implode(', ', $insufficient),
            ])->withInput();
        }
    }

    try {
        $order = DB::transaction(function () use ($request, $validated, $stockEnabled, $stockAllowNegative) {

            // 🆕 Crear nuevo pedido
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
                if (!$product) {
                    continue;
                }

                if ($stockEnabled) {
                    if ($product->stock <= 0) {
                        throw new \RuntimeException($product->name . ' (agotado)');
                    }

                    if (!$product->hasStockFor((int) $item['quantity'], $stockAllowNegative)) {
                        throw new \RuntimeException($product->name . ' (disponible: ' . $product->stock . ')');
                    }
                }

                $price = $product->price;

                if ($validated['type'] === 'llevar' && $price > 9) {
                    $price += 1;
                }

                $subtotal = $price * $item['quantity'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $price,
                    'comment' => $item['comment'] ?? null,
                ]);

                if ($stockEnabled) {
                    $product->decreaseStock((int) $item['quantity'], $stockAllowNegative);
                }

                $total += $subtotal;
            }

            $order->update(['total' => $total]);

            return $order;
        });

        // Imprimir en cocina
        if ($request->user()->role->name === 'mozo') {
            try {
                app(PrinterService::class)->printKitchenOrder($order);
            } catch (\Throwable $e) {
                \Log::error('Automatic kitchen print failed for order ' . $order->id . ': ' . $e->getMessage());
            }
        }

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
    $request->validate([
        'payment_method' => 'required|in:efectivo,yape,tarjeta',
        'receipt_type' => 'required|in:ticket,boleta,factura',
    ]);

    $order->payment_method = $request->payment_method;
    $order->receipt_type = $request->receipt_type;
    $order->status = 'pagado';
    $order->save();

    return redirect()->route('orders.show', $order)->with('paid', true);
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
        // Si la orden cancelada es una ORDEN PADRE, promover una hija pendiente (si existe)
        // Esto mantiene la mesa visible en la vista de mozo (las hijas no se listan como padres).
        if (is_null($order->origin_order_id)) {
            $pendingChildren = $order->childOrders()->where('status', 'pendiente')->get();
            foreach ($pendingChildren as $pendingChild) {
                $pendingChild->origin_order_id = null;
                // Asignar la orden promovida al mismo mozo que era responsable del padre
                $pendingChild->user_id = $order->user_id;
                $pendingChild->save();
            }
        }

        // ✅ Si se está cancelando una orden HIJA, decrementar el total del padre
        if ($order->origin_order_id && $order->type !== 'llevar') {
            $parent = Order::find($order->origin_order_id);
            $parent?->decrement('total', $order->total);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Pedido cancelado.');
    }

    public function show(Order $order)
    {
        $order->load('items.product');
    
        $categories = Category::with('products')->get();
        return view('orders.show', compact('order', 'categories'));
    }
    public function addProduct(Request $request)
    {
        $order = Order::findOrFail($request->order_id);
    
        $order->items()->create([
            'product_id' => $request->product_id,
            'quantity' => 1,
            'price' => Product::find($request->product_id)->price,
        ]);
    
        return back();
    }

    /**
 * Cobrar todos los pedidos pendientes de una mesa
 */
public function payTable(Request $request)
{
    $request->validate([
        'table_key' => 'required|string',
        'payment_method' => 'required|in:efectivo,yape,tarjeta',
        'receipt_type' => 'required|in:ticket,boleta,factura',
    ]);

    $tableKey = $request->table_key;

    // Buscar todos los pedidos pendientes de esa mesa
    $orders = Order::where('status', 'pendiente')
        ->when($tableKey !== 'llevar', function($query) use ($tableKey) {
            // Para mesas específicas
            $tableNumbers = explode(',', $tableKey);
            $query->where(function($q) use ($tableNumbers) {
                foreach ($tableNumbers as $table) {
                    $q->orWhereJsonContains('table_numbers', (int)$table);
                }
            });
        })
        ->when($tableKey === 'llevar', function($query) {
            // Para pedidos "para llevar"
            $query->where('type', 'llevar');
        })
        ->get();

    if ($orders->isEmpty()) {
        return back()->withErrors(['table' => 'No hay pedidos pendientes en esta mesa.']);
    }

    // Actualizar todos los pedidos
    DB::transaction(function() use ($orders, $request) {
        foreach ($orders as $order) {
            $order->update([
                'payment_method' => $request->payment_method,
                'receipt_type' => $request->receipt_type,
                'status' => 'pagado',
            ]);
        }
    });

    $totalCobrado = $orders->sum('total');
    $cantidadPedidos = $orders->count();

    return redirect()->route('orders.index')->with('success', 
        "Mesa cobrada exitosamente. {$cantidadPedidos} pedido(s) | Total: S/ " . number_format($totalCobrado, 2)
    );
}



    // public function show(Order $order)
    // {
    //     $order->load('items.product');
    //     return view('orders.show', compact('order'));
    // }
}
