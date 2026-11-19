@extends('errors.layout')

@section('title', '404 - Halaman Tidak Ditemukan')
@section('code', '404')
@section('badge', 'Status 404 • Halaman Tidak Ditemukan')

@section('quote')
Arah yang keliru terkadang menuntun pada proses evaluasi. Sistem kami memastikan Anda selalu memiliki jalan kembali ke tujuan kerja Anda.
@endsection

@section('quote_author', 'Inspirasi Insan Prima Terpadu')

@section('icon')
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 9v2m0 2h.01" />
</svg>
@endsection

@section('heading', 'Halaman Tidak Ditemukan')

@section('description')
{{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'Halaman atau berkas yang Anda tuju tidak tersedia. Tautan mungkin telah dipindahkan, dihapus, atau terjadi kekeliruan ketik URL.' }}
@endsection

@section('hint_title', 'Periksa Kembali Alamat Tautan')

@section('hint')
Pastikan URL yang dimasukkan sudah tepat, atau gunakan tombol navigasi di bawah untuk langsung menuju beranda sistem kepegawaian.
@endsection
