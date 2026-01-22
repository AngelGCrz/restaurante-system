<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center gap-4">
            <flux:button variant="subtle" icon="arrow-left" href="{{ auth()->user()->role->name === 'mozo' ? route('mozo.orders.create') : route('orders.index') }}" />
            <h1 class="text-2xl font-bold">Detalle de Pedido #{{ $order->id }}</h1>
        </div>

        @if(session('success'))
            <flux:callout variant="success" heading="{{ session('success') }}" />
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 class="text-lg font-semibold mb-4">Productos</h2>
                    <table class="w-full text-left">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="pb-3 font-semibold">Producto</th>
                                <th class="pb-3 font-semibold">Cantidad</th>
                                <th class="pb-3 font-semibold text-right">Precio</th>
                                <th class="pb-3 font-semibold text-right">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($order->items as $item)
                                <tr>
                                    <td class="py-3">{{ $item->product->name }}</td>
                                    <td class="py-3">{{ $item->quantity }}</td>
                                    <td class="py-3 text-right">${{ number_format($item->price, 2) }}</td>
                                    <td class="py-3 text-right">${{ number_format($item->price * $item->quantity, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="pt-4 text-right font-bold text-lg">Total:</td>
                                <td class="pt-4 text-right font-bold text-lg text-primary-600">${{ number_format($order->total, 2) }}</td>
                            </tr>
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
                        <div class="flex justify-between">
                            <span class="text-zinc-500">Atendido por:</span>
                            <span class="font-medium">{{ $order->user->name }}</span>
                        </div>
                    </div>
                    @if($order->status === 'pendiente' && auth()->user()->role->name === 'cajero')
                        <div class="mt-6 space-y-3">
                            <form action="{{ route('orders.pay', $order) }}" method="POST">
                                @csrf
                                <flux:button type="submit" variant="primary" class="w-full">Registrar Pago</flux:button>
                            </form>

                            <form action="{{ route('orders.cancel', $order) }}" method="POST" onsubmit="return confirm('¿Confirmar cancelación del pedido #{{ $order->id }}?');">
                                @csrf
                                <flux:button type="submit" variant="danger" class="w-full">Cancelar Pedido</flux:button>
                            </form>
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
