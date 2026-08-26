<x-layouts.app>
    <x-slot:title>{{ __('ui.page_schedule_edit') }} — {{ $schedule->product->name }}</x-slot>
    <h1 class="h4 fw-semibold mb-3">{{ __('ui.page_schedule_edit') }}</h1>
    <x-ui.card>@include('schedules._form')</x-ui.card>
</x-layouts.app>
