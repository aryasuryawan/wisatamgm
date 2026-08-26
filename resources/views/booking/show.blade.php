<x-layouts.app>
    <x-slot:title>{{ __('ui.booking') }} — {{ $booking->guest_name }}</x-slot>
    <x-slot:pretitle>{{ __('ui.booking_module') }} · {{ $booking->unit?->name }}</x-slot:pretitle>

    @php
        $statusColors = ['confirmed' => 'primary', 'checked_in' => 'warning', 'checked_out' => 'success', 'cancelled' => 'secondary'];
        $statusLabels = [
            'confirmed' => __('ui.status_confirmed'),
            'checked_in' => __('ui.status_checked_in'),
            'checked_out' => __('ui.status_checked_out'),
            'cancelled' => __('ui.status_cancelled'),
        ];
        $paid = $booking->paidTotal();
        $remaining = max(0, (float) $booking->amount_total - $paid);
        $isCancelled = $booking->status === 'cancelled';
    @endphp

    <x-slot:page_actions>
        @if ($booking->status === 'confirmed')
            @can('bookings.edit')
                <a href="{{ route('bookings.edit', $booking) }}" class="btn btn-outline-primary">{{ __('ui.edit') }}</a>
                @if (! $booking->transaction_id)
                    <form method="POST" action="{{ route('bookings.invoice', $booking) }}" class="d-inline">
                        @csrf
                        <button type="submit" class="btn btn-info" dusk="issue-invoice">{{ __('ui.issue_invoice') }}</button>
                    </form>
                @endif
                <form method="POST" action="{{ route('bookings.check-in', $booking) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-warning" dusk="check-in-button">Check-in</button>
                </form>
                <form method="POST" action="{{ route('bookings.cancel', $booking) }}" class="d-inline"
                      onsubmit="return confirm('{{ __('ui.confirm_cancel_booking') }}')">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger" dusk="cancel-booking">{{ __('ui.cancel_booking') }}</button>
                </form>
            @endcan
        @elseif ($booking->status === 'checked_in')
            @can('bookings.edit')
                <form method="POST" action="{{ route('bookings.check-out', $booking) }}" class="d-inline">
                    @csrf
                    <button type="submit" class="btn btn-success" dusk="check-out-button">Check-out</button>
                </form>
            @endcan
        @endif
        @if ($isCancelled && can('bookings.delete'))
            <form method="POST" action="{{ route('bookings.destroy', $booking) }}" class="d-inline"
                  onsubmit="return confirm('{{ __('ui.confirm_delete_booking') }}')">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-danger" dusk="delete-booking">{{ __('ui.delete') }}</button>
            </form>
        @endif
    </x-slot:page_actions>

    <div class="row row-deck row-cards mb-3">
        <div class="col-sm-6 col-lg-3">
            <div class="card"><div class="card-body py-3">
                <div class="subheader">{{ __('ui.table_status') }}</div>
                <x-ui.badge color="{{ $statusColors[$booking->status] ?? 'secondary' }}" dusk="booking-status">
                    {{ $statusLabels[$booking->status] ?? $booking->status }}
                </x-ui.badge>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card"><div class="card-body py-3">
                <div class="subheader">{{ __('ui.table_period') }}</div>
                <div class="fw-semibold" dusk="booking-dates">
                    {{ $booking->date_start->translatedFormat('d M Y') }} → {{ $booking->date_end->translatedFormat('d M Y') }}
                    <span class="text-secondary small">({{ $booking->nights() }} {{ __('ui.nights') }})</span>
                </div>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-2">
            <div class="card"><div class="card-body py-3">
                <div class="subheader">{{ __('ui.pax') }}</div>
                <div class="fw-semibold">{{ $booking->guests_count }} / {{ $booking->unit?->capacity }}</div>
            </div></div>
        </div>
        <div class="col-sm-6 col-lg-3">
            <div class="card"><div class="card-body py-3">
                <div class="subheader">{{ __('ui.payment_status') }}</div>
                <div class="h3 mb-0 fw-bold {{ $remaining <= 0 ? 'text-success' : 'text-warning' }}" dusk="booking-paid">
                    Rp {{ number_format($paid, 0, ',', '.') }}
                </div>
                <div class="text-secondary small">{{ __('ui.remaining') }}: Rp {{ number_format($remaining, 0, ',', '.') }}</div>
            </div></div>
        </div>
    </div>

    <div class="row row-deck row-cards">
        {{-- Detail tamu --}}
        <div class="col-lg-5">
            <x-ui.card :title="__('ui.guest_info')">
                <table class="table table-vcenter mb-0">
                    <tbody>
                    <tr><td class="text-secondary w-50">{{ __('ui.guest') }}</td><td class="fw-semibold">{{ $booking->guest_name }}</td></tr>
                    <tr><td class="text-secondary">{{ __('ui.phone') }}</td><td>{{ $booking->guest_phone ?? '-' }}</td></tr>
                    <tr><td class="text-secondary">{{ __('ui.customer') }}</td><td>{{ $booking->customer?->name ?? '-' }}</td></tr>
                    <tr><td class="text-secondary">{{ __('ui.table_unit') }}</td><td>{{ $booking->unit?->name }} ({{ $booking->unit?->type }})</td></tr>
                    <tr><td class="text-secondary">{{ __('ui.transaction_no') }}</td><td>
                        @if ($booking->transaction)
                            <a href="{{ route('transactions.show', $booking->transaction) }}">#{{ $booking->transaction->id }}</a>
                        @else
                            -
                        @endif
                    </td></tr>
                    <tr><td class="text-secondary">{{ __('ui.note') }}</td><td>{{ $booking->notes ?: '-' }}</td></tr>
                    </tbody>
                </table>

                @if (! $isCancelled && ($remaining > 0 || ! $booking->transaction))
                    <div class="hr-text my-3">{{ __('ui.record_payment') }}</div>
                    <form method="POST" action="{{ route('bookings.payments.store', $booking) }}" enctype="multipart/form-data" dusk="booking-payment-form">
                        @csrf
                        <div class="row g-2 align-items-end">
                            <div class="col-4">
                                <x-ui.select name="method" :label="__('ui.payment_method')"
                                             :options="collect($methods)->mapWithKeys(fn ($m) => [$m => ucfirst($m)])->all()" />
                            </div>
                            <div class="col-4">
                                <x-ui.money name="amount" :label="__('ui.payment_amount')" required
                                            :value="$remaining > 0 ? $remaining : null" />
                            </div>
                            <div class="col-4">
                                <label for="proof" class="form-label fw-semibold">{{ __('ui.proof_attachment') }}</label>
                                <input type="file" id="proof" name="proof" accept=".jpg,.jpeg,.png,.webp,.pdf"
                                       class="form-control form-control-sm" dusk="input-proof">
                            </div>
                        </div>
                        <x-ui.button type="submit" variant="primary" size="sm" dusk="save-payment" class="mt-2">
                            {{ __('ui.record_payment') }}
                        </x-ui.button>
                    </form>
                @endif

                @if ($booking->transaction && $booking->transaction->payments->isNotEmpty())
                    <div class="hr-text my-3">{{ __('ui.payments_history') }}</div>
                    <ul class="list-unstyled mb-0 small">
                        @foreach ($booking->transaction->payments as $payment)
                            <li class="d-flex justify-content-between align-items-center border-bottom pb-1 mb-1">
                                <span>
                                    {{ ucfirst($payment->method) }}
                                    @if($payment->reference_no)<span class="text-secondary">· {{ $payment->reference_no }}</span>@endif
                                    @if ($payment->proof_path)
                                        <span class="ms-1"><x-ui.proof-link :path="$payment->proof_path" size="sm" /></span>
                                    @endif
                                </span>
                                <span class="fw-semibold">Rp {{ number_format($payment->amount, 0, ',', '.') }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </x-ui.card>
        </div>

        {{-- Cek ketersediaan unit ini --}}
        <div class="col-lg-7">
            <x-ui.card :title="__('ui.availability_check')" :title-actions="null">
                <p class="text-secondary small mb-3">
                    {{ __('ui.availability_hint') }}
                </p>
                <form method="GET" dusk="availability-form">
                    <div class="row g-2 align-items-end">
                        <div class="col-4">
                            <x-ui.input name="check_start" :label="__('ui.check_in')" type="date"
                                        :value="$availability['start'] ?? now()->addDay()->format('Y-m-d')" />
                        </div>
                        <div class="col-4">
                            <x-ui.input name="check_end" :label="__('ui.check_out')" type="date"
                                        :value="$availability['end'] ?? now()->addDays(2)->format('Y-m-d')" />
                        </div>
                        <div class="col-auto">
                            <button type="submit" class="btn btn-outline-primary" dusk="check-availability">
                                {{ __('ui.check_availability') }}
                            </button>
                        </div>
                    </div>
                </form>

                @if ($availability)
                    <div class="alert mt-3 {{ $availability['free'] ? 'alert-success' : 'alert-danger' }} mb-0" dusk="availability-result">
                        @if ($availability['free'])
                            <strong>{{ __('ui.available') }}!</strong>
                            {{ __('ui.available_range', ['start' => \Carbon\Carbon::parse($availability['start'])->translatedFormat('d M Y'), 'end' => \Carbon\Carbon::parse($availability['end'])->translatedFormat('d M Y')]) }}
                        @else
                            <strong>{{ __('ui.not_available') }}.</strong>
                            {{ __('ui.booked_range', ['start' => \Carbon\Carbon::parse($availability['start'])->translatedFormat('d M Y'), 'end' => \Carbon\Carbon::parse($availability['end'])->translatedFormat('d M Y')]) }}
                        @endif
                    </div>
                @endif
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
