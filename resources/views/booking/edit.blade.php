<x-layouts.app>
    <x-slot:title>{{ __('ui.edit_booking') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.booking_module') }}</x-slot:pretitle>

    <x-ui.card dusk="booking-edit-card">
        @include('booking._form')
    </x-ui.card>
</x-layouts.app>
