<!-- Bottom Navigation Bar for Mobile -->
<nav class="bottom-nav d-flex d-lg-none fixed-bottom bg-white border-top justify-content-around align-items-center py-2">
    <a href="/faskes" class="bottom-nav-link {{ (request()->is('faskes*') || request()->is('/')) ? 'active' : '' }}">
        <i class="bi {{ (request()->is('faskes*') || request()->is('faskes*')) ? 'bi-house-door-fill' : 'bi-house-door' }} bottom-nav-icon"></i>
        <span class="bottom-nav-text">Beranda</span>
    </a>
    <a href="/map" class="bottom-nav-link {{ request()->is('map*') ? 'active' : '' }}">
        <i class="bi {{ request()->is('map*') ? 'bi-map-fill' : 'bi-map' }} bottom-nav-icon"></i>
        <span class="bottom-nav-text">Peta</span>
    </a>
    <a href="/discussions" class="bottom-nav-link {{ request()->is('discussions*') ? 'active' : '' }}">
        <i class="bi {{ request()->is('discussions*') ? 'bi-chat-left-text-fill' : 'bi-chat-left-text' }} bottom-nav-icon"></i>
        <span class="bottom-nav-text">Forum</span>
    </a>
    <a href="/profile" class="bottom-nav-link {{ request()->is('profile*') ? 'active' : '' }}">
        @if(Auth::check())
            <x-avatar :name="Auth::user()->name" :src="Auth::user()->avatar_url ?? null" :size="24" class="mb-1" />
            <span class="bottom-nav-text">Profil</span>
        @else
            <i class="bi {{ request()->is('profile*') ? 'bi-person-fill' : 'bi-person' }} bottom-nav-icon"></i>
            <span class="bottom-nav-text">Masuk</span>
        @endif
    </a>
</nav>
