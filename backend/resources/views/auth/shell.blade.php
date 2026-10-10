<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="robots" content="noindex">
    <title>@yield('title') — EcoWin</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ecowin-emblem.png') }}">
    <style>
        html,body{margin:0;height:100%}
        body{font-family:'Inter Variable',Inter,ui-sans-serif,system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;-webkit-font-smoothing:antialiased}
        .ecl-root h1,.ecl-root h2,.ecl-root p{margin:0}
        .ecl-root *{box-sizing:border-box}
    </style>
</head>
<body>
    <div class="ecl-root">
        @include('auth.styles')

        <div class="ecl-bg" aria-hidden="true"></div>

        @include('auth.brand')

        <main class="ecl-form">
            <div class="ecl-card">
                @yield('card')
            </div>
        </main>

        @include('auth.features')
    </div>
</body>
</html>
