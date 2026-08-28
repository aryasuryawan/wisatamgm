<x-layouts.guest>
    <div class="card card-md">
        <div class="card-body">
            <h2 class="h2 text-center mb-4">{{ __('auth.login_title') }}</h2>

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert" dusk="alert-danger">
                    {{ $errors->first() }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" autocomplete="off" novalidate dusk="login-form">
                @csrf

                <div class="mb-3">
                    <label class="form-label" for="email">{{ __('ui.email') }}</label>
                    <input
                        id="email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        placeholder="your@email.com"
                        required
                        autofocus
                        autocomplete="email"
                        class="form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                        dusk="input-email"
                    >
                    @error('email')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-2" x-data="{ show: false }">
                    <label class="form-label" for="password">
                        {{ __('ui.password') }}
                    </label>
                    <div class="input-group input-group-flat">
                        <input
                            id="password"
                            name="password"
                            :type="show ? 'text' : 'password'"
                            placeholder="{{ __('ui.password') }}"
                            required
                            autocomplete="current-password"
                            class="form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                            dusk="input-password"
                        >
                        <span class="input-group-text">
                            <a href="#" class="link-secondary" :title="show ? 'Hide password' : 'Show password'" @click.prevent="show = !show" tabindex="-1">
                                <i x-show="!show" class="ti ti-eye icon m-0"></i>
                                <i x-show="show" class="ti ti-eye-off icon m-0" x-cloak></i>
                            </a>
                        </span>
                    </div>
                    @error('password')
                        <div class="invalid-feedback d-block">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-2">
                    <label class="form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" value="1" dusk="checkbox-remember">
                        <span class="form-check-label">{{ __('ui.remember_me') }}</span>
                    </label>
                </div>

                <div class="form-footer">
                    <button type="submit" class="btn btn-primary w-100" dusk="login-button">
                        {{ __('auth.login_button') }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.guest>
