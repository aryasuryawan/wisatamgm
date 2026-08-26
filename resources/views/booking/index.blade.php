<x-layouts.app>
    <x-slot:title>{{ __('ui.bookings') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.booking_module') }}</x-slot:pretitle>

    <x-slot:page_actions>
        <a href="{{ route('bookings.calendar') }}" class="btn btn-outline-secondary" dusk="open-calendar">
            <i class="ti ti-calendar-month icon icon-1"></i> {{ __('ui.booking_calendar') }}
        </a>
        @can('bookings.create')
            <a href="{{ route('bookings.create') }}" class="btn btn-primary" dusk="create-booking">
                + {{ __('ui.add_booking') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="bookings-card" :padded="false">
        <div class="card-header"><form method="GET" class="row g-2 w-100">
            <div class="col-md-3">
                <select name="status" class="form-select">
                    <option value="">{{ __('ui.all_status') }}</option>
                    @foreach (['confirmed' => __('ui.status_confirmed'), 'checked_in' => __('ui.status_checked_in'), 'checked_out' => __('ui.status_checked_out'), 'cancelled' => __('ui.status_cancelled')] as $val => $label)
                        <option value="{{ $val }}" @selected(request('status')===$val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-4">
                <select name="bookable_unit_id" class="form-select">
                    <option value="">{{ __('ui.all_units') }}</option>
                    @foreach ($units as $unit)
                        <option value="{{ $unit->id }}" @selected(request('bookable_unit_id')==$unit->id)>{{ $unit->name }} — {{ $unit->branch->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-outline-primary">{{ __('ui.filter') }}</button>
            </div>
        </form></div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="bookings-table">
                <thead>
                <tr>
                    <th>{{ __('ui.guest') }}</th>
                    <th>{{ __('ui.table_unit') }}</th>
                    <th>{{ __('ui.table_period') }}</th>
                    <th class="text-center">{{ __('ui.nights') }}</th>
                    <th class="text-center">{{ __('ui.pax') }}</th>
                    <th>{{ __('ui.table_status') }}</th>
                    <th class="text-end">{{ __('ui.table_amount') }}</th>
                    <th class="text-end">{{ __('ui.table_action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($bookings as $booking)
                    @php
                        $statusColors = ['confirmed' => 'primary', 'checked_in' => 'warning', 'checked_out' => 'success', 'cancelled' => 'secondary'];
                        $statusLabels = [
                            'confirmed' => __('ui.status_confirmed'),
                            'checked_in' => __('ui.status_checked_in'),
                            'checked_out' => __('ui.status_checked_out'),
                            'cancelled' => __('ui.status_cancelled'),
                        ];
                        $paid = $booking->paidTotal();
                    @endphp
                    <tr dusk="booking-row-{{ $booking->id }}" @class(['text-secondary' => $booking->status === 'cancelled'])>
                        <td class="fw-semibold">{{ $booking->guest_name }}</td>
                        <td>{{ $booking->unit?->name }}</td>
                        <td>{{ $booking->date_start->format('d M') }} – {{ $booking->date_end->format('d M Y') }}</td>
                        <td class="text-center">{{ $booking->nights() }}</td>
                        <td class="text-center">{{ $booking->guests_count }}</td>
                        <td>
                            <x-ui.badge color="{{ $statusColors[$booking->status] ?? 'secondary' }}">{{ $statusLabels[$booking->status] ?? $booking->status }}</x-ui.badge>
                            @if ($booking->status !== 'cancelled' && $paid >= (float) $booking->amount_total && $booking->amount_total > 0)
                                <x-ui.badge color="success">{{ __('ui.lunas') }}</x-ui.badge>
                            @endif
                        </td>
                        <td class="text-end fw-semibold">Rp {{ number_format($booking->amount_total, 0, ',', '.') }}</td>
                        <td class="text-end">
                            <a href="{{ route('bookings.show', $booking) }}" class="btn btn-outline-primary btn-sm"
                               dusk="view-booking-{{ $booking->id }}">{{ __('ui.continue') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-4">{{ __('ui.empty_bookings') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $bookings->links() }}</div>
    </x-ui.card>
</x-layouts.app>
