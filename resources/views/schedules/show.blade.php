@php
$statusColors = [
    'draft' => 'secondary',
    'confirmed' => 'primary',
    'ongoing' => 'warning text-dark',
    'completed' => 'success',
    'cancelled' => 'danger',
];
@endphp

<x-layouts.app>
    <x-slot:title>{{ $schedule->product->name }} — {{ __('ui.schedules') }}</x-slot>

    <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h4 fw-semibold mb-1" dusk="schedule-title">{{ $schedule->product->name }}</h1>
            <x-ui.badge :color="$statusColors[$schedule->status]" dusk="schedule-status">
                {{ __('ui.status_' . $schedule->status) }}
            </x-ui.badge>
        </div>
        <a href="{{ route('schedules.index') }}" class="btn btn-outline-secondary btn-sm">{{ __('ui.cancel') }}</a>
    </div>

    @can('schedules.edit')
        @if (count($transitions) > 0)
            <div class="d-flex gap-2 mb-3 flex-wrap" dusk="status-actions">
                @foreach ($transitions as $t)
                    <form method="POST" action="{{ route('schedules.status', $schedule) }}" class="d-inline">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="status" value="{{ $t['to'] }}">
                        <button type="submit"
                                class="btn {{ $t['to'] === 'cancelled' ? 'btn-outline-danger' : 'btn-primary' }} btn-sm"
                                dusk="status-action-{{ $t['to'] }}">
                            {{ $t['label'] }}
                        </button>
                    </form>
                @endforeach
            </div>
        @endif
    @endcan

    <x-ui.card dusk="schedule-info">
        <div class="row g-3">
            <div class="col-md-4">
                <div class="text-muted small">{{ __('ui.date_start') }}</div>
                <div class="fw-semibold">{{ $schedule->date_start->translatedFormat('l, d F Y — H:i') }}</div>
            </div>
            <div class="col-md-4">
                <div class="text-muted small">{{ __('ui.date_end') }}</div>
                <div class="fw-semibold">
                    {{ $schedule->date_end?->translatedFormat('l, d F Y — H:i') ?? '-' }}
                </div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">{{ __('ui.branch') }}</div>
                <div class="fw-semibold">{{ $schedule->branch->name }}</div>
            </div>
            <div class="col-md-2">
                <div class="text-muted small">{{ __('ui.capacity') }}</div>
                <div class="fw-semibold" dusk="seats-info">
                    {{ $schedule->participants_count ?? $schedule->participants->count() }}/{{ $schedule->capacity }}
                </div>
            </div>
            @if ($schedule->notes)
                <div class="col-12">
                    <div class="text-muted small">{{ __('ui.note') }}</div>
                    <div>{{ $schedule->notes }}</div>
                </div>
            @endif
        </div>
    </x-ui.card>

    <div class="row g-3 mt-0">
        <div class="col-lg-6">
            <x-ui.card dusk="participants-card">
                <h2 class="h5 fw-semibold mb-3">{{ __('ui.participants') }} ({{ $schedule->participants->count() }})</h2>

                @can('schedules.edit')
                    <form method="POST" action="{{ route('schedules.participants.store', $schedule) }}"
                          class="row g-2 align-items-end mb-3" dusk="participant-form">
                        @csrf
                        <div class="col">
                            <x-ui.select name="customer_id" :label="__('ui.add_participant')" required
                                         :options="$customers->pluck('name', 'id')->all()"
                                         placeholder="{{ __('ui.select_customer') }}" />
                        </div>
                        <div class="col-auto">
                            <x-ui.button type="submit" variant="outline-primary" dusk="add-participant-button">
                                {{ __('ui.add') }}
                            </x-ui.button>
                        </div>
                    </form>
                @endcan

                <table class="table table-sm align-middle" dusk="participants-table">
                    <thead>
                    <tr>
                        <th>{{ __('ui.table_name') }}</th>
                        <th>{{ __('ui.phone') }}</th>
                        <th class="text-end">{{ __('ui.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($schedule->participants as $participant)
                        <tr dusk="participant-row-{{ $participant->id }}">
                            <td class="fw-semibold">{{ $participant->customer->name }}</td>
                            <td>{{ $participant->customer->phone ?? '-' }}</td>
                            <td class="text-end">
                                @can('schedules.edit')
                                    <form method="POST"
                                          action="{{ route('schedules.participants.destroy', [$schedule, $participant]) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('{{ __('ui.remove_participant_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                                dusk="remove-participant-{{ $participant->id }}">
                                            {{ __('ui.delete') }}
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">{{ __('ui.empty_schedules') }}</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </x-ui.card>
        </div>

        <div class="col-lg-6">
            <x-ui.card dusk="staff-card">
                <h2 class="h5 fw-semibold mb-3">{{ __('ui.staff') }} ({{ $schedule->staff->count() }})</h2>

                @can('schedules.edit')
                    <form method="POST" action="{{ route('schedules.staff.store', $schedule) }}"
                          class="row g-2 align-items-end mb-3" dusk="staff-form">
                        @csrf
                        <div class="col">
                            <x-ui.select name="user_id" :label="__('ui.add_staff_member')" required
                                         :options="$guides->pluck('name', 'id')->all()"
                                         placeholder="{{ __('ui.select_guide') }}" />
                        </div>
                        <div class="col-auto">
                            <x-ui.select name="role_in_trip" :label="__('ui.role_in_trip')" required
                                         :options="array_combine($staffRoles, array_map(fn ($r) => __('ui.role_' . $r), $staffRoles))"
                                         value="guide" />
                        </div>
                        <div class="col-auto">
                            <x-ui.button type="submit" variant="outline-primary" dusk="add-staff-button">
                                {{ __('ui.add') }}
                            </x-ui.button>
                        </div>
                    </form>
                @endcan

                <table class="table table-sm align-middle" dusk="staff-table">
                    <thead>
                    <tr>
                        <th>{{ __('ui.table_name') }}</th>
                        <th>{{ __('ui.role_in_trip') }}</th>
                        <th class="text-end">{{ __('ui.action') }}</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse ($schedule->staff as $staffMember)
                        <tr dusk="staff-row-{{ $staffMember->id }}">
                            <td class="fw-semibold">{{ $staffMember->user?->name ?? '-' }}</td>
                            <td>{{ __('ui.role_' . $staffMember->role_in_trip) }}</td>
                            <td class="text-end">
                                @can('schedules.edit')
                                    <form method="POST"
                                          action="{{ route('schedules.staff.destroy', [$schedule, $staffMember]) }}"
                                          class="d-inline"
                                          onsubmit="return confirm('{{ __('ui.remove_staff_confirm') }}')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-outline-danger btn-sm"
                                                dusk="remove-staff-{{ $staffMember->id }}">
                                            {{ __('ui.delete') }}
                                        </button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center text-muted py-3">-</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </x-ui.card>
        </div>
    </div>
</x-layouts.app>
