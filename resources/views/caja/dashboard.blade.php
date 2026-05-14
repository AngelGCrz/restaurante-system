<x-layouts.app :title="'Panel de Caja'">

<div x-data="cajaDashboard()" x-init="init()">

    {{-- ── TABS ────────────────────────────────────────────────────────────── --}}
    <div class="sticky top-0 z-40 bg-white dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700 flex items-center px-2 shadow-sm">

        <button @click="cambiarTab('cocina')"
            :class="tab==='cocina' ? 'border-b-2 border-orange-500 text-orange-600 dark:text-orange-400 font-semibold' : 'text-zinc-500 hover:text-zinc-700'"
            class="relative flex items-center gap-2 px-5 py-4 text-sm transition-colors">
            🍳 Cocina
            <span x-show="badgeCocina > 0" x-text="badgeCocina"
                class="absolute -top-0.5 right-0 bg-red-500 text-white text-xs font-bold rounded-full min-w-[18px] h-[18px] flex items-center justify-center px-1 animate-bounce">
            </span>
        </button>

        <button @click="cambiarTab('cobros')"
            :class="tab==='cobros' ? 'border-b-2 border-blue-500 text-blue-600 dark:text-blue-400 font-semibold' : 'text-zinc-500 hover:text-zinc-700'"
            class="flex items-center gap-2 px-5 py-4 text-sm transition-colors">
            💰 Cobros
        </button>

        <button @click="cambiarTab('historial')"
            :class="tab==='historial' ? 'border-b-2 border-purple-500 text-purple-600 dark:text-purple-400 font-semibold' : 'text-zinc-500 hover:text-zinc-700'"
            class="flex items-center gap-2 px-5 py-4 text-sm transition-colors">
            📋 Historial
        </button>

        <button @click="cambiarTab('caja')"
            :class="tab==='caja' ? 'border-b-2 border-green-500 text-green-600 dark:text-green-400 font-semibold' : 'text-zinc-500 hover:text-zinc-700'"
            class="flex items-center gap-2 px-5 py-4 text-sm transition-colors">
            🗃️ Caja
        </button>

        {{-- Indicador en vivo --}}
        <div class="ml-auto flex items-center gap-1.5 pr-4">
            <span :class="enLinea ? 'bg-green-500 animate-pulse' : 'bg-red-500'"
                class="inline-block w-2 h-2 rounded-full"></span>
            <span x-text="enLinea ? 'En vivo' : 'Sin conexión'" class="text-xs text-zinc-400"></span>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TAB COCINA
    ══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'cocina'" x-cloak class="flex h-full w-full flex-1 flex-col gap-4 p-4">

        <div x-show="badgeCocina > 0"
            class="flex items-center gap-3 rounded-xl bg-orange-50 border border-orange-300 dark:bg-orange-950 dark:border-orange-700 px-4 py-3">
            <span class="text-orange-500 text-xl">🔔</span>
            <span class="text-sm font-semibold text-orange-700 dark:text-orange-300"
                x-text="badgeCocina + ' pedido(s) nuevo(s) por imprimir'"></span>
        </div>

        <div id="orders-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse($pedidosCocina as $order)
            <div class="order-card rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 overflow-hidden"
                 data-order-id="{{ $order->id }}">

                <div class="flex justify-between items-center px-4 py-3 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <span class="font-bold dark:text-white">Orden #{{ $order->id }}</span>
                        <span class="text-xs text-zinc-400 ml-2">{{ $order->created_at->format('H:i') }}</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-600 dark:text-zinc-300">{{ $order->table_label }}</span>
                        @if($order->origin_order_id)
                            <span class="text-xs bg-red-600 text-yellow-300 rounded-full px-2 py-0.5 font-semibold">+Adicional</span>
                        @endif
                    </div>
                </div>

                <div class="px-4 py-3 space-y-2">
                    @if($order->origin_order_id)
                    <div class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-yellow-300">
                        ➕ Agregado a la orden #{{ $order->origin_order_id }}
                    </div>
                    @endif
                    @if($order->comment)
                    <div class="rounded-lg bg-blue-50 dark:bg-blue-950 border border-blue-200 dark:border-blue-800 px-3 py-2 text-xs text-blue-700 dark:text-blue-300 italic">
                        🗒️ {{ $order->comment }}
                    </div>
                    @endif
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($order->items as $item)
                        <li class="py-2 flex gap-3">
                            <span class="font-bold text-orange-500 w-6 text-right shrink-0">{{ $item->quantity }}x</span>
                            <div>
                                <span class="text-sm dark:text-white">{{ $item->product->name ?? '?' }}</span>
                                @if($item->comment)
                                    <p class="text-xs text-zinc-400 italic mt-0.5">📝 {{ $item->comment }}</p>
                                @endif
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>

                <div class="px-4 pb-4">
                    <button onclick="imprimirPedido({{ $order->id }}, this)"
                        class="btn-imprimir w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 rounded-lg transition flex items-center justify-center gap-2">
                        🖨️ Imprimir ticket
                    </button>
                </div>
            </div>
            @empty
            <div class="col-span-3 flex flex-col items-center justify-center py-20 text-zinc-400">
                <span class="text-5xl mb-4">✅</span>
                <p class="text-sm">No hay pedidos pendientes por imprimir</p>
            </div>
            @endforelse
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TAB COBROS
    ══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'cobros'" x-cloak class="flex h-full w-full flex-1 flex-col gap-4 p-4">

        @if(session('success'))
        <div class="rounded-xl bg-green-100 border border-green-300 text-green-800 p-4 dark:bg-green-900 dark:border-green-700 dark:text-green-200">
            {{ session('success') }}
        </div>
        @endif

        @if($ordersByTable->isEmpty())
        <div class="rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 p-8 text-center text-zinc-400">
            <div class="text-4xl mb-3">🕐</div>
            <p class="text-sm">No hay pedidos impresos pendientes de cobro.</p>
            <p class="text-xs mt-1 text-zinc-300">Los pedidos aparecen aquí luego de imprimirse en cocina.</p>
        </div>
        @endif

        @foreach($ordersByTable as $session)
            @php
                $tableKey     = $session['tableKey'];
                $tableOrders  = $session['orders'];
                $isCurrent    = $session['isCurrent'];
                $hasPending   = $tableOrders->where('status', 'pendiente')->count() > 0;
                $allCancelled = $tableOrders->every(fn($o) => $o->status === 'cancelado');

                // ── Total correcto: suma de TODOS los items de TODOS los pedidos pendientes de la mesa ──
                $tableTotal = $tableOrders->where('status', 'pendiente')->reduce(function($carry, $order) {
                    return $carry + $order->items->sum(fn($it) => $it->price * $it->quantity);
                }, 0);
            @endphp

            <div class="rounded-xl border bg-white dark:bg-zinc-800 p-6 mb-4
                @if($hasPending) border-yellow-400 dark:border-yellow-600 border-l-4 border-l-yellow-500
                @else border-zinc-200 dark:border-zinc-700 @if(!$isCurrent) opacity-60 @endif
                @endif">

                {{-- Header mesa --}}
                <div class="flex items-start justify-between mb-4 pb-3 border-b border-zinc-200 dark:border-zinc-700">
                    <div>
                        <div class="flex items-center gap-3 flex-wrap">
                            <h2 class="text-xl font-bold dark:text-white">
                                @if($tableKey === 'llevar') 🥡 Para Llevar
                                @elseif($tableKey === 'reserva') 📋 Reservas
                                @elseif($tableKey === 'personal') 👤 Personal
                                @else 🪑 Mesa {{ $tableKey }}
                                @endif
                            </h2>
                            @if($hasPending)
                                <span class="text-xs bg-yellow-100 text-yellow-700 dark:bg-yellow-900 dark:text-yellow-300 px-2 py-1 rounded-full font-semibold">🟡 Activa</span>
                            @elseif($allCancelled)
                                <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full font-semibold">🚫 Anulado</span>
                            @elseif(!$isCurrent)
                                <span class="text-xs bg-zinc-100 text-zinc-500 px-2 py-1 rounded-full">Histórico</span>
                            @else
                                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full font-semibold">✅ Cobrado</span>
                            @endif
                        </div>
                        <p class="text-sm text-gray-500 mt-1">
                            {{ $tableOrders->count() }} pedido(s) — Pendientes: <span class="font-semibold">{{ $tableOrders->where('status', 'pendiente')->count() }}</span>
                        </p>
                    </div>

                    @if($hasPending)
                    <div class="text-right shrink-0 ml-4">
                        <p class="text-xs text-zinc-400 mb-1">Total a cobrar</p>
                        <p class="text-2xl font-bold text-yellow-500">S/ {{ number_format($tableTotal, 2) }}</p>
                        <button onclick="openPayTableModal('{{ $tableKey }}', {{ $tableTotal }})"
                            class="mt-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-semibold text-sm transition">
                            💳 Cobrar Mesa
                        </button>
                    </div>
                    @endif
                </div>

                {{-- Pedidos de esta mesa --}}
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500 uppercase">
                            <th class="pb-2 font-semibold">ID</th>
                            <th class="pb-2 font-semibold">Cliente</th>
                            <th class="pb-2 font-semibold">Subtotal</th>
                            <th class="pb-2 font-semibold">Estado</th>
                            <th class="pb-2 font-semibold">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($tableOrders->sortBy('created_at') as $order)
                        @php
                            // Subtotal real de este pedido calculado desde sus items
                            $subtotal = $order->items->sum(fn($it) => $it->price * $it->quantity);
                        @endphp
                        <tr class="{{ $order->status !== 'pendiente' ? 'opacity-40' : '' }}">
                            <td class="py-2 dark:text-white">
                                #{{ $order->id }}
                                @if($order->origin_order_id)
                                    <span class="text-xs text-yellow-600">+</span>
                                @endif
                            </td>
                            <td class="py-2 dark:text-white">
                                {{ $order->customer_name ?? 'N/A' }}
                                @if($order->type === 'llevar' && !empty($order->table_numbers))
                                    <span class="text-xs bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-full ml-1">🥡</span>
                                @elseif($order->type === 'reserva')
                                    <span class="text-xs bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full ml-1">📋</span>
                                @elseif($order->type === 'personal')
                                    <span class="text-xs bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full ml-1">👤 $0</span>
                                @endif
                            </td>
                            <td class="py-2 font-semibold dark:text-white">S/ {{ number_format($subtotal, 2) }}</td>
                            <td class="py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs
                                    {{ $order->status === 'pagado'    ? 'bg-green-100 text-green-700' :
                                      ($order->status === 'pendiente' ? 'bg-yellow-100 text-yellow-700' :
                                                                        'bg-red-100 text-red-700') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="py-2 flex items-center gap-2">
                                <a href="{{ route('orders.show', $order) }}"
                                    class="text-xs bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 dark:text-white px-2 py-1 rounded-lg transition">
                                    Ver detalle
                                </a>
                                @if($order->status === 'pendiente')
                                <button type="button"
                                    onclick="openPayOrderModal({{ $order->id }}, {{ $subtotal }})"
                                    class="text-xs bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded-lg transition font-semibold">
                                    💳 Cobrar
                                </button>
                                <button type="button"
                                    onclick="openCancelModal({{ $order->id }})"
                                    class="text-xs bg-red-100 hover:bg-red-200 text-red-700 px-2 py-1 rounded-lg transition">
                                    Cancelar
                                </button>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TAB HISTORIAL
    ══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'historial'" x-cloak class="flex h-full w-full flex-1 flex-col gap-4 p-4">

        {{-- Resumen del día --}}
        <div class="grid grid-cols-2 md:grid-cols-2 gap-4">
            <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                <p class="text-xs text-green-600 font-semibold uppercase tracking-wide">Cobrados hoy</p>
                <p class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">{{ $countPagado }}</p>
                <p class="text-sm text-green-600 mt-0.5">S/ {{ number_format($totalPagado, 2) }}</p>
            </div>
            <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <p class="text-xs text-red-500 font-semibold uppercase tracking-wide">Cancelados hoy</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-300 mt-1">{{ $countCancelado }}</p>
            </div>
            <!-- <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-xs text-zinc-500 font-semibold uppercase tracking-wide">Ingreso neto</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">S/ {{ number_format($totalPagado, 2) }}</p>
            </div> -->
        </div>

        {{-- Tabla historial --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">
            @if($historial->isEmpty())
            <div class="p-8 text-center text-zinc-400 text-sm">No hay movimientos hoy.</div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700 text-xs text-zinc-500 uppercase tracking-wide">
                        <tr>
                            <th class="px-4 py-3 font-semibold">ID</th>
                            <th class="px-4 py-3 font-semibold">Hora</th>
                            <th class="px-4 py-3 font-semibold">Cliente</th>
                            <th class="px-4 py-3 font-semibold">Mesa</th>
                            <th class="px-4 py-3 font-semibold">Total</th>
                            <th class="px-4 py-3 font-semibold">Estado</th>
                            <th class="px-4 py-3 font-semibold">Pago</th>
                            <th class="px-4 py-3 font-semibold">Comprobante</th>
                            <th class="px-4 py-3 font-semibold">Detalle</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($historial as $order)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                            <td class="px-4 py-3 font-medium dark:text-white">#{{ $order->id }}</td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">{{ $order->updated_at->format('H:i') }}</td>
                            <td class="px-4 py-3 dark:text-white">{{ $order->customer_name ?? 'N/A' }}</td>
                            <td class="px-4 py-3 dark:text-white">
                                @if(!empty($order->table_numbers)) 🪑 {{ implode('+', $order->table_numbers) }}
                                @elseif($order->type === 'llevar') 🥡 Llevar
                                @elseif($order->type === 'reserva') 📋 Reserva
                                @elseif($order->type === 'personal') 👤 Personal
                                @else —
                                @endif
                            </td>
                            <td class="px-4 py-3 font-bold {{ $order->status === 'pagado' ? 'text-green-600 dark:text-green-400' : 'text-zinc-400 line-through' }}">
                                S/ {{ number_format($order->items->sum(fn($it) => $it->price * $it->quantity), 2) }}
                            </td>
                            <td class="px-4 py-3">
                                @if($order->status === 'pagado')
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs bg-green-100 text-green-700 font-semibold">✅ Cobrado</span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs bg-red-100 text-red-700 font-semibold">🚫 Cancelado</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-xs">
                                @php $pm = ['efectivo'=>'💵 Efectivo','yape'=>'📱 Yape','tarjeta'=>'💳 Tarjeta']; @endphp
                                {{ $pm[$order->payment_method] ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-xs">
                                @php $rt = ['ticket'=>'🧾 Ticket','boleta'=>'📄 Boleta','factura'=>'📋 Factura']; @endphp
                                {{ $rt[$order->receipt_type] ?? '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <a href="{{ route('orders.show', $order) }}"
                                    class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                    Ver
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════
         TAB CAJA
    ══════════════════════════════════════════════════════════════ --}}
    <div x-show="tab === 'caja'" x-cloak class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="max-w-lg mx-auto w-full space-y-4">

            {{-- Resumen ventas --}}
            <div class="grid grid-cols-2 gap-4">
                <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-4">
                    <p class="text-xs text-zinc-400 mb-1">Cobrados hoy</p>
                    <p class="text-xl font-bold dark:text-white">{{ $countPagado }} pedidos</p>
                </div>
                <div class="rounded-xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 p-4">
                    <p class="text-xs text-zinc-400 mb-1">Ventas hoy</p>
                    <p class="text-xl font-bold text-green-600 dark:text-green-400">S/ {{ number_format($ventasHoy, 2) }}</p>
                </div>
            </div>

            @if(session('caja_success'))
            <div class="rounded-xl bg-green-100 border border-green-300 text-green-800 p-4">{{ session('caja_success') }}</div>
            @endif

            @if($cajaAbierta)
            {{-- Caja abierta --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 space-y-5">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">🟢</span>
                    <div>
                        <p class="font-bold text-lg dark:text-white">Caja abierta</p>
                        <p class="text-xs text-zinc-400">Desde las {{ $cajaAbierta->opened_at->format('H:i') }} · {{ $cajaAbierta->opened_at->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="rounded-lg bg-zinc-50 dark:bg-zinc-900 p-4">
                        <p class="text-xs text-zinc-400 mb-1">Monto inicial</p>
                        <p class="text-xl font-bold dark:text-white">S/ {{ number_format($cajaAbierta->opening_balance, 2) }}</p>
                    </div>
                    <div class="rounded-lg bg-green-50 dark:bg-green-950 p-4">
                        <p class="text-xs text-zinc-400 mb-1">Ventas cobradas</p>
                        <p class="text-xl font-bold text-green-600 dark:text-green-400">S/ {{ number_format($ventasHoy, 2) }}</p>
                    </div>
                </div>
                <form id="closeCajaForm" method="POST" action="{{ route('cash.close') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Monto final en caja (conteo físico)</label>
                        <input type="number" step="0.01" name="closing_balance" required placeholder="0.00"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Observaciones (opcional)</label>
                        <input type="text" name="notes" placeholder="Ej: faltante de S/5.00"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <button type="button" onclick="openCloseCajaModal()"
                        class="w-full bg-red-500 hover:bg-red-600 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                        Cerrar caja del día
                    </button>
                </form>
            </div>
            @else
            {{-- Caja cerrada --}}
            <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-6 space-y-5">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">🔴</span>
                    <div>
                        <p class="font-bold text-lg dark:text-white">Caja cerrada</p>
                        <p class="text-xs text-zinc-400">Abre la caja para comenzar a registrar cobros</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('cash.open') }}" class="space-y-3">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Monto inicial en caja</label>
                        <input type="number" step="0.01" name="opening_balance" required placeholder="0.00"
                            class="w-full rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500">
                    </div>
                    <button type="submit"
                        class="w-full bg-green-500 hover:bg-green-600 text-white font-semibold py-2.5 rounded-lg text-sm transition">
                        Abrir caja
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>

</div>


{{-- ── MODAL CANCELAR PEDIDO ─────────────────────────────────────────────── --}}
<div id="cancelOrderModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 max-w-sm w-full shadow-2xl">
        <h2 class="text-xl font-bold mb-2 dark:text-white">🚫 Cancelar Pedido</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
            ¿Confirmas la cancelación del <span id="cancel_order_label" class="font-semibold text-zinc-700 dark:text-zinc-200"></span>?
            Esta acción no se puede deshacer.
        </p>
        <form id="cancelOrderForm" method="POST">
            @csrf
            <div class="mb-5">
                <label class="block text-sm font-semibold mb-2 dark:text-white">Motivo de cancelación <span class="text-red-500">*</span></label>
                <textarea id="cancel_reason_input" name="cancel_reason" rows="3" required
                    placeholder="Ej: cliente se retiró, pedido duplicado..."
                    class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg p-2.5 text-sm dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-red-500 resize-none"></textarea>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closeCancelModal()"
                    class="flex-1 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 dark:text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition">
                    Volver
                </button>
                <button type="submit"
                    class="flex-1 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition">
                    Sí, cancelar
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL COBRAR ORDEN INDIVIDUAL ────────────────────────────────────── --}}
<div id="payOrderModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 max-w-md w-full shadow-2xl">
        <h2 class="text-2xl font-bold mb-5 dark:text-white">💳 Cobrar Orden</h2>
        <form id="payOrderForm" method="POST">
            @csrf
            <div class="mb-5 rounded-xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 p-4 text-center">
                <p class="text-xs text-zinc-500 mb-1">Orden <span id="pay_order_label" class="font-semibold"></span> — Total a cobrar</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400" id="pay_order_total"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2 dark:text-white">Método de Pago</label>
                <select name="payment_method" required
                    class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg p-2.5 text-sm dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="efectivo">💵 Efectivo</option>
                    <option value="yape">📱 Yape</option>
                    <option value="tarjeta">💳 Tarjeta</option>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2 dark:text-white">Tipo de Comprobante</label>
                <select name="receipt_type" required
                    class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg p-2.5 text-sm dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="ticket">🧾 Ticket</option>
                    <option value="boleta">📄 Boleta</option>
                    <option value="factura">📋 Factura</option>
                </select>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closePayOrderModal()"
                    class="flex-1 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 dark:text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition">
                    Confirmar Cobro
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL COBRAR MESA ─────────────────────────────────────────────────── --}}
<div id="payTableModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 max-w-md w-full shadow-2xl">
        <h2 class="text-2xl font-bold mb-5 dark:text-white">💳 Cobrar Mesa</h2>
        <form action="{{ route('orders.pay-table') }}" method="POST">
            @csrf
            <input type="hidden" name="table_key" id="modal_table_key">
            <div class="mb-5 rounded-xl bg-green-50 dark:bg-green-950 border border-green-200 dark:border-green-800 p-4 text-center">
                <p class="text-xs text-zinc-500 mb-1">Total a cobrar</p>
                <p class="text-3xl font-bold text-green-600 dark:text-green-400" id="modal_total"></p>
            </div>
            <div class="mb-4">
                <label class="block text-sm font-semibold mb-2 dark:text-white">Método de Pago</label>
                <select name="payment_method" required
                    class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg p-2.5 text-sm dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="efectivo">💵 Efectivo</option>
                    <option value="yape">📱 Yape</option>
                    <option value="tarjeta">💳 Tarjeta</option>
                </select>
            </div>
            <div class="mb-6">
                <label class="block text-sm font-semibold mb-2 dark:text-white">Tipo de Comprobante</label>
                <select name="receipt_type" required
                    class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg p-2.5 text-sm dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="ticket">🧾 Ticket</option>
                    <option value="boleta">📄 Boleta</option>
                    <option value="factura">📋 Factura</option>
                </select>
            </div>
            <div class="flex gap-3">
                <button type="button" onclick="closePayTableModal()"
                    class="flex-1 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 dark:text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition">
                    Cancelar
                </button>
                <button type="submit"
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition">
                    Confirmar Cobro
                </button>
            </div>
        </form>
    </div>
</div>

{{-- ── MODAL CERRAR CAJA ───────────────────────────────────────────────────── --}}
<div id="closeCajaModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl p-8 max-w-sm w-full shadow-2xl">
        <h2 class="text-xl font-bold mb-2 dark:text-white">🗃️ Cerrar caja del día</h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">
            ¿Cerrar la caja del día?
        </p>
        <div class="flex gap-3">
            <button type="button" onclick="closeCloseCajaModal()"
                class="flex-1 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 dark:text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition">
                Volver
            </button>
            <button type="button" onclick="document.getElementById('closeCajaForm').submit()"
                class="flex-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2.5 rounded-lg font-semibold text-sm transition">
                Sí, cerrar caja
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
// ── Alpine ────────────────────────────────────────────────────────────────────
function cajaDashboard() {
    return {
        tab: 'cocina',
        badgeCocina: 0,
        enLinea: true,
        hashCocina: '{{ $hashCocina }}',

        init() {
            const tabPendiente = sessionStorage.getItem('caja_tab_pendiente');
            if (tabPendiente) {
                this.tab = tabPendiente;
                sessionStorage.removeItem('caja_tab_pendiente');
            }
            this.ocultarImpresasAlCargar();
            // Auto-imprimir pedidos que ya están visibles al cargar la página
            document.querySelectorAll('.order-card').forEach(card => {
                if (card.style.display !== 'none') {
                    scheduleAutoPrint(card.dataset.orderId);
                }
            });
            setInterval(() => this.pollCocina(), 5000);
        },

        cambiarTab(nuevoTab) {
            this.tab = nuevoTab;
            if (nuevoTab === 'cocina') this.badgeCocina = 0;
        },

        // localStorage para recordar impresas entre refreshes
        getImpresas() {
            return JSON.parse(localStorage.getItem('caja_printed_orders') || '[]').map(String);
        },

        ocultarImpresasAlCargar() {
            this.getImpresas().forEach(id => {
                const card = document.querySelector(`.order-card[data-order-id="${id}"]`);
                if (card) card.style.display = 'none';
            });
        },

        async pollCocina() {
            try {
                const res  = await fetch('{{ route("kitchen.poll") }}', { credentials: 'same-origin' });
                if (!res.ok) throw new Error();
                const data = await res.json();
                this.enLinea = true;

                if (data.hash !== this.hashCocina) {
                    this.hashCocina = data.hash;
                    this.renderCocina(data.orders);
                    if (this.tab !== 'cocina' && data.orders.length > 0) {
                        this.badgeCocina = data.orders.length;
                        this.sonar();
                    }
                }
            } catch {
                this.enLinea = false;
            }
        },

        sonar() {
            try {
                const ctx  = new (window.AudioContext || window.webkitAudioContext)();
                const osc  = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain); gain.connect(ctx.destination);
                osc.frequency.value = 880;
                gain.gain.setValueAtTime(0.3, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.4);
                osc.start(ctx.currentTime); osc.stop(ctx.currentTime + 0.4);
            } catch(e) {}
        },

        renderCocina(orders) {
            const grid = document.getElementById('orders-grid');
            if (!grid) return;

            const impresas    = this.getImpresas();
            const idsEnDom    = new Set([...grid.querySelectorAll('.order-card')].map(c => c.dataset.orderId));
            const idsServidor = new Set(orders.map(o => String(o.id)));

            // Agregar nuevas (saltando impresas)
            orders.forEach(order => {
                const id = String(order.id);
                if (impresas.includes(id)) return;
                if (!idsEnDom.has(id)) {
                    const card = buildCard(order);
                    grid.prepend(card);
                    requestAnimationFrame(() => { card.style.opacity = '1'; card.style.transform = 'translateY(0)'; });
                    scheduleAutoPrint(order.id);
                }
            });

            // Quitar las que ya no están pendientes en servidor
            idsEnDom.forEach(id => {
                if (!idsServidor.has(id)) {
                    const card = grid.querySelector(`.order-card[data-order-id="${id}"]`);
                    if (card && card.style.display !== 'none') {
                        card.style.transition = 'opacity 0.4s';
                        card.style.opacity = '0';
                        setTimeout(() => card.remove(), 400);
                    }
                }
            });

            // Vacío
            if (orders.filter(o => !impresas.includes(String(o.id))).length === 0
                && grid.querySelectorAll('.order-card:not([style*="display: none"])').length === 0) {
                grid.innerHTML = `<div class="col-span-3 flex flex-col items-center justify-center py-20 text-zinc-400">
                    <span class="text-5xl mb-4">✅</span><p class="text-sm">No hay pedidos pendientes por imprimir</p></div>`;
            }
        }
    }
}

// ── Tarjeta dinámica ──────────────────────────────────────────────────────────
function buildCard(order) {
    const div = document.createElement('div');
    div.className = 'order-card rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800 overflow-hidden ring-2 ring-green-400';
    div.dataset.orderId = order.id;
    div.style.cssText = 'opacity:0;transform:translateY(-10px);transition:opacity 0.4s,transform 0.4s';

    const origin   = order.origin_order_id ? `<div class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-yellow-300">➕ Agregado a la orden #${order.origin_order_id}</div>` : '';
    const comentario = order.comment ? `<div class="rounded-lg bg-blue-50 border border-blue-200 px-3 py-2 text-xs text-blue-700 italic">🗒️ ${esc(order.comment)}</div>` : '';
    const items    = order.items.map(i => `
        <li class="py-2 flex gap-3">
            <span class="font-bold text-orange-500 w-6 text-right shrink-0">${i.quantity}x</span>
            <div><span class="text-sm">${esc(i.product_name)}</span>
            ${i.comment ? `<p class="text-xs text-zinc-400 italic mt-0.5">📝 ${esc(i.comment)}</p>` : ''}</div>
        </li>`).join('');

    div.innerHTML = `
        <div class="flex justify-between items-center px-4 py-3 bg-zinc-50 dark:bg-zinc-900 border-b border-zinc-200 dark:border-zinc-700">
            <div><span class="font-bold">Orden #${order.id}</span><span class="text-xs text-zinc-400 ml-2">${order.created_at}</span></div>
            <span class="text-sm text-zinc-600">${esc(order.table_label)}</span>
        </div>
        <div class="px-4 py-3 space-y-2">${origin}${comentario}<ul class="divide-y divide-zinc-100">${items}</ul></div>
        <div class="px-4 pb-4">
            <button onclick="imprimirPedido(${order.id}, this)"
                class="btn-imprimir w-full bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold py-2 rounded-lg transition flex items-center justify-center gap-2">
                🖨️ Imprimir ticket
            </button>
        </div>`;

    setTimeout(() => div.classList.remove('ring-2', 'ring-green-400'), 3000);
    return div;
}

function esc(s) {
    return s ? String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;') : '';
}

// ── Auto-imprimir con cuenta regresiva ──────────────────────────────────────
function scheduleAutoPrint(orderId) {
    let secsLeft = 3;

    function getBtn() {
        const card = document.querySelector(`.order-card[data-order-id="${orderId}"]`);
        if (!card || card.style.display === 'none') return null;
        return card.querySelector('.btn-imprimir');
    }

    function tick() {
        const btn = getBtn();
        if (!btn || btn.disabled) return; // ya imprimiendo o desaparecida
        btn.innerHTML = ` Imprimiendo en ${secsLeft}s...`;
    }

    tick(); // mostrar inmediatamente

    const iv = setInterval(() => {
        secsLeft--;
        if (secsLeft <= 0) {
            clearInterval(iv);
            const btn = getBtn();
            if (btn && !btn.disabled) {
                imprimirPedido(orderId, btn);
            }
        } else {
            tick();
        }
    }, 1000);
}

// ── Imprimir ──────────────────────────────────────────────────────────────────
async function imprimirPedido(orderId, btn) {
    btn.disabled = true;
    btn.innerHTML = ' Enviando...';

    try {
        // 1. Obtener datos del pedido
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
        const res  = await fetch(`/kitchen/${orderId}/print`, {
            method: 'POST', credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({})
        });
        if (!res.ok) throw new Error('Error al obtener pedido del servidor');

        const { order } = await res.json();

        // 2. Marcar como impresa en el servidor SIEMPRE (independiente de la impresora)
        try {
            await fetch(`/kitchen/${orderId}/mark-printed`, {
                method: 'POST', credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrf }
            });
        } catch(e) {}

        // 3. Guardar en localStorage (persiste en refresh)
        marcarImpresaLocal(orderId);

        // 4. Intentar enviar a impresora física (no bloquea el flujo si falla)
        const pedidobody = {
            order_id:            order.id,
            order_created_at:    order.created_at,
            order_customer_name: order.customer_name,
            order_table_label:   order.table_label,
            order_comment:       order.comment,
            items: order.items.map(i => ({
                item_quantity:        i.quantity,
                item_product_name:    i.product.name,
                item_product_comment: i.comment
            }))
        };

        try {
            const socket = new WebSocket('ws://localhost:3000');
            socket.onopen = () => {
                socket.send(JSON.stringify({ action: 'print-ticket', pedido: pedidobody }));
            };
            socket.onmessage = e => console.log('Respuesta impresora:', JSON.parse(e.data));
        } catch(e) {
            console.warn('[Impresora]', e.message);
        }

        // 5. Ocultar tarjeta e ir a Cobros
        const card = btn.closest('.order-card');
        if (card) { card.style.transition = 'opacity 0.4s'; card.style.opacity = '0'; }
        sessionStorage.setItem('caja_tab_pendiente', 'cobros');
        setTimeout(() => location.reload(), 800);

    } catch (err) {
        btnError(btn);
        console.error('[Imprimir]', err.message);
    }
}

function marcarImpresaLocal(id) {
    const arr = JSON.parse(localStorage.getItem('caja_printed_orders') || '[]');
    if (!arr.includes(String(id))) { arr.push(String(id)); localStorage.setItem('caja_printed_orders', JSON.stringify(arr)); }
}

function btnError(btn) {
    btn.disabled = false;
    btn.innerHTML = '⚠️ Falló — Reintentar';
    btn.classList.remove('bg-blue-600','hover:bg-blue-700');
    btn.classList.add('bg-red-500','hover:bg-red-600');
}

// ── Modal cobrar mesa ─────────────────────────────────────────────────────────

function openCancelModal(orderId) {
    document.getElementById('cancel_order_label').textContent = 'Pedido #' + orderId;
    document.getElementById('cancelOrderForm').action = '/orders/' + orderId + '/cancel';
    document.getElementById('cancel_reason_input').value = '';
    document.getElementById('cancelOrderModal').classList.remove('hidden');
}
function closeCancelModal() {
    document.getElementById('cancelOrderModal').classList.add('hidden');
}
document.getElementById('cancelOrderModal').addEventListener('click', function(e) {
    if (e.target === this) closeCancelModal();
});


function openPayOrderModal(orderId, subtotal) {
    document.getElementById('pay_order_label').textContent = '#' + orderId;
    document.getElementById('pay_order_total').textContent = 'S/ ' + parseFloat(subtotal).toFixed(2);
    document.getElementById('payOrderForm').action = '/orders/' + orderId + '/pay';
    document.getElementById('payOrderModal').classList.remove('hidden');
}
function closePayOrderModal() {
    document.getElementById('payOrderModal').classList.add('hidden');
}
document.getElementById('payOrderModal').addEventListener('click', function(e) {
    if (e.target === this) closePayOrderModal();
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
function openCloseCajaModal() {
    document.getElementById('closeCajaModal').classList.remove('hidden');
}
function closeCloseCajaModal() {
    document.getElementById('closeCajaModal').classList.add('hidden');
}
document.getElementById('closeCajaModal').addEventListener('click', function(e) {
    if (e.target === this) closeCloseCajaModal();
});
</script>
@endpush

</x-layouts.app>