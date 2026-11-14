<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MediGO')</title>
    <link rel="icon" type="image/png" href="{{ asset('storage/medigo_icon.png') }}">
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/css/medigo-ui.css', 'resources/js/app.js', 'resources/js/core/toast.js'])
    <script>
        window.MEDIGO_BE_URL = @json(config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005')));
    </script>
    @stack('styles')
</head>

<body class="{{ request()->is('map*') ? 'map-page-body' : '' }} {{ request()->is('faskes/*') ? 'faskes-detail-page' : '' }} {{ request()->is('search*') ? 'search-standalone-body' : '' }}">

    {{-- NAVBAR --}}
    @if(!request()->is('/') && !request()->is('search*'))
        @include('partials.header')
    @endif
    
    {{-- MAIN CONTENT --}}
    <main class="{{ request()->is('search*') ? 'search-standalone-main' : '' }}">
        @yield('content')
    </main>

     {{-- FOOTER --}}
     @if(!request()->is('search*'))
         @include('partials.footer')
     @endif

     {{-- MOBILE BOTTOM NAVBAR --}}
     @if(!request()->is('landing*') && !request()->is('search*'))
         @include('partials.bottom-nav')
     @endif

     <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    {{-- Pesan flash -> toast global (resources/js/core/toast.js) --}}
    @include('partials.flash')

    @stack('scripts')
</body>

