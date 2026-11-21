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
            <div id="wkTabPanelLeger" data-wk-panel="leger" role="tabpanel">
                <div class="wk-surface">
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

                <div class="p-3 p-md-4">
                    <div class="table-responsive">
                        <table class="mb-0 table table-hover align-middle datatable-ordered" id="wkPerkembanganTable"
                            data-search-placeholder="Cari nama siswa..."
                            data-empty-table="Belum ada siswa terdaftar di kelas {{ $selectedKelas->nama_kelas }}.">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 46px;" data-orderable="false" data-searchable="false">No</th>
                                    <th>Siswa</th>
                                    <th class="text-center" data-searchable="false">Kehadiran</th>
                                    @if ($isSd)
                                        @foreach ($mapels as $mp)
                                            <th class="text-center" data-searchable="false" title="{{ $mp->nama_mata_pelajaran }}">
                                                {{ $mp->singkatan ?: \Illuminate\Support\Str::limit($mp->nama_mata_pelajaran, 8) }}
                                            </th>
                                        @endforeach
                                        <th class="text-center" data-searchable="false">Ketuntasan</th>
                                        <th class="text-center" data-searchable="false">Rata NA</th>
                                    @endif
                                    <th class="text-center" style="width: 72px; text-align: center;" data-orderable="false" data-searchable="false">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($studentRows as $idx => $row)
                                    @php
                                        $anggota = $row['anggota'];
                                        $siswa = $anggota->siswa;
                                        $siswaName = $siswa?->nama_lengkap ?? $anggota->siswa_nis;
                                        $presensi = $row['presensi'];
                                        $detailUrl = route($perkembanganRoutePrefix.'.show', ['anggota_kelas' => $anggota, 'periode' => $periode]);
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted font-weight-semibold">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center" style="gap: 0.65rem;">
                                                @if ($siswa?->avatar_url)
                                                    <img src="{{ $siswa->avatar_url }}" alt="{{ $siswaName }}" class="wk-avatar-thumb">
                                                @else
                                                    <div class="wk-avatar-fallback">
                                                        {{ $siswa?->inisial_nama ?? 'S' }}
                                                    </div>
                                                @endif
                                                <div style="min-width: 0;">
                                                    <a href="{{ $detailUrl }}" class="font-weight-bold text-dark text-decoration-none d-block"
                                                        style="font-size: 0.83rem;">
                                                        {{ $siswaName }}
                                                    </a>
                                                    <div class="small text-muted" style="font-size: 0.7rem;">
                                                        NIS: {{ $anggota->siswa_nis }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="font-weight-bold" style="font-size: 0.8rem; color: {{ ($presensi['hadir_rate'] ?? 100) < 85 ? '#b91c1c' : '#047857' }};">
                                                {{ $presensi['hadir_rate'] !== null ? $presensi['hadir_rate'].'%' : '-' }}
                                            </div>
                                            <div class="text-muted" style="font-size: 0.66rem;">
                                                H:{{ $presensi['hadir'] }} T:{{ $presensi['terlambat'] }} I:{{ $presensi['izin'] }} S:{{ $presensi['sakit'] }} A:{{ $presensi['alpa'] }}
                                            </div>
                                        </td>
                                        @if ($isSd)
                                            @foreach ($mapels as $mp)
                                                @php
                                                    $cell = $row['mapel_cells'][$mp->id] ?? ['nilai' => null, 'predikat' => null, 'tuntas' => null];
                                                @endphp
                                                <td class="text-center">
                                                    @if ($cell['nilai'] !== null)
                                                        <span class="badge px-2 py-1"
                                                            style="font-size: 0.73rem; font-weight: 700; background: {{ $cell['tuntas'] === false ? '#fee2e2' : '#dcfce7' }}; color: {{ $cell['tuntas'] === false ? '#991b1b' : '#166534' }};">
                                                            {{ rtrim(rtrim(number_format((float) $cell['nilai'], 1, '.', ''), '0'), '.') }}
                                                            @if ($cell['predikat'])
                                                                ({{ $cell['predikat'] }})
                                                            @endif
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="text-center">
                                                @if ($row['dinilai_count'] > 0)
                                                    <span class="badge {{ $row['belum_tuntas_count'] > 0 ? 'badge-warning text-dark' : 'badge-success' }} px-2 py-1" style="font-size: 0.7rem;">
                                                        {{ $row['tuntas_count'] }}/{{ $row['dinilai_count'] }} Tuntas
                                                    </span>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center font-weight-bold text-dark" style="font-size: 0.85rem;">
                                                {{ $row['rata_rapor'] !== null ? number_format((float) $row['rata_rapor'], 1) : '-' }}
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
                })();
            </script>
        @endPushOnce
    @endif
@endsection

