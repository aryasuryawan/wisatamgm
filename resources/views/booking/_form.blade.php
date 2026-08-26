@php
$isEdit = isset($booking) && $booking?->exists;
$action = $isEdit ? route('bookings.update', $booking) : route('bookings.store');
@endphp

<form method="POST" action="{{ $action }}" dusk="booking-form">
    @csrf
    @if ($isEdit) @method('PUT') @endif

    <div class="row g-3">
        <div class="col-md-5">
            <x-ui.select name="bookable_unit_id" :label="__('ui.table_unit')" required
                         :options="$units->mapWithKeys(fn ($u) => [$u->id => $u->name.' ('.$u->type.') — Rp '.number_format($u->base_price, 0, ',', '.').'/'.$u->product?->unit])->all()"
                         :value="$booking->bookable_unit_id ?? null" />
        </div>
        <div class="col-md-4">
            <x-ui.input name="guest_name" :label="__('ui.guest')" required :value="$booking->guest_name ?? null" />
        </div>
        <div class="col-md-3">
            <x-ui.input name="guest_phone" :label="__('ui.phone')" :value="$booking->guest_phone ?? null" />
        </div>
        <div class="col-md-3">
            <x-ui.input name="date_start" :label="__('ui.check_in')" type="date" required
                        :value="old('date_start', ($booking ?? null)?->date_start?->format('Y-m-d') ?? now()->format('Y-m-d'))" />
        </div>
        <div class="col-md-3">
            <x-ui.input name="date_end" :label="__('ui.check_out')" type="date" required
                        :value="old('date_end', ($booking ?? null)?->date_end?->format('Y-m-d') ?? now()->addDay()->format('Y-m-d'))" />
        </div>
        <div class="col-md-2">
            <x-ui.input name="guests_count" :label="__('ui.pax')" type="number" min="1" required
                        :value="$booking->guests_count ?? 1" />
        </div>
        <div class="col-md-4">
            <x-ui.money name="amount_total" :label="__('ui.total_price')" required
                        :value="$booking->amount_total ?? null" />
        </div>
        <div class="col-md-6">
            <x-ui.select name="customer_id" :label="__('ui.customer')"
                         :options="$customers->pluck('name','id')->all()"
                         :value="$booking->customer_id ?? null"
                         :placeholder="__('ui.select_customer_optional')" />
        </div>
        <div class="col-12">
            <label class="form-label fw-semibold">{{ __('ui.note') }}</label>
            <textarea name="notes" rows="2" class="form-control">{{ old('notes', $booking->notes ?? '') }}</textarea>
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-booking">
            {{ $isEdit ? __('ui.save') : __('ui.create') }}
        </x-ui.button>
        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>
