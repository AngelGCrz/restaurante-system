<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Monitor de Cocina</h1>
            <div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">
                <span id="polling-indicator" class="inline-block w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
                <span id="polling-status">En vivo</span>
            </div>
        </div>

        <div id="orders-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
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

                    @if(!empty($order->origin_order_id))
                        <div class="mb-3 rounded-lg bg-yellow-50 p-2 text-sm text-yellow-700 italic">
                            Agregado a la orden: #{{ $order->origin_order_id }}
                        </div>
                    @endif

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

    {{-- Notificación de nuevo pedido --}}
    <div id="new-order-toast"
         class="hidden fixed bottom-6 right-6 z-50 flex items-center gap-3 rounded-xl bg-green-600 text-white px-5 py-4 shadow-xl text-sm font-semibold">
        🔔 <span id="new-order-toast-text">Nuevo pedido recibido</span>
    </div>

<script>
(function () {
    // ─── ESTADO DEL MÓDULO ───────────────────────────────────────────────────────
    // Usamos una variable global para poder limpiar el intervalo cuando Livewire
    // desmonta la página (wire:navigate) y evitar que se acumulen intervalos.
    if (window._kitchenInterval) {
        clearInterval(window._kitchenInterval);
        window._kitchenInterval = null;
    }

    const POLL_INTERVAL = 5000;

    // IDs de órdenes ya impresas (persistidas en localStorage)
    function getPrintedOrders() {
        try {
            return new Set(JSON.parse(localStorage.getItem('kitchen_printed_orders') || '[]').map(String));
        } catch { return new Set(); }
    }

    // NOTA: NO ocultamos las tarjetas del servidor al cargar.
    // Si el servidor las devuelve como pendientes, deben mostrarse siempre.
    // El filtro de "impresas" solo aplica a tarjetas nuevas que llegan via polling.

    // ─── POLLING ─────────────────────────────────────────────────────────────────
    async function pollKitchen() {
        const grid = document.getElementById('orders-grid');
        if (!grid) return; // La página fue desmontada

        try {
            const resp = await fetch('/kitchen/poll', { credentials: 'same-origin' });
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            const data = await resp.json();

            const printed     = getPrintedOrders();
            const serverIds   = new Set(data.orders.map(o => String(o.id)));
            const displayedIds = new Set(
                [...grid.querySelectorAll('.order-card')].map(c => c.dataset.orderId)
            );

            let hasNew = false;

            // Agregar nuevas tarjetas
            data.orders.forEach(order => {
                const id = String(order.id);
                if (displayedIds.has(id)) return;

                const card = buildOrderCard(order);
                grid.prepend(card);

                card.style.opacity   = '0';
                card.style.transform = 'translateY(-12px)';
                card.style.transition = 'opacity 0.4s ease, transform 0.4s ease';
                requestAnimationFrame(() => {
                    card.style.opacity   = '1';
                    card.style.transform = 'translateY(0)';
                });

                // No ocultar - si el servidor la envía es porque sigue pendiente
                hasNew = true;
            });

            // Remover tarjetas de pedidos que ya no son pendientes
            displayedIds.forEach(id => {
                if (!serverIds.has(id)) {
                    const card = grid.querySelector(`.order-card[data-order-id="${id}"]`);
                    if (card) {
                        card.style.transition = 'opacity 0.4s ease';
                        card.style.opacity    = '0';
                        setTimeout(() => card.remove(), 400);
                    }
                }
            });

            if (hasNew) showToast('🔔 Nuevo pedido recibido');

            // Limpiar del localStorage IDs que ya no están en el servidor
            try {
                const stored = JSON.parse(localStorage.getItem('kitchen_printed_orders') || '[]');
                const cleaned = stored.filter(id => serverIds.has(String(id)));
                localStorage.setItem('kitchen_printed_orders', JSON.stringify(cleaned));
            } catch {}
            setIndicator('online');

        } catch (err) {
            console.error('[Kitchen Poll]', err);
            setIndicator('offline');
        }
    }

    // ─── HELPERS ─────────────────────────────────────────────────────────────────
    function buildOrderCard(order) {
        const div = document.createElement('div');
        div.className   = 'order-card rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 ring-2 ring-green-400';
        div.dataset.orderId = order.id;

        const originBadge  = order.origin_order_id
            ? `<div class="mb-3 rounded-lg bg-yellow-50 p-2 text-sm text-yellow-700 italic">Agregado a la orden: #${order.origin_order_id}</div>`
            : '';
        const commentBadge = order.comment
            ? `<div class="mb-3 rounded-lg bg-blue-50 p-2 text-sm text-blue-700 italic">🗒️ ${escapeHtml(order.comment)}</div>`
            : '';
        const itemsHtml = order.items.map(item => `
            <li class="py-2">
                <div class="flex justify-between">
                    <span>${item.quantity}x ${escapeHtml(item.product_name)}</span>
                </div>
                ${item.comment ? `<p class="mt-1 text-sm text-zinc-500 italic">📝 ${escapeHtml(item.comment)}</p>` : ''}
            </li>`).join('');

        div.innerHTML = `
            <div class="flex justify-between items-center mb-3">
                <span class="font-bold">Orden #${order.id}</span>
                <div class="flex items-center gap-2">
                    <span class="text-sm text-zinc-500">${order.created_at}</span>
                    <button type="button" onclick="printKitchenOrder(this)"
                        class="rounded-md bg-blue-600 px-3 py-1 text-sm text-white hover:bg-blue-700">🖨️</button>
                </div>
            </div>
            <div class="mb-3 space-y-1">
                <p class="text-sm font-semibold">Cliente: ${escapeHtml(order.customer_name)}</p>
                <p class="text-sm">Servicio: ${escapeHtml(order.table_label)}</p>
            </div>
            ${originBadge}${commentBadge}
            <ul class="divide-y divide-zinc-100 dark:divide-zinc-700">${itemsHtml}</ul>`;

        setTimeout(() => div.classList.remove('ring-2', 'ring-green-400'), 3000);
        return div;
    }

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function setIndicator(state) {
        const dot = document.getElementById('polling-indicator');
        const txt = document.getElementById('polling-status');
        if (!dot || !txt) return;
        if (state === 'online') {
            dot.className    = 'inline-block w-2 h-2 rounded-full bg-green-500 animate-pulse';
            txt.textContent  = 'En vivo';
        } else {
            dot.className    = 'inline-block w-2 h-2 rounded-full bg-red-500';
            txt.textContent  = 'Sin conexión';
        }
    }

    let toastTimeout;
    function showToast(msg) {
        const toast = document.getElementById('new-order-toast');
        if (!toast) return;
        document.getElementById('new-order-toast-text').textContent = msg;
        toast.classList.remove('hidden');
        clearTimeout(toastTimeout);
        toastTimeout = setTimeout(() => toast.classList.add('hidden'), 2000);
    }

    // ─── INICIAR POLLING ─────────────────────────────────────────────────────────
    window._kitchenInterval = setInterval(pollKitchen, POLL_INTERVAL);

    // Limpiar intervalo cuando Livewire navega a otra página (wire:navigate)
    document.addEventListener('livewire:navigating', function cleanup() {
        if (window._kitchenInterval) {
            clearInterval(window._kitchenInterval);
            window._kitchenInterval = null;
        }
        document.removeEventListener('livewire:navigating', cleanup);
    }, { once: true });

    // ─── IMPRESIÓN ───────────────────────────────────────────────────────────────
    window.printKitchenOrder = async function (button) {
        const orderCard = button.closest('.order-card');
        const orderId   = orderCard?.dataset?.orderId;
        if (!orderId) return alert('Pedido no identificado');

        const responseOrder = await getOrder(orderId);
        if (!responseOrder) return;

        const order      = responseOrder.order;
        const orderitems = order.items.map(i => ({
            item_quantity:        i.quantity,
            item_product_name:    i.product.name,
            item_product_comment: i.comment,
        }));

        const pedidobody = {
            order_id:           order.id,
            order_created_at:   order.created_at,
            order_customer_name: order.customer_name,
            order_table_label:  order.table_label,
            order_comment:      order.comment,
            items:              orderitems,
        };

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';

        // 1) Marcar en servidor como impresa; si falla, no ocultar la tarjeta.
        try {
            const markResp = await fetch(`/kitchen/${orderId}/mark-printed`, {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body:        JSON.stringify({}),
            });

            if (!markResp.ok) {
                throw new Error('No se pudo marcar como impresa');
            }
        } catch (e) {
            console.error('[mark-printed]', e);
            alert('Error al marcar como impresa. Intenta de nuevo.');
            return;
        }

        // 2) Enviar al agente de impresión (WebSocket) — no bloqueante
        try {
            const socket    = new WebSocket('ws://localhost:3000');
            socket.onopen   = () => socket.send(JSON.stringify({ action: 'print-ticket', pedido: pedidobody }));
            socket.onmessage = e => console.log('Respuesta:', JSON.parse(e.data));
        } catch (e) {
            console.warn('WebSocket print failed', e);
        }

        // 3) Ocultar la tarjeta sólo después de que el servidor confirmó el marcado
        orderCard.style.transition = 'opacity 0.4s ease';
        orderCard.style.opacity    = '0';
        setTimeout(() => { orderCard.style.display = 'none'; }, 400);

        const printed = JSON.parse(localStorage.getItem('kitchen_printed_orders') || '[]');
        if (!printed.includes(orderId)) {
            printed.push(orderId);
            localStorage.setItem('kitchen_printed_orders', JSON.stringify(printed));
        }
    };

    async function getOrder(orderId) {
        try {
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
            const resp = await fetch(`/kitchen/${orderId}/print`, {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body:        JSON.stringify({}),
            });
            if (!resp.ok) throw new Error('Error al obtener pedido');
            return await resp.json();
        } catch (e) {
            console.error(e);
            return null;
        }
    }
})();
</script>
</x-layouts.app>
