<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Editar Tag</h1>
            <flux:button variant="subtle" icon="arrow-left" href="{{ route('admin.tags.index') }}">Volver</flux:button>
        </div>

        <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <form action="{{ route('admin.tags.update', $tag) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <flux:input name="name" label="Nombre del tag" value="{{ $tag->name }}" required />
                @error('name')
                    <p class="text-sm text-red-600">{{ $message }}</p>
                @enderror
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_active" value="1" {{ $tag->is_active ? 'checked' : '' }}
                           class="rounded border-zinc-300 text-indigo-600 focus:ring-indigo-500 dark:border-zinc-600 dark:bg-zinc-900">
                    Activo
                </label>
                <div class="flex justify-end gap-2">
                    <flux:button variant="subtle" href="{{ route('admin.tags.index') }}">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">Actualizar</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
