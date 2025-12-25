<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Gestión de Mesas</h1>
            <flux:button variant="primary" icon="plus" href="{{ route('admin.tables.create') }}">Nueva Mesa</flux:button>
        </div>

        @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                {{ session('success') }}
            </div>
        @endif

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <table class="w-full text-left">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="pb-3 font-semibold">Número</th>
                        <th class="pb-3 font-semibold">Capacidad</th>
                        <th class="pb-3 font-semibold">Estado</th>
                        <th class="pb-3 font-semibold text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @foreach($tables as $table)
                        <tr>
                            <td class="py-3">{{ $table->number }}</td>
                            <td class="py-3">{{ $table->capacity }} personas</td>
                            <td class="py-3">
                                <span class="rounded-full px-2 py-1 text-xs {{ $table->status === 'libre' ? 'bg-green-100 text-green-700' : ($table->status === 'ocupada' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700') }}">
                                    {{ ucfirst($table->status) }}
                                </span>
                            </td>
                            <td class="py-3 text-right space-x-2">
                                <flux:button variant="ghost" icon="pencil" size="sm" href="{{ route('admin.tables.edit', $table) }}"></flux:button>
                                <form action="{{ route('admin.tables.destroy', $table) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button variant="ghost" icon="trash" size="sm" type="submit" onclick="return confirm('¿Eliminar mesa?')"></flux:button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
