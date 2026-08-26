<x-layouts.app>
    <x-slot:title>{{ __('ui.add_booking') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.booking_module') }}</x-slot:pretitle>

    <x-ui.card dusk="booking-create-card">
        @include('booking._form')
    </x-ui.card>
</x-layouts.app>
