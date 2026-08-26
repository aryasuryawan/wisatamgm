<x-layouts.app>
    <x-slot:title>{{ __('ui.page_product_create') }}</x-slot>
    <h1 class="h4 fw-semibold mb-3">{{ __('ui.page_product_create') }}</h1>
    <x-ui.card>@include('products._form')</x-ui.card>
</x-layouts.app>
