@extends('errors.layout')

@section('title', '500 - Kendala Internal Server')
@section('code', '500')
@section('badge', 'Status 500 • Internal Error')

@section('quote')
Setiap sistem bertumbuh dari proses perbaikan yang berkesinambungan. Tim teknis senantiasa memantau dan menjaga stabilitas layanan.
@endsection

@section('quote_author', 'Pusat Operasional Teknologi & Informasi Prima Insani')

@section('icon')
<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01" />
</svg>
@endsection

@section('heading', 'Kendala Internal Server')

@section('description')
{{ isset($exception) && $exception->getMessage() && !app()->environment('production') ? $exception->getMessage() : 'Terjadi kendala pada sistem internal saat memproses instruksi ini. Laporan kendala telah tercatat dalam sistem monitoring.' }}
@endsection

@section('hint_title', 'Tindakan yang Disarankan')

@section('hint')
Silakan tunggu beberapa saat lalu coba segarkan halaman. Bila kendala berulang, silakan laporkan waktu kejadian kepada tim IT yayasan.
@endsection
