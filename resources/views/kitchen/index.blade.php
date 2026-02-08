<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Monitor de Cocina</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($orders as $order)
                <div class="order-card rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800" data-order-id="{{ $order->id }}">
                    <div class="flex justify-between items-center mb-3">
                        <span class="font-bold">Orden #{{ $order->id }}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-zinc-500">{{ $order->created_at->format('H:i') }}</span>
                            <button type="button"
                                onclick="printKitchenOrder(this)"
                                class="rounded-md bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700">
                                🖨️ 
                            </button>
                        </div>
                    </div>

                    <div class="mb-3 space-y-1">
                        <p class="text-sm font-semibold">Cliente: {{ $order->customer_name ?? 'N/A' }}</p>
                        <p class="text-sm">Servicio: {{ $order->table_label }}</p>
                    </div>

                    {{-- Comentario general del pedido --}}
                    @if(!empty($order->comment))
                        <div class="mb-3 rounded-lg bg-blue-50 p-2 text-sm text-blue-700 italic">
                            🗒️ {{ $order->comment }}
                        </div>
                    @endif

                    <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        @foreach($order->items as $item)
                            <li class="py-2">
                                <div class="flex justify-between">
                                    <span>{{ $item->quantity }}x {{ $item->product->name }}</span>
                                </div>

                                @if(!empty($item->comment))
                                    <p class="mt-1 text-sm text-zinc-500 italic">
                                        📝 {{ $item->comment }}
                                    </p>
                                @endif
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach
        </div>
    </div>

<script>
    async function printKitchenOrder(button) {
        const orderCard = button.closest('.order-card');
        const orderId = orderCard?.dataset?.orderId;
        if (!orderId) return alert('Pedido no identificado');

        button.disabled = true;
        const originalText = button.innerText;
        button.innerText = 'Print';

        try {
            const tokenMeta = document.querySelector('meta[name="csrf-token"]');
            const tokenInput = document.querySelector('input[name="_token"]');
            const csrfToken = tokenMeta ? tokenMeta.getAttribute('content') : (tokenInput ? tokenInput.value : (window?.Laravel?.csrfToken || ''));

            const headers = { 'Content-Type': 'application/json' };
            if (csrfToken) headers['X-CSRF-TOKEN'] = csrfToken;

            const resp = await fetch("{{ url('') }}" + `/kitchen/${orderId}/print`, {
                method: 'POST',
                headers,
                body: JSON.stringify({})
            });

            if (!resp.ok) {
                const err = await resp.json().catch(() => null);
                throw new Error(err?.message || 'Error al imprimir');
            }

            // Mostrar confirmación breve
            button.innerText = 'Enviado';
            setTimeout(() => location.reload(), 700);
        } catch (e) {
            console.error(e);
            alert('No se pudo imprimir: ' + (e.message || e));
            button.disabled = false;
            button.innerText = originalText;
        }
    }
</script>

    {{-- <script>
    function printOrder(button) {
        const orderCard = button.closest('.order-card');
        const printContents = orderCard.innerHTML;
        const originalContents = document.body.innerHTML;

        document.body.innerHTML =
            '<div class="ticket">' +
                printContents +
            '</div>';

        window.print();
        document.body.innerHTML = originalContents;
        location.reload();
    }
    </script> --}}
</x-layouts.app>
