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
    <x-slot:title>{{ __('ui.page_schedules') }}</x-slot>

    <x-slot:page_actions>
        @can('schedules.create')
            <a href="{{ route('schedules.create') }}" class="btn btn-primary" dusk="create-schedule">
                + {{ __('ui.add') }} {{ __('ui.schedules') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="schedules-card" :padded="false">
        <div class="card-header"><form method="GET" action="{{ route('schedules.index') }}" class="row g-2 w-100">
            <div class="col-md-3">
                <x-ui.select name="status" :label="__('ui.table_status')"
                             :options="['' => __('ui.all_status')] + array_combine($statuses, array_map(fn ($s) => __('ui.status_' . $s), $statuses))"
                             :value="request('status')" />
            </div>
            <div class="col-md-3">
                <x-ui.select name="branch_id" :label="__('ui.branch')"
                             :options="['' => __('ui.all_branches')] + $branches->pluck('name', 'id')->all()"
                             :value="request('branch_id')" />
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-outline-secondary mb-3" dusk="filter-button">
                    {{ __('ui.filter') }}
                </button>
            </div>
        </form></div>

        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="schedules-table">
                <thead>
                <tr>
                    <th>{{ __('ui.date') }}</th>
                    <th>{{ __('ui.product') }}</th>
                    <th>{{ __('ui.branch') }}</th>
                    <th>{{ __('ui.capacity') }}</th>
                    <th>{{ __('ui.change_status') }}</th>
                    <th class="text-end">{{ __('ui.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($schedules as $schedule)
                    <tr dusk="schedule-row-{{ $schedule->id }}">
                        <td class="fw-semibold">
                            {{ $schedule->date_start->translatedFormat('d M Y H:i') }}
                        </td>
                        <td>{{ $schedule->product->name }}</td>
                        <td>{{ $schedule->branch->name }}</td>
                        <td>
                            {{ $schedule->participants_count }}/{{ $schedule->capacity }}
                            <small class="text-muted d-block">
                                {{ __('ui.seats_left', ['left' => $schedule->seatsLeft()]) }}
                            </small>
                        </td>
                        <td>
                            <x-ui.badge :color="$statusColors[$schedule->status]" dusk="schedule-status-{{ $schedule->id }}">
                                {{ __('ui.status_' . $schedule->status) }}
            </x-ui.badge>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('schedules.show', $schedule) }}"
                               class="btn btn-outline-secondary btn-sm" dusk="view-schedule-{{ $schedule->id }}">
                                Detail
                            </a>
                            @can('schedules.edit')
                                <a href="{{ route('schedules.edit', $schedule) }}"
                                   class="btn btn-outline-primary btn-sm" dusk="edit-schedule-{{ $schedule->id }}">
                                    {{ __('ui.edit') }}
                                </a>
                            @endcan
                            @can('schedules.delete')
                                <form method="POST" action="{{ route('schedules.destroy', $schedule) }}"
                                      class="d-inline"
                                      onsubmit="return confirm('{{ __('ui.confirm_delete_schedule') }}')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            dusk="delete-schedule-{{ $schedule->id }}">
                                        {{ __('ui.delete') }}
                                    </button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">{{ __('ui.empty_schedules') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card-footer">{{ $schedules->links() }}</div>
    </x-ui.card>
</x-layouts.app>
