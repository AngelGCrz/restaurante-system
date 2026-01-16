<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Crear Producto</h1>

        <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <form action="{{ route('admin.products.store') }}" method="POST" class="space-y-4">
                @csrf
                <flux:input name="name" label="Nombre del Producto" required />
                <flux:select name="category_id" label="Categoría" required>
                    @foreach($categories as $category)
                        <flux:select.option value="{{ $category->id }}">{{ $category->name }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:input name="price" label="Precio" type="number" step="0.01" required />
                <flux:input name="stock" label="Stock inicial" type="number" step="1" />
                <flux:textarea name="description" label="Descripción (Opcional)" />
                <div class="flex justify-end gap-2">
                    <flux:button variant="subtle" href="{{ route('admin.products.index') }}">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">Guardar Producto</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
