<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center gap-4">
            <flux:button variant="subtle" icon="arrow-left" href="{{ auth()->user()->role->name === 'mozo' ? route('mozo.orders.index') : route('caja.dashboard') }}" />
            <h1 class="text-2xl font-bold">Detalle de Pedido #{{ $order->id }}</h1>
            @if($order->type === 'llevar')
                <span class="ml-2 inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-sm font-semibold text-indigo-700">🥡 Para llevar</span>
            @elseif($order->type === 'reserva')
                <span class="ml-2 inline-flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-sm font-semibold text-amber-700">📋 Reserva</span>
            @elseif($order->type === 'personal')
                <span class="ml-2 inline-flex items-center gap-2 rounded-full bg-green-50 px-3 py-1 text-sm font-semibold text-green-700">👤 Personal</span>
            @endif
        </div>

        @if(session('success'))
            <flux:callout variant="success" heading="{{ session('success') }}" />
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 class="text-lg font-semibold mb-4">Productos</h2>
                    @php
                        $orderItemsTotal = $order->items->sum(function($it) { return ($it->price * $it->quantity); });
                    @endphp
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="pb-3 font-semibold">Producto</th>
                                <th class="pb-3 font-semibold">Cant</th>
                                <th class="pb-3 font-semibold text-right">P.Unit</th>
                                <th class="pb-3 font-semibold text-right">SubT</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">

                            {{-- Items del pedido principal --}}
                            @foreach($order->items as $item)
                            <tr>
                                <td class="py-3">
                                    {{ $item->product->name }}
                                    @if($item->comment)
                                        <p class="mt-1 text-sm text-blue-600 font-medium">📝 {{ $item->comment }}</p>
                                    @endif
                                </td>
                                <td class="py-3">{{ $item->quantity }}</td>
                                <td class="py-3 text-right">S/ {{ number_format($item->price, 2) }}</td>
                                <td class="py-3 text-right">S/ {{ number_format($item->price * $item->quantity, 2) }}</td>
                            </tr>
                            @endforeach

                            {{-- Items de órdenes hijas (agregados posteriormente) --}}
                            @foreach($order->childOrders->where('status', '!=', 'cancelado') as $child)
                                <tr>
                                    <td colspan="4" class="pt-3 pb-1">
                                        <span class="text-xs font-semibold text-yellow-600 dark:text-yellow-400 bg-yellow-50 dark:bg-yellow-900/30 px-2 py-0.5 rounded-full">
                                            ➕ Agregado — Orden #{{ $child->id }}
                                        </span>
                                    </td>
                                </tr>
                                @foreach($child->items as $item)
                                <tr class="bg-yellow-50/30 dark:bg-yellow-900/10">
                                    <td class="py-2 pl-4">
                                        {{ $item->product->name }}
                                        @if($item->comment)
                                            <p class="mt-0.5 text-sm text-blue-600 font-medium">📝 {{ $item->comment }}</p>
                                        @endif
                                    </td>
                                    <td class="py-2">{{ $item->quantity }}</td>
                                    <td class="py-2 text-right">S/ {{ number_format($item->price, 2) }}</td>
                                    <td class="py-2 text-right">S/ {{ number_format($item->price * $item->quantity, 2) }}</td>
                                </tr>
                                @endforeach
                            @endforeach

                        </tbody>
                        <tfoot>
                            @php
                                $totalEstaOrden = $order->items->sum(fn($it) => $it->price * $it->quantity);
                                $totalSesion    = $totalEstaOrden
                                    + $order->childOrders->where('status', '!=', 'cancelado')
                                        ->sum(fn($child) => $child->items->sum(fn($it) => $it->price * $it->quantity));
                                $tieneHijos = $order->childOrders->where('status', '!=', 'cancelado')->isNotEmpty();
                            @endphp

                            {{-- Total solo de esta orden --}}
                            <tr class="border-t border-zinc-200 dark:border-zinc-600">
                                <td colspan="3" class="pt-3 text-right text-sm text-zinc-500 dark:text-zinc-400">
                                    Total orden #{{ $order->id }}:
                                </td>
                                <td class="pt-3 text-right text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                                    S/ {{ number_format($totalEstaOrden, 2) }}
                                </td>
                            </tr>

                            {{-- Total sesión completa (solo si hay hijos) --}}
                            @if($tieneHijos)
                            <tr class="border-t-2 border-zinc-300 dark:border-zinc-600">
                                <td colspan="3" class="pt-3 text-right font-bold text-lg dark:text-white">
                                    Total sesión mesa:
                                </td>
                                <td class="pt-3 text-right font-bold text-lg text-green-600 dark:text-green-400">
                                    S/ {{ number_format($totalSesion, 2) }}
                                </td>
                            </tr>
                            @else
                            <tr class="border-t-2 border-zinc-300 dark:border-zinc-600">
                                <td colspan="3" class="pt-3 text-right font-bold text-lg dark:text-white">Total:</td>
                                <td class="pt-3 text-right font-bold text-lg text-green-600 dark:text-green-400">
                                    S/ {{ number_format($totalEstaOrden, 2) }}
                                </td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 class="text-lg font-semibold mb-4">Información General</h2>
                    <div class="space-y-3">
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Estado:</span>
                            <span class="font-medium capitalize {{ $order->status === 'pagado' ? 'text-green-600' : ($order->status === 'cancelado' ? 'text-red-600' : 'text-yellow-600') }}">{{ $order->status }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Cliente:</span>
                            <span class="font-medium">{{ $order->customer_name ?? 'N/A' }}</span>
                        </div>
                        @if($order->comment)
                            <div>
                                <span class="text-zinc-500 block">Comentario:</span>
                                <p class="mt-1 text-sm">{{ $order->comment }}</p>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Servicio:</span>
                            <span class="font-medium">{{ $order->table_label }}</span>
                        </div>
                        @if($order->type === 'llevar')
                            <div class="flex justify-between">
                                <span class="text-zinc-500">Para llevar:</span>
                                <span class="font-medium text-indigo-600">🥡 Sí</span>
                            </div>
                        @endif
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Atendido por:</span>
                            <span class="font-medium">{{ $order->user->name }}</span>
                        </div>
                    </div>
                    @if($order->status === 'pendiente' && auth()->user()->role->name === 'cajero')
                        <div class="mt-6 space-y-3">
                            <form action="{{ route('orders.pay', $order) }}" method="POST" class="space-y-4">
    @csrf

    <!-- Forma de pago -->
    <div>
        <label class="block text-sm font-medium mb-1">Forma de pago</label>
        <select name="payment_method" required
            class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
            <option value="">Seleccione...</option>
            <option value="efectivo">Efectivo</option>
            <option value="yape">Yape</option>
            <option value="tarjeta">Tarjeta</option>
        </select>
    </div>

    <!-- Tipo de comprobante -->
    <div>
        <label class="block text-sm font-medium mb-1">Comprobante</label>
        <select name="receipt_type" required
            class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white">
            <option value="">Seleccione...</option>
            <option value="ticket">Ticket</option>
            <option value="boleta">Boleta</option>
            <option value="factura">Factura</option>
        </select>
    </div>

    <flux:button type="submit" variant="primary" class="w-full">
        Registrar Pago
    </flux:button>
</form>


                            <form action="{{ route('orders.cancel', $order) }}" method="POST" onsubmit="return confirm('¿Confirmar cancelación del pedido #{{ $order->id }}?');">
                                @csrf
                                <flux:button type="submit" variant="danger" class="w-full">Cancelar Pedido</flux:button>
                            </form>
                        </div>
                    @endif
                    @if($order->status === 'pendiente' && auth()->user()->role->name === 'mozo' && is_null($order->origin_order_id))
                        <div class="mt-6">
                            <flux:button href="{{ route('mozo.orders.add-items', $order) }}" variant="primary" class="w-full">➕ Agregar Nueva Orden</flux:button>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    @if(session('paid'))
        <script>
            window.addEventListener('load', () => {
                if (window.showToast) {
                    showToast('PAGO REALIZADO', 'success');
                } else {
                    alert('PAGO REALIZADO');
                }
            });
        </script>
    @endif
</x-layouts.app>