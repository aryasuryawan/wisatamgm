<x-layouts.app>
    <x-slot:title>{{ __('ui.add_expense') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.finance_module') }}</x-slot:pretitle>

    <x-ui.card dusk="expense-create-card">
        @include('finance.expenses._form')
    </x-ui.card>
</x-layouts.app>
