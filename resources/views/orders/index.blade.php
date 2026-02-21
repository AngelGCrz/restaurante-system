<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Listado de Pedidos (Caja)</h1>
        </div>

        @if(session('success'))
            <div class="rounded-xl bg-green-100 border border-green-300 text-green-800 p-4 dark:bg-green-900 dark:border-green-700 dark:text-green-200">
                {{ session('success') }}
            </div>
        @endif

        @if($ordersByTable->isEmpty())
            <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                <p class="text-gray-500">No hay pedidos registrados.</p>
            </div>
        @endif

        @foreach($ordersByTable as $session)
            @php
                $tableKey    = $session['tableKey'];
                $tableOrders = $session['orders'];
                $isCurrent   = $session['isCurrent'];
                $hasPending  = $tableOrders->where('status', 'pendiente')->count() > 0;
                $allCancelled = $tableOrders->every(fn($o) => $o->status === 'cancelado');  // ← NUEVO
            @endphp

            <div class="rounded-xl border bg-white p-6 dark:bg-zinc-800 mb-4
                {{-- Borde según estado de la sesión --}}
                @if($hasPending)
                    border-yellow-400 dark:border-yellow-600 border-l-4 border-l-yellow-500
                @elseif($isCurrent)
                    border-zinc-200 dark:border-zinc-700
                @else
                    border-zinc-200 dark:border-zinc-700 opacity-60
                @endif">

                {{-- 🏷️ Header --}}
                <div class="flex items-center justify-between mb-4 pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <div class="flex items-center gap-3">
                            <h2 class="text-xl font-bold dark:text-white">
                                @if($tableKey === 'llevar')
                                    🥡 Para Llevar
                                @else
                                    🪑 Mesa {{ $tableKey }}
                                @endif
                            </h2>

                            {{-- Badge de sesión --}}
                            @if($hasPending)
                                <span class="text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 px-2 py-1 rounded-full font-semibold">
                                    🟡 Sesión Activa
                                </span>
                            @elseif($allCancelled)   {{-- ← NUEVO BLOQUE --}}
                                <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 px-2 py-1 rounded-full font-semibold">
                                    🚫 Anulado
                                </span>
                            @elseif(!$isCurrent)
                                <span class="text-xs bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400 px-2 py-1 rounded-full">
                                    Histórico
                                </span>
                            @else
                                <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 px-2 py-1 rounded-full font-semibold">
                                    ✅ Cobrado
                                </span>
                            @endif
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $tableOrders->count() }} pedido(s) |
                            <span class="font-semibold">Pendientes: {{ $tableOrders->where('status', 'pendiente')->count() }}</span>
                        </p>
                    </div>

                    <div class="text-right">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Total Mesa</p>
                        <p class="text-3xl font-bold {{ $hasPending ? 'text-yellow-500' : ($allCancelled ? 'text-red-500' : 'text-green-600') }}">

                        @php
                            // Calcular total cobrable de la mesa:
                            // - Solo sumar el total de los pedidos padre que estén PENDIENTES (evita cobrar padres anulados)
                            // - Añadir siempre el total de las órdenes hijas que estén pendientes
                            $tableTotal = $tableOrders->whereNull('origin_order_id')->reduce(function($carry, $o) {
                                $parentItemsTotal = ($o->status === 'pendiente')
                                    ? (($o->items ?? collect())->sum(function($it) { return ($it->price * $it->quantity); }))
                                    : 0;
                                $childrenPendingTotal = ($o->childOrders ?? collect())->where('status', 'pendiente')->sum('total');
                                return $carry + $parentItemsTotal + $childrenPendingTotal;
                            }, 0);
                        @endphp

                        @if($hasPending)
                        <button onclick="openPayTableModal('{{ $tableKey }}', {{ $tableTotal }})"
                                class="mt-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                                💳 Cobrar Mesa Completa
                            </button>
                        @endif
                    </div>
                </div>

                {{-- 📋 Tabla --}}
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700">
                            <th class="pb-3 font-semibold dark:text-white">ID</th>
                            <th class="pb-3 font-semibold dark:text-white">Cliente</th>
                            <th class="pb-3 font-semibold dark:text-white">Total</th>
                            <th class="pb-3 font-semibold dark:text-white">Estado</th>
                            <th class="pb-3 font-semibold dark:text-white">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        @foreach($tableOrders->sortByDesc('created_at') as $order)
                            <tr class="{{ $order->status !== 'pendiente' ? 'opacity-50' : '' }}">
                                <td class="py-3 dark:text-white">#{{ $order->id }}</td>
                                <td class="py-3 dark:text-white flex items-center gap-2">
                                    <span>{{ $order->customer_name ?? 'N/A' }}</span>
                                    @if($order->type === 'llevar' && !empty($order->table_numbers))
                                        <span class="text-xs bg-indigo-100 text-indigo-700 dark:bg-indigo-900 dark:text-indigo-200 px-2 py-1 rounded-full">
                                            🥡 Para llevar (Mesa {{ implode('+', $order->table_numbers) }})
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 dark:text-white">S/ {{ number_format($order->total, 2) }}</td>
                                <td class="py-3">
                                    <span class="rounded-full px-2 py-1 text-xs
                                        {{ $order->status === 'pagado'    ? 'bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300' :
                                          ($order->status === 'pendiente' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300' :
                                                                            'bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300') }}">
                                        {{ ucfirst($order->status) }}
                                    </span>
                                </td>
                                <td class="py-3 flex items-center gap-2">
                                    <flux:button size="sm" variant="subtle" href="{{ route('orders.show', $order) }}">
                                        Ver
                                    </flux:button>
                                    @if($order->status === 'pendiente')
                                        <form action="{{ route('orders.cancel', $order) }}" method="POST"
                                            onsubmit="return confirm('¿Cancelar pedido #{{ $order->id }}?');">
                                            @csrf
                                            <flux:button type="submit" size="sm" variant="danger">Cancelar</flux:button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    {{-- 💳 Modal --}}
    <div id="payTableModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 max-w-md w-full mx-4">
            <h2 class="text-2xl font-bold mb-4 dark:text-white">Cobrar Mesa</h2>
            <form action="{{ route('orders.pay-table') }}" method="POST">
                @csrf
                <input type="hidden" name="table_key" id="modal_table_key">
                <div class="mb-4">
                    <p class="text-lg mb-2 dark:text-gray-300">
                        Total a cobrar: <span class="font-bold text-green-600 text-2xl" id="modal_total"></span>
                    </p>
                </div>
                <div class="mb-4">
                    <label class="block font-semibold mb-2 dark:text-white">Método de Pago</label>
                    <select name="payment_method" required
                        class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 dark:bg-zinc-700 dark:text-white">
                        <option value="efectivo">💵 Efectivo</option>
                        <option value="yape">📱 Yape</option>
                        <option value="tarjeta">💳 Tarjeta</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="block font-semibold mb-2 dark:text-white">Tipo de Comprobante</label>
                    <select name="receipt_type" required
                        class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 dark:bg-zinc-700 dark:text-white">
                        <option value="ticket">🧾 Ticket</option>
                        <option value="boleta">📄 Boleta</option>
                        <option value="factura">📋 Factura</option>
                    </select>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closePayTableModal()"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 px-4 py-2 rounded-lg font-semibold dark:text-white">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold">
                        Confirmar Cobro
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openPayTableModal(tableKey, total) {
            document.getElementById('modal_table_key').value = tableKey;
            document.getElementById('modal_total').textContent = 'S/ ' + parseFloat(total).toFixed(2);
            document.getElementById('payTableModal').classList.remove('hidden');
        }
        function closePayTableModal() {
            document.getElementById('payTableModal').classList.add('hidden');
        }
        document.getElementById('payTableModal').addEventListener('click', function(e) {
            if (e.target === this) closePayTableModal();
        });
    </script>
    @endpush
</x-layouts.app>