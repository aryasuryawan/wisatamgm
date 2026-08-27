<x-layouts.app>
    <x-slot:title>{{ __('ui.page_user_edit') }}</x-slot>

    <h1 class="h4 fw-semibold mb-3">{{ __('ui.page_user_edit') }}: {{ $user->name }}</h1>

    <x-ui.card>
        @include('users._form')
    </x-ui.card>
</x-layouts.app>