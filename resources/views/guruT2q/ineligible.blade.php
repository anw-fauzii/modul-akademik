@extends('layouts.appui')

@section('title')
    <title>{{ $pageTitle ?? 'Yaumiyah Guru T2Q' }} - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @php
        $todayDate = $todayDate ?? \Carbon\Carbon::now();
        $displayName = $karyawanProfile?->nama_gelar ?? Auth::user()?->name ?? 'Guru T2Q';
        $ineligibleReason = $ineligibleReason ?? 'not_assigned_active_year';
    @endphp

    <div class="app-main__inner">
        <div class="wk-hero">
            <div class="wk-hero-glow"></div>
            <div class="row align-items-center position-relative" style="z-index: 1;">
                <div class="col-lg-9">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge text-white px-2 py-1 font-weight-bold"
                            style="background: rgba(234, 88, 12, 0.92); font-size: 11px; border-radius: 6px;">
                            <i class="fa fa-quran mr-1"></i> GURU T2Q
                        </span>
                        <span class="small" style="color: rgba(255, 255, 255, 0.72);">
                            <i class="fa fa-calendar-alt mr-1"></i>
                            {{ $todayDate->copy()->locale('id')->translatedFormat('l, d F Y') }}
                        </span>
                    </div>
                    <h3 class="font-weight-bold text-white mb-2">
                        {{ $heroTitle ?? 'Yaumiyah Bimbingan T2Q' }}
                    </h3>
                    <p class="mb-2" style="color: rgba(255, 255, 255, 0.78); max-width: 640px; font-size: 0.86rem;">
                        Halo {{ $displayName }}, halaman pencatatan Yaumiyah Tahsin &amp; Tahfizh terbuka otomatis berdasarkan daftar siswa bimbingan Anda pada data Anggota T2Q Tahun Ajaran aktif.
                    </p>
                </div>
                <div class="col-lg-3 d-flex justify-content-lg-end mt-2 mt-lg-0">
                    <a href="{{ route('guru-t2q.dashboard') }}" class="btn btn-sm btn-light font-weight-semibold shadow-sm" style="border-radius: 9px;">
                        <i class="fa fa-arrow-left text-warning mr-1"></i> Dashboard
                    </a>
                </div>
            </div>
        </div>

        <div class="wk-empty-guard">
            <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                style="width: 58px; height: 58px; border-radius: 16px; background: #fff7ed; color: #ea580c; font-size: 1.5rem;">
                <i class="fa fa-quran"></i>
            </div>
            <h5 class="font-weight-bold text-dark mb-2">
                Belum Ada Daftar Bimbingan T2Q Aktif
            </h5>
            @if ($ineligibleReason === 'no_active_tahun_ajaran')
                <p class="small text-muted mb-3">
                    Saat ini belum ada <strong>Tahun Ajaran &amp; Semester aktif</strong> di sistem. Data bimbingan T2Q akan tampil setelah Tahun Ajaran aktif ditetapkan oleh Admin.
                </p>
            @elseif ($ineligibleReason === 'missing_nipy')
                <p class="small text-muted mb-3">
                    Akun Anda belum memiliki data <strong>NIPY / Nomor Induk</strong> yang terhubung ke profil Karyawan.
                </p>
            @else
                <p class="small text-muted mb-3">
                    Pada Tahun Ajaran aktif (<strong>{{ $activeTahunAjaran?->full_label }}</strong>), NIPY Anda (<code>{{ $nipy ?? '-' }}</code>) belum tercatat sebagai pembimbing pada daftar <strong>Anggota T2Q</strong>.
                </p>
            @endif
            <div class="small text-muted px-3 py-2 rounded d-inline-block"
                style="background: #f8fafc; border: 1px solid #e2e8f0;">
                <i class="fa fa-info-circle text-warning mr-1"></i>
                Hubungi Admin Akademik apabila penetapan kelompok siswa Anggota T2Q perlu diperbarui.
            </div>
        </div>
    </div>
@endsection

