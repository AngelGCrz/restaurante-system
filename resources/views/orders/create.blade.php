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
            })'
        >
            @csrf
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-4 flex flex-wrap items-center gap-2">
                        <button type="button" class="rounded-full border px-3 py-1 text-sm font-medium transition hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-700" :class="!currentCategory ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-black' : ''" @click="currentCategory = null">Todas</button>
                        <template x-for="cat in categories" :key="cat.id">
                            <button type="button" class="rounded-full border px-3 py-1 text-sm font-medium transition hover:bg-zinc-100 dark:border-zinc-700 dark:hover:bg-zinc-700" :class="currentCategory === cat.id ? 'border-zinc-900 bg-zinc-900 text-white dark:border-white dark:bg-white dark:text-black' : ''" @click="currentCategory = cat.id" x-text="cat.name"></button>
                        </template>
                    </div>

                    <h2 class="mb-4 text-lg font-semibold">Productos Disponibles</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
                        <template x-for="product in filteredProducts" :key="product.id">
                            <button
                                type="button"
                                class="relative flex flex-col items-start rounded-lg border border-zinc-200 bg-white p-4 text-left shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900"
                                @click="addProduct(product)"
                            >
                                <div class="absolute right-2 top-2" x-show="selectedMap[product.id]" x-cloak>
                                    <span class="inline-flex min-w-[32px] justify-center rounded-full bg-emerald-600 px-2 py-1 text-xs font-semibold text-white" x-text="selectedMap[product.id]?.quantity"></span>
                                </div>
                                <p class="font-semibold" x-text="product.name"></p>
                                <p class="text-sm text-zinc-500" x-text="currency(product.price)"></p>
                            </button>
                        </template>
                        <p x-show="!filteredProducts.length" class="col-span-full text-sm text-zinc-500" x-cloak>No hay productos en esta categoría.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <div class="mb-2 flex items-center justify-between">
                        <h2 class="text-lg font-semibold">Productos seleccionados</h2>
                        <button type="button" class="text-sm text-rose-600 hover:underline" @click="clearProducts" x-show="selectedList.length" x-cloak>Vaciar</button>
                    </div>
                    <div class="space-y-3" x-show="selectedList.length" x-cloak>
                        <template x-for="item in selectedList" :key="item.id">
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div>
                                    <p class="font-medium" x-text="item.name"></p>
                                    <p class="text-xs text-zinc-500" x-text="currency(item.price)"></p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full border border-zinc-300 text-lg leading-none dark:border-zinc-600" @click="decrement(item.id)">-</button>
                                    <span class="min-w-[24px] text-center text-sm font-semibold" x-text="item.quantity"></span>
                                    <button type="button" class="flex h-8 w-8 items-center justify-center rounded-full border border-zinc-300 text-lg leading-none dark:border-zinc-600" @click="increment(item.id)">+</button>
                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="text-sm text-zinc-500" x-show="!selectedList.length">Toca un producto para agregarlo al pedido.</p>

                    <template x-for="(item, index) in selectedList" :key="`hidden-${item.id}`">
                        <div>
                            <input type="hidden" :name="`items[${index}][product_id]`" :value="item.id">
                            <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                        </div>
                    </template>
                </div>

                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 class="mb-4 text-lg font-semibold">Información del Pedido</h2>
                    <div class="space-y-4">
                        <flux:input name="customer_name" label="Nombre del Cliente" placeholder="Opcional" />

                        <flux:select name="type" label="Tipo de Servicio" x-on:change="serviceType = $event.target.value; handleTypeChange();">
                            <flux:select.option value="mesa">En Mesa</flux:select.option>
                            <flux:select.option value="llevar">Para Llevar</flux:select.option>
                        </flux:select>

                        <div x-show="serviceType === 'mesa'" x-cloak class="space-y-2">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="text-sm text-zinc-600">Selecciona una o varias mesas.</p>
                                    <p class="text-sm font-medium" x-text="selectionLabel()"></p>
                                </div>
                                <flux:button
                                    type="button"
                                    variant="subtle"
                                    icon="table-cells"
                                    x-on:click="goToTableSelector"
                                    x-bind:disabled="totalTables === 0"
                                >
                                    MESAS
                                </flux:button>
                            </div>

                            <template x-for="table in selectedTables" :key="table">
                                <input type="hidden" name="tables[]" :value="table">
                            </template>

                            <p x-show="totalTables === 0" class="text-sm text-red-600">Configura la cantidad total de mesas en Administración.</p>
                        </div>

                        <hr class="dark:border-zinc-700">
                        <flux:button variant="primary" type="submit" class="w-full">Registrar Pedido</flux:button>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <script>
        function orderFormComponent({ totalTables = 0, presetTables = [], presetSelection = [], tableSelectUrl = '', products = [], categories = [] }) {
            return {
                serviceType: 'mesa',
                totalTables,
                tableNumbers: presetTables.length ? presetTables : Array.from({ length: totalTables }, (_, idx) => idx + 1),
                selectedTables: presetSelection,
                tableSelectUrl,
                products,
                categories,
                currentCategory: null,
                selectedMap: {},
                isSelected(table) {
                    return this.selectedTables.includes(table);
                },
                clearSelection() {
                    this.selectedTables = [];
                },
                clearProducts() {
                    this.selectedMap = {};
                },
                selectionLabel() {
                    if (this.serviceType !== 'mesa') {
                        return 'Pedido para llevar';
                    }

                    if (!this.selectedTables.length) {
                        return 'Sin mesas seleccionadas';
                    }

                    const prefix = this.selectedTables.length === 1 ? 'Mesa' : 'Mesas';
                    return `${prefix} ${this.selectedTables.join(' + ')}`;
                },
                handleTypeChange() {
                    if (this.serviceType !== 'mesa') {
                        this.selectedTables = [];
                    }
                },
                goToTableSelector() {
                    const params = new URLSearchParams();
                    this.selectedTables.forEach((table) => params.append('tables[]', table));
                    window.location.href = params.toString()
                        ? `${this.tableSelectUrl}?${params.toString()}`
                        : this.tableSelectUrl;
                },
                addProduct(product) {
                    const existing = this.selectedMap[product.id] ?? { ...product, quantity: 0 };
                    existing.quantity += 1;
                    this.selectedMap[product.id] = existing;
                },
                increment(productId) {
                    if (!this.selectedMap[productId]) return;
                    this.selectedMap[productId].quantity += 1;
                },
                decrement(productId) {
                    if (!this.selectedMap[productId]) return;
                    this.selectedMap[productId].quantity -= 1;
                    if (this.selectedMap[productId].quantity <= 0) {
                        delete this.selectedMap[productId];
                    }
                },
                get selectedList() {
                    return Object.values(this.selectedMap);
                },
                get filteredProducts() {
                    if (!this.currentCategory) {
                        return this.products;
                    }
                    return this.products.filter((product) => product.category_id === this.currentCategory);
                },
                currency(value) {
                    return new Intl.NumberFormat('es-CL', { style: 'currency', currency: 'CLP', maximumFractionDigits: 0 }).format(value);
                },
            };
        }
    </script>
</x-layouts.app>
