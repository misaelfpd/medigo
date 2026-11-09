@extends('layouts.app')

@section('title', 'MediGO - Temukan Layanan Kesehatan Terdekat')

@section('content')
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <h1 class="hero-title">Temukan Layanan Kesehatan Terdekat</h1>
            <p class="hero-subtitle">
                Temukan rumah sakit dan klinik terbaik di dekat Anda dengan cepat. Akses layanan kesehatan yang dapat diandalkan saat Anda sangat membutuhkannya.
            </p>
            
            <a href="/faskes" class="btn-search text-nowrap text-decoration-none">Mulai Sekarang</a>

            <!-- <div class="search-box">
                <i class="bi bi-search search-icon"></i>
                <input type="text" class="form-control search-input" placeholder="Cari rumah sakit, klinik...">
                <i class="bi bi-sliders filter-icon"></i>
                <button class="btn-search text-nowrap">Cari Faskes</button>
            </div> -->
        </div>
    </section>
@endsection
