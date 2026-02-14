<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center gap-4">
            <flux:button variant="subtle" icon="arrow-left" href="{{ route('mozo.orders.show', $order) }}" />
            <h1 class="text-2xl font-bold">Agregar Productos al Pedido #{{ $order->id }}</h1>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800">
            <form action="{{ route('mozo.orders.add-items.store', $order) }}" method="POST">
                @csrf

                @foreach($categories as $category)
                    <h3 class="text-lg font-semibold mt-4">{{ $category->name }}</h3>
                    <table class="w-full text-left mb-4">
                        <thead>
                            <tr class="border-b border-zinc-200 dark:border-zinc-700">
                                <th class="pb-3 font-semibold">Producto</th>
                                <th class="pb-3 font-semibold">Precio</th>
                                <th class="pb-3 font-semibold">Cantidad</th>
                                <th class="pb-3 font-semibold">Comentario</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @foreach($category->products as $product)
                                <tr>
                                    <td class="py-2">{{ $product->name }}</td>
                                    <td class="py-2">${{ number_format($product->price, 2) }}</td>
                                    <td class="py-2">
                                        <input type="number" min="0" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][quantity]" value="0" class="w-20 rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white" />
                                        <input type="hidden" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][product_id]" value="{{ $product->id }}">
                                    </td>
                                    <td class="py-2">
                                        <input type="text" name="items[{{ $loop->parent->index }}_{{ $loop->index }}][comment]" placeholder="(opcional)" class="w-full rounded-lg border-zinc-300 dark:border-zinc-600 dark:bg-zinc-900 dark:text-white" />
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endforeach

                <div class="mt-4">
                    <flux:button type="submit" variant="primary">Agregar productos y enviar a Cocina</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>
