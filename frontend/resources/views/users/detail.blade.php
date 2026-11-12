@extends('layouts.app')

@section('title', ($faskes['nama_faskes'] ?? 'Detail Faskes') . ' - MediGO')

@push('styles')
    <!-- Leaflet CSS for Mini Map Preview -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css" />
@endpush

@php
    $currentUserId = $currentUserId ?? (Auth::id() ?? ($currentUser->id ?? null));
    $isLoggedIn = $isLoggedIn ?? Auth::check();
    $currentUser = $currentUser ?? Auth::user();
    $myComment = $myComment ?? null;
    $hasMoreComments = $hasMoreComments ?? false;
    $commentsPage = $commentsPage ?? 1;
    $reviews = $reviews ?? [];
    $ratingBreakdown = $ratingBreakdown ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
    $ratingPercentages = $ratingPercentages ?? [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
@endphp

@section('content')
<div class="faskes-detail-wrapper bg-white pb-5">

    {{-- Mobile Top Header --}}
    <x-detail.mobile-topbar :faskes="$faskes" />

    {{-- Mobile Hero Banner (Full-bleed di bawah topbar) --}}
    <x-detail.mobile-hero :faskes="$faskes" />

    <div class="container py-3 py-lg-4">

        {{-- Desktop Hero Showcase Banner --}}
        <x-detail.desktop-hero :faskes="$faskes" />

        {{-- Navigation Tabs (Tentang, Fasilitas, Spesialisasi) --}}
        <x-detail.tabs-nav />

        {{-- Main Content Area (Left 8 Cols Tab Content + Right 4 Cols Contact Sidebar) --}}
        <div class="row g-4 align-items-start">

            {{-- Left / Center Dynamic Tab Panes --}}
            <div class="col-lg-8 col-12">
                <div class="tab-content" id="faskesDetailTabContent">
                    {{-- Tab 1: Tentang --}}
                    <x-detail.tab-tentang
                        :faskes="$faskes"
                        :rating-percentages="$ratingPercentages"
                        :my-comment="$myComment"
                        :reviews="$reviews"
                        :has-more-comments="$hasMoreComments"
                    />

                    {{-- Tab 2: Fasilitas Medis (Scrollable, tanpa deskripsi & tanpa logo) --}}
                    <x-detail.tab-fasilitas :faskes="$faskes" />

                    {{-- Tab 3: Spesialisasi Medis (Scrollable, tanpa deskripsi & tanpa logo) --}}
                    <x-detail.tab-spesialisasi :faskes="$faskes" />
                </div>
            </div>

            {{-- Right Persistent Sidebar: Mini Map & Informasi Kontak --}}
            <div class="col-lg-4 col-12">
                <x-detail.contact-sidebar :faskes="$faskes" />
            </div>

        </div>
    </div>
</div>

{{-- Modals (Edit Review, Koreksi Data, Laporkan Ulasan) --}}
<x-detail.modals :faskes="$faskes" :my-comment="$myComment" />

{{-- Pass Data to Detail JS --}}
<script>
    window.detailFaskesData = {
        id: {{ $faskes['id'] }},
        nama_faskes: @json($faskes['nama_faskes']),
        lat: {{ $faskes['lat'] ?? -6.2758 }},
        lng: {{ $faskes['lng'] ?? 106.7820 }},
        navigasi_url: @json($faskes['navigasi_url']),
        alamat: @json($faskes['alamat'] ?? ''),
        kota_kabupaten: @json($faskes['kota_kabupaten'] ?? ''),
        isLoggedIn: @json($isLoggedIn),
        currentUserId: @json($currentUserId),
        myComment: @json($myComment),
        total_reviews: {{ (int)($faskes['total_reviews'] ?? 0) }},
        avg_rating: @json($faskes['avg_rating'] ?? null),
        rating_percentages: @json($ratingPercentages),
        csrfToken: @json(csrf_token())
    };
</script>
@endsection

@push('scripts')
    <!-- Leaflet JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js"></script>
    <!-- Detail Page JS Module -->
    @vite(['resources/js/detail.js'])
@endpush