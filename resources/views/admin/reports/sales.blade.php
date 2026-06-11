<x-layouts.app>
<div class="flex h-full w-full flex-1 flex-col gap-4 p-3 sm:p-4">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 sm:text-2xl">Reporte de Ventas</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ $startDate->format('d/m/Y') }} — {{ $endDate->format('d/m/Y') }}
            </p>
        </div>
        <flux:button href="{{ route('admin.reports.index') }}" variant="ghost" icon="arrow-left">Volver</flux:button>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-4">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Desde</label>
                <input type="date" name="start" value="{{ $startDate->format('Y-m-d') }}"
                    class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Hasta</label>
                <input type="date" name="end" value="{{ $endDate->format('Y-m-d') }}"
                    class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Estado</label>
                <select name="status" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">Todos (sin cancelados)</option>
                    <option value="pendiente" {{ request('status') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="pagado"    {{ request('status') === 'pagado'    ? 'selected' : '' }}>Cobrado</option>
                    <option value="all"       {{ request('status') === 'all'       ? 'selected' : '' }}>Incluir cancelados</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium text-zinc-600 dark:text-zinc-400">Mozo</label>
                <select name="user_id" class="w-full rounded border border-zinc-300 bg-white px-2 py-1.5 text-sm dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100">
                    <option value="">Todos los mozos</option>
                    @foreach($mozos as $mozo)
                        <option value="{{ $mozo->id }}" {{ request('user_id') == $mozo->id ? 'selected' : '' }}>{{ $mozo->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="mt-3 flex flex-wrap gap-2">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700">Filtrar</button>
            <button type="submit" name="export" value="1" class="rounded-lg bg-zinc-700 px-4 py-1.5 text-sm font-medium text-white hover:bg-zinc-800">Exportar CSV</button>
            <a href="{{ route('admin.reports.sales') }}" class="rounded-lg border border-zinc-300 px-4 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">Limpiar</a>
        </div>
    </form>

    {{-- Tarjetas de resumen --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
        @php
            $cards = [
                ['label' => 'Total pedidos',   'value' => $totals->orders_count ?? 0,                               'color' => 'text-zinc-900 dark:text-zinc-100'],
                ['label' => 'Cobrados',        'value' => $totals->paid_count ?? 0,                                 'color' => 'text-green-600'],
                ['label' => 'Pendientes',      'value' => $totals->pending_count ?? 0,                              'color' => 'text-yellow-600'],
                ['label' => 'Cancelados',      'value' => $totals->cancelled_count ?? 0,                            'color' => 'text-red-600'],
                ['label' => 'Total ventas',    'value' => '$' . number_format($totals->total_sales ?? 0, 2),         'color' => 'text-blue-600'],
                ['label' => 'Ticket promedio', 'value' => '$' . number_format($totals->avg_ticket ?? 0, 2),         'color' => 'text-zinc-900 dark:text-zinc-100'],
            ];
        @endphp
        @foreach ($cards as $card)
            <div class="rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <div class="text-xs text-zinc-500 dark:text-zinc-400">{{ $card['label'] }}</div>
                <div class="mt-1 text-lg font-bold {{ $card['color'] }}">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    {{-- Resumen por día --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">Resumen por día</h2>
        </div>

        {{-- Mobile: cards --}}
        <div class="block space-y-2 p-3 lg:hidden">
            @forelse ($perDay as $row)
                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</span>
                        <span class="text-sm font-semibold text-blue-600">${{ number_format($row->total_sales ?? 0, 2) }}</span>
                    </div>
                    <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-zinc-500 dark:text-zinc-400">
                        <span>{{ $row->orders_count }} pedidos</span>
                        <span>Prom. ${{ number_format($row->avg_ticket ?? 0, 2) }}</span>
                        <span class="text-green-600">{{ $row->paid_count ?? 0 }} cobrados</span>
                        <span class="text-yellow-600">{{ $row->pending_count ?? 0 }} pend.</span>
                        @if(($row->cancelled_count ?? 0) > 0)
                            <span class="text-red-500">{{ $row->cancelled_count }} cancel. ({{ round($row->cancelled_pct ?? 0) }}%)</span>
                        @endif
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-zinc-400">No hay datos en el rango seleccionado.</p>
            @endforelse
        </div>

        {{-- Desktop: tabla --}}
        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-zinc-900 dark:text-zinc-100">
                <thead class="border-b border-zinc-100 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3 text-right">Pedidos</th>
                        <th class="px-4 py-3 text-right">Cobrados</th>
                        <th class="px-4 py-3 text-right">Pendientes</th>
                        <th class="px-4 py-3 text-right">Cancelados</th>
                        <th class="px-4 py-3 text-right">Total ventas</th>
                        <th class="px-4 py-3 text-right">Promedio</th>
                        <th class="px-4 py-3 text-right">% Cancelado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($perDay as $row)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ $row->orders_count }}</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ $row->paid_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-right text-yellow-600">{{ $row->pending_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-right text-red-500">{{ $row->cancelled_count ?? 0 }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-blue-600">${{ number_format($row->total_sales ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-right">${{ number_format($row->avg_ticket ?? 0, 2) }}</td>
                            <td class="px-4 py-3 text-right {{ ($row->cancelled_pct ?? 0) > 30 ? 'font-semibold text-red-600' : '' }}">
                                {{ round($row->cancelled_pct ?? 0, 1) }}%
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="px-4 py-8 text-center text-zinc-400">No hay datos en el rango seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Top productos --}}
    @if($productsSummary->isNotEmpty())
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">Productos más vendidos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-zinc-900 dark:text-zinc-100">
                <thead class="border-b border-zinc-100 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3 text-right">Cantidad</th>
                        <th class="px-4 py-3 text-right">Ingresos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($productsSummary as $i => $item)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-2 text-zinc-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-2 font-medium">{{ $item->product->name ?? 'Eliminado' }}</td>
                            <td class="px-4 py-2 text-right">{{ $item->total_quantity }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-blue-600">${{ number_format($item->total_sales, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- Detalle de pedidos --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">
                Pedidos del período
                <span class="ml-1 text-sm font-normal text-zinc-400">({{ $orders->count() }})</span>
            </h2>
        </div>

        {{-- Mobile: cards --}}
        <div class="block space-y-2 p-3 lg:hidden">
            @forelse ($orders as $order)
                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">#{{ $order->id }}</div>
                            <div class="text-xs text-zinc-500">{{ $order->created_at->format('d/m/Y H:i') }} — {{ $order->user->name ?? '—' }}</div>
                        </div>
                        <div class="text-right">
                            <div class="font-bold text-blue-600">${{ number_format($order->total, 2) }}</div>
                            <span @class([
                                'inline-block rounded px-1.5 py-0.5 text-xs font-medium capitalize',
                                'bg-green-100 text-green-700' => $order->status === 'pagado',
                                'bg-yellow-100 text-yellow-700' => $order->status === 'pendiente',
                                'bg-red-100 text-red-600' => $order->status === 'cancelado',
                            ])>{{ $order->status }}</span>
                        </div>
                    </div>
                    <button onclick="this.nextElementSibling.classList.toggle('hidden')"
                        class="mt-2 text-xs text-blue-500 hover:underline">Ver productos ▾</button>
                    <ul class="mt-2 hidden space-y-0.5 text-xs text-zinc-600 dark:text-zinc-400">
                        @foreach ($order->items as $item)
                            <li>• {{ $item->product->name ?? 'Eliminado' }} — {{ $item->quantity }} × ${{ number_format($item->price, 2) }} = <strong>${{ number_format($item->quantity * $item->price, 2) }}</strong></li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-zinc-400">No hay pedidos en este rango.</p>
            @endforelse
        </div>

        {{-- Desktop: tabla --}}
        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-zinc-900 dark:text-zinc-100">
                <thead class="border-b border-zinc-100 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Mozo</th>
                        <th class="px-4 py-3">Mesa</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($orders as $order)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-2 font-mono text-zinc-400">#{{ $order->id }}</td>
                            <td class="px-4 py-2">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $order->user->name ?? '—' }}</td>
                            <td class="px-4 py-2">{{ $order->table_label ?? '—' }}</td>
                            <td class="px-4 py-2">
                                <span @class([
                                    'inline-block rounded px-1.5 py-0.5 text-xs font-medium capitalize',
                                    'bg-green-100 text-green-700' => $order->status === 'pagado',
                                    'bg-yellow-100 text-yellow-700' => $order->status === 'pendiente',
                                    'bg-red-100 text-red-600' => $order->status === 'cancelado',
                                ])>{{ $order->status }}</span>
                            </td>
                            <td class="px-4 py-2 text-right font-semibold text-blue-600">${{ number_format($order->total, 2) }}</td>
                        </tr>
                        <tr class="bg-zinc-50/60 dark:bg-zinc-800/30">
                            <td colspan="6" class="px-6 py-1.5">
                                <ul class="flex flex-wrap gap-x-4 gap-y-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    @foreach ($order->items as $item)
                                        <li>{{ $item->product->name ?? 'Eliminado' }} ×{{ $item->quantity }} — ${{ number_format($item->quantity * $item->price, 2) }}</li>
                                    @endforeach
                                </ul>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-400">No hay pedidos en este rango.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
</x-layouts.app>

