<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Mis Pedidos por Mesa</h1>
        </div>

        @if($ordersByTable->isEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <p class="text-gray-500">No tienes pedidos activos.</p>
            </div>
        @endif

        {{-- 🔄 Mostrar pedidos agrupados por mesa --}}
        @foreach($ordersByTable as $tableKey => $tableOrders)
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 mb-4">
                
                {{-- 🏷️ Header de la mesa --}}
                <div class="flex items-start justify-between mb-4 pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <h2 class="text-xl font-bold">
                            @if($tableKey === 'llevar')
                                🥡 Para Llevar
                            @else
                                🪑 Mesa {{ $tableKey }}
                            @endif
                        </h2>
                        @php $firstPendingOrder = $tableOrders->firstWhere('status', 'pendiente'); @endphp
                        @if($firstPendingOrder)
                        <a href="{{ route('mozo.orders.add-items', $firstPendingOrder) }}"
                           class="mt-1.5 inline-flex items-center gap-1 rounded-full bg-emerald-600 px-3 py-1 text-xs font-semibold text-white hover:bg-emerald-700 transition">
                            ➕ Agregar Nueva Orden
                        </a>
                        @endif
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Mesa</p>
                        <p class="text-2xl font-bold text-green-600">
                            @php
        // Calcular total de mesa a partir de totales reales: cada orden padre
        // suma sus propios items + solo las órdenes hijas que estén 'pendiente'.
        $totalMesa = $tableOrders->reduce(function($carry, $order) {
            // total de items del pedido padre
            $parentItemsTotal = $order->items->sum(function($it) { return ($it->price * $it->quantity); });
            // total de órdenes hijas pendientes
            $childrenPendingTotal = ($order->childOrders ?? collect())->where('status', 'pendiente')->sum('total');
            return $carry + $parentItemsTotal + $childrenPendingTotal;
        }, 0);
    @endphp
S/ {{ number_format($totalMesa, 2) }}
                        </p>
                    </div>
                </div>

                {{-- 📋 Tabla responsive para escritorio y tarjetas para móviles --}}
                {{-- Escritorio: tabla horizontal con scroll si es necesario --}}
                <div class="hidden">
                    <table class="w-full text-left min-w-[600px]">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="pb-3 font-semibold">ID</th>
                                <th class="pb-3 font-semibold">Cliente</th>
                                <th class="pb-3 font-semibold">Estado</th>
                                <th class="pb-3 font-semibold">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($tableOrders as $order)
                                <tr class="bg-zinc-50 dark:bg-zinc-800">
                                    <td class="py-3 font-bold">#{{ $order->id }}</td>
                                    <td class="py-3">
                                        {{ $order->customer_name ?? 'N/A' }}
                                        <span class="text-xs text-gray-400 ml-1">{{ $order->table_label }}</span>
                                    </td>
                                    <td class="py-3">
                                        <span class="rounded-full px-2 py-1 text-xs
                                            {{ $order->status === 'pagado' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 
                                               ($order->status === 'pendiente' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' : 
                                               'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300') }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="py-3 flex items-center gap-2">
                                        <flux:button size="sm" variant="subtle" href="{{ route('mozo.orders.show', $order) }}">Ver</flux:button>
                                        @if($order->status === 'pendiente')
                                            <flux:button size="sm" variant="primary" href="{{ route('mozo.orders.add-items', $order) }}">+ Agregar</flux:button>
                                            <flux:button size="sm" variant="outline" href="{{ route('mozo.orders.change-table', $order) }}">Cambiar Mesa</flux:button>
                                        @endif
                                    </td>
                                </tr>

                                {{-- Filas hijas (escritorio) --}}
                                @foreach(($order->childOrders ?? collect())->sortBy('created_at') as $child)
                                    <tr class="dark:bg-zinc-800 {{ $child->type === 'llevar' ? 'border-l-blue-500' : 'border-l-zinc-400' }} dark:bg-zinc-800">
                                        <td class="py-2 pl-6 text-sm text-gray-400">#{{ $child->id }}</td>
                                        <td class="py-2 text-sm text-gray-400">
                                            {{ $child->customer_name ?? 'N/A' }}
                                            @if($child->type === 'llevar')
                                                <span class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 px-2 py-0.5 rounded-full ml-1">🥡 Para llevar</span>
                                            @endif
                                        </td>
                                        <td class="py-2">
                                            <span class="rounded-full px-2 py-1 text-xs
                                                {{ $child->status === 'pagado' ? 'bg-green-100 text-green-700' : 
                                                   ($child->status === 'pendiente' ? 'bg-yellow-100 text-yellow-700' : 
                                                   'bg-red-100 text-red-700') }}">
                                                {{ ucfirst($child->status) }}
                                            </span>
                                        </td>
                                        <td class="py-2">
                                            <flux:button size="sm" variant="subtle" href="{{ route('mozo.orders.show', $child) }}">Ver</flux:button>
                                        </td>
                                    </tr>

                                    {{-- Detalle de items para la orden hija (escritorio): fila anidada --}}
                                    <tr class="bg-white dark:bg-zinc-900">
                                        <td colspan="4" class="px-6 py-2 text-sm text-gray-600 dark:text-gray-300">
                                            @if(($child->items ?? collect())->isEmpty())
                                                <div class="text-xs text-gray-400">Sin items.</div>
                                            @else
                                                <div class="space-y-1">
                                                    @foreach($child->items as $it)
                                                        <div class="flex items-center justify-between">
                                                            <div class="text-sm text-gray-700 dark:text-gray-200 flex items-center">
                                                                {{ $it->quantity }}× {{ $it->product->name ?? $it->name ?? 'Item' }}
                                                                @if($child->type === 'llevar')
                                                                    <span class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 px-2 py-0.5 rounded-full ml-2">🥡 Para llevar</span>
                                                                @endif
                                                            </div>
                                                            <div class="text-sm text-gray-700 dark:text-gray-200">S/ {{ number_format(($it->price * $it->quantity), 2) }}</div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Móvil: tarjetas apiladas --}}
                <div class="block space-y-3">
                    @foreach($tableOrders as $order)
                        <div class="rounded-lg border border-zinc-200 bg-white p-3 dark:border-zinc-700 dark:bg-zinc-800">
                            <div class="flex items-start justify-between">
                                <div>
                                    <div class="text-sm text-gray-500">#{{ $order->id }} · {{ $order->table_label }}</div>
                                    <div class="font-semibold">{{ $order->customer_name ?? 'N/A' }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="text-sm text-gray-500">Estado</div>
                                    <div>
                                        <span class="rounded-full px-2 py-1 text-xs
                                            {{ $order->status === 'pagado' ? 'bg-green-100 text-green-700' : 
                                               ($order->status === 'pendiente' ? 'bg-yellow-100 text-yellow-700' : 
                                               'bg-red-100 text-red-700') }}">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-3 flex gap-2 flex-wrap">
                                <flux:button size="sm" variant="subtle" href="{{ route('mozo.orders.show', $order) }}">Ver</flux:button>
                                @if($order->status === 'pendiente')
                                    <flux:button size="sm" variant="primary" href="{{ route('mozo.orders.add-items', $order) }}">+ Agregar</flux:button>
                                    <flux:button size="sm" variant="outline" href="{{ route('mozo.orders.change-table', $order) }}">Cambiar Mesa</flux:button>
                                @endif
                            </div>

                            @if(($order->childOrders ?? collect())->isNotEmpty())
                                <div class="mt-3 space-y-2">
                                    @foreach(($order->childOrders ?? collect())->sortBy('created_at') as $child)
                                        <div class="space-y-1">
                                            <div class="flex items-center justify-between bg-zinc-50 dark:bg-zinc-900 rounded-md p-2">
                                                <div class="text-sm text-gray-500">#{{ $child->id }} · {{ $child->customer_name ?? 'N/A' }}</div>
                                                <div>
                                                    <span class="rounded-full px-2 py-1 text-xs
                                                        {{ $child->status === 'pagado' ? 'bg-green-100 text-green-700' : 
                                                           ($child->status === 'pendiente' ? 'bg-yellow-100 text-yellow-700' : 
                                                           'bg-red-100 text-red-700') }}">
                                                        {{ ucfirst($child->status) }}
                                                    </span>
                                                </div>
                                            </div>

                                            {{-- Detalle de items (móvil) --}}
                                            <div class="px-2">
                                                @if(($child->items ?? collect())->isEmpty())
                                                    <div class="text-xs text-gray-400">Sin items.</div>
                                                @else
                                                    <div class="space-y-1">
                                                        @foreach($child->items as $it)
                                                            <div class="flex items-center justify-between text-sm text-gray-700 dark:text-gray-200">
                                                                <div class="flex items-center">
                                                                    <div>{{ $it->quantity }}× {{ $it->product->name ?? $it->name ?? 'Item' }}</div>
                                                                    @if($child->type === 'llevar')
                                                                        <span class="text-xs bg-blue-100 text-blue-700 dark:bg-blue-900 dark:text-blue-300 px-2 py-0.5 rounded-full ml-2">🥡 Para llevar</span>
                                                                    @endif
                                                                </div>
                                                                <div>S/ {{ number_format(($it->price * $it->quantity), 2) }}</div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</x-layouts.app>