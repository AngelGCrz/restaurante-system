<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">

        <form
            action="{{ route('mozo.orders.add-items.store', $order) }}"
            method="POST"
            class="grid grid-cols-1 gap-6 lg:grid-cols-3"
            x-data='addItemsComponent({
                products: @json($products ?? []),
                categories: @json($categories ?? []),
                isTakeaway: {{ $order->type === "llevar" ? "true" : "false" }},
            })'
        >
            @csrf

            {{-- Barra superior móvil: total en tiempo real --}}
            <div x-show="selectedList.length" x-cloak
                 class="md:hidden sticky top-0 z-40 w-full bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between gap-3 px-4 py-2">
                    <div class="flex items-baseline gap-3">
                        <div class="text-xs text-zinc-500 dark:text-zinc-300">Total a agregar</div>
                        <div class="text-lg font-semibold text-red-600 dark:text-red-400" x-text="currency(previewTotal)"></div>
                    </div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-300" x-text="itemCount + ' ítems'"></div>
                </div>
            </div>

            {{-- Columna izquierda: categorías + productos --}}
            <div class="space-y-6 lg:col-span-2">

                {{-- Header del pedido --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800 flex items-center justify-between">
                    <div>
                        <h1 class="text-xl font-bold dark:text-white">Agregar a Pedido #{{ $order->id }}</h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">
                            {{ $order->table_label }}
                            @if($order->customer_name)
                                · {{ $order->customer_name }}
                            @endif
                        </p>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        {{ $order->status === 'pagado' ? 'bg-green-100 text-green-700' :
                           ($order->status === 'pendiente' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700') }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>

                {{-- Filtros de categoría (oculta cat 4 = Entradas, aparece embebida debajo) --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <template x-for="cat in categories" :key="cat.id">
                            <button
                                x-show="cat.id !== 4"
                                type="button"
                                class="rounded-full border px-3 py-1 text-sm font-medium transition hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-700"
                                :class="currentCategory === cat.id
                                    ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-black'
                                    : ''"
                                @click="currentCategory = cat.id"
                                x-text="cat.name"
                            ></button>
                        </template>
                    </div>

                    <h2 class="mb-4 text-lg font-semibold dark:text-white">Productos Disponibles</h2>

                    {{-- Vista especial para categorías 1, 2, 5: Entradas arriba + plato abajo --}}
                    <template x-if="currentCategory === 1 || currentCategory === 2 || currentCategory === 5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-widest text-red-500 mb-2">── Entradas ──</p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 mb-6">
                                <template x-for="product in products.filter(p => p.category_id == 4)" :key="product.id">
                                    <button
                                        type="button"
                                        :disabled="product.sold_out"
                                        :class="product.sold_out
                                            ? 'opacity-50 cursor-not-allowed relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900'
                                            : 'relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm transition active:scale-95 dark:border-zinc-700 dark:bg-zinc-900'"
                                        @click="addProduct(product)"
                                    >
                                        <div class="absolute right-2 top-2" x-show="selectedMap[mapKey(product)]" x-cloak>
                                            <span class="inline-flex min-w-[32px] justify-center rounded-full bg-emerald-600 px-2 py-1 text-xs font-semibold text-white"
                                                  x-text="selectedMap[mapKey(product)]?.quantity"></span>
                                        </div>
                                        <p class="font-semibold text-sm leading-tight" x-text="product.name"></p>
                                        <p class="text-xs text-zinc-500" x-text="currency(entradaPrice(product))"></p>
                                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 mt-2"
                                              x-show="product.sold_out" x-cloak>Agotado</span>
                                        <p class="text-xs text-rose-600 mt-2"
                                           x-show="!product.sold_out && product.low_stock" x-cloak
                                           x-text="'Quedan ' + (product.stock ?? 0)"></p>
                                    </button>
                                </template>
                            </div>

                            <p class="text-xs font-semibold uppercase tracking-widest text-red-500 mb-2"
                               x-text="currentCategory === 1 ? '── Segundos ──' : currentCategory === 2 ? '── Extras ──' : '── Porciones ──'"></p>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                <template x-for="product in products.filter(p => currentCategory === 1 ? p.category_id == 1 : p.category_id == currentCategory)" :key="product.id">
                                    <button
                                        type="button"
                                        :disabled="product.sold_out"
                                        :class="product.sold_out
                                            ? 'opacity-50 cursor-not-allowed relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900'
                                            : 'relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm transition active:scale-95 dark:border-zinc-700 dark:bg-zinc-900'"
                                        @click="addProduct(product)"
                                    >
                                        <div class="absolute right-2 top-2" x-show="selectedMap[mapKey(product)]" x-cloak>
                                            <span class="inline-flex min-w-[32px] justify-center rounded-full bg-emerald-600 px-2 py-1 text-xs font-semibold text-white"
                                                  x-text="selectedMap[mapKey(product)]?.quantity"></span>
                                        </div>
                                        <p class="font-semibold text-sm leading-tight" x-text="product.name"></p>
                                        <p class="text-xs text-zinc-500" x-text="currency(product.price)"></p>
                                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 mt-2"
                                              x-show="product.sold_out" x-cloak>Agotado</span>
                                        <p class="text-xs text-rose-600 mt-2"
                                           x-show="!product.sold_out && product.low_stock" x-cloak
                                           x-text="'Quedan ' + (product.stock ?? 0)"></p>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    {{-- Vista normal para las demás categorías --}}
                    <template x-if="currentCategory !== 1 && currentCategory !== 2 && currentCategory !== 5">
                        <div>
                            <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                                <template x-for="product in filteredProducts" :key="product.id">
                                    <button
                                        type="button"
                                        :disabled="product.sold_out"
                                        :class="product.sold_out
                                            ? 'opacity-50 cursor-not-allowed relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900'
                                            : 'relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm transition active:scale-95 dark:border-zinc-700 dark:bg-zinc-900'"
                                        @click="addProduct(product)"
                                    >
                                        <div class="absolute right-2 top-2" x-show="selectedMap[mapKey(product)]" x-cloak>
                                            <span class="inline-flex min-w-[32px] justify-center rounded-full bg-emerald-600 px-2 py-1 text-xs font-semibold text-white"
                                                  x-text="selectedMap[mapKey(product)]?.quantity"></span>
                                        </div>
                                        <p class="font-semibold text-sm leading-tight" x-text="product.name"></p>
                                        <p class="text-xs text-zinc-500" x-text="currency(product.price)"></p>
                                        <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 mt-2"
                                              x-show="product.sold_out" x-cloak>Agotado</span>
                                        <p class="text-xs text-rose-600 mt-2"
                                           x-show="!product.sold_out && product.low_stock" x-cloak
                                           x-text="'Quedan ' + (product.stock ?? 0)"></p>
                                    </button>
                                </template>
                                <p x-show="!filteredProducts.length" class="col-span-full text-sm text-zinc-500" x-cloak>
                                    No hay productos en esta categoría.
                                </p>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Columna derecha: seleccionados + opciones --}}
            <div class="space-y-6">

                {{-- Productos seleccionados --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-lg font-semibold dark:text-white">Productos a agregar</h2>
                        <button type="button" class="text-sm text-rose-600 hover:underline"
                                @click="selectedMap = {}" x-show="selectedList.length" x-cloak>
                            Vaciar
                        </button>
                    </div>

                    <div class="space-y-3" x-show="selectedList.length" x-cloak>
                        <template x-for="item in selectedList" :key="item.mapKey">
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div>
                                    <p class="font-medium dark:text-white" x-text="item.name"></p>
                                    <p class="text-xs text-zinc-500" x-text="currency(item.price)"></p>
                                    <p class="text-xs text-blue-600 italic mt-0.5"
                                       x-show="item.comment" x-cloak x-text="'📝 ' + item.comment"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button"
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-zinc-300 text-lg leading-none dark:border-zinc-600"
                                            @click="decrement(item.mapKey)">-</button>
                                    <span class="min-w-[24px] text-center text-sm font-semibold dark:text-white"
                                          x-text="item.quantity"></span>
                                    <button type="button"
                                            class="flex h-8 w-8 items-center justify-center rounded-full border border-zinc-300 text-lg leading-none dark:border-zinc-600"
                                            @click="increment(item.mapKey)">+</button>
                                    <button type="button"
                                            class="text-xs text-blue-600 underline"
                                            @click="openCommentModal(item)">📝</button>
                                </div>
                            </div>
                        </template>

                        {{-- Subtotal --}}
                        <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700 flex justify-between font-semibold dark:text-white">
                            <span>Subtotal</span>
                            <span x-text="currency(previewTotal)"></span>
                        </div>
                    </div>

                    <p class="text-sm text-zinc-500" x-show="!selectedList.length">
                        Toca un producto para agregarlo.
                    </p>

                    {{-- Inputs ocultos --}}
                    <template x-for="(item, index) in selectedList" :key="`hidden-${item.mapKey}`">
                        <div>
                            <input type="hidden" :name="`items[${index}][product_id]`" :value="item.id">
                            <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                            <input type="hidden" :name="`items[${index}][price]`" :value="item.price">
                            <input type="hidden" :name="`items[${index}][comment]`" :value="item.comment || ''">
                        </div>
                    </template>
                </div>

                {{-- Para llevar + botones --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 space-y-4">
                    <div class="flex items-center justify-between">
                        <span class="text-sm font-medium dark:text-white">Para llevar</span>
                        <button type="button"
                                @click="toggleTakeaway()"
                                :class="takeaway
                                    ? 'bg-indigo-600 text-white'
                                    : 'bg-zinc-200 text-zinc-700 dark:bg-zinc-700 dark:text-white'"
                                class="px-4 py-1.5 rounded-full text-sm font-semibold transition">
                            <span x-text="takeaway ? '🥡 Sí' : 'No'"></span>
                        </button>
                    </div>

                    <input type="hidden" name="takeaway" :value="takeaway ? 1 : 0">

                    <hr class="dark:border-zinc-700">

                    <button
                        type="submit"
                        class="w-full rounded-lg bg-zinc-900 px-4 py-2 font-semibold text-white transition hover:bg-zinc-700 disabled:opacity-50 dark:bg-white dark:text-black dark:hover:bg-zinc-200"
                        :disabled="!selectedList.length"
                    >
                        Agregar al Pedido
                    </button>

                    <flux:button
                        href="{{ route('mozo.orders.show', $order) }}"
                        variant="subtle"
                        class="w-full"
                    >
                        Cancelar
                    </flux:button>
                </div>
            </div>

            {{-- Modal comentario por ítem --}}
            <div x-show="showCommentModal" x-cloak
                 class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 w-full max-w-md mx-4">
                    <h3 class="text-lg font-semibold mb-2 dark:text-white">
                        Nota para: <span class="font-bold" x-text="currentCommentItem?.name"></span>
                    </h3>
                    <textarea rows="3"
                              class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900 dark:text-white"
                              placeholder="Ej: sin sal, término medio..."
                              x-model="currentCommentText"></textarea>
                    <div class="mt-4 flex justify-end gap-2">
                        <button type="button"
                                class="px-4 py-2 rounded-lg border dark:border-zinc-600 dark:text-white"
                                @click="closeCommentModal">Cancelar</button>
                        <button type="button"
                                class="px-4 py-2 rounded-lg bg-zinc-900 text-white dark:bg-white dark:text-black"
                                @click="saveItemComment">Guardar</button>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <script>
        window.showToast = function(message, variant = 'error') {
            const containerId = 'app-toasts-container';
            let container = document.getElementById(containerId);
            if (!container) {
                container = document.createElement('div');
                container.id = containerId;
                container.style.cssText = 'position:fixed;right:16px;top:16px;z-index:9999';
                document.body.appendChild(container);
            }
            const toast = document.createElement('div');
            toast.textContent = message;
            toast.style.cssText = `margin-top:8px;padding:10px 14px;border-radius:8px;color:#fff;font-size:13px;` +
                `box-shadow:0 4px 16px rgba(0,0,0,.12);opacity:0;transition:opacity 200ms ease,transform 200ms ease;` +
                `background:${variant === 'success' ? '#16a34a' : '#dc2626'}`;
            container.appendChild(toast);
            toast.offsetWidth;
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';
            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-8px)';
                setTimeout(() => container.removeChild(toast), 300);
            }, 3000);
        };

        function addItemsComponent({ products = [], categories = [], isTakeaway = false }) {
            return {
                products,
                categories,
                currentCategory: 1,
                selectedMap: {},
                takeaway: isTakeaway,
                showCommentModal: false,
                currentCommentItem: null,
                currentCommentText: '',

                // ─── UTILIDADES DE PRECIO ───────────────────────────────────────────────

                mapKey(product) {
                    return product.category_id == 4
                        ? product.id + '_' + this.currentCategory
                        : String(product.id);
                },

                entradaPrice(product) {
                    const isCeviche = product.name.toLowerCase().includes('ceviche');
                    if (this.currentCategory === 1) return 0;
                    if (this.currentCategory === 2) return isCeviche ? 2 : 1;
                    if (this.currentCategory === 5) return isCeviche ? 5 : 4;
                    return Number(product.price);
                },

                computePrice(product) {
                    let price = product.category_id == 4
                        ? this.entradaPrice(product)
                        : Number(product.price);
                    if (this.takeaway && price > 9) price += 1;
                    return price;
                },

                recalcEntradaPrice(product, catId) {
                    const isCeviche = product.name.toLowerCase().includes('ceviche');
                    if (catId === 1) return 0;
                    if (catId === 2) return isCeviche ? 2 : 1;
                    if (catId === 5) return isCeviche ? 5 : 4;
                    return Number(product.price);
                },

                // ─── MAPA REACTIVO ──────────────────────────────────────────────────────

                _setMap(key, value) {
                    this.selectedMap = { ...this.selectedMap, [key]: value };
                },
                _delMap(key) {
                    const m = { ...this.selectedMap };
                    delete m[key];
                    this.selectedMap = m;
                },

                // ─── SELECCIÓN ──────────────────────────────────────────────────────────

                addProduct(product) {
                    if (product.sold_out) return;
                    const key      = this.mapKey(product);
                    const price    = this.computePrice(product);
                    const existing = this.selectedMap[key];
                    const newQty   = (existing ? existing.quantity : 0) + 1;

                    if (!product.allow_negative && typeof product.stock === 'number' && newQty > product.stock) {
                        showToast('Stock insuficiente para ' + product.name + '. Disponible: ' + product.stock);
                        return;
                    }

                    this._setMap(key, {
                        ...(existing ?? { ...product, comment: '' }),
                        id: product.id,
                        quantity: newQty,
                        price,
                        mapKey: key,
                    });
                },

                increment(key) {
                    const item = this.selectedMap[key];
                    if (!item) return;
                    const product = this.products.find(p => p.id == item.id) || item;
                    const newQty  = item.quantity + 1;
                    if (!product.allow_negative && typeof product.stock === 'number' && newQty > product.stock) {
                        showToast('Stock insuficiente para ' + item.name + '. Disponible: ' + product.stock);
                        return;
                    }
                    this._setMap(key, { ...item, quantity: newQty });
                },

                decrement(key) {
                    const item = this.selectedMap[key];
                    if (!item) return;
                    if (item.quantity <= 1) {
                        this._delMap(key);
                    } else {
                        this._setMap(key, { ...item, quantity: item.quantity - 1 });
                    }
                },

                // ─── PARA LLEVAR ─────────────────────────────────────────────────────────

                toggleTakeaway() {
                    this.takeaway = !this.takeaway;
                    const newMap = {};
                    Object.entries(this.selectedMap).forEach(([key, item]) => {
                        const product = this.products.find(p => p.id == item.id);
                        if (!product) return;
                        let basePrice;
                        if (product.category_id == 4) {
                            const catId = parseInt(key.split('_')[1]);
                            basePrice = this.recalcEntradaPrice(product, catId);
                        } else {
                            basePrice = Number(product.price);
                        }
                        if (this.takeaway && basePrice > 9) basePrice += 1;
                        newMap[key] = { ...item, price: basePrice };
                    });
                    this.selectedMap = newMap;
                },

                // ─── MODAL COMENTARIO ────────────────────────────────────────────────────

                openCommentModal(item) {
                    this.currentCommentItem = item;
                    this.currentCommentText = item.comment || '';
                    this.showCommentModal   = true;
                },
                closeCommentModal() {
                    this.showCommentModal   = false;
                    this.currentCommentItem = null;
                    this.currentCommentText = '';
                },
                saveItemComment() {
                    if (this.currentCommentItem) {
                        this._setMap(this.currentCommentItem.mapKey, {
                            ...this.selectedMap[this.currentCommentItem.mapKey],
                            comment: this.currentCommentText,
                        });
                    }
                    this.closeCommentModal();
                },

                // ─── COMPUTED ────────────────────────────────────────────────────────────

                get selectedList() {
                    return Object.values(this.selectedMap);
                },
                get previewTotal() {
                    return Object.values(this.selectedMap)
                        .reduce((sum, item) => sum + (Number(item.price) || 0) * (Number(item.quantity) || 0), 0);
                },
                get itemCount() {
                    return Object.values(this.selectedMap)
                        .reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
                },
                get filteredProducts() {
                    return this.products.filter(p => p.category_id == this.currentCategory);
                },

                currency(value) {
                    return 'S/ ' + Number(value).toFixed(2);
                },
            };
        }
    </script>
</x-layouts.app>
