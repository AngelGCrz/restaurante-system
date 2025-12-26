<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Gestión Categorías</h1>
            <flux:button variant="primary" icon="plus" href="{{ route('admin.categories.create') }}">Nueva Categoría</flux:button>
        </div>

        @if(session('success'))
            <flux:callout variant="success" heading="{{ session('success') }}" />
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="pb-3 font-semibold">Nombre</th>
                        <th class="pb-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($categories as $category)
                        <tr>
                            <td class="py-3">{{ $category->name }}</td>
                            <td class="py-3 text-right space-x-2">
                                <flux:button size="sm" variant="subtle" icon="pencil" href="{{ route('admin.categories.edit', $category) }}">Editar</flux:button>
                                <form action="{{ route('admin.categories.destroy', $category) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar categoría?');">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button type="submit" size="sm" variant="ghost" icon="trash">Eliminar</flux:button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-3 text-sm text-zinc-500" colspan="2">No hay categorías registradas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
