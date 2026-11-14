@extends('errors.layout')

@section('code', '419')
@section('icon', 'bi-hourglass-split')
@section('title', 'Sesi Halaman Kedaluwarsa')
@section('message', 'Halaman terlalu lama dibiarkan terbuka sehingga sesinya berakhir. Kembali ke halaman sebelumnya, muat ulang, lalu kirim kembali formulir Anda.')

@section('actions')
    <a href="javascript:history.back()" class="err-btn err-btn--primary"><i class="bi bi-arrow-left"></i> Kembali ke Formulir</a>
    <a href="{{ url('/') }}" class="err-btn err-btn--ghost"><i class="bi bi-house-door"></i> Beranda</a>
@endsection
