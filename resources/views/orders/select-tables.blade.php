<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">

        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Seleccionar Mesa</h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    Mesa libre → nuevo pedido &nbsp;|&nbsp; Mesa tuya activa → agregar nueva orden
                </p>
            </div>
            <a href="{{ route('mozo.orders.index') }}"
               class="inline-flex items-center gap-1 rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium text-red-600 hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-red-400">
                ← Volver
            </a>
        </div>

        {{-- Leyenda --}}
        <div class="flex flex-wrap gap-3 text-xs font-medium">
            <span class="flex items-center gap-1.5 rounded-full border border-zinc-300 bg-white px-3 py-1 dark:border-zinc-600 dark:bg-zinc-800">
                <span class="h-3 w-3 rounded-full bg-white border-2 border-zinc-400"></span> Libre
            </span>
            <span class="flex items-center gap-1.5 rounded-full border border-amber-400 bg-white px-3 py-1 text-amber-700 dark:border-amber-500 dark:bg-zinc-800 dark:text-amber-300">
                <span class="h-3 w-3 rounded-full bg-amber-400"></span> Tu mesa activa → agregar orden
            </span>
            <span class="flex items-center gap-1.5 rounded-full border border-red-300 bg-white px-3 py-1 text-red-600 dark:border-red-500 dark:bg-zinc-800 dark:text-red-400">
                <span class="h-3 w-3 rounded-full bg-red-400"></span> Ocupada por otro mozo
            </span>
        </div>

        <div
            class="rounded-xl border border-dashed border-zinc-300 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900"
            x-data="mesaSelector()"
            x-init="init()"
        >
            {{-- Toolbar --}}
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <p class="text-sm font-medium">{{ $tableCount }} mesas configuradas</p>
                    {{-- Label selección --}}
                    <p class="mt-1 text-sm" x-cloak>
                        <span x-show="selected.length === 0" class="font-semibold text-red-600">Sin mesas seleccionadas</span>
                        <span x-show="selected.length > 0" class="font-bold text-blue-700 dark:text-blue-300"
                              x-text="(selected.length === 1 ? 'Mesa' : 'Mesas') + ' ' + selected.join(' + ')"></span>
                    </p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <button type="button"
                            class="rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm font-medium hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800"
                            @click="selected = []">
                        🔄 Limpiar
                    </button>
                    <button type="button"
                            class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-40"
                            :disabled="selected.length === 0"
                            @click="confirmar()">
                        ✓ Confirmar selección
                    </button>
                </div>
            </div>

            {{-- Grid de mesas --}}
            <div class="max-h-[60vh] overflow-y-auto">
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    @foreach($tableNumbers as $num)
                    @php
                        $isOtherBusy = in_array($num, $otherBusyTables ?? []);
                        $isMyTable   = array_key_exists($num, $myTableOrderMap ?? []);
                        $myOrderId   = $myTableOrderMap[$num] ?? null;
                    @endphp

                    <button
                        type="button"
                        @if($isOtherBusy) disabled @endif
                        @if($isMyTable)
                            {{-- Mesa propia activa: ir directo a agregar orden --}}
                            @click="window.location.href = '{{ route('mozo.orders.add-items', $myOrderId) }}'"
                            class="flex h-20 flex-col items-center justify-center rounded-xl border-2 border-amber-400 bg-white text-amber-700 font-semibold text-sm transition hover:bg-amber-50 dark:border-amber-500 dark:bg-zinc-800 dark:text-amber-300"
                        @elseif($isOtherBusy)
                            class="flex h-20 flex-col items-center justify-center rounded-xl border-2 border-red-300 bg-white text-red-400 font-semibold text-sm cursor-not-allowed dark:border-red-600 dark:bg-zinc-800 dark:text-red-400"
                        @else
                            {{-- Mesa libre: toggle selección --}}
                            @click="toggleMesa({{ $num }})"
                            :class="selected.includes({{ $num }}) ? 'border-blue-500 bg-blue-50 text-blue-700 dark:border-blue-400 dark:bg-blue-900/30 dark:text-blue-100' : 'border-zinc-200 bg-white text-zinc-700 hover:border-zinc-400 hover:bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-100'"
                            class="flex h-20 flex-col items-center justify-center rounded-xl border-2 font-semibold text-sm transition"
                        @endif
                    >
                        <span>Mesa {{ $num }}</span>
                        <span class="mt-1 text-xs font-normal">
                            @if($isOtherBusy)   🔒 Ocupada
                            @elseif($isMyTable) ✏️ Agregar orden
                            @endif
                        </span>
                        @if(!$isOtherBusy && !$isMyTable)
                        <span class="mt-1 text-xs font-normal" x-cloak
                              x-show="selected.includes({{ $num }})" x-text="'✓ Seleccionada'">
                        </span>
                        @endif
                    </button>
                    @endforeach
                </div>

                @if(count($tableNumbers) === 0)
                    <p class="py-6 text-center text-sm text-red-600">
                        Configura la cantidad total de mesas en Administración.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <script>
    function mesaSelector() {
        return {
            selected: @json($selectedTables ?? []),

            init() {
                // noop — todo lo necesario está en Blade
            },

            toggleMesa(num) {
                if (this.selected.includes(num)) {
                    this.selected = this.selected.filter(t => t !== num);
                } else {
                    this.selected = [...this.selected, num];
                }
            },

            confirmar() {
                if (this.selected.length === 0) return;
                const base = '{{ route('mozo.orders.create') }}';
                const params = new URLSearchParams();
                this.selected.forEach(t => params.append('tables[]', t));
                window.location.href = base + '?' + params.toString();
            },
        };
    }
    </script>

    {{-- Bloquear navegación hacia atrás --}}
    <script>
        history.replaceState(null, '', window.location.href);

        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                window.location.replace('{{ route('mozo.orders.index') }}');
            }
        });
    </script>
</x-layouts.app>