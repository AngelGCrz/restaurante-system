<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid">
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                </flux:navlist.group>

                @if(auth()->user()->role->name === 'admin')
                    <flux:navlist.group heading="Administración" class="grid">
                        <flux:navlist.item icon="shopping-bag" :href="route('admin.products.index')" :current="request()->routeIs('admin.products.*')" wire:navigate>Productos</flux:navlist.item>
                        <flux:navlist.item icon="users" :href="route('admin.users.index')" :current="request()->routeIs('admin.users.*')" wire:navigate>Cuentas</flux:navlist.item>
                        @if (Route::has('admin.categories.index'))
                            <flux:navlist.item icon="swatch" :href="route('admin.categories.index')" :current="request()->routeIs('admin.categories.*')" wire:navigate>Categorías</flux:navlist.item>
                        @endif
                        <flux:navlist.item icon="table-cells" :href="route('admin.tables.edit')" :current="request()->routeIs('admin.tables.*')" wire:navigate>Mesas</flux:navlist.item>
                        <flux:navlist.item icon="cube" :href="route('admin.settings.stock.edit')" :current="request()->routeIs('admin.settings.stock.*')" wire:navigate>Ajustes de Stock</flux:navlist.item>
                        <flux:navlist.item icon="chart-bar" :href="route('admin.reports.index')" :current="request()->routeIs('admin.reports.*')" wire:navigate>Reportes</flux:navlist.item>
                    </flux:navlist.group>
                @endif

                @if(auth()->user()->role->name === 'mozo')
                    <flux:navlist.group heading="Atención" class="grid">
                        <flux:navlist.item icon="plus-circle" :href="route('mozo.orders.create')" :current="request()->routeIs('mozo.orders.create')" wire:navigate>Nuevo Pedido</flux:navlist.item>
                    </flux:navlist.group>
                @endif

                @if(auth()->user()->role->name === 'cocina')
                    <flux:navlist.group heading="Cocina" class="grid">
                        <flux:navlist.item icon="clipboard-document-list" :href="route('kitchen.index')" :current="request()->routeIs('kitchen.*')" wire:navigate>Monitor</flux:navlist.item>
                    </flux:navlist.group>
                @endif

                @if(auth()->user()->role->name === 'cajero')
                    <flux:navlist.group heading="Caja" class="grid">
                        <flux:navlist.item icon="banknotes" :href="route('orders.index')" :current="request()->routeIs('orders.*')" wire:navigate>Pedidos</flux:navlist.item>
                    </flux:navlist.group>
                @endif
            </flux:navlist>

            <flux:spacer />

            <flux:navlist variant="outline">
                <flux:navlist.item icon="folder-open" href="https://github.com/laravel/livewire-starter-kit" target="_blank">
                {{ __('Repository') }}
                </flux:navlist.item>

                <flux:navlist.item icon="book-open" href="https://laravel.com/docs/starter-kits#livewire" target="_blank">
                {{ __('Documentation') }}
                </flux:navlist.item>
            </flux:navlist>

            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('profile.edit')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        <script>
            // Global toast helper (used across pages)
            window.showToast = window.showToast || function(message, variant = 'error') {
                const containerId = 'app-toasts-container';
                let container = document.getElementById(containerId);
                if (!container) {
                    container = document.createElement('div');
                    container.id = containerId;
                    container.style.position = 'fixed';
                    container.style.right = '16px';
                    container.style.top = '16px';
                    container.style.zIndex = 9999;
                    document.body.appendChild(container);
                }

                const toast = document.createElement('div');
                toast.textContent = message;
                toast.style.marginTop = '8px';
                toast.style.padding = '10px 14px';
                toast.style.borderRadius = '8px';
                toast.style.color = '#fff';
                toast.style.fontSize = '13px';
                toast.style.boxShadow = '0 4px 16px rgba(0,0,0,0.12)';
                toast.style.opacity = '0';
                toast.style.transition = 'opacity 200ms ease, transform 200ms ease';

                if (variant === 'success') {
                    toast.style.background = '#16a34a';
                } else {
                    toast.style.background = '#dc2626';
                }

                container.appendChild(toast);

                // force reflow then show
                // eslint-disable-next-line no-unused-expressions
                toast.offsetWidth;
                toast.style.opacity = '1';
                toast.style.transform = 'translateY(0)';

                setTimeout(() => {
                    toast.style.opacity = '0';
                    toast.style.transform = 'translateY(-8px)';
                    setTimeout(() => container.removeChild(toast), 300);
                }, 3000);
            };
        </script>

        @fluxScripts
    </body>
</html>
