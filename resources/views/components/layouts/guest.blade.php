@props(['brand' => config('app.name', 'Tulamben Scuba')])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $brand }} — {{ __('auth.login_title') }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/app.js'])
    @endif
</head>
<body class="d-flex flex-column">
<script src="{{ asset('dist/js/tabler-theme.min.js') }}" onerror="this.remove()"></script>
<div class="page page-center">
    <div class="container container-normal py-4">
        <div class="row align-items-center g-4">
            <div class="col-lg">
                <div class="container-tight">
                    <div class="text-center mb-4">
                        <a href="{{ route('login') }}" class="navbar-brand navbar-brand-autodark">
                            <span class="navbar-brand-text fw-bold fs-2 text-primary" style="letter-spacing:.02em">{{ $brand }}</span>
                        </a>
                    </div>

                    {{ $slot }}

                    <div class="text-center text-secondary mt-3">
                        <x-ui.language-switcher />
                    </div>
                </div>
            </div>
            <div class="col-lg d-none d-lg-block">
                @php
                    $customIllustration = \App\Models\Setting::get('login_illustration');
                    $hasCustom = $customIllustration && \Illuminate\Support\Facades\Storage::disk('public')->exists($customIllustration);
                @endphp
                @if($hasCustom)
                    <img src="{{ \Illuminate\Support\Facades\Storage::url($customIllustration) }}"
                         alt="Login illustration" class="img d-block mx-auto" style="max-height:400px; max-width:100%; object-fit:contain;" dusk="login-illustration-custom">
                @else
                    @include('components.illustrations.default-login')
                @endif
            </div>
            </div>
        </div>
    </div>
</div>
</body>
</html>
