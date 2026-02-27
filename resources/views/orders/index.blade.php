<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Listado de Pedidos (Caja)</h1>
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <span id="caja-poll-dot" class="inline-block w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span id="caja-poll-txt">En vivo</span>
            </div>
        </div>

        {{-- Toast --}}
        <div id="caja-toast" class="hidden fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-xl bg-blue-600 text-white px-5 py-4 shadow-xl text-sm font-semibold">
            <span>🔔 Nuevos pedidos</span>
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
                $tableKey     = $session['tableKey'];
                $tableOrders  = $session['orders'];
                $isCurrent    = $session['isCurrent'];
                $hasPending   = $tableOrders->where('status', 'pendiente')->count() > 0;
                $allCancelled = $tableOrders->every(fn($o) => $o->status === 'cancelado');

                // Comentario general: del pedido padre más antiguo
                $parentOrder  = $tableOrders->whereNull('origin_order_id')->sortBy('created_at')->first();
                $tableComment = $parentOrder?->comment;

                // Separar por estado
                $pendingOrders = $tableOrders->where('status', 'pendiente')->sortByDesc('created_at');
                $closedOrders  = $tableOrders->where('status', '!=', 'pendiente')->sortByDesc('created_at');

                $tableTotal = $tableOrders->whereNull('origin_order_id')->reduce(function($carry, $o) {
                    $parentItemsTotal = ($o->status === 'pendiente')
                        ? (($o->items ?? collect())->sum(fn($it) => $it->price * $it->quantity))
                        : 0;
                    $childrenPendingTotal = ($o->childOrders ?? collect())->where('status', 'pendiente')->sum('total');
                    return $carry + $parentItemsTotal + $childrenPendingTotal;
                }, 0);
            @endphp

            <div class="rounded-xl border bg-white p-6 dark:bg-zinc-800 mb-4
                @if($hasPending) border-yellow-400 dark:border-yellow-600 border-l-4 border-l-yellow-500
                @elseif($allCancelled) border-red-200 dark:border-red-800 opacity-70
                @elseif(!$isCurrent) border-zinc-200 dark:border-zinc-700 opacity-60
                @else border-zinc-200 dark:border-zinc-700 @endif">

                {{-- Header --}}
                <div class="flex items-start justify-between mb-4 pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h2 class="text-xl font-bold dark:text-white">
                                @if($tableKey === 'llevar') 🥡 Para Llevar
                                @else 🪑 Mesa {{ $tableKey }} @endif
                            </h2>
                            @if($hasPending)
                                <span class="text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 px-2 py-1 rounded-full font-semibold">🟡 Sesión Activa</span>
                            @elseif($allCancelled)
                                <span class="text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 px-2 py-1 rounded-full font-semibold">🚫 Anulado</span>
                            @elseif(!$isCurrent)
                                <span class="text-xs bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400 px-2 py-1 rounded-full">Histórico</span>
                            @else
                                <span class="text-xs bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 px-2 py-1 rounded-full font-semibold">✅ Cobrado</span>
                            @endif
                        </div>

                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            {{ $tableOrders->count() }} pedido(s) |
                            <span class="font-semibold text-yellow-600 dark:text-yellow-400">Por cobrar: {{ $pendingOrders->count() }}</span>
                            @if($closedOrders->count() > 0)
                                | <span class="font-semibold text-green-600 dark:text-green-400">Cerrados: {{ $closedOrders->count() }}</span>
                            @endif
                        </p>

                        {{-- 💬 Comentario general de mesa --}}
                        @if(!empty($tableComment))
                            <div class="mt-2 flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 dark:bg-amber-900/20 dark:border-amber-700 px-3 py-2 text-sm text-amber-800 dark:text-amber-300 max-w-lg">
                                <span class="shrink-0 text-base">🗒️</span>
                                <div><span class="font-semibold">Nota de mesa:</span> {{ $tableComment }}</div>
                            </div>
                        @endif
                    </div>

                    <div class="text-right ml-4 shrink-0">
                        @if($hasPending)
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total a cobrar</p>
                            <p class="text-3xl font-bold text-yellow-500">S/ {{ number_format($tableTotal, 2) }}</p>
                            <button onclick="openPayTableModal('{{ $tableKey }}', {{ $tableTotal }})"
                                    class="mt-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                                💳 Cobrar Mesa
                            </button>
                        @elseif(!$allCancelled)
                            <p class="text-sm text-gray-500 dark:text-gray-400">Total cobrado</p>
                            <p class="text-2xl font-bold text-green-600">S/ {{ number_format($tableOrders->where('status','pagado')->sum('total'), 2) }}</p>
                        @endif
                    </div>
                </div>

                {{-- 🟡 SECCIÓN: Por cobrar --}}
                @if($pendingOrders->count() > 0)
                    <div class="mb-5">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-yellow-600 dark:text-yellow-400 mb-2 flex items-center gap-2">
                            <span class="inline-block w-2 h-2 rounded-full bg-yellow-400"></span>
                            Por cobrar ({{ $pendingOrders->count() }})
                        </h3>
                        <table class="w-full text-left text-sm">
                            <thead>
                                <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-400">
                                    <th class="pb-2 font-semibold">ID</th>
                                    <th class="pb-2 font-semibold">Cliente</th>
                                    <th class="pb-2 font-semibold">Comentario del pedido</th>
                                    <th class="pb-2 font-semibold">Total</th>
                                    <th class="pb-2 font-semibold">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                @foreach($pendingOrders as $order)
                                    <tr>
                                        <td class="py-2 dark:text-white font-medium">#{{ $order->id }}</td>
                                        <td class="py-2 dark:text-white">
                                            <div class="flex flex-wrap items-center gap-1">
                                                <span>{{ $order->customer_name ?? 'N/A' }}</span>
                                                @if($order->origin_order_id)
                                                    <span class="text-xs bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-300 px-2 py-0.5 rounded-full">➕ Agregado</span>
                                                @endif
                                            </div>
                                        </td>
                                        <td class="py-2 max-w-xs">
                                            @if(!empty($order->comment))
                                                <span class="flex items-start gap-1 text-zinc-600 dark:text-zinc-300 italic text-xs">
                                                    <span class="shrink-0">📝</span>{{ $order->comment }}
                                                </span>
                                            @else
                                                <span class="text-zinc-300 dark:text-zinc-600 text-xs">—</span>
                                            @endif
                                        </td>
                                        <td class="py-2 font-bold text-yellow-600 dark:text-yellow-400 whitespace-nowrap">
                                            S/ {{ number_format($order->total, 2) }}
                                        </td>
                                        <td class="py-2">
                                            <div class="flex items-center gap-2">
                                                <flux:button size="sm" variant="subtle" href="{{ route('orders.show', $order) }}">Ver</flux:button>
                                                <button type="button"
                                                    onclick="openCancelModal({{ $order->id }})"
                                                    class="inline-flex items-center px-2 py-1 text-xs font-medium rounded-md bg-red-100 text-red-700 hover:bg-red-200 dark:bg-red-900 dark:text-red-300 dark:hover:bg-red-800 transition">
                                                    Cancelar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif

                {{-- ✅ SECCIÓN: Cobrados / Cancelados --}}
                @if($closedOrders->count() > 0)
                    <div>
                        <button type="button"
                            onclick="toggleClosed('closed-{{ $loop->index }}')"
                            class="w-full text-left text-xs font-bold uppercase tracking-wider text-zinc-400 dark:text-zinc-500 mb-2 flex items-center gap-2 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                            <span class="inline-block w-2 h-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                            Cobrados / Cancelados ({{ $closedOrders->count() }})
                            <span id="icon-closed-{{ $loop->index }}">▶</span>
                        </button>
                        <div id="closed-{{ $loop->index }}" class="hidden">
                            <table class="w-full text-left text-sm opacity-70">
                                <thead>
                                    <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-400">
                                        <th class="pb-2 font-semibold">ID</th>
                                        <th class="pb-2 font-semibold">Cliente</th>
                                        <th class="pb-2 font-semibold">Comentario del pedido</th>
                                        <th class="pb-2 font-semibold">Total</th>
                                        <th class="pb-2 font-semibold">Estado</th>
                                        <th class="pb-2 font-semibold">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                                    @foreach($closedOrders as $order)
                                        <tr>
                                            <td class="py-2 dark:text-white">#{{ $order->id }}</td>
                                            <td class="py-2 dark:text-white">{{ $order->customer_name ?? 'N/A' }}</td>
                                            <td class="py-2 max-w-xs">
                                                @if(!empty($order->comment))
                                                    <span class="flex items-start gap-1 text-zinc-500 dark:text-zinc-400 italic text-xs">
                                                        <span class="shrink-0">📝</span>{{ $order->comment }}
                                                    </span>
                                                @else
                                                    <span class="text-zinc-300 dark:text-zinc-600 text-xs">—</span>
                                                @endif
                                            </td>
                                            <td class="py-2 dark:text-white">S/ {{ number_format($order->total, 2) }}</td>
                                            <td class="py-2">
                                                @if($order->status === 'pagado')
                                                    <span class="rounded-full px-2 py-1 text-xs bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300">✅ Cobrado</span>
                                                @else
                                                    <span class="rounded-full px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300">🚫 Cancelado</span>
                                                @endif
                                            </td>
                                            <td class="py-2">
                                                <flux:button size="sm" variant="subtle" href="{{ route('orders.show', $order) }}">Ver</flux:button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

            </div>
        @endforeach
    </div>

    {{-- 🚫 Modal Cancelar Pedido --}}
    <div id="cancelModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 max-w-md w-full mx-4 shadow-2xl">
            <h2 class="text-xl font-bold mb-1 dark:text-white">Cancelar Pedido</h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-5">Pedido <span id="cancel-order-label" class="font-semibold text-red-500"></span></p>
            <form id="cancelForm" method="POST">
                @csrf
                <div class="mb-5">
                    <label class="block font-semibold mb-2 dark:text-white text-sm">
                        Motivo de cancelación <span class="text-zinc-400 font-normal">(opcional)</span>
                    </label>
                    <textarea name="cancel_reason" rows="3" placeholder="Ej: Cliente cambió de opinión, error de ingreso..."
                        class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg p-3 text-sm dark:bg-zinc-700 dark:text-white resize-none focus:outline-none focus:ring-2 focus:ring-red-400"></textarea>
                </div>
                <div class="flex gap-3">
                    <button type="button" onclick="closeCancelModal()"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 px-4 py-2 rounded-lg font-semibold dark:text-white transition">
                        Volver
                    </button>
                    <button type="submit"
                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-semibold transition">
                        Confirmar Cancelación
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- 💳 Modal Cobro --}}
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
                    <select name="payment_method" required class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 dark:bg-zinc-700 dark:text-white">
                        <option value="efectivo">💵 Efectivo</option>
                        <option value="yape">📱 Yape</option>
                        <option value="tarjeta">💳 Tarjeta</option>
                    </select>
                </div>
                <div class="mb-6">
                    <label class="block font-semibold mb-2 dark:text-white">Tipo de Comprobante</label>
                    <select name="receipt_type" required class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg p-2 dark:bg-zinc-700 dark:text-white">
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
                    <button type="submit" class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold">
                        Confirmar Cobro
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function openCancelModal(orderId) {
            document.getElementById('cancel-order-label').textContent = '#' + orderId;
            document.getElementById('cancelForm').action = '/orders/' + orderId + '/cancel';
            document.getElementById('cancelModal').classList.remove('hidden');
        }
        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }
        document.getElementById('cancelModal').addEventListener('click', function(e) {
            if (e.target === this) closeCancelModal();
        });

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

        function toggleClosed(id) {
            const el = document.getElementById(id);
            const icon = document.getElementById('icon-' + id);
            const nowHidden = el.classList.toggle('hidden');
            if (icon) icon.textContent = nowHidden ? '▶' : '▼';
        }

        // POLLING
        const POLL_INTERVAL = 5000;
        let lastHash = null;

        async function pollCaja() {
            if (!document.getElementById('payTableModal').classList.contains('hidden')) return;
            try {
                const resp = await fetch('/orders/poll-caja', { credentials: 'same-origin' });
                if (!resp.ok) return;
                const data = await resp.json();
                if (lastHash === null) { lastHash = data.hash; updateIndicator('online'); return; }
                if (data.hash !== lastHash) {
                    lastHash = data.hash;
                    showCajaToast('🔔 Nuevos pedidos — recargando...');
                    setTimeout(() => location.reload(), 1200);
                }
                updateIndicator('online');
            } catch (err) { updateIndicator('offline'); }
        }

        function updateIndicator(state) {
            const dot = document.getElementById('caja-poll-dot');
            const txt = document.getElementById('caja-poll-txt');
            if (!dot) return;
            dot.className = 'inline-block w-2 h-2 rounded-full ' + (state === 'online' ? 'bg-green-500 animate-pulse' : 'bg-red-500');
            txt.textContent = state === 'online' ? 'En vivo' : 'Sin conexión';
        }

        let cajaToastTimer;
        function showCajaToast(msg) {
            const t = document.getElementById('caja-toast');
            if (!t) return;
            t.querySelector('span').textContent = msg;
            t.classList.remove('hidden');
            clearTimeout(cajaToastTimer);
            cajaToastTimer = setTimeout(() => t.classList.add('hidden'), 3000);
        }

        setInterval(pollCaja, POLL_INTERVAL);
    </script>
    @endpush
</x-layouts.app>
