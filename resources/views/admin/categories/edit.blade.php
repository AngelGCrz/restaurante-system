<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Editar Categoría</h1>
            <flux:button variant="subtle" icon="arrow-left" href="{{ route('admin.categories.index') }}">Volver</flux:button>
        </div>

        <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <form action="{{ route('admin.categories.update', $category) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <flux:input name="name" label="Nombre" value="{{ $category->name }}" required />
                <div class="flex justify-end gap-2">
                    <flux:button variant="subtle" href="{{ route('admin.categories.index') }}">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">Actualizar</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
