<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Nuevo Pedido</h1>

        <form
            action="{{ route('mozo.orders.store') }}"
            method="POST"
            class="grid grid-cols-1 gap-6 lg:grid-cols-3"
            x-data='tableSelectionComponent({
                totalTables: {{ (int) ($tableCount ?? 0) }},
                presetTables: @json($tableNumbers ?? []),
                presetSelection: @json($selectedTables ?? []),
                tableSelectUrl: "{{ route('mozo.tables.select') }}",
            })'
        >
            @csrf
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 class="mb-4 text-lg font-semibold">Productos Disponibles</h2>
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        @foreach($products as $product)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-100 p-3 dark:border-zinc-700">
                                <div>
                                    <p class="font-medium">{{ $product->name }}</p>
                                    <p class="text-sm text-zinc-500">${{ number_format($product->price, 2) }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="items[{{ $product->id }}][quantity]" value="0" min="0" class="w-16 rounded border-zinc-300 text-sm dark:border-zinc-600 dark:bg-zinc-700">
                                    <input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-6">
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
        function tableSelectionComponent({ totalTables = 0, presetTables = [], presetSelection = [], tableSelectUrl = '' }) {
            return {
                serviceType: 'mesa',
                totalTables,
                tableNumbers: presetTables.length ? presetTables : Array.from({ length: totalTables }, (_, idx) => idx + 1),
                selectedTables: presetSelection,
                tableSelectUrl,
                isSelected(table) {
                    return this.selectedTables.includes(table);
                },
                clearSelection() {
                    this.selectedTables = [];
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
            };
        }
    </script>
</x-layouts.app>
