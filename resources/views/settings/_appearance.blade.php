@php $s = $settings['appearance'] ?? []; @endphp

<form method="POST" action="{{ route('settings.update', ['tab' => 'appearance']) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    <input type="hidden" name="tab" value="appearance">

    <div class="row g-4">
        <div class="col-12">
            <label class="form-label">{{ __('ui.login_illustration') }}</label>
            <div class="mb-3 p-3 border rounded bg-light text-center" style="min-height:200px;">
                @php
                    $illustrationPath = $s['login_illustration'] ?? \App\Models\Setting::get('login_illustration');
                    $hasCustom = $illustrationPath && \Illuminate\Support\Facades\Storage::disk('public')->exists($illustrationPath);
                @endphp
                @if($hasCustom)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($illustrationPath) }}" alt="Login illustration" style="max-height:260px; max-width:100%; object-fit:contain;" dusk="current-login-illustration">
                    <div class="form-hint mt-2">{{ $illustrationPath }}</div>
                @else
                    <div class="text-secondary small py-4">
                        <i class="ti ti-photo icon mb-2" style="font-size:2rem;"></i>
                        <div>{{ __('ui.login_illustration_empty') }}</div>
                        <div class="form-hint">{{ __('ui.login_illustration_default_hint') }}</div>
                    </div>
                    <div class="mt-2" style="opacity:.9; max-width:420px; margin:0 auto;">
                        @include('components.illustrations.default-login')
                    </div>
                @endif
            </div>

            <input type="file" name="login_illustration" class="form-control" accept="image/jpeg,image/png,image/webp,image/svg+xml" dusk="input-login-illustration">
            <div class="form-hint">{{ __('ui.login_illustration_hint') }}</div>

            @error('login_illustration')
                <div class="invalid-feedback d-block">{{ $message }}</div>
            @enderror

            @if($hasCustom)
                <label class="form-check mt-2">
                    <input class="form-check-input" type="checkbox" name="remove_login_illustration" value="1" dusk="checkbox-remove-illustration">
                    <span class="form-check-label">{{ __('ui.login_illustration_remove') }}</span>
                </label>
            @endif
        </div>
    </div>

    <div class="mt-4">
        <x-ui.button type="submit" variant="primary" dusk="save-appearance">{{ __('ui.save_changes') }}</x-ui.button>
        <span class="ms-2 form-hint">{{ __('ui.appearance_tab_hint') }}</span>
    </div>
</form>
