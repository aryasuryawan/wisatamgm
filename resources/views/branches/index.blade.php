<x-layouts.app>
    <x-slot:title>{{ __('ui.page_branches') }}</x-slot>
    <x-slot:page_actions>
        @can('branches.create')
            <a href="{{ route('branches.create') }}" class="btn btn-primary" dusk="create-branch">
                <i class="ti ti-plus icon icon-2 me-1"></i>{{ __('ui.add') }} {{ __('ui.branches') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="branches-table-card" :padded="false">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="branches-table">
                <thead>
                <tr>
                    <th>{{ __('ui.table_name') }}</th>
                    <th>{{ __('ui.brand') }}</th>
                    <th>{{ __('ui.phone') }}</th>
                    <th>{{ __('ui.table_status') }}</th>
                    <th>{{ __('ui.staff') }}</th>
                    <th class="text-end">{{ __('ui.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($branches as $branch)
                    <tr dusk="branch-row-{{ $branch->id }}">
                        <td class="fw-semibold">{{ $branch->name }}</td>
                        <td>{{ $branch->brand }}</td>
                        <td>{{ $branch->phone ?: '-' }}</td>
                        <td>
                            <x-ui.badge :color="$branch->is_active ? 'success' : 'secondary'">
                                {{ $branch->is_active ? __('ui.active') : __('ui.inactive') }}
                            </x-ui.badge>
                        </td>
                        <td>{{ $branch->users_count }}</td>
                        <td class="text-end">
                            @can('branches.edit')
                                <a href="{{ route('branches.edit', $branch) }}" class="btn btn-outline-primary btn-sm"
                                   dusk="edit-branch-{{ $branch->id }}">{{ __('ui.edit') }}</a>
                            @endcan
                            @can('branches.delete')
                                <form method="POST" action="{{ route('branches.destroy', $branch) }}"
                                      class="d-inline" dusk="delete-branch-form-{{ $branch->id }}"
                                      onsubmit="return confirm('{{ __('ui.confirm_delete_branch') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            dusk="delete-branch-{{ $branch->id }}">{{ __('ui.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-4">{{ __('ui.empty_branches') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($branches->hasPages())
            <div class="card-footer">{{ $branches->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.app>
