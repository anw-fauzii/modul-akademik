@extends('errors.layout')

@section('title', '403 - Akses Dibatasi')
@section('code', '403')
@section('badge', 'Status 403 • Akses Terbatas')

@section('quote')
Hak akses dan batasan wewenang adalah bagian dari sistem keamanan terpadu demi menjaga integritas serta privasi data seluruh civitas.
@endsection

@section('quote_author', 'Keamanan Sistem Terintegrasi SSO')

@section('icon')
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
</svg>
@endsection

@section('heading', 'Wewenang Akses Terbatas')

@section('description')
{{ isset($exception) && $exception->getMessage() ? $exception->getMessage() : 'Akun Anda saat ini tidak memiliki peran (Role) atau wewenang yang diizinkan untuk membuka fitur atau halaman ini.' }}
@endsection

@section('hint_title', 'Khusus Peran Super Admin')

@section('hint')
Menu ini dikhususkan bagi peran pengguna <strong>Super Admin</strong>. Silakan hubungi admin yayasan jika Anda memerlukan delegasi wewenang ini.
@endsection
