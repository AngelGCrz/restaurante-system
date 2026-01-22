<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Listado de Pedidos (Caja)</h1>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="pb-3 font-semibold">ID</th>
                        <th class="pb-3 font-semibold">Cliente</th>
                        <th class="pb-3 font-semibold">Mesa/Tipo</th>
                        <th class="pb-3 font-semibold">Total</th>
                        <th class="pb-3 font-semibold">Estado</th>
                        <th class="pb-3 font-semibold">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($orders as $order)
                        <tr>
                            <td class="py-3">#{{ $order->id }}</td>
                            <td class="py-3">{{ $order->customer_name ?? 'N/A' }}</td>
                            <td class="py-3">
                                {{ $order->table_label }}
                            </td>
                            <td class="py-3">${{ number_format($order->total, 2) }}</td>
                            <td class="py-3">
                                <span class="rounded-full px-2 py-1 text-xs
                                    {{ $order->status === 'pagado' ? 'bg-green-100 text-green-700' : ($order->status === 'pendiente' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                            <td class="py-3 flex items-center gap-2">
                                <flux:button size="sm" variant="subtle" href="{{ route('orders.show', $order) }}">Ver</flux:button>
                                @if($order->status === 'pendiente' && auth()->check() && auth()->user()->role->name === 'cajero')
                                    <form action="{{ route('orders.cancel', $order) }}" method="POST" onsubmit="return confirm('¿Confirmar cancelación del pedido #{{ $order->id }}?');">
                                        @csrf
                                        <flux:button type="submit" size="sm" variant="danger">Cancelar</flux:button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
