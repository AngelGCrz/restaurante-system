<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Editar Cuenta</h1>

        <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <flux:input name="name" label="Nombre" :value="old('name', $user->name)" required />
                <flux:input name="email" label="Email" type="email" :value="old('email', $user->email)" required />
                <flux:input name="password" label="Contraseña (dejar vacío para no cambiar)" type="password" />
                <flux:input name="password_confirmation" label="Confirmar Contraseña" type="password" />

                <flux:select name="role_id" label="Rol" required>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ $user->role_id === $role->id ? 'selected' : '' }}>{{ $role->display_name ?? $role->name }}</option>
                    @endforeach
                </flux:select>

                <div class="flex justify-end gap-2">
                    <flux:button variant="subtle" href="{{ route('admin.users.index') }}">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">Guardar</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
