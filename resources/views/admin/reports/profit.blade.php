<x-layouts.app>
<div class="flex h-full w-full flex-1 flex-col gap-4 p-3 sm:p-4">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 sm:text-2xl">Reporte de Ganancias</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Ingresos totales y tendencia mensual.</p>
        </div>
        <flux:button href="{{ route('admin.reports.index') }}" variant="ghost" icon="arrow-left">Volver</flux:button>
    </div>

    {{-- Filtros --}}
    <form method="GET" class="rounded-xl border border-zinc-200 bg-white p-3 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 sm:p-4">
        <div class="grid grid-cols-1 gap-2 sm:grid-cols-2">
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
        </div>
        <div class="mt-3 flex gap-2">
            <button type="submit" class="rounded-lg bg-blue-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-blue-700">Filtrar</button>
            <a href="{{ route('admin.reports.profit') }}" class="rounded-lg border border-zinc-300 px-4 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">Limpiar</a>
        </div>
    </form>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Ingresos totales</div>
            <div class="mt-1 text-2xl font-bold text-blue-600">${{ number_format($totalRevenue, 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Pedidos cobrados</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totalOrders }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Mejor mes</div>
            <div class="mt-1 text-lg font-bold text-green-600">
                {{ $bestMonth ? \Carbon\Carbon::parse($bestMonth->month . '-01')->format('M Y') : '—' }}
            </div>
            @if($bestMonth)
                <div class="text-xs text-zinc-400">${{ number_format($bestMonth->revenue, 2) }}</div>
            @endif
        </div>
    </div>

    {{-- Ingresos por mes --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">Ingresos por mes</h2>
        </div>

        {{-- Mobile --}}
        <div class="block space-y-2 p-3 lg:hidden">
            @forelse ($byMonth as $row)
                @php $pct = $totalRevenue > 0 ? round($row->revenue / $totalRevenue * 100, 1) : 0; @endphp
                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($row->month . '-01')->format('M Y') }}</span>
                        <span class="font-bold text-blue-600">${{ number_format($row->revenue, 2) }}</span>
                    </div>
                    <div class="mt-1 flex gap-4 text-xs text-zinc-500">
                        <span>{{ $row->orders_count }} pedidos</span>
                        <span>Prom. ${{ number_format($row->avg_ticket, 2) }}</span>
                        <span>{{ $pct }}% del total</span>
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div class="h-2 rounded-full bg-blue-500" style="width: {{ $pct }}%"></div>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-zinc-400">No hay ingresos en este período.</p>
            @endforelse
        </div>

        {{-- Desktop --}}
        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-zinc-900 dark:text-zinc-100">
                <thead class="border-b border-zinc-100 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Mes</th>
                        <th class="px-4 py-3 text-right">Pedidos</th>
                        <th class="px-4 py-3 text-right">Ingresos</th>
                        <th class="px-4 py-3 text-right">Ticket promedio</th>
                        <th class="px-4 py-3">% del total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($byMonth as $row)
                        @php $pct = $totalRevenue > 0 ? round($row->revenue / $totalRevenue * 100, 1) : 0; @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 font-medium">{{ \Carbon\Carbon::parse($row->month . '-01')->format('F Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ $row->orders_count }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-blue-600">${{ number_format($row->revenue, 2) }}</td>
                            <td class="px-4 py-3 text-right">${{ number_format($row->avg_ticket, 2) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-2 w-24 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                                        <div class="h-2 rounded-full bg-blue-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-zinc-400">{{ $pct }}%</span>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-400">No hay ingresos en este período.</td></tr>
                    @endforelse
                </tbody>
                @if($byMonth->isNotEmpty())
                <tfoot class="border-t-2 border-zinc-200 dark:border-zinc-700">
                    <tr class="font-bold">
                        <td class="px-4 py-3">TOTAL</td>
                        <td class="px-4 py-3 text-right">{{ $totalOrders }}</td>
                        <td class="px-4 py-3 text-right text-blue-600">${{ number_format($totalRevenue, 2) }}</td>
                        <td class="px-4 py-3 text-right">${{ $totalOrders > 0 ? number_format($totalRevenue / $totalOrders, 2) : '0.00' }}</td>
                        <td class="px-4 py-3">100%</td>
                    </tr>
                </tfoot>
                @endif
            </table>
        </div>
    </div>

    {{-- Top productos --}}
    @if($topProducts->isNotEmpty())
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">Top 10 productos por ingresos</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-zinc-900 dark:text-zinc-100">
                <thead class="border-b border-zinc-100 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3 text-right">Unidades</th>
                        <th class="px-4 py-3 text-right">Ingresos</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($topProducts as $i => $item)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-2 text-zinc-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-2 font-medium">{{ $item->product->name ?? 'Eliminado' }}</td>
                            <td class="px-4 py-2 text-right">{{ $item->qty }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-blue-600">${{ number_format($item->revenue, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
</x-layouts.app>
