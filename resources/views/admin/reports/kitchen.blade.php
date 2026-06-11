<x-layouts.app>
<div class="flex h-full w-full flex-1 flex-col gap-4 p-3 sm:p-4">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 sm:text-2xl">Reporte de Cocina</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Tiempos de preparación por día.</p>
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
            <a href="{{ route('admin.reports.kitchen') }}" class="rounded-lg border border-zinc-300 px-4 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">Limpiar</a>
        </div>
    </form>

    {{-- Tarjeta promedio global --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Tiempo promedio de preparación</div>
            @if($globalAvg)
                @php
                    $mins = floor($globalAvg / 60);
                    $secs = round($globalAvg % 60);
                @endphp
                <div class="mt-1 text-2xl font-bold text-blue-600">{{ $mins }}m {{ $secs }}s</div>
            @else
                <div class="mt-1 text-2xl font-bold text-zinc-400">Sin datos</div>
            @endif
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Pedidos lentos (&gt;15 min)</div>
            <div class="mt-1 text-2xl font-bold text-red-600">{{ $slowOrders->count() }}</div>
        </div>
    </div>

    {{-- Resumen por día --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">Tiempos por día</h2>
        </div>

        {{-- Mobile --}}
        <div class="block space-y-2 p-3 lg:hidden">
            @forelse ($ordersWithTime as $row)
                @php
                    $avgM = floor($row->avg_seconds / 60);
                    $avgS = round($row->avg_seconds % 60);
                    $minM = floor($row->min_seconds / 60);
                    $maxM = floor($row->max_seconds / 60);
                @endphp
                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</span>
                        <span class="font-bold text-blue-600">Prom. {{ $avgM }}m {{ $avgS }}s</span>
                    </div>
                    <div class="mt-1 flex flex-wrap gap-x-4 text-xs text-zinc-500">
                        <span>{{ $row->orders_count }} pedidos</span>
                        <span>Mín. {{ $minM }}m</span>
                        <span>Máx. {{ $maxM }}m</span>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-zinc-400">Sin datos de tiempo de preparación en este período.</p>
            @endforelse
        </div>

        {{-- Desktop --}}
        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-zinc-900 dark:text-zinc-100">
                <thead class="border-b border-zinc-100 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3 text-right">Pedidos</th>
                        <th class="px-4 py-3 text-right">Promedio</th>
                        <th class="px-4 py-3 text-right">Mínimo</th>
                        <th class="px-4 py-3 text-right">Máximo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($ordersWithTime as $row)
                        @php
                            $avgM = floor($row->avg_seconds / 60);
                            $avgS = round($row->avg_seconds % 60);
                            $minM = floor($row->min_seconds / 60);
                            $minS = round($row->min_seconds % 60);
                            $maxM = floor($row->max_seconds / 60);
                            $maxS = round($row->max_seconds % 60);
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">{{ \Carbon\Carbon::parse($row->date)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-right">{{ $row->orders_count }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-blue-600">{{ $avgM }}m {{ $avgS }}s</td>
                            <td class="px-4 py-3 text-right text-green-600">{{ $minM }}m {{ $minS }}s</td>
                            <td class="px-4 py-3 text-right {{ $maxM >= 15 ? 'text-red-600 font-semibold' : '' }}">{{ $maxM }}m {{ $maxS }}s</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-zinc-400">Sin datos de tiempo de preparación en este período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Pedidos más lentos --}}
    @if($slowOrders->isNotEmpty())
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">Pedidos más lentos (&gt;15 min)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-zinc-900 dark:text-zinc-100">
                <thead class="border-b border-zinc-100 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Fecha</th>
                        <th class="px-4 py-3">Mozo</th>
                        <th class="px-4 py-3 text-right">Tiempo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @foreach ($slowOrders as $order)
                        @php
                            $m = floor($order->preparation_seconds / 60);
                            $s = $order->preparation_seconds % 60;
                        @endphp
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-2 font-mono text-zinc-400">#{{ $order->id }}</td>
                            <td class="px-4 py-2">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2">{{ $order->user->name ?? '—' }}</td>
                            <td class="px-4 py-2 text-right font-semibold text-red-600">{{ $m }}m {{ $s }}s</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
</x-layouts.app>
