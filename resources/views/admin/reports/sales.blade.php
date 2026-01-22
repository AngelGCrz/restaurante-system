<x-layouts.app>
    <div class="p-4">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-bold">Reporte de Ventas</h1>
            <form method="GET" class="flex items-center gap-2">
                <input type="date" name="start" value="{{ $startDate->format('Y-m-d') }}" class="rounded border px-2 py-1 text-sm">
                <input type="date" name="end" value="{{ $endDate->format('Y-m-d') }}" class="rounded border px-2 py-1 text-sm">
                <select name="status" class="rounded border px-2 py-1 text-sm">
                    <option value="">Mostrar (default)</option>
                    <option value="pendiente" {{ request('status') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                    <option value="pagado" {{ request('status') === 'pagado' ? 'selected' : '' }}>Cobrado (Caja)</option>
                    <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Todos (incluye cancelados)</option>
                </select>
                <select name="user_id" class="rounded border px-2 py-1 text-sm">
                    <option value="">Todos los Mozos</option>
                    @foreach(\App\Models\User::whereHas('role', fn($q) => $q->where('name','mozo'))->orderBy('name')->get() as $mozo)
                        <option value="{{ $mozo->id }}" {{ request('user_id') == $mozo->id ? 'selected' : '' }}>{{ $mozo->name }}</option>
                    @endforeach
                </select>
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="breakdown" value="1" {{ request()->boolean('breakdown') ? 'checked' : '' }}>
                    Mostrar desglose por estado
                </label>
                <button type="submit" class="rounded bg-blue-600 text-white px-3 py-1 text-sm">Filtrar</button>
                <button type="submit" name="export" value="1" class="rounded bg-zinc-800 text-white px-3 py-1 text-sm">Exportar CSV</button>
            </form>
        </div>

        <div class="rounded-xl border bg-white p-4">
            <h2 class="font-semibold mb-2">Resumen</h2>
            <div class="grid grid-cols-3 gap-4 mb-4">
                <div class="p-3 border rounded">
                    <div class="text-sm text-zinc-500">Pedidos</div>
                    <div class="text-xl font-bold">{{ $totals->orders_count ?? 0 }}</div>
                </div>
                <div class="p-3 border rounded">
                    <div class="text-sm text-zinc-500">Total ventas</div>
                    <div class="text-xl font-bold">${{ number_format($totals->total_sales ?? 0, 2) }}</div>
                </div>
                <div class="p-3 border rounded">
                    <div class="text-sm text-zinc-500">Promedio por ticket</div>
                    <div class="text-xl font-bold">${{ number_format($totals->avg_ticket ?? 0, 2) }}</div>
                </div>
                @if(request()->boolean('breakdown'))
                    <div class="p-3 border rounded">
                        <div class="text-sm text-zinc-500">Pendientes</div>
                        <div class="text-xl font-bold">{{ $totals->pending_count ?? 0 }}</div>
                    </div>
                    <div class="p-3 border rounded">
                        <div class="text-sm text-zinc-500">Cobrado</div>
                        <div class="text-xl font-bold">{{ $totals->paid_count ?? 0 }}</div>
                    </div>
                    <div class="p-3 border rounded">
                        <div class="text-sm text-zinc-500">Cancelados</div>
                        <div class="text-xl font-bold">{{ $totals->cancelled_count ?? 0 }}</div>
                    </div>
                @endif
            </div>

            <h2 class="font-semibold mb-2">Detalle por día</h2>
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b">
                        <th class="py-2">Fecha</th>
                        <th class="py-2">Pedidos</th>
                        <th class="py-2">Total ventas</th>
                        <th class="py-2">Promedio</th>
                        @if(request()->boolean('breakdown'))
                            <th class="py-2">Pendiente</th>
                            <th class="py-2">Cobrado</th>
                            <th class="py-2">Cancelado</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($perDay as $row)
                            <tr class="border-b">
                                <td class="py-2">{{ $row->date }}</td>
                                <td class="py-2">{{ $row->orders_count }}</td>
                                <td class="py-2">${{ number_format($row->total_sales ?? 0, 2) }}</td>
                                <td class="py-2">${{ number_format($row->avg_ticket ?? 0, 2) }}</td>
                                    @if(request()->boolean('breakdown'))
                                        <td class="py-2">{{ $row->pending_count ?? 0 }}</td>
                                        <td class="py-2">{{ $row->paid_count ?? 0 }}</td>
                                        <td class="py-2">{{ $row->cancelled_count ?? 0 }}</td>
                                    @endif
                            </tr>
                    @empty
                        <tr><td colspan="4" class="py-4 text-sm text-zinc-500">No hay datos en el rango seleccionado.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
