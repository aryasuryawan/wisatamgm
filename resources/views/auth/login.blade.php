<x-layouts.guest>
    <h1 class="h5 fw-semibold mb-3">{{ __('auth.login_title') }}</h1>

    @if ($errors->any())
        <x-ui.alert type="danger" :message="$errors->first()" />
    @endif

    <form method="POST" action="{{ route('login') }}" dusk="login-form">
        @csrf

        <x-ui.input name="email" type="email" :label="__('ui.email')" required autofocus />

        <x-ui.input name="password" type="password" :label="__('ui.password')" required />

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember"
                   value="1" dusk="checkbox-remember">
            <label class="form-check-label" for="remember">{{ __('ui.remember_me') }}</label>
        </div>

        <x-ui.button type="submit" class="w-100" dusk="login-button">
            {{ __('auth.login_button') }}
        </x-ui.button>
    </form>
</x-layouts.guest>
