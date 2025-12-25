<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Editar Mesa: {{ $table->number }}</h1>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800 max-w-lg">
            <form action="{{ route('admin.tables.update', $table) }}" method="POST" class="flex flex-col gap-6">
                @csrf
                @method('PUT')
                <flux:input name="number" label="Número de Mesa" value="{{ $table->number }}" required />
                <flux:input name="capacity" type="number" label="Capacidad" value="{{ $table->capacity }}" required />
                <flux:select name="status" label="Estado">
                    <flux:select.option value="libre" :selected="$table->status === 'libre'">Libre</flux:select.option>
                    <flux:select.option value="ocupada" :selected="$table->status === 'ocupada'">Ocupada</flux:select.option>
                    <flux:select.option value="reservada" :selected="$table->status === 'reservada'">Reservada</flux:select.option>
                </flux:select>

                <div class="flex justify-end gap-2">
                    <flux:button href="{{ route('admin.tables.index') }}" variant="ghost">Cancelar</flux:button>
                    <flux:button type="submit" variant="primary">Actualizar Mesa</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
