@extends('layouts.appui')

@section('title')
    <title>Dashboard Wali Kelas - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @pushOnce('css')
        <style>
            .wk-dock-bar {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.6rem;
                margin-bottom: 1.05rem;
            }

            @media (min-width: 640px) {
                .wk-dock-bar.is-sd {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }

                .wk-dock-bar.is-non-sd {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1100px) {
                .wk-dock-bar.is-sd {
                    grid-template-columns: repeat(6, minmax(0, 1fr));
                }

                .wk-dock-bar.is-non-sd {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .wk-dock-tile {
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

            .wk-dock-tile:hover {
                transform: translateY(-2px);
                border-color: #fdba74;
                box-shadow: 0 10px 20px -8px rgba(15, 23, 42, 0.1);
            }

            .wk-dock-icon {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.86rem;
                flex-shrink: 0;
            }

            .wk-dock-meta {
                min-width: 0;
                flex: 1 1 auto;
            }

            .wk-dock-title {
                font-size: 0.75rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.25;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .wk-dock-sub {
                font-size: 0.66rem;
                color: #64748b;
                line-height: 1.25;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-top: 0.1rem;
            }

            .wk-bento-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
                align-items: start;
            }

            @media (min-width: 1024px) {
                .wk-bento-row {
                    grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr);
                }
            }

            .wk-seg-tabs {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.25rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 11px;
            }

            .wk-seg-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.38rem;
                padding: 0.36rem 0.75rem;
                border-radius: 8px;
                border: 0;
                background: transparent;
                color: #475569;
                font-size: 0.75rem;
                font-weight: 700;
                cursor: pointer;
                white-space: nowrap;
                transition: all 0.15s ease;
            }

            .wk-seg-btn:hover {
                color: #0f172a;
            }

            .wk-seg-btn.is-active {
                background: #ffffff;
                color: #c2410c;
                box-shadow: 0 1px 4px rgba(15, 23, 42, 0.08);
            }

            .wk-seg-count {
                display: inline-flex;
                align-items: center;
                padding: 0.08rem 0.42rem;
                border-radius: 999px;
                font-size: 0.65rem;
                font-weight: 800;
                background: #e2e8f0;
                color: #334155;
            }

            .wk-seg-btn.is-active .wk-seg-count {
                background: #ffedd5;
                color: #c2410c;
            }

            .wk-ghost-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.32rem;
                padding: 0.32rem 0.65rem;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #334155;
                font-size: 0.71rem;
                font-weight: 700;
                text-decoration: none !important;
                white-space: nowrap;
                transition: all 0.15s ease;
            }

            .wk-ghost-btn:hover {
                background: #ffffff;
                border-color: #cbd5e1;
                color: #0f172a;
            }

            .wk-accent-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.32rem;
                padding: 0.34rem 0.72rem;
                border-radius: 8px;
                border: 0;
                background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);
                color: #ffffff !important;
                font-size: 0.71rem;
                font-weight: 700;
                text-decoration: none !important;
                white-space: nowrap;
                box-shadow: 0 2px 6px rgba(234, 88, 12, 0.22);
                transition: opacity 0.15s ease, transform 0.15s ease;
            }

            .wk-accent-btn:hover {
                opacity: 0.94;
                transform: translateY(-1px);
            }

            .wk-pill-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                padding: 0.18rem 0.54rem;
                border-radius: 999px;
                font-size: 0.68rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .wk-readiness-item {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.55rem;
                padding: 0.46rem 0.68rem;
                border-radius: 10px;
                border: 1px solid #f1f5f9;
                background: #f8fafc;
            }

            .wk-timeline-item {
                display: flex;
                align-items: center;
                gap: 0.65rem;
                padding: 0.52rem 0.68rem;
                border-radius: 10px;
                background: #f8fafc;
                border: 1px solid #f1f5f9;
            }

            .wk-timeline-time {
                min-width: 60px;
                padding: 0.22rem 0.4rem;
                border-radius: 7px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                text-align: center;
                font-size: 0.68rem;
                font-weight: 800;
                color: #0f172a;
                font-variant-numeric: tabular-nums;
                flex-shrink: 0;
            }

            .wk-list-item {
                padding-bottom: 0.58rem;
                margin-bottom: 0.58rem;
                border-bottom: 1px solid #f1f5f9;
            }

            .wk-list-item:last-child {
                padding-bottom: 0;
                margin-bottom: 0;
                border-bottom: 0;
            }

            .wk-info-subhead {
                font-size: 0.68rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
                margin-bottom: 0.48rem;
                display: flex;
                align-items: center;
                gap: 0.35rem;
            }
        </style>
    @endPushOnce

    @php
        $todayDate = $todayDate ?? \Carbon\Carbon::now();
        $jam = (int) $todayDate->format('H');
        $salam = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));
        $authUser = Auth::user();
        $displayName = $karyawanProfile?->nama_gelar ?? $authUser?->name ?? 'Wali Kelas';
        $isEligible = $isEligible ?? false;
        $assignedClasses = $assignedClasses ?? collect();
        $students = $students ?? collect();
        $studentStats = $studentStats ?? ['total' => 0, 'laki' => 0, 'perempuan' => 0];
        $todayPresensiByAnggota = $todayPresensiByAnggota ?? [];
        $presensiTodaySummary = $presensiTodaySummary ?? ['recorded' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'belum' => 0];
        $presensiMonthSummary = $presensiMonthSummary ?? ['total' => 0, 'hadir_rate' => null, 'hadir_dan_terlambat' => 0];
        $jadwalHariIni = $jadwalHariIni ?? collect();
        $upcomingAgendas = $upcomingAgendas ?? collect();
        $latestPengumuman = $latestPengumuman ?? collect();
        $dashReadiness = $raporReadiness ?? null;
        $isSdDashboard = $isEligible && $selectedKelas
            && (strtoupper(trim((string) $selectedKelas->jenjang_kelas)) === 'SD' || $penilaianSummary !== null);
        $activeDashPeriode = $penilaianSummary['periode'] ?? ($dashReadiness['periode'] ?? 'mid');
        $activeDashPeriodeLabel = $penilaianSummary['periode_label']
            ?? ($activeDashPeriode === 'semester' ? 'Semester' : 'Mid Semester');
    @endphp

    <div class="app-main__inner">
        @include('waliKelas.partials._hero', [
            'heroBadgeText' => $isEligible ? strtoupper((string) $peranDiKelas) : 'DASHBOARD WALI KELAS',
            'heroTitle' => "{$salam}, {$displayName}!",
            'heroActionUrl' => $isEligible && $selectedKelas
                ? route('wali-kelas.perkembangan-siswa.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $activeDashPeriode])
                : null,
            'heroActionLabel' => $isEligible && $selectedKelas ? 'Perkembangan & Rapor' : null,
            'heroActionIcon' => 'fa-chart-line',
        ])

        @if (! $isEligible)
            @include('waliKelas.partials._ineligible_guard')
        @else
            @if ($assignedClasses->count() > 1)
                @include('waliKelas.partials._class_switcher')
            @endif

            <!-- 1. KPI SUMMARY STRIP (4 METRIK UTAMA) -->
            <div class="wk-kpi-grid">
                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #3b82f6;"></span> Siswa Binaan
                        </div>
                        <div class="wk-kpi-value">{{ $studentStats['total'] }}</div>
                        <div class="wk-kpi-sub">
                            {{ $studentStats['laki'] }} Laki-laki &bull; {{ $studentStats['perempuan'] }} Perempuan
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-users"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    @php
                        $hadirTodayCount = $presensiTodaySummary['hadir'] + $presensiTodaySummary['terlambat'];
                    @endphp
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #10b981;"></span> Hadir Hari Ini
                        </div>
                        <div class="wk-kpi-value" style="color: #047857;">
                            {{ $hadirTodayCount }}<span style="font-size: 0.9rem; font-weight: 600; color: #64748b;"> / {{ $studentStats['total'] }}</span>
                        </div>
                        <div class="wk-kpi-sub">
                            Izin {{ $presensiTodaySummary['izin'] }} &bull; Sakit {{ $presensiTodaySummary['sakit'] }} &bull; Alpa {{ $presensiTodaySummary['alpa'] }}
                            @if ($presensiTodaySummary['belum'] > 0)
                                &bull; Belum {{ $presensiTodaySummary['belum'] }}
                            @endif
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa fa-user-check"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #ea580c;"></span> Kehadiran Bulan Ini
                        </div>
                        <div class="wk-kpi-value" style="color: #c2410c;">
                            {{ $presensiMonthSummary['hadir_rate'] !== null ? $presensiMonthSummary['hadir_rate'].'%' : '-' }}
                        </div>
                        <div class="wk-kpi-sub">
                            {{ $presensiMonthSummary['total'] }} catatan presensi bulan {{ $todayDate->copy()->locale('id')->translatedFormat('F') }}
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-chart-pie"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    @if ($dashReadiness !== null && ($dashReadiness['is_sd'] ?? false))
                        @php
                            $kpiReady = (bool) ($dashReadiness['is_ready'] ?? false);
                        @endphp
                        <div>
                            <div class="wk-kpi-label">
                                <span class="wk-kpi-dot" style="background: {{ $kpiReady ? '#10b981' : '#f59e0b' }};"></span>
                                Kesiapan Rapor
                            </div>
                            <div class="wk-kpi-value" style="color: {{ $kpiReady ? '#047857' : '#b45309' }};">
                                {{ $dashReadiness['finalized_count'] }}<span style="font-size: 0.9rem; font-weight: 600; color: #64748b;"> / {{ $dashReadiness['total_components'] }} Final</span>
                            </div>
                            <div class="wk-kpi-sub">
                                Periode {{ $activeDashPeriodeLabel }} &bull; {{ $dashReadiness['progress_pct'] ?? 0 }}% siap cetak
                            </div>
                        </div>
                        <span class="wk-kpi-icon"
                            style="background: {{ $kpiReady ? '#ecfdf5' : '#fffbeb' }}; color: {{ $kpiReady ? '#059669' : '#d97706' }};">
                            <i class="fa {{ $kpiReady ? 'fa-check-double' : 'fa-clipboard-check' }}"></i>
                        </span>
                    @elseif ($penilaianSummary !== null)
                        <div>
                            <div class="wk-kpi-label">
                                <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Progres Penilaian SD
                            </div>
                            <div class="wk-kpi-value" style="color: #6d28d9;">
                                {{ $penilaianSummary['assessed_mapel'] }}<span style="font-size: 0.9rem; font-weight: 600; color: #64748b;"> / {{ $penilaianSummary['total_mapel'] }} Mapel</span>
                            </div>
                            <div class="wk-kpi-sub">
                                Periode {{ $penilaianSummary['periode_label'] }}
                            </div>
                        </div>
                        <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                            <i class="fa fa-clipboard-check"></i>
                        </span>
                    @else
                        <div>
                            <div class="wk-kpi-label">
                                <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Jadwal Pelajaran
                            </div>
                            <div class="wk-kpi-value" style="color: #6d28d9;">
                                {{ $jadwalHariIni->count() }} <span style="font-size: 0.9rem; font-weight: 600; color: #64748b;">Sesi</span>
                            </div>
                            <div class="wk-kpi-sub">
                                {{ $isWeekday ? "Hari {$todayDayLabel}" : 'Akhir Pekan' }} &bull; {{ $jadwalMingguanCount }} total slot/minggu
                            </div>
                        </div>
                        <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                            <i class="fa fa-clock"></i>
                        </span>
                    @endif
                </div>
            </div>

            <!-- 2. DOCK AKSES CEPAT (SIMETRIS: 6 FITUR SD / 4 FITUR NON-SD, TANPA ORPHAN TILE) -->
            @php
                $quickFeatures = [
                    [
                        'title' => 'Input Presensi',
                        'sub' => $presensiTodaySummary['belum'] > 0
                            ? $presensiTodaySummary['belum'].' belum diisi'
                            : 'Hari ini lengkap',
                        'url' => route('wali-kelas.presensi-siswa.bulk', ['kelas_id' => $selectedKelas->sqid]),
                        'icon' => 'fa-clipboard-check',
                        'bg' => '#fff7ed',
                        'color' => '#ea580c',
                    ],
                    ...($isSdDashboard ? [
                        [
                            'title' => 'Nilai TP',
                            'sub' => 'Matriks & sumatif',
                            'url' => route('wali-kelas.penilaian-tp.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $activeDashPeriode]),
                            'icon' => 'fa-edit',
                            'bg' => '#eff6ff',
                            'color' => '#2563eb',
                        ],
                        [
                            'title' => 'Rekap & Deskripsi',
                            'sub' => 'Capaian & finalisasi',
                            'url' => route('wali-kelas.penilaian-rekap.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $activeDashPeriode]),
                            'icon' => 'fa-file-alt',
                            'bg' => '#eef2ff',
                            'color' => '#4f46e5',
                        ],
                    ] : []),
                    [
                        'title' => 'Ekstrakurikuler',
                        'sub' => 'Pramuka & pilihan',
                        'url' => route('wali-kelas.ekstrakurikuler.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $activeDashPeriode]),
                        'icon' => 'fa-medal',
                        'bg' => '#fefce8',
                        'color' => '#ca8a04',
                    ],
                    [
                        'title' => 'Yaumiyah T2Q',
                        'sub' => 'Tahsin & tahfizh',
                        'url' => route('wali-kelas.t2q.yaumiyah-tahsin.matriks', ['kelas_id' => $selectedKelas->sqid]),
                        'icon' => 'fa-quran',
                        'bg' => '#ecfdf5',
                        'color' => '#059669',
                    ],
                    [
                        'title' => 'Leger & Rapor',
                        'sub' => $dashReadiness !== null && ($dashReadiness['is_sd'] ?? false)
                            ? $dashReadiness['finalized_count'].'/'.$dashReadiness['total_components'].' final'
                            : 'Leger & cetak PDF',
                        'url' => route('wali-kelas.perkembangan-siswa.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $activeDashPeriode]),
                        'icon' => 'fa-graduation-cap',
                        'bg' => '#f5f3ff',
                        'color' => '#7c3aed',
                    ],
                ];
            @endphp
            <div class="wk-dock-bar {{ $isSdDashboard ? 'is-sd' : 'is-non-sd' }}" aria-label="Akses Cepat Wali Kelas">
                @foreach ($quickFeatures as $feat)
                    <a href="{{ $feat['url'] }}" class="wk-dock-tile">
                        <span class="wk-dock-icon" style="background: {{ $feat['bg'] }}; color: {{ $feat['color'] }};">
                            <i class="fa {{ $feat['icon'] }}"></i>
                        </span>
                        <span class="wk-dock-meta">
                            <span class="wk-dock-title d-block">{{ $feat['title'] }}</span>
                            <span class="wk-dock-sub d-block">{{ $feat['sub'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <!-- 3. BENTO WORKSPACE SEIMBANG: MAIN WORKSPACE (KIRI) + OPERATIONAL RAIL (KANAN) -->
            <div class="wk-bento-row">
                <!-- KOLOM UTAMA KIRI: WORKSPACE BERTAB (DAFTAR SISWA / PROGRES PENILAIAN TP) -->
                <div>
                    <div class="wk-surface mb-0">
                        <div class="wk-surface-head">
                            @if ($penilaianSummary !== null)
                                <div class="wk-seg-tabs" role="tablist" aria-label="Workspace Kelas">
                                    <button type="button"
                                        class="wk-seg-btn is-active js-wk-dash-tab"
                                        role="tab"
                                        aria-selected="true"
                                        data-wk-dash-tab="siswa">
                                        <i class="fa fa-users"></i>
                                        <span>Siswa &amp; Presensi</span>
                                        <span class="wk-seg-count">{{ $students->count() }}</span>
                                    </button>
                                    <button type="button"
                                        class="wk-seg-btn js-wk-dash-tab"
                                        role="tab"
                                        aria-selected="false"
                                        data-wk-dash-tab="mapel">
                                        <i class="fa fa-chart-bar"></i>
                                        <span>Progres Penilaian TP</span>
                                        <span class="wk-seg-count">{{ $penilaianSummary['assessed_mapel'] }}/{{ $penilaianSummary['total_mapel'] }}</span>
                                    </button>
                                </div>
                            @else
                                <div>
                                    <h6 class="wk-surface-title">
                                        <i class="fa fa-users text-primary mr-1"></i> Daftar Siswa Kelas {{ $selectedKelas->nama_kelas }}
                                    </h6>
                                    <div class="small text-muted">Status kehadiran hari ini &amp; profil siswa</div>
                                </div>
                            @endif

                            <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                                <a href="{{ route('wali-kelas.anggota-kelas.index', ['kelas_id' => $selectedKelas->sqid]) }}"
                                    class="wk-ghost-btn">
                                    <i class="fa fa-id-card"></i> Anggota Kelas
                                </a>
                                <a href="{{ route('wali-kelas.presensi-siswa.bulk', ['kelas_id' => $selectedKelas->sqid]) }}"
                                    class="wk-accent-btn">
                                    <i class="fa fa-clipboard-check"></i> Presensi Massal
                                </a>
                            </div>
                        </div>

                        <!-- TAB PANEL 1: DAFTAR SISWA & PRESENSI HARI INI -->
                        <div id="wkDashPanelSiswa" data-wk-dash-panel="siswa" role="tabpanel">
                            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2"
                                style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                                <div class="small text-muted" style="font-size: 0.72rem;">
                                    Kehadiran hari ini kelas <strong>{{ $selectedKelas->nama_kelas }}</strong>
                                </div>
                                <div class="wk-search-wrap" style="max-width: 240px;">
                                    <i class="fa fa-search"></i>
                                    <input type="search" id="wkStudentSearch" class="wk-search-input"
                                        style="background: #ffffff;"
                                        placeholder="Cari nama / NIS siswa...">
                                </div>
                            </div>

                            <div class="wk-scroll-body" style="max-height: 495px;">
                                <table class="wk-table" id="wkStudentsTable">
                                    <thead>
                                        <tr>
                                            <th class="text-center" style="width: 44px;">No</th>
                                            <th>Siswa</th>
                                            <th class="text-center" style="width: 56px;">L/P</th>
                                            <th class="text-center" style="width: 125px;">Presensi Hari Ini</th>
                                            <th class="text-center" style="width: 78px;">Detail</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($students as $idx => $anggota)
                                            @php
                                                $siswa = $anggota->siswa;
                                                $siswaName = $siswa?->nama_lengkap ?? $anggota->siswa_nis;
                                                $jkRaw = strtoupper(trim((string) ($siswa?->jenis_kelamin ?? '')));
                                                $jkShort = ($jkRaw === 'L' || $jkRaw === 'LAKI-LAKI') ? 'L' : (($jkRaw === 'P' || $jkRaw === 'PEREMPUAN') ? 'P' : '-');
                                                $presensiToday = $todayPresensiByAnggota[$anggota->id] ?? null;
                                                $presensiStatus = strtolower(trim((string) ($presensiToday?->status ?? '')));
                                                $statusStyle = match ($presensiStatus) {
                                                    'hadir' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Hadir'],
                                                    'terlambat' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Terlambat'],
                                                    'izin' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Izin'],
                                                    'sakit' => ['bg' => '#fae8ff', 'color' => '#86198f', 'label' => 'Sakit'],
                                                    'alpa' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Alpa'],
                                                    default => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => 'Belum Tercatat'],
                                                };
                                            @endphp
                                            <tr class="js-wk-student-row"
                                                data-search="{{ mb_strtolower($siswaName.' '.$anggota->siswa_nis.' '.($siswa?->nisn ?? '')) }}">
                                                <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center" style="gap: 0.6rem;">
                                                        @if ($siswa?->avatar_url)
                                                            <img src="{{ $siswa->avatar_url }}" alt="{{ $siswaName }}" class="wk-avatar-thumb" style="width: 32px; height: 32px;">
                                                        @else
                                                            <div class="wk-avatar-fallback" style="width: 32px; height: 32px; font-size: 0.69rem;">
                                                                {{ $siswa?->inisial_nama ?? 'S' }}
                                                            </div>
                                                        @endif
                                                        <div style="min-width: 0;">
                                                            <a href="{{ route('wali-kelas.anggota-kelas.show', $anggota) }}"
                                                                class="font-weight-semibold text-dark text-decoration-none d-block text-truncate"
                                                                style="font-size: 0.81rem;">
                                                                {{ $siswaName }}
                                                            </a>
                                                            <div class="small text-muted" style="font-size: 0.68rem;">
                                                                NIS: {{ $anggota->siswa_nis }}
                                                                @if ($siswa?->nisn)
                                                                    &bull; NISN: {{ $siswa->nisn }}
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge badge-light"
                                                        style="color: {{ $jkShort === 'L' ? '#1d4ed8' : ($jkShort === 'P' ? '#be185d' : '#64748b') }};">
                                                        {{ $jkShort }}
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="wk-badge-status"
                                                        style="background: {{ $statusStyle['bg'] }}; color: {{ $statusStyle['color'] }};">
                                                        {{ $statusStyle['label'] }}
                                                    </span>
                                                    @if ($presensiToday?->jam_masuk)
                                                        <div class="text-muted mt-1" style="font-size: 0.66rem;">
                                                            {{ substr((string) $presensiToday->jam_masuk, 0, 5) }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    <a href="{{ route('wali-kelas.perkembangan-siswa.show', ['anggota_kelas' => $anggota, 'periode' => $activeDashPeriode]) }}"
                                                        class="wk-ghost-btn"
                                                        style="padding: 0.22rem 0.52rem; font-size: 0.68rem;"
                                                        title="Lihat perkembangan {{ $siswaName }}">
                                                        Rapor
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted small">
                                                    Belum ada siswa terdaftar di kelas {{ $selectedKelas->nama_kelas }}.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TAB PANEL 2: PROGRES PENILAIAN TP (SD) -->
                        @if ($penilaianSummary !== null)
                            <div id="wkDashPanelMapel" data-wk-dash-panel="mapel" role="tabpanel" style="display: none;">
                                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2"
                                    style="background: #f8fafc; border-bottom: 1px solid #f1f5f9;">
                                    <div class="small text-muted" style="font-size: 0.72rem;">
                                        Periode <strong>{{ $penilaianSummary['periode_label'] }}</strong> &bull; Tingkat {{ $selectedKelas->tingkatan_kelas }} SD
                                    </div>
                                    <div class="d-flex flex-wrap align-items-center gap-2">
                                        <a href="{{ route('wali-kelas.penilaian-rekap.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $activeDashPeriode]) }}"
                                            class="wk-ghost-btn" style="padding: 0.24rem 0.58rem;">
                                            <i class="fa fa-file-alt"></i> Rekap &amp; Deskripsi
                                        </a>
                                        <a href="{{ route('wali-kelas.penilaian-tp.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $activeDashPeriode]) }}"
                                            class="wk-accent-btn" style="padding: 0.24rem 0.62rem;">
                                            <i class="fa fa-edit"></i> Input Nilai TP
                                        </a>
                                    </div>
                                </div>

                                <div class="wk-scroll-body" style="max-height: 495px;">
                                    <table class="wk-table">
                                        <thead>
                                            <tr>
                                                <th>Mata Pelajaran</th>
                                                <th class="text-center" style="width: 58px;">TP</th>
                                                <th style="width: 170px;">Progres Siswa</th>
                                                <th class="text-center" style="width: 78px;">Rata NA</th>
                                                <th class="text-center" style="width: 72px;">Tuntas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($penilaianSummary['mapels'] as $mp)
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-light font-weight-bold mr-1" style="color: #c2410c; background: #fff7ed;">
                                                            {{ $mp['singkatan'] }}
                                                        </span>
                                                        <span class="font-weight-semibold text-dark">{{ $mp['nama'] }}</span>
                                                    </td>
                                                    <td class="text-center text-muted">{{ $mp['tp_count'] }}</td>
                                                    <td>
                                                        <div class="d-flex justify-content-between small mb-1" style="font-size: 0.69rem;">
                                                            <span class="font-weight-semibold">{{ $mp['dinilai_count'] }} / {{ $studentStats['total'] }}</span>
                                                            <span class="text-muted">{{ $mp['progress_pct'] }}%</span>
                                                        </div>
                                                        <div class="wk-progress-track" style="height: 6px;">
                                                            <div class="wk-progress-fill" style="width: {{ $mp['progress_pct'] }}%;"></div>
                                                        </div>
                                                    </td>
                                                    <td class="text-center font-weight-bold" style="color: #0f172a;">
                                                        {{ $mp['avg_rapor'] !== null ? $mp['avg_rapor'] : '-' }}
                                                    </td>
                                                    <td class="text-center">
                                                        @if ($mp['dinilai_count'] > 0)
                                                            <span class="wk-pill-chip" style="background: #dcfce7; color: #166534;">{{ $mp['tuntas_count'] }}</span>
                                                        @else
                                                            <span class="text-muted">-</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="5" class="text-center py-4 text-muted small">
                                                        Belum ada mata pelajaran SD aktif.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- KOLOM KANAN: KESIAPAN RAPOR, JADWAL HARI INI & INFORMASI SEKOLAH -->
                <div class="d-flex flex-column" style="gap: 1rem;">
                    @if ($dashReadiness !== null && ($dashReadiness['is_sd'] ?? false))
                        @php
                            $rdReady = (bool) ($dashReadiness['is_ready'] ?? false);
                            $rdPct = (int) ($dashReadiness['progress_pct'] ?? 0);
                            $rdPending = collect($dashReadiness['pending_components'] ?? []);
                            $perkembanganUrl = route('wali-kelas.perkembangan-siswa.index', [
                                'kelas_id' => $selectedKelas->sqid,
                                'periode' => $activeDashPeriode,
                            ]);
                        @endphp
                        <div class="wk-surface mb-0">
                            <div class="wk-surface-head">
                                <div>
                                    <h6 class="wk-surface-title">
                                        <i class="fa {{ $rdReady ? 'fa-shield-alt text-success' : 'fa-clipboard-check text-warning' }} mr-1"></i>
                                        Kesiapan &amp; Finalisasi Rapor
                                    </h6>
                                    <div class="small text-muted">Periode {{ $activeDashPeriodeLabel }}</div>
                                </div>
                                <a href="{{ $perkembanganUrl }}" class="wk-ghost-btn">
                                    Kelola <i class="fa fa-arrow-right"></i>
                                </a>
                            </div>

                            <div class="p-3">
                                <div class="d-flex align-items-center justify-content-between small mb-1" style="font-size: 0.72rem;">
                                    <span class="font-weight-bold text-dark">
                                        {{ $dashReadiness['finalized_count'] }}/{{ $dashReadiness['total_components'] }} Komponen Final
                                    </span>
                                    <span class="font-weight-bold" style="color: {{ $rdReady ? '#047857' : '#c2410c' }};">{{ $rdPct }}%</span>
                                </div>
                                <div class="wk-progress-track mb-2" style="height: 6px;">
                                    <div class="wk-progress-fill"
                                        style="width: {{ $rdPct }}%; background: {{ $rdReady ? 'linear-gradient(90deg, #059669 0%, #10b981 100%)' : 'linear-gradient(90deg, #ea580c 0%, #f59e0b 100%)' }};">
                                    </div>
                                </div>

                                @if ($rdReady)
                                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-2 rounded mt-2"
                                        style="background: #ecfdf5; border: 1px solid #a7f3d0;">
                                        <span class="small font-weight-semibold" style="color: #065f46; font-size: 0.74rem;">
                                            <i class="fa fa-check-circle mr-1"></i> Rapor siap dicetak
                                        </span>
                                        <div class="d-flex align-items-center gap-1">
                                            <a href="{{ route('wali-kelas.rapor.identitas.kelas', $selectedKelas) }}" target="_blank" class="wk-ghost-btn" style="padding: 0.22rem 0.5rem;">
                                                Identitas
                                            </a>
                                            <a href="{{ route('wali-kelas.rapor.nilai.kelas', ['kelas' => $selectedKelas, 'periode' => $activeDashPeriode]) }}" target="_blank" class="wk-accent-btn" style="padding: 0.24rem 0.58rem;">
                                                <i class="fa fa-file-pdf"></i> Cetak Nilai
                                            </a>
                                        </div>
                                    </div>
                                @else
                                    <div class="d-flex flex-column mt-2" style="gap: 0.38rem;">
                                        @foreach ($rdPending->take(3) as $comp)
                                            @php
                                                $compJenis = $comp['jenis_komponen'] ?? '';
                                                $compActionUrl = match ($compJenis) {
                                                    'ekskul_wajib' => route('wali-kelas.ekstrakurikuler.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $activeDashPeriode]),
                                                    'presensi' => route('wali-kelas.presensi-siswa.index', ['kelas_id' => $selectedKelas->sqid]),
                                                    'mapel' => ($comp['is_owned_by_user'] ?? false)
                                                        ? route('wali-kelas.penilaian-tp.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $activeDashPeriode])
                                                        : $perkembanganUrl,
                                                    default => $perkembanganUrl,
                                                };
                                            @endphp
                                            <div class="wk-readiness-item">
                                                <div style="min-width: 0;">
                                                    <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.75rem;">
                                                        {{ $comp['nama'] ?? 'Komponen' }}
                                                    </div>
                                                    <div class="text-muted text-truncate" style="font-size: 0.67rem;">
                                                        {{ $comp['owner_label'] ?? '-' }}
                                                    </div>
                                                </div>
                                                <a href="{{ $compActionUrl }}" class="wk-ghost-btn flex-shrink-0" style="padding: 0.2rem 0.5rem; font-size: 0.66rem;">
                                                    {{ ($comp['is_owned_by_user'] ?? false) ? 'Kelola' : 'Lihat' }}
                                                </a>
                                            </div>
                                        @endforeach
                                        @if ($rdPending->count() > 3)
                                            <a href="{{ $perkembanganUrl }}" class="small font-weight-semibold text-decoration-none text-right" style="color: #c2410c; font-size: 0.7rem;">
                                                +{{ $rdPending->count() - 3 }} lainnya di Kesiapan Rapor &rarr;
                                            </a>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif

                    <!-- JADWAL PELAJARAN HARI INI -->
                    <div class="wk-surface mb-0">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-calendar-day text-info mr-1"></i> Jadwal Hari Ini
                                </h6>
                                <div class="small text-muted">{{ $todayDayLabel }}, {{ $todayDate->copy()->locale('id')->translatedFormat('d M Y') }}</div>
                            </div>
                            <a href="{{ route('wali-kelas.jadwal-pelajaran.index', ['kelas_id' => $selectedKelas->sqid]) }}"
                                class="wk-ghost-btn">
                                Mingguan ({{ $jadwalHariIni->count() }})
                            </a>
                        </div>
                        <div class="p-3">
                            @if (! $isWeekday)
                                <div class="text-center py-2 text-muted small">
                                    <i class="fa fa-coffee mr-1" style="color: #94a3b8;"></i>
                                    Akhir pekan ({{ $jadwalMingguanCount }} sesi aktif Senin–Jumat).
                                </div>
                            @elseif ($jadwalHariIni->isEmpty())
                                <div class="text-center py-2 text-muted small">
                                    Belum ada jadwal tercatat untuk hari {{ $todayDayLabel }}.
                                </div>
                            @else
                                <div class="d-flex flex-column" style="gap: 0.42rem;">
                                    @foreach ($jadwalHariIni->take(4) as $slot)
                                        <div class="wk-timeline-item">
                                            <div class="wk-timeline-time">
                                                <div>Jam {{ $slot->jamPelajaran?->jam_ke ?? '-' }}</div>
                                                <div style="font-size: 0.6rem; font-weight: 600; color: #64748b;">
                                                    {{ $slot->jamPelajaran?->rentang_waktu ?? '-' }}
                                                </div>
                                            </div>
                                            <div style="min-width: 0; flex: 1 1 auto;">
                                                <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.78rem;">
                                                    {{ $slot->judul_tampil }}
                                                </div>
                                                <div class="small text-muted text-truncate" style="font-size: 0.68rem;">
                                                    {{ $slot->guru?->nama_gelar ?? 'Guru pengampu belum ditetapkan' }}
                                                </div>
                                            </div>
                                            @if ($slot->ruang)
                                                <span class="badge badge-light flex-shrink-0" style="border: 1px solid #e2e8f0; color: #475569; font-size: 0.64rem;">
                                                    {{ $slot->ruang }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- INFORMASI SEKOLAH (AGENDA & PENGUMUMAN TERPADU) -->
                    <div class="wk-surface mb-0">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-bullhorn text-danger mr-1"></i> Agenda &amp; Pengumuman
                                </h6>
                                <div class="small text-muted">Tahun Ajaran {{ $activeTahunAjaran->full_label }}</div>
                            </div>
                        </div>
                        <div class="p-3">
                            <div class="wk-info-subhead">
                                <i class="fa fa-calendar-check text-success"></i> Agenda Sekolah
                            </div>
                            @forelse ($upcomingAgendas->take(3) as $agenda)
                                <div class="wk-list-item d-flex align-items-start justify-content-between gap-2">
                                    <div style="min-width: 0;">
                                        <div class="font-weight-semibold text-dark text-truncate" style="font-size: 0.78rem;">
                                            {{ $agenda->nama_agenda }}
                                        </div>
                                        <div class="small text-muted" style="font-size: 0.68rem;">
                                            {{ $agenda->tanggal_formatted }} &bull; {{ $agenda->jam_formatted }}
                                        </div>
                                    </div>
                                    <span class="wk-pill-chip flex-shrink-0" style="background: #f1f5f9; color: #475569; font-size: 0.64rem;">
                                        {{ $agenda->unit }}
                                    </span>
                                </div>
                            @empty
                                <div class="text-muted small mb-2">Belum ada agenda sekolah terdekat.</div>
                            @endforelse

                            <div class="wk-info-subhead mt-3 pt-2" style="border-top: 1px dashed #e2e8f0;">
                                <i class="fa fa-newspaper text-warning"></i> Pengumuman Terbaru
                            </div>
                            @forelse ($latestPengumuman->take(2) as $info)
                                <div class="wk-list-item">
                                    <div class="d-flex align-items-center justify-content-between gap-2">
                                        <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.78rem;">
                                            {{ $info->judul }}
                                        </div>
                                        <span class="text-muted flex-shrink-0" style="font-size: 0.64rem;">
                                            {{ $info->tanggal_dibuat_formatted }}
                                        </span>
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.71rem; line-height: 1.4;">
                                        {{ $info->ringkasan_isi }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-muted small">Belum ada pengumuman terbaru.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabBtns = document.querySelectorAll('.js-wk-dash-tab[data-wk-dash-tab]');
            const tabPanels = document.querySelectorAll('[data-wk-dash-panel]');

            tabBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const target = btn.dataset.wkDashTab;
                    tabPanels.forEach(function (panel) {
                        panel.style.display = panel.dataset.wkDashPanel === target ? '' : 'none';
                    });
                    tabBtns.forEach(function (b) {
                        const active = b.dataset.wkDashTab === target;
                        b.classList.toggle('is-active', active);
                        b.setAttribute('aria-selected', active ? 'true' : 'false');
                    });
                });
            });

            const searchInput = document.getElementById('wkStudentSearch');
            const rows = document.querySelectorAll('.js-wk-student-row');
            if (!searchInput || !rows.length) {
                return;
            }

            searchInput.addEventListener('input', function () {
                const q = searchInput.value.trim().toLowerCase();
                rows.forEach(function (tr) {
                    const hay = tr.dataset.search || '';
                    tr.style.display = q === '' || hay.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        });
    </script>
@endpush
