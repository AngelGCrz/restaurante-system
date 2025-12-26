<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Gestión de Productos</h1>
            <flux:button variant="primary" icon="plus" href="{{ route('admin.products.create') }}">Nuevo Producto</flux:button>
        </div>

        @if(session('success'))
            <flux:callout variant="success" heading="{{ session('success') }}" />
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="pb-3 font-semibold">Nombre</th>
                        <th class="pb-3 font-semibold">Categoría</th>
                        <th class="pb-3 font-semibold">Precio</th>
                        <th class="pb-3 font-semibold">Estado</th>
                        <th class="pb-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($products as $product)
                        <tr>
                            <td class="py-3">{{ $product->name }}</td>
                            <td class="py-3">{{ $product->category->name ?? 'Sin categoría' }}</td>
                            <td class="py-3">${{ number_format($product->price, 2) }}</td>
                            <td class="py-3">
                                <span class="rounded-full px-2 py-1 text-xs {{ $product->is_available ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    {{ $product->is_available ? 'Disponible' : 'No disponible' }}
                                </span>
                            </td>
                            <td class="py-3 text-right space-x-2">
                                <form action="{{ route('admin.products.update', $product) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="quick_toggle" value="1">
                                    <input type="hidden" name="is_available" value="{{ $product->is_available ? 0 : 1 }}">
                                    <flux:button type="submit" size="sm" variant="ghost" icon="power">
                                        {{ $product->is_available ? 'Desactivar' : 'Activar' }}
                                    </flux:button>
                                </form>

                                <flux:button size="sm" variant="subtle" icon="pencil" href="{{ route('admin.products.edit', $product) }}">Editar</flux:button>

                                <form action="{{ route('admin.products.destroy', $product) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar producto?');">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button type="submit" size="sm" variant="ghost" icon="trash">Eliminar</flux:button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
