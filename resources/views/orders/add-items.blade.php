<x-layouts.app>
    <div class="min-h-screen bg-gradient-to-br from-gray-900 to-gray-800 text-white p-6">

        <form method="POST" action="{{ route('mozo.orders.add-items.store', $order) }}">
            @csrf

            <div x-data="orderManager()">

                {{-- 📋 Información del Pedido --}}
        <div class="mb-6 bg-gray-900 border border-gray-700 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold">Pedido #{{ $order->id }}</h1>
                    <p class="text-gray-400 text-sm mt-1">
                        Mesa: <span class="text-white font-semibold">{{ $order->table_label }}</span>
                    </p>
                    @if($order->customer_name)
                        <p class="text-gray-400 text-sm">
                            Cliente: <span class="text-white">{{ $order->customer_name }}</span>
                        </p>
                    @endif
                </div>
                <div class="text-right">
                    <span class="px-3 py-1 rounded-full text-xs font-semibold
                        {{ $order->status === 'pagado' ? 'bg-green-600' : 
                           ($order->status === 'pendiente' ? 'bg-yellow-600' : 'bg-red-600') }}">
                        {{ ucfirst($order->status) }}
                    </span>
                </div>
            </div>
        </div>

                {{-- Botones de Categorías --}}
                <div class="flex gap-2 mb-6 flex-wrap">
                    @foreach($categories as $category)
                        <button type="button"
                            @click="activeCategory = Number({{ $category->id }})"

                            :class="activeCategory == {{ $category->id }}
                                    ? 'bg-white text-black'
                                    : 'bg-gray-800 text-white'"
                            class="px-4 py-2 rounded-full text-sm font-semibold transition border border-gray-600">
                            {{ $category->name }}
                        </button>
                    @endforeach
                </div>

                {{-- Productos --}}
                @foreach($categories as $category)
                    <div x-show="activeCategory === {{ $category->id }}"

                         class="grid grid-cols-2 md:grid-cols-4 gap-4">

                        @foreach($category->products as $product)
                            <div class="bg-gray-900 p-4 rounded-2xl border border-gray-700 shadow hover:shadow-lg transition">

                                <h3 class="font-semibold">
                                    {{ $product->name }}
                                </h3>

                                <p class="text-sm text-gray-400">
                                    S/ {{ number_format($product->price, 2) }}
                                </p>

                                <div class="flex items-center justify-between mt-3">

                                    <button type="button"
                                        @click="decrease({{ $product->id }})"
                                        class="bg-red-600 hover:bg-red-700 px-3 py-1 rounded-lg">
                                        -
                                    </button>

                                    <span class="font-bold"
                                          x-text="getQty({{ $product->id }})">
                                    </span>

                                    <button type="button"
                                        @click="increase({{ $product->id }}, '{{ $product->name }}', {{ (float) $product->price }})"
                                        class="bg-green-600 hover:bg-green-700 px-3 py-1 rounded-lg">
                                        +
                                    </button>

                                </div>

                            </div>
                        @endforeach

                    </div>
                @endforeach

                {{-- 🧾 Detalle del Pedido --}}
                <div class="mt-8 bg-gray-900 border border-gray-700 rounded-2xl p-6">
                    <h2 class="text-lg font-bold mb-4">Detalle del Pedido</h2>
                
                    <template x-if="items.length === 0">
                        <p class="text-gray-400 text-sm">No hay productos seleccionados</p>
                    </template>
                
                    <template x-for="(item, index) in items" :key="item.id">
                        <div class="flex justify-between items-center mb-3 border-b border-gray-700 pb-2">
                            <div>
                                <p class="font-semibold" x-text="item.name"></p>
                                <p class="text-sm text-gray-400">
                                    Cant: <span x-text="item.quantity"></span> × 
                                    S/ <span x-text="item.price.toFixed(2)"></span>
                                </p>
                            </div>
                        
                            <div class="text-right">
                                <p class="font-bold"
                                   x-text="'S/ ' + (item.quantity * item.price).toFixed(2)">
                                </p>
                            </div>
                        </div>
                    </template>
                
                    {{-- Total --}}
                    <div class="flex justify-between items-center mt-4 text-xl font-bold">
                        <span>Total:</span>
                        <span x-text="'S/ ' + total().toFixed(2)"></span>
                    </div>
                
                    {{-- Botón limpiar --}}
                    <button type="button"
                        @click="items = []"
                        class="mt-4 bg-red-600 hover:bg-red-700 px-4 py-2 rounded-lg text-sm">
                        Limpiar
                    </button>
                </div>


                {{-- Inputs ocultos --}}
                <template x-for="(item, index) in items" :key="index">
                    <div>
                        <input type="hidden" :name="'items[' + index + '][product_id]'" :value="item.id">
                        <input type="hidden" :name="'items[' + index + '][quantity]'" :value="item.quantity">
                    </div>
                </template>

                {{-- Botón flotante --}}
                <button type="submit"
                    class="fixed bottom-6 right-6 bg-green-600 hover:bg-green-700 px-6 py-3 rounded-2xl text-white font-bold shadow-xl">
                    Agregar al Pedido
                </button>

            </div>
        </form>

    </div>

    @push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('orderManager', () => ({
        activeCategory: {{ $categories->first()?->id ?? 0 }},
        items: [],

        increase(id, name, price) {
            // ✅ Forzar que price sea número flotante
            price = parseFloat(price);
            let existing = this.items.find(p => p.id === id);
            if (existing) {
                existing.quantity++;
            } else {
                this.items.push({ id, name, price, quantity: 1 });
            }
        },

        decrease(id) {
            let existing = this.items.find(p => p.id === id);
            if (!existing) return;

            existing.quantity--;
            if (existing.quantity <= 0) {
                this.items = this.items.filter(p => p.id !== id);
            }
        },

        getQty(id) {
            let item = this.items.find(p => p.id === id);
            return item ? item.quantity : 0;
        },
        
        total() {
        return this.items.reduce((sum, item) => {
            return sum + (item.price * item.quantity);
        }, 0);
    }
    }))
})
</script>
@endpush


</x-layouts.app>
