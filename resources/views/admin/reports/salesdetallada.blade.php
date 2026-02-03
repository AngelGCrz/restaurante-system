<x-layouts.app>
    <div class="p-4">
        <div class="flex flex-col gap-4 mb-4 lg:flex-row lg:items-center lg:justify-between">
    <h1 class="text-xl lg:text-2xl font-bold text-zinc-900 dark:text-zinc-100">
        Reporte de Ventas Detalladas
    </h1>

    <form method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-6 gap-2 w-full lg:w-auto">
        <input type="date" name="start" value="{{ $startDate->format('Y-m-d') }}" class="rounded border px-2 py-1 text-sm">
        <input type="date" name="end" value="{{ $endDate->format('Y-m-d') }}" class="rounded border px-2 py-1 text-sm">

        <select name="status" class="rounded border px-2 py-1 text-sm">
            <option value="">Estado</option>
            <option value="pendiente" {{ request('status') === 'pendiente' ? 'selected' : '' }}>Pendiente</option>
            <option value="pagado" {{ request('status') === 'pagado' ? 'selected' : '' }}>Cobrado</option>
            <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>Todos</option>
        </select>

        <select name="user_id" class="rounded border px-2 py-1 text-sm">
            <option value="">Todos los Mozos</option>
            @foreach(\App\Models\User::whereHas('role', fn($q) => $q->where('name','mozo'))->orderBy('name')->get() as $mozo)
                <option value="{{ $mozo->id }}" {{ request('user_id') == $mozo->id ? 'selected' : '' }}>
                    {{ $mozo->name }}
                </option>
            @endforeach
        </select>

        <label class="flex items-center gap-2 text-sm">
            <input type="checkbox" name="breakdown" value="1" {{ request()->boolean('breakdown') ? 'checked' : '' }}>
            Desglose
        </label>

        <div class="flex gap-2">
            <button type="submit" class="flex-1 rounded bg-blue-600 text-white px-3 py-1 text-sm">Filtrar</button>
            <button type="submit" name="export" value="1" class="flex-1 rounded bg-zinc-800 text-white px-3 py-1 text-sm">CSV</button>
        </div>
    </form>
</div>

        <div class="rounded-xl border bg-white dark:bg-zinc-900 dark:border-zinc-700 p-4">
            <h2 class="font-semibold mb-2 text-zinc-900 dark:text-zinc-100">Resumen</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-4">
                <div class="p-3 border rounded border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Pedidos</div>
                    <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totals->orders_count ?? 0 }}</div>
                </div>
                <div class="p-3 border rounded border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Total ventas</div>
                    <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100">${{ number_format($totals->total_sales ?? 0, 2) }}</div>
                </div>
                <div class="p-3 border rounded border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                    <div class="text-sm text-zinc-500 dark:text-zinc-400">Venta promedio por pedido</div>
                    <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100">${{ number_format($totals->avg_ticket ?? 0, 2) }}</div>
                </div>
                @if(request()->boolean('breakdown'))
                    <div class="p-3 border rounded border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Pendientes</div>
                        <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totals->pending_count ?? 0 }}</div>
                    </div>
                    <div class="p-3 border rounded border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Cobrado</div>
                        <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totals->paid_count ?? 0 }}</div>
                    </div>
                    <div class="p-3 border rounded border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800">
                        <div class="text-sm text-zinc-500 dark:text-zinc-400">Cancelados</div>
                        <div class="text-xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totals->cancelled_count ?? 0 }}</div>
                    </div>
                @endif
            </div>

            
           <div class="block lg:hidden space-y-4">
    @forelse($orders as $order)
        <div class="border rounded-lg p-3 bg-white dark:bg-zinc-800">
            <div class="flex justify-between items-center mb-1">
                <div class="font-semibold">Pedido #{{ $order->id }}</div>
                <span class="text-sm capitalize">{{ $order->status }}</span>
            </div>
            <div class="text-sm text-zinc-500 mb-1">
                {{ $order->created_at->format('d/m/Y H:i') }} — {{ $order->user->name ?? '—' }}
            </div>
            <div class="font-bold mb-2">${{ number_format($order->total, 2) }}</div>

            <button 
                onclick="toggleRow('mobile-order-{{ $order->id }}')" 
                class="text-blue-600 text-sm mb-2">
                Ver productos
            </button>

            <div id="mobile-order-{{ $order->id }}" class="hidden">
                <ul class="text-sm space-y-1">
                    @foreach($order->orderItems as $item)
                        <li>
                            • {{ $item->product->name ?? 'Producto eliminado' }} —
                            {{ $item->quantity }} x ${{ number_format($item->price, 2) }}
                            = <strong>${{ number_format($item->quantity * $item->price, 2) }}</strong>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    @empty
        <div class="text-sm text-zinc-500">No hay pedidos en este rango.</div>
    @endforelse
</div>

<div class="hidden lg:block">
            <h2 class="font-semibold mt-6 mb-2">Detalle de Pedidos</h2>
<table class="w-full text-left text-zinc-900 dark:text-zinc-100">
    <thead>
        <tr class="border-b">
            <th class="py-2">#</th>
            <th class="py-2">Fecha</th>
            <th class="py-2">Mozo</th>
            <th class="py-2">Estado</th>
            <th class="py-2">Total</th>
        </tr>
    </thead>
    <tbody>
        @forelse($orders as $order)
            <tr class="border-b">
                <td class="py-2">#{{ $order->id }}</td>
                <td class="py-2">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                <td class="py-2">{{ $order->user->name ?? '—' }}</td>
                <td class="py-2 capitalize">{{ $order->status }}</td>
                <td class="py-2 font-semibold">${{ number_format($order->total, 2) }}</td>
            </tr>
            <tr class="bg-zinc-50 dark:bg-zinc-800">
                <td colspan="5" class="py-2 px-4">
                    <ul class="text-sm space-y-1">
                        @foreach($order->orderItems as $item)
                            <li>
                                • {{ $item->product->name ?? 'Producto eliminado' }} —
                                {{ $item->quantity }} x ${{ number_format($item->price, 2) }}
                                = <strong>${{ number_format($item->quantity * $item->price, 2) }}</strong>
                            </li>
                        @endforeach
                    </ul>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="py-4 text-sm text-zinc-500 dark:text-zinc-400">
                    No hay pedidos en este rango.
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
</div>
        </div>
    </div>

    <script>
    function toggleRow(id) {
        document.getElementById(id).classList.toggle('hidden');
    }
</script>

</x-layouts.app>
