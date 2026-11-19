@extends('layouts.appui')

@section('title')
    <title>{{ $pageTitle ?? 'Yaumiyah T2Q Wali Kelas' }} - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @php
        $heroDate = \Carbon\Carbon::now();
        $displayName = $karyawanProfile?->nama_gelar ?? Auth::user()?->name ?? 'Wali Kelas';
    @endphp

    <div class="app-main__inner">
        @include('waliKelas.partials._hero', [
            'todayDate' => $heroDate,
            'heroBadgeText' => 'YAUMIYAH T2Q WALI KELAS',
            'heroTitle' => $heroTitle ?? 'Yaumiyah T2Q Kelas Binaan',
            'heroFallbackSubtitle' => "Halo {$displayName}, modul Matriks & Statistik Yaumiyah T2Q otomatis terbuka untuk memantau anggota kelas yang Anda ampu sebagai Wali Kelas atau Guru Pendamping.",
        ])

        @include('waliKelas.partials._ineligible_guard')
    </div>
@endsection

