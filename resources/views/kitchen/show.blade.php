<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Detalle de Orden #{{ $order->id }}</h1>
            <a href="{{ route('kitchen.index') }}" class="text-zinc-500 hover:text-zinc-800">✕</a>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <p class="text-sm font-semibold">Cliente: {{ $order->customer_name ?? 'N/A' }}</p>
                    <p class="text-sm">Servicio: {{ $order->table_label }}</p>
                    <p class="text-sm text-zinc-500 mt-1">Mozo: <span class="font-medium">{{ $order->user->name }}</span></p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-zinc-500">Tiempo</p>
                    <p id="kitchen-timer" class="font-mono font-medium">00:00</p>
                </div>
            </div>

            <h2 class="text-lg font-semibold mb-3">Productos y Comentarios</h2>
            <ul class="divide-y divide-zinc-200 dark:divide-zinc-700">
                @foreach($order->items as $item)
                    <li class="py-3">
                        <div class="font-medium">{{ $item->quantity }}x {{ $item->product->name }}</div>
                        @if($item->comment)
                            <p class="mt-1 text-sm text-blue-600">📝 {{ $item->comment }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>

            @if($order->status === 'listo' && $order->preparation_seconds)
                <div class="mt-3 text-sm text-zinc-600">Tiempo de preparación: <span class="font-medium">{{ gmdate('H:i:s', $order->preparation_seconds) }}</span></div>
            @endif
        </div>
    </div>

    <script>
        // Simple elapsed timer since order creation
        (function(){
            const created = new Date("{{ $order->created_at->toIsoString() }}");
            const el = document.getElementById('kitchen-timer');
            function pad(n){return n.toString().padStart(2,'0');}
            function update(){
                const diff = Math.max(0, Date.now() - created.getTime());
                const totalSeconds = Math.floor(diff/1000);
                const s = totalSeconds % 60;
                const m = Math.floor(totalSeconds / 60) % 60;
                const h = Math.floor(totalSeconds / 3600);
                if (h > 0) {
                    el.textContent = pad(h) + ':' + pad(m) + ':' + pad(s);
                } else {
                    el.textContent = pad(m) + ':' + pad(s);
                }
            }
            update();
            setInterval(update, 1000);
        })();
    </script>
</x-layouts.app>
