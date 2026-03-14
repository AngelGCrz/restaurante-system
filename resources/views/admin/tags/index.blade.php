<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Tags de Comentarios</h1>
            <flux:button variant="primary" icon="plus" href="{{ route('admin.tags.create') }}">Nuevo Tag</flux:button>
        </div>

        @if(session('success'))
            <flux:callout variant="success" heading="{{ session('success') }}" />
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="pb-3 font-semibold">#</th>
                        <th class="pb-3 font-semibold">Nombre</th>
                        <th class="pb-3 font-semibold text-center">Estado</th>
                        <th class="pb-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse($tags as $tag)
                        <tr>
                            <td class="py-3 text-zinc-400">{{ $tag->sort_order }}</td>
                            <td class="py-3 font-medium">{{ $tag->name }}</td>
                            <td class="py-3 text-center">
                                @if($tag->is_active)
                                    <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">Activo</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-semibold text-zinc-500">Inactivo</span>
                                @endif
                            </td>
                            <td class="py-3 text-right space-x-2">
                                <flux:button size="sm" variant="subtle" icon="pencil" href="{{ route('admin.tags.edit', $tag) }}">Editar</flux:button>
                                <form action="{{ route('admin.tags.destroy', $tag) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar tag «{{ $tag->name }}»?');">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button type="submit" size="sm" variant="ghost" icon="trash">Eliminar</flux:button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="py-3 text-sm text-zinc-500" colspan="4">No hay tags registrados. Crea uno para que los mozos puedan usarlo como comentario rápido.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
