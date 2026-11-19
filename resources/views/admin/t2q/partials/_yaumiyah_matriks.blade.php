@include('admin.t2q.partials._yaumiyah_shared_styles')

@push('css')
    <style>
        .t2q-mx-kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 0.85rem;
        }

        .t2q-mx-kpi {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.9rem 1.15rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 0.35rem;
        }

        .t2q-mx-kpi-label {
            font-size: 0.69rem;
            font-weight: 700;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: #64748b;
        }

        .t2q-mx-kpi-value {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.5rem;
            font-size: 1.12rem;
            font-weight: 800;
            color: #0f172a;
            font-variant-numeric: tabular-nums;
        }

        .t2q-mx-kpi-sub {
            font-size: 0.74rem;
            color: #64748b;
            font-weight: 500;
        }

        .t2q-mx-progress {
            height: 4px;
            border-radius: 9999px;
            background: #f1f5f9;
            overflow: hidden;
            margin-top: 0.2rem;
        }

        .t2q-mx-progress-bar {
            height: 100%;
            border-radius: 9999px;
            background: linear-gradient(90deg, #ea580c 0%, #f59e0b 100%);
            transition: width 0.25s ease;
        }

        .t2q-mx-filter-grid {
            display: grid;
            grid-template-columns: minmax(320px, 1.5fr) minmax(220px, 1fr) auto;
            gap: 0.9rem;
            align-items: end;
        }

        .t2q-mx-filter-grid--with-kelas {
            grid-template-columns: minmax(190px, 0.9fr) minmax(300px, 1.45fr) minmax(200px, 0.95fr) auto;
        }

        .t2q-mx-week-nav {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.4rem;
        }

        .t2q-mx-week-nav .filter-control {
            flex: 1 1 145px;
            min-width: 140px;
        }

        @media (max-width: 991.98px) {
            .t2q-mx-kpi-grid {
                grid-template-columns: 1fr;
            }

            .t2q-mx-filter-grid,
            .t2q-mx-filter-grid--with-kelas {
                grid-template-columns: 1fr;
            }

            .t2q-mx-week-nav {
                flex-wrap: wrap;
            }
        }

        .t2q-mx-table-wrap {
            overflow-x: auto;
        }

        .t2q-mx-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 0;
        }

        .t2q-mx-table thead th {
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 0.75rem;
            font-size: 0.72rem;
            font-weight: 700;
            color: #475569;
            vertical-align: middle;
        }

        .t2q-mx-table thead th.is-today-col {
            background: #fffaf5;
            border-bottom-color: #fed7aa;
        }

        .t2q-mx-day-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
            padding: 0.25rem 0.4rem;
            border-radius: 8px;
            color: inherit;
            text-decoration: none !important;
            transition: background-color 0.15s ease;
        }

        .t2q-mx-day-head:hover {
            background: rgba(234, 88, 12, 0.08);
            color: #9a3412;
        }

        .t2q-mx-day-head.is-readonly {
            cursor: default;
        }

        .t2q-mx-day-head.is-readonly:hover {
            background: transparent;
            color: inherit;
        }

        .t2q-mx-day-name {
            font-size: 0.74rem;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #0f172a;
        }

        .t2q-mx-day-date {
            font-size: 0.73rem;
            font-weight: 600;
            color: #64748b;
            font-variant-numeric: tabular-nums;
        }

        .t2q-mx-today-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            font-size: 0.65rem;
            font-weight: 700;
            padding: 0.12rem 0.45rem;
            border-radius: 9999px;
            background: #ffedd5;
            color: #c2410c;
        }

        .t2q-mx-table tbody td {
            padding: 0.65rem 0.65rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
            background: #ffffff;
            transition: background-color 0.12s ease;
        }

        .t2q-mx-table tbody td.is-today-col {
            background: #fffdfb;
        }

        .t2q-mx-table tbody tr:hover td {
            background: #f8fafc;
        }

        .t2q-mx-table tbody tr:hover td.is-today-col {
            background: #fff9f2;
        }

        .t2q-mx-sticky-col {
            position: sticky;
            left: 0;
            z-index: 2;
            min-width: 225px;
            max-width: 265px;
            border-right: 1px solid #f1f5f9;
        }

        .t2q-mx-table thead .t2q-mx-sticky-col,
        .t2q-mx-table tfoot .t2q-mx-sticky-col {
            background: #f8fafc;
            z-index: 3;
        }

        .t2q-mx-day-col {
            min-width: 175px;
        }

        .t2q-mx-summary-col {
            min-width: 132px;
            border-left: 1px solid #f1f5f9;
        }

        .t2q-mx-student-wrap {
            display: flex;
            align-items: flex-start;
            gap: 0.6rem;
        }

        .t2q-mx-num {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
            border-radius: 7px;
            background: #f1f5f9;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            flex-shrink: 0;
            margin-top: 1px;
        }

        .t2q-mx-student-name {
            font-size: 0.83rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }

        .t2q-mx-student-meta {
            font-size: 0.7rem;
            color: #64748b;
            margin-top: 2px;
            line-height: 1.3;
        }

        /* Minimalist Entry Card inside Matrix Cell */
        .t2q-mx-entry {
            display: flex;
            flex-direction: column;
            gap: 0.28rem;
            width: 100%;
            text-align: left;
            padding: 0.52rem 0.65rem 0.52rem 0.75rem;
            border-radius: 11px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            position: relative;
            overflow: hidden;
            cursor: pointer;
            transition: transform 0.14s ease, box-shadow 0.14s ease, border-color 0.14s ease;
        }

        .t2q-mx-entry::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #cbd5e1;
        }

        .t2q-mx-entry--excellent::before {
            background: #10b981;
        }

        .t2q-mx-entry--good::before {
            background: #3b82f6;
        }

        .t2q-mx-entry--fair::before {
            background: #f59e0b;
        }

        .t2q-mx-entry--poor::before {
            background: #f43f5e;
        }

        .t2q-mx-entry:hover {
            transform: translateY(-1px);
            border-color: #cbd5e1;
            box-shadow: 0 4px 12px -4px rgba(15, 23, 42, 0.1);
        }

        .t2q-mx-entry:focus {
            outline: 2px solid #ea580c;
            outline-offset: 1px;
        }

        .t2q-mx-entry-kicker {
            font-size: 0.63rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #b45309;
            line-height: 1.1;
        }

        .t2q-mx-entry-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 0.4rem;
        }

        .t2q-mx-entry-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.25;
            min-width: 0;
            overflow-wrap: anywhere;
        }

        .t2q-mx-entry-bottom {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.35rem;
            font-size: 0.72rem;
            color: #64748b;
            font-weight: 500;
        }

        .t2q-mx-empty {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 52px;
            border-radius: 11px;
            border: 1px dashed #e2e8f0;
            background: #fafbfc;
            color: #94a3b8;
            font-size: 0.74rem;
            font-weight: 600;
            text-decoration: none !important;
            transition: all 0.15s ease;
        }

        .t2q-mx-empty-hover {
            display: none;
            align-items: center;
            gap: 0.3rem;
            color: #c2410c;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .t2q-mx-empty:hover {
            border-color: #fdba74;
            background: #fff7ed;
        }

        .t2q-mx-empty:hover .t2q-mx-empty-dash {
            display: none;
        }

        .t2q-mx-empty:hover .t2q-mx-empty-hover {
            display: inline-flex;
        }

        .t2q-mx-empty.is-readonly {
            cursor: default;
        }

        .t2q-mx-empty.is-readonly:hover {
            border-color: #e2e8f0;
            background: #fafbfc;
        }

        /* Weekly Row Streak Summary */
        .t2q-mx-streak {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.35rem;
        }

        .t2q-mx-dots {
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }

        .t2q-mx-dot {
            width: 7px;
            height: 7px;
            border-radius: 9999px;
            background: #e2e8f0;
        }

        .t2q-mx-dot.is-filled {
            background: #10b981;
        }

        .t2q-mx-streak-meta {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            font-size: 0.71rem;
            font-weight: 700;
            color: #475569;
            font-variant-numeric: tabular-nums;
        }

        .t2q-mx-table tfoot td {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 0.7rem 0.75rem;
            font-size: 0.74rem;
            color: #475569;
            vertical-align: middle;
        }

        .t2q-mx-legend {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.65rem 1rem;
            padding: 0.8rem 1.25rem;
            border-top: 1px solid #f1f5f9;
            background: #fcfcfd;
            font-size: 0.73rem;
            color: #64748b;
        }

        .t2q-mx-legend-items {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.65rem 0.9rem;
        }

        .t2q-mx-legend-dot {
            display: inline-block;
            width: 8px;
            height: 8px;
            border-radius: 9999px;
            margin-right: 4px;
        }

        /* Clean Detail Modal */
        .t2q-mx-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 1060;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(2px);
        }

        .t2q-mx-modal-overlay.is-open {
            display: flex;
        }

        .t2q-mx-modal-dialog {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 18px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.25);
            overflow: hidden;
        }
    </style>
@endpush

@php
    $isTahsin = ($mode ?? 'tahsin') === 'tahsin';
    $isReadOnly = (bool) ($isReadOnly ?? false);
    $isWaliKelasScope = (bool) ($isWaliKelasScope ?? false);
    $routePrefix = (string) ($routePrefix ?? 't2q');
    $assignedClasses = $assignedClasses ?? collect();
    $selectedKelas = $selectedKelas ?? null;
    $selectedKelasId = (int) ($selectedKelasId ?? ($selectedKelas?->id ?? 0));
    $peranDiKelas = $peranDiKelas ?? null;
    $showKelasSelector = $isWaliKelasScope && $assignedClasses->count() > 1;

    $indexRoute = $isTahsin ? 't2q.yaumiyah-tahsin.index' : 't2q.yaumiyah-tahfizh.index';
    $tahsinMatriksRoute = $routePrefix . '.yaumiyah-tahsin.matriks';
    $tahfizhMatriksRoute = $routePrefix . '.yaumiyah-tahfizh.matriks';
    $matriksRoute = $isTahsin ? $tahsinMatriksRoute : $tahfizhMatriksRoute;
    $statistikRoute = $routePrefix . '.' . ($isTahsin ? 'yaumiyah-tahsin.statistik' : 'yaumiyah-tahfizh.statistik');
    $pageTitle = $isTahsin ? 'Matriks Catatan Tahsin' : 'Matriks Catatan Tahfizh';
    $pageSubtitle = $isTahsin
        ? 'Rekap setoran bacaan jilid & Al-Qur\'an mingguan (Senin – Kamis).'
        : 'Rekap setoran hafalan surah Al-Qur\'an mingguan (Senin – Kamis).';

    $activeTahunAjaran = $activeTahunAjaran ?? null;
    $tanggal = $tanggal ?? now()->toDateString();
    $guruOptions = $guruOptions ?? collect();
    $selectedGuruNipy = (string) ($selectedGuruNipy ?? '');
    $tingkatTabs = $tingkatTabs ?? [];
    $selectedTingkat = (string) ($selectedTingkat ?? '');
    $targetCapaian = $targetCapaian ?? collect();

    $period = $period ?? [
        'anchor_date' => $tanggal,
        'prev_week_date' => \Carbon\Carbon::parse($tanggal)->subWeek()->toDateString(),
        'next_week_date' => \Carbon\Carbon::parse($tanggal)->addWeek()->toDateString(),
        'current_week_date' => now()->toDateString(),
        'is_current_week' => true,
        'range_label' => $tanggal,
        'days' => [],
    ];
    $matrix = $matrix ?? [
        'days' => $period['days'] ?? [],
        'rows' => [],
        'daily_totals' => [],
        'summary' => [
            'total_students' => 0,
            'total_recorded' => 0,
            'possible_slots' => 0,
            'completion_pct' => 0,
            'overall_avg_formatted' => '-',
            'overall_avg_tier' => 'neutral',
        ],
    ];
    $matrixDays = $matrix['days'] ?? [];
    $matrixRows = $matrix['rows'] ?? [];
    $matrixDailyTotals = $matrix['daily_totals'] ?? [];
    $matrixSummary = $matrix['summary'] ?? [];

    $selectedKelasSqid = $selectedKelasId > 0
        ? \App\Support\Sqid::encodeFor(\App\Models\Admin\DataMaster\Kelas::class, $selectedKelasId)
        : null;
    $kelasQueryParam = $selectedKelasSqid !== null ? ['kelas_id' => $selectedKelasSqid] : [];
    $sharedQuery = array_filter([
        ...$kelasQueryParam,
        'tanggal' => $period['anchor_date'],
        'karyawan_nipy' => $selectedGuruNipy,
        'tingkat' => $selectedTingkat,
    ]);
    $statistikQuery = array_filter([
        ...$kelasQueryParam,
        'tahun_ajaran_id' => $activeTahunAjaran?->sqid,
        'karyawan_nipy' => $selectedGuruNipy,
        'tingkat' => $selectedTingkat,
    ]);
@endphp

<div class="app-main__inner">
    <!-- PAGE HEADER -->
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon text-white shadow-sm"
                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                    <i class="{{ $isTahsin ? 'pe-7s-bookmarks' : 'pe-7s-medal' }}" style="color: white;"></i>
                </div>
                <div>
                    {{ $pageTitle }}
                    <div class="page-title-subheading">
                        {{ $pageSubtitle }}
                    </div>
                </div>
            </div>

            <div class="page-title-actions d-flex flex-wrap align-items-center" style="gap: 0.65rem;">
                <div class="t2q-view-switch" role="navigation" aria-label="Pilih program matriks">
                    <a href="{{ route($tahsinMatriksRoute, $sharedQuery) }}"
                        class="t2q-view-pill {{ $isTahsin ? 'is-active' : '' }}">
                        <i class="fa fa-book-open"></i>
                        <span>Tahsin</span>
                    </a>
                    <a href="{{ route($tahfizhMatriksRoute, $sharedQuery) }}"
                        class="t2q-view-pill {{ ! $isTahsin ? 'is-active' : '' }}">
                        <i class="fa fa-quran"></i>
                        <span>Tahfizh</span>
                    </a>
                </div>

                @if (! $isReadOnly)
                    <a href="{{ route($indexRoute, $sharedQuery) }}" class="filter-btn filter-btn-secondary" style="height: 2.2rem; min-height: 2.2rem;">
                        <i class="fa fa-edit text-warning"></i>
                        <span>Input Harian</span>
                    </a>
                @endif
                <a href="{{ route($statistikRoute, $statistikQuery) }}" class="filter-btn filter-btn-secondary" style="height: 2.2rem; min-height: 2.2rem;">
                    <i class="fa fa-chart-bar text-info"></i>
                    <span>Statistik</span>
                </a>

                @if ($isWaliKelasScope && $selectedKelas)
                    <span class="badge badge-light border px-3 py-2 font-weight-semibold text-slate-700"
                        style="font-size: 0.78rem; border-radius: 9999px;">
                        <i class="fa fa-chalkboard-teacher text-warning mr-1"></i>
                        {{ $selectedKelas->nama_kelas }}@if ($peranDiKelas) &bull; {{ $peranDiKelas }}@endif
                    </span>
                @endif

                @if ($isReadOnly)
                    <span class="badge badge-light border px-3 py-2 font-weight-semibold text-muted"
                        style="font-size: 0.75rem; border-radius: 9999px;">
                        <i class="fa fa-eye mr-1"></i>Lihat Saja
                    </span>
                @endif

                @if ($activeTahunAjaran)
                    <span class="badge badge-light border px-3 py-2 font-weight-semibold text-slate-700"
                        style="font-size: 0.78rem; border-radius: 9999px;">
                        <i class="fa fa-calendar-check text-success mr-1"></i>
                        {{ $activeTahunAjaran->full_label }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if (! $activeTahunAjaran)
        <div class="alert alert-warning border shadow-sm" style="border-radius: 12px;">
            <i class="fa fa-exclamation-triangle mr-1"></i>
            Belum ada <strong>Tahun Ajaran aktif</strong>. Aktifkan Tahun Ajaran terlebih dahulu untuk menampilkan {{ $pageTitle }}.
        </div>
    @else
        <!-- RINGKASAN PEKAN (MINIMALIST BENTO STRIP) -->
        <div class="t2q-mx-kpi-grid mb-3">
            <div class="t2q-mx-kpi">
                <span class="t2q-mx-kpi-label">Pekan Efektif (Sen – Kam)</span>
                <div class="t2q-mx-kpi-value">
                    <span>{{ $period['range_label'] }}</span>
                    @if ($period['is_current_week'])
                        <span class="t2q-mx-today-tag">Pekan Ini</span>
                    @endif
                </div>
                <span class="t2q-mx-kpi-sub">4 hari setoran reguler T2Q</span>
            </div>

            <div class="t2q-mx-kpi">
                <span class="t2q-mx-kpi-label">Keterisian Setoran</span>
                <div class="t2q-mx-kpi-value">
                    <span>{{ $matrixSummary['total_recorded'] }} <small class="text-muted font-weight-normal">/ {{ $matrixSummary['possible_slots'] }}</small></span>
                    <span class="t2q-score-pill t2q-score-pill--neutral">{{ $matrixSummary['completion_pct'] }}%</span>
                </div>
                <div class="t2q-mx-progress" aria-hidden="true">
                    <div class="t2q-mx-progress-bar" style="width: {{ min(100, (float) $matrixSummary['completion_pct']) }}%;"></div>
                </div>
            </div>

            <div class="t2q-mx-kpi">
                <span class="t2q-mx-kpi-label">Rata-rata Nilai Pekan</span>
                <div class="t2q-mx-kpi-value">
                    <span>{{ $matrixSummary['overall_avg_formatted'] }}</span>
                    <span class="t2q-score-pill t2q-score-pill--{{ $matrixSummary['overall_avg_tier'] }}">
                        {{ $isTahsin ? 'Tahsin' : 'Tahfizh' }}
                    </span>
                </div>
                <span class="t2q-mx-kpi-sub">Akumulasi {{ $matrixSummary['total_students'] }} siswa tingkat {{ $selectedTingkat ?: '-' }}</span>
            </div>
        </div>

        <!-- FILTER PEKAN & GURU PEMBIMBING -->
        <div class="filter-card mb-3">
            <div class="filter-card-body">
                <form method="GET" action="{{ route($matriksRoute) }}" id="t2qMatrixFilterForm"
                    class="t2q-mx-filter-grid {{ $showKelasSelector ? 't2q-mx-filter-grid--with-kelas' : '' }}">
                    <input type="hidden" name="tingkat" value="{{ $selectedTingkat }}">
                    @if (! $showKelasSelector && $selectedKelasId > 0)
                        <input type="hidden" name="kelas_id" value="{{ $selectedKelasSqid }}">
                    @endif

                    @if ($showKelasSelector)
                        <div>
                            <label for="mx_filter_kelas" class="filter-label">Kelas Binaan</label>
                            <select name="kelas_id" id="mx_filter_kelas" class="filter-control js-mx-auto-submit">
                                @foreach ($assignedClasses as $kelasItem)
                                    <option value="{{ $kelasItem->sqid }}" @selected((int) $kelasItem->id === $selectedKelasId)>
                                        {{ $kelasItem->nama_kelas }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div>
                        <label for="mx_filter_tanggal" class="filter-label">Pilih Pekan (Senin – Kamis)</label>
                        <div class="t2q-mx-week-nav">
                            <a href="{{ route($matriksRoute, array_filter([...$kelasQueryParam, 'tanggal' => $period['prev_week_date'], 'karyawan_nipy' => $selectedGuruNipy, 'tingkat' => $selectedTingkat])) }}"
                                class="filter-btn filter-btn-secondary filter-btn-icon" title="Pekan sebelumnya">
                                <i class="fa fa-chevron-left"></i>
                            </a>
                            <input type="date" name="tanggal" id="mx_filter_tanggal"
                                class="filter-control js-mx-auto-submit" value="{{ $period['anchor_date'] }}">
                            <a href="{{ route($matriksRoute, array_filter([...$kelasQueryParam, 'tanggal' => $period['next_week_date'], 'karyawan_nipy' => $selectedGuruNipy, 'tingkat' => $selectedTingkat])) }}"
                                class="filter-btn filter-btn-secondary filter-btn-icon" title="Pekan berikutnya">
                                <i class="fa fa-chevron-right"></i>
                            </a>
                            <a href="{{ route($matriksRoute, array_filter([...$kelasQueryParam, 'tanggal' => $period['current_week_date'], 'karyawan_nipy' => $selectedGuruNipy, 'tingkat' => $selectedTingkat])) }}"
                                class="filter-btn filter-btn-secondary t2q-chip-btn {{ $period['is_current_week'] ? 'is-current' : '' }}">
                                Pekan Ini
                            </a>
                        </div>
                    </div>

                    <div>
                        <label for="mx_filter_guru" class="filter-label">Guru Pembimbing T2Q</label>
                        <select name="karyawan_nipy" id="mx_filter_guru" class="filter-control js-mx-auto-submit">
                            <option value="">Semua Guru T2Q</option>
                            @foreach ($guruOptions as $guru)
                                <option value="{{ $guru->nipy }}" @selected($selectedGuruNipy === (string) $guru->nipy)>
                                    {{ $guru->nama_gelar }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex align-items-center" style="gap: 0.45rem;">
                        <button type="submit" class="filter-btn filter-btn-primary">
                            <i class="fa fa-sync-alt"></i> Terapkan
                        </button>
                        @if ($selectedGuruNipy !== '' || ! $period['is_current_week'])
                            <a href="{{ route($matriksRoute, array_filter([...$kelasQueryParam, 'tingkat' => $selectedTingkat])) }}"
                                class="filter-btn filter-btn-secondary filter-btn-icon" title="Reset ke pekan ini">
                                <i class="fa fa-undo"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- TAB TINGKAT & TARGET CAPAIAN -->
        @include('admin.t2q.partials._tingkat_strip', [
            'tabRoute' => $matriksRoute,
            'tabBaseQuery' => [
                ...$kelasQueryParam,
                'tanggal' => $period['anchor_date'],
                'karyawan_nipy' => $selectedGuruNipy,
            ],
            'tingkatTabs' => $tingkatTabs,
            'selectedTingkat' => $selectedTingkat,
            'targetCapaian' => $targetCapaian,
        ])

        <!-- TABEL MATRIKS SENIN - KAMIS -->
        <div class="t2q-surface overflow-hidden mb-3">
            @if (count($matrixRows) === 0)
                <div class="text-center py-5 px-4 text-muted">
                    <i class="fa fa-th-list fa-2x mb-2 text-slate-300 d-block"></i>
                    Belum ada anggota T2Q yang terdaftar untuk filter tingkat / guru pada Tahun Ajaran aktif.
                </div>
            @else
                <div class="px-4 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between"
                    style="gap: 0.75rem; border-color: #f1f5f9 !important;">
                    <div class="d-flex flex-wrap align-items-center" style="gap: 0.6rem;">
                        <h6 class="font-weight-bold text-slate-800 mb-0" style="font-size: 0.92rem;">
                            Matriks {{ $isTahsin ? 'Tahsin' : 'Tahfizh' }} &bull; Tingkat {{ $selectedTingkat }}
                        </h6>
                        <span class="filter-count-pill">
                            <i class="fa fa-calendar-week text-warning"></i>
                            <strong>{{ $period['range_label'] }}</strong>
                        </span>
                    </div>

                    <div class="t2q-search-box">
                        <i class="fa fa-search"></i>
                        <input type="text" id="t2qMatrixSearch" placeholder="Cari siswa / NIS / kelas...">
                    </div>
                </div>

                <div class="t2q-mx-table-wrap">
                    <table class="t2q-mx-table" id="t2qMatrixTable">
                        <thead>
                            <tr>
                                <th class="t2q-mx-sticky-col">Siswa ({{ count($matrixRows) }})</th>
                                @foreach ($matrixDays as $dayInfo)
                                    <th class="t2q-mx-day-col {{ $dayInfo['is_today'] ? 'is-today-col' : '' }}">
                                        @if ($isReadOnly)
                                            <div class="t2q-mx-day-head is-readonly">
                                                <div>
                                                    <div class="t2q-mx-day-name">{{ $dayInfo['day_name'] }}</div>
                                                    <div class="t2q-mx-day-date">{{ $dayInfo['date_label'] }}</div>
                                                </div>
                                                @if ($dayInfo['is_today'])
                                                    <span class="t2q-mx-today-tag">Hari Ini</span>
                                                @endif
                                            </div>
                                        @else
                                            @php
                                                $dayInputUrl = route($indexRoute, array_filter([
                                                    'tanggal' => $dayInfo['date'],
                                                    'karyawan_nipy' => $selectedGuruNipy,
                                                    'tingkat' => $selectedTingkat,
                                                ]));
                                            @endphp
                                            <a href="{{ $dayInputUrl }}" class="t2q-mx-day-head"
                                                title="Buka input harian {{ $dayInfo['full_label'] }}">
                                                <div>
                                                    <div class="t2q-mx-day-name">{{ $dayInfo['day_name'] }}</div>
                                                    <div class="t2q-mx-day-date">{{ $dayInfo['date_label'] }}</div>
                                                </div>
                                                @if ($dayInfo['is_today'])
                                                    <span class="t2q-mx-today-tag">Hari Ini</span>
                                                @endif
                                            </a>
                                        @endif
                                    </th>
                                @endforeach
                                <th class="t2q-mx-summary-col text-center">Ringkasan Pekan</th>
                            </tr>
                        </thead>

                        <tbody>
                            @foreach ($matrixRows as $rowIdx => $row)
                                @php
                                    $anggota = $row['anggota'];
                                    $ak = $anggota->relationLoaded('anggotaKelas') ? $anggota->anggotaKelas : null;
                                    $siswa = ($ak && $ak->relationLoaded('siswa')) ? $ak->siswa : null;
                                    $kelas = ($ak && $ak->relationLoaded('kelas')) ? $ak->kelas : null;
                                    $guru = $anggota->relationLoaded('guruT2q') ? $anggota->guruT2q : null;

                                    $namaSiswa = $siswa?->nama_lengkap ?? ($ak?->siswa_nis ?? ('Anggota #' . $anggota->id));
                                    $nisSiswa = $ak?->siswa_nis ?? '';
                                    $metaParts = array_filter([
                                        $nisSiswa,
                                        $kelas?->nama_kelas,
                                        $guru?->nama_gelar,
                                    ]);
                                    $searchText = strtolower(trim($namaSiswa . ' ' . implode(' ', $metaParts)));
                                    $cells = $row['cells'];
                                @endphp
                                <tr data-mx-row data-search-text="{{ $searchText }}">
                                    <td class="t2q-mx-sticky-col">
                                        <div class="t2q-mx-student-wrap">
                                            <span class="t2q-mx-num">{{ str_pad((string) ($rowIdx + 1), 2, '0', STR_PAD_LEFT) }}</span>
                                            <div class="min-w-0">
                                                <div class="t2q-mx-student-name">{{ $namaSiswa }}</div>
                                                @if ($metaParts !== [])
                                                    <div class="t2q-mx-student-meta">{{ implode(' • ', $metaParts) }}</div>
                                                @endif
                                            </div>
                                        </div>
                                    </td>

                                    @foreach ($matrixDays as $dayInfo)
                                        @php
                                            $dateKey = $dayInfo['date'];
                                            $cell = $cells[$dateKey] ?? null;
                                            $dayInputUrl = ! $isReadOnly
                                                ? route($indexRoute, array_filter([
                                                    'tanggal' => $dateKey,
                                                    'karyawan_nipy' => $selectedGuruNipy,
                                                    'tingkat' => $selectedTingkat,
                                                ]))
                                                : null;
                                        @endphp
                                        <td class="t2q-mx-day-col {{ $dayInfo['is_today'] ? 'is-today-col' : '' }}">
                                            @if ($cell !== null)
                                                @php
                                                    $modalPayload = [
                                                        'program' => $isTahsin ? 'Yaumiyah Tahsin' : 'Yaumiyah Tahfizh',
                                                        'siswa' => $namaSiswa,
                                                        'meta' => implode(' • ', $metaParts),
                                                        'tanggal_label' => $cell['tanggal_label'],
                                                        'context_label' => $cell['context_label'],
                                                        'primary_label' => $cell['primary_label'],
                                                        'arab_title' => $cell['arab_title'],
                                                        'secondary_label' => $cell['secondary_label'],
                                                        'arab_number' => $cell['arab_number'],
                                                        'ringkasan' => $cell['ringkasan'],
                                                        'nilai' => $cell['nilai_formatted'],
                                                        'tier' => $cell['nilai_tier'],
                                                        'tier_label' => $cell['nilai_label'],
                                                        'edit_url' => $dayInputUrl,
                                                    ];
                                                @endphp
                                                <button type="button"
                                                    class="t2q-mx-entry t2q-mx-entry--{{ $cell['nilai_tier'] }} js-mx-detail-btn"
                                                    data-entry='@json($modalPayload)'
                                                    aria-label="{{ $namaSiswa }} {{ $dayInfo['day_name'] }}: {{ $cell['ringkasan'] }}, Nilai {{ $cell['nilai_formatted'] }}">
                                                    @if ($cell['context_label'] !== '')
                                                        <span class="t2q-mx-entry-kicker">{{ $cell['context_label'] }}</span>
                                                    @endif
                                                    <div class="t2q-mx-entry-top">
                                                        <span class="t2q-mx-entry-title">{{ $cell['primary_label'] }}</span>
                                                        <span class="t2q-score-pill t2q-score-pill--{{ $cell['nilai_tier'] }}">
                                                            {{ $cell['nilai_formatted'] }}
                                                        </span>
                                                    </div>
                                                    @if ($cell['secondary_label'] !== '' || $cell['arab_title'] !== '')
                                                        <div class="t2q-mx-entry-bottom">
                                                            <span>
                                                                {{ $cell['secondary_label'] !== '' ? $cell['secondary_label'] : 'Tercatat' }}
                                                                @if ($cell['arab_number'] !== '')
                                                                    <span class="t2q-arab-inline text-slate-700 ml-1">{{ $cell['arab_number'] }}</span>
                                                                @endif
                                                            </span>
                                                            @if ($cell['arab_title'] !== '' && (! $isTahsin || $cell['context_label'] !== ''))
                                                                <span class="t2q-arab-inline text-slate-500">{{ $cell['arab_title'] }}</span>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </button>
                                            @elseif ($isReadOnly)
                                                <span class="t2q-mx-empty is-readonly" aria-label="Belum ada setoran">&mdash;</span>
                                            @else
                                                <a href="{{ $dayInputUrl }}" class="t2q-mx-empty"
                                                    title="Isi setoran {{ $namaSiswa }} tanggal {{ $dayInfo['full_label'] }}">
                                                    <span class="t2q-mx-empty-dash">&mdash;</span>
                                                    <span class="t2q-mx-empty-hover">
                                                        <i class="fa fa-plus-circle"></i> Isi
                                                    </span>
                                                </a>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="t2q-mx-summary-col text-center">
                                        <div class="t2q-mx-streak">
                                            <div class="t2q-mx-dots" title="Keteraturan Senin – Kamis">
                                                @foreach ($matrixDays as $dayInfo)
                                                    @php $hasDay = ($cells[$dayInfo['date']] ?? null) !== null; @endphp
                                                    <span class="t2q-mx-dot {{ $hasDay ? 'is-filled' : '' }}"
                                                        title="{{ $dayInfo['day_name'] }}: {{ $hasDay ? 'Setor' : 'Belum' }}"></span>
                                                @endforeach
                                            </div>
                                            <div class="t2q-mx-streak-meta">
                                                <span>{{ $row['recorded_count'] }}/4</span>
                                                <span class="t2q-score-pill t2q-score-pill--{{ $row['avg_tier'] }}">
                                                    {{ $row['avg_formatted'] }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>

                        <tfoot>
                            <tr>
                                <td class="t2q-mx-sticky-col font-weight-bold text-slate-700">
                                    Partisipasi &amp; Rata-rata / Hari
                                </td>
                                @foreach ($matrixDays as $dayInfo)
                                    @php $dayStat = $matrixDailyTotals[$dayInfo['date']] ?? ['recorded_count' => 0, 'completion_pct' => 0, 'avg_formatted' => '-']; @endphp
                                    <td class="text-center {{ $dayInfo['is_today'] ? 'is-today-col' : '' }}">
                                        <div class="font-weight-bold text-slate-800" style="font-variant-numeric: tabular-nums;">
                                            {{ $dayStat['recorded_count'] }}/{{ count($matrixRows) }}
                                            <span class="text-muted font-weight-normal">({{ $dayStat['completion_pct'] }}%)</span>
                                        </div>
                                        <div class="small text-muted" style="font-size: 0.7rem;">
                                            Rata-rata: <strong>{{ $dayStat['avg_formatted'] }}</strong>
                                        </div>
                                    </td>
                                @endforeach
                                <td class="t2q-mx-summary-col text-center font-weight-bold text-slate-800"
                                    style="font-variant-numeric: tabular-nums;">
                                    <div>{{ $matrixSummary['completion_pct'] }}%</div>
                                    <div class="small text-muted font-weight-normal" style="font-size: 0.7rem;">
                                        Rata-rata {{ $matrixSummary['overall_avg_formatted'] }}
                                    </div>
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- LEGENDA TENANG & INFORMATIF -->
                <div class="t2q-mx-legend">
                    <div class="t2q-mx-legend-items">
                        <span><i class="t2q-mx-legend-dot" style="background: #10b981;"></i>&ge; 85 Sangat Baik</span>
                        <span><i class="t2q-mx-legend-dot" style="background: #3b82f6;"></i>75 – 84 Baik</span>
                        <span><i class="t2q-mx-legend-dot" style="background: #f59e0b;"></i>60 – 74 Cukup</span>
                        <span><i class="t2q-mx-legend-dot" style="background: #f43f5e;"></i>&lt; 60 Perlu Bimbingan</span>
                    </div>
                    <span>
                        {{ $isReadOnly
                            ? 'Klik kartu setoran untuk melihat detail setoran siswa kelas binaan (mode lihat saja).'
                            : 'Klik kartu setoran untuk melihat detail, atau klik sel kosong untuk mengisi tanggal tersebut.' }}
                    </span>
                </div>
            @endif
        </div>
    @endif
</div>

<!-- MODAL DETAIL SETORAN MATRIKS -->
<div id="t2qMatrixDetailModal" class="t2q-mx-modal-overlay" role="dialog" aria-modal="true"
    aria-hidden="true" aria-labelledby="mxModalTitle">
    <div class="t2q-mx-modal-dialog">
        <div class="px-4 py-3 border-bottom d-flex align-items-center justify-content-between bg-slate-50"
            style="border-color: #f1f5f9 !important;">
            <div>
                <h6 id="mxModalTitle" class="font-weight-bold text-slate-800 mb-0" style="font-size: 0.9rem;">
                    Detail Setoran
                </h6>
                <div id="mxModalTanggal" class="small text-muted" style="font-size: 0.74rem;">-</div>
            </div>
            <button type="button" id="mxModalCloseBtn"
                class="btn btn-sm btn-light border-0 text-muted" style="border-radius: 8px;" aria-label="Tutup">
                <i class="fa fa-times"></i>
            </button>
        </div>

        <div class="p-4">
            <div class="d-flex align-items-start justify-content-between mb-3" style="gap: 0.75rem;">
                <div>
                    <div id="mxModalSiswa" class="font-weight-bold text-slate-900" style="font-size: 1rem;">-</div>
                    <div id="mxModalMeta" class="small text-muted mt-1">-</div>
                </div>
                <span id="mxModalScorePill" class="t2q-score-pill t2q-score-pill--neutral"
                    style="font-size: 0.85rem; padding: 0.25rem 0.65rem;">-</span>
            </div>

            <div class="p-3 rounded-lg border mb-3" style="background: #f8fafc; border-color: #e2e8f0 !important; border-radius: 12px !important;">
                <div id="mxModalContext" class="small font-weight-bold text-uppercase mb-1"
                    style="font-size: 0.68rem; color: #b45309; display: none;"></div>
                <div class="d-flex align-items-center justify-content-between" style="gap: 0.75rem;">
                    <div>
                        <div id="mxModalPrimary" class="font-weight-bold text-slate-900" style="font-size: 0.98rem;">-</div>
                        <div id="mxModalSecondary" class="small text-muted mt-1">-</div>
                    </div>
                    <div id="mxModalArabWrap" class="text-right">
                        <div id="mxModalArabTitle" class="t2q-arab-text" style="font-size: 1.35rem;"></div>
                        <div id="mxModalArabNum" class="t2q-arab-text text-muted" style="font-size: 1rem;"></div>
                    </div>
                </div>
            </div>

            <div class="d-flex align-items-center justify-content-between" style="gap: 0.5rem;">
                <span id="mxModalPredikat" class="small font-weight-semibold text-muted">-</span>
                @if (! $isReadOnly)
                    <a href="#" id="mxModalEditLink" class="filter-btn filter-btn-primary">
                        <i class="fa fa-edit"></i> Ubah di Input Harian
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.getElementById('t2qMatrixFilterForm');
            if (filterForm) {
                filterForm.querySelectorAll('.js-mx-auto-submit').forEach(function (el) {
                    el.addEventListener('change', function () {
                        filterForm.submit();
                    });
                });
            }

            const searchInput = document.getElementById('t2qMatrixSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const term = searchInput.value.trim().toLowerCase();
                    document.querySelectorAll('tr[data-mx-row]').forEach(function (row) {
                        const haystack = row.getAttribute('data-search-text') || '';
                        row.style.display = (term === '' || haystack.includes(term)) ? '' : 'none';
                    });
                });
            }

            const modalOverlay = document.getElementById('t2qMatrixDetailModal');
            const closeBtn = document.getElementById('mxModalCloseBtn');
            const titleEl = document.getElementById('mxModalTitle');
            const tanggalEl = document.getElementById('mxModalTanggal');
            const siswaEl = document.getElementById('mxModalSiswa');
            const metaEl = document.getElementById('mxModalMeta');
            const scoreEl = document.getElementById('mxModalScorePill');
            const contextEl = document.getElementById('mxModalContext');
            const primaryEl = document.getElementById('mxModalPrimary');
            const secondaryEl = document.getElementById('mxModalSecondary');
            const arabTitleEl = document.getElementById('mxModalArabTitle');
            const arabNumEl = document.getElementById('mxModalArabNum');
            const predikatEl = document.getElementById('mxModalPredikat');
            const editLinkEl = document.getElementById('mxModalEditLink');

            function closeDetailModal() {
                if (!modalOverlay) {
                    return;
                }
                modalOverlay.classList.remove('is-open');
                modalOverlay.setAttribute('aria-hidden', 'true');
            }

            document.querySelectorAll('.js-mx-detail-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!modalOverlay) {
                        return;
                    }
                    let payload = {};
                    try {
                        payload = JSON.parse(btn.getAttribute('data-entry') || '{}');
                    } catch (e) {
                        return;
                    }

                    titleEl.textContent = 'Detail ' + (payload.program || 'Setoran T2Q');
                    tanggalEl.textContent = payload.tanggal_label || '-';
                    siswaEl.textContent = payload.siswa || '-';
                    metaEl.textContent = payload.meta || '-';
                    primaryEl.textContent = payload.primary_label || '-';
                    secondaryEl.textContent = payload.secondary_label || (payload.ringkasan || '-');
                    arabTitleEl.textContent = payload.arab_title || '';
                    arabNumEl.textContent = payload.arab_number || '';
                    predikatEl.textContent = 'Predikat: ' + (payload.tier_label || '-');
                    if (editLinkEl) {
                        editLinkEl.setAttribute('href', payload.edit_url || '#');
                    }

                    if (payload.context_label) {
                        contextEl.textContent = payload.context_label;
                        contextEl.style.display = 'block';
                    } else {
                        contextEl.style.display = 'none';
                    }

                    scoreEl.textContent = payload.nilai || '-';
                    scoreEl.className = 't2q-score-pill t2q-score-pill--' + (payload.tier || 'neutral');

                    modalOverlay.classList.add('is-open');
                    modalOverlay.setAttribute('aria-hidden', 'false');
                });
            });

            if (closeBtn) {
                closeBtn.addEventListener('click', closeDetailModal);
            }
            if (modalOverlay) {
                modalOverlay.addEventListener('click', function (event) {
                    if (event.target === modalOverlay) {
                        closeDetailModal();
                    }
                });
            }
            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeDetailModal();
                }
            });
        });
    </script>
@endpush
