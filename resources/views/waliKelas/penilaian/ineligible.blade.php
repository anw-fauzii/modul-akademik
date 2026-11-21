@extends('layouts.appui')

@section('title')
    <title>{{ $pageTitle ?? 'Penilaian TP Wali Kelas' }} - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @php
        $heroDate = \Carbon\Carbon::now();
        $displayName = $karyawanProfile?->nama_gelar ?? Auth::user()?->name ?? 'Guru';
        $guardReason = $guardReason ?? 'unassigned';
        $assignedClasses = $assignedClasses ?? collect();
        $isGuruMapelScope = $isGuruMapelScope ?? false;
        $dashboardRouteName = $dashboardRouteName ?? 'wali-kelas.dashboard';
        $perkembanganRoutePrefix = $perkembanganRoutePrefix ?? 'wali-kelas.perkembangan-siswa';
        $dashboardLabel = $isGuruMapelScope ? 'Dashboard Guru Mapel' : 'Dashboard Wali Kelas';
    @endphp

    <div class="app-main__inner">
        @include('waliKelas.partials._hero', [
            'todayDate' => $heroDate,
            'heroBadgeText' => $isGuruMapelScope ? 'PENILAIAN TP (SD) GURU MAPEL' : 'PENILAIAN TP (SD) WALI KELAS',
            'heroTitle' => $heroTitle ?? 'Penilaian TP Mapel Diampu',
            'heroFallbackSubtitle' => "Halo {$displayName}, input & rekap Penilaian TP dikhususkan untuk mata pelajaran SD yang Anda ampu pada Jadwal Pelajaran Tahun Ajaran aktif.",
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
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route($perkembanganRoutePrefix.'.index') }}" class="btn btn-sm btn-outline-primary font-weight-semibold px-3"
                        style="border-radius: 9px;">
                        <i class="fa fa-chart-line mr-1"></i> Lihat Perkembangan Siswa
                    </a>
                    <a href="{{ route($dashboardRouteName) }}" class="btn btn-sm text-white font-weight-semibold px-3"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 9px;">
                        <i class="fa fa-arrow-left mr-1"></i> Kembali ke {{ $dashboardLabel }}
                    </a>
                </div>
            </div>
        @elseif ($guardReason === 'no_taught_mapel')
            <div class="wk-empty-guard">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                    style="width: 58px; height: 58px; border-radius: 16px; background: #eff6ff; color: #2563eb; font-size: 1.5rem;">
                    <i class="fa fa-chalkboard"></i>
                </div>
                <h5 class="font-weight-bold text-dark mb-2">
                    Belum Ada Mata Pelajaran yang Diampu di Jadwal
                </h5>
                <p class="small text-muted mb-3">
                    Pada Tahun Ajaran aktif (<strong>{{ $activeTahunAjaran?->full_label }}</strong>), NIPY Anda (<code>{{ $nipy ?? '-' }}</code>) belum tercatat sebagai pengampu mata pelajaran SD pada <strong>Jadwal Pelajaran</strong> aktif.
                    @if (! $isGuruMapelScope)
                        Jika Anda adalah Wali Kelas SD, Anda tetap dapat memantau hasil belajar seluruh mata pelajaran siswa binaan melalui menu <strong>Perkembangan Siswa</strong>.
                    @endif
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    @if (! $isGuruMapelScope)
                        <a href="{{ route($perkembanganRoutePrefix.'.index') }}" class="btn btn-sm text-white font-weight-semibold px-3"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 9px;">
                            <i class="fa fa-chart-line mr-1"></i> Buka Perkembangan Siswa
                        </a>
                    @endif
                    <a href="{{ route($dashboardRouteName) }}" class="btn btn-sm {{ $isGuruMapelScope ? 'text-white' : 'btn-outline-secondary' }} font-weight-semibold px-3"
                        style="{{ $isGuruMapelScope ? 'background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important;' : '' }} border-radius: 9px;">
                        <i class="fa fa-arrow-left mr-1"></i> {{ $dashboardLabel }}
                    </a>
                </div>
            </div>
        @else
            @include('waliKelas.partials._ineligible_guard')
        @endif
    </div>
@endsection
