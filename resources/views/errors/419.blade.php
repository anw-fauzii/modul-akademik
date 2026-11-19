@extends('errors.layout')

@section('title', '419 - Sesi Kedaluwarsa')
@section('code', '419')
@section('badge', 'Status 419 • Page Expired')

@section('quote')
Keamanan akun dan integritas sesi adalah prioritas kami dalam memastikan setiap aktivitas kerja Anda tetap terlindungi.
@endsection

@section('quote_author', 'Protokol Keamanan Sesi SSO Prima Insani')

@section('icon')
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
</svg>
@endsection

@section('heading', 'Sesi Telah Berakhir')

@section('description')
{{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'Halaman ini telah berada dalam kondisi tidak aktif cukup lama sehingga token keamanan Anda telah kedaluwarsa.' }}
@endsection

@section('hint_title', 'Perbarui Sesi Kerja')

@section('hint')
Silakan muat ulang halaman untuk memperbarui token keamanan atau masuk ulang melalui akun SSO Anda.
@endsection
