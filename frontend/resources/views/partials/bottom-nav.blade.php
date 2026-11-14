<!-- Bottom Navigation Bar for Mobile -->
<nav class="bottom-nav d-flex d-lg-none fixed-bottom bg-white border-top justify-content-around align-items-center py-2">
    <a href="{{ route('faskes.index') }}" class="bottom-nav-link {{ request()->is('faskes*') ? 'active' : '' }}">
        <i class="bi {{ request()->is('faskes*') ? 'bi-house-door-fill' : 'bi-house-door' }} bottom-nav-icon"></i>
        <span class="bottom-nav-text">Beranda</span>
    </a>
    <a href="{{ route('map.index') }}" class="bottom-nav-link {{ request()->is('map*') ? 'active' : '' }}">
        <i class="bi {{ request()->is('map*') ? 'bi-map-fill' : 'bi-map' }} bottom-nav-icon"></i>
        <span class="bottom-nav-text">Peta</span>
    </a>
    <a href="{{ route('chat') }}" class="bottom-nav-link {{ request()->is('chat*') ? 'active' : '' }}">
        <i class="bi bi-stars bottom-nav-icon"></i>
        <span class="bottom-nav-text">MediBot</span>
    </a>
    <a href="{{ route('discussions') }}" class="bottom-nav-link {{ request()->is('discussions*') ? 'active' : '' }}">
        <i class="bi {{ request()->is('discussions*') ? 'bi-chat-left-text-fill' : 'bi-chat-left-text' }} bottom-nav-icon"></i>
        <span class="bottom-nav-text">Forum</span>
    </a>
    <a href="{{ route('profile') }}" class="bottom-nav-link {{ request()->is('profile*') ? 'active' : '' }}">
        @if(Auth::check())
            <x-avatar :name="Auth::user()->name" :src="Auth::user()->avatar_url ?? null" :size="24" class="mb-1" />
            <span class="bottom-nav-text">Profil</span>
        @else
            <i class="bi {{ request()->is('profile*') ? 'bi-person-fill' : 'bi-person' }} bottom-nav-icon"></i>
            <span class="bottom-nav-text">Masuk</span>
        @endif
    </a>
</nav>
