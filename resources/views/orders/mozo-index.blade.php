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
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <h2 class="text-xl font-bold">
                        @if($tableKey === 'llevar')
                            🥡 Para Llevar
                        @else
                            🪑 Mesa {{ $tableKey }}
                        @endif
                    </h2>
                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Mesa</p>
                        <p class="text-2xl font-bold text-green-600">
                            S/ {{ number_format($tableOrders->sum('total'), 2) }}
                        </p>
                    </div>
                </div>

                {{-- 📋 Tabla de pedidos --}}
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="pb-3 font-semibold">ID</th>
                            <th class="pb-3 font-semibold">Cliente</th>
                            <th class="pb-3 font-semibold">Total</th>
                            <th class="pb-3 font-semibold">Estado</th>
                            <th class="pb-3 font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($tableOrders as $order)
                            <tr>
                                <td class="py-3">#{{ $order->id }}</td>
                                <td class="py-3">{{ $order->customer_name ?? 'N/A' }}</td>
                                <td class="py-3">S/ {{ number_format($order->total, 2) }}</td>
                                <td class="py-3">
                                    <span class="rounded-full px-2 py-1 text-xs
                                        {{ $order->status === 'pagado' ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' : 
                                           ($order->status === 'pendiente' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' : 
                                           'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300') }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="py-3 flex items-center gap-2">
                                    <flux:button size="sm" variant="subtle" 
                                        href="{{ route('mozo.orders.show', $order) }}">
                                        Ver
                                    </flux:button>
                                    
                                    @if($order->status === 'pendiente')
                                        <flux:button size="sm" variant="primary" 
                                            href="{{ route('mozo.orders.add-items', $order) }}">
                                            + Agregar
                                        </flux:button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>
</x-layouts.app>
