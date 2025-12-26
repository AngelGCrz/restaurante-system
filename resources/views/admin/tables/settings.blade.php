<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Configuración de Mesas</h1>
                <p class="text-sm text-zinc-600">Define la cantidad total de mesas. El sistema generará Mesa 1, Mesa 2, ... Mesa N automáticamente.</p>
            </div>
        </div>

        @if(session('success'))
            <div class="relative rounded border border-green-400 bg-green-100 px-4 py-3 text-green-700">
                {{ session('success') }}
            </div>
        @endif

        <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <form action="{{ route('admin.tables.update') }}" method="POST" class="flex flex-col gap-6">
                @csrf
                @method('PUT')

                <flux:input
                    name="total_tables"
                    type="number"
                    label="Cantidad total de mesas"
                    min="0"
                    value="{{ old('total_tables', $tableCount) }}"
                    required
                />
                <p class="text-sm text-zinc-600">Ejemplo: si estableces 20, el sistema mostrará Mesa 1 a Mesa 20 para los mozos.</p>

                <div class="flex justify-end gap-2">
                    <flux:button type="submit" variant="primary">Guardar cambios</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
