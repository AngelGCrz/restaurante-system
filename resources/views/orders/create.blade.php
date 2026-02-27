<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Nuevo Pedido</h1>

        <form
            action="{{ route('mozo.orders.store') }}"
            method="POST"
            class="grid grid-cols-1 gap-6 lg:grid-cols-3"
            x-data='orderFormComponent({
                totalTables: {{ (int) ($tableCount ?? 0) }},
                presetTables: @json($tableNumbers ?? []),
                presetSelection: @json($selectedTables ?? []),
                tableSelectUrl: "{{ route('mozo.tables.select') }}",
                products: @json($products ?? []),
                categories: @json($categories ?? []),
                initialServiceType: @json(old('type', 'mesa')),
                initialCustomerName: @json(old('customer_name', '')),
                initialComment: @json(old('comment', '')),
            })'
            x-init="init()"
            x-on:submit="handleSubmit($event)"
            if (serviceType === 'llevar' && customerName.trim() === '') {
                document.querySelector('[name=customer_name]').focus();
                return;
            }
            localStorage.removeItem('order_form_draft');
            sessionStorage.setItem('order_just_submitted', '1');
            $el.removeEventListener('submit', arguments.callee);
            $el.submit();
            "
        >
            @csrf

            <!-- Barra superior fija en móviles: muestra total y cantidad de ítems en tiempo real -->
            <div x-show="selectedList.length" x-cloak class="md:hidden sticky top-0 z-40 w-full bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between gap-3 px-4 py-2">
                    <div class="flex items-baseline gap-3">
                        <div class="text-xs text-zinc-500 dark:text-zinc-300">Total a pagar</div>
                        <div class="text-lg font-semibold text-red-600 dark:text-red-400" x-text="currency(previewTotal)"></div>
                    </div>
                    <div class="text-sm text-zinc-500 dark:text-zinc-300" x-text="itemCount + ' ítems'"></div>
                </div>
            </div>
            
            <!-- Barra superior móvil removida: el total de la mesa se muestra en la vista de mis pedidos -->
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        {{-- <button type="button" class="rounded-full border px-3 py-1 text-sm font-medium transition hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-700" :class="!currentCategory ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-black' : ''" @click="currentCategory = null">Todas</button> --}}
                        <template x-for="cat in categories" :key="cat.id">
                            <button x-show="cat.id !== 4" type="button" class="rounded-full border px-3 py-1 text-sm font-medium transition hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-700" :class="currentCategory === cat.id ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-black' : ''" @click="currentCategory = cat.id" x-text="cat.name"></button>
                        </template>
                    </div>

                    <h2 class="mb-4 text-lg font-semibold">Productos Disponibles</h2>

{{-- Vista especial cuando está seleccionado ENTRADA (id=4): muestra entradas arriba y segundos abajo --}}
<template x-if="currentCategory === 1 || currentCategory === 2 || currentCategory === 5">
    <div>
        {{-- ENTRADAS --}}
        <p class="text-xs font-semibold uppercase tracking-widest text-red-500 mb-2">── Entradas ──</p>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4 mb-6">
            <template x-for="product in products.filter(p => p.category_id == 4)" :key="product.id">
                <div
                :class="product.sold_out
                    ? 'opacity-50 cursor-not-allowed relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900'
                    : 'relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm transition cursor-pointer dark:border-zinc-700 dark:bg-zinc-900'"
                @click="!selectedMap[product.id + '_' + currentCategory] && !product.sold_out && addProduct(product)"
            >
                    {{-- <div class="absolute right-2 top-2" x-show="selectedMap[product.id + '_' + currentCategory]" x-cloak>
                        <span class="inline-flex min-w-[32px] justify-center rounded-full bg-emerald-600 px-2 py-1 text-xs font-semibold text-white" x-text="selectedMap[product.id]?.quantity"></span>
                    </div> --}}
                    <p class="font-semibold text-sm leading-tight" x-text="product.name"></p>
                    <p class="text-xs text-zinc-500" x-text="currency(product.price)"></p>
                    <div class="flex items-center gap-2 mt-2" x-show="selectedMap[product.id + '_' + currentCategory]" x-cloak @click.stop>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-500 hover:bg-red-600 text-white text-lg font-bold leading-none transition" @click.stop="decrement(product.id + '_' + currentCategory)">-</button>
                        <span class="min-w-[20px] text-center text-sm font-semibold" x-text="selectedMap[product.id + '_' + currentCategory]?.quantity"></span>
                        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-500 hover:bg-green-600 text-white text-lg font-bold leading-none transition" @click.stop="addProduct(product)">+</button>
                    </div>
                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 mt-2" x-show="product.sold_out" x-cloak>Agotado</span>
                    <p class="text-xs text-rose-600 mt-2" x-show="!product.sold_out && product.low_stock" x-cloak x-text="'Quedan ' + (product.stock ?? 0)"></p>
                </div>
            </template>
        </div>

        {{-- SEGUNDOS --}}
        <p class="text-xs font-semibold uppercase tracking-widest text-red-500 mb-2" x-text="currentCategory === 1 ? '── Segundos ──' : currentCategory === 2 ? '── Extras ──' : '── Porciones ──'"></p>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
                    <template x-for="product in products.filter(p => currentCategory === 1 ? p.category_id == 1 : p.category_id == currentCategory)" :key="product.id">

                       <div
                :class="product.sold_out
                    ? 'opacity-50 cursor-not-allowed relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900'
                    : 'relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm transition cursor-pointer dark:border-zinc-700 dark:bg-zinc-900'"
                @click="!selectedMap[product.id + '_' + currentCategory] && !product.sold_out && addProduct(product)"
            >
                <p class="font-semibold text-sm leading-tight" x-text="product.name"></p>
                <p class="text-xs text-zinc-500" x-text="currency(product.price)"></p>
                <div class="flex items-center gap-2 mt-2" x-show="selectedMap[product.id + '_' + currentCategory]" x-cloak @click.stop>
                    <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-500 hover:bg-red-600 text-white text-lg font-bold leading-none transition" @click.stop="decrement(product.id + '_' + currentCategory)">-</button>
                    <span class="min-w-[20px] text-center text-sm font-semibold" x-text="selectedMap[product.id + '_' + currentCategory]?.quantity"></span>
                    <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-500 hover:bg-green-600 text-white text-lg font-bold leading-none transition" @click.stop="addProduct(product)">+</button>
                </div>
                <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 mt-2" x-show="product.sold_out" x-cloak>Agotado</span>
                <p class="text-xs text-rose-600 mt-2" x-show="!product.sold_out && product.low_stock" x-cloak x-text="'Quedan ' + (product.stock ?? 0)"></p>
            </div> 

            </template>
        </div>
    </div>
</template>

{{-- Vista normal para todas las demás categorías (MENU, BEBIDAS, EXTRAS, PORCIONES) --}}
<template x-if="currentCategory !== 1 && currentCategory !== 2 && currentCategory !== 5">
    <div>
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 lg:grid-cols-4">
            <template x-for="product in filteredProducts" :key="product.id">
               <div
    :class="product.sold_out
        ? 'opacity-50 cursor-not-allowed relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900'
        : 'relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm transition cursor-pointer dark:border-zinc-700 dark:bg-zinc-900'"
    @click="!selectedMap[product.id + '_' + currentCategory] && !product.sold_out && addProduct(product)"
>
    <p class="font-semibold text-sm leading-tight" x-text="product.name"></p>
    <p class="text-xs text-zinc-500" x-text="currency(product.price)"></p>
    <div class="flex items-center gap-2 mt-2" x-show="selectedMap[product.id + '_' + currentCategory]" x-cloak @click.stop>
        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg bg-red-500 hover:bg-red-600 text-white text-lg font-bold leading-none transition" @click.stop="decrement(product.id + '_' + currentCategory)">-</button>
        <span class="min-w-[20px] text-center text-sm font-semibold" x-text="selectedMap[product.id + '_' + currentCategory]?.quantity"></span>
        <button type="button" class="flex h-7 w-7 items-center justify-center rounded-lg bg-green-500 hover:bg-green-600 text-white text-lg font-bold leading-none transition" @click.stop="addProduct(product)">+</button>
    </div>
    <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 mt-2" x-show="product.sold_out" x-cloak>Agotado</span>
    <p class="text-xs text-rose-600 mt-2" x-show="!product.sold_out && product.low_stock" x-cloak x-text="'Quedan ' + (product.stock ?? 0)"></p>
</div>
            </template>
            <p x-show="!filteredProducts.length" class="col-span-full text-sm text-zinc-500" x-cloak>No hay productos en esta categoría.</p>
        </div>
    </div>
</template>
                    

                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Productos seleccionados</h2>
                        <button type="button" class="text-sm text-rose-600 hover:underline" @click="clearProducts" x-show="selectedList.length" x-cloak>Vaciar</button>
                    </div>
                    <div class="space-y-3" x-show="selectedList.length" x-cloak>
                        <template x-for="item in selectedList" :key="item._mapKey ?? item.id">
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div>
                                    <p class="font-medium" x-text="item.name"></p>
                                    <p class="text-xs text-zinc-500" x-text="currency(item.price)"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-500 hover:bg-red-600 text-white text-lg font-bold leading-none transition" @click="decrement(item._mapKey ?? item.id)">-</button>
                                    <span class="min-w-[24px] text-center text-sm font-semibold" x-text="item.quantity"></span>
                                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-lg bg-green-500 hover:bg-green-600 text-white text-lg font-bold leading-none transition" @click="increment(item._mapKey ?? item.id)">+</button>
                                    <button type="button" class="text-xs text-blue-600 underline flex items-center gap-1" @click="openCommentModal(item)">
                                    <i class="bi bi-chat-dots"></i>
                                </button>

                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="text-sm text-zinc-500" x-show="!selectedList.length">Toca un producto para agregarlo al pedido.</p>

                    <template x-for="(item, index) in selectedList" :key="`hidden-${item._mapKey ?? item.id}`">
                    <div>
                        <input type="hidden" :name="`items[${index}][product_id]`" :value="item.id">
                        <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                        <input type="hidden" :name="`items[${index}][comment]`" :value="item.comment || ''">
                        <input type="hidden" :name="`items[${index}][override_price]`" :value="item.price">
                    </div>
                </template>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 class="mb-4 text-lg font-semibold">Información del Pedido</h2>
                    <div class="space-y-4">
                        <label class="block space-y-1">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                        Nombre del Cliente
                        <span x-show="serviceType === 'llevar'" class="text-red-500">*</span>
                        <span x-show="serviceType !== 'llevar'" class="text-zinc-400 text-xs">(opcional)</span>
                    </span>
                    <input
                        type="text"
                        name="customer_name"
                        x-model="customerName"
                        x-on:input="saveDraft"
                        :required="serviceType === 'llevar'"
                        :placeholder="serviceType === 'llevar' ? 'Requerido para llevar' : 'Opcional'"
                        :class="serviceType === 'llevar' && customerName.trim() === ''
                            ? 'w-full rounded-lg border border-red-500 bg-red-50 px-3 py-2 text-sm focus:border-red-500 focus:outline-none dark:border-red-500 dark:bg-zinc-900'
                            : 'w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900'"
                    >
                    <p
                        x-show="serviceType === 'llevar' && customerName.trim() === ''"
                        x-cloak
                        class="text-xs text-red-500 mt-1"
                    >⚠ Ingresa el nombre del cliente para pedidos para llevar.</p>
                </label>

                        <label class="block space-y-1">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-200">Comentario (opcional)</span>
                            <textarea name="comment" rows="3" x-model="comment" x-on:input="saveDraft" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900" placeholder="Notas para cocina o entrega"></textarea>
                        </label>

                        <label class="block space-y-1">
                            <span class="text-sm font-medium text-red-500">Tipo de Servicio</span>
                            <select name="type" x-model="serviceType" @change="serviceType = $event.target.value; handleTypeChange(); saveDraft();" class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm focus:border-zinc-500 focus:outline-none dark:border-zinc-700 dark:bg-zinc-900">
                                <option value="mesa">En Mesa</option>
                                <option value="llevar">Para Llevar</option>
                            </select>
                        </label>

                        <div x-show="serviceType === 'mesa'" x-cloak class="space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm text-zinc-600">Selecciona una o varias mesas.</p>
                                    <p class="text-sm font-medium" x-html="selectionLabel()"></p>
                                </div>
                                <button
    type="button"
    x-on:click="goToTableSelector"
    x-bind:disabled="totalTables === 0"
    class="flex items-center gap-2 rounded-lg bg-red-600 hover:bg-red-700 px-3 py-2 text-sm font-semibold text-white transition disabled:opacity-50"
>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M10 3v18M14 3v18" />
    </svg>
    MESAS
</button>
                            </div>

                            <template x-for="table in selectedTables" :key="table">
                                <input type="hidden" name="tables[]" :value="table">
                            </template>

                            <p x-show="totalTables === 0" class="text-sm text-red-600">Configura la cantidad total de mesas en Administración.</p>
                            @error('tables')
                                <p class="text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <hr class="dark:border-zinc-700">
                        <flux:button variant="primary" type="submit" class="w-full">Registrar Pedido</flux:button>
                    </div>
                </div>
            </div>
                <div x-show="showCommentModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 w-full max-w-md">
                        <h3 class="text-lg font-semibold mb-2">
                            Comentario para: <span class="font-bold" x-text="currentCommentItem?.name"></span>
                        </h3>
                        <textarea rows="4"
                                class="w-full rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-900"
                                x-model="currentCommentText">
                        </textarea>
                        <div class="mt-4 flex justify-end gap-2">
                            <button type="button" class="px-4 py-2 rounded-lg border" @click="closeCommentModal">Cancelar</button>
                            <button type="button" class="px-4 py-2 rounded-lg bg-zinc-900 text-white" @click="saveItemComment">Guardar</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        </form>
    </div>

    <script>
        // Simple toast helper for non-blocking notifications
        window.showToast = function(message, variant = 'error') {
            const containerId = 'app-toasts-container';
            let container = document.getElementById(containerId);
            if (!container) {
                container = document.createElement('div');
                container.id = containerId;
                container.style.position = 'fixed';
                container.style.right = '16px';
                container.style.top = '16px';
                container.style.zIndex = 9999;
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.textContent = message;
            toast.style.marginTop = '8px';
            toast.style.padding = '10px 14px';
            toast.style.borderRadius = '8px';
            toast.style.color = '#fff';
            toast.style.fontSize = '13px';
            toast.style.boxShadow = '0 4px 16px rgba(0,0,0,0.12)';
            toast.style.opacity = '0';
            toast.style.transition = 'opacity 200ms ease, transform 200ms ease';

            if (variant === 'success') {
                toast.style.background = '#16a34a';
            } else {
                toast.style.background = '#dc2626';
            }

            container.appendChild(toast);

            // force reflow then show
            // eslint-disable-next-line no-unused-expressions
            toast.offsetWidth;
            toast.style.opacity = '1';
            toast.style.transform = 'translateY(0)';

            setTimeout(() => {
                toast.style.opacity = '0';
                toast.style.transform = 'translateY(-8px)';
                setTimeout(() => container.removeChild(toast), 300);
            }, 3000);
        };

        function orderFormComponent({ totalTables = 0, presetTables = [], presetSelection = [], tableSelectUrl = '', products = [], categories = [], initialServiceType = 'mesa', initialCustomerName = '', initialComment = '' }) {
            return {
                persistKey: 'order_form_draft',
                serviceType: initialServiceType || 'mesa',
                customerName: initialCustomerName || '',
                comment: initialComment || '',
                showCommentModal: false,
                currentCommentItem: null,
                currentCommentText: '',
                totalTables,
                tableNumbers: presetTables.length ? presetTables : Array.from({ length: totalTables }, (_, idx) => idx + 1),
                selectedTables: presetSelection,
                tableSelectUrl,
                products,
                categories,
                currentCategory: 1,
                selectedMap: {},
                init() {
                    // Si acaba de registrar un pedido, no cargar el draft
                if (sessionStorage.getItem('order_just_submitted')) {
                    sessionStorage.removeItem('order_just_submitted');
                    this.saveDraft(); // guarda estado vacío
                    return;
                }
            
                const saved = this.loadDraft();
                if (saved) {
                    this.serviceType = saved.serviceType || this.serviceType;
                    this.selectedMap = saved.selectedMap || {};
                    this.customerName = saved.customerName || '';
                    this.comment = saved.comment || '';
                    if (!this.selectedTables.length && Array.isArray(saved.selectedTables)) {
                        this.selectedTables = saved.selectedTables;
                    }
                }
            
                if (this.serviceType !== 'mesa') {
                    this.selectedTables = [];
                }
            
                const ids = Object.keys(this.selectedMap || {});
                let changed = false;
                ids.forEach((mapKey) => {
                    const item = this.selectedMap[mapKey];
                    // La clave es "productId_categoryId", extraer solo el productId
                    const productId = item.id ?? mapKey.split('_')[0];
                    const product = this.products.find(p => p.id == productId);
                    if (!product) {
                        delete this.selectedMap[mapKey];
                        changed = true;
                        return;
                    }
                    if ((product.sold_out) || (!product.allow_negative && typeof product.stock === 'number' && item.quantity > product.stock)) {
                        delete this.selectedMap[mapKey];
                        changed = true;
                    }
                });
            
                this.saveDraft();
                },
                loadDraft() {
                    try {
                        const raw = localStorage.getItem(this.persistKey);
                        return raw ? JSON.parse(raw) : null;
                    } catch (error) {
                        console.error('No se pudo cargar el borrador del pedido', error);
                        return null;
                    }
                },
                saveDraft() {
                    const payload = {
                        serviceType: this.serviceType,
                        selectedTables: this.selectedTables,
                        selectedMap: this.selectedMap,
                        customerName: this.customerName,
                        comment: this.comment,
                    };
                    localStorage.setItem(this.persistKey, JSON.stringify(payload));
                },
                clearDraft() {
                    localStorage.removeItem(this.persistKey);
                },
                isSelected(table) {
                    return this.selectedTables.includes(table);
                },
                clearSelection() {
                    this.selectedTables = [];
                    this.saveDraft();
                },
                clearProducts() {
                    this.selectedMap = {};
                    this.saveDraft();
                },
                selectionLabel() {
                    if (this.serviceType !== 'mesa') {
                        return 'Pedido para llevar';
                    }

                    if (!this.selectedTables.length) {
                        return '<span style="color:red">SELECCIONA MESAS</span>';
                    }

                    const prefix = this.selectedTables.length === 1 ? 'Mesa' : 'Mesas';
                    return `${prefix} ${this.selectedTables.join(' + ')}`;
                },
                handleTypeChange() {
                    if (this.serviceType !== 'mesa') {
                        this.selectedTables = [];
                    }
                    // Recalcular precios al cambiar tipo de servicio
                    Object.keys(this.selectedMap).forEach((mapKey) => {
                        const item = this.selectedMap[mapKey];
                        const product = this.products.find(p => p.id == item.id);
                        if (!product) return;

                        let basePrice = product.price;
                        if (product.category_id == 4) {
                            const isCeviche = product.name.toLowerCase().includes('ceviche');
                            const catId = parseInt(mapKey.split('_')[1]);
                            if (catId === 1) basePrice = 0;
                            else if (catId === 2) basePrice = isCeviche ? 2 : 1;
                            else if (catId === 5) basePrice = isCeviche ? 5 : 4;
                        }
                        if (this.serviceType === 'llevar' && Number(basePrice) > 9) {
                            basePrice = Number(basePrice) + 1;
                        }
                        this.selectedMap[mapKey] = { ...item, price: basePrice };
                    });
                    this.saveDraft();
                
                },
                goToTableSelector() {
                    this.saveDraft();
                    const params = new URLSearchParams();
                    this.selectedTables.forEach((table) => params.append('tables[]', table));
                    window.location.href = params.toString()
                        ? `${this.tableSelectUrl}?${params.toString()}`
                        : this.tableSelectUrl;
                },
                addProduct(product) {
                    if (product.sold_out) return;
                    
                // Clave única por producto + categoría activa
                    const mapKey = `${product.id}_${this.currentCategory}`;

                // Determinar precio según categoría activa y producto
                let adjustedPrice = product.price;
                if (product.category_id == 4) {
                    const isCeviche = product.name.toLowerCase().includes('ceviche');
                    if (this.currentCategory === 1) {
                        // MENU
                        adjustedPrice = 0;
                    } else if (this.currentCategory === 2) {
                        // EXTRAS
                        adjustedPrice = isCeviche ? 2 : 1;
                    } else if (this.currentCategory === 5) {
                        // PORCIONES
                        adjustedPrice = isCeviche ? 5 : 4;
                    }
                }

                // +1 soles para llevar si precio > 9
                if (this.serviceType === 'llevar' && Number(adjustedPrice) > 9) {
                    adjustedPrice = Number(adjustedPrice) + 1;
                }

                const existing = this.selectedMap[mapKey] ?? { ...product, quantity: 0, price: adjustedPrice, _mapKey: mapKey };
                    const newQty = existing.quantity + 1;

                if (!product.allow_negative && typeof product.stock === 'number' && newQty > product.stock) {
                    showToast('Stock insuficiente para ' + product.name + '. Disponible: ' + product.stock);
                    return;
                    }

                existing.quantity = newQty;
                existing.price = adjustedPrice;
                this.selectedMap[mapKey] = existing;
                this.saveDraft();
            },
                // addProduct(product) {
                //     if (product.sold_out) return;
                //     const existing = this.selectedMap[product.id] ?? { ...product, quantity: 0 };
                //     // If not previously selected, start at 1
                //     const startingQty = existing.quantity > 0 ? existing.quantity : 0;
                //     const newQty = startingQty + 1;
                //     if (!product.allow_negative && typeof product.stock === 'number' && newQty > product.stock) {
                //         showToast('Stock insuficiente para ' + product.name + '. Disponible: ' + product.stock);
                //         return;
                //     }
                //     existing.quantity = newQty;
                //     this.selectedMap[product.id] = existing;
                //     this.saveDraft();
                // },
                //MODAL
                openCommentModal(item) {
                this.currentCommentItem = item;
                this.currentCommentText = item.comment || '';
                this.showCommentModal = true;
            },
            closeCommentModal() {
                this.showCommentModal = false;
                this.currentCommentItem = null;
                this.currentCommentText = '';
            },
            saveItemComment() {
            if (this.currentCommentItem) {
                const key = this.currentCommentItem._mapKey ?? this.currentCommentItem.id;
                this.selectedMap[key] = {
                    ...this.selectedMap[key],
                    comment: this.currentCommentText
                };
                this.saveDraft();
            }
            this.closeCommentModal();
        },
            //END MODAL
                itemSubtotal(item) {
                    return (Number(item.price) || 0) * (Number(item.quantity) || 0);
                },
                get previewTotal() {
                    return this.selectedList.reduce((sum, item) => sum + this.itemSubtotal(item), 0);
                },
                get itemCount() {
                    return this.selectedList.reduce((sum, item) => sum + (Number(item.quantity) || 0), 0);
                },
                increment(productId) {
                    if (!this.selectedMap[productId]) return;
                    const item = this.selectedMap[productId];
                    const product = this.products.find(p => p.id == item.id) || item;
                    const newQty = item.quantity + 1;
                    if (!product.allow_negative && typeof product.stock === 'number' && newQty > product.stock) {
                        showToast('Stock insuficiente para ' + product.name + '. Disponible: ' + product.stock);
                        return;
                    }
                    this.selectedMap[productId].quantity = newQty;
                    this.saveDraft();
                },
                
                decrement(productId) {
                    if (!this.selectedMap[productId]) return;
                    this.selectedMap[productId].quantity -= 1;
                    if (this.selectedMap[productId].quantity <= 0) {
                        delete this.selectedMap[productId];
                    }
                    this.saveDraft();
                },
                get selectedList() {
                    return Object.values(this.selectedMap);
                },
                get filteredProducts() {
                    if (!this.currentCategory) {
                        return this.products;
                    }
                    const current = String(this.currentCategory);
                    return this.products.filter((product) => String(product.category_id) === current);
                },
                currency(value) {
                    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(value);
                },
                handleSubmit(event) {
                if (this.serviceType === 'llevar' && this.customerName.trim() === '') {
                    event.preventDefault();
                    document.querySelector('[name=customer_name]').focus();
                    return;
                }
                localStorage.removeItem('order_form_draft');
                sessionStorage.setItem('order_just_submitted', '1');
                // Dejar que el form se envíe normalmente
            },
            };
        }
    </script>
    {{-- Limpiar draft ANTES de que Alpine inicialice --}}
{{-- @if(session('success')) --}}
{{-- <script>
    localStorage.removeItem('order_form_draft');
</script>
@endif --}}

</x-layouts.app>