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

                <div>
                    <label class="text-sm font-medium">Nombre del Producto</label>
                    <input type="text" name="name" value="{{ old('name', $product->name) }}" required class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700" />
                </div>

                <div>
                    <label class="text-sm font-medium">Categoría</label>
                    <select name="category_id" required class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700">
                        <option value="">Seleccione</option>
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}" @selected($product->category_id === $category->id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="text-sm font-medium">Precio</label>
                    <input type="number" name="price" step="0.01" value="{{ old('price', $product->price) }}" required class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700" />
                </div>

                <div>
                    <label class="text-sm font-medium">Descripción (Opcional)</label>
                    <textarea name="description" class="mt-1 w-full rounded border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700" rows="3">{{ old('description', $product->description) }}</textarea>
                </div>

                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="is_available" value="1" {{ old('is_available', $product->is_available) ? 'checked' : '' }}>
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
