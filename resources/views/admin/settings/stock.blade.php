<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <h1 class="text-2xl font-bold">Ajustes de Stock</h1>
            <flux:button variant="subtle" href="{{ route('admin.products.index') }}" icon="arrow-left">Volver</flux:button>
        </div>

        @if(session('success'))
            <flux:callout variant="success" heading="{{ session('success') }}" />
        @endif

        <div class="max-w-xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <form action="{{ route('admin.settings.stock.update') }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="stock_enabled" value="1" {{ $stockEnabled ? 'checked' : '' }}>
                    Habilitar control de stock
                </label>

                <label class="flex items-center gap-2 text-sm font-medium">
                    <input type="checkbox" name="stock_allow_negative" value="1" {{ $allowNegative ? 'checked' : '' }}>
                    Permitir stock negativo
                </label>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="subtle" href="{{ route('admin.products.index') }}">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">Guardar</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
