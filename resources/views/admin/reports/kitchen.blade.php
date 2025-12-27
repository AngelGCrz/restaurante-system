<x-layouts.app>
    <div class="flex h-full w-full flex-1 flex-col gap-4 p-4">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold">Reporte de Cocina</h1>
                <p class="text-sm text-zinc-600">Tiempos de preparación y cola de pedidos.</p>
            </div>
            <flux:button href="{{ route('admin.reports.index') }}" variant="ghost" icon="arrow-left">Volver</flux:button>
        </div>

        <flux:callout heading="En construcción" description="Incluye tiempos promedio, cuellos de botella y pendientes." icon="wrench" />
    </div>
</x-layouts.app>
