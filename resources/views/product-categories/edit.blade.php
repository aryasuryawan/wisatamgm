<x-layouts.app>
    <x-slot:title>{{ __('ui.page_category_edit') }} — {{ $category->name }}</x-slot>
    <h1 class="h4 fw-semibold mb-3">{{ __('ui.page_category_edit') }}: {{ $category->name }}</h1>
    <x-ui.card>@include('product-categories._form')</x-ui.card>
</x-layouts.app>
