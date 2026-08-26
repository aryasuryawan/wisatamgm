@php
/** @var \App\Models\Branch|null $branch */
$isEdit = isset($branch) && $branch?->exists;
$action = $isEdit ? route('branches.update', $branch) : route('branches.store');
$assignedUserIds = $assignedUserIds ?? [];
@endphp

<form method="POST" action="{{ $action }}" dusk="branch-form">
    @csrf
    @if ($isEdit)
        @method('PUT')
    @endif

    <div class="row g-3">
        <div class="col-md-6">
            <x-ui.input name="name" :label="__('ui.name')" required :value="$branch->name ?? null" />
        </div>

        <div class="col-md-6">
            <x-ui.select name="brand" :label="__('ui.brand')" required
                         :options="['tulambenscuba' => 'Tulamben Scuba', 'scubago' => 'ScubaGo', 'lainnya' => __('ui.other')]"
                         :value="$branch->brand ?? null" />
        </div>

        <div class="col-md-6">
            <x-ui.input name="domain" type="text" :label="__('ui.code')" :placeholder="__('ui.example_domain')"
                        :value="$branch->domain ?? null" />
        </div>

        <div class="col-md-6">
            <x-ui.input name="phone" type="tel" :label="__('ui.phone')" :value="$branch->phone ?? null" />
        </div>

        <div class="col-md-6">
            <x-ui.select name="pic_user_id" :label="__('ui.staff')" :placeholder="__('ui.select_pic')"
                         :options="$users->pluck('name', 'id')->all()"
                         :value="$branch->pic_user_id ?? null" />
        </div>

        <div class="col-md-12">
            <label class="form-label fw-semibold">{{ __('ui.address') }}</label>
            <textarea name="address" rows="2" class="form-control"
                      dusk="input-address">{{ old('address', $branch->address ?? null) }}</textarea>
        </div>

        <div class="col-12">
            <label class="form-label fw-semibold d-block">{{ __('ui.assigned_staff') }}</label>
            <div class="d-flex flex-wrap gap-3" dusk="branch-users">
                @foreach ($users as $user)
                    <div class="form-check form-check-lg">
                        <input class="form-check-input" type="checkbox" name="users[]" id="user-{{ $user->id }}"
                               value="{{ $user->id }}" dusk="checkbox-user-{{ $user->id }}"
                               @checked(in_array($user->id, old('users', $assignedUserIds)))>
                        <label class="form-check-label" for="user-{{ $user->id }}">{{ $user->name }}</label>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="col-12">
            <x-ui.checkbox name="is_active" :label="__('ui.branch_active')"
                           :checked="$branch->is_active ?? true" />
        </div>
    </div>

    <div class="d-flex gap-2 mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-branch">
            {{ $isEdit ? __('ui.save_changes') : __('ui.create') }}
        </x-ui.button>

        <a href="{{ route('branches.index') }}" class="btn btn-outline-secondary">{{ __('ui.cancel') }}</a>
    </div>
</form>
