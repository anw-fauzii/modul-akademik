@extends('layouts.appui')

@section('title')
    <title>Dashboard Guru Mapel SD - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @pushOnce('css')
        <style>
            .gm-dock-bar {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.6rem;
                margin-bottom: 1.05rem;
            }

            @media (min-width: 640px) {
                .gm-dock-bar {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (min-width: 1100px) {
                .gm-dock-bar {
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                }
            }

            .gm-dock-tile {
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

            .gm-dock-tile:hover {
                transform: translateY(-2px);
                border-color: #fdba74;
                box-shadow: 0 10px 20px -8px rgba(15, 23, 42, 0.1);
            }

            .gm-dock-icon {
                width: 34px;
                height: 34px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.86rem;
                flex-shrink: 0;
            }

            .gm-dock-meta {
                min-width: 0;
                flex: 1 1 auto;
            }

            .gm-dock-title {
                font-size: 0.75rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.25;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .gm-dock-sub {
                font-size: 0.66rem;
                color: #64748b;
                line-height: 1.25;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-top: 0.1rem;
            }

            .gm-bento-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
                align-items: start;
            }

            @media (min-width: 1024px) {
                .gm-bento-row {
                    grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr);
                }
            }

            .gm-ghost-btn {
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

            .gm-ghost-btn:hover {
                background: #ffffff;
                border-color: #cbd5e1;
                color: #0f172a;
            }

            .gm-accent-btn {
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

            .gm-pill-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.26rem;
                padding: 0.16rem 0.52rem;
                border-radius: 999px;
                font-size: 0.67rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .gm-timeline-item {
                display: flex;
                align-items: center;
                gap: 0.65rem;
                padding: 0.52rem 0.68rem;
                border-radius: 10px;
                background: #f8fafc;
                border: 1px solid #f1f5f9;
            }

            .gm-timeline-time {
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

            .gm-list-item {
                padding-bottom: 0.58rem;
                margin-bottom: 0.58rem;
                border-bottom: 1px solid #f1f5f9;
            }

            .gm-list-item:last-child {
                padding-bottom: 0;
                margin-bottom: 0;
                border-bottom: 0;
            }

            .gm-info-subhead {
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
        $displayName = $karyawanProfile?->nama_gelar ?? $authUser?->name ?? 'Guru Mapel SD';
        $isEligible = $isEligible ?? false;
        $taughtClasses = $taughtClasses ?? collect();
        $taughtAssignments = $taughtAssignments ?? collect();
        $assignedEkskuls = $assignedEkskuls ?? collect();
        $jadwalHariIni = $jadwalHariIni ?? collect();
        $jadwalMingguanCount = $jadwalMingguanCount ?? 0;
        $upcomingAgendas = $upcomingAgendas ?? collect();
        $latestPengumuman = $latestPengumuman ?? collect();
        $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $periodeLabel = $periodeLabel ?? 'Mid Semester';
        $stats = $stats ?? [
            'total_classes' => 0,
            'total_mapels' => 0,
            'total_students' => 0,
            'total_assignments' => 0,
            'final_assignments' => 0,
            'draft_assignments' => 0,
            'ekskul_count' => 0,
        ];
    @endphp

    <div class="app-main__inner">
        <div class="wk-hero">
            <div class="wk-hero-glow"></div>
            <div class="row align-items-center position-relative" style="z-index: 1;">
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge text-white px-2 py-1 font-weight-bold"
                            style="background: rgba(234, 88, 12, 0.92); font-size: 11px; border-radius: 6px;">
                            <i class="fa fa-chalkboard-teacher mr-1"></i> GURU MAPEL SD
                        </span>
                        <span class="small" style="color: rgba(255, 255, 255, 0.72);">
                            <i class="fa fa-calendar-alt mr-1"></i>
                            {{ $todayDate->copy()->locale('id')->translatedFormat('l, d F Y') }}
                        </span>
                    </div>

                    <h3 class="font-weight-bold text-white mb-2">
                        {{ $salam }}, {{ $displayName }}!
                    </h3>

                    <p class="mb-3" style="color: rgba(255, 255, 255, 0.78); max-width: 640px; font-size: 0.86rem; line-height: 1.5;">
                        @if ($isEligible)
                            Panel khusus pengampu mata pelajaran unit <strong>SD</strong>: pantau jadwal mengajar pribadi, input nilai TP &amp; deskripsi capaian, finalisasi mapel, serta kelola ekstrakurikuler pilihan yang Anda bina.
                        @else
                            Panel Guru Mapel SD terhubung otomatis dengan penugasan Jadwal Pelajaran &amp; Ekstrakurikuler pada Tahun Ajaran aktif.
                        @endif
                    </p>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($activeTahunAjaran)
                            <span class="badge px-3 py-2"
                                style="background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.22); color: #ffffff; font-size: 0.74rem;">
                                <i class="fa fa-graduation-cap text-warning mr-1"></i>
                                {{ $activeTahunAjaran->full_label }}
                            </span>
                        @endif
                        @if ($isEligible)
                            <span class="badge px-3 py-2"
                                style="background: rgba(16, 185, 129, 0.22); border: 1px solid rgba(52, 211, 153, 0.35); color: #ecfdf5; font-size: 0.74rem;">
                                <i class="fa fa-book-open mr-1"></i>
                                {{ $stats['total_classes'] }} Kelas &bull; {{ $stats['total_mapels'] }} Mapel
                            </span>
                            <a href="{{ route('guru-mapel.jadwal-pelajaran.index') }}" class="badge px-3 py-2 text-decoration-none"
                                style="background: rgba(234, 88, 12, 0.9); border: 1px solid rgba(251, 146, 60, 0.65); color: #ffffff; font-size: 0.74rem;">
                                <i class="fa fa-calendar-alt mr-1"></i> Lihat Jadwal Mengajar
                            </a>
                        @endif
                    </div>
                </div>

                @if ($isEligible)
                    <div class="col-lg-4">
                        <div class="wk-team-card">
                            <div class="text-uppercase font-weight-bold mb-2"
                                style="font-size: 0.68rem; letter-spacing: 0.05em; color: #fb923c;">
                                Ringkasan Tugas Mengajar ({{ $periodeLabel }})
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.78rem;">
                                <span style="color: rgba(255, 255, 255, 0.72);">Finalisasi Nilai Mapel</span>
                                <span class="font-weight-bold text-white">{{ $stats['final_assignments'] }} / {{ $stats['total_assignments'] }} Final</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.78rem;">
                                <span style="color: rgba(255, 255, 255, 0.72);">Slot Mengajar Mingguan</span>
                                <span class="font-weight-bold text-white">{{ $jadwalMingguanCount }} Sesi</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" style="font-size: 0.78rem;">
                                <span style="color: rgba(255, 255, 255, 0.72);">Ekstrakurikuler Binaan</span>
                                <span class="font-weight-bold text-white">{{ $stats['ekskul_count'] }} Ekskul</span>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if (! $isEligible)
            <div class="wk-empty-guard">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                    style="width: 58px; height: 58px; border-radius: 16px; background: #fff7ed; color: #ea580c; font-size: 1.5rem;">
                    <i class="fa fa-chalkboard-teacher"></i>
                </div>
                <h5 class="font-weight-bold text-dark mb-2">
                    Belum Ada Penugasan Jadwal Mengajar SD Aktif
                </h5>
                @if ($ineligibleReason === 'no_active_tahun_ajaran')
                    <p class="small text-muted mb-3">
                        Saat ini belum ada <strong>Tahun Ajaran &amp; Semester aktif</strong> di sistem.
                    </p>
                @elseif ($ineligibleReason === 'missing_nipy')
                    <p class="small text-muted mb-3">
                        Akun Anda belum memiliki <strong>NIPY / Nomor Induk</strong> yang terhubung ke profil Karyawan.
                    </p>
                @else
                    <p class="small text-muted mb-3">
                        Pada Tahun Ajaran aktif (<strong>{{ $activeTahunAjaran?->full_label }}</strong>), NIPY Anda (<code>{{ $nipy }}</code>) belum tercatat pada <strong>Jadwal Pelajaran SD</strong> maupun pembina <strong>Ekstrakurikuler Pilihan</strong>.
                    </p>
                @endif
                <div class="small text-muted px-3 py-2 rounded d-inline-block"
                    style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <i class="fa fa-info-circle text-warning mr-1"></i>
                    Hubungi Admin Akademik apabila jadwal mengajar SD Anda perlu diperbarui.
                </div>
            </div>
        @else
            <!-- 1. KPI STRIP -->
            <div class="wk-kpi-grid">
                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #2563eb;"></span> Kelas &amp; Mapel SD
                        </div>
                        <div class="wk-kpi-value">{{ $stats['total_classes'] }} <span style="font-size: 0.9rem; font-weight: 600; color: #64748b;">Kelas</span></div>
                        <div class="wk-kpi-sub">{{ $stats['total_mapels'] }} Mata pelajaran &bull; {{ $stats['total_students'] }} siswa</div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-school"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #ea580c;"></span> Mengajar Hari Ini
                        </div>
                        <div class="wk-kpi-value" style="color: #c2410c;">{{ $jadwalHariIni->count() }} <span style="font-size: 0.9rem; font-weight: 600; color: #64748b;">Sesi</span></div>
                        <div class="wk-kpi-sub">{{ $isWeekday ? "Hari {$todayDayLabel}" : 'Akhir Pekan' }} &bull; {{ $jadwalMingguanCount }} slot/minggu</div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-clock"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    @php
                        $allMapelFinal = $stats['total_assignments'] > 0 && $stats['draft_assignments'] === 0;
                    @endphp
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: {{ $allMapelFinal ? '#10b981' : '#f59e0b' }};"></span> Finalisasi Mapel
                        </div>
                        <div class="wk-kpi-value" style="color: {{ $allMapelFinal ? '#047857' : '#b45309' }};">
                            {{ $stats['final_assignments'] }}<span style="font-size: 0.9rem; font-weight: 600; color: #64748b;"> / {{ $stats['total_assignments'] }} Final</span>
                        </div>
                        <div class="wk-kpi-sub">Periode {{ $periodeLabel }} &bull; {{ $stats['draft_assignments'] }} draft</div>
                    </div>
                    <span class="wk-kpi-icon" style="background: {{ $allMapelFinal ? '#ecfdf5' : '#fffbeb' }}; color: {{ $allMapelFinal ? '#059669' : '#d97706' }};">
                        <i class="fa {{ $allMapelFinal ? 'fa-lock' : 'fa-clipboard-check' }}"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Ekskul Pilihan
                        </div>
                        <div class="wk-kpi-value" style="color: #6d28d9;">{{ $stats['ekskul_count'] }}</div>
                        <div class="wk-kpi-sub">
                            {{ $stats['ekskul_count'] > 0 ? 'Ekskul binaan aktif Anda' : 'Tidak membina ekskul pilihan' }}
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                        <i class="fa fa-medal"></i>
                    </span>
                </div>
            </div>

            <!-- 2. DOCK AKSES CEPAT (5 MODUL GURU MAPEL SD) -->
            @php
                $dockItems = [
                    [
                        'title' => 'Jadwal Saya',
                        'sub' => $jadwalMingguanCount.' sesi aktif',
                        'url' => route('guru-mapel.jadwal-pelajaran.index'),
                        'icon' => 'fa-calendar-alt',
                        'bg' => '#fff7ed',
                        'color' => '#ea580c',
                    ],
                    [
                        'title' => 'Input Nilai TP',
                        'sub' => 'Matriks aspek & sumatif',
                        'url' => route('guru-mapel.penilaian-tp.index'),
                        'icon' => 'fa-edit',
                        'bg' => '#eff6ff',
                        'color' => '#2563eb',
                    ],
                    [
                        'title' => 'Rekap & Deskripsi',
                        'sub' => 'Capaian & finalisasi',
                        'url' => route('guru-mapel.penilaian-rekap.index'),
                        'icon' => 'fa-file-alt',
                        'bg' => '#eef2ff',
                        'color' => '#4f46e5',
                    ],
                    [
                        'title' => 'Perkembangan Siswa',
                        'sub' => 'Capaian mapel saya',
                        'url' => route('guru-mapel.perkembangan-siswa.index'),
                        'icon' => 'fa-chart-line',
                        'bg' => '#ecfdf5',
                        'color' => '#059669',
                    ],
                    [
                        'title' => 'Ekstrakurikuler',
                        'sub' => $stats['ekskul_count'] > 0 ? $stats['ekskul_count'].' ekskul binaan' : 'Ekskul pilihan',
                        'url' => route('guru-mapel.ekstrakurikuler.index'),
                        'icon' => 'fa-medal',
                        'bg' => '#fefce8',
                        'color' => '#ca8a04',
                    ],
                ];
            @endphp
            <div class="gm-dock-bar" aria-label="Akses Cepat Guru Mapel SD">
                @foreach ($dockItems as $item)
                    <a href="{{ $item['url'] }}" class="gm-dock-tile">
                        <span class="gm-dock-icon" style="background: {{ $item['bg'] }}; color: {{ $item['color'] }};">
                            <i class="fa {{ $item['icon'] }}"></i>
                        </span>
                        <span class="gm-dock-meta">
                            <span class="gm-dock-title d-block">{{ $item['title'] }}</span>
                            <span class="gm-dock-sub d-block">{{ $item['sub'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <!-- 3. BENTO WORKSPACE SEIMBANG -->
            <div class="gm-bento-row">
                <!-- KIRI: DAFTAR KELAS & MAPEL DIAMPU + EKSKUL BINAAN -->
                <div class="d-flex flex-column" style="gap: 1rem;">
                    <div class="wk-surface mb-0">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-book-open text-warning mr-1"></i>
                                    Kelas &amp; Mata Pelajaran Saya ({{ $periodeLabel }})
                                </h6>
                                <div class="small text-muted">Progres pengisian &amp; finalisasi nilai khusus kelas yang Anda ajar</div>
                            </div>
                            <span class="gm-pill-chip" style="background: #f1f5f9; color: #334155;">
                                {{ $stats['final_assignments'] }}/{{ $stats['total_assignments'] }} Final
                            </span>
                        </div>

                        <div class="wk-scroll-body" style="max-height: 460px;">
                            <table class="wk-table" id="gmAssignmentsTable">
                                <thead>
                                    <tr>
                                        <th>Kelas &amp; Mapel</th>
                                        <th class="text-center" style="width: 55px;">TP</th>
                                        <th style="width: 145px;">Progres Siswa</th>
                                        <th class="text-center" style="width: 72px;">Rata NA</th>
                                        <th class="text-center" style="width: 82px;">Status</th>
                                        <th class="text-center" style="width: 132px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($taughtAssignments as $asg)
                                        @php
                                            $k = $asg['kelas'];
                                            $m = $asg['mapel'];
                                            $qParams = [
                                                'kelas_id' => $k->sqid,
                                                'mata_pelajaran_id' => $m->sqid,
                                                'periode' => $periode,
                                            ];
                                        @endphp
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap: 0.4rem;">
                                                    <span class="badge badge-light font-weight-bold" style="background: #fff7ed; color: #c2410c;">
                                                        {{ $k->nama_kelas }}
                                                    </span>
                                                    <span class="font-weight-bold text-dark">{{ $m->nama_mata_pelajaran }}</span>
                                                </div>
                                                <div class="small text-muted" style="font-size: 0.68rem;">
                                                    {{ $m->singkatan ?: 'MP' }} &bull; {{ $asg['total_siswa'] }} siswa
                                                </div>
                                            </td>
                                            <td class="text-center text-muted">{{ $asg['tp_count'] }}</td>
                                            <td>
                                                <div class="d-flex justify-content-between small mb-1" style="font-size: 0.68rem;">
                                                    <span class="font-weight-semibold">{{ $asg['dinilai_count'] }}/{{ $asg['total_siswa'] }}</span>
                                                    <span class="text-muted">{{ $asg['progress_pct'] }}%</span>
                                                </div>
                                                <div class="wk-progress-track" style="height: 6px;">
                                                    <div class="wk-progress-fill" style="width: {{ $asg['progress_pct'] }}%;"></div>
                                                </div>
                                            </td>
                                            <td class="text-center font-weight-bold text-dark">
                                                {{ $asg['avg_rapor'] !== null ? number_format((float) $asg['avg_rapor'], 1) : '-' }}
                                            </td>
                                            <td class="text-center">
                                                <span class="gm-pill-chip"
                                                    style="background: {{ $asg['is_final'] ? '#d1fae5' : '#fef3c7' }}; color: {{ $asg['is_final'] ? '#047857' : '#b45309' }};">
                                                    <i class="fa {{ $asg['is_final'] ? 'fa-lock' : 'fa-clock' }}"></i>
                                                    {{ $asg['is_final'] ? 'Final' : 'Draft' }}
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="d-inline-flex align-items-center" style="gap: 0.3rem;">
                                                    <a href="{{ route('guru-mapel.penilaian-tp.index', $qParams) }}" class="gm-accent-btn">
                                                        Nilai TP
                                                    </a>
                                                    <a href="{{ route('guru-mapel.penilaian-rekap.index', $qParams) }}" class="gm-ghost-btn">
                                                        Rekap
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center py-4 text-muted small">
                                                Belum ada mata pelajaran SD pada jadwal mengajar Anda.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($assignedEkskuls->isNotEmpty())
                        <div class="wk-surface mb-0">
                            <div class="wk-surface-head">
                                <div>
                                    <h6 class="wk-surface-title">
                                        <i class="fa fa-medal text-warning mr-1"></i> Ekstrakurikuler Pilihan Binaan Saya
                                    </h6>
                                    <div class="small text-muted">Kelola nilai &amp; finalisasi peserta ekskul pilihan</div>
                                </div>
                                <a href="{{ route('guru-mapel.ekstrakurikuler.index') }}" class="gm-ghost-btn">
                                    Kelola Nilai <i class="fa fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="p-3 d-flex flex-wrap" style="gap: 0.5rem;">
                                @foreach ($assignedEkskuls as $eks)
                                    <a href="{{ route('guru-mapel.ekstrakurikuler.index', ['mode' => 'pilihan', 'ekstrakurikuler_id' => $eks->sqid, 'periode' => $periode]) }}"
                                        class="gm-ghost-btn" style="padding: 0.45rem 0.78rem;">
                                        <i class="fa fa-medal text-warning"></i>
                                        <span>{{ $eks->nama_ekstrakurikuler }}</span>
                                        <span class="gm-pill-chip" style="background: #ffedd5; color: #c2410c;">
                                            {{ $eks->anggota_ekstrakurikuler_count ?? 0 }} Peserta
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- KANAN: JADWAL MENGAJAR HARI INI + AGENDA & PENGUMUMAN -->
                <div class="d-flex flex-column" style="gap: 1rem;">
                    <div class="wk-surface mb-0">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-calendar-day text-info mr-1"></i> Jadwal Mengajar Hari Ini
                                </h6>
                                <div class="small text-muted">{{ $todayDayLabel }}, {{ $todayDate->copy()->locale('id')->translatedFormat('d M Y') }}</div>
                            </div>
                            <a href="{{ route('guru-mapel.jadwal-pelajaran.index') }}" class="gm-ghost-btn">
                                Mingguan ({{ $jadwalHariIni->count() }})
                            </a>
                        </div>
                        <div class="p-3">
                            @if (! $isWeekday)
                                <div class="text-center py-2 text-muted small">
                                    <i class="fa fa-coffee mr-1" style="color: #94a3b8;"></i>
                                    Akhir pekan ({{ $jadwalMingguanCount }} sesi mengajar aktif Senin–Jumat).
                                </div>
                            @elseif ($jadwalHariIni->isEmpty())
                                <div class="text-center py-2 text-muted small">
                                    Tidak ada jadwal mengajar Anda pada hari {{ $todayDayLabel }}.
                                </div>
                            @else
                                <div class="d-flex flex-column" style="gap: 0.42rem;">
                                    @foreach ($jadwalHariIni as $slot)
                                        <div class="gm-timeline-item">
                                            <div class="gm-timeline-time">
                                                <div>Jam {{ $slot->jamPelajaran?->jam_ke ?? '-' }}</div>
                                                <div style="font-size: 0.6rem; font-weight: 600; color: #64748b;">
                                                    {{ $slot->jamPelajaran?->rentang_waktu ?? '-' }}
                                                </div>
                                            </div>
                                            <div style="min-width: 0; flex: 1 1 auto;">
                                                <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.78rem;">
                                                    {{ $slot->mataPelajaran?->nama_mata_pelajaran ?? $slot->judul_tampil }}
                                                </div>
                                                <div class="small text-muted text-truncate" style="font-size: 0.68rem;">
                                                    Kelas <strong class="text-dark">{{ $slot->kelas?->nama_kelas ?? '-' }}</strong>
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
                            <div class="gm-info-subhead">
                                <i class="fa fa-calendar-check text-success"></i> Agenda Sekolah
                            </div>
                            @forelse ($upcomingAgendas->take(3) as $agenda)
                                <div class="gm-list-item d-flex align-items-start justify-content-between gap-2">
                                    <div style="min-width: 0;">
                                        <div class="font-weight-semibold text-dark text-truncate" style="font-size: 0.78rem;">
                                            {{ $agenda->nama_agenda }}
                                        </div>
                                        <div class="small text-muted" style="font-size: 0.68rem;">
                                            {{ $agenda->tanggal_formatted }} &bull; {{ $agenda->jam_formatted }}
                                        </div>
                                    </div>
                                    <span class="gm-pill-chip flex-shrink-0" style="background: #f1f5f9; color: #475569; font-size: 0.64rem;">
                                        {{ $agenda->unit }}
                                    </span>
                                </div>
                            @empty
                                <div class="text-muted small mb-2">Belum ada agenda sekolah terdekat.</div>
                            @endforelse

                            <div class="gm-info-subhead mt-3 pt-2" style="border-top: 1px dashed #e2e8f0;">
                                <i class="fa fa-newspaper text-warning"></i> Pengumuman Terbaru
                            </div>
                            @forelse ($latestPengumuman->take(2) as $info)
                                <div class="gm-list-item">
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

