@extends('layouts.app')

@section('title', 'MediGO - Temukan Layanan Kesehatan Terdekat')

@push('styles')
    @vite('resources/css/pages/landing.css')
@endpush

@push('scripts')
    @vite('resources/js/pages/landing.js')
@endpush

@php
    $features = [
        ['icon' => 'bi-geo-alt', 'tone' => 'blue', 'title' => 'Navigasi Langsung Google Maps', 'text' => 'Deteksi radius faskes terdekat dengan rute perjalanan tercepat yang otomatis terhubung dan diarahkan ke Google Maps secara presisi.'],
        ['icon' => 'bi-shield-check', 'tone' => 'green', 'title' => 'BPJS & Asuransi', 'text' => 'Informasi lengkap faskes rujukan BPJS Kesehatan Kelas 1, 2, 3 hingga Non BPJS.'],
        ['icon' => 'bi-stars', 'tone' => 'purple', 'title' => 'Asisten AI Medis Cerdas', 'text' => 'Konsultasi awal berbasis AI yang menganalisis gejala Anda dan merekomendasikan poli yang tepat.'],
        ['icon' => 'bi-chat-square-text', 'tone' => 'orange', 'title' => 'Forum Diskusi & Komunitas', 'text' => 'Berbagi pengalaman faskes, tips kesehatan terpercaya, dan tanya jawab langsung dengan sesama pengguna.'],
    ];

    $faskesTypes = [
        [
            'key' => 'rumah-sakit',
            'icon' => 'bi-hospital-fill',
            'title' => 'Rumah Sakit',
            'text' => 'Rawat inap, IGD 24 jam & poliklinik spesialis',
            'query' => 'Rumah Sakit',
        ],
        [
            'key' => 'puskesmas',
            'icon' => 'bi-house-heart-fill',
            'title' => 'Puskesmas',
            'text' => 'Layanan primer & rujukan pertama masyarakat',
            'query' => 'Puskesmas',
        ],
        [
            'key' => 'klinik',
            'icon' => 'bi-heart-pulse-fill',
            'title' => 'Klinik Pratama',
            'text' => 'Pemeriksaan rawat jalan dan tindakan cepat',
            'query' => 'Klinik',
        ],
        [
            'key' => 'apotek',
            'icon' => 'bi-capsule-pill',
            'title' => 'Apotek & Farmasi',
            'text' => 'Penebusan resep obat dan alat kesehatan',
            'query' => 'Apotek',
        ],
        [
            'key' => 'laboratorium',
            'icon' => 'bi-eyedropper',
            'title' => 'Laboratorium Medis',
            'text' => 'Pemeriksaan darah, diagnostik, & tes medis',
            'query' => 'Laboratorium',
        ],
        [
            'key' => 'optik',
            'icon' => 'bi-eyeglasses',
            'title' => 'Optik',
            'text' => 'Pemeriksaan refraksi mata dan kacamata',
            'query' => 'Optik',
        ],
        [
            'key' => 'dokter-gigi',
            'icon' => 'bi-tooth',
            'title' => 'Dokter Gigi',
            'text' => 'Perawatan kesehatan gigi, mulut, & estetika',
            'query' => 'Dokter Gigi',
        ],
        [
            'key' => 'dokter-umum',
            'icon' => 'bi-stethoscope',
            'title' => 'Praktik Mandiri Dokter',
            'text' => 'Konsultasi medis langsung dengan dokter umum',
            'query' => 'Dokter',
        ],
    ];

    $steps = [
        ['title' => 'Tentukan Lokasi & Gejala', 'text' => 'Masukkan lokasi Anda atau gunakan fitur Tanya AI untuk panduan awal indikasi keluhan kesehatan.'],
        ['title' => 'Pilih Rumah Sakit / Klinik', 'text' => 'Bandingkan jarak, ketersediaan dokter, fasilitas rawat, penerimaan BPJS, dan ulasan pasien terverifikasi.'],
        ['title' => 'Navigasi via Google Maps', 'text' => 'Dapatkan rute perjalanan langsung yang otomatis terhubung dan diarahkan ke Google Maps untuk navigasi GPS tercepat.'],
    ];
@endphp

@section('content')
    {{-- Topbar / Navbar Landing Page: Sticky dengan tombol 'Lihat Faskes' saat scroll --}}
    <header class="mg-landing-topbar" id="mgLandingTopbar">
        <div class="container mg-landing-topbar__inner">
            <a href="{{ url('/') }}" class="mg-landing-topbar__brand" aria-label="MediGO">
                <img src="{{ asset('images/medigo_logo.png') }}" alt="MediGO - Your Health, Faster." class="mg-landing-logo">
            </a>

            <div class="mg-landing-topbar__actions">
                <a href="{{ route('faskes.index') }}" class="mg-landing-topbar__btn" id="mgLandingNavFaskesBtn">
                    <span>Cari Faskes</span>
                </a>
            </div>
        </div>
    </header>

    {{-- Hero: Layar Penuh (100dvh) --}}
    <section class="mg-landing-hero" id="landingHero" style="--hero-bg: url('{{ asset('images/reception.jpg') }}');">
        <div class="container mg-landing-hero__inner">
            <h1 class="mg-landing-hero__title">Temukan Layanan Kesehatan Terdekat</h1>
            <p class="mg-landing-hero__subtitle">
                Temukan rumah sakit dan klinik terbaik di dekat Anda dengan cepat. Akses layanan kesehatan yang dapat diandalkan saat Anda sangat membutuhkannya.
            </p>
            <div class="mg-landing-hero__actions" id="landingHeroActions">
                <a href="{{ route('faskes.index') }}" class="mg-landing-btn">
                    <span>Cari RS</span> <i class="bi bi-hospital"></i>
                </a>
                <a href="{{ route('chat') }}" class="mg-landing-btn">
                    <span>Tanya AI</span> <i class="bi bi-stars"></i>
                </a>
            </div>
        </div>

        {{-- Indikator scroll ke section berikutnya --}}
        <a href="#keunggulan" class="mg-landing-hero__scroll" aria-label="Gulir ke bawah">
            <span class="mg-landing-hero__scroll-text">Pelajari Lebih Lanjut</span>
            <i class="bi bi-chevron-down mg-landing-hero__scroll-icon"></i>
        </a>
    </section>

    {{-- Keunggulan --}}
    <section class="mg-landing-section" id="keunggulan">
        <div class="container">
            <div class="mg-landing-heading">
                <h2>Keunggulan Ekosistem MediGo</h2>
                <p>Dirancang untuk menghubungkan Anda dengan fasilitas medis terbaik secara instan, aman, dan tanpa hambatan</p>
            </div>
            <div class="row g-3 g-lg-4">
                @foreach($features as $feature)
                    <div class="col-12 col-sm-6 col-lg-3">
                        <div class="mg-landing-card mg-landing-feature">
                            <span class="mg-landing-icon mg-landing-icon--{{ $feature['tone'] }}"><i class="bi {{ $feature['icon'] }}"></i></span>
                            <h3>{{ $feature['title'] }}</h3>
                            <p>{{ $feature['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Jenis Fasilitas Kesehatan --}}
    <section class="mg-landing-section">
        <div class="container">
            <div class="mg-landing-heading">
                <h2>Jenis Fasilitas Kesehatan</h2>
                <p>Temukan fasilitas medis terdekat sesuai kebutuhan penanganan kesehatan Anda dan keluarga</p>
            </div>
            <div class="mg-landing-services">
                @foreach($faskesTypes as $faskes)
                    <a href="{{ route('search.index', ['q' => $faskes['query']]) }}" class="mg-landing-card mg-landing-service text-decoration-none">
                        <span class="mg-landing-icon mg-landing-icon--round faskes-visual-{{ $faskes['key'] }} text-white shadow-sm">
                            <i class="bi {{ $faskes['icon'] }}"></i>
                        </span>
                        <h3>{{ $faskes['title'] }}</h3>
                        <p>{{ $faskes['text'] }}</p>
                    </a>
                @endforeach
            </div>
        </div>
    </section>

    {{-- 3 Langkah --}}
    <section class="mg-landing-section mg-landing-section--last">
        <div class="container">
            <div class="mg-landing-heading">
                <h2>3 Langkah Menemukan Penanganan Tepat</h2>
                <p>Lakukan dengan mudah dan cepat di Medigo</p>
            </div>
            <div class="row g-3 g-lg-4 justify-content-center">
                @foreach($steps as $i => $step)
                    <div class="col-12 col-md-4">
                        <div class="mg-landing-card mg-landing-step">
                            <span class="mg-landing-step__num">{{ $i + 1 }}</span>
                            <h3 class="step-title">{{ $step['title'] }}</h3>
                            <p>{{ $step['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
