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
    <!-- BEGIN SIDEBAR -->
    <aside class="navbar navbar-vertical navbar-expand-lg" data-bs-theme="dark">
        <div class="container-fluid">
            <!-- NAVBAR TOGGLER -->
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse" data-bs-target="#sidebar-menu"
                    aria-controls="sidebar-menu" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- NAVBAR LOGO -->
            <div class="navbar-brand navbar-brand-autodark" dusk="brand">
                {{ $brand }}
            </div>

            <!-- USER AREA: language switcher selalu terlihat + menu pengguna -->
            <div class="d-none d-lg-block w-100 mt-2">
                <x-ui.language-switcher />
            </div>

            <div class="d-none d-lg-flex align-items-center gap-2 mt-2">
                <div class="nav-item dropdown">
                    <a href="#" class="nav-link d-flex lh-1 text-secondary p-0 px-2"
                       data-bs-toggle="dropdown" aria-label="Open user menu" dusk="user-menu">
                        <span class="avatar avatar-sm bg-primary text-white">
                            {{ strtoupper(substr(auth()->user()?->name ?? 'U', 0, 1)) }}
                        </span>
                        <div class="d-none d-xl-block ps-2">
                            <div class="text-white">{{ auth()->user()?->name }}</div>
                            <div class="mt-1 small text-secondary">
                                {{ auth()->user()?->getRoleNames()->implode(', ') }}
                            </div>
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
                                'label' => __('ui.nav_administration'), 'icon' => 'ti-settings', 'match' => ['branches.*', 'notifications.*'],
                                'items' => [
                                    ['can' => 'branches.view', 'route' => 'branches.index', 'match' => 'branches.*', 'label' => __('ui.branches')],
                                    ['can' => 'notifications.view', 'route' => 'notifications.index', 'match' => 'notifications.*', 'label' => __('ui.notifications')],
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

                    <div class="d-lg-none mt-3 pt-3 border-top" dusk="language-switcher-mobile">
                        <span class="text-secondary small d-block mb-2">{{ __('ui.language') }}</span>
                        <x-ui.language-switcher />
                    </div>
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
