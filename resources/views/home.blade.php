@extends('layouts.appui')

@section('title')
    <title>Dashboard Akademik - SIPIT Yayasan Prima Insani</title>
@endsection

@section('content')
<style>
    /* Hero Banner */
    .dashboard-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
        border-radius: 16px;
        padding: 28px 32px;
        color: #ffffff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
    }
    .hero-decor-circle {
        position: absolute;
        right: -30px;
        top: -40px;
        width: 220px;
        height: 220px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(234, 88, 12, 0.25) 0%, rgba(234, 88, 12, 0) 70%);
        pointer-events: none;
    }
    .hero-decor-circle-2 {
        position: absolute;
        right: 120px;
        bottom: -60px;
        width: 160px;
        height: 160px;
        border-radius: 50%;
        background: radial-gradient(circle, rgba(217, 119, 6, 0.2) 0%, rgba(217, 119, 6, 0) 70%);
        pointer-events: none;
    }

    /* Modern KPI Cards */
    .kpi-card {
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        padding: 20px 22px;
        transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.03);
    }
    .kpi-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 24px -6px rgba(0, 0, 0, 0.08);
        border-color: #cbd5e1;
    }
    .kpi-icon-box {
        width: 50px;
        height: 50px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 22px;
        color: #ffffff;
        flex-shrink: 0;
    }

    /* Content Cards */
    .content-card {
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        background: #ffffff;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.04);
        margin-bottom: 24px;
        overflow: hidden;
    }
    .content-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid #f1f5f9;
        background-color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    /* Quick Jump Buttons */
    .quick-jump-item {
        display: flex;
        align-items: center;
        padding: 14px 16px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        text-decoration: none !important;
        color: #1e293b;
        transition: all 0.2s ease;
        height: 100%;
    }
    .quick-jump-item:hover {
        background: #ffffff;
        border-color: #ea580c;
        color: #ea580c;
        transform: translateY(-2px);
        box-shadow: 0 6px 16px rgba(234, 88, 12, 0.12);
    }
    .quick-jump-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        margin-right: 14px;
        flex-shrink: 0;
        color: #ffffff;
    }
</style>

@php
    $jam = (int) date('H');
    $salam = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));
@endphp

<div class="app-main__inner">
    <!-- HERO WELCOME BANNER -->
    <div class="dashboard-hero mb-4">
        <div class="hero-decor-circle"></div>
        <div class="hero-decor-circle-2"></div>
        
        <div class="row align-items-center position-relative" style="z-index: 1;">
            <div class="col-lg-8 mb-3 mb-lg-0">
                <div class="d-flex align-items-center mb-2">
                    <span class="badge text-white px-2 py-1 mr-2 font-weight-bold"
                        style="background: rgba(234, 88, 12, 0.9); font-size: 11px; border-radius: 6px;">
                        <i class="pe-7s-study mr-1"></i> MODUL AKADEMIK
                    </span>
                    <span class="text-white-50 small">
                        <i class="fa fa-calendar-alt mr-1"></i> {{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}
                    </span>
                </div>
                <h3 class="font-weight-bold text-white mb-2">
                    {{ $salam }}, {{ Auth::user()->name }}!
                </h3>
                <p class="text-white-50 mb-3" style="max-width: 620px; font-size: 13.5px; line-height: 1.5;">
                    Selamat datang di Dashboard Sistem Informasi Akademik Yayasan Prima Insani. Kelola data master tahun ajaran dan data pelengkap akademik secara terpusat, cepat, dan terpadu.
                </p>

                <div class="d-flex flex-wrap align-items-center">
                    @if ($activeTahunAjaran)
                        <div class="d-inline-flex align-items-center px-3 py-1.5 rounded mr-2 mb-2"
                            style="background-color: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2);">
                            <i class="fa fa-graduation-cap text-warning mr-2"></i>
                            <span class="small text-white">
                                Tahun Ajaran Aktif: <strong>{{ $activeTahunAjaran->nama_tahun_ajaran }}</strong>
                                <span class="badge badge-success ml-1">Semester {{ strtoupper($activeTahunAjaran->semester) }}</span>
                            </span>
                        </div>
                    @else
                        <div class="d-inline-flex align-items-center px-3 py-1.5 rounded mr-2 mb-2"
                            style="background-color: rgba(239, 68, 68, 0.2); border: 1px solid rgba(239, 68, 68, 0.3);">
                            <i class="fa fa-exclamation-circle text-danger mr-2"></i>
                            <span class="small text-white">
                                Belum ada Tahun Ajaran aktif. Silakan pilih atau tambahkan tahun ajaran.
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-lg-4 text-lg-right">
                <div class="d-flex flex-column flex-sm-row flex-lg-column justify-content-lg-end gap-2">
                    <a href="{{ route('tahun-ajaran.create') }}" class="btn font-weight-bold text-white shadow mb-2"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; padding: 10px 18px;">
                        <i class="fa fa-plus-circle mr-1"></i> Tambah Tahun Ajaran
                    </a>
                    <a href="{{ route('karyawan.index') }}" class="btn btn-outline-light btn-sm font-weight-bold py-2 mb-2"
                        style="border-radius: 8px;">
                        <i class="pe-7s-users mr-1"></i> Data Guru & Karyawan
                    </a>
                    <a href="{{ route('tahun-ajaran.index') }}" class="btn btn-outline-light btn-sm font-weight-bold py-2"
                        style="border-radius: 8px;">
                        <i class="pe-7s-date mr-1"></i> Kelola Tahun Ajaran
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- KPI STATS SUMMARY ROW -->
    <div class="row mb-4">
        <!-- Card 1: Total Tahun Ajaran -->
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                            Tahun Ajaran
                        </div>
                        <h2 class="font-weight-bold mb-0 text-slate-900 mt-1">
                            {{ $totalTahunAjaran }}
                        </h2>
                    </div>
                    <div class="kpi-icon-box shadow-sm" style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);">
                        <i class="pe-7s-date"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: #f1f5f9 !important;">
                    <span class="small text-muted">
                        Status Aktif:
                    </span>
                    @if ($activeTahunAjaran)
                        <span class="badge badge-success font-weight-bold">
                            {{ $activeTahunAjaran->nama_tahun_ajaran }} (Sem {{ $activeTahunAjaran->semester }})
                        </span>
                    @else
                        <span class="badge badge-secondary font-weight-bold">Nonaktif</span>
                    @endif
                </div>
            </div>
        </div>

        <!-- Card 2: Total Guru & Karyawan -->
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                            Guru & Karyawan
                        </div>
                        <h2 class="font-weight-bold mb-0 text-slate-900 mt-1">
                            {{ $totalKaryawan }}
                        </h2>
                    </div>
                    <div class="kpi-icon-box shadow-sm" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                        <i class="pe-7s-users"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: #f1f5f9 !important;">
                    <span class="small text-muted">Tenaga Pendidik:</span>
                    <a href="{{ route('karyawan.index') }}" class="font-weight-bold text-primary small">
                        Lihat Data <i class="fa fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Card 3: Pekerjaan & Penghasilan Ortu -->
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                            Pekerjaan & Penghasilan
                        </div>
                        <h2 class="font-weight-bold mb-0 text-slate-900 mt-1">
                            {{ $totalPekerjaan + $totalPenghasilan }}
                        </h2>
                    </div>
                    <div class="kpi-icon-box shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
                        <i class="pe-7s-portfolio"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: #f1f5f9 !important;">
                    <span class="small text-muted">Rincian:</span>
                    <span class="font-weight-bold text-emerald-600 small">
                        {{ $totalPekerjaan }} Pekerjaan • {{ $totalPenghasilan }} Penghasilan
                    </span>
                </div>
            </div>
        </div>

        <!-- Card 4: Penunjang Siswa Lainnya -->
        <div class="col-md-6 col-xl-3 mb-3">
            <div class="kpi-card">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <div>
                        <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.5px;">
                            Data Penunjang Siswa
                        </div>
                        <h2 class="font-weight-bold mb-0 text-slate-900 mt-1">
                            {{ $totalJenjangPendidikan + $totalTransportasi + $totalBerkebutuhanKhusus }}
                        </h2>
                    </div>
                    <div class="kpi-icon-box shadow-sm" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
                        <i class="pe-7s-study"></i>
                    </div>
                </div>
                <div class="d-flex align-items-center justify-content-between pt-2 border-top" style="border-color: #f1f5f9 !important;">
                    <span class="small text-muted">Jenjang / Trans / Khusus:</span>
                    <span class="font-weight-bold text-purple-600 small">
                        {{ $totalJenjangPendidikan }} / {{ $totalTransportasi }} / {{ $totalBerkebutuhanKhusus }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- MODUL DATA PELENGKAP QUICK JUMP CARDS -->
    <div class="content-card mb-4">
        <div class="content-card-header">
            <div>
                <h6 class="font-weight-bold text-slate-900 mb-0">
                    <i class="pe-7s-keypad mr-1 text-orange-600"></i> Modul Data Pelengkap Akademik
                </h6>
                <span class="text-muted small">Pintasan cepat untuk mengelola data referensi dan formulir siswa/wali</span>
            </div>
        </div>
        <div class="p-3">
            <div class="row">
                <!-- 1. Berkebutuhan Khusus -->
                <div class="col-lg col-md-4 col-sm-6 mb-2">
                    <a href="{{ route('berkebutuhan-khusus.index') }}" class="quick-jump-item">
                        <div class="quick-jump-icon shadow-sm" style="background: linear-gradient(135deg, #ea580c 0%, #c2410c 100%);">
                            <i class="pe-7s-id"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-slate-900" style="font-size: 13px;">Kebutuhan Khusus</div>
                            <span class="text-muted small">{{ $totalBerkebutuhanKhusus }} Data</span>
                        </div>
                    </a>
                </div>

                <!-- 2. Jenjang Pendidikan -->
                <div class="col-lg col-md-4 col-sm-6 mb-2">
                    <a href="{{ route('jenjang-pendidikan.index') }}" class="quick-jump-item">
                        <div class="quick-jump-icon shadow-sm" style="background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);">
                            <i class="pe-7s-study"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-slate-900" style="font-size: 13px;">Jenjang Pendidikan</div>
                            <span class="text-muted small">{{ $totalJenjangPendidikan }} Data</span>
                        </div>
                    </a>
                </div>

                <!-- 3. Pekerjaan -->
                <div class="col-lg col-md-4 col-sm-6 mb-2">
                    <a href="{{ route('pekerjaan.index') }}" class="quick-jump-item">
                        <div class="quick-jump-icon shadow-sm" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
                            <i class="pe-7s-portfolio"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-slate-900" style="font-size: 13px;">Pekerjaan</div>
                            <span class="text-muted small">{{ $totalPekerjaan }} Data</span>
                        </div>
                    </a>
                </div>

                <!-- 4. Penghasilan -->
                <div class="col-lg col-md-4 col-sm-6 mb-2">
                    <a href="{{ route('penghasilan.index') }}" class="quick-jump-item">
                        <div class="quick-jump-icon shadow-sm" style="background: linear-gradient(135deg, #f59e0b 0%, #b45309 100%);">
                            <i class="pe-7s-cash"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-slate-900" style="font-size: 13px;">Penghasilan</div>
                            <span class="text-muted small">{{ $totalPenghasilan }} Data</span>
                        </div>
                    </a>
                </div>

                <!-- 5. Transportasi -->
                <div class="col-lg col-md-4 col-sm-6 mb-2">
                    <a href="{{ route('transportasi.index') }}" class="quick-jump-item">
                        <div class="quick-jump-icon shadow-sm" style="background: linear-gradient(135deg, #8b5cf6 0%, #6d28d9 100%);">
                            <i class="pe-7s-car"></i>
                        </div>
                        <div>
                            <div class="font-weight-bold text-slate-900" style="font-size: 13px;">Transportasi</div>
                            <span class="text-muted small">{{ $totalTransportasi }} Data</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN TWO-COLUMN SECTION -->
    <div class="row">
        <!-- LEFT COLUMN: Tahun Ajaran Overview Table -->
        <div class="col-lg-7 mb-4">
            <div class="content-card h-100">
                <div class="content-card-header">
                    <div>
                        <h6 class="font-weight-bold text-slate-900 mb-0">
                            <i class="pe-7s-date mr-1 text-orange-600"></i> Ringkasan Tahun Ajaran
                        </h6>
                        <span class="text-muted small">Daftar periode tahun akademik dan semester</span>
                    </div>
                    <div>
                        <a href="{{ route('tahun-ajaran.index') }}" class="btn btn-sm btn-outline-primary" style="border-radius: 6px;">
                            Lihat Semua <i class="fa fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="bg-slate-50 text-slate-600">
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th>Tahun Ajaran</th>
                                    <th>Semester</th>
                                    <th class="text-center">Status</th>
                                    <th style="width: 15%; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $no = 1; @endphp
                                @forelse ($listTahunAjaran as $item)
                                    <tr>
                                        <td>{{ $no++ }}</td>
                                        <td><strong>{{ $item->nama_tahun_ajaran }}</strong></td>
                                        <td>
                                            <span class="badge badge-info">
                                                {{ strtoupper($item->semester) }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if ($item->is_active)
                                                <span class="badge badge-success">Aktif</span>
                                            @else
                                                <span class="badge badge-secondary">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ route('tahun-ajaran.edit', $item->id) }}"
                                                class="btn btn-xs btn-primary shadow-sm"
                                                style="border-radius: 4px; padding: 3px 8px;"
                                                title="Edit">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">
                                            <i class="fa fa-info-circle mr-1"></i> Belum ada data tahun ajaran.
                                            <a href="{{ route('tahun-ajaran.create') }}" class="text-primary font-weight-bold ml-1">
                                                Tambah Baru Sekarang
                                            </a>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT COLUMN: Panduan & Pintasan Input Cepat -->
        <div class="col-lg-5 mb-4">
            <!-- Card Panduan Operasional -->
            <div class="content-card mb-4">
                <div class="content-card-header">
                    <div>
                        <h6 class="font-weight-bold text-slate-900 mb-0">
                            <i class="pe-7s-info mr-1 text-primary"></i> Informasi & Panduan Akademik
                        </h6>
                        <span class="text-muted small">Petunjuk kelola data akademik sistem</span>
                    </div>
                </div>
                <div class="p-3">
                    <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                        <div class="kpi-icon-box mr-3" style="width: 38px; height: 38px; font-size: 16px; background: rgba(234, 88, 12, 0.1); color: #ea580c;">
                            <i class="pe-7s-check"></i>
                        </div>
                        <div>
                            <h6 class="font-weight-bold text-slate-900 mb-1" style="font-size: 13.5px;">Tahun Ajaran Aktif</h6>
                            <p class="text-muted small mb-0">
                                Pastikan hanya ada 1 (satu) Tahun Ajaran yang berstatus <strong>Aktif</strong> agar proses administrasi dan pencatatan nilai terhubung ke periode yang tepat.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start mb-3 pb-3 border-bottom">
                        <div class="kpi-icon-box mr-3" style="width: 38px; height: 38px; font-size: 16px; background: rgba(59, 130, 246, 0.1); color: #3b82f6;">
                            <i class="pe-7s-network"></i>
                        </div>
                        <div>
                            <h6 class="font-weight-bold text-slate-900 mb-1" style="font-size: 13.5px;">Kelengkapan Data Pelengkap</h6>
                            <p class="text-muted small mb-0">
                                Data referensi (Pekerjaan, Penghasilan, Jenjang Pendidikan, Transportasi, Kebutuhan Khusus) menjadi dasar pilihan pada formulir data siswa dan wali.
                            </p>
                        </div>
                    </div>

                    <div class="d-flex align-items-start">
                        <div class="kpi-icon-box mr-3" style="width: 38px; height: 38px; font-size: 16px; background: rgba(16, 185, 129, 0.1); color: #10b981;">
                            <i class="pe-7s-shield"></i>
                        </div>
                        <div>
                            <h6 class="font-weight-bold text-slate-900 mb-1" style="font-size: 13.5px;">Integritas & Keamanan Data</h6>
                            <p class="text-muted small mb-0">
                                Setiap perubahan tersimpan secara aman dengan proteksi CSRF dan validasi unik pada tiap kode dan entitas data.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Tambah Data Cepat -->
            <div class="content-card">
                <div class="content-card-header">
                    <div>
                        <h6 class="font-weight-bold text-slate-900 mb-0">
                            <i class="pe-7s-plus mr-1 text-emerald-600"></i> Tambah Data Cepat
                        </h6>
                        <span class="text-muted small">Buat entri baru langsung dari dashboard</span>
                    </div>
                </div>
                <div class="p-3">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="{{ route('tahun-ajaran.create') }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2" style="border-radius: 8px;">
                            <i class="fa fa-plus-circle text-orange-600 mr-1"></i> Tahun Ajaran
                        </a>
                        <a href="{{ route('berkebutuhan-khusus.create') }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2" style="border-radius: 8px;">
                            <i class="fa fa-plus-circle text-orange-600 mr-1"></i> Kebutuhan Khusus
                        </a>
                        <a href="{{ route('jenjang-pendidikan.create') }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2" style="border-radius: 8px;">
                            <i class="fa fa-plus-circle text-orange-600 mr-1"></i> Jenjang Pendidikan
                        </a>
                        <a href="{{ route('pekerjaan.create') }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2" style="border-radius: 8px;">
                            <i class="fa fa-plus-circle text-orange-600 mr-1"></i> Pekerjaan
                        </a>
                        <a href="{{ route('penghasilan.create') }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2" style="border-radius: 8px;">
                            <i class="fa fa-plus-circle text-orange-600 mr-1"></i> Penghasilan
                        </a>
                        <a href="{{ route('transportasi.create') }}" class="btn btn-sm btn-outline-secondary mr-2 mb-2" style="border-radius: 8px;">
                            <i class="fa fa-plus-circle text-orange-600 mr-1"></i> Transportasi
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
