<x-layouts.app>
    <x-slot:title>{{ __('ui.page_users') }}</x-slot>
    <x-slot:page_actions>
        @can('users.create')
            <a href="{{ route('users.create') }}" class="btn btn-primary" dusk="create-user">
                <i class="ti ti-plus icon icon-2 me-1"></i>{{ __('ui.add') }} {{ __('ui.users') }}
            </a>
        @endcan
    </x-slot:page_actions>

    <x-ui.card dusk="users-table-card" :padded="false">
        <div class="table-responsive">
            <table class="table card-table table-vcenter text-nowrap table-hover" dusk="users-table">
                <thead>
                <tr>
                    <th>{{ __('ui.table_name') }}</th>
                    <th>{{ __('ui.email') }}</th>
                    <th>{{ __('ui.phone') }}</th>
                    <th>{{ __('ui.roles') }}</th>
                    <th>{{ __('ui.branches') }}</th>
                    <th>{{ __('ui.table_status') }}</th>
                    <th class="text-end">{{ __('ui.action') }}</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($users as $user)
                    <tr dusk="user-row-{{ $user->id }}">
                        <td class="fw-semibold">{{ $user->name }}</td>
                        <td>{{ $user->email }}</td>
                        <td>{{ $user->phone ?: '-' }}</td>
                        <td>
                            @foreach ($user->roles as $role)
                                <span class="badge bg-primary-subtle text-primary me-1">{{ $role->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            @foreach ($user->branches as $branch)
                                <span class="badge bg-secondary-subtle text-secondary me-1">{{ $branch->name }}</span>
                            @endforeach
                        </td>
                        <td>
                            <x-ui.badge :color="$user->is_active ? 'success' : 'secondary'">
                                {{ $user->is_active ? __('ui.active') : __('ui.inactive') }}
                            </x-ui.badge>
                        </td>
                        <td class="text-end">
                            @can('users.edit')
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-outline-primary btn-sm"
                                   dusk="edit-user-{{ $user->id }}">{{ __('ui.edit') }}</a>
                            @endcan
                            @can('users.delete')
                                <form method="POST" action="{{ route('users.destroy', $user) }}"
                                      class="d-inline" dusk="delete-user-form-{{ $user->id }}"
                                      onsubmit="return confirm('{{ __('ui.confirm_delete_user') }}')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-outline-danger btn-sm"
                                            dusk="delete-user-{{ $user->id }}">{{ __('ui.delete') }}</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-4">{{ __('ui.empty_users') }}</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </x-ui.card>
</x-layouts.app>