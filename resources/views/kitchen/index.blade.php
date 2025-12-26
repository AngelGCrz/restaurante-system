<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Monitor de Cocina</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($orders as $order)
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex justify-between items-center mb-3">
                        <span class="font-bold">Orden #{{ $order->id }}</span>
                        <span class="text-sm text-zinc-500">{{ $order->created_at->format('H:i') }}</span>
                    </div>
                    <div class="mb-3 space-y-1">
                        <p class="text-sm font-semibold">Cliente: {{ $order->customer_name ?? 'N/A' }}</p>
                        <p class="text-sm">Servicio: {{ $order->table_label }}</p>
                        <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-700">
                            Pendiente
                        </span>
                    </div>
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($order->items as $item)
                            <li class="py-2 flex justify-between">
                                <span>{{ $item->quantity }}x {{ $item->product->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>
</x-layouts.app>
