<x-layouts.app>
    <x-slot:title>{{ __('ui.page_customer_edit') }} — {{ $customer->name }}</x-slot>
    <h1 class="h4 fw-semibold mb-3">{{ __('ui.page_customer_edit') }}: {{ $customer->name }}</h1>
    <x-ui.card>
        @include('customers._form')
    </x-ui.card>
</x-layouts.app>
