<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <h1 class="text-2xl font-bold">Crear Producto</h1>

        @if($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc pl-4 space-y-1">
                    @foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach
                </ul>
            </div>
        @endif

        <div class="max-w-2xl rounded-xl border border-zinc-200 bg-white p-6 dark:border-zinc-700 dark:bg-zinc-800"
             x-data="{
                 rows: [{ category_id: '', price: '' }],
                 addRow() { this.rows.push({ category_id: '', price: '' }); },
                 removeRow(i) { if (this.rows.length > 1) this.rows.splice(i, 1); },
             }">
            <form action="{{ route('admin.products.store') }}" method="POST" class="space-y-4">
                @csrf

                <flux:input name="name" label="Nombre del Producto" required value="{{ old('name') }}" />

                <flux:input name="stock" label="Stock inicial" type="number" step="1" value="{{ old('stock', 0) }}" />

                <flux:textarea name="description" label="Descripción (Opcional)">{{ old('description') }}</flux:textarea>

                {{-- Categorías con precio propio --}}
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium text-zinc-700 dark:text-zinc-200">
                            Categoría y Precio
                            <span class="text-red-500">*</span>
                        </label>
                        <button type="button" @click="addRow()"
                            class="text-xs text-blue-600 hover:text-blue-800 font-medium hover:underline">
                            + Agregar otra categoría
                        </button>
                    </div>

                    <div class="space-y-2">
                        <template x-for="(row, i) in rows" :key="i">
                            <div class="flex items-center gap-2">
                                <select :name="'category_prices[' + i + '][category_id]'"
                                    x-model="row.category_id"
                                    required
                                    class="flex-1 rounded-lg border border-zinc-300 px-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700">
                                    <option value="">— Seleccione categoría —</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                <div class="relative">
                                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-sm">S/</span>
                                    <input type="number" step="0.01" min="0"
                                        :name="'category_prices[' + i + '][price]'"
                                        x-model="row.price"
                                        placeholder="0.00"
                                        required
                                        class="w-28 rounded-lg border border-zinc-300 pl-8 pr-3 py-2 text-sm dark:border-zinc-600 dark:bg-zinc-700" />
                                </div>
                                <button type="button" @click="removeRow(i)"
                                    x-show="rows.length > 1"
                                    class="flex-shrink-0 text-rose-500 hover:text-rose-700 text-xl font-bold leading-none w-8 h-8 flex items-center justify-center rounded-full hover:bg-rose-50">×</button>
                            </div>
                        </template>
                    </div>

                    <p class="text-xs text-zinc-500 mt-2">
                        💡 Puedes agregar el mismo producto a varias categorías con precios distintos. El stock es compartido.
                    </p>
                </div>

                <div class="flex justify-end gap-2 pt-2">
                    <flux:button variant="subtle" href="{{ route('admin.products.index') }}">Cancelar</flux:button>
                    <flux:button variant="primary" type="submit">Guardar Producto</flux:button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.app>

