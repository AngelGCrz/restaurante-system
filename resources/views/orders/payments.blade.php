<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">

        {{-- Header --}}
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold dark:text-white">Lista de Pagos</h1>
        </div>

        {{-- Resumen de tarjetas --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 p-4">
                <p class="text-xs text-green-600 dark:text-green-400 font-semibold uppercase tracking-wide">Cobrados</p>
                <p class="text-2xl font-bold text-green-700 dark:text-green-300 mt-1">{{ $countPagado }}</p>
                <p class="text-sm text-green-600 dark:text-green-400 mt-0.5">S/ {{ number_format($totalPagado, 2) }}</p>
            </div>
            <div class="rounded-xl bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 p-4">
                <p class="text-xs text-red-500 dark:text-red-400 font-semibold uppercase tracking-wide">Cancelados</p>
                <p class="text-2xl font-bold text-red-600 dark:text-red-300 mt-1">{{ $countCancelado }}</p>
                <p class="text-sm text-red-500 dark:text-red-400 mt-0.5">{{ $countCancelado }} pedido(s)</p>
            </div>
            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-xs text-zinc-500 font-semibold uppercase tracking-wide">Total registros</p>
                <p class="text-2xl font-bold dark:text-white mt-1">{{ $countPagado + $countCancelado }}</p>
                <p class="text-sm text-zinc-500 mt-0.5">pedidos cerrados</p>
            </div>
            <div class="rounded-xl bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 p-4">
                <p class="text-xs text-zinc-500 font-semibold uppercase tracking-wide">Ingreso neto</p>
                <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">S/ {{ number_format($totalPagado, 2) }}</p>
                <p class="text-sm text-zinc-500 mt-0.5">solo cobrados</p>
            </div>
        </div>

        {{-- Filtros --}}
        <form method="GET" action="{{ route('orders.payments') }}"
              class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 items-end">

                {{-- Estado --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-1 uppercase tracking-wide">Estado</label>
                    <select name="status"
                        class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="all"      {{ $status === 'all'      ? 'selected' : '' }}>Todos</option>
                        <option value="pagado"   {{ $status === 'pagado'   ? 'selected' : '' }}>✅ Cobrados</option>
                        <option value="cancelado"{{ $status === 'cancelado'? 'selected' : '' }}>🚫 Cancelados</option>
                    </select>
                </div>

                {{-- Desde --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-1 uppercase tracking-wide">Desde</label>
                    <input type="date" name="from" value="{{ $from }}"
                        class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                {{-- Hasta --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-1 uppercase tracking-wide">Hasta</label>
                    <input type="date" name="to" value="{{ $to }}"
                        class="w-full border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>

                {{-- Buscar --}}
                <div>
                    <label class="block text-xs font-semibold text-zinc-500 dark:text-zinc-400 mb-1 uppercase tracking-wide">Buscar cliente / ID</label>
                    <div class="flex gap-2">
                        <input type="text" name="search" value="{{ $search }}" placeholder="Nombre o #ID"
                            class="flex-1 border border-zinc-300 dark:border-zinc-600 rounded-lg px-3 py-2 text-sm dark:bg-zinc-700 dark:text-white focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition">
                            Filtrar
                        </button>
                        @if($status !== 'all' || $from || $to || $search)
                            <a href="{{ route('orders.payments') }}"
                               class="bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 dark:text-white px-3 py-2 rounded-lg text-sm font-semibold transition">
                                ✕
                            </a>
                        @endif
                    </div>
                </div>

            </div>
        </form>

        {{-- Tabla de resultados --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 overflow-hidden">

            @if($orders->isEmpty())
                <div class="p-8 text-center text-zinc-400">
                    No hay pedidos que coincidan con los filtros seleccionados.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
                            <tr class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">
                                <th class="px-4 py-3 font-semibold">ID</th>
                                <th class="px-4 py-3 font-semibold">Fecha</th>
                                <th class="px-4 py-3 font-semibold">Cliente</th>
                                <th class="px-4 py-3 font-semibold">Mesa</th>
                                <th class="px-4 py-3 font-semibold">Total</th>
                                <th class="px-4 py-3 font-semibold">Estado</th>
                                <th class="px-4 py-3 font-semibold">Método</th>
                                <th class="px-4 py-3 font-semibold">Comprobante</th>
                                <th class="px-4 py-3 font-semibold">Motivo / Nota</th>
                                <th class="px-4 py-3 font-semibold">Acción</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            @foreach($orders as $order)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                                    <td class="px-4 py-3 font-medium dark:text-white">#{{ $order->id }}</td>
                                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 whitespace-nowrap">
                                        {{ $order->updated_at->format('d/m/Y') }}<br>
                                        <span class="text-xs">{{ $order->updated_at->format('H:i') }}</span>
                                    </td>
                                    <td class="px-4 py-3 dark:text-white">{{ $order->customer_name ?? 'N/A' }}</td>
                                    <td class="px-4 py-3 dark:text-white">
                                        @if(!empty($order->table_numbers))
                                            🪑 {{ implode('+', $order->table_numbers) }}
                                        @elseif($order->type === 'llevar')
                                            🥡 Llevar
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 font-bold
                                        {{ $order->status === 'pagado' ? 'text-green-600 dark:text-green-400' : 'text-zinc-400 line-through' }}">
                                        S/ {{ number_format($order->total, 2) }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @if($order->status === 'pagado')
                                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs bg-green-100 text-green-700 dark:bg-green-900 dark:text-green-300 font-semibold">
                                                ✅ Cobrado
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs bg-red-100 text-red-700 dark:bg-red-900 dark:text-red-300 font-semibold">
                                                🚫 Cancelado
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">
                                        @if($order->payment_method)
                                            @php $pm = ['efectivo'=>'💵 Efectivo','yape'=>'📱 Yape','tarjeta'=>'💳 Tarjeta']; @endphp
                                            {{ $pm[$order->payment_method] ?? $order->payment_method }}
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400">
                                        @if($order->receipt_type)
                                            @php $rt = ['ticket'=>'🧾 Ticket','boleta'=>'📄 Boleta','factura'=>'📋 Factura']; @endphp
                                            {{ $rt[$order->receipt_type] ?? $order->receipt_type }}
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3 max-w-xs">
                                        @if($order->status === 'cancelado' && !empty($order->cancel_reason))
                                            <span class="flex items-start gap-1 text-xs text-red-500 dark:text-red-400 italic">
                                                <span class="shrink-0">🚫</span>{{ $order->cancel_reason }}
                                            </span>
                                        @elseif(!empty($order->comment))
                                            <span class="flex items-start gap-1 text-xs text-zinc-500 dark:text-zinc-400 italic">
                                                <span class="shrink-0">📝</span>{{ $order->comment }}
                                            </span>
                                        @else
                                            <span class="text-zinc-300 dark:text-zinc-600 text-xs">—</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-3">
                                        <a href="{{ route('orders.show', $order) }}"
                                           class="text-xs text-blue-600 dark:text-blue-400 hover:underline font-medium">
                                            Ver detalle
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                {{-- Paginación --}}
                @if($orders->hasPages())
                    <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700">
                        {{ $orders->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-layouts.app>
