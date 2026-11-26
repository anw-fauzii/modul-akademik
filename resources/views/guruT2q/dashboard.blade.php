@extends('layouts.appui')

@section('title')
    <title>Dashboard Guru T2Q - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @pushOnce('css')
        <style>
            .gt2q-dock-bar {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.6rem;
                margin-bottom: 1.05rem;
            }

            @media (min-width: 768px) {
                .gt2q-dock-bar {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            @media (min-width: 1340px) {
                .gt2q-dock-bar {
                    grid-template-columns: repeat(7, minmax(0, 1fr));
                }
            }

            .gt2q-dock-tile {
                display: flex;
                align-items: center;
                gap: 0.65rem;
                padding: 0.68rem 0.8rem;
                border-radius: 13px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
                text-decoration: none !important;
                color: #0f172a;
                transition: transform 0.14s ease, border-color 0.14s ease, box-shadow 0.14s ease;
                min-width: 0;
            }

            .gt2q-dock-tile:hover {
                transform: translateY(-2px);
                border-color: #fdba74;
                box-shadow: 0 8px 18px -6px rgba(234, 88, 12, 0.16);
                color: #0f172a;
            }

            .gt2q-dock-icon {
                width: 34px;
                height: 34px;
                border-radius: 9px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.85rem;
                flex-shrink: 0;
            }

            .gt2q-dock-meta {
                min-width: 0;
                line-height: 1.2;
            }

            .gt2q-dock-title {
                font-size: 0.77rem;
                font-weight: 700;
                color: #0f172a;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .gt2q-dock-sub {
                font-size: 0.67rem;
                color: #64748b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                margin-top: 0.12rem;
            }

            .gt2q-bento-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 1rem;
                align-items: start;
            }

            @media (min-width: 1100px) {
                .gt2q-bento-row {
                    grid-template-columns: minmax(0, 1.55fr) minmax(300px, 1fr);
                }
            }

            .gt2q-tingkat-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 0.65rem;
            }

            @media (min-width: 640px) {
                .gt2q-tingkat-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            .gt2q-tingkat-card {
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.78rem 0.9rem;
                background: #fcfcfd;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .gt2q-tingkat-card:hover {
                border-color: #fed7aa;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
                background: #ffffff;
            }

            .gt2q-pill-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                padding: 0.16rem 0.52rem;
                border-radius: 999px;
                font-size: 0.66rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .gt2q-accent-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.24rem 0.58rem;
                border-radius: 7px;
                font-size: 0.69rem;
                font-weight: 700;
                text-decoration: none !important;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #c2410c;
                transition: all 0.14s ease;
            }

            .gt2q-accent-btn:hover {
                background: #ea580c;
                border-color: #ea580c;
                color: #ffffff;
            }

            .gt2q-ghost-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.24rem 0.58rem;
                border-radius: 7px;
                font-size: 0.69rem;
                font-weight: 700;
                text-decoration: none !important;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                color: #475569;
                transition: all 0.14s ease;
            }

            .gt2q-ghost-btn:hover {
                background: #f8fafc;
                border-color: #cbd5e1;
                color: #0f172a;
            }

            .gt2q-list-item {
                padding-bottom: 0.58rem;
                margin-bottom: 0.58rem;
                border-bottom: 1px solid #f1f5f9;
            }

            .gt2q-list-item:last-child {
                padding-bottom: 0;
                margin-bottom: 0;
                border-bottom: 0;
            }

            .gt2q-info-subhead {
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
        $displayName = $karyawanProfile?->nama_gelar ?? $authUser?->name ?? 'Guru T2Q';
        $isEligible = $isEligible ?? false;
        $nipy = (string) ($nipy ?? '');
        $weekRangeLabel = (string) ($weekRangeLabel ?? '');
        $tingkatSummaries = $tingkatSummaries ?? collect();
        $studentRows = $studentRows ?? collect();
        $assignedEkskuls = $assignedEkskuls ?? collect();
        $t2qFinalisasi = $t2qFinalisasi ?? [];
        $activePeriode = $activePeriode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $upcomingAgendas = $upcomingAgendas ?? collect();
        $latestPengumuman = $latestPengumuman ?? collect();
        $stats = $stats ?? [
            'total_students' => 0,
            'baru_count' => 0,
            'lanjutan_count' => 0,
            'tingkat_count' => 0,
            'tahsin_today_count' => 0,
            'tahfizh_today_count' => 0,
            'tahsin_today_pct' => 0,
            'tahfizh_today_pct' => 0,
            'week_recorded_count' => 0,
            'week_avg_score' => null,
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
                            <i class="fa fa-quran mr-1"></i> GURU T2Q
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
                            Panel pembimbing <strong>Tahsin &amp; Tahfizh (T2Q)</strong>: catat setoran harian lintas jenjang, pantau matriks mingguan Senin–Kamis, lihat statistik capaian target, dan finalkan nilai T2Q periode rapor.
                        @else
                            Panel Guru T2Q terhubung otomatis dengan daftar siswa bimbingan pada data Anggota T2Q Tahun Ajaran aktif.
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
                                <i class="fa fa-users mr-1"></i>
                                {{ $stats['total_students'] }} Siswa &bull; {{ $stats['tingkat_count'] }} Tingkat
                            </span>
                            <a href="{{ route('guru-t2q.yaumiyah-tahsin.index') }}" class="badge px-3 py-2 text-decoration-none"
                                style="background: rgba(234, 88, 12, 0.9); border: 1px solid rgba(251, 146, 60, 0.65); color: #ffffff; font-size: 0.74rem;">
                                <i class="fa fa-edit mr-1"></i> Input Yaumiyah Hari Ini
                            </a>
                        @endif
                    </div>
                </div>

                @if ($isEligible)
                    <div class="col-lg-4">
                        <div class="wk-team-card">
                            <div class="text-uppercase font-weight-bold mb-2"
                                style="font-size: 0.68rem; letter-spacing: 0.05em; color: #fb923c;">
                                Progres Setoran Hari Ini
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.78rem;">
                                <span style="color: rgba(255, 255, 255, 0.72);">Yaumiyah Tahsin</span>
                                <span class="font-weight-bold text-white">{{ $stats['tahsin_today_count'] }} / {{ $stats['total_students'] }} siswa</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.78rem;">
                                <span style="color: rgba(255, 255, 255, 0.72);">Yaumiyah Tahfizh</span>
                                <span class="font-weight-bold text-white">{{ $stats['tahfizh_today_count'] }} / {{ $stats['total_students'] }} siswa</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.78rem;">
                                <span style="color: rgba(255, 255, 255, 0.72);">Rata-Rata Pekan Ini</span>
                                <span class="font-weight-bold text-white">
                                    {{ $stats['week_avg_score'] !== null ? number_format((float) $stats['week_avg_score'], 1) : '-' }}
                                </span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center" style="font-size: 0.78rem;">
                                <span style="color: rgba(255, 255, 255, 0.72);">Ekstrakurikuler Binaan</span>
                                <span class="font-weight-bold text-white">{{ $stats['ekskul_count'] ?? $assignedEkskuls->count() }} Ekskul</span>
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
                    <i class="fa fa-quran"></i>
                </div>
                <h5 class="font-weight-bold text-dark mb-2">
                    Belum Ada Daftar Bimbingan T2Q Aktif
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
                        Pada Tahun Ajaran aktif (<strong>{{ $activeTahunAjaran?->full_label }}</strong>), NIPY Anda (<code>{{ $nipy }}</code>) belum tercatat sebagai pembimbing pada daftar <strong>Anggota T2Q</strong>.
                    </p>
                @endif
                <div class="small text-muted px-3 py-2 rounded d-inline-block"
                    style="background: #f8fafc; border: 1px solid #e2e8f0;">
                    <i class="fa fa-info-circle text-warning mr-1"></i>
                    Hubungi Admin Akademik apabila penetapan siswa Anggota T2Q perlu diperbarui.
                </div>
            </div>
        @else
            <!-- 1. KPI STRIP -->
            <div class="wk-kpi-grid">
                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #2563eb;"></span> Siswa Bimbingan T2Q
                        </div>
                        <div class="wk-kpi-value">{{ $stats['total_students'] }} <span style="font-size: 0.9rem; font-weight: 600; color: #64748b;">Siswa</span></div>
                        <div class="wk-kpi-sub">
                            {{ $stats['baru_count'] }} Baru &bull; {{ $stats['lanjutan_count'] }} Lanjutan &bull; {{ $stats['tingkat_count'] }} Tingkat
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-users"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div style="flex: 1 1 auto;">
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #ea580c;"></span> Tahsin Hari Ini
                        </div>
                        <div class="wk-kpi-value" style="color: #c2410c;">
                            {{ $stats['tahsin_today_count'] }}<span style="font-size: 0.9rem; font-weight: 600; color: #64748b;"> / {{ $stats['total_students'] }}</span>
                        </div>
                        <div class="wk-progress-track mt-2" style="height: 5px;">
                            <div class="wk-progress-fill" style="width: {{ $stats['tahsin_today_pct'] }}%;"></div>
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-book-open"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div style="flex: 1 1 auto;">
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #10b981;"></span> Tahfizh Hari Ini
                        </div>
                        <div class="wk-kpi-value" style="color: #047857;">
                            {{ $stats['tahfizh_today_count'] }}<span style="font-size: 0.9rem; font-weight: 600; color: #64748b;"> / {{ $stats['total_students'] }}</span>
                        </div>
                        <div class="wk-progress-track mt-2" style="height: 5px;">
                            <div class="wk-progress-fill" style="width: {{ $stats['tahfizh_today_pct'] }}%; background: linear-gradient(90deg, #10b981 0%, #059669 100%);"></div>
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa fa-quran"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Rata-Rata Pekan Ini
                        </div>
                        <div class="wk-kpi-value" style="color: #6d28d9;">
                            {{ $stats['week_avg_score'] !== null ? number_format((float) $stats['week_avg_score'], 1) : '-' }}
                        </div>
                        <div class="wk-kpi-sub">
                            {{ $stats['week_recorded_count'] }} setoran ({{ $weekRangeLabel }})
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                        <i class="fa fa-chart-line"></i>
                    </span>
                </div>
            </div>

            <!-- 2. DOCK AKSES CEPAT -->
            @php
                $dockItems = [
                    [
                        'title' => 'Input Tahsin',
                        'sub' => 'Setoran jilid & bacaan',
                        'url' => route('guru-t2q.yaumiyah-tahsin.index'),
                        'icon' => 'fa-book-open',
                        'bg' => '#fff7ed',
                        'color' => '#ea580c',
                    ],
                    [
                        'title' => 'Input Tahfizh',
                        'sub' => 'Setoran hafalan surah',
                        'url' => route('guru-t2q.yaumiyah-tahfizh.index'),
                        'icon' => 'fa-quran',
                        'bg' => '#ecfdf5',
                        'color' => '#059669',
                    ],
                    [
                        'title' => 'Matriks Tahsin',
                        'sub' => 'Rekap Senin – Kamis',
                        'url' => route('guru-t2q.yaumiyah-tahsin.matriks'),
                        'icon' => 'fa-th',
                        'bg' => '#eff6ff',
                        'color' => '#2563eb',
                    ],
                    [
                        'title' => 'Matriks Tahfizh',
                        'sub' => 'Rekap Senin – Kamis',
                        'url' => route('guru-t2q.yaumiyah-tahfizh.matriks'),
                        'icon' => 'fa-th-list',
                        'bg' => '#eef2ff',
                        'color' => '#4f46e5',
                    ],
                    [
                        'title' => 'Statistik & Target',
                        'sub' => 'Capaian Tahun Ajaran',
                        'url' => route('guru-t2q.yaumiyah-tahsin.statistik'),
                        'icon' => 'fa-chart-bar',
                        'bg' => '#fefce8',
                        'color' => '#ca8a04',
                    ],
                    [
                        'title' => 'Rekap Rapor SD',
                        'sub' => 'Nilai akhir & deskripsi',
                        'url' => route('guru-t2q.rapor.index'),
                        'icon' => 'fa-file-signature',
                        'bg' => '#ecfeff',
                        'color' => '#0891b2',
                    ],
                    [
                        'title' => 'Catatan Rapor T2Q',
                        'sub' => 'Komentar Mid & Semester',
                        'url' => route('guru-t2q.catatan-rapor.index'),
                        'icon' => 'fa-comment-dots',
                        'bg' => '#fef3c7',
                        'color' => '#d97706',
                    ],
                    [
                        'title' => 'Ekstrakurikuler',
                        'sub' => ($stats['ekskul_count'] ?? $assignedEkskuls->count()) > 0
                            ? ($stats['ekskul_count'] ?? $assignedEkskuls->count()).' ekskul binaan'
                            : 'Ekskul pilihan',
                        'url' => route('guru-t2q.ekstrakurikuler.index'),
                        'icon' => 'fa-medal',
                        'bg' => '#fff7ed',
                        'color' => '#c2410c',
                    ],
                ];
            @endphp
            <div class="gt2q-dock-bar" aria-label="Akses Cepat Guru T2Q">
                @foreach ($dockItems as $item)
                    <a href="{{ $item['url'] }}" class="gt2q-dock-tile">
                        <span class="gt2q-dock-icon" style="background: {{ $item['bg'] }}; color: {{ $item['color'] }};">
                            <i class="fa {{ $item['icon'] }}"></i>
                        </span>
                        <span class="gt2q-dock-meta">
                            <span class="gt2q-dock-title d-block">{{ $item['title'] }}</span>
                            <span class="gt2q-dock-sub d-block">{{ $item['sub'] }}</span>
                        </span>
                    </a>
                @endforeach
            </div>

            <!-- 3. PANEL FINALISASI T2Q -->
            @include('guruT2q.partials._finalisasi_card', [
                't2qFinalisasi' => $t2qFinalisasi,
                'compact' => false,
            ])

            <!-- 4. BENTO WORKSPACE -->
            <div class="gt2q-bento-row">
                <!-- KIRI: RINGKASAN PER TINGKAT & DAFTAR SISWA HARI INI -->
                <div class="d-flex flex-column" style="gap: 1rem;">
                    <div class="wk-surface mb-0">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-layer-group text-warning mr-1"></i>
                                    Kelompok Bimbingan per Tingkat (Lintas Jenjang)
                                </h6>
                                <div class="small text-muted">Klik Input Tahsin / Tahfizh untuk mencatat setoran hari ini per tingkat</div>
                            </div>
                        </div>

                        <div class="p-3 p-md-4">
                            @if ($tingkatSummaries->isEmpty())
                                <div class="text-center py-3 text-muted small">
                                    Belum ada daftar siswa bimbingan T2Q pada Tahun Ajaran aktif.
                                </div>
                            @else
                                <div class="gt2q-tingkat-grid">
                                    @foreach ($tingkatSummaries as $tSummary)
                                    <div class="gt2q-tingkat-card">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span class="font-weight-bold text-slate-900" style="font-size: 0.9rem;">
                                                Tingkat {{ $tSummary['tingkat'] }}
                                            </span>
                                            <span class="gt2q-pill-chip" style="background: #f1f5f9; color: #334155;">
                                                {{ $tSummary['student_count'] }} siswa
                                            </span>
                                        </div>

                                        <div class="d-flex flex-wrap" style="gap: 0.35rem;">
                                            <span class="gt2q-pill-chip" style="background: #fff7ed; color: #c2410c;">
                                                Tahsin: {{ $tSummary['tahsin_today'] }}/{{ $tSummary['student_count'] }}
                                            </span>
                                            <span class="gt2q-pill-chip" style="background: #ecfdf5; color: #047857;">
                                                Tahfizh: {{ $tSummary['tahfizh_today'] }}/{{ $tSummary['student_count'] }}
                                            </span>
                                        </div>

                                        @if ($tSummary['targets'] !== [])
                                            <div class="small text-muted" style="font-size: 0.7rem; line-height: 1.35;">
                                                <i class="fa fa-bullseye text-warning mr-1"></i>
                                                Target: {{ implode(' • ', $tSummary['targets']) }}
                                            </div>
                                        @endif

                                        <div class="d-flex align-items-center pt-1" style="gap: 0.35rem;">
                                            <a href="{{ route('guru-t2q.yaumiyah-tahsin.index', ['tingkat' => $tSummary['tingkat']]) }}"
                                                class="gt2q-accent-btn">
                                                Input Tahsin
                                            </a>
                                            <a href="{{ route('guru-t2q.yaumiyah-tahfizh.index', ['tingkat' => $tSummary['tingkat']]) }}"
                                                class="gt2q-ghost-btn">
                                                Input Tahfizh
                                            </a>
                                        </div>
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
                                    <i class="fa fa-user-check text-success mr-1"></i>
                                    Pantauan Setoran Siswa Hari Ini
                                </h6>
                                <div class="small text-muted">Menampilkan progres harian seluruh siswa bimbingan Anda</div>
                            </div>
                            <div class="wk-search-wrap">
                                <i class="fa fa-search"></i>
                                <input type="text" id="gt2qStudentSearch" class="wk-search-input" placeholder="Cari siswa / NIS / tingkat...">
                            </div>
                        </div>

                        <div class="wk-scroll-body" style="max-height: 430px;">
                            <table class="wk-table" id="gt2qDashboardStudentTable">
                                <thead>
                                    <tr>
                                        <th>Siswa Bimbingan</th>
                                        <th class="text-center" style="width: 82px;">Tingkat</th>
                                        <th>Tahsin Hari Ini</th>
                                        <th>Tahfizh Hari Ini</th>
                                        <th class="text-center" style="width: 78px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($studentRows as $sRow)
                                        @php
                                            $tahsinEntry = $sRow['tahsin_today'];
                                            $tahfizhEntry = $sRow['tahfizh_today'];
                                            $searchHaystack = mb_strtolower(trim($sRow['nama_siswa'].' '.$sRow['nis'].' tingkat '.$sRow['tingkat']));
                                        @endphp
                                        <tr data-gt2q-student-row="{{ $searchHaystack }}">
                                            <td>
                                                <div class="font-weight-bold text-slate-900">{{ $sRow['nama_siswa'] }}</div>
                                                <div class="small text-muted" style="font-size: 0.69rem;">
                                                    NIS {{ $sRow['nis'] }} &bull; {{ $sRow['pendaftaran'] }}
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="gt2q-pill-chip" style="background: #f1f5f9; color: #334155;">
                                                    Tingkat {{ $sRow['tingkat'] }}
                                                </span>
                                            </td>
                                            <td>
                                                @if ($tahsinEntry !== null)
                                                    <span class="gt2q-pill-chip" style="background: #dcfce7; color: #166534;">
                                                        <i class="fa fa-check"></i>
                                                        {{ $tahsinEntry->nilai_formatted }}
                                                    </span>
                                                    <div class="small text-muted mt-1" style="font-size: 0.68rem;">
                                                        {{ $tahsinEntry->ringkasan_capaian }}
                                                    </div>
                                                @else
                                                    <span class="gt2q-pill-chip" style="background: #f1f5f9; color: #64748b;">
                                                        Belum setor
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                @if ($tahfizhEntry !== null)
                                                    <span class="gt2q-pill-chip" style="background: #dcfce7; color: #166534;">
                                                        <i class="fa fa-check"></i>
                                                        {{ $tahfizhEntry->nilai_formatted }}
                                                    </span>
                                                    <div class="small text-muted mt-1" style="font-size: 0.68rem;">
                                                        {{ $tahfizhEntry->ringkasan_capaian }}
                                                    </div>
                                                @else
                                                    <span class="gt2q-pill-chip" style="background: #f1f5f9; color: #64748b;">
                                                        Belum setor
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                <a href="{{ route('guru-t2q.yaumiyah-tahsin.index', ['tingkat' => $sRow['tingkat']]) }}"
                                                    class="gt2q-accent-btn">
                                                    Input
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
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
                                    <div class="small text-muted">Kelola nilai, predikat, &amp; finalisasi peserta ekskul pilihan</div>
                                </div>
                                <a href="{{ route('guru-t2q.ekstrakurikuler.index') }}" class="gt2q-ghost-btn">
                                    Kelola Nilai <i class="fa fa-arrow-right"></i>
                                </a>
                            </div>
                            <div class="p-3 d-flex flex-wrap" style="gap: 0.5rem;">
                                @foreach ($assignedEkskuls as $eks)
                                    <a href="{{ route('guru-t2q.ekstrakurikuler.index', ['mode' => 'pilihan', 'ekstrakurikuler_id' => $eks->sqid, 'periode' => $activePeriode]) }}"
                                        class="gt2q-ghost-btn" style="padding: 0.45rem 0.78rem;">
                                        <i class="fa fa-medal text-warning"></i>
                                        <span>{{ $eks->nama_ekstrakurikuler }}</span>
                                        <span class="gt2q-pill-chip" style="background: #ffedd5; color: #c2410c;">
                                            {{ $eks->anggota_ekstrakurikuler_count ?? 0 }} Peserta
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <!-- KANAN: RINGKASAN TARGET CAPAIAN & INFORMASI SEKOLAH -->
                <div class="d-flex flex-column" style="gap: 1rem;">
                    <div class="wk-surface mb-0">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-bullseye text-warning mr-1"></i> Target Capaian Bimbingan
                                </h6>
                                <div class="small text-muted">Acuan target Tahun Ajaran {{ $activeTahunAjaran->full_label }}</div>
                            </div>
                        </div>
                        <div class="p-3">
                            @forelse ($tingkatSummaries as $tSummary)
                                <div class="gt2q-list-item">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="font-weight-bold text-dark" style="font-size: 0.8rem;">
                                            Tingkat {{ $tSummary['tingkat'] }}
                                        </span>
                                        <span class="small text-muted">{{ $tSummary['student_count'] }} siswa</span>
                                    </div>
                                    @if ($tSummary['targets'] !== [])
                                        <div class="small text-slate-600" style="font-size: 0.74rem; line-height: 1.4;">
                                            {{ implode(' • ', $tSummary['targets']) }}
                                        </div>
                                    @else
                                        <div class="small text-muted font-italic" style="font-size: 0.72rem;">
                                            Belum ada target capaian ditetapkan untuk tingkat ini.
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="text-muted small">Belum ada tingkat bimbingan terdaftar.</div>
                            @endforelse
                        </div>
                    </div>

                    <div class="wk-surface mb-0">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-bullhorn text-danger mr-1"></i> Agenda &amp; Pengumuman
                                </h6>
                                <div class="small text-muted">Informasi sekolah Tahun Ajaran aktif</div>
                            </div>
                        </div>
                        <div class="p-3">
                            <div class="gt2q-info-subhead">
                                <i class="fa fa-calendar-check text-success"></i> Agenda Sekolah
                            </div>
                            @forelse ($upcomingAgendas->take(3) as $agenda)
                                <div class="gt2q-list-item d-flex align-items-start justify-content-between gap-2">
                                    <div style="min-width: 0;">
                                        <div class="font-weight-semibold text-dark text-truncate" style="font-size: 0.78rem;">
                                            {{ $agenda->nama_agenda }}
                                        </div>
                                        <div class="small text-muted" style="font-size: 0.68rem;">
                                            {{ $agenda->tanggal_formatted }} &bull; {{ $agenda->jam_formatted }}
                                        </div>
                                    </div>
                                    <span class="gt2q-pill-chip flex-shrink-0" style="background: #f1f5f9; color: #475569; font-size: 0.64rem;">
                                        {{ $agenda->unit }}
                                    </span>
                                </div>
                            @empty
                                <div class="text-muted small mb-2">Belum ada agenda sekolah terdekat.</div>
                            @endforelse

                            <div class="gt2q-info-subhead mt-3 pt-2" style="border-top: 1px dashed #e2e8f0;">
                                <i class="fa fa-newspaper text-warning"></i> Pengumuman Terbaru
                            </div>
                            @forelse ($latestPengumuman->take(2) as $info)
                                <div class="gt2q-list-item">
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

@pushOnce('js')
    <script>
        (function () {
            const searchInput = document.getElementById('gt2qStudentSearch');
            if (!searchInput) {
                return;
            }

            searchInput.addEventListener('input', function () {
                const query = searchInput.value.trim().toLowerCase();
                document.querySelectorAll('[data-gt2q-student-row]').forEach(function (row) {
                    const haystack = (row.getAttribute('data-gt2q-student-row') || '').toLowerCase();
                    row.style.display = query === '' || haystack.includes(query) ? '' : 'none';
                });
            });
        })();
    </script>
@endPushOnce

