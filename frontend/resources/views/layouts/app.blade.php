<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'MediGO')</title>
    
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <!-- Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script>
        window.MEDIGO_BE_URL = @json(config('services.medigo.url', env('MEDIGO_BE', 'http://127.0.0.1:8005')));
    </script>
    @stack('styles')
</head>

<body class="{{ request()->is('map*') ? 'map-page-body' : '' }} {{ request()->is('faskes/*') ? 'faskes-detail-page' : '' }}">

    {{-- NAVBAR --}}
    @if(!request()->is('/'))
        @include('partials.header')
    @endif
    
    {{-- MAIN CONTENT --}}
    <main>
        @yield('content')
    </main>

     {{-- FOOTER --}}
     @include('partials.footer')

     {{-- MOBILE BOTTOM NAVBAR --}}
     @if(!request()->is('landing*'))
         @include('partials.bottom-nav')
     @endif

     <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Global Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1080;" id="globalToastContainer">
        <!-- Toasts will be injected here -->
    </div>

    <script>
        // Global Toast System
        window.showGlobalToast = function(message, type = 'success') {
            const container = document.getElementById('globalToastContainer');
            if (!container) return;

            const toastId = 'toast-' + Date.now();
            
            // Define styling based on type
            // Success uses primary color of the web (medigo-primary)
            let icon = 'bi-check-circle';
            let bgColor = 'bg-medigo-primary';
            let textColor = 'text-white';
            
            if (type === 'error') {
                icon = 'bi-exclamation-octagon';
                bgColor = 'bg-danger';
            } else if (type === 'warning') {
                icon = 'bi-exclamation-triangle';
                bgColor = 'bg-warning';
                textColor = 'text-dark';
            } else if (type === 'success') {
                icon = 'bi-check-circle';
                // User requested success to be primary color
                bgColor = 'bg-medigo-primary';
            }

            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center ${textColor} ${bgColor} border-0 rounded-3 shadow-sm mb-2" role="alert" aria-live="assertive" aria-atomic="true">
                    <div class="d-flex">
                        <div class="toast-body d-flex align-items-center gap-2 fw-medium" style="font-size: 0.9rem;">
                            <i class="bi ${icon} fs-5"></i>
                            <span>${message}</span>
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', toastHtml);
            const toastEl = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastEl, { delay: 4000 });
            toast.show();
            
            toastEl.addEventListener('hidden.bs.toast', () => {
                toastEl.remove();
            });
        };

        // Auto-show toast if there are session flash messages
        document.addEventListener('DOMContentLoaded', () => {
            @if(session('success'))
                window.showGlobalToast(@json(session('success')), 'success');
            @endif
            @if(session('global_success'))
                window.showGlobalToast(@json(session('global_success')), 'success');
            @endif
            @if(session('error'))
                window.showGlobalToast(@json(session('error')), 'error');
            @endif
            @if(session('warning'))
                window.showGlobalToast(@json(session('warning')), 'warning');
            @endif
            @if($errors->any())
                window.showGlobalToast(@json($errors->first()), 'error');
            @endif
        });
    </script>

    @stack('scripts')
</body>

