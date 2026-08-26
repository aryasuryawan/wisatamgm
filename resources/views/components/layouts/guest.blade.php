@props(['brand' => config('app.name', 'Tulamben Scuba')])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ $brand }}</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/app.js'])
    @endif
</head>
<body class="border-top-wide border-primary d-flex flex-column">
<div class="page page-center">
    <div class="container-normal">
        <div class="row align-items-center justify-content-center g-4">
            <div class="col-12 text-center mb-3">
                <h1 class="fw-bold text-primary mb-0">{{ $brand }}</h1>
            </div>
            <div class="col-12 col-sm-10 col-md-8 col-lg-6 col-xl-5">
                <x-ui.card>
                    {{ $slot }}
                </x-ui.card>
            </div>
        </div>
    </div>
</div>
</body>
</html>
