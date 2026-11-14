@extends('layouts.app')

@section('title', 'Detail Diskusi - MediGO')

@push('styles')
    @vite(['resources/css/pages/forum-detail.css', 'resources/js/pages/forum-detail.js', 'resources/js/pages/forum-like.js'])
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
            
            <article class="mg-thread-card mb-4">
                <!-- Panggil Header Diskusi -->
                @include('components.forum.detail-header', ['discussion' => $discussion])

                <!-- Panggil Konten Diskusi -->
                @include('components.forum.detail-content', ['discussion' => $discussion])
            </article>

            <!-- Panggil Bagian Komentar & Balasan -->
            <div id="comments">
                @include('components.forum.comments-section', ['comments' => $comments])
            </div>

            <div style="height: 60px;"></div>
        </div>
    </div>
</div>

<!-- Panggil Komponen Form Balasan -->
@include('components.forum.reply-form', ['discussion' => $discussion])

<!-- Panggil Komponen Modals -->
@include('components.forum.modal')

@endsection
