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
    
    <!-- Global Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 9999; margin-top: 10px; margin-right: 6px;" id="globalToastContainer">
        <!-- Toasts will be injected here -->
    </div>

    <script>
        // Global Toast System (MediGO Primary Theme)
        window.showGlobalToast = function(message, type = 'success') {
            const container = document.getElementById('globalToastContainer');
            if (!container) return;

            const toastId = 'toast-' + Date.now();
            
            let icon = 'bi-check-circle-fill';
            let iconColor = '#34d399'; // emerald
            let iconBg = 'rgba(52, 211, 153, 0.18)';
            let titleText = 'Berhasil';
            let borderColor = 'rgba(52, 211, 153, 0.4)';
            let bgGradient = 'linear-gradient(135deg, #0b2b52 0%, #001f44 100%)';
            
            if (type === 'error') {
                icon = 'bi-exclamation-octagon-fill';
                iconColor = '#f87171'; // red
                iconBg = 'rgba(239, 68, 68, 0.2)';
                titleText = 'Terjadi Kesalahan';
                borderColor = 'rgba(239, 68, 68, 0.4)';
                bgGradient = 'linear-gradient(135deg, #2b0b14 0%, #150509 100%)';
            } else if (type === 'warning') {
                icon = 'bi-exclamation-triangle-fill';
                iconColor = '#fbbf24'; // amber
                iconBg = 'rgba(245, 158, 11, 0.2)';
                titleText = 'Perhatian';
                borderColor = 'rgba(245, 158, 11, 0.4)';
                bgGradient = 'linear-gradient(135deg, #2e1d08 0%, #170d03 100%)';
            } else if (type === 'info') {
                icon = 'bi-info-circle-fill';
                iconColor = '#38bdf8'; // sky blue
                iconBg = 'rgba(56, 189, 248, 0.2)';
                titleText = 'Informasi';
                borderColor = 'rgba(56, 189, 248, 0.4)';
                bgGradient = 'linear-gradient(135deg, #0b2b52 0%, #001f44 100%)';
            }

            const toastHtml = `
                <div id="${toastId}" class="toast align-items-center text-white border shadow-lg mb-2 overflow-hidden" 
                     role="alert" aria-live="assertive" aria-atomic="true"
                     style="background: ${bgGradient}; border-color: ${borderColor} !important; border-radius: 14px; min-width: 310px; max-width: 400px; box-shadow: 0 10px 25px -4px rgba(11, 43, 82, 0.4), 0 6px 10px -4px rgba(0, 0, 0, 0.3) !important;">
                    <div class="d-flex align-items-center p-3 gap-3">
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" 
                             style="width: 36px; height: 36px; background-color: ${iconBg}; color: ${iconColor};">
                            <i class="bi ${icon} fs-5"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="fw-semibold text-uppercase" style="font-size: 0.72rem; letter-spacing: 0.6px; color: ${iconColor}; opacity: 0.95;">
                                ${titleText}
                            </div>
                            <div class="fw-medium text-white text-break" style="font-size: 0.86rem; line-height: 1.38;">
                                ${message}
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white m-0 flex-shrink-0 align-self-start" data-bs-dismiss="toast" aria-label="Close" style="font-size: 0.75rem; opacity: 0.8;"></button>
                    </div>
                </div>
            `;

            container.insertAdjacentHTML('beforeend', toastHtml);
            const toastEl = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastEl, { delay: 4500 });
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

