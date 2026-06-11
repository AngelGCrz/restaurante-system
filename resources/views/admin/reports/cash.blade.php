<x-layouts.app>
<div class="flex h-full w-full flex-1 flex-col gap-4 p-3 sm:p-4">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 sm:text-2xl">Reporte de Caja</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Aperturas, cierres y movimientos por período.</p>
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
            <a href="{{ route('admin.reports.cash') }}" class="rounded-lg border border-zinc-300 px-4 py-1.5 text-sm text-zinc-700 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-800">Limpiar</a>
        </div>
    </form>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Sesiones abiertas</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totalOpened }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Sesiones cerradas</div>
            <div class="mt-1 text-2xl font-bold text-green-600">{{ $totalClosed }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Ventas cobradas en período</div>
            <div class="mt-1 text-2xl font-bold text-blue-600">${{ number_format($totalRevenue, 2) }}</div>
        </div>
    </div>

    {{-- Listado de sesiones --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">Sesiones de caja</h2>
        </div>

        {{-- Mobile --}}
        <div class="block space-y-2 p-3 lg:hidden">
            @forelse ($sessions as $s)
                <div class="rounded-lg border border-zinc-100 p-3 dark:border-zinc-800">
                    <div class="flex items-start justify-between">
                        <div>
                            <div class="font-medium text-zinc-900 dark:text-zinc-100">{{ $s->user->name ?? '—' }}</div>
                            <div class="text-xs text-zinc-500">Apertura: {{ $s->opened_at->format('d/m/Y H:i') }}</div>
                            @if($s->closed_at)
                                <div class="text-xs text-zinc-500">Cierre: {{ $s->closed_at->format('d/m/Y H:i') }}</div>
                            @endif
                        </div>
                        <div class="text-right">
                            @if($s->closed_at)
                                <span class="inline-block rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Cerrada</span>
                            @else
                                <span class="inline-block rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">Abierta</span>
                            @endif
                        </div>
                    </div>
                    <div class="mt-2 grid grid-cols-3 gap-2 text-xs">
                        <div><span class="text-zinc-400">Apertura</span><br><strong>${{ number_format($s->opening_balance, 2) }}</strong></div>
                        <div><span class="text-zinc-400">Cierre</span><br><strong>{{ $s->closing_balance !== null ? '$' . number_format($s->closing_balance, 2) : '—' }}</strong></div>
                        <div>
                            <span class="text-zinc-400">Diferencia</span><br>
                            @if($s->balance_diff !== null)
                                <strong class="{{ $s->balance_diff >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $s->balance_diff >= 0 ? '+' : '' }}${{ number_format($s->balance_diff, 2) }}
                                </strong>
                            @else
                                <strong>—</strong>
                            @endif
                        </div>
                    </div>
                    @if($s->notes)
                        <div class="mt-1 text-xs text-zinc-400">{{ $s->notes }}</div>
                    @endif
                </div>
            @empty
                <p class="py-6 text-center text-sm text-zinc-400">No hay sesiones de caja en este período.</p>
            @endforelse
        </div>

        {{-- Desktop --}}
        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-zinc-900 dark:text-zinc-100">
                <thead class="border-b border-zinc-100 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Cajero</th>
                        <th class="px-4 py-3">Apertura</th>
                        <th class="px-4 py-3">Cierre</th>
                        <th class="px-4 py-3">Duración</th>
                        <th class="px-4 py-3 text-right">Saldo apertura</th>
                        <th class="px-4 py-3 text-right">Saldo cierre</th>
                        <th class="px-4 py-3 text-right">Diferencia</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Notas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($sessions as $s)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3 font-medium">{{ $s->user->name ?? '—' }}</td>
                            <td class="px-4 py-3">{{ $s->opened_at->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $s->closed_at ? $s->closed_at->format('d/m/Y H:i') : '—' }}</td>
                            <td class="px-4 py-3">{{ $s->duration_minutes !== null ? $s->duration_minutes . ' min' : '—' }}</td>
                            <td class="px-4 py-3 text-right">${{ number_format($s->opening_balance, 2) }}</td>
                            <td class="px-4 py-3 text-right">{{ $s->closing_balance !== null ? '$' . number_format($s->closing_balance, 2) : '—' }}</td>
                            <td class="px-4 py-3 text-right {{ $s->balance_diff !== null ? ($s->balance_diff >= 0 ? 'text-green-600' : 'text-red-600') : '' }}">
                                @if($s->balance_diff !== null)
                                    {{ $s->balance_diff >= 0 ? '+' : '' }}${{ number_format($s->balance_diff, 2) }}
                                @else —
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if($s->closed_at)
                                    <span class="rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">Cerrada</span>
                                @else
                                    <span class="rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">Abierta</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-400">{{ $s->notes ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="px-4 py-8 text-center text-zinc-400">No hay sesiones de caja en este período.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-layouts.app>
