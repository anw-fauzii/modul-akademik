@include('admin.t2q.partials._yaumiyah_shared_styles')

@push('css')
    <style>
        .t2q-st-filter-grid {
            display: grid;
            grid-template-columns: minmax(230px, 1.3fr) minmax(200px, 1fr) minmax(160px, 0.8fr) auto;
            gap: 0.9rem;
            align-items: end;
        }

        .t2q-st-filter-grid--with-kelas {
            grid-template-columns: minmax(210px, 1.15fr) minmax(175px, 0.9fr) minmax(185px, 0.95fr) minmax(150px, 0.75fr) auto;
        }

        .t2q-st-kpi-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .t2q-st-kpi {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.95rem 1.15rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.35rem;
        }

        .t2q-st-kpi-label {
            font-size: 0.68rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
        }

        .t2q-st-kpi-val {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            font-size: 1.32rem;
            font-weight: 800;
            color: #0f172a;
            font-variant-numeric: tabular-nums;
            line-height: 1.2;
        }

        .t2q-st-kpi-sub {
            font-size: 0.73rem;
            color: #64748b;
            font-weight: 500;
        }

        .t2q-st-analytics-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) minmax(0, 1fr);
            gap: 0.9rem;
            align-items: stretch;
        }

        @media (max-width: 1199.98px) {
            .t2q-st-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .t2q-st-analytics-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 991.98px) {
            .t2q-st-filter-grid,
            .t2q-st-filter-grid--with-kelas {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 575.98px) {
            .t2q-st-kpi-grid {
                grid-template-columns: 1fr;
            }
        }

        .t2q-st-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .t2q-st-panel-head {
            padding: 0.9rem 1.2rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
        }

        .t2q-st-panel-title {
            font-size: 0.86rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .t2q-st-chart-scroll {
            overflow-x: auto;
            padding: 1rem 1.2rem 1.1rem;
        }

        .t2q-st-chart-bars {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(56px, 1fr));
            gap: 0.65rem;
            align-items: end;
            min-width: 380px;
            padding-top: 0.5rem;
        }

        .t2q-st-bar-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
        }

        .t2q-st-bar-stage {
            width: 100%;
            height: 150px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 1px;
        }

        .t2q-st-bar-count {
            font-size: 0.7rem;
            font-weight: 700;
            color: #475569;
            font-variant-numeric: tabular-nums;
            margin-bottom: 4px;
        }

        .t2q-st-bar-fill {
            width: 24px;
            border-radius: 6px 6px 2px 2px;
            background: linear-gradient(180deg, #f97316 0%, #ea580c 100%);
            transition: height 0.25s ease, opacity 0.2s ease;
        }

        .t2q-st-bar-fill.is-empty {
            width: 24px;
            height: 3px !important;
            background: #e2e8f0;
        }

        .t2q-st-bar-month {
            font-size: 0.74rem;
            font-weight: 700;
            color: #334155;
        }

        .t2q-st-bar-avg {
            font-size: 0.66rem;
            font-weight: 600;
            color: #64748b;
            font-variant-numeric: tabular-nums;
        }

        .t2q-st-meter-row {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .t2q-st-meter-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            font-size: 0.77rem;
        }

        .t2q-st-meter-track {
            height: 6px;
            border-radius: 9999px;
            background: #f1f5f9;
            overflow: hidden;
        }

        .t2q-st-meter-fill {
            height: 100%;
            border-radius: 9999px;
            transition: width 0.25s ease;
        }

        .t2q-st-meter-fill--excellent {
            background: #10b981;
        }

        .t2q-st-meter-fill--good {
            background: #3b82f6;
        }

        .t2q-st-meter-fill--fair {
            background: #f59e0b;
        }

        .t2q-st-meter-fill--poor {
            background: #f43f5e;
        }

        .t2q-st-meter-fill--material {
            background: linear-gradient(90deg, #ea580c 0%, #f59e0b 100%);
        }

        .t2q-st-tab-panel {
            display: none;
        }

        .t2q-st-tab-panel.is-active {
            display: block;
        }

        .t2q-st-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-top: none;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 0.9rem;
            white-space: nowrap;
        }

        .t2q-st-table tbody td {
            padding: 0.75rem 0.9rem;
            border-top: 1px solid #f1f5f9;
            vertical-align: middle;
            font-size: 0.82rem;
            color: #334155;
        }

        .t2q-st-table tbody tr:hover {
            background: #f8fafc;
        }

        /* Diagram & Grafik Pencapaian Target */
        .t2q-st-target-top {
            display: grid;
            grid-template-columns: minmax(0, 0.95fr) minmax(0, 1.35fr);
            gap: 0.9rem;
            align-items: stretch;
        }

        .t2q-st-donut-wrap {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: center;
            gap: 1.35rem;
            padding: 1.1rem 1.25rem;
            flex: 1;
        }

        .t2q-st-donut-box {
            position: relative;
            width: 146px;
            height: 146px;
            flex-shrink: 0;
        }

        .t2q-st-donut-svg {
            width: 100%;
            height: 100%;
            display: block;
        }

        .t2q-st-donut-center {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            pointer-events: none;
        }

        .t2q-st-donut-pct {
            font-size: 1.38rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.1;
            font-variant-numeric: tabular-nums;
        }

        .t2q-st-donut-cap {
            font-size: 0.67rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: #64748b;
            margin-top: 2px;
        }

        .t2q-st-donut-legend {
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
            min-width: 175px;
            flex: 1;
        }

        .t2q-st-legend-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.65rem;
            padding: 0.45rem 0.65rem;
            border-radius: 10px;
            background: #f8fafc;
            border: 1px solid #f1f5f9;
            font-size: 0.77rem;
        }

        .t2q-st-legend-dot {
            display: inline-block;
            width: 9px;
            height: 9px;
            border-radius: 9999px;
            flex-shrink: 0;
        }

        .t2q-st-stacked-track {
            display: flex;
            height: 11px;
            border-radius: 9999px;
            overflow: hidden;
            background: #f1f5f9;
        }

        .t2q-st-stacked-seg {
            height: 100%;
            transition: width 0.25s ease;
        }

        .t2q-st-who-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.9rem;
        }

        .t2q-st-who-col {
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            background: #ffffff;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        .t2q-st-who-head {
            padding: 0.75rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            background: #fcfcfd;
        }

        .t2q-st-who-list {
            max-height: 295px;
            overflow-y: auto;
            padding: 0.65rem 0.85rem;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .t2q-st-who-card {
            padding: 0.62rem 0.75rem;
            border-radius: 10px;
            border: 1px solid #f1f5f9;
            background: #fafbfc;
            display: flex;
            flex-direction: column;
            gap: 0.35rem;
        }

        .t2q-st-who-card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.5rem;
        }

        @media (max-width: 991.98px) {
            .t2q-st-target-top,
            .t2q-st-who-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
@endpush

@php
    $isTahsin = ($mode ?? 'tahsin') === 'tahsin';
    $isReadOnly = (bool) ($isReadOnly ?? false);
    $isWaliKelasScope = (bool) ($isWaliKelasScope ?? false);
    $isGuruT2qScope = (bool) ($isGuruT2qScope ?? false);
    $karyawanProfile = $karyawanProfile ?? null;
    $routePrefix = (string) ($routePrefix ?? 't2q');
    $assignedClasses = $assignedClasses ?? collect();
    $selectedKelas = $selectedKelas ?? null;
    $selectedKelasId = (int) ($selectedKelasId ?? ($selectedKelas?->id ?? 0));
    $peranDiKelas = $peranDiKelas ?? null;
    $showKelasSelector = $isWaliKelasScope && $assignedClasses->count() > 1;

    $indexRoute = $routePrefix . '.' . ($isTahsin ? 'yaumiyah-tahsin.index' : 'yaumiyah-tahfizh.index');
    $tahsinStatistikRoute = $routePrefix . '.yaumiyah-tahsin.statistik';
    $tahfizhStatistikRoute = $routePrefix . '.yaumiyah-tahfizh.statistik';
    $matriksRoute = $routePrefix . '.' . ($isTahsin ? 'yaumiyah-tahsin.matriks' : 'yaumiyah-tahfizh.matriks');
    $statistikRoute = $isTahsin ? $tahsinStatistikRoute : $tahfizhStatistikRoute;
    $pageTitle = $isTahsin ? 'Statistik Yaumiyah Tahsin' : 'Statistik Yaumiyah Tahfizh';
    $pageSubtitle = $isTahsin
        ? 'Ringkasan capaian setoran jilid & bacaan Al-Qur\'an berdasarkan Tahun Ajaran.'
        : 'Ringkasan capaian setoran hafalan surah Al-Qur\'an berdasarkan Tahun Ajaran.';

    $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
    $selectedTahunAjaran = $selectedTahunAjaran ?? null;
    $selectedTahunAjaranId = (int) ($selectedTahunAjaranId ?? ($selectedTahunAjaran?->id ?? 0));
    $guruOptions = $guruOptions ?? collect();
    $selectedGuruNipy = (string) ($selectedGuruNipy ?? '');
    $tingkatOptions = $tingkatOptions ?? [];
    $selectedTingkat = (string) ($selectedTingkat ?? '');

    $stats = $stats ?? [];
    $summary = $stats['summary'] ?? [];
    $targetAchievement = $stats['target_achievement'] ?? [
        'achieved_count' => 0,
        'unachieved_count' => 0,
        'no_target_count' => 0,
        'evaluated_count' => 0,
        'achieved_pct' => 0.0,
        'evaluated_achieved_pct' => 0.0,
        'donut_segments' => [],
        'achieved_students' => [],
        'unachieved_students' => [],
    ];
    $donutSegments = $targetAchievement['donut_segments'] ?? [];
    $achievedStudents = $targetAchievement['achieved_students'] ?? [];
    $unachievedStudents = $targetAchievement['unachieved_students'] ?? [];

    $tierRows = $stats['tier_rows'] ?? [];
    $monthlyRows = $stats['monthly_rows'] ?? [];
    $materialRows = $stats['material_rows'] ?? [];
    $tingkatRows = $stats['tingkat_rows'] ?? [];
    $guruRows = $stats['guru_rows'] ?? [];
    $studentRows = $stats['student_rows'] ?? [];

    $extractStudentMeta = static function ($anggota) use ($isGuruT2qScope): array {
        $ak = $anggota->relationLoaded('anggotaKelas') ? $anggota->anggotaKelas : null;
        $siswa = ($ak && $ak->relationLoaded('siswa')) ? $ak->siswa : null;
        $kelas = ($ak && $ak->relationLoaded('kelas')) ? $ak->kelas : null;
        $guru = $anggota->relationLoaded('guruT2q') ? $anggota->guruT2q : null;
        $namaSiswa = $siswa?->nama_lengkap ?? ($ak?->siswa_nis ?? ('Anggota #'.$anggota->id));
        $nisSiswa = $ak?->siswa_nis ?? '';

        return [
            'nama' => $namaSiswa,
            'nis' => $nisSiswa,
            'kelas' => $isGuruT2qScope ? '' : ($kelas?->nama_kelas ?? ''),
            'guru' => $isGuruT2qScope ? '' : ($guru?->nama_gelar ?? ''),
            'tingkat' => (string) ($anggota->tingkat ?? '-'),
            'search' => strtolower(trim("{$namaSiswa} {$nisSiswa} ".(! $isGuruT2qScope ? ($kelas?->nama_kelas ?? '').' '.($guru?->nama_gelar ?? '').' ' : '').'tingkat '.($anggota->tingkat ?? ''))),
        ];
    };

    $modeSwitchQuery = \App\Support\Sqid::encodeQueryParams(array_filter([
        'tahun_ajaran_id' => $selectedTahunAjaranId,
        'kelas_id' => $selectedKelasId > 0 ? $selectedKelasId : null,
        'karyawan_nipy' => $isGuruT2qScope ? null : $selectedGuruNipy,
        'tingkat' => $selectedTingkat,
    ]));
    $workspaceQuery = \App\Support\Sqid::encodeQueryParams(array_filter([
        'kelas_id' => ($selectedKelasId > 0 && ($selectedTahunAjaran?->is_active ?? false)) ? $selectedKelasId : null,
        'karyawan_nipy' => $isGuruT2qScope ? null : $selectedGuruNipy,
        'tingkat' => $selectedTingkat,
    ]));
@endphp

<div class="app-main__inner">
    <!-- PAGE HEADER -->
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon text-white shadow-sm"
                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                    <i class="pe-7s-graph2" style="color: white;"></i>
                </div>
                <div>
                    {{ $pageTitle }}
                    <div class="page-title-subheading">
                        {{ $pageSubtitle }}
                    </div>
                </div>
            </div>

            <div class="page-title-actions d-flex flex-wrap align-items-center" style="gap: 0.65rem;">
                @if ($isGuruT2qScope)
                    <a href="{{ route('guru-t2q.dashboard') }}" class="filter-btn filter-btn-secondary" style="height: 2.2rem; min-height: 2.2rem;">
                        <i class="fa fa-arrow-left text-warning"></i>
                        <span>Dashboard</span>
                    </a>
                @endif
                <div class="t2q-view-switch" role="navigation" aria-label="Pilih program statistik">
                    <a href="{{ route($tahsinStatistikRoute, $modeSwitchQuery) }}"
                        class="t2q-view-pill {{ $isTahsin ? 'is-active' : '' }}">
                        <i class="fa fa-book-open"></i>
                        <span>Tahsin</span>
                    </a>
                    <a href="{{ route($tahfizhStatistikRoute, $modeSwitchQuery) }}"
                        class="t2q-view-pill {{ ! $isTahsin ? 'is-active' : '' }}">
                        <i class="fa fa-quran"></i>
                        <span>Tahfizh</span>
                    </a>
                </div>

                <a href="{{ route($matriksRoute, $workspaceQuery) }}" class="filter-btn filter-btn-secondary" style="height: 2.2rem; min-height: 2.2rem;">
                    <i class="fa fa-th text-warning"></i>
                    <span>Matriks (Sen–Kam)</span>
                </a>
                @if (! $isReadOnly)
                    <a href="{{ route($indexRoute, $workspaceQuery) }}" class="filter-btn filter-btn-secondary" style="height: 2.2rem; min-height: 2.2rem;">
                        <i class="fa fa-edit text-success"></i>
                        <span>Input Harian</span>
                    </a>
                @endif
                @if ($isReadOnly)
                    <span class="badge badge-light border px-3 py-2 font-weight-semibold text-muted"
                        style="font-size: 0.75rem; border-radius: 9999px;">
                        <i class="fa fa-eye mr-1"></i>Lihat Saja
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if ($selectedTahunAjaran === null)
        <div class="alert alert-warning border shadow-sm" style="border-radius: 12px;">
            <i class="fa fa-exclamation-triangle mr-1"></i>
            Belum ada data <strong>Tahun Ajaran</strong> yang tersedia untuk menampilkan statistik.
        </div>
    @else
        <!-- FILTER TAHUN AJARAN, GURU, TINGKAT -->
        <div class="filter-card mb-3">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-chart-pie"></i></span>
                    <span>Filter Statistik Tahun Ajaran</span>
                </div>
                <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                    @if ($isWaliKelasScope)
                        <span class="badge badge-light border px-3 py-1 font-weight-semibold text-slate-700" style="border-radius: 9999px;">
                            <i class="fa fa-chalkboard-teacher text-warning mr-1"></i>
                            @if ($selectedKelas)
                                {{ $selectedKelas->nama_kelas }}@if ($peranDiKelas) &bull; {{ $peranDiKelas }}@endif
                            @else
                                Semua Kelas Binaan ({{ $assignedClasses->count() }})
                            @endif
                        </span>
                    @endif
                    <span class="badge badge-light border px-3 py-1 font-weight-semibold text-slate-700" style="border-radius: 9999px;">
                        <i class="fa fa-calendar-alt mr-1 {{ $selectedTahunAjaran->is_active ? 'text-success' : 'text-muted' }}"></i>
                        {{ $selectedTahunAjaran->full_label }}
                        @if ($selectedTahunAjaran->is_active)
                            (Aktif)
                        @else
                            (Arsip)
                        @endif
                    </span>
                </div>
            </div>

            <div class="filter-card-body">
                <form method="GET" action="{{ route($statistikRoute) }}" id="t2qStatistikFilterForm"
                    class="t2q-st-filter-grid {{ $showKelasSelector ? 't2q-st-filter-grid--with-kelas' : '' }}">
                    <div>
                        <label for="st_filter_ta" class="filter-label">Tahun Ajaran</label>
                        <select name="tahun_ajaran_id" id="st_filter_ta" class="filter-control js-st-auto-submit">
                            @foreach ($tahunAjaranOptions as $ta)
                                <option value="{{ $ta->sqid }}" @selected((int) $ta->id === $selectedTahunAjaranId)>
                                    {{ $ta->full_label }}@if ($ta->is_active) (Aktif)@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if ($showKelasSelector)
                        <div>
                            <label for="st_filter_kelas" class="filter-label">Kelas Binaan</label>
                            <select name="kelas_id" id="st_filter_kelas" class="filter-control js-st-auto-submit">
                                <option value="">Semua Kelas Binaan ({{ $assignedClasses->count() }})</option>
                                @foreach ($assignedClasses as $kelasItem)
                                    <option value="{{ $kelasItem->sqid }}" @selected((int) $kelasItem->id === $selectedKelasId)>
                                        {{ $kelasItem->nama_kelas }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="st_filter_guru" class="filter-label">Guru Pembimbing T2Q</label>
                        @if ($isGuruT2qScope)
                            <input type="hidden" name="karyawan_nipy" value="{{ $selectedGuruNipy }}">
                            <div class="filter-control d-flex align-items-center font-weight-semibold text-slate-700"
                                style="background: #f8fafc; color: #334155;">
                                <i class="fa fa-user-check text-success mr-2"></i>
                                <span class="text-truncate">{{ $karyawanProfile?->nama_gelar ?? $selectedGuruNipy }}</span>
                            </div>
                        @else
                            <select name="karyawan_nipy" id="st_filter_guru" class="filter-control js-st-auto-submit">
                                <option value="">Semua Guru T2Q</option>
                                @foreach ($guruOptions as $guru)
                                    <option value="{{ $guru->nipy }}" @selected($selectedGuruNipy === (string) $guru->nipy)>
                                        {{ $guru->nama_gelar }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div>
                        <label for="st_filter_tingkat" class="filter-label">Tingkat</label>
                        <select name="tingkat" id="st_filter_tingkat" class="filter-control js-st-auto-submit">
                            <option value="">Semua Tingkat</option>
                            @foreach ($tingkatOptions as $tKey => $tCount)
                                <option value="{{ $tKey }}" @selected(strcasecmp($selectedTingkat, (string) $tKey) === 0)>
                                    Tingkat {{ $tKey }} ({{ $tCount }} siswa)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex align-items-center" style="gap: 0.45rem;">
                        <button type="submit" class="filter-btn filter-btn-primary">
                            <i class="fa fa-filter"></i> Terapkan
                        </button>
                        @if ((! $isGuruT2qScope && $selectedGuruNipy !== '') || $selectedTingkat !== '' || ($showKelasSelector && $selectedKelasId > 0) || ! $selectedTahunAjaran->is_active)
                            <a href="{{ route($statistikRoute) }}" class="filter-btn filter-btn-secondary filter-btn-icon"
                                title="Reset ke Tahun Ajaran aktif">
                                <i class="fa fa-undo"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- KPI STRIP -->
        <div class="t2q-st-kpi-grid mb-3">
            <div class="t2q-st-kpi">
                <span class="t2q-st-kpi-label">Siswa Binaan T2Q</span>
                <div class="t2q-st-kpi-val">
                    <span>{{ $summary['total_students'] ?? 0 }} <small class="text-muted font-weight-normal" style="font-size: 0.78rem;">siswa</small></span>
                    <span class="t2q-score-pill t2q-score-pill--neutral">{{ $summary['student_activity_pct'] ?? 0 }}% aktif</span>
                </div>
                <span class="t2q-st-kpi-sub">
                    {{ $summary['active_students'] ?? 0 }} sudah setor &bull; {{ $summary['inactive_students'] ?? 0 }} belum setor
                </span>
            </div>

            <div class="t2q-st-kpi">
                <span class="t2q-st-kpi-label">Total Setoran Tercatat</span>
                <div class="t2q-st-kpi-val">
                    <span>{{ $summary['total_recorded'] ?? 0 }}</span>
                    <span class="t2q-score-pill t2q-score-pill--neutral">{{ $summary['active_days_count'] ?? 0 }} hari</span>
                </div>
                <span class="t2q-st-kpi-sub">Rata-rata {{ $summary['avg_per_student'] ?? 0 }} setoran per siswa</span>
            </div>

            <div class="t2q-st-kpi">
                <span class="t2q-st-kpi-label">Rata-rata Nilai Siswa</span>
                <div class="t2q-st-kpi-val">
                    <span>{{ $summary['overall_avg_formatted'] ?? '-' }}</span>
                    <span class="t2q-score-pill t2q-score-pill--{{ $summary['overall_avg_tier'] ?? 'neutral' }}">
                        {{ $summary['overall_avg_label'] ?? 'Belum Ada Data' }}
                    </span>
                </div>
                <span class="t2q-st-kpi-sub">Dihitung dari rata-rata {{ $summary['scored_students'] ?? 0 }} siswa bernilai</span>
            </div>

            <div class="t2q-st-kpi">
                <span class="t2q-st-kpi-label">Siswa Sangat Baik (&ge; 85)</span>
                <div class="t2q-st-kpi-val">
                    <span>{{ $summary['excellent_pct'] ?? 0 }}%</span>
                    <span class="t2q-score-pill t2q-score-pill--excellent">{{ $summary['excellent_count'] ?? 0 }} siswa</span>
                </div>
                <span class="t2q-st-kpi-sub">Dari {{ $summary['scored_students'] ?? 0 }} siswa yang memiliki nilai</span>
            </div>
        </div>

        <!-- DIAGRAM & GRAFIK PENCAPAIAN TARGET CAPAIAN -->
        <div class="t2q-st-target-top mb-3">
            <!-- DIAGRAM DONAT STATUS TARGET -->
            <div class="t2q-st-panel">
                <div class="t2q-st-panel-head">
                    <div>
                        <h6 class="t2q-st-panel-title">Diagram Pencapaian Target</h6>
                        <div class="small text-muted" style="font-size: 0.72rem;">
                            Proporsi siswa yang sudah vs belum mencapai target TA
                        </div>
                    </div>
                    <span class="filter-count-pill">
                        <i class="fa fa-bullseye text-warning"></i>
                        <span>{{ $targetAchievement['evaluated_count'] ?? 0 }} Terevaluasi</span>
                    </span>
                </div>

                <div class="t2q-st-donut-wrap">
                    <div class="t2q-st-donut-box">
                        <svg viewBox="0 0 120 120" class="t2q-st-donut-svg" role="img"
                            aria-label="Diagram donat pencapaian target {{ $targetAchievement['evaluated_achieved_pct'] ?? 0 }}%">
                            <circle cx="60" cy="60" r="46" fill="none" stroke="#f1f5f9" stroke-width="13" />
                            <g transform="rotate(-90 60 60)">
                                @foreach ($donutSegments as $seg)
                                    @if ($seg['count'] > 0)
                                        <circle cx="60" cy="60" r="46" fill="none"
                                            stroke="{{ $seg['color'] }}"
                                            stroke-width="13"
                                            stroke-dasharray="{{ $seg['dash'] }} 289.03"
                                            stroke-dashoffset="{{ $seg['offset'] }}" />
                                    @endif
                                @endforeach
                            </g>
                        </svg>
                        <div class="t2q-st-donut-center">
                            <span class="t2q-st-donut-pct">{{ $targetAchievement['evaluated_achieved_pct'] ?? 0 }}%</span>
                            <span class="t2q-st-donut-cap">Tercapai</span>
                        </div>
                    </div>

                    <div class="t2q-st-donut-legend">
                        @foreach ($donutSegments as $seg)
                            @if ($seg['key'] !== 'tanpa_target' || $seg['count'] > 0)
                                <div class="t2q-st-legend-item">
                                    <span class="d-inline-flex align-items-center font-weight-semibold text-slate-700" style="gap: 0.45rem;">
                                        <span class="t2q-st-legend-dot" style="background: {{ $seg['color'] }};"></span>
                                        <span>{{ $seg['label'] }}</span>
                                    </span>
                                    <span class="text-muted" style="font-variant-numeric: tabular-nums;">
                                        <strong class="text-slate-900">{{ $seg['count'] }}</strong> ({{ $seg['pct'] }}%)
                                    </span>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- GRAFIK BATANG BERTUMPUK PER TINGKAT -->
            <div class="t2q-st-panel">
                <div class="t2q-st-panel-head">
                    <div>
                        <h6 class="t2q-st-panel-title">Grafik Pencapaian Target per Tingkat</h6>
                        <div class="small text-muted" style="font-size: 0.72rem;">
                            Perbandingan siswa tercapai vs belum tercapai berdasarkan target tingkat
                        </div>
                    </div>
                </div>

                <div class="p-4 d-flex flex-column" style="gap: 0.85rem;">
                    @if ($tingkatRows === [])
                        <div class="small text-muted font-italic">Belum ada data tingkat pada Tahun Ajaran ini.</div>
                    @else
                        @foreach ($tingkatRows as $tRow)
                            <div class="t2q-st-meter-row"
                                title="Tingkat {{ $tRow['tingkat'] }}: {{ $tRow['achieved_count'] }} tercukupi, {{ $tRow['unachieved_count'] }} belum">
                                <div class="t2q-st-meter-head">
                                    <span class="font-weight-bold text-slate-800">
                                        Tingkat {{ $tRow['tingkat'] }}
                                        @if ($tRow['target_labels'] !== [])
                                            <span class="text-muted font-weight-normal small ml-1">({{ implode(', ', $tRow['target_labels']) }})</span>
                                        @endif
                                    </span>
                                    <span class="text-muted small" style="font-variant-numeric: tabular-nums;">
                                        <strong class="text-success">{{ $tRow['achieved_count'] }}</strong>
                                        <span>/</span>
                                        <span>{{ $tRow['student_count'] }} siswa</span>
                                        <span class="ml-1">({{ $tRow['achieved_pct'] }}%)</span>
                                    </span>
                                </div>
                                <div class="t2q-st-stacked-track">
                                    @if (($tRow['achieved_pct'] ?? 0) > 0)
                                        <div class="t2q-st-stacked-seg" style="width: {{ $tRow['achieved_pct'] }}%; background: #10b981;"></div>
                                    @endif
                                    @if (($tRow['unachieved_pct'] ?? 0) > 0)
                                        <div class="t2q-st-stacked-seg" style="width: {{ $tRow['unachieved_pct'] }}%; background: #f59e0b;"></div>
                                    @endif
                                    @if (($tRow['no_target_pct'] ?? 0) > 0)
                                        <div class="t2q-st-stacked-seg" style="width: {{ $tRow['no_target_pct'] }}%; background: #cbd5e1;"></div>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- SIAPA SAJA YANG SUDAH & BELUM MENCAPAI TARGET -->
        <div class="t2q-surface p-3 mb-3">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-3 px-1" style="gap: 0.75rem;">
                <div>
                    <h6 class="font-weight-bold text-slate-800 mb-0" style="font-size: 0.9rem;">
                        Daftar Pencapaian Target Siswa (Siapa Saja)
                    </h6>
                    <div class="small text-muted" style="font-size: 0.72rem;">
                        @if (($targetAchievement['no_target_count'] ?? 0) > 0)
                            {{ $targetAchievement['no_target_count'] }} siswa berada di tingkat yang belum memiliki target capaian.
                        @else
                            Rincian individu siswa yang sudah mencapai target maupun yang masih dalam proses.
                        @endif
                    </div>
                </div>
                <div class="t2q-search-box">
                    <i class="fa fa-search"></i>
                    <input type="text" id="t2qTargetStudentSearch" placeholder="Cari nama / tingkat / kelas...">
                </div>
            </div>

            <div class="t2q-st-who-grid">
                <!-- SUDAH MENCAPAI TARGET -->
                <div class="t2q-st-who-col">
                    <div class="t2q-st-who-head">
                        <span class="d-inline-flex align-items-center font-weight-bold text-slate-800" style="gap: 0.45rem; font-size: 0.82rem;">
                            <span class="t2q-st-legend-dot" style="background: #10b981;"></span>
                            <span>Sudah Mencapai Target</span>
                        </span>
                        <span class="t2q-score-pill t2q-score-pill--excellent">{{ count($achievedStudents) }} siswa</span>
                    </div>
                    <div class="t2q-st-who-list" id="t2qTargetAchievedList">
                        @if ($achievedStudents === [])
                            <div class="text-center py-4 text-muted small font-italic">
                                Belum ada siswa yang tercatat mencapai target pada filter ini.
                            </div>
                        @else
                            @foreach ($achievedStudents as $item)
                                @php $meta = $extractStudentMeta($item['anggota']); @endphp
                                <div class="t2q-st-who-card" data-st-target-card data-search-text="{{ $meta['search'] }}">
                                    <div class="t2q-st-who-card-top">
                                        <div class="min-w-0">
                                            <div class="font-weight-bold text-slate-900" style="font-size: 0.81rem;">{{ $meta['nama'] }}</div>
                                            <div class="small text-muted" style="font-size: 0.7rem;">
                                                Tingkat {{ $meta['tingkat'] }}
                                                @if ($meta['kelas'] !== '')
                                                    &bull; {{ $meta['kelas'] }}
                                                @endif
                                                @if ($meta['guru'] !== '')
                                                    &bull; {{ $meta['guru'] }}
                                                @endif
                                            </div>
                                        </div>
                                        <span class="t2q-score-pill t2q-score-pill--excellent">Tercapai</span>
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.72rem;">{{ $item['target_detail'] }}</div>
                                    <div class="t2q-st-meter-track">
                                        <div class="t2q-st-meter-fill t2q-st-meter-fill--excellent" style="width: 100%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>

                <!-- BELUM MENCAPAI TARGET -->
                <div class="t2q-st-who-col">
                    <div class="t2q-st-who-head">
                        <span class="d-inline-flex align-items-center font-weight-bold text-slate-800" style="gap: 0.45rem; font-size: 0.82rem;">
                            <span class="t2q-st-legend-dot" style="background: #f59e0b;"></span>
                            <span>Belum Mencapai Target</span>
                        </span>
                        <span class="t2q-score-pill t2q-score-pill--fair">{{ count($unachievedStudents) }} siswa</span>
                    </div>
                    <div class="t2q-st-who-list" id="t2qTargetUnachievedList">
                        @if ($unachievedStudents === [])
                            <div class="text-center py-4 text-muted small font-italic">
                                Tidak ada siswa yang tertinggal dari target pada filter ini.
                            </div>
                        @else
                            @foreach ($unachievedStudents as $item)
                                @php
                                    $meta = $extractStudentMeta($item['anggota']);
                                    $pct = (int) ($item['target_progress_pct'] ?? 0);
                                @endphp
                                <div class="t2q-st-who-card" data-st-target-card data-search-text="{{ $meta['search'] }}">
                                    <div class="t2q-st-who-card-top">
                                        <div class="min-w-0">
                                            <div class="font-weight-bold text-slate-900" style="font-size: 0.81rem;">{{ $meta['nama'] }}</div>
                                            <div class="small text-muted" style="font-size: 0.7rem;">
                                                Tingkat {{ $meta['tingkat'] }}
                                                @if ($meta['kelas'] !== '')
                                                    &bull; {{ $meta['kelas'] }}
                                                @endif
                                                @if ($meta['guru'] !== '')
                                                    &bull; {{ $meta['guru'] }}
                                                @endif
                                            </div>
                                        </div>
                                        <span class="t2q-score-pill t2q-score-pill--fair">{{ $pct }}%</span>
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.72rem;">{{ $item['target_detail'] }}</div>
                                    <div class="t2q-st-meter-track">
                                        <div class="t2q-st-meter-fill t2q-st-meter-fill--fair" style="width: {{ $pct > 0 ? max(4, $pct) : 0 }}%;"></div>
                                    </div>
                                </div>
                            @endforeach
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- ANALITIK BULANAN & DISTRIBUSI CAPAIAN -->
        <div class="t2q-st-analytics-grid mb-3">
            <!-- TREN BULANAN -->
            <div class="t2q-st-panel">
                <div class="t2q-st-panel-head">
                    <div>
                        <h6 class="t2q-st-panel-title">Tren Setoran &amp; Nilai Bulanan</h6>
                        <div class="small text-muted" style="font-size: 0.72rem;">
                            Puncak aktivitas: <strong>{{ $summary['best_month_label'] ?? '-' }}</strong>
                            @if (($summary['best_month_count'] ?? 0) > 0)
                                ({{ $summary['best_month_count'] }} setoran)
                            @endif
                        </div>
                    </div>
                    <span class="filter-count-pill">
                        <i class="fa fa-calendar-check text-warning"></i>
                        <span>{{ count($monthlyRows) }} Bulan</span>
                    </span>
                </div>

                <div class="t2q-st-chart-scroll">
                    @if ($monthlyRows === [])
                        <div class="text-center py-5 text-muted small">
                            Belum ada rentang bulan terpetakan untuk Tahun Ajaran ini.
                        </div>
                    @else
                        <div class="t2q-st-chart-bars">
                            @foreach ($monthlyRows as $mRow)
                                <div class="t2q-st-bar-col" title="{{ $mRow['month_full'] }}: {{ $mRow['recorded_count'] }} setoran • Rata-rata {{ $mRow['avg_formatted'] }}">
                                    <div class="t2q-st-bar-stage">
                                        <span class="t2q-st-bar-count">{{ $mRow['recorded_count'] }}</span>
                                        <div class="t2q-st-bar-fill {{ $mRow['recorded_count'] === 0 ? 'is-empty' : '' }}"
                                            style="height: {{ $mRow['bar_height_pct'] }}%;"></div>
                                    </div>
                                    <span class="t2q-st-bar-month">{{ $mRow['month_label'] }}</span>
                                    <span class="t2q-st-bar-avg">Ø {{ $mRow['avg_formatted'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <!-- DISTRIBUSI PREDIKAT & MATERI -->
            <div class="t2q-st-panel">
                <div class="t2q-st-panel-head">
                    <div>
                        <h6 class="t2q-st-panel-title">
                            Distribusi Nilai Siswa &amp; {{ $isTahsin ? 'Jilid Bacaan' : 'Surah Teratas' }}
                        </h6>
                        <div class="small text-muted" style="font-size: 0.72rem;">
                            Dihitung berdasarkan rata-rata nilai per siswa ({{ $summary['scored_students'] ?? 0 }} siswa bernilai)
                        </div>
                    </div>
                </div>

                <div class="p-4 d-flex flex-column" style="gap: 1.15rem;">
                    <div class="d-flex flex-column" style="gap: 0.6rem;">
                        @foreach ($tierRows as $tRow)
                            <div class="t2q-st-meter-row">
                                <div class="t2q-st-meter-head">
                                    <span class="font-weight-semibold text-slate-700">{{ $tRow['label'] }}</span>
                                    <span class="text-muted" style="font-variant-numeric: tabular-nums;">
                                        <strong class="text-slate-800">{{ $tRow['count'] }}</strong> siswa ({{ $tRow['pct'] }}%)
                                    </span>
                                </div>
                                <div class="t2q-st-meter-track">
                                    <div class="t2q-st-meter-fill t2q-st-meter-fill--{{ $tRow['key'] }}"
                                        style="width: {{ min(100, (float) $tRow['pct']) }}%;"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="border-top pt-3" style="border-color: #f1f5f9 !important;">
                        <div class="filter-label mb-2">
                            {{ $isTahsin ? 'Distribusi Jilid & Rata-rata Nilai Siswa' : 'Surah Paling Sering Disetor' }}
                        </div>
                        @if ($materialRows === [])
                            <div class="small text-muted font-italic">Belum ada setoran tercatat pada filter ini.</div>
                        @else
                            <div class="d-flex flex-column" style="gap: 0.55rem;">
                                @foreach ($materialRows as $matRow)
                                    <div class="t2q-st-meter-row">
                                        <div class="t2q-st-meter-head">
                                            <span class="font-weight-semibold text-slate-800">
                                                {{ $matRow['label'] }}
                                                @if ($matRow['arab_label'] !== '')
                                                    <span class="t2q-arab-inline text-muted ml-1">{{ $matRow['arab_label'] }}</span>
                                                @endif
                                            </span>
                                            <span class="text-muted small" style="font-variant-numeric: tabular-nums;">
                                                <strong class="text-slate-800">{{ $matRow['unique_students_count'] ?? 0 }}</strong> siswa
                                                @if ($isTahsin && $matRow['latest_student_count'] > 0)
                                                    ({{ $matRow['latest_student_count'] }} terkini)
                                                @else
                                                    ({{ $matRow['recorded_count'] }}x)
                                                @endif
                                                &bull;
                                                <span class="t2q-score-pill t2q-score-pill--{{ $matRow['avg_tier'] }}">
                                                    Ø {{ $matRow['avg_formatted'] }}
                                                </span>
                                            </span>
                                        </div>
                                        <div class="t2q-st-meter-track">
                                            <div class="t2q-st-meter-fill t2q-st-meter-fill--material"
                                                style="width: {{ $matRow['width_pct'] }}%;"></div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL REKAP DETAIL: PER TINGKAT / PER GURU / PER SISWA -->
        <div class="t2q-surface overflow-hidden mb-3">
            <div class="px-4 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between"
                style="gap: 0.75rem; border-color: #f1f5f9 !important;">
                <div class="t2q-tingkat-tabs" role="tablist">
                    <button type="button" class="t2q-tingkat-tab is-active" data-st-tab="tingkat" role="tab">
                        <span>Per Tingkat</span>
                        <span class="t2q-tingkat-count">{{ count($tingkatRows) }}</span>
                    </button>
                    @if (! $isGuruT2qScope)
                        <button type="button" class="t2q-tingkat-tab" data-st-tab="guru" role="tab">
                            <span>Per Guru T2Q</span>
                            <span class="t2q-tingkat-count">{{ count($guruRows) }}</span>
                        </button>
                    @endif
                    <button type="button" class="t2q-tingkat-tab" data-st-tab="siswa" role="tab">
                        <span>Per Siswa</span>
                        <span class="t2q-tingkat-count">{{ count($studentRows) }}</span>
                    </button>
                </div>

                <div class="d-none align-items-center flex-wrap" id="t2qStatStudentSearchWrap" style="gap: 0.5rem;">
                    <div class="t2q-view-switch" role="group" aria-label="Filter status target siswa">
                        <button type="button" class="t2q-view-pill is-active" data-st-target-filter="all">Semua</button>
                        <button type="button" class="t2q-view-pill" data-st-target-filter="tercapai">Tercapai</button>
                        <button type="button" class="t2q-view-pill" data-st-target-filter="belum_tercapai">Belum</button>
                    </div>
                    <div class="t2q-search-box">
                        <i class="fa fa-search"></i>
                        <input type="text" id="t2qStatStudentSearch" placeholder="Cari siswa / NIS / kelas...">
                    </div>
                </div>
            </div>

            <!-- PANEL 1: PER TINGKAT -->
            <div class="t2q-st-tab-panel is-active" data-st-panel="tingkat">
                @if ($tingkatRows === [])
                    <div class="text-center py-5 px-4 text-muted small">Belum ada anggota T2Q terdaftar pada Tahun Ajaran ini.</div>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0 t2q-st-table">
                            <thead>
                                <tr>
                                    <th>Tingkat</th>
                                    <th>Target Capaian TA</th>
                                    <th class="text-center">Tercapai / Belum</th>
                                    <th class="text-center">Siswa</th>
                                    <th class="text-center">Aktif Setor</th>
                                    <th class="text-center">Total Setoran</th>
                                    <th class="text-center">% Sangat Baik</th>
                                    <th class="text-center">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tingkatRows as $tRow)
                                    <tr>
                                        <td class="font-weight-bold text-slate-900">Tingkat {{ $tRow['tingkat'] }}</td>
                                        <td>
                                            @if ($tRow['target_labels'] !== [])
                                                <div class="t2q-target-strip">
                                                    @foreach ($tRow['target_labels'] as $targetLabel)
                                                        <span class="t2q-target-pill">{{ $targetLabel }}</span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-muted small font-italic">Belum ada target</span>
                                            @endif
                                        </td>
                                        <td class="text-center" style="font-variant-numeric: tabular-nums;">
                                            <span class="t2q-score-pill t2q-score-pill--excellent">{{ $tRow['achieved_count'] }}</span>
                                            <span class="text-muted mx-1">/</span>
                                            <span class="t2q-score-pill t2q-score-pill--fair">{{ $tRow['unachieved_count'] }}</span>
                                        </td>
                                        <td class="text-center font-weight-semibold">{{ $tRow['student_count'] }}</td>
                                        <td class="text-center">{{ $tRow['active_students'] }}</td>
                                        <td class="text-center font-weight-bold text-slate-900">{{ $tRow['recorded_count'] }}</td>
                                        <td class="text-center">{{ $tRow['excellent_pct'] }}%</td>
                                        <td class="text-center">
                                            <span class="t2q-score-pill t2q-score-pill--{{ $tRow['avg_tier'] }}">
                                                {{ $tRow['avg_formatted'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            @if (! $isGuruT2qScope)
                <!-- PANEL 2: PER GURU -->
                <div class="t2q-st-tab-panel" data-st-panel="guru">
                @if ($guruRows === [])
                    <div class="text-center py-5 px-4 text-muted small">Belum ada guru pembimbing T2Q tercatat pada Tahun Ajaran ini.</div>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0 t2q-st-table">
                            <thead>
                                <tr>
                                    <th>Guru Pembimbing T2Q</th>
                                    <th>Tingkat Diampu</th>
                                    <th class="text-center">Siswa Binaan</th>
                                    <th class="text-center">Aktif Setor</th>
                                    <th class="text-center">Total Setoran</th>
                                    <th class="text-center">% Sangat Baik</th>
                                    <th class="text-center">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($guruRows as $gRow)
                                    <tr>
                                        <td class="font-weight-bold text-slate-900">{{ $gRow['nama_guru'] }}</td>
                                        <td>
                                            @if (($gRow['tingkat_list'] ?? []) !== [])
                                                <span class="small text-muted">Tingkat {{ implode(', ', $gRow['tingkat_list']) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center font-weight-semibold">{{ $gRow['student_count'] }}</td>
                                        <td class="text-center">{{ $gRow['active_students'] }}</td>
                                        <td class="text-center font-weight-bold text-slate-900">{{ $gRow['recorded_count'] }}</td>
                                        <td class="text-center">{{ $gRow['excellent_pct'] }}%</td>
                                        <td class="text-center">
                                            <span class="t2q-score-pill t2q-score-pill--{{ $gRow['avg_tier'] }}">
                                                {{ $gRow['avg_formatted'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
                </div>
            @endif

            <!-- PANEL 3: PER SISWA -->
            <div class="t2q-st-tab-panel" data-st-panel="siswa">
                @if ($studentRows === [])
                    <div class="text-center py-5 px-4 text-muted small">Belum ada siswa T2Q pada Tahun Ajaran ini.</div>
                @else
                    <div class="table-responsive">
                        <table class="table mb-0 t2q-st-table">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 48px;">No</th>
                                    <th>Siswa</th>
                                    <th>Tingkat &amp; Guru</th>
                                    <th>Status Target</th>
                                    <th>Capaian Terakhir</th>
                                    <th class="text-center">Setoran</th>
                                    <th class="text-center">Min – Max</th>
                                    <th class="text-center">Rata-rata</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($studentRows as $sIdx => $sRow)
                                    @php
                                        $meta = $extractStudentMeta($sRow['anggota']);
                                        $targetPillTone = match ($sRow['target_status'] ?? 'tanpa_target') {
                                            'tercapai' => 'excellent',
                                            'belum_tercapai' => 'fair',
                                            default => 'neutral',
                                        };
                                    @endphp
                                    <tr data-st-student-row
                                        data-search-text="{{ $meta['search'] }}"
                                        data-target-status="{{ $sRow['target_status'] ?? 'tanpa_target' }}">
                                        <td class="text-center text-muted font-weight-semibold">{{ $sIdx + 1 }}</td>
                                        <td>
                                            <div class="font-weight-bold text-slate-900">{{ $meta['nama'] }}</div>
                                            <div class="small text-muted" style="font-size: 0.72rem;">
                                                {{ $meta['nis'] }}
                                                @if ($meta['kelas'] !== '')
                                                    &bull; {{ $meta['kelas'] }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            <div class="font-weight-semibold text-slate-800">Tingkat {{ $meta['tingkat'] }}</div>
                                            @if ($meta['guru'] !== '')
                                                <div class="small text-muted" style="font-size: 0.72rem;">{{ $meta['guru'] }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="t2q-score-pill t2q-score-pill--{{ $targetPillTone }}">
                                                {{ $sRow['target_status_label'] ?? 'Belum Ada Target' }}
                                            </span>
                                            <div class="small text-muted mt-1" style="font-size: 0.71rem;">
                                                {{ $sRow['target_detail'] ?? '-' }}
                                            </div>
                                        </td>
                                        <td>
                                            @if ($sRow['recorded_count'] > 0)
                                                <div class="font-weight-semibold text-slate-800">{{ $sRow['latest_ringkasan'] }}</div>
                                                <div class="small text-muted" style="font-size: 0.71rem;">{{ $sRow['latest_tanggal_label'] }}</div>
                                            @else
                                                <span class="small text-muted font-italic">Belum pernah setor</span>
                                            @endif
                                        </td>
                                        <td class="text-center font-weight-bold text-slate-900">{{ $sRow['recorded_count'] }}</td>
                                        <td class="text-center text-muted" style="font-variant-numeric: tabular-nums;">
                                            {{ $sRow['min_formatted'] }} – {{ $sRow['max_formatted'] }}
                                        </td>
                                        <td class="text-center">
                                            <span class="t2q-score-pill t2q-score-pill--{{ $sRow['avg_tier'] }}">
                                                {{ $sRow['avg_formatted'] }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.getElementById('t2qStatistikFilterForm');
            if (filterForm) {
                const taSelect = document.getElementById('st_filter_ta');
                const kelasSelect = document.getElementById('st_filter_kelas');
                filterForm.querySelectorAll('.js-st-auto-submit').forEach(function (el) {
                    el.addEventListener('change', function () {
                        if (el === taSelect && kelasSelect) {
                            kelasSelect.value = '';
                        }
                        filterForm.submit();
                    });
                });
            }

            const tabButtons = document.querySelectorAll('[data-st-tab]');
            const tabPanels = document.querySelectorAll('[data-st-panel]');
            const searchWrap = document.getElementById('t2qStatStudentSearchWrap');
            const searchInput = document.getElementById('t2qStatStudentSearch');
            const targetSearchInput = document.getElementById('t2qTargetStudentSearch');
            const targetFilterButtons = document.querySelectorAll('[data-st-target-filter]');
            let activeTargetFilter = 'all';

            function applyStudentTableFilters() {
                const term = searchInput ? searchInput.value.trim().toLowerCase() : '';
                document.querySelectorAll('tr[data-st-student-row]').forEach(function (row) {
                    const haystack = row.getAttribute('data-search-text') || '';
                    const rowStatus = row.getAttribute('data-target-status') || '';
                    const matchesText = term === '' || haystack.includes(term);
                    const matchesStatus = activeTargetFilter === 'all' || rowStatus === activeTargetFilter;
                    row.style.display = (matchesText && matchesStatus) ? '' : 'none';
                });
            }

            tabButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const target = btn.getAttribute('data-st-tab');
                    tabButtons.forEach(function (item) {
                        item.classList.toggle('is-active', item.getAttribute('data-st-tab') === target);
                    });
                    tabPanels.forEach(function (panel) {
                        panel.classList.toggle('is-active', panel.getAttribute('data-st-panel') === target);
                    });
                    if (searchWrap) {
                        searchWrap.classList.toggle('d-none', target !== 'siswa');
                        searchWrap.classList.toggle('d-flex', target === 'siswa');
                    }
                });
            });

            targetFilterButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activeTargetFilter = btn.getAttribute('data-st-target-filter') || 'all';
                    targetFilterButtons.forEach(function (item) {
                        item.classList.toggle('is-active', item.getAttribute('data-st-target-filter') === activeTargetFilter);
                    });
                    applyStudentTableFilters();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', applyStudentTableFilters);
            }

            if (targetSearchInput) {
                targetSearchInput.addEventListener('input', function () {
                    const term = targetSearchInput.value.trim().toLowerCase();
                    document.querySelectorAll('[data-st-target-card]').forEach(function (card) {
                        const haystack = card.getAttribute('data-search-text') || '';
                        card.style.display = (term === '' || haystack.includes(term)) ? '' : 'none';
                    });
                });
            }
        });
    </script>
@endpush

