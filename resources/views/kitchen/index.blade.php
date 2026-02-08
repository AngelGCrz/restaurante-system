<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Monitor de Cocina</h1>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($orders as $order)
                <div class="order-card rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="flex justify-between items-center mb-3">
                        <span class="font-bold">Orden #{{ $order->id }}</span>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-zinc-500">{{ $order->created_at->format('H:i') }}</span>
                            <button type="button"
                                onclick="printOrder(this)"
                                class="rounded-md bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700">
                                🖨️ Imprimir
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

    <style>
@media print {

    @page {
        size: 80mm auto;
        margin: 0;
    }

    body {
        margin: 0;
        font-family: monospace;
    }

    .ticket {
        width: 76mm;/*80*/
        padding: 2mm 2mm;/*5mm*/
        page-break-after: always; /* FUERZA EL CORTE */
    }

    h1, button {
        display: none !important;
    }

    .order-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    .order-card span,
    .order-card p {
        font-size: 11px;
        line-height: 1.2;
        white-space: normal;
        word-wrap: break-word;
    }

    .order-card .text-sm {
        font-size: 10px;
    }
    .order-card ul {
        padding-left: 0;
        margin-left: 0;
    }

    .order-card li {
        list-style: none;
    }
}
</style>


    {{-- <style>
@media print {

    @page {
        size: 80mm auto; /* usa 58mm auto si tu impresora es pequeña */
        margin: 0;
    }
    .ticket {
        page-break-after: always;
    }

    body {
        margin: 0;
        font-family: monospace;
    }

    .ticket {
        width: 80mm;
        padding: 15mm;
    }

    h1, button {
        display: none;
    }

    .order-card {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    .order-card span,
    .order-card p {
        font-size: 12px;
    }

    .order-card .text-sm {
        font-size: 11px;
    }
}
</style> --}}

<script>
function printOrder(button) {
    const orderCard = button.closest('.order-card');
    const printContents = orderCard.innerHTML;
    const originalContents = document.body.innerHTML;

    document.body.innerHTML = `
        <div class="ticket">
            ${printContents}
        </div>
    `;

    window.print();

    document.body.innerHTML = originalContents;
    location.reload();
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
