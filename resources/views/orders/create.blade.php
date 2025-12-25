<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Nuevo Pedido</h1>

        <form action="{{ route('orders.store') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            @csrf
            <div class="lg:col-span-2 space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 class="text-lg font-semibold mb-4">Productos Disponibles</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach($products as $product)
                            <div class="flex items-center justify-between p-3 border border-zinc-100 rounded-lg dark:border-zinc-700">
                                <div>
                                    <p class="font-medium">{{ $product->name }}</p>
                                    <p class="text-sm text-zinc-500">${{ number_format($product->price, 2) }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <input type="number" name="items[{{ $product->id }}][quantity]" value="0" min="0" class="w-16 rounded border-zinc-300 text-sm dark:bg-zinc-700 dark:border-zinc-600">
                                    <input type="hidden" name="items[{{ $product->id }}][product_id]" value="{{ $product->id }}">
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
                    <h2 class="text-lg font-semibold mb-4">Información del Pedido</h2>
                    <div class="space-y-4">
                        <flux:input name="customer_name" label="Nombre del Cliente" placeholder="Opcional" />
                        <flux:select name="type" label="Tipo de Servicio">
                            <flux:select.option value="mesa">En Mesa</flux:select.option>
                            <flux:select.option value="llevar">Para Llevar</flux:select.option>
                        </flux:select>
                        <hr class="dark:border-zinc-700">
                        <flux:button variant="primary" type="submit" class="w-full">Registrar Pedido</flux:button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</x-layouts.app>
