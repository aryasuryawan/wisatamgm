<x-layouts.app>
    <x-slot:title>{{ __('ui.add_campaign') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.finance_module') }}</x-slot:pretitle>

    <x-ui.card dusk="campaign-create-card">
        @include('finance.campaigns._form')
    </x-ui.card>
</x-layouts.app>
