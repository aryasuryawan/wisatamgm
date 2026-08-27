@props([
    'brand' => config('app.name', 'Tulamben Scuba'),
    'title' => __('ui.dashboard'),
    'pretitle' => null,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title }} | {{ $brand }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/app.js'])
    @endif
</head>
<body class="layout-fluid">
<div class="page">
    <!-- BEGIN TOP NAVBAR -->
    <header class="navbar navbar-expand-md d-print-none" data-bs-theme="light">
        <div class="container-xl">
            <!-- NAVBAR TOGGLER -->
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
                    aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- NAVBAR LOGO -->
            <a href="{{ route('dashboard') }}" class="navbar-brand navbar-brand-autodark me-3" dusk="brand">
                <span class="sg-logo-mark">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22c4-4 8-7.5 8-12a8 8 0 1 0-16 0c0 4.5 4 8 8 12z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </span>
                <span class="d-none d-xl-inline">{{ $brand }}</span>
            </a>

            <!-- NAVBAR RIGHT: language switcher & user menu -->
            <div class="navbar-nav flex-row order-md-last ms-auto">
                <!-- Language switcher -->
                <div class="nav-item d-flex align-items-center">
                    <x-ui.language-switcher class="d-inline-block align-middle"/>
                </div>

                <!-- User menu -->
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-reset p-0 px-2"
                       data-bs-toggle="dropdown" aria-label="Open user menu" dusk="user-menu">
                        <span class="avatar avatar-sm" style="background: var(--tblr-primary); color: #fff; border: 2px solid rgba(255,255,255,0.15);">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                        </span>
                        <div class="d-none d-xl-block ps-2">
                            <div>{{ auth()->user()?->name }}</div>
                            <div class="mt-1 small text-secondary">{{ auth()->user()?->getRoleNames()->implode(', ') }}</div>
                        </div>
                    </a>
                    <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow" dusk="user-dropdown">
                        <div class="dropdown-header text-secondary small">
                            {{ auth()->user()?->getRoleNames()->implode(', ') }}
                        </div>
                        <div class="dropdown-divider"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="dropdown-item" dusk="logout-button">
                                <i class="ti ti-logout icon icon-2 me-1"></i>{{ __('ui.logout') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <!-- END TOP NAVBAR -->

    <!-- BEGIN SIDEBAR -->
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <!-- NAVBAR TOGGLER -->
            <button class="navbar-toggler d-lg-none" type="button"
                    data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
                    aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- NAVBAR LOGO (sidebar) -->
            <div class="navbar-brand navbar-brand-autodark" dusk="brand">
                <span class="sg-logo-mark">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 22c4-4 8-7.5 8-12a8 8 0 1 0-16 0c0 4.5 4 8 8 12z"/>
                        <circle cx="12" cy="10" r="3"/>
                    </svg>
                </span>
                <span class="d-none d-xl-inline">{{ $brand }}</span>
            </div>

            <!-- COLLAPSE WRAPPER -->
            <div class="collapse navbar-collapse" id="sidebar-menu">
                <ul class="navbar-nav pt-lg-3">
                    @php
                        // Menu dikelompokkan per konteks (pola resmi Tabler:
                        // li.nav-item.dropdown, lihat layout-fluid-vertical demo).
                        $menuGroups = [
                            [
                                'label' => __('ui.nav_reservations'), 'icon' => 'ti-bed', 'match' => ['bookings.*', 'schedules.*'],
                                'items' => [
                                    ['can' => 'bookings.view', 'route' => 'bookings.index', 'match' => 'bookings.*', 'label' => __('ui.bookings')],
                                    ['can' => 'bookings.view', 'route' => 'bookings.calendar', 'match' => 'bookings.calendar', 'label' => __('ui.booking_calendar')],
                                    ['can' => 'schedules.view', 'route' => 'schedules.index', 'match' => 'schedules.*', 'label' => __('ui.schedules')],
                                ],
                            ],
                            [
                                'label' => __('ui.nav_sales'), 'icon' => 'ti-shopping-cart', 'match' => ['transactions.*', 'discounts.*', 'customers.*'],
                                'items' => [
                                    ['can' => 'transactions.create', 'route' => 'transactions.create', 'match' => 'transactions.create', 'label' => __('ui.pos')],
                                    ['can' => 'transactions.view', 'route' => 'transactions.invoices', 'match' => 'transactions.invoices', 'label' => __('ui.invoices')],
                                    ['can' => 'transactions.view', 'route' => 'transactions.index', 'match' => ['transactions.index', 'transactions.show'], 'label' => __('ui.transactions')],
                                    ['can' => 'customers.view', 'route' => 'customers.index', 'match' => 'customers.*', 'label' => __('ui.customers')],
                                    ['can' => 'discounts.view', 'route' => 'discounts.index', 'match' => 'discounts.*', 'label' => __('ui.discounts')],
                                ],
                            ],
                            [
                                'label' => __('ui.nav_catalog'), 'icon' => 'ti-box', 'match' => ['products.*', 'equipment.*', 'inventory.*'],
                                'items' => [
                                    ['can' => 'products.view', 'route' => 'products.index', 'match' => 'products.*', 'label' => __('ui.products')],
                                    ['can' => 'products.view', 'route' => 'product-categories.index', 'match' => 'product-categories.*', 'label' => __('ui.categories')],
                                    ['can' => 'equipment.view', 'route' => 'equipment.index', 'match' => 'equipment.*', 'label' => __('ui.equipment')],
                                    ['can' => 'inventory.view', 'route' => 'inventory.index', 'match' => 'inventory.*', 'label' => __('ui.inventory')],
                                ],
                            ],
                            [
                                'label' => __('ui.nav_finance'), 'icon' => 'ti-wallet', 'match' => ['expenses.*', 'marketing-campaigns.*', 'payroll.*', 'reports.*'],
                                'items' => [
                                    ['can' => 'expenses.view', 'route' => 'expenses.index', 'match' => ['expenses.*', 'marketing-campaigns.*'], 'label' => __('ui.expenses')],
                                    ['can' => 'payroll.view', 'route' => 'payroll.index', 'match' => 'payroll.*', 'label' => __('ui.payroll')],
                                    ['can' => 'reports.view', 'route' => 'reports.index', 'match' => 'reports.*', 'label' => __('ui.reports')],
                                ],
                            ],
                            [
                                'label' => __('ui.nav_administration'), 'icon' => 'ti-settings', 'match' => ['branches.*', 'notifications.*', 'settings.*', 'users.*'],
                                'items' => [
                                    ['can' => 'branches.view', 'route' => 'branches.index', 'match' => 'branches.*', 'label' => __('ui.branches')],
                                    ['can' => 'notifications.view', 'route' => 'notifications.index', 'match' => 'notifications.*', 'label' => __('ui.notifications')],
                                    ['can' => 'settings.view', 'route' => 'settings.index', 'match' => 'settings.*', 'label' => __('ui.settings')],
                                    ['can' => 'users.view', 'route' => 'users.index', 'match' => 'users.*', 'label' => __('ui.users')],
                                ],
                            ],
                        ];

                        $visible = fn (array $item) => $item['can'] === null || auth()->user()?->can($item['can']);
                    @endphp

                    {{-- Dashboard standalone --}}
                    <li class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <a class="nav-link" href="{{ route('dashboard') }}" dusk="nav-dashboard">
                            <span class="nav-link-icon d-md-none d-lg-inline-block">
                                <i class="ti ti-home icon icon-1"></i>
                            </span>
                            <span class="nav-link-title">{{ __('ui.dashboard') }}</span>
                        </a>
                    </li>

                    {{-- Dashboard per cabang --}}
                    @if (auth()->user()?->can('dashboard.view'))
                        <li class="nav-item {{ request()->routeIs('dashboard.branches') || request()->routeIs('dashboard.branch') ? 'active' : '' }}">
                            <a class="nav-link" href="{{ route('dashboard.branches') }}" dusk="nav-dashboard-cabang">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti ti-layout-dashboard icon icon-1"></i>
                                </span>
                                <span class="nav-link-title">{{ __('ui.branch_dashboard') }}</span>
                            </a>
                        </li>
                    @endif

                    @foreach ($menuGroups as $group)
                        @php
                            $groupItems = array_values(array_filter($group['items'], fn ($item) => $visible($item)));
                        @endphp

                        @continue(count($groupItems) === 0)

                        <li class="nav-item dropdown {{ request()->routeIs(...$group['match']) ? 'active' : '' }}"
                            dusk="nav-group-{{ \Illuminate\Support\Str::slug($group['label']) }}">
                            <a class="nav-link dropdown-toggle" href="#sidebar-{{ \Illuminate\Support\Str::slug($group['label']) }}"
                               data-bs-toggle="dropdown" data-bs-auto-close="false" role="button" aria-expanded="false">
                                <span class="nav-link-icon d-md-none d-lg-inline-block">
                                    <i class="ti {{ $group['icon'] }} icon icon-1"></i>
                                </span>
                                <span class="nav-link-title">{{ $group['label'] }}</span>
                            </a>
                            <div class="dropdown-menu">
                                @foreach ($groupItems as $item)
                                    <a class="dropdown-item {{ request()->routeIs(...(array) $item['match']) ? 'active' : '' }}"
                                       href="{{ route($item['route']) }}" dusk="nav-{{ Str::before($item['route'], '.') }}">
                                        {{ $item['label'] }}
                                    </a>
                                @endforeach
                            </div>
                        </li>
                    @endforeach
                    </ul>
                </div>
        </div>
    </aside>
    <!-- END SIDEBAR -->

    <div class="page-wrapper">
        <!-- BEGIN PAGE HEADER -->
        <div class="page-header d-print-none" aria-label="Page header">
            <div class="container-xl">
                @php
                    // View memakai <x-slot:page_actions> (snake_case) — normalisasi
                    // ke satu variabel supaya tombol aksi halaman selalu tampil.
                    $pageActions = $pageActions ?? $page_actions ?? null;
                @endphp
                <div class="row g-2 align-items-center">
                    <div class="col">
                        @if ($pretitle)
                            <div class="page-pretitle">{{ $pretitle }}</div>
                        @endif
                        <h2 class="page-title" dusk="page-title">
                            {{ $title }}
                        </h2>
                    </div>
                    @if (! empty($pageActions))
                        <div class="col-auto ms-auto d-print-none">
                            <div class="btn-list">
                                {{ $pageActions }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
        <!-- END PAGE HEADER -->

        <!-- BEGIN PAGE BODY -->
        <div class="page-body">
            <div class="container-xl">
                @if (session('success'))
                    <x-ui.alert type="success" :message="session('success')" />
                @endif
                @if (session('error'))
                    <x-ui.alert type="danger" :message="session('error')" />
                @endif

                {{ $slot }}
            </div>
        </div>
        <!-- END PAGE BODY -->

        <footer class="footer footer-transparent d-print-none">
            <div class="container-xl text-center text-secondary small">
                &copy; {{ date('Y') }} {{ $brand }}
            </div>
        </footer>
    </div>
</div>

@stack('scripts')
</body>
</html>
