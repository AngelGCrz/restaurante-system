<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Seleccionar Mesas</h1>
                <p class="text-sm text-zinc-600">Toca las mesas para elegir una o varias. Luego confirma para continuar el pedido.</p>
            </div>
            <flux:button href="{{ route('mozo.orders.create') }}" variant="ghost" icon="arrow-left">Volver al pedido</flux:button>
        </div>

        <div class="rounded-xl border border-dashed border-zinc-300 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
             x-data="tableSelector({
                 baseRedirect: '{{ route('mozo.orders.create') }}',
                 tableNumbers: @json($tableNumbers),
                 selected: @json($selectedTables),
             })">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                <div class="space-y-1">
                    <p class="text-sm font-medium">{{ $tableCount }} mesas configuradas</p>
                    <p class="text-sm text-zinc-600" x-text="label()"></p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <flux:button variant="ghost" icon="arrow-path" type="button" x-on:click="clearSelection">Limpiar</flux:button>
                    <flux:button variant="primary" icon="check" type="button" x-on:click="confirmSelection" x-bind:disabled="selected.length === 0">Confirmar selección</flux:button>
                </div>
            </div>

            <div class="max-h-[60vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    <template x-for="table in tableNumbers" :key="table">
                        <button
                            type="button"
                            class="flex h-20 items-center justify-center rounded-lg border text-sm font-semibold transition"
                            :class="isSelected(table)
                                ? 'border-primary-500 bg-primary-50 text-primary-700 dark:border-primary-400 dark:bg-primary-900/30 dark:text-primary-100'
                                : 'border-zinc-200 bg-white text-zinc-700 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100'"
                            x-on:click="toggle(table)"
                        >
                            Mesa <span class="ml-1" x-text="table"></span>
                        </button>
                    </template>
                </div>
                <p x-show="tableNumbers.length === 0" class="py-6 text-center text-sm text-red-600">Configura la cantidad total de mesas en Administración.</p>
            </div>
        </div>
    </div>

    <script>
        function tableSelector({ baseRedirect, tableNumbers = [], selected = [] }) {
            return {
                baseRedirect,
                tableNumbers,
                selected,
                isSelected(table) {
                    return this.selected.includes(table);
                },
                toggle(table) {
                    if (this.isSelected(table)) {
                        this.selected = this.selected.filter((t) => t !== table);
                    } else {
                        this.selected = [...this.selected, table];
                    }
                },
                clearSelection() {
                    this.selected = [];
                },
                label() {
                    if (!this.selected.length) return 'Sin mesas seleccionadas';
                    const prefix = this.selected.length === 1 ? 'Mesa' : 'Mesas';
                    return `${prefix} ${this.selected.join(' + ')}`;
                },
                confirmSelection() {
                    const params = new URLSearchParams();
                    this.selected.forEach((table) => params.append('tables[]', table));
                    window.location.href = `${this.baseRedirect}?${params.toString()}`;
                },
            };
        }
    </script>
</x-layouts.app>
