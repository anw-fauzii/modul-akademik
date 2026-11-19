@extends('errors.layout')

@section('title', '400 - Permintaan Tidak Valid')
@section('code', '400')
@section('badge', 'Status 400 • Bad Request')

@section('quote')
Ketepatan dan integritas data adalah pondasi utama dalam pengelolaan kepegawaian yayasan yang profesional dan akuntabel.
@endsection

@section('quote_author', 'Tata Kelola Data Yayasan Prima Insani')

@section('icon')
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
</svg>
@endsection

@section('heading', 'Permintaan Tidak Valid')

@section('description')
{{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'Server tidak dapat memproses permintaan ini karena parameter, format berkas, atau data formulir tidak sesuai format yang diharapkan.' }}
@endsection

@section('hint_title', 'Validasi Parameter & Formulir')

@section('hint')
Coba muat ulang halaman, bersihkan cache peramban, atau pastikan format berkas dan isian data yang Anda kirim telah sesuai petunjuk.
@endsection
