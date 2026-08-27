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

    @php
        $viewLabels = ['month' => 'cal_view_month', 'week' => 'cal_view_week', 'day' => 'cal_view_day'];

        $navPrev = match($viewType) {
            'month' => $date->copy()->subMonth()->format('Y-m-d'),
            'week' => $date->copy()->subWeek()->format('Y-m-d'),
            'day' => $date->copy()->subDay()->format('Y-m-d'),
        };
        $navNext = match($viewType) {
            'month' => $date->copy()->addMonth()->format('Y-m-d'),
            'week' => $date->copy()->addWeek()->format('Y-m-d'),
            'day' => $date->copy()->addDay()->format('Y-m-d'),
        };

        $headerLabel = match($viewType) {
            'month' => $date->translatedFormat('F Y'),
            'week' => $rangeStart->translatedFormat('d M') . ' – ' . $rangeEnd->translatedFormat('d M Y'),
            'day' => $date->translatedFormat('l, d F Y'),
        };

        $prevLabel = match($viewType) {
            'month' => $date->copy()->subMonth()->translatedFormat('M Y'),
            'week' => $date->copy()->subWeek()->translatedFormat('d M'),
            'day' => $date->copy()->subDay()->translatedFormat('d M'),
        };
        $nextLabel = match($viewType) {
            'month' => $date->copy()->addMonth()->translatedFormat('M Y'),
            'week' => $date->copy()->addWeek()->translatedFormat('d M'),
            'day' => $date->copy()->addDay()->translatedFormat('d M'),
        };

        $isToday = $date->isToday();
    @endphp

    <x-ui.card dusk="calendar-card" :padded="false">
        <div class="card-header d-flex align-items-center flex-wrap gap-2">
            <div class="btn-list">
                <a href="{{ route('bookings.calendar', ['view' => $viewType, 'date' => $navPrev]) }}"
                   class="btn btn-outline-secondary btn-sm" dusk="prev-period">
                    ← {{ $prevLabel }}
                </a>
                @if(!$isToday)
                    <a href="{{ route('bookings.calendar', ['view' => $viewType, 'date' => now()->format('Y-m-d')]) }}"
                       class="btn btn-outline-info btn-sm" dusk="today-btn">
                        {{ __('ui.cal_today') }}
                    </a>
                @endif
                <span class="fw-semibold px-2 align-self-center" dusk="current-period">{{ $headerLabel }}</span>
                <a href="{{ route('bookings.calendar', ['view' => $viewType, 'date' => $navNext]) }}"
                   class="btn btn-outline-secondary btn-sm" dusk="next-period">
                    {{ $nextLabel }} →
                </a>
            </div>

            <div class="ms-auto d-flex align-items-center gap-2">
                <div class="btn-group btn-group-sm" role="group" dusk="view-switcher">
                    @foreach(['month', 'week', 'day'] as $v)
                        <a href="{{ route('bookings.calendar', ['view' => $v, 'date' => $date->format('Y-m-d')]) }}"
                           class="btn btn-{{ $viewType === $v ? 'primary' : 'outline-secondary' }}"
                           dusk="view-{{ $v }}">
                            {{ __('ui.'.$viewLabels[$v]) }}
                        </a>
                    @endforeach
                </div>
                <div class="d-none d-md-block small text-secondary">
                    <span class="badge bg-success-lt">█</span> {{ __('ui.occupied_night') }}
                    <span class="badge bg-secondary-lt ms-2">█</span> {{ __('ui.free_night') }}
                </div>
            </div>
        </div>

        {{-- MONTH / WEEK VIEW --}}
        @if(in_array($viewType, ['month', 'week']))
            <div class="table-responsive">
                <table class="table table-vcenter card-table booking-calendar" dusk="booking-calendar-table">
                    <thead>
                    <tr>
                        <th class="text-nowrap">{{ __('ui.table_unit') }}</th>
                        @foreach($dates as $d)
                            @php
                                $isWeekend = $d->isWeekend();
                                $isCurrentDay = $d->isToday();
                            @endphp
                            <th class="text-center {{ $isWeekend ? 'bg-light' : '' }}"
                                style="min-width:{{ $viewType === 'week' ? '80px' : '26px' }}; {{ $isCurrentDay ? 'color:#d63939' : '' }}">
                                @if($viewType === 'week')
                                    <div class="small">{{ $d->translatedFormat('D') }}</div>
                                    <div class="fw-semibold">{{ $d->day }}</div>
                                @else
                                    {{ $d->day }}
                                @endif
                            </th>
                        @endforeach
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($units as $unit)
                        @php
                            $unitBookings = $bookingsByUnit->get($unit->id, collect());
                            $occupied = [];
                            foreach ($unitBookings as $b) {
                                $from = max($b->date_start->copy(), $rangeStart->copy());
                                $to = min($b->date_end->copy()->subDay(), $rangeEnd->copy());
                                if ($from->lte($to)) {
                                    for ($d = $from->copy(); $d->lte($to); $d->addDay()) {
                                        $key = $d->format('Y-m-d');
                                        $occupied[$key] = $b;
                                    }
                                }
                            }
                        @endphp
                        <tr dusk="calendar-row-unit-{{ $unit->id }}">
                            <td class="text-nowrap fw-semibold" title="{{ $unit->branch->name }}">
                                {{ $unit->name }}
                                <span class="text-secondary d-block small">{{ __('ui.unit_type_'.$unit->type) }}</span>
                            </td>
                            @foreach($dates as $d)
                                @php
                                    $key = $d->format('Y-m-d');
                                    $b = $occupied[$key] ?? null;
                                    $cellClass = $b ? ($b->status === 'checked_in' ? 'bg-warning-lt' : 'bg-success-lt') : '';
                                @endphp
                                <td class="text-center p-0 {{ $cellClass }}"
                                    dusk="cell-{{ $unit->id }}-{{ $d->format('Y-m-d') }}"
                                    @if($b) title="{{ $b->guest_name }} ({{ $b->date_start->format('d/m') }}–{{ $b->date_end->format('d/m') }})" @endif>
                                    @if ($b)
                                        <a href="{{ route('bookings.show', $b) }}"
                                           class="d-block text-decoration-none py-1 px-0 small"
                                           dusk="cal-booked-{{ $b->id }}">
                                            @if($viewType === 'week')
                                                {{ Str::limit($b->guest_name, 12, '…') }}
                                            @else
                                                {{ Str::limit($b->guest_name, 8, '…') }}
                                            @endif
                                        </a>
                                    @else
                                        <span class="d-block py-1 text-transparent">·</span>
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($dates) + 1 }}" class="text-center text-muted py-4">{{ __('ui.empty_bookings') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

        {{-- DAY VIEW --}}
        @else
            @php
                $hours = range(6, 22);
            @endphp
            <div class="table-responsive">
                <table class="table table-vcenter card-table booking-calendar" dusk="booking-calendar-table">
                    <thead>
                    <tr>
                        <th class="text-nowrap" style="min-width:160px">{{ __('ui.table_unit') }}</th>
                        @foreach($hours as $h)
                            <th class="text-center {{ $h >= 18 || $h < 6 ? 'bg-light' : '' }}"
                                style="min-width:50px; font-size:0.75rem;">
                                {{ sprintf('%02d', $h) }}:00
                            </th>
                        @endforeach
                        <th class="text-center" style="min-width:120px; font-size:0.75rem;">{{ __('ui.total_price') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($units as $unit)
                        @php
                            $unitBookings = $bookingsByUnit->get($unit->id, collect())->filter(function ($b) use ($date) {
                                return $b->date_start->lte($date) && $b->date_end->gt($date);
                            });
                        @endphp
                        <tr dusk="calendar-row-unit-{{ $unit->id }}">
                            <td class="text-nowrap fw-semibold" title="{{ $unit->branch->name }}">
                                {{ $unit->name }}
                                <span class="text-secondary d-block small">{{ __('ui.unit_type_'.$unit->type) }}</span>
                            </td>
                            @if($unitBookings->isEmpty())
                                @foreach($hours as $h)
                                    <td class="text-center p-0">
                                        <span class="d-block py-2 text-transparent">·</span>
                                    </td>
                                @endforeach
                                <td></td>
                            @else
                                @php
                                    $booking = $unitBookings->first();
                                    $checkInHour = $booking->checked_in_at ? $booking->checked_in_at->hour : 14;
                                    $checkOutHour = $booking->checked_out_at ? $booking->checked_out_at->hour : 11;
                                    $isActiveToday = $booking->date_start->lt($date) || $checkInHour <= now()->hour;
                                    $colored = false;
                                @endphp
                                @foreach($hours as $h)
                                    @php
                                        $isCheckInCell = !$colored && $h >= $checkInHour && $booking->date_start->lte($date);
                                        $isOccupied = $colored && $h < $checkOutHour;
                                        $isCheckoutCell = $h === $checkOutHour && $colored;

                                        if ($isCheckInCell) $colored = true;
                                        $showBooking = $isCheckInCell || $isOccupied;
                                    @endphp
                                    <td class="text-center p-0 {{ $showBooking ? ($booking->status === 'checked_in' ? 'bg-warning-lt' : 'bg-success-lt') : '' }}"
                                        @if($showBooking) title="{{ $b->guest_name ?? $booking->guest_name }}" @endif>
                                        @if($isCheckInCell)
                                            <a href="{{ route('bookings.show', $booking) }}"
                                               class="d-block text-decoration-none py-1 px-0 small fw-semibold"
                                               dusk="cal-booked-{{ $booking->id }}">
                                                {{ Str::limit($booking->guest_name, 10, '…') }}
                                            </a>
                                        @elseif($isOccupied)
                                            <span class="d-block py-1 text-transparent">█</span>
                                        @else
                                            <span class="d-block py-1 text-transparent">·</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="text-center small">
                                    @if($booking->date_start->lte($date) && $booking->date_end->gt($date))
                                        <a href="{{ route('bookings.show', $booking) }}" class="text-decoration-none">
                                            {{ Str::limit($booking->guest_name, 10, '…') }}
                                            <span class="text-secondary d-block">{{ $booking->date_start->format('d/m') }}–{{ $booking->date_end->format('d/m') }}</span>
                                        </a>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr><td colspan="{{ count($hours) + 2 }}" class="text-center text-muted py-4">{{ __('ui.empty_bookings') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </x-ui.card>
</x-layouts.app>
