<x-layouts.app>
    <x-slot:title>{{ __('ui.page_equipment_edit') }} — {{ $unit->code }}</x-slot>
    <h1 class="h4 fw-semibold mb-3">{{ __('ui.page_equipment_edit') }}: {{ $unit->code }}</h1>
    <x-ui.card>@include('equipment._form')</x-ui.card>
</x-layouts.app>
