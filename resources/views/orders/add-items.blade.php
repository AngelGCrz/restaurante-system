<x-layouts.app>
    <div
        class="flex h-full w-full flex-1 flex-col gap-4 p-4"
        x-data='addItemsComponent({
            products: @json($products),
            categories: @json($categories->map(fn($c) => ["id" => $c->id, "name" => $c->name])),
            orderType: @json($order->type),
        })'
    >

        {{-- Encabezado --}}
        <div class="flex items-center gap-3">
            <a href="{{ route('mozo.orders.index') }}"
               class="inline-flex items-center justify-center rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:hover:bg-zinc-700">
                ← Volver
            </a>
            <div>
                <h1 class="text-xl font-bold leading-tight">
                    ➕ Agregar Nueva Orden — {{ $order->table_label }}
                </h1>
                <p class="text-sm text-zinc-500">
                    Pedido principal #{{ $rootOrder->id }}
                    @if($order->type === 'llevar')
                        · <span class="font-semibold text-indigo-600">🥡 Para Llevar</span>
                    @endif
                </p>
            </div>
        </div>

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4">
                <p class="font-semibold text-red-700 mb-1">Error al agregar productos:</p>
                <ul class="list-disc pl-4 text-sm text-red-600">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Resumen colapsable de lo ya pedido --}}
        <details class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
            <summary class="cursor-pointer select-none text-sm font-semibold text-zinc-600 dark:text-zinc-300">
                📋 Ver qué hay ya pedido en esta orden
            </summary>
            <div class="mt-3 space-y-2">
                @foreach($rootOrder->items as $it)
                    <div class="flex justify-between text-sm">
                        <span>{{ $it->quantity }}× {{ $it->product->name ?? 'Item' }}</span>
                        <span class="text-zinc-500">S/ {{ number_format($it->price * $it->quantity, 2) }}</span>
                    </div>
                @endforeach
                @foreach($rootOrder->childOrders->where('status', '!=', 'cancelado') as $child)
                    <div class="mt-2 border-t border-zinc-200 pt-2 dark:border-zinc-700">
                        <p class="mb-1 text-xs font-semibold text-yellow-600">
                            ➕ Sub-orden #{{ $child->id }}
                            @if($child->type === 'llevar') 🥡 @endif
                        </p>
                        @foreach($child->items as $it)
                            <div class="flex justify-between pl-2 text-sm">
                                <span>{{ $it->quantity }}× {{ $it->product->name ?? 'Item' }}</span>
                                <span class="text-zinc-500">S/ {{ number_format($it->price * $it->quantity, 2) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </details>

        {{-- Layout principal --}}
        <form
            action="{{ route('mozo.orders.add-items.store', $order) }}"
            method="POST"
            class="grid grid-cols-1 gap-6 lg:grid-cols-3"
            @submit.prevent="submitForm($el)"
        >
            @csrf

            {{-- Barra sticky móvil --}}
            <div x-show="selectedList.length > 0" x-cloak
                 class="md:hidden sticky top-0 z-40 -mx-4 w-[calc(100%+2rem)] bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 px-4 py-2 flex items-center justify-between">
                <div class="flex items-baseline gap-2">
                    <span class="text-xs text-zinc-500">Nueva orden:</span>
                    <span class="font-bold text-red-600" x-text="currency(previewTotal)"></span>
                </div>
                <span class="text-sm text-zinc-400" x-text="itemCount + ' ítem(s)'"></span>
            </div>

            {{-- Columna izquierda: productos --}}
            <div class="space-y-4 lg:col-span-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">

                    {{-- Filtro categorías --}}
                    <div class="mb-4 flex flex-wrap gap-2">
                        <button type="button"
                            class="rounded-full border px-3 py-1 text-sm font-medium transition hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-700"
                            :class="currentCategory === null ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-black' : ''"
                            @click="currentCategory = null">Todas</button>
                        <template x-for="cat in categories" :key="cat.id">
                            <button type="button"
                                class="rounded-full border px-3 py-1 text-sm font-medium transition hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-700"
                                :class="currentCategory === cat.id ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-black' : ''"
                                @click="currentCategory = cat.id"
                                x-text="cat.name"></button>
                        </template>
                    </div>

                    <h2 class="mb-3 text-base font-semibold">Productos Disponibles</h2>

                    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <button
                                type="button"
                                :disabled="product.sold_out"
                                @click="addProduct(product)"
                                :class="[
                                    'relative flex flex-col items-center justify-center rounded-md border p-2 text-center shadow-sm transition',
                                    product.sold_out
                                        ? 'cursor-not-allowed opacity-50 border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900'
                                        : 'border-zinc-200 bg-white hover:border-emerald-400 active:scale-95 dark:border-zinc-700 dark:bg-zinc-900',
                                    selectedMap[product.id] ? 'ring-2 ring-emerald-400 border-emerald-400' : ''
                                ]">
                                <div class="absolute right-1 top-1" x-show="selectedMap[product.id]" x-cloak>
                                    <span class="inline-flex min-w-[22px] justify-center rounded-full bg-emerald-600 px-1 py-0.5 text-xs font-bold text-white"
                                          x-text="selectedMap[product.id]?.quantity"></span>
                                </div>
                                <p class="text-sm font-semibold leading-tight" x-text="product.name"></p>
                                <p class="mt-0.5 text-xs text-zinc-500" x-text="currency(product.price)"></p>
                                <span x-show="product.sold_out" x-cloak class="mt-1 inline-flex rounded-full bg-rose-100 px-2 py-0.5 text-xs font-semibold text-rose-700">Agotado</span>
                                <p x-show="!product.sold_out && product.low_stock" x-cloak class="mt-1 text-xs text-orange-500" x-text="'Quedan ' + product.stock"></p>
                            </button>
                        </template>
                    </div>

                    <p x-show="filteredProducts.length === 0" x-cloak class="py-4 text-center text-sm text-zinc-400">
                        Sin productos disponibles en esta categoría.
                    </p>
                </div>
            </div>

            {{-- Columna derecha: opciones + resumen --}}
            <div class="space-y-4">

                {{-- Opción para llevar --}}
                @if($order->type === 'mesa')
                <div class="rounded-xl border border-indigo-300 bg-white p-4 dark:border-indigo-700 dark:bg-zinc-800">
                    <label class="flex cursor-pointer items-start gap-3">
                        <input type="checkbox" name="takeaway" value="1"
                               x-model="takeaway"
                               class="mt-0.5 h-5 w-5 rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500">
                        <div>
                            <p class="font-semibold text-indigo-700 dark:text-indigo-300">🥡 Esta orden es para llevar</p>
                            <p class="mt-0.5 text-xs text-indigo-500 dark:text-indigo-400">Los productos se enviarán marcados como para llevar</p>
                        </div>
                    </label>
                </div>
                @else
                    <input type="hidden" name="takeaway" value="0">
                    <div class="rounded-xl border border-indigo-300 bg-white p-4 dark:border-indigo-700 dark:bg-zinc-800">
                        <p class="font-semibold text-indigo-700 dark:text-indigo-300">🥡 Orden Para Llevar</p>
                        <p class="mt-0.5 text-xs text-indigo-500">Esta nueva sub-orden se agregará como para llevar.</p>
                    </div>
                @endif

                {{-- Resumen ítems seleccionados --}}
                <div class="rounded-xl border border-zinc-200 bg-white p-4 dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 class="mb-3 font-semibold">Nueva Orden</h2>

                    <p x-show="!selectedList.length" x-cloak class="py-3 text-center text-sm text-zinc-400">
                        Selecciona productos del menú ←
                    </p>

                    <div class="space-y-3" x-show="selectedList.length > 0" x-cloak>
                        <template x-for="item in selectedList" :key="item.id">
                            <div>
                                <input type="hidden" :name="'items[' + item.id + '][product_id]'" :value="item.id">
                                <input type="hidden" :name="'items[' + item.id + '][quantity]'" :value="item.quantity">
                                <input type="hidden" :name="'items[' + item.id + '][comment]'" :value="item.comment ?? ''">

                                <div class="flex items-center gap-2">
                                    <div class="flex items-center overflow-hidden rounded-lg border border-zinc-200 dark:border-zinc-600">
                                        <button type="button" class="px-2 py-1 text-sm font-bold hover:bg-zinc-100 dark:hover:bg-zinc-700" @click="decrement(item.id)">−</button>
                                        <span class="px-2 text-sm font-semibold" x-text="item.quantity"></span>
                                        <button type="button" class="px-2 py-1 text-sm font-bold hover:bg-zinc-100 dark:hover:bg-zinc-700" @click="increment(item.id)">+</button>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <p class="truncate text-sm font-medium" x-text="item.name"></p>
                                    </div>
                                    <p class="shrink-0 text-sm font-semibold" x-text="currency(item.price * item.quantity)"></p>
                                </div>
                                <div class="ml-20 mt-0.5">
                                    <button type="button"
                                            class="text-xs text-indigo-500 hover:text-indigo-700 dark:text-indigo-400"
                                            @click="openCommentModal(item)"
                                            x-text="item.comment ? '📝 ' + item.comment : '+ Agregar nota'">
                                    </button>
                                </div>
                            </div>
                        </template>

                        <div class="flex justify-between border-t border-zinc-200 pt-3 text-base font-bold dark:border-zinc-700">
                            <span>Total</span>
                            <span class="text-emerald-600" x-text="currency(previewTotal)"></span>
                        </div>
                    </div>
                </div>

                {{-- Botones --}}
                <button type="submit"
                        class="w-full rounded-xl bg-emerald-600 px-4 py-3 font-semibold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="selectedList.length === 0">
                    ✅ Confirmar y Enviar a Cocina
                </button>

                <a href="{{ route('mozo.orders.index') }}"
                   class="mt-1 block w-full rounded-xl border border-zinc-300 px-4 py-2.5 text-center text-sm font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700">
                    Cancelar
                </a>
            </div>
        </form>

        {{-- Modal comentario --}}
        <div x-show="showCommentModal" x-cloak
             class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
             @keydown.escape.window="closeCommentModal()">
            <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl dark:bg-zinc-800" @click.stop>
                <h3 class="mb-3 text-lg font-semibold">
                    Nota para: <span x-text="currentCommentItem?.name" class="text-indigo-600"></span>
                </h3>
                <textarea
                    x-model="currentCommentText"
                    class="w-full rounded-lg border border-zinc-300 p-3 text-sm dark:border-zinc-600 dark:bg-zinc-900"
                    rows="3"
                    placeholder="Ej: sin cebolla, término 3/4..."></textarea>
                <div class="mt-4 flex gap-3">
                    <button type="button"
                            class="flex-1 rounded-lg bg-indigo-600 py-2 font-semibold text-white hover:bg-indigo-700"
                            @click="saveItemComment()">Guardar</button>
                    <button type="button"
                            class="flex-1 rounded-lg border border-zinc-300 py-2 font-medium text-zinc-600 hover:bg-zinc-50 dark:border-zinc-600 dark:text-zinc-300 dark:hover:bg-zinc-700"
                            @click="closeCommentModal()">Cancelar</button>
                </div>
            </div>
        </div>

    </div>

    <script>
    window.addItemsComponent = function({ products, categories, orderType }) {
        return {
            products, categories, orderType,
            currentCategory: null,
            selectedMap: {},
            takeaway: false,
            showCommentModal: false,
            currentCommentItem: null,
            currentCommentText: '',

            addProduct(product) {
                if (product.sold_out) return;
                const existing = this.selectedMap[product.id] ?? { ...product, quantity: 0, comment: '' };
                const newQty = existing.quantity + 1;
                if (!product.allow_negative && typeof product.stock === 'number' && newQty > product.stock) {
                    window.showToast ? showToast('Stock insuficiente: ' + product.name) : alert('Stock insuficiente para ' + product.name);
                    return;
                }
                this.selectedMap = { ...this.selectedMap, [product.id]: { ...existing, quantity: newQty } };
            },
            increment(id) {
                if (!this.selectedMap[id]) return;
                const item = this.selectedMap[id];
                const product = this.products.find(p => p.id == id) || item;
                const newQty = item.quantity + 1;
                if (!product.allow_negative && typeof product.stock === 'number' && newQty > product.stock) {
                    window.showToast ? showToast('Stock insuficiente') : alert('Stock insuficiente');
                    return;
                }
                this.selectedMap = { ...this.selectedMap, [id]: { ...item, quantity: newQty } };
            },
            decrement(id) {
                if (!this.selectedMap[id]) return;
                const newQty = this.selectedMap[id].quantity - 1;
                if (newQty <= 0) {
                    const m = { ...this.selectedMap };
                    delete m[id];
                    this.selectedMap = m;
                } else {
                    this.selectedMap = { ...this.selectedMap, [id]: { ...this.selectedMap[id], quantity: newQty } };
                }
            },
            openCommentModal(item) {
                this.currentCommentItem = item;
                this.currentCommentText = item.comment || '';
                this.showCommentModal = true;
            },
            closeCommentModal() { this.showCommentModal = false; this.currentCommentItem = null; this.currentCommentText = ''; },
            saveItemComment() {
                if (this.currentCommentItem) {
                    const id = this.currentCommentItem.id;
                    this.selectedMap = { ...this.selectedMap, [id]: { ...this.selectedMap[id], comment: this.currentCommentText } };
                }
                this.closeCommentModal();
            },
            get selectedList() { return Object.values(this.selectedMap).filter(i => i.quantity > 0); },
            get filteredProducts() {
                return this.currentCategory === null ? this.products : this.products.filter(p => String(p.category_id) === String(this.currentCategory));
            },
            get previewTotal() { return this.selectedList.reduce((s, i) => s + Number(i.price) * Number(i.quantity), 0); },
            get itemCount() { return this.selectedList.reduce((s, i) => s + Number(i.quantity), 0); },
            currency(v) { return 'S/ ' + Number(v).toFixed(2); },
            submitForm(form) { if (this.selectedList.length > 0) form.submit(); },
        };
    };
    </script>

    {{-- Bloquear navegación hacia atrás: estas páginas no deben aparecer en historial --}}
    <script>
        // Reemplaza la entrada actual del historial para que el botón "atrás"
        // no regrese a esta página sino al índice del mozo.
        history.replaceState(null, '', window.location.href);

        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                // La página se cargó desde caché (bfcache) tras presionar Atrás
                window.location.replace('{{ route('mozo.orders.index') }}');
            }
        });
    </script>
</x-layouts.app>