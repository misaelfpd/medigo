@extends('layouts.app')

@section('title', $isLoggedIn ? 'Profil Saya - MediGO' : 'Masuk ke MediGO - Nikmati Fitur Diskusi & Komentar')

@php
    $savedHospitals = $savedHospitals ?? [];
    $activities = $activities ?? [];
@endphp

@section('content')
<div class="profile-page-wrapper py-3 py-lg-4">
    <div class="container">
        
        @if(!$isLoggedIn)
            <!-- KOMPONEN UNTUK GUEST -->
            @include('components.profile.guest-cta')
        @else

            <!-- ALERT PESAN -->
            <!-- 1. TAMPILAN DESKTOP (>= 992px) -->
            <div class="d-none d-lg-block">
                <div class="row g-4">
                    <div class="col-lg-4">
                        @include('components.profile.sidebar-desktop')
                    </div>
                    <div class="col-lg-8">
                        @include('components.profile.section-reviews', ['isMobile' => false])
                        @include('components.profile.section-discussions', ['isMobile' => false])
                        @include('components.profile.section-comments', ['isMobile' => false])
                    </div>
                </div>
            </div>

            <!-- 2. TAMPILAN MOBILE (< 992px) -->
            <div class="d-block d-lg-none">
                @include('components.profile.sidebar-mobile')
                
                @include('components.profile.section-reviews', ['isMobile' => true])
                @include('components.profile.section-discussions', ['isMobile' => true])
                @include('components.profile.section-comments', ['isMobile' => true])
                
                <!-- Menu Settings untuk Mobile (Pisahan dari Sidebar) -->
                <div class="card border rounded-4 bg-white shadow-2xs overflow-hidden mb-3 mt-4">
                    <div class="list-group list-group-flush profile-menu-list">
                        <a href="{{ route('profile.edit') }}" class="list-group-item list-group-item-action p-3">
                            <i class="bi bi-gear fs-5 text-secondary"></i> Pengaturan Akun
                        </a>
                    </div>
                </div>
                <form action="{{ route('logout') }}" method="POST" class="mb-4">
                    @csrf
                    <button type="submit" class="btn btn-outline-danger w-100 rounded-4 py-2-5">Keluar</button>
                </form>
            </div>
        @endif

    </div>
</div>

@push('scripts')
    <!-- MODULAR JS: Memanggil file javascript eksternal -->
    @vite('resources/js/pages/profile.js')
@endpush
@endsection