<!-- Navbar / Top Header -->
<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container d-flex justify-content-between gap-5 align-items-center">
        <!-- Logo Section -->
        <a class="navbar-brand d-flex align-items-center m-0" href="/faskes">
            <img style="max-height: 40px; width: auto;" src="{{ asset('images/medigo_logo.png') }}" alt="Logo Faskes">
        </a>

        <!-- Desktop Navigation Links -->
        <div class="collapse navbar-collapse justify-content-center d-none d-lg-flex" id="navbarNav">
            <ul class="navbar-nav">
                <x-navbar.nav-link href="/faskes" :active="request()->is('/') || request()->is('faskes*')">
                    <i class="bi bi-house-door-fill"></i> Beranda
                </x-navbar.nav-link>

                <x-navbar.nav-link href="/chat" :active="request()->is('chat*')">
                    <i class="bi bi-stars"></i> Chat AI
                </x-navbar.nav-link>

                <x-navbar.nav-link href="/map" :active="request()->is('map*')">
                    <i class="bi bi-map"></i> Peta
                </x-navbar.nav-link>

                <x-navbar.nav-link href="/discussions" :active="request()->is('discussions*')">
                    <i class="bi bi-chat-square-text"></i> Forum
                </x-navbar.nav-link>


            </ul>
        </div>

        <!-- Desktop Top Right Action: Login / User Profile -->
        <div class="d-none d-lg-flex align-items-center gap-2">
            @if(Auth::check())
                <!-- Authenticated User Profile Link -->
                <a href="{{ route('profile') }}" class="btn user-nav-btn d-flex align-items-center gap-2 rounded-pill px-3 py-1 text-decoration-none {{ request()->is('profile*') ? 'btn-medigo-primary border-0' : 'border' }}">
                    <x-avatar :name="Auth::user()->name" :src="Auth::user()->avatar_url ?? null" :size="28" />
                    <span class="user-nav-name fw-semibold text-truncate {{ request()->is('profile*') ? 'text-white' : 'text-dark' }}" style="max-width: 130px; font-size: 0.88rem;">
                        {{ Auth::user()->name }}
                    </span>
                </a>
            @else
                <!-- Single Guest 'Masuk' Button -->
                <a href="{{ route('login') }}" class="btn btn-medigo-primary d-flex align-items-center gap-2 rounded-pill px-4 py-2 fw-semibold text-decoration-none shadow-xs text-white">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span>Masuk</span>
                </a>
            @endif
        </div>

        <!-- Mobile Top Right Action -->
        <div class="d-flex d-lg-none align-items-center gap-2">
            @if(Auth::check())
                <!-- Tampilan jika SUDAH login di Mobile -->
                <a href="{{ route('profile') }}" class="btn btn-sm user-nav-btn rounded-circle p-0 {{ request()->is('profile*') ? 'btn-medigo-primary border-0' : 'border' }} overflow-hidden" style="width: 32px; height: 32px; display: flex; align-items: center; justify-content: center;">
                    <x-avatar :name="Auth::user()->name" :src="Auth::user()->avatar_url ?? null" :size="32" />
                </a>
            @else
                <!-- Tampilan jika BELUM login di Mobile -->
                <a href="{{ route('login') }}" class="btn btn-sm btn-medigo-primary rounded-pill px-3 py-1 fw-semibold text-white text-decoration-none" style="font-size: 0.82rem;">
                    Masuk
                </a>
            @endif
        </div>
    </div>
</nav>
