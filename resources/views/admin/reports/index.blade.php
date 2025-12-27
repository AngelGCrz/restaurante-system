<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Reportes</h1>
                <p class="text-sm text-zinc-600">Selecciona el reporte que necesitas consultar.</p>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-3">
            @php($links = [
                ['ruta' => route('admin.reports.sales'), 'titulo' => 'Ventas', 'desc' => 'Totales, tickets y tendencias'],
                ['ruta' => route('admin.reports.cash'), 'titulo' => 'Caja', 'desc' => 'Aperturas, cierres y arqueos'],
                ['ruta' => route('admin.reports.inventory'), 'titulo' => 'Inventario', 'desc' => 'Stock y movimientos'],
                ['ruta' => route('admin.reports.customers'), 'titulo' => 'Clientes', 'desc' => 'Frecuencia y consumo'],
                ['ruta' => route('admin.reports.tables'), 'titulo' => 'Mesas', 'desc' => 'Ocupación y rotación'],
                ['ruta' => route('admin.reports.kitchen'), 'titulo' => 'Cocina', 'desc' => 'Tiempos de preparación'],
                ['ruta' => route('admin.reports.profit'), 'titulo' => 'Ganancias', 'desc' => 'Margen y rentabilidad real'],
            ])
            @foreach ($links as $link)
                <a href="{{ $link['ruta'] }}" class="flex flex-col gap-1 rounded-xl border border-zinc-200 bg-white p-4 shadow-sm transition hover:border-primary-300 hover:shadow-md dark:border-zinc-700 dark:bg-zinc-900">
                    <span class="text-base font-semibold text-zinc-900 dark:text-zinc-50">{{ $link['titulo'] }}</span>
                    <span class="text-sm text-zinc-600 dark:text-zinc-400">{{ $link['desc'] }}</span>
                </a>
            @endforeach
        </div>
    </div>
</x-layouts.app>
