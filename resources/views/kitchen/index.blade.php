<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Monitor de Cocina</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($orders as $order)
                <a href="{{ route('kitchen.show', $order) }}" class="block rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 hover:shadow"> 
                
                    <div class="flex justify-between items-center mb-3">
                        <span class="font-bold">Orden #{{ $order->id }}</span>
                        <span class="text-sm text-zinc-500">{{ $order->created_at->format('H:i') }}</span>
                    </div>
                    <div class="mb-3 space-y-1">
                        <p class="text-sm font-semibold">Cliente: {{ $order->customer_name ?? 'N/A' }}</p>
                        <p class="text-sm">Servicio: {{ $order->table_label }}</p>
                        @if($order->status === 'pendiente')
                            <span class="inline-flex items-center rounded-full bg-yellow-100 px-2 py-1 text-xs font-semibold text-yellow-700">Pendiente</span>
                        @elseif($order->status === 'en_preparacion')
                            <span class="inline-flex items-center rounded-full bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">En Preparación</span>
                        @elseif($order->status === 'listo')
                            <span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-1 text-xs font-semibold text-indigo-700">Listo</span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-zinc-100 px-2 py-1 text-xs font-semibold text-zinc-700">{{ ucfirst($order->status) }}</span>
                        @endif

                        <div class="text-xs text-zinc-500 mt-2">
                            @if($order->status === 'listo' && $order->preparation_seconds)
                                Tiempo: {{ gmdate('H:i:s', $order->preparation_seconds) }}
                            @else
                                <span id="timer-{{ $order->id }}">00:00</span>
                            @endif
                        </div>
                    </div>
                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($order->items as $item)
                            <li class="py-2 flex justify-between">
                                <span>{{ $item->quantity }}x {{ $item->product->name }}</span>
                            </li>
                        @endforeach
                    </ul>
                    <div class="mt-3 flex gap-2">
                        @if($order->status === 'pendiente')
                            <form action="{{ route('kitchen.prepare', $order) }}" method="POST">
                                @csrf
                                <flux:button type="submit" size="sm">En Preparación</flux:button>
                            </form>
                        @endif

                        @if($order->status === 'en_preparacion')
                            <form action="{{ route('kitchen.ready', $order) }}" method="POST">
                                @csrf
                                <flux:button type="submit" size="sm" variant="primary" color="green">Listo</flux:button>
                            </form>
                        @endif
                    </div>
                </a>
            @endforeach
        </div>
    </div>

    <script>
        // Start timers for all visible orders that are not yet 'listo'
        (function(){
            const orders = [
                @foreach($orders as $order)
                    @if($order->status !== 'listo')
                        { id: {{ $order->id }}, created: new Date("{{ $order->created_at->toIsoString() }}") },
                    @endif
                @endforeach
            ];

            function pad(n){return n.toString().padStart(2,'0');}

            orders.forEach(o => {
                const el = document.getElementById('timer-' + o.id);
                if (!el) return;
                function update(){
                    const diff = Math.max(0, Date.now() - o.created.getTime());
                    const totalSeconds = Math.floor(diff/1000);
                    const s = totalSeconds % 60;
                    const m = Math.floor(totalSeconds / 60) % 60;
                    const h = Math.floor(totalSeconds / 3600);
                    if (h > 0) {
                        el.textContent = pad(h)+ ':' + pad(m) + ':' + pad(s);
                    } else {
                        el.textContent = pad(m) + ':' + pad(s);
                    }
                }
                update();
                setInterval(update, 1000);
            });
        })();
    </script>
</x-layouts.app>
