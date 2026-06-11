<x-layouts.app>
<div class="flex h-full w-full flex-1 flex-col gap-4 p-3 sm:p-4">

    {{-- Encabezado --}}
    <div class="flex flex-wrap items-start justify-between gap-2">
        <div>
            <h1 class="text-xl font-bold text-zinc-900 dark:text-zinc-100 sm:text-2xl">Reporte de Inventario</h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400">Stock actual de todos los productos.</p>
        </div>
        <flux:button href="{{ route('admin.reports.index') }}" variant="ghost" icon="arrow-left">Volver</flux:button>
    </div>

    {{-- Tarjetas resumen --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Total productos</div>
            <div class="mt-1 text-2xl font-bold text-zinc-900 dark:text-zinc-100">{{ $totalProducts }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Stock bajo (≤5)</div>
            <div class="mt-1 text-2xl font-bold text-yellow-600">{{ $lowStock }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Sin stock</div>
            <div class="mt-1 text-2xl font-bold text-red-600">{{ $outOfStock }}</div>
        </div>
        <div class="rounded-xl border border-zinc-200 bg-white p-4 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="text-xs text-zinc-500 dark:text-zinc-400">Valor total stock</div>
            <div class="mt-1 text-2xl font-bold text-blue-600">${{ number_format($totalStockValue, 2) }}</div>
        </div>
    </div>

    {{-- Alerta stock bajo --}}
    @if($lowStock > 0 || $outOfStock > 0)
    <div class="rounded-lg border border-yellow-200 bg-yellow-50 px-4 py-3 text-sm text-yellow-800 dark:border-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-300">
        ⚠️ Hay <strong>{{ $outOfStock }}</strong> producto(s) sin stock y <strong>{{ $lowStock }}</strong> con stock bajo. Revisa la lista a continuación.
    </div>
    @endif

    {{-- Tabla de productos --}}
    <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
            <h2 class="font-semibold text-zinc-900 dark:text-zinc-100">Productos</h2>
        </div>

        {{-- Mobile --}}
        <div class="block space-y-2 p-3 lg:hidden">
            @forelse ($products as $p)
                <div @class([
                    'rounded-lg border p-3',
                    'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/10' => $p->stock <= 0,
                    'border-yellow-200 bg-yellow-50 dark:border-yellow-800 dark:bg-yellow-900/10' => $p->stock > 0 && $p->stock <= 5,
                    'border-zinc-100 dark:border-zinc-800' => $p->stock > 5,
                ])>
                    <div class="flex items-center justify-between">
                        <span class="font-medium text-zinc-900 dark:text-zinc-100">{{ $p->name }}</span>
                        <span @class([
                            'rounded px-2 py-0.5 text-xs font-bold',
                            'bg-red-100 text-red-700' => $p->stock <= 0,
                            'bg-yellow-100 text-yellow-700' => $p->stock > 0 && $p->stock <= 5,
                            'bg-green-100 text-green-700' => $p->stock > 5,
                        ])>{{ $p->stock }} uds.</span>
                    </div>
                    <div class="mt-1 flex gap-4 text-xs text-zinc-500">
                        <span>Precio: ${{ number_format($p->price, 2) }}</span>
                        <span>Valor: ${{ number_format($p->stock * $p->price, 2) }}</span>
                        <span>{{ $p->is_available ? 'Disponible' : 'No disponible' }}</span>
                    </div>
                </div>
            @empty
                <p class="py-6 text-center text-sm text-zinc-400">No hay productos registrados.</p>
            @endforelse
        </div>

        {{-- Desktop --}}
        <div class="hidden overflow-x-auto lg:block">
            <table class="w-full text-left text-sm text-zinc-900 dark:text-zinc-100">
                <thead class="border-b border-zinc-100 bg-zinc-50 text-xs uppercase text-zinc-500 dark:border-zinc-800 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">Producto</th>
                        <th class="px-4 py-3 text-right">Precio</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th class="px-4 py-3 text-right">Valor stock</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Disponibilidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-800">
                    @forelse ($products as $p)
                        <tr @class([
                            'hover:bg-zinc-50 dark:hover:bg-zinc-800/50',
                            'bg-red-50/40 dark:bg-red-900/10' => $p->stock <= 0,
                            'bg-yellow-50/40 dark:bg-yellow-900/10' => $p->stock > 0 && $p->stock <= 5,
                        ])>
                            <td class="px-4 py-3 font-medium">{{ $p->name }}</td>
                            <td class="px-4 py-3 text-right">${{ number_format($p->price, 2) }}</td>
                            <td class="px-4 py-3 text-right font-bold">{{ $p->stock }}</td>
                            <td class="px-4 py-3 text-right">${{ number_format($p->stock * $p->price, 2) }}</td>
                            <td class="px-4 py-3">
                                @if($p->stock <= 0)
                                    <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700">Sin stock</span>
                                @elseif($p->stock <= 5)
                                    <span class="rounded bg-yellow-100 px-2 py-0.5 text-xs font-medium text-yellow-700">Stock bajo</span>
                                @else
                                    <span class="rounded bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700">OK</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="{{ $p->is_available ? 'text-green-600' : 'text-zinc-400' }}">
                                    {{ $p->is_available ? 'Disponible' : 'Desactivado' }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-4 py-8 text-center text-zinc-400">No hay productos registrados.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
</x-layouts.app>
