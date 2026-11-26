@extends('layouts.appui')

@section('title')
    <title>{{ ($isGuruMapelScope ?? false) ? 'Perkembangan Siswa Guru Mapel SD' : 'Perkembangan Siswa Wali Kelas' }} - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @pushOnce('css')
        <style>
            .wk-mapel-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 0.55rem;
            }

            @media (min-width: 640px) {
                .wk-mapel-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1024px) {
                .wk-mapel-grid {
                    grid-template-columns: repeat(3, minmax(0, 1fr));
                }
            }

            @media (min-width: 1320px) {
                .wk-mapel-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .wk-mapel-tile {
                position: relative;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.65rem 0.78rem;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 0.45rem;
                transition: border-color 0.15s ease, box-shadow 0.15s ease, transform 0.15s ease;
                overflow: hidden;
            }

            .wk-mapel-tile:hover {
                border-color: #cbd5e1;
                box-shadow: 0 4px 12px rgba(15, 23, 42, 0.05);
                transform: translateY(-1px);
            }

            .wk-mapel-tile.is-mine {
                border-color: #fed7aa;
                background: linear-gradient(180deg, #fffaf5 0%, #ffffff 100%);
            }

            .wk-mapel-code {
                display: inline-flex;
                align-items: center;
                padding: 0.14rem 0.42rem;
                border-radius: 6px;
                font-size: 0.65rem;
                font-weight: 800;
                letter-spacing: 0.02em;
                background: #f1f5f9;
                color: #334155;
                flex-shrink: 0;
            }

            .wk-mapel-tile.is-mine .wk-mapel-code {
                background: #ffedd5;
                color: #c2410c;
            }

            .wk-mapel-name {
                font-size: 0.79rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.25;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .wk-mapel-teacher {
                font-size: 0.7rem;
                color: #64748b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .wk-mapel-foot {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.4rem;
                padding-top: 0.42rem;
                border-top: 1px dashed #e2e8f0;
                font-size: 0.71rem;
                font-variant-numeric: tabular-nums;
            }

            .wk-mapel-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.14rem 0.48rem;
                border-radius: 999px;
                font-size: 0.66rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .wk-mapel-bar {
                position: absolute;
                left: 0;
                right: 0;
                bottom: 0;
                height: 2.5px;
                background: #f1f5f9;
            }

            .wk-mapel-bar > span {
                display: block;
                height: 100%;
                border-radius: 999px;
            }

            .wk-subtabs {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.4rem;
                padding: 0.35rem;
                margin-bottom: 0.9rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 13px;
                width: fit-content;
                max-width: 100%;
            }

            .wk-subtab-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.48rem 0.9rem;
                border-radius: 10px;
                border: 0;
                background: transparent;
                color: #475569;
                font-size: 0.8rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.15s ease;
                white-space: nowrap;
            }

            .wk-subtab-btn:hover {
                color: #0f172a;
                background: rgba(255, 255, 255, 0.55);
            }

            .wk-subtab-btn.is-active {
                background: #ffffff;
                color: #c2410c;
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.08);
            }

            .wk-subtab-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.12rem 0.48rem;
                border-radius: 999px;
                font-size: 0.68rem;
                font-weight: 800;
            }

            .wk-controlbar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 0.65rem 0.95rem;
                margin-bottom: 1.05rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem;
            }

            .wk-periode-seg {
                display: inline-flex;
                align-items: center;
                padding: 3px;
                border-radius: 10px;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                gap: 2px;
                flex-shrink: 0;
            }

            .wk-periode-btn {
                padding: 0.34rem 0.8rem;
                border-radius: 7px;
                font-size: 0.76rem;
                font-weight: 700;
                color: #64748b;
                text-decoration: none !important;
                transition: all 0.15s ease;
                white-space: nowrap;
            }

            .wk-periode-btn:hover {
                color: #0f172a;
                background: rgba(255, 255, 255, 0.7);
            }

            .wk-periode-btn.is-active {
                background: #ffffff;
                color: #c2410c;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            }

            /* === LEGER SISWA: RAPIH UNTUK BANYAK SISWA & BANYAK MAPEL === */
            .wk-leger-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem;
                padding: 0.72rem 1.1rem;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
            }

            .wk-leger-filters {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.38rem;
            }

            .wk-leger-filter-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.3rem 0.72rem;
                border-radius: 999px;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #475569;
                font-size: 0.73rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.14s ease;
                white-space: nowrap;
            }

            .wk-leger-filter-pill:hover {
                border-color: #94a3b8;
                color: #0f172a;
            }

            .wk-leger-filter-pill.is-active {
                background: #0f172a;
                border-color: #0f172a;
                color: #ffffff;
            }

            .wk-leger-filter-pill.is-alert.is-active {
                background: #dc2626;
                border-color: #dc2626;
                color: #ffffff;
            }

            .wk-leger-filter-count {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 18px;
                height: 18px;
                padding: 0 0.34rem;
                border-radius: 999px;
                font-size: 0.65rem;
                font-weight: 800;
                background: #f1f5f9;
                color: #334155;
            }

            .wk-leger-filter-pill.is-active .wk-leger-filter-count {
                background: rgba(255, 255, 255, 0.22);
                color: #ffffff;
            }

            .wk-density-seg {
                display: inline-flex;
                align-items: center;
                padding: 2px;
                border-radius: 9px;
                background: #e2e8f0;
                gap: 2px;
            }

            .wk-density-btn {
                border: 0;
                background: transparent;
                color: #475569;
                font-size: 0.71rem;
                font-weight: 700;
                padding: 0.24rem 0.65rem;
                border-radius: 7px;
                cursor: pointer;
                transition: all 0.14s ease;
                white-space: nowrap;
            }

            .wk-density-btn.is-active {
                background: #ffffff;
                color: #0f172a;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            }

            .wk-leger-legend {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem 1rem;
                padding: 0.52rem 1.1rem;
                background: #ffffff;
                border-bottom: 1px solid #f1f5f9;
                font-size: 0.71rem;
                color: #64748b;
            }

            .wk-legend-items {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.75rem;
            }

            .wk-legend-dot {
                display: inline-block;
                width: 9px;
                height: 9px;
                border-radius: 3px;
                margin-right: 0.28rem;
                vertical-align: -1px;
            }

            .wk-leger-body {
                padding: 0.85rem 1.1rem 1rem;
            }

            .wk-leger-table-slot:not(:has(> .dataTables_wrapper)) {
                overflow: auto;
                max-height: 66vh;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                background: #ffffff;
            }

            .wk-leger-scroll {
                grid-column: 1 / -1 !important;
                width: 100% !important;
                max-height: 66vh;
                overflow: auto;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                background: #ffffff;
                box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.6);
            }

            #wkPerkembanganTable,
            #wkPerkembanganTable_wrapper table.dataTable {
                border-collapse: separate !important;
                border-spacing: 0 !important;
                margin: 0 !important;
                border: 0 !important;
                border-radius: 0 !important;
                overflow: visible !important;
                width: 100% !important;
                font-variant-numeric: tabular-nums;
            }

            #wkPerkembanganTable thead th,
            #wkPerkembanganTable_wrapper table.dataTable thead th {
                position: sticky;
                top: 0;
                z-index: 15;
                background: #f8fafc !important;
                color: #475569 !important;
                font-size: 0.68rem !important;
                font-weight: 800 !important;
                text-transform: uppercase;
                letter-spacing: 0.03em !important;
                padding: 0.58rem 0.65rem !important;
                border-top: 0 !important;
                border-bottom: 1.5px solid #cbd5e1 !important;
                white-space: nowrap !important;
                vertical-align: middle !important;
            }

            #wkPerkembanganTable_wrapper table.dataTable thead th.sorting,
            #wkPerkembanganTable_wrapper table.dataTable thead th.sorting_asc,
            #wkPerkembanganTable_wrapper table.dataTable thead th.sorting_desc {
                padding-right: 1.45rem !important;
            }

            #wkPerkembanganTable thead th.wk-col-mapel,
            #wkPerkembanganTable_wrapper table.dataTable thead th.wk-col-mapel {
                min-width: 74px;
                text-align: center;
                padding-left: 0.55rem !important;
            }

            #wkPerkembanganTable thead th.wk-col-mapel.is-mine-col,
            #wkPerkembanganTable_wrapper table.dataTable thead th.wk-col-mapel.is-mine-col {
                background: #fff7ed !important;
                color: #c2410c !important;
            }

            .wk-th-kkm {
                display: block;
                font-size: 0.58rem;
                font-weight: 600;
                color: #94a3b8;
                text-transform: none;
                letter-spacing: 0;
                margin-top: 1px;
            }

            #wkPerkembanganTable tbody td,
            #wkPerkembanganTable_wrapper table.dataTable tbody td {
                background-color: #ffffff;
                padding: 0.52rem 0.58rem !important;
                border-top: 0 !important;
                border-bottom: 1px solid #f1f5f9 !important;
                vertical-align: middle !important;
                transition: background-color 0.12s ease;
            }

            #wkPerkembanganTable tbody tr:last-child td {
                border-bottom: 0 !important;
            }

            #wkPerkembanganTable tbody tr:nth-child(even) td {
                background-color: #fafbfc;
            }

            #wkPerkembanganTable tbody tr:hover td {
                background-color: #fff7ed !important;
            }

            .wk-student-name-link {
                font-size: 0.81rem;
                font-weight: 700;
                color: #0f172a;
                text-decoration: none !important;
                display: block;
                max-width: 175px;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                line-height: 1.25;
            }

            .wk-student-name-link:hover {
                color: #c2410c;
            }

            .wk-student-nis {
                font-size: 0.68rem;
                color: #64748b;
                line-height: 1.2;
                margin-top: 1px;
            }

            .wk-att-rate {
                font-size: 0.79rem;
                font-weight: 800;
                line-height: 1.2;
            }

            .wk-att-detail {
                font-size: 0.63rem;
                color: #64748b;
                white-space: nowrap;
                letter-spacing: -0.01em;
                margin-top: 2px;
            }

            .wk-score-chip {
                display: inline-flex;
                align-items: baseline;
                justify-content: center;
                gap: 0.22rem;
                padding: 0.2rem 0.44rem;
                border-radius: 7px;
                font-size: 0.76rem;
                line-height: 1.15;
                white-space: nowrap;
                min-width: 52px;
            }

            .wk-score-chip.is-pass {
                background: #f0fdf4;
                border: 1px solid #bbf7d0;
                color: #166534;
                font-weight: 700;
            }

            .wk-score-chip.is-fail {
                background: #fef2f2;
                border: 1px solid #fca5a5;
                color: #b91c1c;
                font-weight: 800;
            }

            .wk-score-pred {
                font-size: 0.62rem;
                font-weight: 700;
                opacity: 0.82;
            }

            .wk-score-empty {
                color: #cbd5e1;
                font-weight: 600;
                font-size: 0.78rem;
            }

            .wk-tuntas-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.2rem 0.5rem;
                border-radius: 999px;
                font-size: 0.69rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .wk-tuntas-pill.is-ok {
                background: #ecfdf5;
                color: #047857;
                border: 1px solid #a7f3d0;
            }

            .wk-tuntas-pill.is-warn {
                background: #fef2f2;
                color: #b91c1c;
                border: 1px solid #fecaca;
            }

            .wk-rata-pill {
                display: inline-block;
                min-width: 44px;
                padding: 0.22rem 0.48rem;
                border-radius: 8px;
                background: #f1f5f9;
                color: #0f172a;
                font-size: 0.8rem;
                font-weight: 800;
            }

            /* Sticky Kolom Kiri (No & Siswa) + Kolom Kanan (Aksi) */
            @media (min-width: 640px) {
                #wkPerkembanganTable th:nth-child(1),
                #wkPerkembanganTable tbody td:not(.dataTables_empty):nth-child(1) {
                    position: sticky;
                    left: 0;
                    width: 44px;
                    min-width: 44px;
                    max-width: 44px;
                    text-align: center;
                }

                #wkPerkembanganTable th:nth-child(2),
                #wkPerkembanganTable tbody td:not(.dataTables_empty):nth-child(2) {
                    position: sticky;
                    left: 44px;
                    min-width: 215px;
                    max-width: 250px;
                    border-right: 1px solid #e2e8f0 !important;
                    box-shadow: 5px 0 10px -6px rgba(15, 23, 42, 0.14);
                }

                #wkPerkembanganTable tbody td:not(.dataTables_empty):nth-child(1),
                #wkPerkembanganTable tbody td:not(.dataTables_empty):nth-child(2) {
                    z-index: 8;
                }

                #wkPerkembanganTable thead th:nth-child(1),
                #wkPerkembanganTable thead th:nth-child(2) {
                    z-index: 25;
                }
            }

            @media (min-width: 768px) {
                #wkPerkembanganTable th:last-child,
                #wkPerkembanganTable tbody td:not(.dataTables_empty):last-child {
                    position: sticky;
                    right: 0;
                    min-width: 66px;
                    border-left: 1px solid #e2e8f0 !important;
                    box-shadow: -5px 0 10px -6px rgba(15, 23, 42, 0.12);
                }

                #wkPerkembanganTable tbody td:not(.dataTables_empty):last-child {
                    z-index: 8;
                }

                #wkPerkembanganTable thead th:last-child {
                    z-index: 24;
                }
            }

            /* Mode Padat untuk Kelas Besar & Banyak Mapel */
            .wk-leger-wrap.is-compact #wkPerkembanganTable thead th,
            .wk-leger-wrap.is-compact #wkPerkembanganTable_wrapper table.dataTable thead th {
                padding: 0.42rem 0.45rem !important;
                font-size: 0.63rem !important;
            }

            .wk-leger-wrap.is-compact #wkPerkembanganTable_wrapper table.dataTable thead th.sorting,
            .wk-leger-wrap.is-compact #wkPerkembanganTable_wrapper table.dataTable thead th.sorting_asc,
            .wk-leger-wrap.is-compact #wkPerkembanganTable_wrapper table.dataTable thead th.sorting_desc {
                padding-right: 1.3rem !important;
            }

            .wk-leger-wrap.is-compact #wkPerkembanganTable tbody td,
            .wk-leger-wrap.is-compact #wkPerkembanganTable_wrapper table.dataTable tbody td {
                padding: 0.34rem 0.42rem !important;
            }

            .wk-leger-wrap.is-compact .wk-avatar-thumb,
            .wk-leger-wrap.is-compact .wk-avatar-fallback {
                width: 28px;
                height: 28px;
                border-radius: 8px;
                font-size: 0.66rem;
            }

            .wk-leger-wrap.is-compact .wk-student-name-link {
                font-size: 0.77rem;
            }

            .wk-leger-wrap.is-compact .wk-score-chip {
                padding: 0.14rem 0.34rem;
                font-size: 0.72rem;
                min-width: 46px;
            }

            .wk-leger-wrap.is-compact .wk-att-detail {
                display: none;
            }
        </style>
    @endPushOnce

    @php
        $isEligible = $isEligible ?? false;
        $isSd = $isSd ?? false;
        $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $isMid = $periode === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $assignedClasses = $assignedClasses ?? collect();
        $students = $students ?? collect();
        $studentStats = $studentStats ?? ['total' => 0, 'laki' => 0, 'perempuan' => 0, 'aktif' => 0];
        $mapels = $mapels ?? collect();
        $mapelSummaries = $mapelSummaries ?? collect();
        $studentRows = $studentRows ?? collect();
        $overviewStats = $overviewStats ?? [
            'avg_rapor' => null,
            'tuntas_rate' => null,
            'scored_entries' => 0,
            'tuntas_entries' => 0,
            'need_attention_students' => 0,
            'avg_kehadiran' => null,
        ];
        $readiness = $readiness ?? null;
        $isGuruMapelScope = $isGuruMapelScope ?? false;
        $canPrintRapor = $canPrintRapor ?? ! $isGuruMapelScope;
        $perkembanganRoutePrefix = $perkembanganRoutePrefix ?? 'wali-kelas.perkembangan-siswa';
        $dashboardRouteName = $dashboardRouteName ?? 'wali-kelas.dashboard';
        $tpRoutePrefix = $tpRoutePrefix ?? 'wali-kelas.penilaian-tp';
        $canPrintNilai = $canPrintRapor && ($readiness === null || (bool) ($readiness['is_ready'] ?? false));
        $pendingCountRapor = (int) ($readiness['pending_count'] ?? 0);
    @endphp

    <div class="app-main__inner">
        @include('waliKelas.partials._hero', [
            'heroBadgeText' => $isEligible ? 'PERKEMBANGAN SISWA • '.strtoupper((string) $peranDiKelas) : 'PERKEMBANGAN SISWA',
            'heroTitle' => $isEligible && $selectedKelas ? 'Perkembangan Siswa Kelas '.$selectedKelas->nama_kelas : 'Perkembangan Hasil Belajar Siswa',
            'heroSubtitle' => $isEligible && $selectedKelas
                ? ($isGuruMapelScope
                    ? "Pantauan capaian mata pelajaran yang Anda ampu serta ringkasan kehadiran siswa kelas {$selectedKelas->nama_kelas} pada {$activeTahunAjaran->full_label}."
                    : "Pantauan menyeluruh capaian semua mata pelajaran dan kehadiran seluruh siswa kelas {$selectedKelas->nama_kelas} pada {$activeTahunAjaran->full_label}.")
                : null,
            'heroActionUrl' => route($dashboardRouteName, (! $isGuruMapelScope && $selectedKelas) ? ['kelas_id' => $selectedKelas->sqid] : []),
            'heroActionLabel' => 'Kembali ke Dashboard',
            'heroActionIcon' => 'fa-arrow-left',
        ])

        @if (! $isEligible)
            @include('waliKelas.partials._ineligible_guard')
        @else
            <div class="wk-controlbar">
                @if ($assignedClasses->count() > 1)
                    @include('waliKelas.partials._class_switcher', [
                        'switcherRoute' => $perkembanganRoutePrefix.'.index',
                        'switcherParams' => $isSd ? ['periode' => $periode] : [],
                        'isGuruMapelScope' => $isGuruMapelScope,
                        'switcherInline' => true,
                    ])
                @else
                    <div class="small text-muted font-weight-semibold">
                        <i class="fa fa-info-circle text-warning mr-1"></i>
                        {{ $isGuruMapelScope
                            ? 'Menampilkan capaian khusus mata pelajaran yang Anda ampu pada kelas ini.'
                            : 'Menampilkan ringkasan capaian semua mata pelajaran untuk peran' }}
                        @if (! $isGuruMapelScope)
                            <strong>{{ $peranDiKelas }}</strong>.
                        @endif
                    </div>
                @endif

                @if ($isSd)
                    <div class="wk-periode-seg" role="group" aria-label="Pilih Periode Perkembangan">
                        <a href="{{ route($perkembanganRoutePrefix.'.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => 'mid']) }}"
                            class="wk-periode-btn {{ $isMid ? 'is-active' : '' }}">
                            Mid Semester
                        </a>
                        <a href="{{ route($perkembanganRoutePrefix.'.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => 'semester']) }}"
                            class="wk-periode-btn {{ ! $isMid ? 'is-active' : '' }}">
                            Semester
                        </a>
                    </div>
                @endif
            </div>

            <!-- KPI RINGKASAN PERKEMBANGAN -->
            <div class="wk-kpi-grid">
                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #2563eb;"></span> {{ $isGuruMapelScope ? 'Total Siswa Kelas' : 'Total Siswa Binaan' }}
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
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #ea580c;"></span> {{ $isGuruMapelScope ? 'Rata-Rata Nilai Mapel' : 'Rata-Rata Nilai Kelas' }}
                        </div>
                        <div class="wk-kpi-value" style="color: #c2410c;">
                            {{ $overviewStats['avg_rapor'] !== null ? number_format((float) $overviewStats['avg_rapor'], 1) : '-' }}
                        </div>
                        <div class="wk-kpi-sub">
                            {{ $isSd ? ($isMid ? 'Periode Mid Semester' : 'Periode Semester') : 'Khusus presensi & biodata' }}
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-chart-line"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #10b981;"></span> Ketuntasan KKM
                        </div>
                        <div class="wk-kpi-value" style="color: #047857;">
                            {{ $overviewStats['tuntas_rate'] !== null ? $overviewStats['tuntas_rate'].'%' : '-' }}
                        </div>
                        <div class="wk-kpi-sub">
                            @if ($overviewStats['scored_entries'] > 0)
                                {{ $overviewStats['tuntas_entries'] }}/{{ $overviewStats['scored_entries'] }} capaian tuntas
                                @if ($overviewStats['need_attention_students'] > 0)
                                    &bull; <span class="text-danger font-weight-bold">{{ $overviewStats['need_attention_students'] }} siswa perlu pendampingan</span>
                                @endif
                            @else
                                Belum ada nilai rapor tercatat
                            @endif
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa fa-award"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Rata-Rata Kehadiran
                        </div>
                        <div class="wk-kpi-value" style="color: #6d28d9;">
                            {{ $overviewStats['avg_kehadiran'] !== null ? $overviewStats['avg_kehadiran'].'%' : '-' }}
                        </div>
                        <div class="wk-kpi-sub">
                            Rekap kehadiran efektif kelas {{ $selectedKelas->nama_kelas }}
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                        <i class="fa fa-user-check"></i>
                    </span>
                </div>
            </div>

            @php
                $hasMapelTab = $isSd && $mapelSummaries->isNotEmpty();
                $scoredMapelCount = $hasMapelTab
                    ? $mapelSummaries->filter(static fn (array $ms): bool => ($ms['dinilai_count'] ?? 0) > 0)->count()
                    : 0;
                $allTuntasMapelCount = $hasMapelTab
                    ? $mapelSummaries->filter(static fn (array $ms): bool => ($ms['dinilai_count'] ?? 0) > 0 && ($ms['belum_tuntas_count'] ?? 0) === 0)->count()
                    : 0;

                $hasReadinessTab = $isSd && $readiness !== null;
                $rdReadyTab = (bool) ($readiness['is_ready'] ?? false);
                $rdFinalizedTab = (int) ($readiness['finalized_count'] ?? 0);
                $rdTotalTab = (int) ($readiness['total_components'] ?? 0);
                $hasSubTabs = $hasMapelTab || $hasReadinessTab;
            @endphp

            @if ($hasSubTabs)
                <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-1">
                    <div class="wk-subtabs" role="tablist" aria-label="Tampilan Data Perkembangan">
                        <button type="button"
                            class="wk-subtab-btn is-active js-wk-tab-btn"
                            role="tab"
                            aria-selected="true"
                            aria-controls="wkTabPanelLeger"
                            data-wk-tab="leger">
                            <i class="fa fa-graduation-cap"></i>
                            <span>Leger Siswa</span>
                            <span class="wk-subtab-pill" style="background: #eff6ff; color: #1d4ed8;">
                                {{ $students->count() }}
                            </span>
                        </button>
                        @if ($hasMapelTab)
                            <button type="button"
                                class="wk-subtab-btn js-wk-tab-btn"
                                role="tab"
                                aria-selected="false"
                                aria-controls="wkTabPanelMapel"
                                data-wk-tab="mapel">
                                <i class="fa fa-book-open"></i>
                                <span>Ringkasan Mapel</span>
                                <span class="wk-subtab-pill"
                                    style="background: {{ $allTuntasMapelCount === $mapelSummaries->count() && $scoredMapelCount > 0 ? '#d1fae5' : '#fff7ed' }}; color: {{ $allTuntasMapelCount === $mapelSummaries->count() && $scoredMapelCount > 0 ? '#047857' : '#c2410c' }};">
                                    {{ $scoredMapelCount }}/{{ $mapelSummaries->count() }} Dinilai
                                </span>
                            </button>
                        @endif
                        @if ($hasReadinessTab)
                            <button type="button"
                                class="wk-subtab-btn js-wk-tab-btn"
                                role="tab"
                                aria-selected="false"
                                aria-controls="wkTabPanelKesiapan"
                                data-wk-tab="kesiapan">
                                <i class="fa fa-clipboard-check"></i>
                                <span>Kesiapan &amp; Finalisasi Rapor</span>
                                <span class="wk-subtab-pill"
                                    style="background: {{ $rdReadyTab ? '#d1fae5' : '#fef3c7' }}; color: {{ $rdReadyTab ? '#047857' : '#b45309' }};">
                                    {{ $rdFinalizedTab }}/{{ $rdTotalTab }} Final
                                </span>
                            </button>
                        @endif
                    </div>
                </div>
            @endif

            <!-- TAB 1: LEGER PERKEMBANGAN SELURUH SISWA -->
            @php
                $kkmByMapelId = $mapelSummaries
                    ->mapWithKeys(static fn (array $ms): array => [
                        (int) ($ms['mapel']->id ?? 0) => [
                            'kkm' => $ms['kkm'] ?? null,
                            'is_mine' => (bool) ($ms['is_taught_by_user'] ?? false),
                        ],
                    ])
                    ->all();
                $totalLedgerCount = $studentRows->count();
                $attentionLedgerCount = $isSd
                    ? $studentRows->filter(static fn (array $r): bool => (int) ($r['belum_tuntas_count'] ?? 0) > 0)->count()
                    : 0;
                $lowAttendanceLedgerCount = $studentRows->filter(
                    static fn (array $r): bool => ($r['presensi']['hadir_rate'] ?? null) !== null && (float) $r['presensi']['hadir_rate'] < 85
                )->count();
            @endphp
            <div id="wkTabPanelLeger" data-wk-panel="leger" role="tabpanel">
                <div class="wk-surface wk-leger-wrap" id="wkLegerWrap">
                    <div class="wk-surface-head">
                        <div>
                            <h6 class="wk-surface-title">
                                <i class="fa fa-graduation-cap text-primary mr-1"></i>
                                Leger Perkembangan Siswa Kelas {{ $selectedKelas->nama_kelas }}
                            </h6>
                            <div class="small text-muted">
                                @if ($canPrintRapor)
                                    Klik nama siswa atau tombol <strong>Detail</strong> untuk melihat perkembangan lengkap, atau cetak PDF Identitas &amp; Nilai Rapor terpisah.
                                @else
                                    Klik nama siswa atau tombol <strong>Detail</strong> untuk melihat capaian mapel yang Anda ampu serta ringkasan kehadirannya.
                                @endif
                            </div>
                        </div>
                        @if ($canPrintRapor && $students->isNotEmpty())
                            <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                                <a href="{{ route('wali-kelas.rapor.identitas.kelas', $selectedKelas) }}" target="_blank"
                                    class="btn btn-sm btn-outline-primary font-weight-semibold"
                                    style="border-radius: 8px; font-size: 0.74rem;">
                                    <i class="fa fa-id-card mr-1"></i> Identitas (1 Kelas)
                                </a>
                                @if ($isSd)
                                    @if ($canPrintNilai)
                                        <a href="{{ route('wali-kelas.rapor.nilai.kelas', ['kelas' => $selectedKelas, 'periode' => $periode]) }}" target="_blank"
                                            class="btn btn-sm text-white font-weight-semibold shadow-sm"
                                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border-radius: 8px; font-size: 0.74rem;">
                                            <i class="fa fa-file-pdf mr-1"></i> Nilai Rapor (1 Kelas)
                                        </a>
                                    @else
                                        <button type="button"
                                            class="btn btn-sm btn-secondary font-weight-semibold shadow-sm js-wk-tab-btn js-blocked-print-trigger"
                                            data-wk-tab="kesiapan"
                                            data-blocked-toast="Cetak Nilai Rapor belum dapat dilakukan: masih ada {{ $pendingCountRapor }} komponen yang belum difinalkan."
                                            style="border-radius: 8px; font-size: 0.74rem; opacity: 0.88;"
                                            title="Cetak Nilai Rapor diblokir: masih ada {{ $pendingCountRapor }} komponen belum difinalkan. Klik untuk cek tab Kesiapan Rapor.">
                                            <i class="fa fa-lock mr-1"></i> Nilai Rapor ({{ $pendingCountRapor }} Belum Final)
                                        </button>
                                    @endif
                                @endif
                            </div>
                        @endif
                    </div>

                    @if ($totalLedgerCount > 0)
                        <div class="wk-leger-toolbar">
                            <div class="wk-leger-filters" role="group" aria-label="Filter Cepat Leger Siswa">
                                <button type="button" class="wk-leger-filter-pill is-active js-wk-leger-filter" data-filter="all">
                                    <span>Semua Siswa</span>
                                    <span class="wk-leger-filter-count">{{ $totalLedgerCount }}</span>
                                </button>
                                @if ($isSd)
                                    <button type="button" class="wk-leger-filter-pill is-alert js-wk-leger-filter" data-filter="attention">
                                        <i class="fa fa-exclamation-triangle" style="font-size: 0.65rem;"></i>
                                        <span>Perlu Pendampingan</span>
                                        <span class="wk-leger-filter-count">{{ $attentionLedgerCount }}</span>
                                    </button>
                                @endif
                                <button type="button" class="wk-leger-filter-pill is-alert js-wk-leger-filter" data-filter="low_attendance">
                                    <i class="fa fa-user-clock" style="font-size: 0.65rem;"></i>
                                    <span>Kehadiran &lt; 85%</span>
                                    <span class="wk-leger-filter-count">{{ $lowAttendanceLedgerCount }}</span>
                                </button>
                            </div>

                            <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                <span class="small text-muted font-weight-semibold" style="font-size: 0.7rem;">Tampilan:</span>
                                <div class="wk-density-seg" role="group" aria-label="Kerapatan Tabel Leger">
                                    <button type="button" class="wk-density-btn is-active js-wk-density-btn" data-density="comfortable">
                                        Nyaman
                                    </button>
                                    <button type="button" class="wk-density-btn js-wk-density-btn" data-density="compact">
                                        Padat
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="wk-leger-legend">
                            <div class="wk-legend-items">
                                @if ($isSd)
                                    <span><span class="wk-legend-dot" style="background: #dcfce7; border: 1px solid #86efac;"></span>Tuntas KKM</span>
                                    <span><span class="wk-legend-dot" style="background: #fee2e2; border: 1px solid #fca5a5;"></span>Perlu Pendampingan (&lt; KKM)</span>
                                    <span><span class="text-muted font-weight-bold mr-1">&mdash;</span>Belum Dinilai</span>
                                @else
                                    <span><span class="wk-legend-dot" style="background: #dcfce7; border: 1px solid #86efac;"></span>Kehadiran &ge; 85%</span>
                                    <span><span class="wk-legend-dot" style="background: #fee2e2; border: 1px solid #fca5a5;"></span>Kehadiran &lt; 85%</span>
                                @endif
                            </div>
                            <div class="small text-muted" style="font-size: 0.69rem;">
                                <i class="fa fa-arrows-alt-h mr-1 text-warning"></i>
                                Kolom <strong>No &amp; Siswa</strong> terkunci di kiri saat tabel digeser
                            </div>
                        </div>
                    @endif

                    <div class="wk-leger-body">
                        <div class="wk-leger-table-slot">
                            <table class="mb-0 table table-hover align-middle datatable-ordered" id="wkPerkembanganTable"
                                data-search-placeholder="Cari nama atau NIS siswa..."
                                data-empty-table="Belum ada siswa terdaftar di kelas {{ $selectedKelas->nama_kelas }}.">
                                <thead>
                                    <tr>
                                        <th class="text-center" data-orderable="false" data-searchable="false">No</th>
                                        <th>Siswa</th>
                                        <th class="text-center" data-searchable="false" title="Persentase Kehadiran &amp; Rekap (H=Hadir, T=Terlambat, I=Izin, S=Sakit, A=Alpa)">Kehadiran</th>
                                        @if ($isSd)
                                            @foreach ($mapels as $mp)
                                                @php
                                                    $mpMeta = $kkmByMapelId[(int) $mp->id] ?? ['kkm' => null, 'is_mine' => false];
                                                    $kkmText = $mpMeta['kkm'] !== null ? rtrim(rtrim(number_format((float) $mpMeta['kkm'], 2, '.', ''), '0'), '.') : null;
                                                @endphp
                                                <th class="text-center wk-col-mapel {{ $mpMeta['is_mine'] ? 'is-mine-col' : '' }}"
                                                    data-searchable="false"
                                                    title="{{ $mp->nama_mata_pelajaran }}{{ $kkmText !== null ? ' • KKM '.$kkmText : '' }}">
                                                    <span>{{ $mp->singkatan ?: \Illuminate\Support\Str::limit($mp->nama_mata_pelajaran, 8) }}</span>
                                                    @if ($kkmText !== null)
                                                        <span class="wk-th-kkm">KKM {{ $kkmText }}</span>
                                                    @endif
                                                </th>
                                            @endforeach
                                            <th class="text-center" data-searchable="false">Ketuntasan</th>
                                            <th class="text-center" data-searchable="false">Rata NA</th>
                                        @endif
                                        <th class="text-center" style="text-align: center;" data-orderable="false" data-searchable="false">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($studentRows as $idx => $row)
                                        @php
                                            $anggota = $row['anggota'];
                                            $siswa = $anggota->siswa;
                                            $siswaName = $siswa?->nama_lengkap ?? $anggota->siswa_nis;
                                            $presensi = $row['presensi'];
                                            $hadirRate = $presensi['hadir_rate'] ?? null;
                                            $needsAttention = $isSd && (int) ($row['belum_tuntas_count'] ?? 0) > 0;
                                            $lowAttendance = $hadirRate !== null && (float) $hadirRate < 85;
                                            $detailUrl = route($perkembanganRoutePrefix.'.show', ['anggota_kelas' => $anggota, 'periode' => $periode]);
                                            $dinilaiCnt = (int) ($row['dinilai_count'] ?? 0);
                                            $tuntasCnt = (int) ($row['tuntas_count'] ?? 0);
                                            $belumTuntasCnt = (int) ($row['belum_tuntas_count'] ?? 0);
                                            $tuntasOrder = $dinilaiCnt > 0 ? round(($tuntasCnt / $dinilaiCnt) * 100, 2) : -1;
                                        @endphp
                                        <tr data-needs-attention="{{ $needsAttention ? '1' : '0' }}"
                                            data-low-attendance="{{ $lowAttendance ? '1' : '0' }}">
                                            <td class="text-center text-muted font-weight-semibold">{{ $idx + 1 }}</td>
                                            <td>
                                                <div class="d-flex align-items-center" style="gap: 0.55rem;">
                                                    @if ($siswa?->avatar_url)
                                                        <img src="{{ $siswa->avatar_url }}" alt="{{ $siswaName }}" class="wk-avatar-thumb">
                                                    @else
                                                        <div class="wk-avatar-fallback">
                                                            {{ $siswa?->inisial_nama ?? 'S' }}
                                                        </div>
                                                    @endif
                                                    <div style="min-width: 0; flex: 1;">
                                                        <a href="{{ $detailUrl }}" class="wk-student-name-link" title="{{ $siswaName }}">
                                                            {{ $siswaName }}
                                                        </a>
                                                        <div class="wk-student-nis">
                                                            NIS {{ $anggota->siswa_nis }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center"
                                                data-order="{{ $hadirRate !== null ? $hadirRate : -1 }}"
                                                title="Hadir: {{ $presensi['hadir'] }} • Terlambat: {{ $presensi['terlambat'] }} • Izin: {{ $presensi['izin'] }} • Sakit: {{ $presensi['sakit'] }} • Alpa: {{ $presensi['alpa'] }}">
                                                <div class="wk-att-rate" style="color: {{ $lowAttendance ? '#b91c1c' : '#047857' }};">
                                                    {{ $hadirRate !== null ? $hadirRate.'%' : '-' }}
                                                </div>
                                                <div class="wk-att-detail">
                                                    H{{ $presensi['hadir'] }}&bull;T{{ $presensi['terlambat'] }}&bull;I{{ $presensi['izin'] }}&bull;S{{ $presensi['sakit'] }}&bull;A{{ $presensi['alpa'] }}
                                                </div>
                                            </td>
                                            @if ($isSd)
                                                @foreach ($mapels as $mp)
                                                    @php
                                                        $cell = $row['mapel_cells'][$mp->id] ?? ['nilai' => null, 'predikat' => null, 'tuntas' => null];
                                                        $scoreDisplay = $cell['nilai'] !== null
                                                            ? rtrim(rtrim(number_format((float) $cell['nilai'], 1, '.', ''), '0'), '.')
                                                            : null;
                                                    @endphp
                                                    <td class="text-center" data-order="{{ $cell['nilai'] !== null ? $cell['nilai'] : -1 }}">
                                                        @if ($scoreDisplay !== null)
                                                            <span class="wk-score-chip {{ $cell['tuntas'] === false ? 'is-fail' : 'is-pass' }}"
                                                                title="{{ $mp->nama_mata_pelajaran }} &bull; Nilai {{ $scoreDisplay }}{{ $cell['predikat'] ? ' ('.$cell['predikat'].')' : '' }}{{ $cell['tuntas'] === false ? ' — Belum Tuntas KKM' : ' — Tuntas KKM' }}">
                                                                <span>{{ $scoreDisplay }}</span>
                                                                @if ($cell['predikat'])
                                                                    <span class="wk-score-pred">{{ $cell['predikat'] }}</span>
                                                                @endif
                                                            </span>
                                                        @else
                                                            <span class="wk-score-empty" title="{{ $mp->nama_mata_pelajaran }}: Belum ada nilai">&mdash;</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                                <td class="text-center" data-order="{{ $tuntasOrder }}">
                                                    @if ($dinilaiCnt > 0)
                                                        <span class="wk-tuntas-pill {{ $belumTuntasCnt > 0 ? 'is-warn' : 'is-ok' }}"
                                                            title="{{ $tuntasCnt }} dari {{ $dinilaiCnt }} mata pelajaran tuntas KKM">
                                                            {{ $tuntasCnt }}/{{ $dinilaiCnt }}
                                                            <span>{{ $belumTuntasCnt > 0 ? '('.$belumTuntasCnt.' Kurang)' : 'Tuntas' }}</span>
                                                        </span>
                                                    @else
                                                        <span class="wk-score-empty">&mdash;</span>
                                                    @endif
                                                </td>
                                                <td class="text-center" data-order="{{ $row['rata_rapor'] !== null ? $row['rata_rapor'] : -1 }}">
                                                    @if ($row['rata_rapor'] !== null)
                                                        <span class="wk-rata-pill">{{ number_format((float) $row['rata_rapor'], 1) }}</span>
                                                    @else
                                                        <span class="wk-score-empty">&mdash;</span>
                                                    @endif
                                                </td>
                                            @endif
                                            <td class="text-center">
                                                @if ($canPrintRapor)
                                                    <x-action-dropdown>
                                                        <a href="{{ $detailUrl }}" class="action-dropdown-item" role="menuitem">
                                                            <i class="fa fa-chart-area text-primary action-item-icon"></i>
                                                            <span>Detail Perkembangan</span>
                                                        </a>
                                                        <div class="action-dropdown-divider"></div>
                                                        <a href="{{ route('wali-kelas.rapor.identitas.siswa', $anggota) }}" target="_blank" class="action-dropdown-item" role="menuitem">
                                                            <i class="fa fa-id-card text-info action-item-icon"></i>
                                                            <span>Cetak Identitas (PDF)</span>
                                                        </a>
                                                        @if ($isSd)
                                                            @if ($canPrintNilai)
                                                                <a href="{{ route('wali-kelas.rapor.nilai.siswa', ['anggota_kelas' => $anggota, 'periode' => $periode]) }}" target="_blank" class="action-dropdown-item" role="menuitem">
                                                                    <i class="fa fa-file-pdf text-danger action-item-icon"></i>
                                                                    <span>Cetak Nilai Rapor (PDF)</span>
                                                                </a>
                                                            @else
                                                                <button type="button"
                                                                    class="action-dropdown-item text-muted js-wk-tab-btn js-blocked-print-trigger"
                                                                    role="menuitem"
                                                                    data-wk-tab="kesiapan"
                                                                    data-blocked-toast="Cetak Nilai Rapor belum dapat dilakukan: masih ada {{ $pendingCountRapor }} komponen yang belum difinalkan.">
                                                                    <i class="fa fa-lock text-warning action-item-icon"></i>
                                                                    <span>Cetak Nilai Rapor ({{ $pendingCountRapor }} Belum Final)</span>
                                                                </button>
                                                            @endif
                                                        @endif
                                                    </x-action-dropdown>
                                                @else
                                                    <a href="{{ $detailUrl }}" class="btn btn-xs btn-light border font-weight-semibold px-2 py-1"
                                                        style="border-radius: 7px; font-size: 0.7rem;">
                                                        Detail
                                                    </a>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            @if ($hasMapelTab)
                <!-- TAB 2: RINGKASAN MATA PELAJARAN -->
                <div id="wkTabPanelMapel" data-wk-panel="mapel" role="tabpanel" style="display: none;">
                    <div class="wk-surface" id="wkMapelSummaryCard">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-book-open text-warning mr-1"></i>
                                    Ringkasan Mata Pelajaran ({{ $isMid ? 'Mid Semester' : 'Semester' }})
                                </h6>
                                <div class="small text-muted">
                                    Pantauan rata-rata kelas, ketuntasan KKM, guru pengampu, dan status finalisasi tiap mata pelajaran.
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                                <span class="badge badge-light border px-2 py-1" style="font-size: 0.71rem; color: #475569;">
                                    {{ $mapelSummaries->count() }} Mapel &bull; {{ $scoredMapelCount }} Dinilai
                                </span>
                                @if ($scoredMapelCount > 0)
                                    <span class="wk-mapel-pill" style="background: #ecfdf5; color: #047857; font-size: 0.71rem; padding: 0.24rem 0.62rem;">
                                        {{ $allTuntasMapelCount }} Mapel 100% Tuntas
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="p-3 p-md-4" id="wkMapelSummaryBody">
                            <div class="wk-mapel-grid">
                                @foreach ($mapelSummaries as $ms)
                                    @php
                                        $mp = $ms['mapel'];
                                        $isMine = (bool) ($ms['is_taught_by_user'] ?? false);
                                        $dinilai = (int) ($ms['dinilai_count'] ?? 0);
                                        $tuntas = (int) ($ms['tuntas_count'] ?? 0);
                                        $belumTuntas = (int) ($ms['belum_tuntas_count'] ?? 0);
                                        $isFinalMapel = (bool) ($ms['is_final'] ?? false);
                                        $isRequiredForRapor = (bool) ($ms['is_required_for_rapor'] ?? false);
                                        $teacherText = $ms['teachers'] !== [] ? implode(', ', $ms['teachers']) : 'Belum dijadwalkan';
                                        $kkmClean = rtrim(rtrim(number_format((float) $ms['kkm'], 2, '.', ''), '0'), '.');
                                        $tuntasPct = $dinilai > 0 ? (int) round(($tuntas / $dinilai) * 100) : 0;
                                        $barColor = $dinilai === 0 ? '#cbd5e1' : ($belumTuntas > 0 ? '#ef4444' : '#10b981');
                                    @endphp
                                    <div class="wk-mapel-tile {{ $isMine ? 'is-mine' : '' }}">
                                        <div style="min-width: 0;">
                                            <div class="d-flex align-items-center justify-content-between" style="gap: 0.4rem; margin-bottom: 0.25rem;">
                                                <div class="d-flex align-items-center" style="gap: 0.4rem; min-width: 0;">
                                                    <span class="wk-mapel-code">{{ $mp->singkatan ?: 'MP' }}</span>
                                                    <span class="wk-mapel-name" title="{{ $mp->nama_mata_pelajaran }}">
                                                        {{ $mp->nama_mata_pelajaran }}
                                                    </span>
                                                </div>
                                                @if ($isMine)
                                                    <span class="wk-mapel-pill" style="background: #ffedd5; color: #c2410c; font-size: 0.62rem;" title="Mata pelajaran yang Anda ampu">
                                                        Mapel Anda
                                                    </span>
                                                @elseif ($isRequiredForRapor)
                                                    <span class="wk-mapel-pill"
                                                        style="background: {{ $isFinalMapel ? '#ecfdf5' : '#fffbeb' }}; color: {{ $isFinalMapel ? '#047857' : '#b45309' }}; font-size: 0.62rem;"
                                                        title="{{ $isFinalMapel ? 'Sudah difinalkan oleh '.($ms['finalized_by_name'] ?? 'Guru Pengampu') : 'Menunggu finalisasi dari guru pengampu' }}">
                                                        <i class="fa {{ $isFinalMapel ? 'fa-lock' : 'fa-clock' }}"></i>
                                                        {{ $isFinalMapel ? 'Final' : 'Draft' }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="d-flex align-items-center justify-content-between" style="gap: 0.35rem;">
                                                <div class="wk-mapel-teacher" title="{{ $teacherText }}" style="flex: 1 1 auto;">
                                                    <i class="fa fa-user-tie mr-1" style="font-size: 0.62rem; opacity: 0.7;"></i>{{ $teacherText }}
                                                </div>
                                                @if ($isMine && $isRequiredForRapor)
                                                    <span class="wk-mapel-pill"
                                                        style="background: {{ $isFinalMapel ? '#d1fae5' : '#fef3c7' }}; color: {{ $isFinalMapel ? '#047857' : '#b45309' }}; font-size: 0.6rem;">
                                                        <i class="fa {{ $isFinalMapel ? 'fa-lock' : 'fa-clock' }}"></i>
                                                        {{ $isFinalMapel ? 'Final' : 'Draft' }}
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="wk-mapel-foot">
                                            <div class="text-muted">
                                                NA <strong class="text-dark">{{ $ms['avg_rapor'] !== null ? number_format((float) $ms['avg_rapor'], 1) : '-' }}</strong>
                                                <span style="opacity: 0.5;">&bull;</span>
                                                <span>KKM {{ $kkmClean }}</span>
                                            </div>

                                            <div class="d-flex align-items-center" style="gap: 0.3rem;">
                                                @if ($dinilai > 0)
                                                    <span class="wk-mapel-pill"
                                                        style="background: {{ $belumTuntas > 0 ? '#fef2f2' : '#ecfdf5' }}; color: {{ $belumTuntas > 0 ? '#b91c1c' : '#047857' }};">
                                                        {{ $tuntas }}/{{ $dinilai }} Tuntas
                                                    </span>
                                                @else
                                                    <span class="wk-mapel-pill" style="background: #f1f5f9; color: #64748b;">
                                                        Belum dinilai
                                                    </span>
                                                @endif

                                                @if ($isMine)
                                                    <a href="{{ route($tpRoutePrefix.'.index', ['kelas_id' => $selectedKelas->sqid, 'mata_pelajaran_id' => $mp->sqid, 'periode' => $periode]) }}"
                                                        class="wk-mapel-pill text-decoration-none"
                                                        style="background: #ea580c; color: #ffffff;"
                                                        title="Input nilai {{ $mp->nama_mata_pelajaran }}">
                                                        Input
                                                    </a>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="wk-mapel-bar" aria-hidden="true">
                                            <span style="width: {{ $dinilai > 0 ? $tuntasPct : 0 }}%; background: {{ $barColor }};"></span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            @if ($hasReadinessTab)
                <!-- TAB 3: KESIAPAN & FINALISASI RAPOR -->
                <div id="wkTabPanelKesiapan" data-wk-panel="kesiapan" role="tabpanel" style="display: none;">
                    @include('admin.penilaian.partials._rapor_readiness_panel', [
                        'readiness' => $readiness,
                        'routePrefix' => 'wali-kelas.penilaian-finalisasi',
                        'periode' => $periode,
                        'collapseId' => 'wkRaporReadinessChecklistBody',
                        'defaultExpanded' => true,
                    ])
                </div>
            @endif
        @endif
    </div>

    @if ($isEligible)
        @pushOnce('js')
            <script>
                (function () {
                    const tabButtons = document.querySelectorAll('.wk-subtab-btn[data-wk-tab]');
                    const tabPanels = document.querySelectorAll('[data-wk-panel]');
                    const tabStorageKey = 'wk_perkembangan_active_tab';

                    function activateTab(tabName) {
                        if (tabPanels.length <= 1) return;

                        const targetPanel = Array.from(tabPanels).find(function (panel) {
                            return panel.dataset.wkPanel === tabName;
                        });
                        const resolvedTab = targetPanel ? tabName : 'leger';

                        tabPanels.forEach(function (panel) {
                            panel.style.display = panel.dataset.wkPanel === resolvedTab ? '' : 'none';
                        });

                        tabButtons.forEach(function (btn) {
                            const active = btn.dataset.wkTab === resolvedTab;
                            btn.classList.toggle('is-active', active);
                            btn.setAttribute('aria-selected', active ? 'true' : 'false');
                        });

                        if (resolvedTab === 'leger' && window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable) {
                            window.jQuery.fn.dataTable.tables({ visible: true, api: true }).columns.adjust();
                        }

                        try {
                            sessionStorage.setItem(tabStorageKey, resolvedTab);
                        } catch (e) {}
                    }

                    document.addEventListener('click', function (e) {
                        const trigger = e.target.closest('.js-wk-tab-btn[data-wk-tab]');
                        if (!trigger) return;
                        activateTab(trigger.dataset.wkTab);

                        const blockedToastMsg = trigger.dataset.blockedToast;
                        if (blockedToastMsg) {
                            if (window.toastr && typeof window.toastr.error === 'function') {
                                window.toastr.error(blockedToastMsg, 'Cetak Nilai Rapor Diblokir');
                            } else {
                                alert(blockedToastMsg);
                            }
                        }
                    });

                    try {
                        const savedTab = sessionStorage.getItem(tabStorageKey);
                        if (savedTab && savedTab !== 'leger') {
                            activateTab(savedTab);
                        }
                    } catch (e) {}

                    // === LEGER: SCROLL TERISOLASI, FILTER CEPAT, & MODE PADAT ===
                    const legerWrap = document.getElementById('wkLegerWrap');
                    const filterBtns = document.querySelectorAll('.js-wk-leger-filter');
                    const densityBtns = document.querySelectorAll('.js-wk-density-btn');
                    const densityStorageKey = 'wk_leger_density';
                    let activeLegerFilter = 'all';

                    function applyDensity(mode) {
                        if (!legerWrap) return;
                        const isCompact = mode === 'compact';
                        legerWrap.classList.toggle('is-compact', isCompact);
                        densityBtns.forEach(function (btn) {
                            btn.classList.toggle('is-active', btn.dataset.density === (isCompact ? 'compact' : 'comfortable'));
                        });
                        try {
                            sessionStorage.setItem(densityStorageKey, isCompact ? 'compact' : 'comfortable');
                        } catch (e) {}
                    }

                    densityBtns.forEach(function (btn) {
                        btn.addEventListener('click', function () {
                            applyDensity(btn.dataset.density);
                        });
                    });

                    try {
                        const savedDensity = sessionStorage.getItem(densityStorageKey);
                        if (savedDensity === 'compact') {
                            applyDensity('compact');
                        }
                    } catch (e) {}

                    function setupLegerEnhancements() {
                        const $tbl = window.jQuery ? window.jQuery('#wkPerkembanganTable') : null;
                        if ($tbl && $tbl.length && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable($tbl)) {
                            if (!$tbl.parent().hasClass('wk-leger-scroll')) {
                                $tbl.wrap('<div class="wk-leger-scroll"></div>');
                            }

                            const dtApi = $tbl.DataTable();
                            window.jQuery.fn.dataTable.ext.search.push(function (settings, data, dataIndex) {
                                if (!settings.nTable || settings.nTable.id !== 'wkPerkembanganTable') {
                                    return true;
                                }
                                if (activeLegerFilter === 'all') {
                                    return true;
                                }
                                const rowNode = dtApi.row(dataIndex).node();
                                if (!rowNode) return true;
                                if (activeLegerFilter === 'attention') {
                                    return rowNode.dataset.needsAttention === '1';
                                }
                                if (activeLegerFilter === 'low_attendance') {
                                    return rowNode.dataset.lowAttendance === '1';
                                }
                                return true;
                            });
                        }

                        filterBtns.forEach(function (btn) {
                            btn.addEventListener('click', function () {
                                activeLegerFilter = btn.dataset.filter || 'all';
                                filterBtns.forEach(function (pill) {
                                    pill.classList.toggle('is-active', pill === btn);
                                });

                                if ($tbl && $tbl.length && window.jQuery.fn.dataTable && window.jQuery.fn.dataTable.isDataTable($tbl)) {
                                    $tbl.DataTable().draw();
                                    return;
                                }

                                document.querySelectorAll('#wkPerkembanganTable tbody tr').forEach(function (tr) {
                                    const show = activeLegerFilter === 'all'
                                        || (activeLegerFilter === 'attention' && tr.dataset.needsAttention === '1')
                                        || (activeLegerFilter === 'low_attendance' && tr.dataset.lowAttendance === '1');
                                    tr.style.display = show ? '' : 'none';
                                });
                            });
                        });
                    }

                    if (window.jQuery) {
                        window.jQuery(setupLegerEnhancements);
                    } else if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', setupLegerEnhancements);
                    } else {
                        setupLegerEnhancements();
                    }
                })();
            </script>
        @endPushOnce
    @endif
@endsection

