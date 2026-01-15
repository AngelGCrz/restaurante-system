<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Cuentas</h1>

        <div class="flex items-center justify-end">
            <flux:button variant="primary" icon="plus" href="{{ route('admin.users.create') }}">Nueva Cuenta</flux:button>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <table class="w-full table-auto">
                <thead>
                    <tr class="text-left text-sm text-zinc-500">
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($users as $user)
                        <tr class="border-t">
                            <td class="py-3">{{ $user->name }}</td>
                            <td class="py-3">{{ $user->email }}</td>
                            <td class="py-3">{{ optional($user->role)->display_name ?? optional($user->role)->name }}</td>
                            <td class="py-3 text-right">
                                <flux:button size="sm" variant="subtle" icon="pencil" href="{{ route('admin.users.edit', $user) }}">Editar</flux:button>
                                <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('¿Eliminar usuario?');">
                                    @csrf
                                    @method('DELETE')
                                    <flux:button size="sm" variant="danger" type="submit">Eliminar</flux:button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
