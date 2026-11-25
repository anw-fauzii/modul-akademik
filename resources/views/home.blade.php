@extends('layouts.appui')

@section('title')
    <title>Dashboard Akademik - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @pushOnce('css')
        <style>
            .adm-dock-bar {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.6rem;
                margin-bottom: 1.05rem;
            }

            @media (min-width: 640px) {
                .adm-dock-bar {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (min-width: 1180px) {
                .adm-dock-bar {
                    grid-template-columns: repeat(6, minmax(0, 1fr));
                }
            }

            .adm-dock-tile {
                display: flex;
                align-items: center;
                gap: 0.62rem;
                padding: 0.66rem 0.78rem;
                border-radius: 13px;
                border: 1px solid #e2e8f0;
                background: #ffffff;
                text-decoration: none !important;
                transition: transform 0.16s ease, box-shadow 0.16s ease, border-color 0.16s ease;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.025);
                min-width: 0;
            }

            .adm-dock-tile:hover {
                transform: translateY(-2px);
                border-color: #fdba74;
                box-shadow: 0 10px 20px -8px rgba(15, 23, 42, 0.1);
            }

            .adm-dock-icon {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.86rem;
                flex-shrink: 0;
            }

            .adm-dock-meta {
                min-width: 0;
                flex: 1 1 auto;
            }

            .adm-dock-title {
                font-size: 0.75rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.25;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .adm-dock-sub {
                font-size: 0.66rem;
                color: #64748b;
                line-height: 1.25;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-top: 0.1rem;
            }

            .adm-bento-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
                align-items: start;
            }

            @media (min-width: 1024px) {
                .adm-bento-row {
                    grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr);
                }
            }

            .adm-ghost-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.32rem;
                padding: 0.3rem 0.62rem;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #334155;
                font-size: 0.7rem;
                font-weight: 700;
                text-decoration: none !important;
                white-space: nowrap;
                transition: all 0.15s ease;
            }

            .adm-ghost-btn:hover {
                background: #ffffff;
                border-color: #cbd5e1;
                color: #0f172a;
            }

            .adm-accent-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.32rem;
                padding: 0.32rem 0.68rem;
                border-radius: 8px;
                border: 0;
                background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);
                color: #ffffff !important;
                font-size: 0.7rem;
                font-weight: 700;
                text-decoration: none !important;
                white-space: nowrap;
                box-shadow: 0 2px 6px rgba(234, 88, 12, 0.22);
            }

            .adm-unit-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.32rem;
                padding: 0.2rem 0.58rem;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #334155;
                font-size: 0.69rem;
                font-weight: 700;
                text-decoration: none !important;
                transition: all 0.14s ease;
            }

            .adm-unit-chip:hover {
                border-color: #fdba74;
                background: #fff7ed;
                color: #c2410c;
            }

            .adm-metric-strip {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.55rem;
                padding: 0.85rem 1.05rem;
                border-bottom: 1px solid #f1f5f9;
            }

            @media (min-width: 640px) {
                .adm-metric-strip {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .adm-mini-metric {
                display: block;
                padding: 0.55rem 0.72rem;
                border-radius: 10px;
                border: 1px solid #f1f5f9;
                background: #f8fafc;
                text-decoration: none !important;
                transition: border-color 0.14s ease, background-color 0.14s ease;
            }

            .adm-mini-metric:hover {
                border-color: #fdba74;
                background: #fff7ed;
            }

            .adm-list-item {
                padding-bottom: 0.58rem;
                margin-bottom: 0.58rem;
                border-bottom: 1px solid #f1f5f9;
            }

            .adm-list-item:last-child {
                padding-bottom: 0;
                margin-bottom: 0;
                border-bottom: 0;
            }

            .adm-info-subhead {
                font-size: 0.68rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
                margin-bottom: 0.5rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.35rem;
            }

            .adm-pill-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.14rem 0.5rem;
                border-radius: 999px;
                font-size: 0.66rem;
                font-weight: 700;
                white-space: nowrap;
            }
        </style>
    @endPushOnce

    @php
        $todayDate = $todayDate ?? \Carbon\Carbon::now('Asia/Jakarta');
        $jam = (int) $todayDate->format('H');
        $salam = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));
        $authUser = Auth::user();
        $displayName = $authUser?->name ?? 'Administrator';

        $activeTahunAjaran = $activeTahunAjaran ?? null;
        $totalTahunAjaran = (int) ($totalTahunAjaran ?? 0);
        $tahunAjaranList = $tahunAjaranList ?? collect();

        $studentStats = $studentStats ?? [
            'total' => 0,
            'aktif' => 0,
            'aktif_laki_laki' => 0,
            'aktif_perempuan' => 0,
            'punya_kelas' => 0,
            'belum_punya_kelas' => 0,
            'jenjang_aktif' => [],
        ];
        $totalKaryawan = (int) ($totalKaryawan ?? 0);
        $totalSekolahAktif = (int) ($totalSekolahAktif ?? 0);
        $activeClasses = $activeClasses ?? collect();
        $kelasSummary = $kelasSummary ?? [
            'total_kelas' => $activeClasses->count(),
            'total_siswa_rombel' => 0,
            'per_unit' => [],
        ];
        $presensiTodaySummary = $presensiTodaySummary ?? [
            'total' => 0,
            'hadir' => 0,
            'terlambat' => 0,
            'izin' => 0,
            'sakit' => 0,
            'alpa' => 0,
            'belum' => 0,
            'hadir_rate' => null,
        ];
        $operationalSummary = $operationalSummary ?? [
            'mapel_aktif' => 0,
            'tp_aktif' => 0,
            'jadwal_aktif' => 0,
            'ekskul_aktif' => 0,
            'anggota_t2q' => 0,
            't2q_tahsin_hari_ini' => 0,
            't2q_tahfizh_hari_ini' => 0,
            'finalisasi_final' => 0,
        ];
        $prestasiSummary = $prestasiSummary ?? [
            'table_ready' => false,
            'total' => 0,
            'akademik' => 0,
            'non_akademik' => 0,
            'beregu' => 0,
            'dengan_foto' => 0,
            'per_unit' => [],
        ];
        $recentPrestasi = $recentPrestasi ?? collect();
        $upcomingAgendas = $upcomingAgendas ?? collect();
        $latestPengumuman = $latestPengumuman ?? collect();

        $hadirHariIni = (int) ($presensiTodaySummary['hadir'] ?? 0) + (int) ($presensiTodaySummary['terlambat'] ?? 0);
        $setoranT2qHariIni = (int) ($operationalSummary['t2q_tahsin_hari_ini'] ?? 0) + (int) ($operationalSummary['t2q_tahfizh_hari_ini'] ?? 0);
        $jenjangAktifList = is_array($studentStats['jenjang_aktif'] ?? null) ? $studentStats['jenjang_aktif'] : [];
    @endphp

    <div class="app-main__inner">
        <!-- 1. HERO HEADER COMPACT -->
        <div class="wk-hero">
            <div class="wk-hero-glow"></div>

            <div class="row align-items-center position-relative" style="z-index: 1;">
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge text-white px-2 py-1 font-weight-bold"
                            style="background: rgba(234, 88, 12, 0.92); font-size: 11px; border-radius: 6px;">
                            <i class="fa fa-shield-alt mr-1"></i> PANEL ADMIN AKADEMIK
                        </span>
                        <span class="small" style="color: rgba(255, 255, 255, 0.72);">
                            <i class="fa fa-calendar-alt mr-1"></i>
                            {{ $todayDate->copy()->locale('id')->translatedFormat('l, d F Y') }}
                        </span>
                    </div>

                    <h3 class="font-weight-bold text-white mb-2">
                        {{ $salam }}, {{ $displayName }}!
                    </h3>

                    <p class="mb-3" style="color: rgba(255, 255, 255, 0.78); max-width: 640px; font-size: 0.85rem; line-height: 1.5;">
                        Pantau data peserta didik, rombongan belajar, presensi harian, capaian T2Q, prestasi siswa, dan agenda sekolah secara ringkas dalam satu panel.
                    </p>

                    <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                        @if ($activeTahunAjaran)
                            <span class="badge px-3 py-2"
                                style="background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.22); color: #ffffff; font-size: 0.74rem;">
                                <i class="fa fa-graduation-cap text-warning mr-1"></i>
                                TA Aktif: {{ $activeTahunAjaran->full_label }}
                            </span>
                        @else
                            <a href="{{ route('tahun-ajaran.index') }}" class="badge px-3 py-2 text-decoration-none"
                                style="background: rgba(239, 68, 68, 0.25); border: 1px solid rgba(248, 113, 113, 0.45); color: #fee2e2; font-size: 0.74rem;">
                                <i class="fa fa-exclamation-triangle mr-1"></i> Belum ada Tahun Ajaran aktif
                            </a>
                        @endif

                        <span class="badge px-3 py-2"
                            style="background: rgba(16, 185, 129, 0.22); border: 1px solid rgba(52, 211, 153, 0.35); color: #ecfdf5; font-size: 0.74rem;">
                            <i class="fa fa-school mr-1"></i>
                            {{ $totalSekolahAktif }} Profil Unit Aktif
                        </span>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="wk-team-card">
                        <div class="text-uppercase font-weight-bold mb-2"
                            style="font-size: 0.65rem; letter-spacing: 0.06em; color: rgba(255, 255, 255, 0.68);">
                            Aksi Operasional Cepat
                        </div>
                        <div class="d-flex flex-column" style="gap: 0.45rem;">
                            <a href="{{ route('presensi-siswa.index') }}"
                                class="btn btn-sm font-weight-bold text-white text-left d-flex align-items-center justify-content-between"
                                style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border-radius: 8px; padding: 0.48rem 0.75rem;">
                                <span><i class="fa fa-fingerprint mr-1"></i> Presensi &amp; Sync Fingerspot</span>
                                <i class="fa fa-arrow-right" style="font-size: 0.7rem;"></i>
                            </a>
                            <div class="d-flex" style="gap: 0.45rem;">
                                <a href="{{ route('prestasi-siswa.create') }}"
                                    class="btn btn-sm btn-outline-light flex-fill font-weight-bold"
                                    style="border-radius: 8px; font-size: 0.72rem;">
                                    <i class="fa fa-trophy mr-1 text-warning"></i> Input Prestasi
                                </a>
                                <a href="{{ route('tahun-ajaran.index') }}"
                                    class="btn btn-sm btn-outline-light flex-fill font-weight-bold"
                                    style="border-radius: 8px; font-size: 0.72rem;">
                                    <i class="fa fa-calendar mr-1"></i> Tahun Ajaran
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 2. DOCK AKSES CEPAT MODUL UTAMA -->
        <div class="adm-dock-bar">
            <a href="{{ route('siswa.index') }}" class="adm-dock-tile">
                <span class="adm-dock-icon" style="background: #eff6ff; color: #2563eb;">
                    <i class="fa fa-user-graduate"></i>
                </span>
                <div class="adm-dock-meta">
                    <div class="adm-dock-title">Data Siswa</div>
                    <div class="adm-dock-sub">{{ number_format($studentStats['aktif']) }} siswa aktif</div>
                </div>
            </a>

            <a href="{{ route('kelas.index') }}" class="adm-dock-tile">
                <span class="adm-dock-icon" style="background: #ecfdf5; color: #059669;">
                    <i class="fa fa-chalkboard"></i>
                </span>
                <div class="adm-dock-meta">
                    <div class="adm-dock-title">Kelas &amp; Rombel</div>
                    <div class="adm-dock-sub">{{ number_format($kelasSummary['total_kelas']) }} kelas aktif</div>
                </div>
            </a>

            <a href="{{ route('presensi-siswa.index') }}" class="adm-dock-tile">
                <span class="adm-dock-icon" style="background: #fff7ed; color: #ea580c;">
                    <i class="fa fa-user-check"></i>
                </span>
                <div class="adm-dock-meta">
                    <div class="adm-dock-title">Presensi Siswa</div>
                    <div class="adm-dock-sub">{{ number_format($presensiTodaySummary['total']) }} log hari ini</div>
                </div>
            </a>

            <a href="{{ route('t2q.yaumiyah-tahsin.index') }}" class="adm-dock-tile">
                <span class="adm-dock-icon" style="background: #f0f9ff; color: #0284c7;">
                    <i class="fa fa-book-open"></i>
                </span>
                <div class="adm-dock-meta">
                    <div class="adm-dock-title">Yaumiyah T2Q</div>
                    <div class="adm-dock-sub">{{ number_format($operationalSummary['anggota_t2q']) }} peserta</div>
                </div>
            </a>

            <a href="{{ route('penilaian-rapor.index') }}" class="adm-dock-tile">
                <span class="adm-dock-icon" style="background: #f5f3ff; color: #7c3aed;">
                    <i class="fa fa-award"></i>
                </span>
                <div class="adm-dock-meta">
                    <div class="adm-dock-title">Nilai &amp; Rapor</div>
                    <div class="adm-dock-sub">{{ number_format($operationalSummary['finalisasi_final']) }} finalisasi</div>
                </div>
            </a>

            <a href="{{ route('prestasi-siswa.index') }}" class="adm-dock-tile">
                <span class="adm-dock-icon" style="background: #fef3c7; color: #d97706;">
                    <i class="fa fa-trophy"></i>
                </span>
                <div class="adm-dock-meta">
                    <div class="adm-dock-title">Prestasi Siswa</div>
                    <div class="adm-dock-sub">
                        {{ ! empty($prestasiSummary['table_ready']) ? number_format($prestasiSummary['total']).' prestasi' : 'Rekam prestasi' }}
                    </div>
                </div>
            </a>
        </div>

        <!-- 3. 4 KARTU METRIK UTAMA -->
        <div class="wk-kpi-grid">
            <div class="wk-kpi-card">
                <div>
                    <div class="wk-kpi-label">
                        <span class="wk-kpi-dot" style="background: #3b82f6;"></span> Siswa Aktif
                    </div>
                    <div class="wk-kpi-value">{{ number_format($studentStats['aktif']) }}</div>
                    <div class="wk-kpi-sub">
                        {{ number_format($studentStats['aktif_laki_laki']) }} Laki-laki &bull; {{ number_format($studentStats['aktif_perempuan']) }} Perempuan
                    </div>
                </div>
                <span class="wk-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                    <i class="fa fa-users"></i>
                </span>
            </div>

            <div class="wk-kpi-card">
                <div>
                    <div class="wk-kpi-label">
                        <span class="wk-kpi-dot" style="background: #10b981;"></span> Rombel &amp; Pendidik
                    </div>
                    <div class="wk-kpi-value" style="color: #047857;">
                        {{ number_format($kelasSummary['total_kelas']) }} <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Kelas</span>
                    </div>
                    <div class="wk-kpi-sub">
                        <a href="{{ route('karyawan.index') }}" class="text-success font-weight-bold text-decoration-none">
                            {{ number_format($totalKaryawan) }} Guru/Staf
                        </a>
                        &bull; {{ number_format($kelasSummary['total_siswa_rombel']) }} siswa rombel
                    </div>
                </div>
                <span class="wk-kpi-icon" style="background: #ecfdf5; color: #059669;">
                    <i class="fa fa-user-tie"></i>
                </span>
            </div>

            <div class="wk-kpi-card">
                <div>
                    <div class="wk-kpi-label">
                        <span class="wk-kpi-dot" style="background: #ea580c;"></span> Presensi Hari Ini
                    </div>
                    <div class="wk-kpi-value" style="color: #c2410c;">
                        {{ number_format($hadirHariIni) }}<span style="font-size: 0.85rem; font-weight: 600; color: #64748b;"> / {{ number_format($kelasSummary['total_siswa_rombel']) }}</span>
                    </div>
                    <div class="wk-kpi-sub">
                        Terlambat {{ $presensiTodaySummary['terlambat'] }} &bull; Izin {{ $presensiTodaySummary['izin'] }} &bull; Sakit {{ $presensiTodaySummary['sakit'] }} &bull; Alpa {{ $presensiTodaySummary['alpa'] }}
                    </div>
                </div>
                <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                    <i class="fa fa-user-check"></i>
                </span>
            </div>

            <div class="wk-kpi-card">
                <div>
                    <div class="wk-kpi-label">
                        <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Setoran T2Q Hari Ini
                    </div>
                    <div class="wk-kpi-value" style="color: #6d28d9;">
                        {{ number_format($setoranT2qHariIni) }} <span style="font-size: 0.85rem; font-weight: 600; color: #64748b;">Log</span>
                    </div>
                    <div class="wk-kpi-sub">
                        Tahsin {{ number_format($operationalSummary['t2q_tahsin_hari_ini']) }} &bull; Tahfizh {{ number_format($operationalSummary['t2q_tahfizh_hari_ini']) }}
                    </div>
                </div>
                <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                    <i class="fa fa-quran"></i>
                </span>
            </div>
        </div>

        <!-- 4. BENTO WORKSPACE -->
        <div class="adm-bento-row">
            <!-- KOLOM KIRI -->
            <div>
                <!-- SEBARAN KELAS PER UNIT -->
                <div class="wk-surface">
                    <div class="wk-surface-head">
                        <div>
                            <h6 class="wk-surface-title">Sebaran Unit &amp; Kelas Tahun Ajaran Aktif</h6>
                            <div class="text-muted small">
                                @if ($activeTahunAjaran)
                                    Periode {{ $activeTahunAjaran->full_label }} &bull; {{ number_format($kelasSummary['total_kelas']) }} kelas terdata
                                @else
                                    Belum ada tahun ajaran berstatus aktif.
                                @endif
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                            <div class="wk-search-wrap" style="max-width: 195px;">
                                <i class="fa fa-search"></i>
                                <input type="text" id="admClassSearch" class="wk-search-input" placeholder="Cari kelas / wali...">
                            </div>
                            <a href="{{ route('kelas.index') }}" class="adm-ghost-btn">
                                Semua Kelas <i class="fa fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    @if (! empty($kelasSummary['per_unit']) || ! empty($jenjangAktifList))
                        <div class="px-3 py-2 bg-slate-50 border-bottom d-flex flex-wrap align-items-center" style="gap: 0.4rem; border-color: #f1f5f9 !important;">
                            <span class="small font-weight-bold text-muted text-uppercase mr-1" style="font-size: 0.66rem;">Per Unit:</span>
                            @forelse ($kelasSummary['per_unit'] as $unitCode => $unitRow)
                                <a href="{{ route('kelas.index', ['jenjang' => $unitCode]) }}" class="adm-unit-chip">
                                    <span>{{ $unitCode }}</span>
                                    <span class="text-muted font-weight-normal">{{ $unitRow['kelas'] }} Kls &bull; {{ $unitRow['siswa'] }} Sis</span>
                                </a>
                            @empty
                                @foreach ($jenjangAktifList as $jKey => $jCount)
                                    @if ($jKey !== 'BELUM_DITEMPATKAN')
                                        <a href="{{ route('siswa.index', ['status' => \App\Models\Admin\DataMaster\Siswa::STATUS_AKTIF, 'jenjang' => $jKey]) }}" class="adm-unit-chip">
                                            <span>{{ $jKey }}</span>
                                            <span class="text-muted font-weight-normal">{{ number_format($jCount) }} Siswa</span>
                                        </a>
                                    @endif
                                @endforeach
                            @endforelse
                        </div>
                    @endif

                    <div class="wk-scroll-body" style="max-height: 310px;">
                        <table class="wk-table" id="admClassTable">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th>Unit</th>
                                    <th>Wali Kelas</th>
                                    <th class="text-center">Siswa</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($activeClasses as $kelasItem)
                                    @php
                                        $waliModel = $kelasItem->relationLoaded('waliKelas') ? $kelasItem->waliKelas : null;
                                        $pendampingModel = $kelasItem->relationLoaded('pendamping') ? $kelasItem->pendamping : null;
                                        $searchRow = strtolower(trim(
                                            $kelasItem->nama_kelas.' '.
                                            $kelasItem->jenjang_kelas.' '.
                                            ($waliModel?->nama_gelar ?? '').' '.
                                            ($pendampingModel?->nama_gelar ?? '')
                                        ));
                                    @endphp
                                    <tr data-adm-class-row data-search="{{ $searchRow }}">
                                        <td>
                                            <div class="font-weight-bold text-slate-800">{{ $kelasItem->nama_kelas }}</div>
                                            <div class="small text-muted" style="font-size: 0.69rem;">Tingkat {{ $kelasItem->tingkatan_kelas ?: '-' }}</div>
                                        </td>
                                        <td>
                                            <span class="badge {{ $kelasItem->jenjang_badge_class }} px-2 py-1" style="font-size: 0.68rem;">
                                                {{ strtoupper((string) $kelasItem->jenjang_kelas) }}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="text-slate-700 font-weight-600" style="font-size: 0.77rem;">
                                                {{ $waliModel?->nama_gelar ?? '-' }}
                                            </div>
                                            @if ($pendampingModel)
                                                <div class="small text-muted" style="font-size: 0.67rem;">
                                                    Pendamping: {{ $pendampingModel->nama_gelar }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="text-center font-weight-bold text-slate-800">
                                            {{ number_format((int) ($kelasItem->anggota_kelas_count ?? 0)) }}
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('kelas.show', $kelasItem) }}" class="adm-ghost-btn">
                                                Detail
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4 small">
                                            Belum ada kelas terdaftar pada Tahun Ajaran aktif.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- KURIKULUM & RINGKASAN TAHUN AJARAN -->
                <div class="wk-surface mb-0">
                    <div class="wk-surface-head">
                        <div>
                            <h6 class="wk-surface-title">Kurikulum &amp; Periode Tahun Ajaran</h6>
                            <div class="text-muted small">{{ $totalTahunAjaran }} periode tersimpan</div>
                        </div>
                        <a href="{{ route('tahun-ajaran.create') }}" class="adm-accent-btn">
                            <i class="fa fa-plus"></i> Tambah Tahun Ajaran
                        </a>
                    </div>

                    <div class="adm-metric-strip">
                        <a href="{{ route('mata-pelajaran.index') }}" class="adm-mini-metric">
                            <div class="small text-muted font-weight-bold" style="font-size: 0.66rem;">MATA PELAJARAN</div>
                            <div class="font-weight-bold text-slate-800" style="font-size: 1.05rem;">{{ number_format($operationalSummary['mapel_aktif']) }}</div>
                        </a>
                        <a href="{{ route('tujuan-pembelajaran.index') }}" class="adm-mini-metric">
                            <div class="small text-muted font-weight-bold" style="font-size: 0.66rem;">TUJUAN PEMBELAJARAN</div>
                            <div class="font-weight-bold text-slate-800" style="font-size: 1.05rem;">{{ number_format($operationalSummary['tp_aktif']) }}</div>
                        </a>
                        <a href="{{ route('jadwal-pelajaran.index') }}" class="adm-mini-metric">
                            <div class="small text-muted font-weight-bold" style="font-size: 0.66rem;">JADWAL AKTIF</div>
                            <div class="font-weight-bold text-slate-800" style="font-size: 1.05rem;">{{ number_format($operationalSummary['jadwal_aktif']) }}</div>
                        </a>
                        <a href="{{ route('ekstrakurikuler.index') }}" class="adm-mini-metric">
                            <div class="small text-muted font-weight-bold" style="font-size: 0.66rem;">EKSTRAKURIKULER</div>
                            <div class="font-weight-bold text-slate-800" style="font-size: 1.05rem;">{{ number_format($operationalSummary['ekskul_aktif']) }}</div>
                        </a>
                    </div>

                    <div class="table-responsive">
                        <table class="wk-table">
                            <thead>
                                <tr>
                                    <th>Tahun Ajaran</th>
                                    <th>Semester</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tahunAjaranList as $taItem)
                                    <tr>
                                        <td class="font-weight-bold text-slate-800">{{ $taItem->nama_tahun_ajaran }}</td>
                                        <td>
                                            <span class="badge badge-info px-2 py-1" style="font-size: 0.67rem;">
                                                {{ $taItem->semester_label }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            @if ($taItem->is_active)
                                                <span class="badge badge-success px-2 py-1" style="font-size: 0.67rem;">Aktif</span>
                                            @else
                                                <span class="badge badge-secondary px-2 py-1" style="font-size: 0.67rem;">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td class="text-right">
                                            <a href="{{ route('tahun-ajaran.edit', $taItem) }}" class="adm-ghost-btn">
                                                Edit
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-3 small">
                                            Belum ada data tahun ajaran.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- KOLOM KANAN -->
            <div>
                <!-- SOROTAN PRESTASI SISWA -->
                <div class="wk-surface">
                    <div class="wk-surface-head">
                        <div>
                            <h6 class="wk-surface-title">Sorotan Prestasi Siswa</h6>
                            <div class="text-muted small">
                                @if (! empty($prestasiSummary['table_ready']))
                                    {{ number_format($prestasiSummary['akademik']) }} akademik &bull; {{ number_format($prestasiSummary['non_akademik']) }} non-akademik
                                @else
                                    Modul pendataan prestasi siswa
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('prestasi-siswa.index') }}" class="adm-ghost-btn">
                            Kelola <i class="fa fa-arrow-right"></i>
                        </a>
                    </div>

                    @if (! empty($prestasiSummary['table_ready']) && ! empty($prestasiSummary['per_unit']))
                        <div class="px-3 py-2 bg-slate-50 border-bottom d-flex flex-wrap align-items-center" style="gap: 0.35rem; border-color: #f1f5f9 !important;">
                            @foreach ($prestasiSummary['per_unit'] as $pUnit => $pCount)
                                <a href="{{ route('prestasi-siswa.index', ['unit' => $pUnit]) }}" class="adm-unit-chip">
                                    <span>{{ $pUnit }}</span>
                                    <span class="text-muted font-weight-normal">{{ number_format($pCount) }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <div class="p-3">
                        @if (empty($prestasiSummary['table_ready']))
                            <div class="text-muted small py-2">
                                Modul Prestasi Siswa siap digunakan setelah menjalankan <code class="text-slate-700">php artisan migrate</code>.
                            </div>
                        @elseif ($recentPrestasi->isNotEmpty())
                            @foreach ($recentPrestasi as $prestasiItem)
                                @php
                                    $anggotaList = $prestasiItem->relationLoaded('siswa') ? $prestasiItem->siswa : collect();
                                    $pCount = $anggotaList->count();
                                    $cleanNote = trim(preg_replace('/\s+/', ' ', (string) $prestasiItem->keterangan) ?? '');
                                    $pSubLabel = $pCount > 1
                                        ? ($cleanNote !== '' ? \Illuminate\Support\Str::limit($cleanNote, 36).' • ' : '').'Beregu ('.$pCount.' Siswa)'
                                        : $prestasiItem->daftar_siswa_ringkas;
                                @endphp
                                <div class="adm-list-item">
                                    <div class="d-flex align-items-start justify-content-between" style="gap: 0.5rem;">
                                        <div style="min-width: 0;">
                                            <a href="{{ route('prestasi-siswa.show', $prestasiItem) }}"
                                                class="font-weight-bold text-slate-800 text-decoration-none d-block text-truncate"
                                                style="font-size: 0.79rem;">
                                                {{ $prestasiItem->nama_prestasi }}
                                            </a>
                                            <div class="small text-muted text-truncate" style="font-size: 0.7rem;">
                                                {{ $pSubLabel }} &bull; {{ $prestasiItem->tanggal_formatted }}
                                            </div>
                                        </div>
                                        <span class="adm-pill-chip flex-shrink-0" style="background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa;">
                                            {{ $prestasiItem->peringkat }}
                                        </span>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-3">
                                <div class="text-muted small mb-2">Belum ada prestasi siswa tercatat.</div>
                                <a href="{{ route('prestasi-siswa.create') }}" class="adm-accent-btn">
                                    <i class="fa fa-plus"></i> Catat Prestasi
                                </a>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- AGENDA & PENGUMUMAN TERBARU -->
                <div class="wk-surface mb-0">
                    <div class="wk-surface-head">
                        <h6 class="wk-surface-title">Agenda &amp; Pengumuman</h6>
                        <div class="d-flex align-items-center" style="gap: 0.35rem;">
                            <a href="{{ route('agenda-sekolah.index') }}" class="adm-ghost-btn">Agenda</a>
                            <a href="{{ route('pengumuman.index') }}" class="adm-ghost-btn">Pengumuman</a>
                        </div>
                    </div>

                    <div class="p-3">
                        <div class="adm-info-subhead">
                            <span><i class="fa fa-calendar-day text-primary mr-1"></i> Agenda Mendatang</span>
                            <span>{{ $upcomingAgendas->count() }} terdekat</span>
                        </div>
                        @forelse ($upcomingAgendas as $agendaItem)
                            <div class="adm-list-item">
                                <div class="d-flex align-items-start justify-content-between" style="gap: 0.5rem;">
                                    <div style="min-width: 0;">
                                        <div class="font-weight-bold text-slate-800 text-truncate" style="font-size: 0.78rem;">
                                            {{ $agendaItem->nama_agenda }}
                                        </div>
                                        <div class="small text-muted" style="font-size: 0.69rem;">
                                            {{ $agendaItem->tanggal_formatted }}
                                            @if ($agendaItem->jam_formatted !== '-')
                                                &bull; {{ $agendaItem->jam_formatted }}
                                            @endif
                                        </div>
                                    </div>
                                    <span class="badge {{ $agendaItem->unit_badge_class }} px-2 py-1 flex-shrink-0" style="font-size: 0.64rem;">
                                        {{ $agendaItem->unit }}
                                    </span>
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small mb-3">Belum ada agenda mendatang.</div>
                        @endforelse

                        <div class="adm-info-subhead mt-3 pt-2 border-top" style="border-color: #f1f5f9 !important;">
                            <span><i class="fa fa-bullhorn text-warning mr-1"></i> Pengumuman Terbaru</span>
                            <span>{{ $latestPengumuman->count() }} terbaru</span>
                        </div>
                        @forelse ($latestPengumuman as $pengumumanItem)
                            <div class="adm-list-item">
                                <div class="font-weight-bold text-slate-800 text-truncate" style="font-size: 0.78rem;">
                                    {{ $pengumumanItem->judul }}
                                </div>
                                <div class="small text-muted text-truncate" style="font-size: 0.7rem;">
                                    {{ $pengumumanItem->ringkasan_isi }}
                                </div>
                            </div>
                        @empty
                            <div class="text-muted small">Belum ada pengumuman terbaru.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        (function() {
            const searchInput = document.getElementById('admClassSearch');
            const rows = Array.from(document.querySelectorAll('[data-adm-class-row]'));

            if (!searchInput || rows.length === 0) {
                return;
            }

            searchInput.addEventListener('input', function() {
                const keyword = this.value.trim().toLowerCase();
                rows.forEach(function(row) {
                    const haystack = row.getAttribute('data-search') || '';
                    row.style.display = keyword === '' || haystack.includes(keyword) ? '' : 'none';
                });
            });
        })();
    </script>
@endpush
