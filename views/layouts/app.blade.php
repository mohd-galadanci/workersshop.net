<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>2025 Future App</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/2025future.css') }}">
    @stack('styles')
</head>
<body>
    <div class="future-app">
        @include('partials.navbar')
        @yield('content')
        @include('partials.modals.login')
        @include('partials.modals.register')
    </div>
    <script src="{{ asset('js/2025future.js') }}"></script>
    @stack('scripts')
</body>
</html>