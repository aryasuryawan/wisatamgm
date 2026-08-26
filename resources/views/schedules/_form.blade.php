@php
$isEdit = isset($schedule) && $schedule?->exists;
$action = $isEdit ? route('schedules.update', $schedule) : route('schedules.store');
$dateStart = $isEdit ? $schedule->date_start->format('Y-m-d\TH:i') : null;
$dateEnd = $isEdit && $schedule->date_end ? $schedule->date_end->format('Y-m-d\TH:i') : null;
@endphp

<form method="POST" action="{{ $action }}" dusk="schedule-form">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-6">
            <x-ui.select name="product_id" :label="__('ui.product')" required
                         :options="$products->pluck('name', 'id')->all()"
                         :value="$schedule->product_id ?? null" placeholder="-- {{ __('ui.select_product') }} --" />
        </div>
        <div class="col-md-6">
            <x-ui.select name="branch_id" :label="__('ui.branch')" required
                         :options="$branches->pluck('name', 'id')->all()"
                         :value="$schedule->branch_id ?? null" placeholder="-- {{ __('ui.select_branch') }} --" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="date_start" :label="__('ui.date_start')" type="datetime-local" required :value="$dateStart" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="date_end" :label="__('ui.date_end')" type="datetime-local" :value="$dateEnd" />
        </div>
        <div class="col-md-6">
            <x-ui.input name="capacity" :label="__('ui.capacity')" type="number" min="1" required
                        :value="$schedule->capacity ?? 8" />
        </div>
        <div class="col-md-6">
            <label for="notes" class="form-label fw-semibold">{{ __('ui.note') }}</label>
            <textarea id="notes" name="notes" rows="1" maxlength="1000"
                      class="form-control @if(($errors ?? null)?->has('notes')) is-invalid @endif"
                      dusk="input-notes">{{ old('notes', $schedule->notes ?? '') }}</textarea>
            @if (($errors ?? null)?->has('notes'))
                <div class="invalid-feedback">{{ $errors->first('notes') }}</div>
            @endif
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-schedule">
            {{ $isEdit ? __('ui.save_changes') : __('ui.create') }}
        </x-ui.button>
        <a href="{{ route('schedules.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>
