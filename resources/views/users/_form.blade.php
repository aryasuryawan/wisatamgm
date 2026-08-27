@php
/** @var \App\Models\User|null $user */
$isEdit = isset($user) && $user?->exists;
$action = $isEdit ? route('users.update', $user) : route('users.store');
$assignedBranchIds = $assignedBranchIds ?? [];
$assignedRoleNames = $assignedRoleNames ?? [];
@endphp

<form method="POST" action="{{ $action }}" dusk="user-form">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <x-ui.input name="name" :label="__('ui.name')" required :value="$user->name ?? null" />
        </div>

        <div class="col-md-6">
            <x-ui.input name="email" type="email" :label="__('ui.email')" required :value="$user->email ?? null" />
        </div>

        <div class="col-md-6">
            <x-ui.input name="phone" type="tel" :label="__('ui.phone')" :value="$user->phone ?? null" />
        </div>

        <div class="col-md-6">
            <x-ui.input name="password" type="password" :label="__('ui.password')"
                        :required="!$isEdit" :value="null" autocomplete="new-password" />
        </div>

        <div class="col-md-6">
            <x-ui.input name="password_confirmation" type="password" :label="__('ui.password_confirm')"
                        :required="!$isEdit" :value="null" autocomplete="new-password" />
        </div>

        <div class="col-md-6">
            <x-ui.input name="base_salary" type="number" step="0.01" :label="__('ui.base_salary')"
                        :value="$user->base_salary ?? null" />
        </div>

        <div class="col-md-6">
            <x-ui.select name="commission_type" :label="__('ui.commission_type')"
                         :options="['per_pax' => __('ui.commission_per_pax'), 'per_trip' => __('ui.commission_per_trip'), 'none' => __('ui.commission_type_none')]"
                         :value="$user->commission_type ?? null"
                         :placeholder="__('ui.select_commission_type')" />
        </div>

        <div class="col-md-6">
            <x-ui.input name="commission_rate" type="number" step="0.01" :label="__('ui.commission_rate')"
                        :value="$user->commission_rate ?? null" />
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold d-block">{{ __('ui.assigned_branches') }}</label>
            <div class="d-flex flex-wrap gap-3" dusk="user-branches">
                @foreach ($branches as $branch)
                    <div class="form-check form-check-lg">
                        <input class="form-check-input" type="checkbox" name="branches[]" id="branch-{{ $branch->id }}"
                               value="{{ $branch->id }}" dusk="checkbox-branch-{{ $branch->id }}"
                               @checked(in_array($branch->id, old('branches', $assignedBranchIds)))>
                        <label class="form-check-label" for="branch-{{ $branch->id }}">{{ $branch->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold d-block">{{ __('ui.assigned_roles') }}</label>
            <div class="d-flex flex-wrap gap-3" dusk="user-roles">
                @foreach ($roles as $role)
                    <div class="form-check form-check-lg">
                        <input class="form-check-input" type="checkbox" name="roles[]" id="role-{{ $role->name }}"
                               value="{{ $role->name }}" dusk="checkbox-role-{{ $role->name }}"
                               @checked(in_array($role->name, old('roles', $assignedRoleNames)))>
                        <label class="form-check-label" for="role-{{ $role->name }}">{{ $role->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <x-ui.checkbox name="is_active" :label="__('ui.user_active')"
                           :checked="$user->is_active ?? true" />
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-user">
            {{ $isEdit ? __('ui.save_changes') : __('ui.create') }}
        </x-ui.button>

        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>