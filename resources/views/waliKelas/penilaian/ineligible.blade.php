@extends('layouts.appui')

@section('title')
    <title>{{ $pageTitle ?? 'Penilaian TP Wali Kelas' }} - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @php
        $heroDate = \Carbon\Carbon::now();
        $displayName = $karyawanProfile?->nama_gelar ?? Auth::user()?->name ?? 'Wali Kelas';
        $guardReason = $guardReason ?? 'unassigned';
        $assignedClasses = $assignedClasses ?? collect();
    @endphp

    <div class="app-main__inner">
        @include('waliKelas.partials._hero', [
            'todayDate' => $heroDate,
            'heroBadgeText' => 'PENILAIAN TP (SD) WALI KELAS',
            'heroTitle' => $heroTitle ?? 'Penilaian TP Kelas Binaan',
            'heroFallbackSubtitle' => "Halo {$displayName}, modul Penilaian TP dikhususkan bagi Wali Kelas atau Guru Pendamping jenjang SD pada Tahun Ajaran aktif.",
        ])

        @if ($guardReason === 'non_sd_only')
            <div class="wk-empty-guard">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                    style="width: 58px; height: 58px; border-radius: 16px; background: #fff7ed; color: #ea580c; font-size: 1.5rem;">
                    <i class="fa fa-school"></i>
                </div>
                <h5 class="font-weight-bold text-dark mb-2">
                    Modul Penilaian TP Khusus Jenjang SD
                </h5>
                <p class="small text-muted mb-3">
                    Pada Tahun Ajaran aktif (<strong>{{ $activeTahunAjaran?->full_label }}</strong>), kelas yang Anda ampu adalah:
                    <strong>{{ $assignedClasses->map(fn ($k): string => "{$k->nama_kelas} ({$k->jenjang_kelas})")->implode(', ') }}</strong>.
                    Modul Penilaian TP &amp; Sumatif Bab saat ini dikhususkan untuk jenjang <strong>SD</strong>.
                </p>
                <a href="{{ route('wali-kelas.dashboard') }}" class="btn btn-sm text-white font-weight-semibold px-3"
                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 9px;">
                    <i class="fa fa-arrow-left mr-1"></i> Kembali ke Dashboard Wali Kelas
                </a>
            </div>
        @else
            @include('waliKelas.partials._ineligible_guard')
        @endif
    </div>
@endsection

