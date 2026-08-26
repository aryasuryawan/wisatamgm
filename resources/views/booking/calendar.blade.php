<x-layouts.app>
    <x-slot:title>{{ __('ui.booking_calendar') }}</x-slot>
    <x-slot:pretitle>{{ __('ui.booking_module') }}</x-slot:pretitle>

    <x-slot:page_actions>
        <a href="{{ route('bookings.index') }}" class="btn btn-outline-secondary">{{ __('ui.bookings') }}</a>
        @can('bookings.create')
            <a href="{{ route('bookings.create') }}" class="btn btn-primary" dusk="create-booking">
                + {{ __('ui.add_booking') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="calendar-card" :padded="false">
        <div class="card-header d-flex align-items-center">
            <div class="btn-list">
                <a href="{{ route('bookings.calendar', ['month' => $prevMonth]) }}" class="btn btn-outline-secondary btn-sm" dusk="prev-month">
                    ← {{ Carbon\Carbon::parse($prevMonth.'-01')->translatedFormat('M Y') }}
                </a>
                <span class="fw-semibold px-2 align-self-center" dusk="current-month">{{ $month->translatedFormat('F Y') }}</span>
                <a href="{{ route('bookings.calendar', ['month' => $nextMonth]) }}" class="btn btn-outline-secondary btn-sm" dusk="next-month">
                    {{ Carbon\Carbon::parse($nextMonth.'-01')->translatedFormat('M Y') }} →
                </a>
            </div>
            <div class="ms-auto d-none d-md-block small text-secondary">
                <span class="badge bg-success-lt">█</span> {{ __('ui.occupied_night') }}
                <span class="badge bg-secondary-lt ms-2">█</span> {{ __('ui.free_night') }}
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-vcenter card-table booking-calendar" dusk="booking-calendar-table">
                <thead>
                <tr>
                    <th class="text-nowrap">{{ __('ui.table_unit') }}</th>
                    @for ($d = 1; $d <= $daysInMonth; $d++)
                        <th class="text-center {{ \Carbon\Carbon::create($month->year, $month->month, $d)->isWeekend() ? 'bg-light' : '' }}"
                            style="min-width:26px; {{ $d == now()->day && $month->isCurrentMonth() ? 'color:#d63939' : '' }}">
                            {{ $d }}
                        </th>
                    @endfor
                </tr>
                </thead>
                <tbody>
                @forelse ($units as $unit)
                    @php
                        // Peta tanggal terisi bulan ini: date >= start && date < end
                        $unitBookings = $bookingsByUnit->get($unit->id, collect());
                        $occupied = [];
                        foreach ($unitBookings as $b) {
                            $from = max($b->date_start->copy(), $month->copy()->startOfMonth());
                            $to = min($b->date_end->copy()->subDay(), $month->copy()->endOfMonth());
                            for ($d = $from; $d->lte($to); $d->addDay()) {
                                $occupied[$d->day] = $b;
                            }
                        }
                    @endphp
                    <tr dusk="calendar-row-unit-{{ $unit->id }}">
                        <td class="text-nowrap fw-semibold" title="{{ $unit->branch->name }}">
                            {{ $unit->name }}
                            <span class="text-secondary d-block small">{{ __('ui.unit_type_'.$unit->type) }}</span>
                        </td>
                        @for ($d = 1; $d <= $daysInMonth; $d++)
                            @php
                                /** @var Booking|null $b */
                                $b = $occupied[$d] ?? null;
                                $cellClass = $b ? ($b->status === 'checked_in' ? 'bg-warning-lt' : 'bg-success-lt') : '';
                            @endphp
                            <td class="text-center p-0 {{ $cellClass }}" dusk="cell-{{ $unit->id }}-{{ $d }}"
                                @if($b) title="{{ $b->guest_name }} ({{ $b->date_start->format('d/m') }}–{{ $b->date_end->format('d/m') }})" @endif>
                                @if ($b)
                                    <a href="{{ route('bookings.show', $b) }}"
                                       class="d-block text-decoration-none py-1 px-0 small"
                                       dusk="cal-booked-{{ $b->id }}">{{ Str::limit($b->guest_name, 8, '…') }}</a>
                                @else
                                    <span class="d-block py-1 text-transparent">·</span>
                                @endif
                            </td>
                        @endfor
                    </tr>
                @empty
                    <tr><td colspan="{{ $daysInMonth + 1 }}" class="text-center text-muted py-4">{{ __('ui.empty_bookings') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-layouts.app>
