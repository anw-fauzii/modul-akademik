@extends('layouts.appui')

@section('title')
    <title>{{ $pageTitle ?? 'Presensi Wali Kelas' }} - PION Yayasan Prima Insani</title>
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
            'heroBadgeText' => 'PRESENSI WALI KELAS',
            'heroTitle' => $heroTitle ?? 'Presensi Siswa Kelas Binaan',
            'heroFallbackSubtitle' => "Halo {$displayName}, modul Presensi Wali Kelas otomatis terbuka untuk kelas yang Anda ampu sebagai Wali Kelas atau Guru Pendamping pada Tahun Ajaran aktif.",
        ])

        @include('waliKelas.partials._ineligible_guard')
    </div>
@endsection

