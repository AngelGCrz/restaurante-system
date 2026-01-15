<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Crear Cuenta</h1>

        <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                @csrf
                <flux:input name="name" label="Nombre" required />
                <flux:input name="email" label="Email" type="email" required />
                <flux:input name="password" label="Contraseña" type="password" />
                <flux:input name="password_confirmation" label="Confirmar Contraseña" type="password" />

                <flux:select name="role_id" label="Rol" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}">{{ $role->display_name ?? $role->name }}</option>
                    @endforeach
                </flux:select>

                <div class="flex justify-end gap-2">
                    <flux:button variant="subtle" href="{{ route('admin.users.index') }}">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">Crear Cuenta</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
