<x-layouts.app>
    <x-slot:title>{{ __('ui.settings') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.nav_administration') }}</x-slot:pretitle>

    @php
        $tabsConfig = [
            'business' => ['label' => 'settings_business', 'icon' => 'ti-building-store'],
            'appearance' => ['label' => 'settings_appearance', 'icon' => 'ti-photo'],
            'notifications' => ['label' => 'settings_notifications', 'icon' => 'ti-mail'],
            'templates' => ['label' => 'settings_templates', 'icon' => 'ti-file-type-pdf'],
            'integrations' => ['label' => 'settings_integrations', 'icon' => 'ti-plug'],
        ];
    @endphp

    <x-ui.card dusk="settings-card">
        <ul class="nav nav-tabs" role="tablist" dusk="settings-tabs">
            @foreach($tabsConfig as $tabKey => $tabInfo)
                <li class="nav-item" role="presentation">
                    <a href="{{ route('settings.index', ['tab' => $tabKey]) }}"
                       class="nav-link {{ $tab === $tabKey ? 'active' : '' }}"
                       dusk="tab-{{ $tabKey }}">
                        <i class="ti {{ $tabInfo['icon'] }} icon me-1"></i>
                        {{ __('ui.'.$tabInfo['label']) }}
                    </a>
                </li>
            @endforeach
        </ul>

        <div class="card-body">
            @include("settings._{$tab}")
        </div>
    </x-ui.card>
</x-layouts.app>
