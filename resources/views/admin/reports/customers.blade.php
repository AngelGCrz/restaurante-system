<x-layouts.app>
<div class="flex h-full w-full flex-1 flex-col gap-4 p-3 sm:p-4">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 sm:text-2xl">Reporte de Clientes</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Frecuencia de visitas y consumo por cliente.</p>
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
            <a href="{{ route('admin.reports.customers') }}" class="rounded-lg border border-zinc-300 px-4 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">Limpiar</a>
        </div>
    </form>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Clientes únicos</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totalCustomers }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Total consumido</div>
            <div class="mt-1 text-2xl font-bold text-blue-600">${{ number_format($totalSpent, 2) }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Cliente top</div>
            <div class="mt-1 text-lg font-bold text-zinc-900 dark:text-zinc-100">
                {{ $topCustomer ? $topCustomer->customer_name : '—' }}
            </div>
            @if($topCustomer)
                <div class="text-xs text-zinc-400">${{ number_format($topCustomer->total_spent, 2) }} en {{ $topCustomer->visits }} visitas</div>
            @endif
        </div>
    </div>

    {{-- Tabla clientes --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">Ranking de clientes (pedidos cobrados)</h2>
        </div>

        {{-- Mobile --}}
        <div class="block space-y-2 p-3 lg:hidden">
            @forelse ($customers as $i => $c)
                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-sm font-bold text-zinc-400">{{ $i + 1 }}.</span>
                            <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $c->customer_name }}</span>
                        </div>
                        <span class="font-bold text-blue-600">${{ number_format($c->total_spent, 2) }}</span>
                    </div>
                    <div class="mt-1 flex flex-wrap gap-x-4 text-xs text-zinc-500">
                        <span>{{ $c->visits }} visitas</span>
                        <span>Prom. ${{ number_format($c->avg_ticket, 2) }}</span>
                        <span>Últ. visita: {{ \Carbon\Carbon::parse($c->last_visit)->format('d/m/Y') }}</span>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-zinc-400">No hay clientes con nombre registrado en este período.</p>
            @endforelse
        </div>

        {{-- Desktop --}}
        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-zinc-900 dark:text-zinc-100">
                <thead class="border-b border-zinc-100 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">#</th>
                        <th class="px-4 py-3">Cliente</th>
                        <th class="px-4 py-3 text-right">Visitas</th>
                        <th class="px-4 py-3 text-right">Total consumido</th>
                        <th class="px-4 py-3 text-right">Ticket promedio</th>
                        <th class="px-4 py-3">Última visita</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($customers as $i => $c)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 text-zinc-400">{{ $i + 1 }}</td>
                            <td class="px-4 py-3 font-medium">{{ $c->customer_name }}</td>
                            <td class="px-4 py-3 text-right">{{ $c->visits }}</td>
                            <td class="px-4 py-3 text-right font-semibold text-blue-600">${{ number_format($c->total_spent, 2) }}</td>
                            <td class="px-4 py-3 text-right">${{ number_format($c->avg_ticket, 2) }}</td>
                            <td class="px-4 py-3 text-zinc-500">{{ \Carbon\Carbon::parse($c->last_visit)->format('d/m/Y') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-400">No hay clientes con nombre registrado en este período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-layouts.app>
