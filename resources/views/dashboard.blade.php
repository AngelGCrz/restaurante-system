<x-layouts.app :title="__('Dashboard')">
<div class="w-full space-y-6 p-1">

    {{-- ═══════════════════════════════════════════════════════════════════════
         ADMIN
    ═══════════════════════════════════════════════════════════════════════ --}}
    @if($role === 'admin')

    <div>
        <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-100">Panel de Administración</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Resumen del día — {{ now()->format('d/m/Y') }}</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-5">
        {{-- Ventas hoy --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 space-y-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Ventas hoy</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($ventasHoy, 2) }}</p>
        </div>
        {{-- Pedidos hoy --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 space-y-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Pedidos hoy</p>
            <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ $pedidosHoy }}</p>
        </div>
        {{-- Pendientes --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 space-y-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Pendientes</p>
            <p class="text-2xl font-bold {{ $pedidosPendientes > 0 ? 'text-amber-500' : 'text-zinc-800 dark:text-zinc-100' }}">{{ $pedidosPendientes }}</p>
        </div>
        {{-- Poco stock --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 space-y-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Poco stock</p>
            <p class="text-2xl font-bold {{ $productosPocoStock > 0 ? 'text-orange-500' : 'text-zinc-800 dark:text-zinc-100' }}">{{ $productosPocoStock }}</p>
        </div>
        {{-- Sin stock --}}
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 space-y-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Sin stock</p>
            <p class="text-2xl font-bold {{ $productosSinStock > 0 ? 'text-red-500' : 'text-zinc-800 dark:text-zinc-100' }}">{{ $productosSinStock }}</p>
        </div>
    </div>

    {{-- Accesos rápidos --}}
    <div>
        <h2 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3 uppercase tracking-wide">Accesos rápidos</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 xl:grid-cols-4">
            <a href="{{ route('admin.reports.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">📊</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Reportes</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Ventas, caja, cocina</p>
                </div>
            </a>
            <a href="{{ route('admin.products.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">🛍️</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Productos</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">{{ $totalProductos }} en total</p>
                </div>
            </a>
            <a href="{{ route('admin.users.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">👥</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Usuarios</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Gestionar cuentas</p>
                </div>
            </a>
            <a href="{{ route('admin.tables.edit') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">🪑</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Mesas</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Configurar mesas</p>
                </div>
            </a>
            <a href="{{ route('admin.settings.stock.edit') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">📦</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Stock</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Ajustes de inventario</p>
                </div>
            </a>
            <a href="{{ route('admin.categories.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">🏷️</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Categorías</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Organizar productos</p>
                </div>
            </a>
            <a href="{{ route('admin.tables.release') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">🔓</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Liberar mesas</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Cancelar pedidos activos</p>
                </div>
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         CAJERO
    ═══════════════════════════════════════════════════════════════════════ --}}
    @elseif($role === 'cajero')

    <div>
        <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-100">Panel del Cajero</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">Resumen del día — {{ now()->format('d/m/Y') }}</p>
    </div>

    {{-- Caja status banner --}}
    @if($cajaAbierta)
    <div class="rounded-xl border border-emerald-300 dark:border-emerald-700 bg-emerald-50 dark:bg-emerald-900/30 p-4 flex items-center gap-3">
        <span class="text-2xl">✅</span>
        <div>
            <p class="font-semibold text-emerald-800 dark:text-emerald-300">Caja abierta</p>
            <p class="text-sm text-emerald-700 dark:text-emerald-400">
                Abierta a las {{ $cajaAbierta->opened_at->format('H:i') }}
                · Saldo inicial: ${{ number_format($cajaAbierta->opening_balance, 2) }}
            </p>
        </div>
    </div>
    @else
    <div class="rounded-xl border border-red-300 dark:border-red-700 bg-red-50 dark:bg-red-900/30 p-4 flex items-center gap-3">
        <span class="text-2xl">⚠️</span>
        <div>
            <p class="font-semibold text-red-800 dark:text-red-300">Caja cerrada</p>
            <p class="text-sm text-red-700 dark:text-red-400">Abre la caja desde el Panel de Caja para empezar a operar.</p>
        </div>
    </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 space-y-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Ventas hoy</p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400">${{ number_format($ventasHoy, 2) }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 space-y-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Cobrados hoy</p>
            <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-100">{{ $pedidosCobradosHoy }}</p>
        </div>
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 space-y-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Pendientes de cobro</p>
            <p class="text-2xl font-bold {{ $pedidosPendientes > 0 ? 'text-amber-500' : 'text-zinc-800 dark:text-zinc-100' }}">{{ $pedidosPendientes }}</p>
        </div>
    </div>

    {{-- Accesos rápidos --}}
    <div>
        <h2 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3 uppercase tracking-wide">Accesos rápidos</h2>
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3">
            <a href="{{ route('caja.dashboard') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">💵</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Panel de Caja</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Abrir / cerrar caja</p>
                </div>
            </a>
            <a href="{{ route('orders.payments') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">🧾</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Cobrar pedidos</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Ver pedidos pendientes</p>
                </div>
            </a>
            <a href="{{ route('orders.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">📋</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Todos los pedidos</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Historial completo</p>
                </div>
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         COCINA
    ═══════════════════════════════════════════════════════════════════════ --}}
    @elseif($role === 'cocina')

    <div>
        <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-100">Panel de Cocina</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-5 space-y-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Pedidos por preparar</p>
            <p class="text-3xl font-bold {{ $pedidosPendientes > 0 ? 'text-amber-500' : 'text-emerald-500' }}">{{ $pedidosPendientes }}</p>
            @if($pedidosPendientes === 0)
            <p class="text-sm text-emerald-600 dark:text-emerald-400">¡Todo al día! 🎉</p>
            @endif
        </div>
    </div>

    {{-- Accesos rápidos --}}
    <div>
        <h2 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3 uppercase tracking-wide">Ir a</h2>
        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <a href="{{ route('kitchen.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">🍳</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Panel de Cocina</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Ver y gestionar pedidos</p>
                </div>
            </a>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════════════════
         MOZO
    ═══════════════════════════════════════════════════════════════════════ --}}
    @elseif($role === 'mozo')

    <div>
        <h1 class="text-xl font-semibold text-zinc-800 dark:text-zinc-100">Hola, {{ auth()->user()->name }} 👋</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">{{ now()->format('d/m/Y H:i') }}</p>
    </div>

    {{-- Stats --}}
    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
        <div class="rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-5 space-y-1">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">Mis pedidos activos</p>
            <p class="text-3xl font-bold {{ $misPedidosActivos > 0 ? 'text-amber-500' : 'text-zinc-800 dark:text-zinc-100' }}">{{ $misPedidosActivos }}</p>
        </div>
    </div>

    {{-- Accesos rápidos --}}
    <div>
        <h2 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-3 uppercase tracking-wide">Acciones</h2>
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('mozo.orders.create') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">➕</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Nuevo pedido</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Elegir mesa e items</p>
                </div>
            </a>
            <a href="{{ route('mozo.orders.index') }}" class="flex items-center gap-3 rounded-xl border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800/60 p-4 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <span class="text-2xl">📋</span>
                <div>
                    <p class="font-medium text-zinc-800 dark:text-zinc-100 text-sm">Mis pedidos</p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">Ver mis pedidos activos</p>
                </div>
            </a>
        </div>
    </div>

    @endif

</div>
</x-layouts.app>
