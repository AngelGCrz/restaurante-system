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
            x-on:submit.prevent="
            if (serviceType === 'llevar' && customerName.trim() === '') {
                document.querySelector('[name=customer_name]').focus();
                return;
            }
            localStorage.removeItem('order_form_draft');
            sessionStorage.setItem('order_just_submitted', '1');
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
                <button
                    type="button"
                    :disabled="product.sold_out"
                    :class="product.sold_out
                        ? 'opacity-50 cursor-not-allowed relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm dark:border-zinc-700 dark:bg-zinc-900'
                        : 'relative flex flex-col items-center justify-center rounded-md border border-zinc-200 bg-white p-2 text-center shadow-sm transition active:scale-95 dark:border-zinc-700 dark:bg-zinc-900'"
                    @click="addProduct(product)"
                >
                    <div class="absolute right-2 top-2" x-show="selectedMap[product.id]" x-cloak>
                        <span class="inline-flex min-w-[32px] justify-center rounded-full bg-emerald-600 px-2 py-1 text-xs font-semibold text-white" x-text="selectedMap[product.id]?.quantity"></span>
                    </div>
                    <p class="font-semibold text-sm leading-tight" x-text="product.name"></p>
                    <p class="text-xs text-zinc-500" x-text="currency(product.price)"></p>
                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 mt-2" x-show="product.sold_out" x-cloak>Agotado</span>
                    <p class="text-xs text-rose-600 mt-2" x-show="!product.sold_out && product.low_stock" x-cloak x-text="'Quedan ' + (product.stock ?? 0)"></p>
                </button>
            </template>
        </div>

        {{-- SEGUNDOS --}}
        <p class="text-xs font-semibold uppercase tracking-widest text-red-500 mb-2" x-text="currentCategory === 1 ? '── Segundos ──' : currentCategory === 2 ? '── Extras ──' : '── Porciones ──'"></p>
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
                    <div class="absolute right-2 top-2" x-show="selectedMap[product.id]" x-cloak>
                        <span class="inline-flex min-w-[32px] justify-center rounded-full bg-emerald-600 px-2 py-1 text-xs font-semibold text-white" x-text="selectedMap[product.id]?.quantity"></span>
                    </div>
                    <p class="font-semibold text-sm leading-tight" x-text="product.name"></p>
                    <p class="text-xs text-zinc-500" x-text="currency(product.price)"></p>
                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 mt-2" x-show="product.sold_out" x-cloak>Agotado</span>
                    <p class="text-xs text-rose-600 mt-2" x-show="!product.sold_out && product.low_stock" x-cloak x-text="'Quedan ' + (product.stock ?? 0)"></p>
                </button>
            </template>
        </div>
    </div>
</template>

{{-- Vista normal para todas las demás categorías (MENU, BEBIDAS, EXTRAS, PORCIONES) --}}
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
                    <div class="absolute right-2 top-2" x-show="selectedMap[product.id]" x-cloak>
                        <span class="inline-flex min-w-[32px] justify-center rounded-full bg-emerald-600 px-2 py-1 text-xs font-semibold text-white" x-text="selectedMap[product.id]?.quantity"></span>
                    </div>
                    <p class="font-semibold text-sm leading-tight" x-text="product.name"></p>
                    <p class="text-xs text-zinc-500" x-text="currency(product.price)"></p>
                    <span class="inline-flex items-center rounded-full bg-rose-100 px-2 py-1 text-xs font-semibold text-rose-700 mt-2" x-show="product.sold_out" x-cloak>Agotado</span>
                    <p class="text-xs text-rose-600 mt-2" x-show="!product.sold_out && product.low_stock" x-cloak x-text="'Quedan ' + (product.stock ?? 0)"></p>
                </button>
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
                                    <button type="button" class="text-xs text-blue-600 underline flex items-center gap-1" @click="openCommentModal(item)">
                                    <i class="bi bi-chat-dots"></i>
                                </button>

                                </div>
                            </div>
                        </template>
                    </div>
                    <p class="text-sm text-zinc-500" x-show="!selectedList.length">Toca un producto para agregarlo al pedido.</p>

                    <template x-for="(item, index) in selectedList" :key="`hidden-${item.id}`">
                        <div>
                            <input type="hidden" :name="`items[${index}][product_id]`" :value="item.id">
                            <input type="hidden" :name="`items[${index}][quantity]`" :value="item.quantity">
                            <input type="hidden" :name="`items[${index}][comment]`" :value="item.comment || ''">
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

{{-- @if(session('success')) --}}
{{-- <script>
    localStorage.removeItem('order_form_draft');
</script>
@endif --}}

</x-layouts.app>