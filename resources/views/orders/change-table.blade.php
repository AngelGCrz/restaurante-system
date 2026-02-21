<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center gap-4">
            <flux:button variant="subtle" icon="arrow-left" href="{{ route('mozo.orders.index') }}" />
            <h1 class="text-2xl font-bold">Cambiar Mesa - Pedido #{{ $order->id }}</h1>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <h2 class="text-lg font-semibold mb-4">Selecciona la(s) nueva(s) mesa(s)</h2>

            <p class="text-sm text-zinc-500 mb-4">Mesa(s) actual(es):
                @if(!empty($order->table_numbers))
                    {{ implode(' + ', $order->table_numbers) }}
                @else
                    Sin mesa asignada
                @endif
            </p>

            <form action="{{ route('mozo.orders.change-table.update', $order) }}" method="POST">
                @csrf

                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                    @foreach($tableNumbers as $num)
                        @php
                            $isBusy = in_array($num, $busyTables ?? []);
                            $checked = in_array($num, $order->table_numbers ?? []);
                        @endphp

                        <label class="flex items-center gap-2 p-3 rounded-lg border cursor-pointer
                            {{ $isBusy && !$checked ? 'opacity-50 cursor-not-allowed bg-zinc-50 dark:bg-zinc-900' : 'bg-white dark:bg-zinc-800' }}">
                            <input type="checkbox" name="tables[]" value="{{ $num }}" {{ $checked ? 'checked' : '' }} {{ $isBusy && !$checked ? 'disabled' : '' }}>
                            <span class="font-medium">Mesa {{ $num }}</span>
                            @if($isBusy && !$checked)
                                <span class="ml-auto text-xs text-red-600">Ocupada</span>
                            @endif
                        </label>
                    @endforeach
                </div>

                @error('tables')
                    <p class="text-red-600 text-sm mb-2">{{ $message }}</p>
                @enderror

                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary">Trasladar Pedido</flux:button>
                    <flux:button href="{{ route('mozo.orders.index') }}" variant="subtle">Cancelar</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
