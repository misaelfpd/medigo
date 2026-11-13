@extends('layouts.app')

@section('title', 'MediBot AI - Asisten Kesehatan Pintar | MediGO')

@push('styles')
    @vite('resources/css/app.css')
@endpush

@section('content')
@php
    $formatChatMessage = function($text) {
        if (empty($text)) return '';
        $safe = e($text);
        // Bold: **text**
        $safe = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $safe);
        // Markdown Links: [text](url)
        $safe = preg_replace_callback('/\[(.*?)\]\((https?:\/\/[^\s\)]+)\)/', function($m) {
            return '<a href="' . $m[2] . '" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary py-0 px-2 rounded-pill my-1 text-decoration-none d-inline-flex align-items-center gap-1" style="font-size:0.8rem;"><i class="bi bi-geo-alt-fill text-danger"></i> ' . $m[1] . '</a>';
        }, $safe);
        return nl2br($safe);
    };
@endphp
<div class="container chat-wrapper">
    <!-- Header -->
    <div class="chat-header bg-medigo-subtle d-flex align-items-center">
        <div class="bg-medigo-primary text-white p-2 rounded-3 me-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
            <i class="bi bi-stars fs-5"></i>
        </div>
        <div>
            <h5 class="mb-0 fw-bold text-medigo-primary">MediBot AI Pintar</h5>
        </div>
    </div>

    <div class="chat-body-container">
        <!-- Chat Box -->
        <div id="chatBox" class="chat-box d-flex flex-column">
            <!-- Initial Bot Message -->
            <div class="msg-row bot">
                <div class="avatar-bot"><i class="bi bi-stars"></i></div>
                <div class="bubble-wrap">
                    <div class="bubble">
                        Halo! Saya <strong>MediBot AI</strong>, asisten kesehatan pintar Anda. Ceritakan gejala fisik yang dirasakan, durasi keluhan, atau tanyakan ketersediaan dokter dan fasilitas BPJS di sekitar Anda.
                    </div>
                    <div class="timestamp">MediBot AI • <span class="time-now"></span></div>
                </div>
            </div>

            @foreach($chatHistory ?? [] as $chat)
                <div class="msg-row {{ ($chat['role'] ?? '') === 'user' ? 'user' : 'bot' }}">
                    @if(($chat['role'] ?? '') === 'user')
                        @if(Auth::check() && !empty(Auth::user()->avatar) && file_exists(public_path(Auth::user()->avatar)))
                            <div class="avatar-user overflow-hidden p-0 border" style="width: 36px; height: 36px; border-radius: 50%;">
                                <img src="{{ asset(Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="w-100 h-100 object-fit-cover rounded-circle">
                            </div>
                        @else
                            <div class="avatar-user"><i class="bi bi-person-fill"></i></div>
                        @endif
                        <div class="bubble-wrap">
                            <div class="bubble">{!! nl2br(e($chat['message'] ?? '')) !!}</div>
                            <div class="timestamp">Anda • {{ !empty($chat['created_at']) ? \Carbon\Carbon::parse($chat['created_at'])->timezone('Asia/Jakarta')->format('H:i') . ' WIB' : '' }}</div>
                        </div>
                    @else
                        <div class="avatar-bot"><i class="bi bi-stars"></i></div>
                        <div class="bubble-wrap">
                            <div class="bubble">{!! $formatChatMessage($chat['message'] ?? '') !!}</div>
                            @if(!empty($chat['faskes']) && is_array($chat['faskes']))
                                <div class="faskes-cards-container" data-faskes='@json($chat['faskes'])'></div>
                            @endif
                            <div class="timestamp">MediBot AI • {{ !empty($chat['created_at']) ? \Carbon\Carbon::parse($chat['created_at'])->timezone('Asia/Jakarta')->format('H:i') . ' WIB' : '' }}</div>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>

        <!-- Input Area -->
        <div class="chat-input-area">
            <div class="input-wrapper d-flex align-items-center">
                <button class="btn btn-link text-muted px-2 text-decoration-none" title="Fitur lampiran segera hadir"><i class="bi bi-paperclip fs-5"></i></button>
                <input type="text" id="userInput" class="form-control" placeholder="Tuliskan keluhan, obat, atau faskes yang dicari..." autocomplete="off">
                <button class="btn btn-medigo-primary rounded-circle d-flex align-items-center justify-content-center ms-2 flex-shrink-0" style="width: 40px; height: 40px;" type="button" id="sendBtn">
                    <i class="bi bi-send-fill"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- MODAL CTA LOGIN -->
<div class="modal fade" id="loginCtaModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="loginCtaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content text-center p-4" style="border-radius: 20px; border: none; box-shadow: 0 10px 40px rgba(0,43,94,0.1);">
            <div class="modal-body">
                <div class="mb-3">
                    <div class="bg-medigo-subtle d-inline-flex align-items-center justify-content-center rounded-circle" style="width: 80px; height: 80px;">
                        <i class="bi bi-lock-fill text-medigo-primary" style="font-size: 2.5rem;"></i>
                    </div>
                </div>
                <h5 class="fw-bold text-dark mb-2">Batas Penggunaan Tercapai</h5>
                <p class="text-muted mb-4" style="font-size: 0.95rem;">
                    Kamu sudah mencoba MediBot AI sebanyak 6 kali! Yuk, login atau daftar akun MediGo secara gratis untuk menikmati akses chat tanpa batas dan fitur unggulan lainnya.
                </p>
                <div class="d-flex flex-column gap-2">
                    <a href="{{ route('login', ['redirect' => route('chat')]) }}" class="btn btn-medigo-primary py-2 w-100 fw-bold rounded-pill">Login Sekarang</a>
                    <button type="button" class="btn btn-medigo-outline py-2 w-100 fw-bold rounded-pill" data-bs-dismiss="modal">Nanti Saja</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    <!-- Meneruskan konfigurasi routing dasar ke JavaScript Module -->
    <script>
        window.MedigoConfig = {
            chatUrl: '{{ url('/chat') }}',
            loginUrl: '{{ route('login') }}',
            csrfToken: document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
            currentUserAvatar: @json(Auth::check() && !empty(Auth::user()->avatar) && file_exists(public_path(Auth::user()->avatar)) ? asset(Auth::user()->avatar) : null)
        };
    </script>
    @vite('resources/js/chat.js')
@endpush