@php
$isEdit = isset($unit) && $unit?->exists;
$action = $isEdit ? route('equipment.update', $unit) : route('equipment.store');
@endphp

<form method="POST" action="{{ $action }}" dusk="equipment-form">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <x-ui.input name="code" :label="__('ui.code')" required :value="$unit->code ?? null" placeholder="EQ-XX-00001" />
        </div>
        <div class="col-md-6">
            <x-ui.select name="product_id" :label="__('ui.product')" required
                         :options="$products->pluck('name','id')->all()"
                         :value="$unit->product_id ?? null" />
        </div>
        <div class="col-md-6">
            <x-ui.select name="branch_id" :label="__('ui.branch')" required
                         :options="$branches->pluck('name','id')->all()"
                         :value="$unit->branch_id ?? null" />
        </div>
        <div class="col-md-3">
            <x-ui.select name="condition" :label="__('ui.condition')" required
                         :options="['good'=>__('ui.condition_good'),'fair'=>__('ui.condition_fair'),'poor'=>__('ui.condition_poor'),'damaged'=>__('ui.condition_damaged')]"
                         :value="$unit->condition ?? 'good'" />
        </div>
        @if ($isEdit)
            <div class="col-md-3">
                <x-ui.select name="status" :label="__('ui.status')" required
                             :options="['available'=>__('ui.status_available'),'rented'=>__('ui.status_rented'),'maintenance'=>__('ui.maintenance')]"
                             :value="$unit->status ?? 'available'" />
            </div>
        @endif
        <div class="col-12">
            <label class="form-label fw-semibold">{{ __('ui.note') }}</label>
            <textarea name="notes" rows="2" class="form-control">{{ old('notes', $unit->notes ?? '') }}</textarea>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-equipment">
            {{ $isEdit ? __('ui.save') : __('ui.create') }}
        </x-ui.button>
        <a href="{{ route('equipment.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>

@if ($isEdit)
    <x-ui.card :title="__('ui.maintenance_log')" class="mt-4">
        @forelse ($maintenanceLogs as $log)
            <div class="border-bottom pb-2 mb-2">
                <small class="text-muted">{{ $log->date->format('d M Y') }} · {{ ucfirst($log->type) }}</small>
                <p class="mb-0">{{ $log->description ?: '-' }} — Rp {{ number_format($log->cost, 0, ',', '.') }}</p>
            </div>
        @empty
            <p class="text-muted mb-0">{{ __('ui.empty_maintenance_logs') }}</p>
        @endforelse

        <form method="POST" action="{{ route('equipment.maintenance', $unit) }}" class="mt-3" dusk="maintenance-form">
            @csrf
            <div class="row g-2 align-items-end">
                <div class="col-md-3">
                    <x-ui.input name="date" :label="__('ui.date')" type="date" required :value="now()->format('Y-m-d')" />
                </div>
                <div class="col-md-3">
                    <x-ui.select name="type" :label="__('ui.type')" required
                                 :options="['routine'=>__('ui.maintenance_routine'),'repair'=>__('ui.maintenance_repair'),'inspection'=>__('ui.maintenance_inspection'),'replacement'=>__('ui.maintenance_replacement')]" />
                </div>
                <div class="col-md-3">
                    <x-ui.money name="cost" :label="__('ui.cost')" required value="0" />
                </div>
                <div class="col-md-3">
                    <x-ui.input name="description" :label="__('ui.description')" />
                </div>
            </div>
            <x-ui.button type="submit" variant="outline-primary" size="sm" dusk="save-maintenance" class="mt-2">
                {{ __('ui.add_log') }}
            </x-ui.button>
        </form>
    </x-ui.card>
@endif
