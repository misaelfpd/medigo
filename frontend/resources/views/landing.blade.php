@extends('layouts.app')

@section('title', 'MediGO - Temukan Layanan Kesehatan Terdekat')

@push('styles')
    @vite('resources/css/pages/landing.css')
@endpush

@php
    $features = [
        ['icon' => 'bi-geo-alt', 'tone' => 'blue', 'title' => 'Pencarian Real-Time', 'text' => 'Deteksi radius faskes terdekat dengan rute tercepat yang presisi.'],
        ['icon' => 'bi-shield-check', 'tone' => 'green', 'title' => 'BPJS & Asuransi', 'text' => 'Informasi lengkap faskes rujukan BPJS Kesehatan Kelas 1, 2, 3 hingga Non BPJS.'],
        ['icon' => 'bi-stars', 'tone' => 'purple', 'title' => 'Asisten AI Medis Cerdas', 'text' => 'Konsultasi awal berbasis AI yang menganalisis gejala Anda dan merekomendasikan poli yang tepat.'],
        ['icon' => 'bi-chat-square-text', 'tone' => 'orange', 'title' => 'Forum Diskusi & Komunitas', 'text' => 'Berbagi pengalaman faskes, tips kesehatan terpercaya, dan tanya jawab langsung dengan sesama pengguna.'],
    ];
    $services = [
        ['icon' => 'bi-exclamation-triangle', 'tone' => 'red', 'title' => 'IGD 24 Jam', 'text' => 'Respon Kritis Cepat'],
        ['icon' => 'bi-person', 'tone' => 'blue', 'title' => 'Poli Spesialis', 'text' => 'Dokter Berpengalaman'],
        ['icon' => 'bi-house-heart', 'tone' => 'green', 'title' => 'Rawat Inap & ICU', 'text' => 'Kebersihan Steril'],
        ['icon' => 'bi-eyedropper', 'tone' => 'purple', 'title' => 'Laboratorium', 'text' => 'Hasil Cepat & Akurat'],
        ['icon' => 'bi-heart', 'tone' => 'pink', 'title' => 'Ibu & Anak', 'text' => 'Poli Khusus Keluarga'],
    ];
    $steps = [
        ['title' => 'Tentukan Lokasi & Gejala', 'text' => 'Masukkan lokasi anda atau gunakan fitur Tanya AI untuk panduan awal indikasi keluhan kesehatan.'],
        ['title' => 'Pilih Rumah Sakit / Klinik', 'text' => 'Bandingkan jarak, ketersediaan dokter, fasilitas rawat, penerimaan BPJS, dan ulasan pasien terverifikasi.'],
        ['title' => 'Navigasi Langsung', 'text' => 'Dapatkan rute langsung dan panduan arah via GPS tercepat menuju lokasi fasilitas kesehatan tujuan Anda.'],
    ];
@endphp

@section('content')
    {{-- Topbar: hanya logo --}}
    <header class="mg-landing-topbar">
        <div class="container">
            <a href="{{ url('/') }}" class="d-inline-flex align-items-center" aria-label="MediGO">
                <img src="{{ asset('images/medigo_logo.png') }}" alt="MediGO - Your Health, Faster." class="mg-landing-logo">
            </a>
        </div>
    </header>

    {{-- Hero --}}
    <section class="mg-landing-hero" style="--hero-bg: url('{{ asset('images/reception.jpg') }}');">
        <div class="container mg-landing-hero__inner">
            <h1 class="mg-landing-hero__title">Temukan Layanan Kesehatan Terdekat</h1>
            <p class="mg-landing-hero__subtitle">
                Temukan rumah sakit dan klinik terbaik di dekat Anda dengan cepat. Akses layanan kesehatan yang dapat diandalkan saat Anda sangat membutuhkannya.
            </p>
            <div class="mg-landing-hero__actions">
                <a href="{{ route('faskes.index') }}" class="mg-landing-btn">
                    <span>Cari RS</span> <i class="bi bi-hospital"></i>
                </a>
                <a href="{{ route('chat') }}" class="mg-landing-btn">
                    <span>Tanya AI</span> <i class="bi bi-stars"></i>
                </a>
            </div>
        </div>
    </section>

    {{-- Keunggulan --}}
    <section class="mg-landing-section">
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

    {{-- Layanan Kesehatan Utama --}}
    <section class="mg-landing-section">
        <div class="container">
            <div class="mg-landing-heading">
                <h2>Layanan Kesehatan Utama</h2>
                <p>Temukan kategori fasilitas medis darurat maupun preventif sesuai dengan kebutuhan kesehatan Anda</p>
            </div>
            <div class="mg-landing-services">
                @foreach($services as $service)
                    <div class="mg-landing-card mg-landing-service">
                        <span class="mg-landing-icon mg-landing-icon--round mg-landing-icon--{{ $service['tone'] }}"><i class="bi {{ $service['icon'] }}"></i></span>
                        <h3>{{ $service['title'] }}</h3>
                        <p>{{ $service['text'] }}</p>
                    </div>
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
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endsection
