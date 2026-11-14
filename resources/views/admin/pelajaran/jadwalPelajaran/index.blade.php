@extends('layouts.appui')

@section('title')
    <title>Jadwal Pelajaran</title>
@endsection

@section('content')
    @push('css')
        <style>
            .jp-day-strip {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }

            .jp-day-pills {
                display: inline-flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
                padding: 0.3rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
            }

            .jp-day-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.38rem 0.72rem;
                border-radius: 8px;
                font-size: 0.76rem;
                font-weight: 600;
                color: #475569;
                text-decoration: none !important;
                transition: all 0.15s ease;
                border: 1px solid transparent;
            }

            .jp-day-pill:hover {
                color: #0f172a;
                background: rgba(255, 255, 255, 0.7);
            }

            .jp-day-pill.is-active {
                background: #ffffff;
                color: #0f172a;
                border-color: #e2e8f0;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            }

            .jp-day-count {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 1.25rem;
                height: 1.2rem;
                padding: 0 0.35rem;
                border-radius: 999px;
                font-size: 0.68rem;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
                background: #e2e8f0;
                color: #475569;
            }

            .jp-day-pill.is-active .jp-day-count {
                background: #fff7ed;
                color: #ea580c;
            }

            .jp-status-summary {
                display: inline-flex;
                align-items: center;
                gap: 0.65rem;
                padding: 0.35rem 0.8rem;
                border-radius: 999px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                font-size: 0.74rem;
                font-weight: 600;
                color: #475569;
                font-variant-numeric: tabular-nums;
            }

            .jp-dot {
                width: 6px;
                height: 6px;
                border-radius: 999px;
                display: inline-block;
                flex-shrink: 0;
            }

            .jp-filter-bar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 0.9rem 1.1rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            }

            .jp-filter-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 0.75rem;
                align-items: end;
            }

            @media (min-width: 640px) {
                .jp-filter-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1120px) {
                .jp-filter-grid {
                    grid-template-columns: 1.35fr 0.7fr 1.15fr 0.8fr 0.75fr 1.45fr;
                }
            }

            .jp-matrix-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 18px;
                overflow: hidden;
                margin-bottom: 1.25rem;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04), 0 8px 24px -16px rgba(15, 23, 42, 0.1);
            }

            .jp-matrix-header {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem 1rem;
                padding: 1rem 1.25rem;
                border-bottom: 1px solid #f1f5f9;
                background: #ffffff;
            }

            .jp-matrix-title-group {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.55rem;
            }

            .jp-matrix-title {
                margin: 0;
                font-size: 0.96rem;
                font-weight: 700;
                color: #0f172a;
                letter-spacing: -0.01em;
            }

            .jp-class-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.22rem 0.65rem;
                border-radius: 999px;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #c2410c;
                font-size: 0.73rem;
                font-weight: 700;
            }

            .jp-matrix-legend {
                display: inline-flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.85rem;
                font-size: 0.72rem;
                font-weight: 500;
                color: #64748b;
            }

            .jp-legend-item {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
            }

            .jp-matrix-scroll {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }

            .jp-matrix-table {
                width: 100%;
                min-width: 880px;
                border-collapse: separate;
                border-spacing: 0;
                table-layout: fixed;
            }

            .jp-matrix-table thead th {
                padding: 0.7rem 0.75rem;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                border-right: 1px solid #f1f5f9;
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.06em;
                color: #64748b;
                text-align: left;
            }

            .jp-matrix-table thead th:last-child {
                border-right: 0;
            }

            .jp-matrix-table thead th.is-day-highlight {
                color: #ea580c;
                background: #fff7ed;
            }

            .jp-matrix-table tbody td {
                padding: 0.45rem;
                vertical-align: top;
                border-bottom: 1px solid #f1f5f9;
                border-right: 1px solid #f1f5f9;
                background: #ffffff;
            }

            .jp-matrix-table tbody tr:last-child td {
                border-bottom: 0;
            }

            .jp-matrix-table tbody td:last-child {
                border-right: 0;
            }

            .jp-time-col {
                position: sticky;
                left: 0;
                z-index: 2;
                width: 128px;
                min-width: 128px;
                background: #fcfcfd !important;
                border-right: 1px solid #e2e8f0 !important;
                padding: 0.75rem 0.8rem !important;
            }

            thead .jp-time-col {
                z-index: 3;
                background: #f8fafc !important;
            }

            .jp-time-jam {
                font-size: 0.7rem;
                font-weight: 700;
                color: #94a3b8;
                text-transform: uppercase;
                letter-spacing: 0.04em;
            }

            .jp-time-range {
                margin-top: 2px;
                font-size: 0.79rem;
                font-weight: 700;
                color: #1e293b;
                font-variant-numeric: tabular-nums;
                white-space: nowrap;
            }

            .jp-time-note {
                margin-top: 3px;
                font-size: 0.69rem;
                color: #64748b;
                line-height: 1.25;
            }

            .jp-slot-tile {
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                width: 100%;
                height: 104px;
                overflow: hidden;
                padding: 0.62rem 0.72rem;
                border-radius: 11px;
                border: 1px solid #e2e8f0;
                background: #ffffff;
                box-shadow: inset 3px 0 0 #ea580c;
                transition: transform 0.15s ease, box-shadow 0.15s ease, border-color 0.15s ease;
            }

            .jp-slot-tile:hover {
                transform: translateY(-1px);
                border-color: #cbd5e1;
                box-shadow: inset 3px 0 0 #ea580c, 0 6px 14px -6px rgba(15, 23, 42, 0.12);
            }

            .jp-slot-tile.is-non-mapel {
                background: #fafaf9;
                border-color: #e7e5e4;
                box-shadow: inset 3px 0 0 #f59e0b;
            }

            .jp-slot-tile.is-non-mapel:hover {
                border-color: #d6d3d1;
                box-shadow: inset 3px 0 0 #f59e0b, 0 6px 14px -6px rgba(15, 23, 42, 0.08);
            }

            .jp-slot-tile.is-inactive {
                background: #f8fafc;
                border-style: dashed;
                border-color: #cbd5e1;
                box-shadow: inset 3px 0 0 #94a3b8;
                opacity: 0.62;
            }

            .jp-slot-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.35rem;
            }

            .jp-slot-code {
                display: inline-flex;
                align-items: center;
                padding: 0.12rem 0.45rem;
                border-radius: 6px;
                font-size: 0.64rem;
                font-weight: 700;
                letter-spacing: 0.03em;
                text-transform: uppercase;
                background: #fff7ed;
                color: #c2410c;
            }

            .jp-slot-tile.is-non-mapel .jp-slot-code {
                background: #fef3c7;
                color: #92400e;
            }

            .jp-slot-tile.is-inactive .jp-slot-code {
                background: #e2e8f0;
                color: #475569;
            }

            .jp-slot-edit {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 22px;
                height: 22px;
                border-radius: 6px;
                color: #94a3b8;
                font-size: 0.72rem;
                text-decoration: none !important;
                transition: background-color 0.15s ease, color 0.15s ease;
            }

            .jp-slot-tile:hover .jp-slot-edit {
                color: #475569;
            }

            .jp-slot-edit:hover {
                background: #f1f5f9;
                color: #ea580c !important;
            }

            .jp-slot-subject {
                margin-top: 0.35rem;
                font-size: 0.8rem;
                font-weight: 700;
                line-height: 1.3;
                color: #0f172a;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
                word-break: break-word;
            }

            .jp-slot-meta {
                margin-top: 0.35rem;
                display: flex;
                flex-direction: column;
                gap: 0.12rem;
                font-size: 0.7rem;
                color: #64748b;
                line-height: 1.25;
                min-width: 0;
            }

            .jp-slot-meta > span {
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .jp-slot-empty {
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.3rem;
                width: 100%;
                height: 104px;
                border-radius: 11px;
                border: 1px dashed transparent;
                color: #cbd5e1;
                font-size: 0.72rem;
                font-weight: 600;
                text-decoration: none !important;
                transition: all 0.15s ease;
            }

            .jp-slot-empty:hover,
            .jp-slot-empty:focus-visible {
                border-color: #fdba74;
                background: #fff7ed;
                color: #c2410c;
                outline: none;
            }

            .jp-class-picker {
                padding: 1.15rem 1.25rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.45rem;
            }

            .jp-class-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.36rem 0.78rem;
                border-radius: 999px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                color: #334155;
                font-size: 0.76rem;
                font-weight: 600;
                text-decoration: none !important;
                transition: all 0.15s ease;
            }

            .jp-class-chip:hover {
                background: #fff7ed;
                border-color: #fdba74;
                color: #c2410c;
                transform: translateY(-1px);
            }

            .jp-class-chip-unit {
                font-size: 0.65rem;
                font-weight: 700;
                color: #64748b;
                text-transform: uppercase;
            }

            .jp-status-pill-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.38rem;
                padding: 0.28rem 0.68rem;
                border-radius: 999px;
                font-size: 0.73rem;
                font-weight: 600;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #475569;
                cursor: pointer;
                transition: all 0.15s ease;
            }

            .jp-status-pill-btn.is-active {
                background: #ecfdf5;
                border-color: #a7f3d0;
                color: #047857;
            }

            .jp-status-pill-btn:hover {
                transform: translateY(-1px);
            }
        </style>
    @endpush

    @php
        $jadwalPelajaran = $jadwalPelajaran ?? collect();
        $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
        $kelasOptions = $kelasOptions ?? collect();
        $unitOptions = $unitOptions ?? \App\Models\Admin\Pelajaran\JamPelajaran::UNIT_OPTIONS;
        $hariOptions = $hariOptions ?? \App\Models\Admin\Pelajaran\JadwalPelajaran::HARI_OPTIONS;
        $selectedKelas = $selectedKelas ?? null;
        $weeklyMatrix = $weeklyMatrix ?? null;
        $filters = $filters ?? [
            'tahun_ajaran_id' => \App\Services\Admin\Pelajaran\JadwalPelajaranService::FILTER_ALL,
            'unit' => '',
            'kelas_id' => '',
            'hari' => '',
            'status' => \App\Services\Admin\Pelajaran\JadwalPelajaranService::FILTER_ALL,
            'search' => '',
        ];

        if (isset($summary)) {
            $stats = $summary;
        } else {
            $fallbackPerHari = array_fill_keys(array_keys($hariOptions), 0);
            $fallbackAktif = 0;

            foreach ($jadwalPelajaran as $row) {
                $hariCode = (int) ($row->hari ?? 0);
                if (array_key_exists($hariCode, $fallbackPerHari)) {
                    $fallbackPerHari[$hariCode]++;
                }
                if ((bool) $row->status) {
                    $fallbackAktif++;
                }
            }

            $stats = [
                'total' => $jadwalPelajaran->count(),
                'aktif' => $fallbackAktif,
                'nonaktif' => max(0, $jadwalPelajaran->count() - $fallbackAktif),
                'per_hari' => $fallbackPerHari,
            ];
        }

        $selectedHari = trim((string) ($filters['hari'] ?? ''));
        $hasActiveFilter = (($filters['unit'] ?? '') !== '')
            || (($filters['kelas_id'] ?? '') !== '')
            || ($selectedHari !== '')
            || (($filters['status'] ?? 'all') !== 'all')
            || (($filters['search'] ?? '') !== '');

        $baseQueryParams = [
            'tahun_ajaran_id' => $filters['tahun_ajaran_id'] ?? 'all',
            'unit' => $filters['unit'] ?? '',
            'kelas_id' => $filters['kelas_id'] ?? '',
            'status' => ($filters['status'] ?? 'all') !== 'all' ? $filters['status'] : '',
            'search' => $filters['search'] ?? '',
        ];

        $createParams = array_filter([
            'tahun_ajaran_id' => ($filters['tahun_ajaran_id'] ?? 'all') !== 'all' ? $filters['tahun_ajaran_id'] : null,
            'kelas_id' => ($filters['kelas_id'] ?? '') !== '' ? $filters['kelas_id'] : null,
            'hari' => $selectedHari !== '' ? $selectedHari : null,
        ]);

        $matrixDayCounts = array_fill_keys(array_keys($hariOptions), 0);
        if ($weeklyMatrix) {
            foreach ($weeklyMatrix['grid'] as $hariCells) {
                foreach ($hariCells as $hariCode => $cellEntry) {
                    if (array_key_exists($hariCode, $matrixDayCounts)) {
                        $matrixDayCounts[$hariCode]++;
                    }
                }
            }
        }
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-date" style="color: white;"></i>
                    </div>
                    <div>
                        Jadwal Pelajaran
                        <div class="page-title-subheading">
                            Jadwal pelajaran dan kegiatan sekolah hari Senin – Jumat.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('jadwal-pelajaran.create', $createParams) }}"
                        class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px;">
                        <i class="fa fa-plus mr-1"></i> Tambah Jadwal
                    </a>
                </div>
            </div>
        </div>

        <!-- RINGKASAN HARI MINIMALIS (SENIN - JUMAT) -->
        <div class="jp-day-strip">
            <div class="jp-day-pills">
                <a href="{{ route('jadwal-pelajaran.index', array_filter($baseQueryParams)) }}"
                    class="jp-day-pill {{ $selectedHari === '' ? 'is-active' : '' }}">
                    <span>Semua Hari</span>
                    <span class="jp-day-count">{{ $stats['total'] }}</span>
                </a>
                @foreach ($hariOptions as $hariCode => $hariNama)
                    <a href="{{ route('jadwal-pelajaran.index', array_filter([...$baseQueryParams, 'hari' => $hariCode])) }}"
                        class="jp-day-pill {{ $selectedHari === (string) $hariCode ? 'is-active' : '' }}">
                        <span>{{ $hariNama }}</span>
                        <span class="jp-day-count">{{ $stats['per_hari'][$hariCode] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            <div class="jp-status-summary">
                <span class="d-inline-flex align-items-center" style="gap: 0.35rem;">
                    <span class="jp-dot" style="background: #10b981;"></span>
                    {{ $stats['aktif'] }} Aktif
                </span>
                @if ($stats['nonaktif'] > 0)
                    <span style="color: #cbd5e1;">|</span>
                    <span class="d-inline-flex align-items-center" style="gap: 0.35rem;">
                        <span class="jp-dot" style="background: #94a3b8;"></span>
                        {{ $stats['nonaktif'] }} Nonaktif
                    </span>
                @endif
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="jp-filter-bar">
            <form method="GET" action="{{ route('jadwal-pelajaran.index') }}" id="jadwalFilterForm" class="jp-filter-grid">
                <div>
                    <label for="filter_tahun_ajaran" class="filter-label">Tahun Ajaran</label>
                    <select name="tahun_ajaran_id" id="filter_tahun_ajaran" class="filter-control js-filter-reset-kelas">
                        <option value="all" @selected(($filters['tahun_ajaran_id'] ?? 'all') === 'all')>
                            Semua Tahun Ajaran
                        </option>
                        @foreach ($tahunAjaranOptions as $ta)
                            <option value="{{ $ta->id }}"
                                @selected((string) ($filters['tahun_ajaran_id'] ?? '') === (string) $ta->id)>
                                {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_unit" class="filter-label">Unit</label>
                    <select name="unit" id="filter_unit" class="filter-control js-filter-reset-kelas">
                        <option value="">Semua</option>
                        @foreach ($unitOptions as $unitOption)
                            <option value="{{ $unitOption }}"
                                @selected(strtoupper((string) ($filters['unit'] ?? '')) === $unitOption)>
                                {{ $unitOption }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_kelas" class="filter-label">Kelas (Matriks)</label>
                    <select name="kelas_id" id="filter_kelas" class="filter-control js-filter-autosubmit">
                        <option value="">Semua Kelas</option>
                        @foreach ($kelasOptions as $kelasOption)
                            <option value="{{ $kelasOption->id }}"
                                @selected((string) ($filters['kelas_id'] ?? '') === (string) $kelasOption->id)>
                                [{{ strtoupper((string) $kelasOption->jenjang_kelas) }}] {{ $kelasOption->nama_kelas }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_hari" class="filter-label">Hari</label>
                    <select name="hari" id="filter_hari" class="filter-control js-filter-autosubmit">
                        <option value="">Semua</option>
                        @foreach ($hariOptions as $hariCode => $hariNama)
                            <option value="{{ $hariCode }}" @selected($selectedHari === (string) $hariCode)>
                                {{ $hariNama }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_status" class="filter-label">Status</label>
                    <select name="status" id="filter_status" class="filter-control js-filter-autosubmit">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Semua</option>
                        <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                        <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                    </select>
                </div>

                <div>
                    <label for="filter_search" class="filter-label">Cari</label>
                    <div class="filter-search-group">
                        <input type="text" name="search" id="filter_search" class="filter-control"
                            placeholder="Mapel, guru, ruang, kegiatan..." value="{{ $filters['search'] ?? '' }}">
                        <button type="submit" class="filter-btn filter-btn-primary filter-btn-icon" title="Terapkan Filter">
                            <i class="fa fa-search"></i>
                        </button>
                        @if ($hasActiveFilter)
                            <a href="{{ route('jadwal-pelajaran.index', ['tahun_ajaran_id' => $filters['tahun_ajaran_id'] ?? 'all']) }}"
                                class="filter-btn filter-btn-secondary filter-btn-icon" title="Reset Filter">
                                <i class="fa fa-undo"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        @if ($selectedKelas && $weeklyMatrix)
            <!-- MATRIKS JADWAL MINGGUAN PER KELAS -->
            <div class="jp-matrix-card">
                <div class="jp-matrix-header">
                    <div class="jp-matrix-title-group">
                        <h2 class="jp-matrix-title">Matriks Jadwal Mingguan</h2>
                        <span class="jp-class-badge">
                            {{ strtoupper((string) $selectedKelas->jenjang_kelas) }} &middot; {{ $selectedKelas->nama_kelas }}
                        </span>
                    </div>

                    <div class="d-flex flex-wrap align-items-center" style="gap: 1rem;">
                        <div class="jp-matrix-legend">
                            <span class="jp-legend-item">
                                <span class="jp-dot" style="background: #ea580c;"></span> Mapel
                            </span>
                            <span class="jp-legend-item">
                                <span class="jp-dot" style="background: #f59e0b;"></span> Non-Mapel
                            </span>
                            <span class="jp-legend-item">
                                <span class="jp-dot" style="background: #94a3b8;"></span> Nonaktif
                            </span>
                        </div>

                        <a href="{{ route('jadwal-pelajaran.index', array_filter([
                            'tahun_ajaran_id' => $filters['tahun_ajaran_id'] ?? 'all',
                            'unit' => $filters['unit'] ?? '',
                        ])) }}" class="text-xs font-weight-bold" style="color: #64748b; text-decoration: none;">
                            Ganti Kelas &times;
                        </a>
                    </div>
                </div>

                @if ($weeklyMatrix['jamSlots']->isEmpty())
                    <div class="p-4 text-center" style="color: #64748b; font-size: 0.84rem;">
                        Belum ada slot <strong>Jam Pelajaran</strong> untuk unit
                        <strong>{{ strtoupper((string) $selectedKelas->jenjang_kelas) }}</strong>.
                        <a href="{{ route('jam-pelajaran.create') }}" style="color: #ea580c; font-weight: 600;">
                            Tambah Jam Pelajaran
                        </a>
                    </div>
                @else
                    <div class="jp-matrix-scroll">
                        <table class="jp-matrix-table">
                            <thead>
                                <tr>
                                    <th class="jp-time-col">Waktu</th>
                                    @foreach ($hariOptions as $hariCode => $hariNama)
                                        <th class="{{ $selectedHari === (string) $hariCode ? 'is-day-highlight' : '' }}">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <span>{{ $hariNama }}</span>
                                                <span style="font-weight: 600; color: #94a3b8; font-size: 0.68rem;">
                                                    {{ $matrixDayCounts[$hariCode] ?? 0 }}
                                                </span>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($weeklyMatrix['jamSlots'] as $jamSlot)
                                    <tr>
                                        <td class="jp-time-col">
                                            <div class="jp-time-jam">Jam Ke-{{ $jamSlot->jam_ke }}</div>
                                            <div class="jp-time-range">{{ $jamSlot->rentang_waktu }}</div>
                                            @if (filled($jamSlot->keterangan))
                                                <div class="jp-time-note">{{ $jamSlot->keterangan }}</div>
                                            @endif
                                        </td>
                                        @foreach ($hariOptions as $hariCode => $hariNama)
                                            @php
                                                $slotEntry = $weeklyMatrix['grid'][$jamSlot->id][$hariCode] ?? null;
                                                $slotMapel = $slotEntry && $slotEntry->relationLoaded('mataPelajaran')
                                                    ? $slotEntry->mataPelajaran
                                                    : null;
                                                $slotGuru = $slotEntry && $slotEntry->relationLoaded('guru')
                                                    ? $slotEntry->guru
                                                    : null;
                                            @endphp
                                            <td>
                                                @if ($slotEntry)
                                                    <div class="jp-slot-tile {{ $slotEntry->is_non_mapel ? 'is-non-mapel' : '' }} {{ ! $slotEntry->status ? 'is-inactive' : '' }}">
                                                        <div>
                                                            <div class="jp-slot-top">
                                                                @if ($slotEntry->is_non_mapel)
                                                                    <span class="jp-slot-code">Non-Mapel</span>
                                                                @elseif ($slotMapel)
                                                                    <span class="jp-slot-code">{{ $slotMapel->singkatan }}</span>
                                                                @else
                                                                    <span class="jp-slot-code">Mapel</span>
                                                                @endif

                                                                <a href="{{ route('jadwal-pelajaran.edit', $slotEntry->id) }}"
                                                                    class="jp-slot-edit" title="Edit Jadwal">
                                                                    <i class="fa fa-pen"></i>
                                                                </a>
                                                            </div>

                                                            <div class="jp-slot-subject">
                                                                {{ $slotEntry->judul_tampil }}
                                                            </div>
                                                        </div>

                                                        @if ($slotGuru || filled($slotEntry->ruang) || ! $slotEntry->status)
                                                            <div class="jp-slot-meta">
                                                                @if ($slotGuru)
                                                                    <span>{{ $slotGuru->nama_gelar }}</span>
                                                                @endif
                                                                @if (filled($slotEntry->ruang))
                                                                    <span style="color: #94a3b8;">{{ $slotEntry->ruang }}</span>
                                                                @endif
                                                                @if (! $slotEntry->status)
                                                                    <span style="color: #64748b; font-weight: 600;">Nonaktif</span>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    </div>
                                                @else
                                                    <a href="{{ route('jadwal-pelajaran.create', [
                                                        'tahun_ajaran_id' => $selectedKelas->akademik_master_tahun_ajaran_id,
                                                        'kelas_id' => $selectedKelas->id,
                                                        'jam_pelajaran_id' => $jamSlot->id,
                                                        'hari' => $hariCode,
                                                    ]) }}" class="jp-slot-empty">
                                                        <i class="fa fa-plus" style="font-size: 0.65rem;"></i>
                                                        <span>Isi Slot</span>
                                                    </a>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        @elseif ($kelasOptions->isNotEmpty())
            <!-- PILIH CEPAT KELAS UNTUK MATRIKS MINGGUAN -->
            <div class="jp-matrix-card">
                <div class="jp-matrix-header">
                    <div class="jp-matrix-title-group">
                        <h2 class="jp-matrix-title">Matriks Jadwal Mingguan</h2>
                        <span style="font-size: 0.78rem; color: #64748b;">
                            Pilih kelas untuk menampilkan tampilan grid Senin – Jumat
                        </span>
                    </div>
                </div>
                <div class="jp-class-picker">
                    @foreach ($kelasOptions as $kelasOption)
                        <a href="{{ route('jadwal-pelajaran.index', array_filter([
                            ...$baseQueryParams,
                            'hari' => $selectedHari,
                            'kelas_id' => $kelasOption->id,
                        ])) }}" class="jp-class-chip">
                            <span class="jp-class-chip-unit">{{ strtoupper((string) $kelasOption->jenjang_kelas) }}</span>
                            <span>{{ $kelasOption->nama_kelas }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- DAFTAR DETAIL JADWAL -->
        <div class="main-card card mb-3 border-0 shadow-sm" style="border-radius: 18px !important;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
                style="border-bottom: 1px solid #f1f5f9;">
                <span style="font-size: 0.92rem; font-weight: 700; color: #0f172a;">
                    Daftar Slot Jadwal
                </span>
                <span style="font-size: 0.76rem; color: #64748b; font-weight: 600;">
                    {{ $jadwalPelajaran->count() }} slot
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover align-middle" id="myTable3">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th class="text-center" style="width: 9%;">Hari</th>
                                <th style="width: 15%;">Jam Pelajaran</th>
                                <th style="width: 14%;">Kelas</th>
                                <th>Mata Pelajaran / Kegiatan</th>
                                <th style="width: 18%;">Guru Pengampu</th>
                                <th class="text-center" style="width: 11%;">Status</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jadwalPelajaran as $item)
                                @php
                                    $kelas = $item->relationLoaded('kelas') ? $item->kelas : null;
                                    $jam = $item->relationLoaded('jamPelajaran') ? $item->jamPelajaran : null;
                                    $mapel = $item->relationLoaded('mataPelajaran') ? $item->mataPelajaran : null;
                                    $guru = $item->relationLoaded('guru') ? $item->guru : null;
                                    $tahunAjaran = $item->relationLoaded('tahunAjaran') ? $item->tahunAjaran : null;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->hari_badge_class }} px-2 py-1" style="font-size: 0.74rem;">
                                            {{ $item->hari_label }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($jam)
                                            <div class="font-weight-bold text-slate-800" style="font-size: 0.82rem;">
                                                Jam Ke-{{ $jam->jam_ke }}
                                            </div>
                                            <div class="small text-muted" style="font-variant-numeric: tabular-nums;">
                                                {{ $jam->rentang_waktu }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($kelas)
                                            <div class="font-weight-bold text-slate-800" style="font-size: 0.84rem;">
                                                {{ $kelas->nama_kelas }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ strtoupper((string) $kelas->jenjang_kelas) }}
                                                @if ($tahunAjaran)
                                                    &middot; {{ $tahunAjaran->full_label }}
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.35rem;">
                                            @if ($item->is_non_mapel)
                                                <span class="jp-slot-code" style="background: #fef3c7; color: #92400e;">Non-Mapel</span>
                                            @elseif ($mapel)
                                                <span class="jp-slot-code">{{ $mapel->singkatan }}</span>
                                            @endif
                                            <span class="font-weight-bold text-slate-800" style="font-size: 0.85rem;">
                                                {{ $item->judul_tampil }}
                                            </span>
                                        </div>

                                        @if (filled($item->ruang) || (! $item->is_non_mapel && filled($item->keterangan)))
                                            <div class="small text-muted mt-1">
                                                @if (filled($item->ruang))
                                                    <span class="mr-2">{{ $item->ruang }}</span>
                                                @endif
                                                @if (! $item->is_non_mapel && filled($item->keterangan))
                                                    <span>{{ \Illuminate\Support\Str::limit($item->keterangan, 90) }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($guru)
                                            <div class="font-weight-bold text-slate-800" style="font-size: 0.83rem;">
                                                {{ $guru->nama_gelar }}
                                            </div>
                                            <div class="small text-muted">{{ $guru->nipy }}</div>
                                        @else
                                            <span class="text-muted font-italic" style="font-size: 0.8rem;">Tanpa guru</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('jadwal-pelajaran.toggle-status', $item->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit"
                                                class="jp-status-pill-btn {{ $item->status ? 'is-active' : '' }}"
                                                data-toggle="tooltip" data-placement="top"
                                                title="{{ $item->status ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan' }}">
                                                <span class="jp-dot"
                                                    style="background: {{ $item->status ? '#10b981' : '#94a3b8' }};"></span>
                                                <span>{{ $item->status_label }}</span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('jadwal-pelajaran.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('jadwal-pelajaran.destroy', $item->id) }}" method="POST" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="action-dropdown-item action-dropdown-item--danger delete-button" role="menuitem">
                                                    <i class="fa fa-trash action-item-icon"></i>
                                                    <span>Hapus Data</span>
                                                </button>
                                            </form>
                                        </x-action-dropdown>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const filterForm = document.getElementById('jadwalFilterForm');
            if (!filterForm) {
                return;
            }

            const kelasSelect = document.getElementById('filter_kelas');

            filterForm.querySelectorAll('.js-filter-reset-kelas').forEach(function (selectEl) {
                selectEl.addEventListener('change', function () {
                    if (kelasSelect) {
                        kelasSelect.value = '';
                    }
                    filterForm.submit();
                });
            });

            filterForm.querySelectorAll('.js-filter-autosubmit').forEach(function (selectEl) {
                selectEl.addEventListener('change', function () {
                    filterForm.submit();
                });
            });
        });
    </script>
@endpush
