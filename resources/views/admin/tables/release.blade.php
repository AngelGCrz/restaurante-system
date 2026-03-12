<x-layouts.app :title="'Liberar Mesas'">
<div class="flex h-full w-full flex-1 flex-col gap-6 p-6">

    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold dark:text-white">🔓 Liberar Mesas</h1>
            <p class="text-sm text-zinc-500 mt-1">Cancela y libera mesas que tienen órdenes pendientes bloqueadas.</p>
        </div>
        <a href="{{ route('admin.tables.edit') }}"
            class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300 flex items-center gap-1">
            ← Volver a Mesas
        </a>
    </div>

    {{-- Alerta de éxito --}}
    @if(session('success'))
    <div class="rounded-xl bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 text-green-800 dark:text-green-300 px-5 py-4 flex items-center gap-3">
        <span class="text-xl">✅</span>
        <p class="text-sm font-medium">{{ session('success') }}</p>
    </div>
    @endif

    {{-- Info box --}}
    <div class="rounded-xl bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 px-5 py-4 flex items-start gap-3">
        <span class="text-xl mt-0.5">⚠️</span>
        <div>
            <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-300">¿Cuándo usar esta función?</p>
            <p class="text-xs text-yellow-700 dark:text-yellow-400 mt-1">
                Úsala cuando una mesa quede bloqueada por un pedido que no se puede completar (cliente se fue, sistema colgado, orden sin imprimir, etc.).
                Todas las órdenes pendientes de la mesa serán canceladas con el motivo <strong>"Liberado desde administración"</strong>.
            </p>
        </div>
    </div>

    {{-- Mesas ocupadas --}}
    @if($mesasOcupadas->isEmpty())
    <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 p-12 text-center">
        <div class="text-5xl mb-4">🪑</div>
        <p class="text-lg font-semibold dark:text-white">No hay mesas bloqueadas</p>
        <p class="text-sm text-zinc-400 mt-1">Todas las mesas están libres o ya fueron cobradas.</p>
    </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
        @foreach($mesasOcupadas as $tableKey => $orders)
        @php
            $tableNums    = explode(',', $tableKey);
            $label        = count($tableNums) > 1 ? 'Mesas ' . $tableKey : 'Mesa ' . $tableKey;
            $totalMesa    = $orders->sum(fn($o) => $o->items->sum(fn($it) => $it->price * $it->quantity));
            $oldestOrder  = $orders->sortBy('created_at')->first();
            $minutosEsper = $oldestOrder ? now()->diffInMinutes($oldestOrder->created_at) : 0;
        @endphp

        <div class="rounded-xl border border-red-200 dark:border-red-800 bg-white dark:bg-zinc-800 overflow-hidden shadow-sm">

            {{-- Card header --}}
            <div class="bg-red-50 dark:bg-red-900/20 px-5 py-4 border-b border-red-100 dark:border-red-800 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-bold dark:text-white flex items-center gap-2">
                        🪑 {{ $label }}
                        <span class="text-xs bg-red-100 dark:bg-red-800 text-red-700 dark:text-red-300 px-2 py-0.5 rounded-full font-semibold">
                            Bloqueada
                        </span>
                    </h2>
                    <p class="text-xs text-zinc-500 mt-0.5">
                        {{ $orders->count() }} orden(es) · Esperando {{ $minutosEsper }}min
                    </p>
                </div>
                <div class="text-right">
                    <p class="text-xs text-zinc-400">Total pendiente</p>
                    <p class="text-xl font-bold text-red-600 dark:text-red-400">S/ {{ number_format($totalMesa, 2) }}</p>
                </div>
            </div>

            {{-- Órdenes de la mesa --}}
            <div class="px-5 py-3 space-y-2">
                @foreach($orders->sortBy('created_at') as $order)
                <div class="flex items-center justify-between text-sm py-1.5 border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                    <div>
                        <span class="font-semibold dark:text-white">#{{ $order->id }}</span>
                        @if($order->origin_order_id)
                            <span class="text-xs text-yellow-600 ml-1">+adicional</span>
                        @endif
                        <span class="text-zinc-400 text-xs ml-2">{{ $order->created_at->format('H:i') }}</span>
                        @if(!$order->kitchen_printed_at)
                            <span class="ml-1 text-xs bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400 px-1.5 py-0.5 rounded-full">Sin imprimir</span>
                        @endif
                    </div>
                    <span class="font-semibold dark:text-zinc-300">
                        S/ {{ number_format($order->items->sum(fn($it) => $it->price * $it->quantity), 2) }}
                    </span>
                </div>
                @endforeach
            </div>

            {{-- Botón liberar --}}
            <div class="px-5 pb-5 pt-2">
                <form action="{{ route('admin.tables.release.confirm') }}" method="POST"
                    onsubmit="return confirm('¿Liberar {{ $label }}?\n\nTodas sus órdenes pendientes serán canceladas con motivo: \"Liberado desde administración\".\n\nEsta acción no se puede deshacer.')">
                    @csrf
                    <input type="hidden" name="table_key" value="{{ $tableKey }}">
                    <button type="submit"
                        class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2.5 rounded-lg text-sm transition flex items-center justify-center gap-2">
                        🔓 Liberar {{ $label }}
                    </button>
                </form>
            </div>
        </div>
        @endforeach
    </div>
    @endif

</div>
</x-layouts.app>