<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Reporte de Ventas</h1>
                <p class="text-sm text-zinc-600">Totales, tickets y tendencias.</p>
            </div>
            <flux:button href="{{ route('admin.reports.index') }}" variant="ghost" icon="arrow-left">Volver</flux:button>
        </div>

        <flux:callout heading="En construcción" description="Define filtros (rango de fechas, tipo de pedido, usuario) y las métricas de ventas que necesitas." icon="wrench" />
    </div>
</x-layouts.app>
