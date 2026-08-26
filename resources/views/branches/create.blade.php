<x-layouts.app>
    <x-slot:title>{{ __('ui.page_branch_create') }}</x-slot>

    <h1 class="h4 fw-semibold mb-3">{{ __('ui.page_branch_create') }}</h1>

    <x-ui.card>
        @include('branches._form')
    </x-ui.card>
</x-layouts.app>
