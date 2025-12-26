<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Editar Producto</h1>
            <flux:button variant="subtle" href="{{ route('admin.products.index') }}" icon="arrow-left">Volver</flux:button>
        </div>

        <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <form action="{{ route('admin.products.update', $product) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <flux:input name="name" label="Nombre del Producto" value="{{ $product->name }}" required />
                <flux:input name="price" label="Precio" type="number" step="0.01" value="{{ $product->price }}" required />
                <flux:textarea name="description" label="Descripción (Opcional)">{{ $product->description }}</flux:textarea>

                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="is_available" value="1" {{ $product->is_available ? 'checked' : '' }}>
                    Disponible
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="subtle" href="{{ route('admin.products.index') }}">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">Actualizar</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
