<x-layouts.app>
    <x-slot:title>{{ __('ui.page_discount_edit') }} — {{ $discount->code }}</x-slot>
    <h1 class="h4 fw-semibold mb-3">{{ __('ui.page_discount_edit') }}: {{ $discount->code }}</h1>
    <x-ui.card>@include('discounts._form')</x-ui.card>
</x-layouts.app>
