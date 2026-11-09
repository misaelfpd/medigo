@extends('layouts.app')

@section('title', 'Detail Diskusi - MediGO')

@push('styles')
<style>
    .hover-primary:hover { color: #002b5e !important; }
    .discussion-reply-form { position: sticky; bottom: 0; background: #fff; border-top: 1px solid #dee2e6; padding: 1rem 0; z-index: 1000; }
    @media (max-width: 991.98px) { .discussion-reply-form { bottom: 60px; } }
    .cursor-pointer { cursor: pointer; }
    .clickable-image { transition: transform 0.2s ease, box-shadow 0.2s ease; }
    .clickable-image:hover { transform: scale(1.02); box-shadow: 0 4px 15px rgba(0,0,0,0.1) !important; }
</style>
@endpush

@section('content')
<div class="container py-4">
    <!-- Tombol Kembali -->
    <div class="row mb-4">
        <div class="col-lg-8 mx-auto d-flex align-items-center justify-content-between">
            <a href="{{ route('discussions') }}" class="text-dark text-decoration-none d-flex align-items-center gap-2 hover-primary">
                <i class="bi bi-arrow-left fs-5"></i><span class="fw-semibold fs-5">Kembali</span>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8 mx-auto">
            
            <!-- Panggil Komponen Alerts -->
            @include('components.alerts')

            <!-- Panggil Header Diskusi -->
            @include('components.forum.detail-header', ['discussion' => $discussion])

            <!-- Panggil Konten Diskusi -->
            @include('components.forum.detail-content', ['discussion' => $discussion])

            <!-- Panggil Bagian Komentar & Balasan -->
            @include('components.forum.comments-section', ['comments' => $comments])

            <div style="height: 60px;"></div>
        </div>
    </div>
</div>

<!-- Panggil Komponen Form Balasan -->
@include('components.forum.reply-form', ['discussion' => $discussion])

<!-- Panggil Komponen Modals -->
@include('components.forum.modal')

@endsection

@push('scripts')
    <!-- Panggil Komponen Scripts -->
    @include('components.forum.scripts')
@endpush