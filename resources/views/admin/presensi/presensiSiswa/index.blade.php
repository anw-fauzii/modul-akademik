@extends('layouts.appui')

@section('title')
    <title>Presensi Siswa - SIPIT Akademik</title>
@endsection

@push('css')
    <style>
        .presensi-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
            border-radius: 18px;
            padding: 24px 28px;
            color: #ffffff;
            box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.3);
        }

        .matrix-scroll {
            overflow-x: auto;
            border-radius: 14px;
            border: 1px solid #e2e8f0;
        }

        .matrix-table {
            border-collapse: separate;
            border-spacing: 0;
            width: max-content;
            min-width: 100%;
            font-size: 12px;
        }

        .matrix-table th,
        .matrix-table td {
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #f1f5f9;
            padding: 7px 6px;
            vertical-align: middle;
        }

        .matrix-table thead th {
            background: #f8fafc;
            color: #475569;
            font-weight: 700;
            text-align: center;
            white-space: nowrap;
        }

        .matrix-sticky-col {
            position: sticky;
            left: 0;
            z-index: 2;
            background: #ffffff;
            box-shadow: 2px 0 6px -2px rgba(15, 23, 42, 0.08);
            min-width: 220px;
        }

        thead .matrix-sticky-col,
        tfoot .matrix-sticky-col {
            background: #f8fafc;
            z-index: 3;
        }

        .matrix-cell-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            border-width: 1px;
            border-style: solid;
            font-weight: 800;
            font-size: 11px;
            text-decoration: none !important;
            cursor: pointer;
        }

        .matrix-cell-btn:focus,
        .js-status-detail-trigger:focus {
            outline: 2px solid #ea580c;
            outline-offset: 2px;
        }

        .presensi-instant-tooltip {
            position: fixed;
            z-index: 1060;
            max-width: 280px;
            padding: 6px 10px;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 600;
            line-height: 1.4;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.35);
            pointer-events: none;
            display: none;
            white-space: normal;
        }

        .presensi-instant-tooltip.is-visible {
            display: block;
        }

        .matrix-cell-empty {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 8px;
            border: 1px dashed #e2e8f0;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 600;
            cursor: default;
            user-select: none;
            pointer-events: none;
        }

        .presensi-detail-overlay {
            position: fixed;
            inset: 0;
            z-index: 1050;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.55);
        }

        .presensi-detail-overlay.is-open {
            display: flex;
        }

        .presensi-detail-dialog {
            width: 100%;
            max-width: 440px;
            background: #ffffff;
            border-radius: 18px;
            box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.35);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
    </style>
@endpush

@section('content')
    @php
        $matrixKelas = $matrix['kelas'];
        $matrixSummary = $matrix['summary'];
        $matrixDays = $matrix['days'];
        $matrixRows = $matrix['rows'];
        $matrixDailyTotals = $matrix['daily_totals'];

        $cellStyles = [
            'hadir' => ['code' => 'H', 'class' => 'bg-emerald-500/15 text-emerald-700 border-emerald-300 hover:bg-emerald-500 hover:text-white'],
            'terlambat' => ['code' => 'T', 'class' => 'bg-amber-500/15 text-amber-800 border-amber-300 hover:bg-amber-500 hover:text-white'],
            'izin' => ['code' => 'I', 'class' => 'bg-sky-500/15 text-sky-700 border-sky-300 hover:bg-sky-500 hover:text-white'],
            'sakit' => ['code' => 'S', 'class' => 'bg-indigo-500/15 text-indigo-700 border-indigo-300 hover:bg-indigo-500 hover:text-white'],
            'alpa' => ['code' => 'A', 'class' => 'bg-rose-500/15 text-rose-700 border-rose-300 hover:bg-rose-500 hover:text-white'],
        ];

        $buildStatusPayload = static function ($record, $siswa, $kelas, string $fallbackDate = '-'): array {
            return [
                'nama' => $siswa?->nama_lengkap ?? '-',
                'nis' => $siswa?->nis ?? '-',
                'kelas' => $kelas ? trim(($kelas->jenjang_kelas ? $kelas->jenjang_kelas.' - ' : '').$kelas->nama_kelas) : '-',
                'tanggal' => $record->tanggal?->translatedFormat('l, d M Y') ?? $fallbackDate,
                'status' => (string) $record->status,
                'status_label' => $record->status_label,
                'keterangan' => $record->status_keterangan,
                'jam_masuk' => $record->jam_masuk ? substr((string) $record->jam_masuk, 0, 5) : '-',
                'terlambat_menit' => (int) $record->keterlambatan_menit,
                'edit_url' => route('presensi-siswa.edit', $record),
                'destroy_url' => route('presensi-siswa.destroy', $record),
            ];
        };

        $preservedRedirectFields = [];
        if ($hasExplicitFilter ?? false) {
            if (request()->exists('tahun_ajaran_id')) {
                $preservedRedirectFields['redirect_tahun_ajaran_id'] = (string) ($filters['tahun_ajaran_id'] ?? '');
            }
            foreach (['kelas_id', 'bulan', 'status', 'tanggal'] as $filterKey) {
                $val = $filters[$filterKey] ?? null;
                if ($val !== null && $val !== '') {
                    $preservedRedirectFields["redirect_{$filterKey}"] = (string) $val;
                }
            }
        }

        $recordedDates = [];
        if ($matrixKelas) {
            foreach ($matrixDays as $dayInfo) {
                $recordCount = 0;
                foreach ($matrixRows as $row) {
                    if (isset($row['cells'][$dayInfo['day']])) {
                        $recordCount++;
                    }
                }
                if ($recordCount > 0) {
                    $recordedDates[] = [
                        'date' => $dayInfo['date'],
                        'short' => $dayInfo['label'].' '.$dayInfo['day_name'],
                        'count' => $recordCount,
                    ];
                }
            }
        }

        $defaultBulkDeleteDate = $recordedDates !== []
            ? $recordedDates[array_key_last($recordedDates)]['date']
            : (($filters['tanggal'] ?? '') !== '' ? $filters['tanggal'] : now()->format('Y-m-d'));
        $defaultBulkDeleteKelasId = $matrixKelas?->id ?? ($filters['kelas_id'] ?? $kelasOptions->first()?->id);
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-date" style="color: white;"></i>
                    </div>
                    <div>
                        Presensi Siswa
                        <div class="page-title-subheading">
                            Matriks kehadiran hari sekolah (Senin–Jumat) per kelas.
                        </div>
                    </div>
                </div>
                <div class="page-title-actions d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                    <a href="{{ route('presensi-siswa.bulk', array_filter([
                        'kelas_id' => ($filters['kelas_id'] ?? 0) > 0 ? $filters['kelas_id'] : null,
                        'tanggal' => ($filters['tanggal'] ?? '') !== '' ? $filters['tanggal'] : null,
                    ])) }}"
                        class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-layer-group mr-1"></i> Input Data
                    </a>
                </div>
            </div>
        </div>

        <!-- JUDUL & INFORMASI KELAS & BULAN -->
        <div class="presensi-hero mb-4">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-500/20 text-orange-300 border border-orange-400/30">
                    <i class="fa fa-calendar-alt mr-1.5"></i> {{ $matrix['bulan_label'] }}
                </span>
                @if ($matrixKelas)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/10 text-slate-200 border border-white/15">
                        {{ $matrixKelas->jenjang_kelas }} • Tingkat {{ $matrixKelas->tingkatan_kelas }}
                    </span>
                    @if ($matrixKelas->tahunAjaran)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                            {{ $matrixKelas->tahunAjaran->nama_tahun_ajaran }} ({{ $matrixKelas->tahunAjaran->semester }})
                        </span>
                    @endif
                @endif
            </div>

            <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                {{ $matrixKelas ? 'Matriks Presensi — '.$matrixKelas->nama_kelas : 'Matriks Presensi Siswa' }}
            </h2>
            <p class="mt-1.5 text-xs sm:text-sm text-slate-300 flex flex-wrap items-center gap-x-4 gap-y-1 mb-0">
                <span>
                    <i class="fa fa-users text-orange-400 mr-1"></i>
                    Total Siswa: <strong class="text-white">{{ $matrixSummary['siswa'] }}</strong>
                </span>
                <span>
                    <i class="fa fa-calendar-week text-emerald-300 mr-1"></i>
                    Hari Sekolah (Sen–Jum): <strong class="text-white">{{ count($matrixDays) }} hari</strong>
                </span>
                @if ($matrixKelas?->waliKelas)
                    <span>
                        <i class="fa fa-user-tie text-amber-300 mr-1"></i>
                        Wali Kelas: <strong class="text-white">{{ $matrixKelas->waliKelas->nama_gelar }}</strong>
                    </span>
                @endif
            </p>
        </div>

        @php
            $matrixTotalCatatan = (int) ($matrixSummary['total_catatan'] ?? 0);
            $matrixHadirEfektif = (int) ($matrixSummary['hadir'] ?? 0) + (int) ($matrixSummary['terlambat'] ?? 0);
            $matrixPctTepatWaktu = $matrixTotalCatatan > 0
                ? round((((int) ($matrixSummary['hadir'] ?? 0)) / $matrixTotalCatatan) * 100, 1)
                : 0;
            $matrixPctTerlambat = $matrixSummary['persentase_terlambat']
                ?? ($matrixTotalCatatan > 0 ? round((((int) $matrixSummary['terlambat']) / $matrixTotalCatatan) * 100, 1) : 0);
            $batasJamMasuk = \App\Models\Admin\Presensi\PresensiSiswa::BATAS_JAM_MASUK;
        @endphp

        <!-- KARTU RINGKASAN STATUS & PERSENTASE (TERPISAH DARI JUDUL) -->
        @include('admin.presensi.presensiSiswa._status_stats', [
            'statusSummary' => $matrixSummary,
            'totalCatatan' => $matrixTotalCatatan,
            'statusMeta' => [
                'hadir' => ['subtext' => $matrixPctTepatWaktu.'% tepat waktu'],
                'terlambat' => ['subtext' => 'Lewat '.$batasJamMasuk],
                'izin' => ['subtext' => 'Izin tercatat'],
                'sakit' => ['subtext' => 'Sakit tercatat'],
                'alpa' => ['subtext' => 'Tanpa keterangan'],
            ],
            'rateCards' => [
                [
                    'label' => '% Kehadiran',
                    'value' => $matrixSummary['persentase_hadir'],
                    'percent' => $matrixSummary['persentase_hadir'],
                    'badge' => 'Hadir + Terlambat',
                    'subtitle' => $matrixHadirEfektif.' dari '.$matrixTotalCatatan.' catatan presensi hadir efektif',
                    'icon' => 'fa fa-chart-line',
                    'tone' => 'orange',
                ],
                [
                    'label' => '% Keterlambatan',
                    'value' => $matrixPctTerlambat,
                    'percent' => $matrixPctTerlambat,
                    'badge' => 'Batas '.$batasJamMasuk,
                    'subtitle' => ((int) ($matrixSummary['terlambat'] ?? 0)).' dari '.$matrixTotalCatatan.' catatan melewati jam masuk',
                    'icon' => 'fa fa-stopwatch',
                    'tone' => 'amber',
                ],
            ],
        ])

        <!-- PANEL FILTER -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter Data &amp; Matriks Presensi Siswa</span>
                    @if ($hasExplicitFilter ?? false)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="filter-count-pill">
                        <i class="fa fa-users text-primary"></i>
                        Siswa Kelas: <strong>{{ $matrixSummary['siswa'] }}</strong>
                    </span>
                    <span class="filter-count-pill">
                        <i class="fa fa-clipboard-list text-success"></i>
                        Catatan Kelas: <strong>{{ (int) ($matrixSummary['total_catatan'] ?? 0) }}</strong>
                    </span>
                    @if ($hasExplicitFilter ?? false)
                        <a href="{{ route('presensi-siswa.index') }}" class="filter-btn filter-btn-secondary"
                            style="height: 1.9rem; min-height: 1.9rem; padding: 0 0.7rem; font-size: 0.75rem;">
                            <i class="fa fa-undo"></i> Reset
                        </a>
                    @endif
                </div>
            </div>

            <div class="filter-card-body">
                <form method="GET" action="{{ route('presensi-siswa.index') }}"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <div class="lg:col-span-3">
                        <label for="filter_tahun_ajaran_id" class="filter-label">Tahun Ajaran</label>
                        <select id="filter_tahun_ajaran_id" name="tahun_ajaran_id" class="filter-control">
                            <option value="">Semua Tahun Ajaran</option>
                            @foreach ($tahunAjaranOptions as $tahunAjaran)
                                <option value="{{ $tahunAjaran->id }}" @selected($filters['tahun_ajaran_id'] === $tahunAjaran->id)>
                                    {{ $tahunAjaran->nama_tahun_ajaran }} ({{ $tahunAjaran->semester }})@if ($tahunAjaran->is_active) (Aktif)@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <label for="filter_kelas_id" class="filter-label">Kelas (Matriks)</label>
                        <select id="filter_kelas_id" name="kelas_id" class="filter-control">
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($kelasOptions as $kelas)
                                <option value="{{ $kelas->id }}"
                                    data-tahun-ajaran-id="{{ $kelas->akademik_master_tahun_ajaran_id }}"
                                    @selected($filters['kelas_id'] === $kelas->id)>
                                    {{ $kelas->jenjang_kelas }} - {{ $kelas->nama_kelas }}
                                    @if ($kelas->tahunAjaran)
                                        ({{ $kelas->tahunAjaran->nama_tahun_ajaran }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="filter_bulan" class="filter-label">Bulan Matriks</label>
                        <input type="month" id="filter_bulan" name="bulan"
                            value="{{ $filters['bulan'] }}" class="filter-control">
                    </div>

                    <div class="lg:col-span-2">
                        <label for="filter_status" class="filter-label">Status</label>
                        <select id="filter_status" name="status" class="filter-control">
                            <option value="">Semua Status</option>
                            @foreach ($statusOptions as $value => $label)
                                <option value="{{ $value }}" @selected($filters['status'] === $value)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-2 flex items-center gap-2">
                        <button type="submit" class="filter-btn filter-btn-primary flex-1 min-w-0 px-3" title="Tampilkan Data">
                            <i class="fa fa-filter"></i> Filter
                        </button>
                        <a href="{{ route('presensi-siswa.index') }}" class="filter-btn filter-btn-secondary filter-btn-icon"
                            title="Reset Filter">
                            <i class="fa fa-sync-alt"></i>
                        </a>
                    </div>
                </form>
            </div>
        </div>

            <!-- LEGENDA KODE STATUS MATRIKS -->
            <div class="flex flex-wrap items-center justify-between gap-3 mb-3">
                <div class="inline-flex items-center gap-2 text-xs font-extrabold text-slate-700">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-orange-50 text-orange-600 border border-orange-200">
                        <i class="fa fa-th"></i>
                    </span>
                    <span>Matriks Per Kelas</span>
                </div>

                <div class="flex flex-wrap items-center gap-2 text-[11px] font-semibold text-slate-600">
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-emerald-50 text-emerald-700 border border-emerald-200"><strong>H</strong> Hadir</span>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-amber-50 text-amber-800 border border-amber-200"><strong>T</strong> Terlambat</span>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-sky-50 text-sky-700 border border-sky-200"><strong>I</strong> Izin</span>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-indigo-50 text-indigo-700 border border-indigo-200"><strong>S</strong> Sakit</span>
                    <span class="inline-flex items-center gap-1 px-2 py-1 rounded-md bg-rose-50 text-rose-700 border border-rose-200"><strong>A</strong> Alpa</span>
                </div>
            </div>

            <!-- PANEL 1: MATRIKS PRESENSI PER KELAS -->
            <div id="tab-panel-matrix" class="main-card mb-4 card shadow-sm border-0" style="border-radius: 16px;">
                <div class="card-body">
                    @if (! $matrixKelas)
                        <div class="text-center py-12 text-slate-500">
                            <i class="fa fa-school text-3xl text-slate-300 mb-3"></i>
                            <p class="font-bold text-slate-700">Belum ada kelas tersedia</p>
                            <p class="text-xs mt-1">Tambahkan kelas terlebih dahulu untuk menampilkan matriks presensi.</p>
                        </div>
                    @elseif (count($matrixRows) === 0)
                        <div class="text-center py-12 text-slate-500">
                            <i class="fa fa-user-graduate text-3xl text-slate-300 mb-3"></i>
                            <p class="font-bold text-slate-700">Belum ada anggota siswa di kelas {{ $matrixKelas->nama_kelas }}</p>
                            <p class="text-xs mt-1">Tambahkan anggota kelas agar matriks presensi bulan {{ $matrix['bulan_label'] }} tampil di sini.</p>
                        </div>
                    @else
                        <div class="flex flex-wrap items-center justify-between gap-2 mb-3">
                            <p class="text-xs text-slate-500 mb-0">
                                Matriks menampilkan hari kerja <strong>Senin–Jumat</strong>. Klik kode status (<strong>H/T/I/S/A</strong>) untuk melihat keterangan masuk atau terlambat, edit, dan hapus; klik header <strong>tanggal</strong> atau tombol <strong>Input Data</strong> untuk input massal.
                            </p>
                            <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                                @if ($kelasOptions->isNotEmpty())
                                    <button type="button" id="openBulkDeleteModalBtn"
                                        class="btn btn-sm btn-outline-danger btn-warning font-weight-bold shadow-xs"
                                        style="border-radius: 8px;">
                                        <i class="fa fa-trash-alt mr-1"></i> Hapus Per Tanggal
                                    </button>
                                @endif
                                <a href="{{ route('presensi-siswa.rekap', [
                                    'kelas_id' => $matrixKelas->id,
                                    'bulan' => $matrix['bulan'],
                                ]) }}"
                                    class="btn btn-sm btn-light border shadow-sm" style="border-radius: 8px;">
                                    <i class="fa fa-chart-bar mr-1"></i> Rekap &amp; Persentase
                                </a>
                            </div>
                        </div>

                        <div class="matrix-scroll">
                            <table class="matrix-table">
                                <thead>
                                    <tr>
                                        <th class="matrix-sticky-col text-left px-3 py-2.5">
                                            Siswa ({{ count($matrixRows) }})
                                        </th>
                                        @foreach ($matrixDays as $dayInfo)
                                            <th class="{{ $dayInfo['is_today'] ? 'bg-orange-50 text-orange-700' : '' }}">
                                                <a href="{{ route('presensi-siswa.bulk', ['kelas_id' => $matrixKelas->id, 'tanggal' => $dayInfo['date']]) }}"
                                                    class="block rounded px-1 py-0.5 text-inherit hover:bg-orange-50 hover:text-orange-700 transition-colors"
                                                    style="text-decoration: none;"
                                                    title="Input massal tanggal {{ $dayInfo['date'] }}">
                                                    <div class="text-[11px] leading-tight">{{ $dayInfo['label'] }}</div>
                                                    <div class="text-[9px] font-semibold opacity-75">{{ $dayInfo['day_name'] }}</div>
                                                </a>
                                            </th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($matrixRows as $idx => $row)
                                        @php
                                            $anggota = $row['anggota'];
                                            $siswa = $anggota->siswa;
                                            $cells = $row['cells'];
                                        @endphp
                                        <tr class="hover:bg-slate-50/70 transition-colors">
                                            <td class="matrix-sticky-col px-3 py-2">
                                                <div class="flex items-center gap-2.5">
                                                    <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-slate-100 text-[11px] font-bold text-slate-600">
                                                        {{ $idx + 1 }}
                                                    </span>
                                                    <div class="min-w-0">
                                                        <div class="font-bold text-slate-800 truncate max-w-[165px]" title="{{ $siswa?->nama_lengkap }}">
                                                            {{ $siswa?->nama_lengkap ?? '-' }}
                                                        </div>
                                                        <div class="text-[10px] text-slate-400">NIS: {{ $siswa?->nis ?? '-' }}</div>
                                                    </div>
                                                </div>
                                            </td>

                                            @foreach ($matrixDays as $dayInfo)
                                                @php
                                                    $record = $cells[$dayInfo['day']] ?? null;
                                                    $colBg = $dayInfo['is_today'] ? 'bg-orange-50/50' : '';
                                                @endphp
                                                <td class="text-center {{ $colBg }}">
                                                    @if ($record)
                                                        @php
                                                            $style = $cellStyles[$record->status] ?? $cellStyles['alpa'];
                                                            $tooltip = $record->status_keterangan;
                                                        @endphp
                                                        <button type="button"
                                                            data-detail="{{ json_encode($buildStatusPayload($record, $siswa, $matrixKelas, $dayInfo['date']), JSON_UNESCAPED_SLASHES) }}"
                                                            data-tooltip="{{ $tooltip }}"
                                                            aria-label="{{ $siswa?->nama_lengkap ?? 'Siswa' }} — {{ $record->status_label }}"
                                                            class="matrix-cell-btn js-status-detail-trigger {{ $style['class'] }}">
                                                            {{ $style['code'] }}
                                                        </button>
                                                    @else
                                                        <span class="matrix-cell-empty" aria-label="Belum ada catatan presensi">
                                                            -
                                                        </span>
                                                    @endif
                                                </td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-50 font-bold text-slate-700">
                                        <td class="matrix-sticky-col px-3 py-2 text-xs">
                                            Total Hadir / Hari
                                        </td>
                                        @foreach ($matrixDays as $dayInfo)
                                            <td class="text-center text-[11px] {{ $dayInfo['is_today'] ? 'bg-orange-50 text-orange-700' : '' }}">
                                                {{ $matrixDailyTotals[$dayInfo['day']] ?? 0 }}
                                            </td>
                                        @endforeach
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
            </div>
    </div>

    <!-- TOOLTIP INSTAN (0MS) UNTUK STATUS PRESENSI -->
    <div id="presensiInstantTooltip" class="presensi-instant-tooltip" role="tooltip" aria-hidden="true"></div>

    <!-- MODAL DETAIL STATUS & HAPUS PRESENSI -->
    <div id="presensiStatusModal" class="presensi-detail-overlay" role="dialog" aria-modal="true"
        aria-hidden="true" aria-labelledby="presensiStatusModalTitle">
        <div class="presensi-detail-dialog">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-slate-50/70">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-white shadow-xs"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);">
                        <i class="fa fa-info-circle"></i>
                    </span>
                    <div>
                        <h5 id="presensiStatusModalTitle" class="text-sm font-extrabold text-slate-800 mb-0">
                            Detail Status Presensi
                        </h5>
                        <p id="presensiDetailTanggal" class="text-[11px] font-medium text-slate-500 mb-0">-</p>
                    </div>
                </div>
                <button type="button" data-close-presensi-modal
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-slate-200/70 hover:text-slate-700 transition cursor-pointer"
                    aria-label="Tutup">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <div class="p-5 space-y-4">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <div id="presensiDetailNama" class="text-base font-extrabold text-slate-900 truncate">-</div>
                        <div id="presensiDetailMeta" class="text-xs text-slate-500 mt-0.5">-</div>
                    </div>
                    <span id="presensiDetailBadge"
                        class="inline-flex shrink-0 items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border">
                        <span id="presensiDetailBadgeDot" class="h-1.5 w-1.5 rounded-full"></span>
                        <span id="presensiDetailBadgeText">-</span>
                    </span>
                </div>

                <div id="presensiDetailKeteranganBox" class="rounded-xl border px-4 py-3 text-sm font-medium leading-relaxed">
                    <div class="flex items-start gap-2.5">
                        <i id="presensiDetailKeteranganIcon" class="fa fa-clock mt-0.5 shrink-0"></i>
                        <span id="presensiDetailKeteranganText">-</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Jam Masuk</div>
                        <div id="presensiDetailJamMasuk" class="mt-0.5 text-sm font-extrabold font-mono text-slate-800">-</div>
                    </div>
                    <div class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Keterlambatan</div>
                        <div id="presensiDetailTerlambat" class="mt-0.5 text-sm font-extrabold text-slate-800">-</div>
                    </div>
                </div>
            </div>

            <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/70 flex flex-wrap items-center justify-between gap-2">
                <button type="button" data-close-presensi-modal class="btn btn-sm btn-light border"
                    style="border-radius: 8px;">
                    Tutup
                </button>
                <div class="flex items-center gap-2">
                    <a id="presensiDetailEditLink" href="#" class="btn btn-sm btn-warning text-white font-weight-bold shadow-xs"
                        style="border-radius: 8px;">
                        <i class="fa fa-edit mr-1"></i> Edit
                    </a>
                    <form id="presensiDetailDeleteForm" action="#" method="POST" class="d-inline">
                        @csrf
                        @method('DELETE')
                        @foreach ($preservedRedirectFields as $fieldName => $fieldValue)
                            <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
                        @endforeach
                        <button type="button" id="presensiDetailDeleteBtn"
                            class="btn btn-sm btn-danger text-white font-weight-bold shadow-xs delete-button"
                            style="border-radius: 8px;">
                            <i class="fa fa-trash mr-1"></i> Hapus
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL HAPUS PRESENSI MASSAL PER TANGGAL & KELAS -->
    <div id="presensiBulkDeleteModal" class="presensi-detail-overlay" role="dialog" aria-modal="true"
        aria-hidden="true" aria-labelledby="presensiBulkDeleteModalTitle">
        <div class="presensi-detail-dialog" style="max-width: 480px;">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between bg-rose-50/70">
                <div class="flex items-center gap-2.5">
                    <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl text-white shadow-xs bg-rose-600">
                        <i class="fa fa-trash-alt"></i>
                    </span>
                    <div>
                        <h5 id="presensiBulkDeleteModalTitle" class="text-sm font-extrabold text-slate-800 mb-0">
                            Hapus Presensi Per Tanggal
                        </h5>
                        <p class="text-[11px] font-medium text-slate-500 mb-0">
                            Hapus seluruh catatan presensi satu kelas pada tanggal tertentu.
                        </p>
                    </div>
                </div>
                <button type="button" data-close-bulk-delete-modal
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg text-slate-400 hover:bg-rose-100 hover:text-rose-700 transition cursor-pointer"
                    aria-label="Tutup">
                    <i class="fa fa-times"></i>
                </button>
            </div>

            <form id="presensiBulkDeleteForm" action="{{ route('presensi-siswa.bulk.destroy') }}" method="POST">
                @csrf
                @method('DELETE')
                @foreach ($preservedRedirectFields as $fieldName => $fieldValue)
                    <input type="hidden" name="{{ $fieldName }}" value="{{ $fieldValue }}">
                @endforeach

                <div class="p-5 space-y-4">
                    <div>
                        <label for="bulkDeleteKelasId" class="filter-label">Kelas</label>
                        <select id="bulkDeleteKelasId" name="kelas_id" class="filter-control" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($kelasOptions as $kelas)
                                <option value="{{ $kelas->id }}" @selected((int) $defaultBulkDeleteKelasId === (int) $kelas->id)>
                                    {{ $kelas->jenjang_kelas }} - {{ $kelas->nama_kelas }}
                                    @if ($kelas->tahunAjaran)
                                        ({{ $kelas->tahunAjaran->nama_tahun_ajaran }} - {{ $kelas->tahunAjaran->semester }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="bulkDeleteTanggal" class="filter-label">Tanggal Presensi</label>
                        <input id="bulkDeleteTanggal" type="date" name="tanggal" value="{{ $defaultBulkDeleteDate }}"
                            class="filter-control" required>
                    </div>

                    @if ($matrixKelas && $recordedDates !== [])
                        <div>
                            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">
                                Pilih Cepat Tanggal Tercatat ({{ $matrixKelas->nama_kelas }})
                            </div>
                            <div class="flex flex-wrap gap-1.5 max-h-28 overflow-y-auto">
                                @foreach ($recordedDates as $recDate)
                                    <button type="button"
                                        class="js-bulk-delete-date-chip inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-bold border border-slate-200 bg-slate-50 text-slate-700 hover:border-rose-300 hover:bg-rose-50 hover:text-rose-700 transition cursor-pointer"
                                        data-kelas-id="{{ $matrixKelas->id }}"
                                        data-tanggal="{{ $recDate['date'] }}">
                                        <span>{{ $recDate['short'] }}</span>
                                        <span class="px-1.5 py-0.2 rounded-full bg-white text-[10px] border border-slate-200 text-slate-500">
                                            {{ $recDate['count'] }}
                                        </span>
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-xs text-rose-800 flex items-start gap-2">
                        <i class="fa fa-exclamation-triangle mt-0.5 shrink-0 text-rose-600"></i>
                        <span>Seluruh data presensi siswa pada kelas dan tanggal terpilih akan dihapus permanen.</span>
                    </div>
                </div>

                <div class="px-5 py-3.5 border-t border-slate-100 bg-slate-50/70 flex items-center justify-between gap-2">
                    <button type="button" data-close-bulk-delete-modal class="btn btn-sm btn-light border"
                        style="border-radius: 8px;">
                        Batal
                    </button>
                    <button type="button" id="presensiBulkDeleteSubmitBtn"
                        class="btn btn-sm btn-danger text-white font-weight-bold shadow-xs delete-button"
                        style="border-radius: 8px;">
                        <i class="fa fa-trash-alt mr-1"></i> Hapus Semua Tanggal Ini
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        (function () {
            const tahunSelect = document.getElementById('filter_tahun_ajaran_id');
            const kelasSelect = document.getElementById('filter_kelas_id');

            function syncKelasOptionsByTahunAjaran() {
                if (!tahunSelect || !kelasSelect) {
                    return;
                }

                const selectedTahunId = tahunSelect.value;
                let selectedVisible = true;

                Array.from(kelasSelect.options).forEach((option, index) => {
                    if (index === 0) {
                        return;
                    }

                    const matches = !selectedTahunId || option.dataset.tahunAjaranId === selectedTahunId;
                    option.hidden = !matches;
                    option.disabled = !matches;
                    if (!matches && option.selected) {
                        selectedVisible = false;
                    }
                });

                if (!selectedVisible) {
                    kelasSelect.value = '';
                }
            }

            if (tahunSelect) {
                tahunSelect.addEventListener('change', syncKelasOptionsByTahunAjaran);
                syncKelasOptionsByTahunAjaran();
            }

            const instantTooltip = document.getElementById('presensiInstantTooltip');
            const matrixScrollContainer = document.querySelector('.matrix-scroll');
            let activeTooltipTarget = null;

            function hideInstantTooltip() {
                if (!instantTooltip) {
                    return;
                }

                activeTooltipTarget = null;
                instantTooltip.classList.remove('is-visible');
                instantTooltip.setAttribute('aria-hidden', 'true');
            }

            function showInstantTooltip(target) {
                if (!instantTooltip || !target) {
                    return;
                }

                const text = target.getAttribute('data-tooltip');
                if (!text) {
                    hideInstantTooltip();
                    return;
                }

                activeTooltipTarget = target;
                instantTooltip.textContent = text;
                instantTooltip.classList.add('is-visible');
                instantTooltip.setAttribute('aria-hidden', 'false');

                const rect = target.getBoundingClientRect();
                const tipRect = instantTooltip.getBoundingClientRect();
                const margin = 8;
                const gap = 8;

                let left = rect.left + (rect.width / 2) - (tipRect.width / 2);
                left = Math.max(margin, Math.min(left, window.innerWidth - tipRect.width - margin));

                let top = rect.top - tipRect.height - gap;
                if (top < margin) {
                    top = rect.bottom + gap;
                }

                instantTooltip.style.left = Math.round(left) + 'px';
                instantTooltip.style.top = Math.round(top) + 'px';
            }

            document.addEventListener('mouseover', function (event) {
                const trigger = event.target.closest('.js-status-detail-trigger[data-tooltip]');
                if (trigger) {
                    if (activeTooltipTarget !== trigger) {
                        showInstantTooltip(trigger);
                    }
                    return;
                }

                if (activeTooltipTarget) {
                    hideInstantTooltip();
                }
            });

            document.addEventListener('focusin', function (event) {
                const trigger = event.target.closest('.js-status-detail-trigger[data-tooltip]');
                if (trigger) {
                    showInstantTooltip(trigger);
                }
            });

            document.addEventListener('focusout', function (event) {
                if (event.target.closest('.js-status-detail-trigger[data-tooltip]')) {
                    hideInstantTooltip();
                }
            });

            document.addEventListener('pointerdown', function (event) {
                if (event.target.closest('.js-status-detail-trigger')) {
                    hideInstantTooltip();
                }
            });

            if (matrixScrollContainer) {
                matrixScrollContainer.addEventListener('scroll', hideInstantTooltip, { passive: true });
            }
            document.documentElement.addEventListener('mouseleave', hideInstantTooltip);
            window.addEventListener('scroll', hideInstantTooltip, { passive: true });
            window.addEventListener('resize', hideInstantTooltip, { passive: true });

            const statusModal = document.getElementById('presensiStatusModal');
            const detailNama = document.getElementById('presensiDetailNama');
            const detailMeta = document.getElementById('presensiDetailMeta');
            const detailTanggal = document.getElementById('presensiDetailTanggal');
            const detailBadge = document.getElementById('presensiDetailBadge');
            const detailBadgeDot = document.getElementById('presensiDetailBadgeDot');
            const detailBadgeText = document.getElementById('presensiDetailBadgeText');
            const detailKeteranganBox = document.getElementById('presensiDetailKeteranganBox');
            const detailKeteranganIcon = document.getElementById('presensiDetailKeteranganIcon');
            const detailKeteranganText = document.getElementById('presensiDetailKeteranganText');
            const detailJamMasuk = document.getElementById('presensiDetailJamMasuk');
            const detailTerlambat = document.getElementById('presensiDetailTerlambat');
            const detailEditLink = document.getElementById('presensiDetailEditLink');
            const detailDeleteForm = document.getElementById('presensiDetailDeleteForm');

            const statusThemeMap = {
                hadir: {
                    badge: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                    dot: 'bg-emerald-500',
                    box: 'bg-emerald-50 border-emerald-200 text-emerald-800',
                    icon: 'fa fa-check-circle text-emerald-600'
                },
                terlambat: {
                    badge: 'bg-amber-50 text-amber-800 border-amber-200',
                    dot: 'bg-amber-500',
                    box: 'bg-amber-50 border-amber-200 text-amber-900',
                    icon: 'fa fa-clock text-amber-600'
                },
                izin: {
                    badge: 'bg-sky-50 text-sky-700 border-sky-200',
                    dot: 'bg-sky-500',
                    box: 'bg-sky-50 border-sky-200 text-sky-800',
                    icon: 'fa fa-envelope-open-text text-sky-600'
                },
                sakit: {
                    badge: 'bg-indigo-50 text-indigo-700 border-indigo-200',
                    dot: 'bg-indigo-500',
                    box: 'bg-indigo-50 border-indigo-200 text-indigo-800',
                    icon: 'fa fa-notes-medical text-indigo-600'
                },
                alpa: {
                    badge: 'bg-rose-50 text-rose-700 border-rose-200',
                    dot: 'bg-rose-500',
                    box: 'bg-rose-50 border-rose-200 text-rose-800',
                    icon: 'fa fa-times-circle text-rose-600'
                }
            };

            function openStatusDetailModal(detail) {
                if (!statusModal) {
                    return;
                }

                hideInstantTooltip();

                const theme = statusThemeMap[detail.status] || statusThemeMap.alpa;
                detailNama.textContent = detail.nama || '-';
                detailMeta.textContent = 'NIS: ' + (detail.nis || '-') + ' • ' + (detail.kelas || '-');
                detailTanggal.textContent = detail.tanggal || '-';
                detailBadge.className = 'inline-flex shrink-0 items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold border ' + theme.badge;
                detailBadgeDot.className = 'h-1.5 w-1.5 rounded-full ' + theme.dot;
                detailBadgeText.textContent = detail.status_label || '-';
                detailKeteranganBox.className = 'rounded-xl border px-4 py-3 text-sm font-medium leading-relaxed ' + theme.box;
                detailKeteranganIcon.className = theme.icon + ' mt-0.5 shrink-0';
                detailKeteranganText.textContent = detail.keterangan || '-';
                detailJamMasuk.textContent = detail.jam_masuk || '-';
                detailTerlambat.textContent = Number(detail.terlambat_menit) > 0
                    ? detail.terlambat_menit + ' menit'
                    : '0 menit (Tepat waktu)';
                detailEditLink.href = detail.edit_url || '#';
                detailDeleteForm.action = detail.destroy_url || '#';

                statusModal.classList.add('is-open');
                statusModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeStatusDetailModal() {
                if (!statusModal) {
                    return;
                }

                statusModal.classList.remove('is-open');
                statusModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            document.addEventListener('click', function (event) {
                const trigger = event.target.closest('.js-status-detail-trigger');
                if (trigger) {
                    event.preventDefault();
                    try {
                        const payload = JSON.parse(trigger.getAttribute('data-detail') || '{}');
                        openStatusDetailModal(payload);
                    } catch (e) {
                        // Ignore invalid payload
                    }
                    return;
                }

                if (event.target.closest('[data-close-presensi-modal]') || event.target === statusModal) {
                    closeStatusDetailModal();
                    return;
                }

                const deleteBtn = event.target.closest('#presensiStatusModal .delete-button, #presensiBulkDeleteModal .delete-button');
                if (deleteBtn && typeof window.Swal === 'undefined') {
                    event.preventDefault();
                    if (window.confirm('Yakin ingin menghapus data presensi ini?')) {
                        deleteBtn.closest('form').submit();
                    }
                }
            });

            const bulkDeleteModal = document.getElementById('presensiBulkDeleteModal');
            const bulkDeleteForm = document.getElementById('presensiBulkDeleteForm');
            const bulkDeleteKelasSelect = document.getElementById('bulkDeleteKelasId');
            const bulkDeleteTanggalInput = document.getElementById('bulkDeleteTanggal');
            const bulkDeleteSubmitBtn = document.getElementById('presensiBulkDeleteSubmitBtn');

            function openBulkDeleteModal() {
                if (!bulkDeleteModal) {
                    return;
                }

                bulkDeleteModal.classList.add('is-open');
                bulkDeleteModal.setAttribute('aria-hidden', 'false');
                document.body.style.overflow = 'hidden';
            }

            function closeBulkDeleteModal() {
                if (!bulkDeleteModal) {
                    return;
                }

                bulkDeleteModal.classList.remove('is-open');
                bulkDeleteModal.setAttribute('aria-hidden', 'true');
                document.body.style.overflow = '';
            }

            const openBulkDeleteBtn = document.getElementById('openBulkDeleteModalBtn');
            if (openBulkDeleteBtn) {
                openBulkDeleteBtn.addEventListener('click', openBulkDeleteModal);
            }

            if (bulkDeleteModal) {
                bulkDeleteModal.addEventListener('click', function (event) {
                    if (event.target === bulkDeleteModal || event.target.closest('[data-close-bulk-delete-modal]')) {
                        closeBulkDeleteModal();
                        return;
                    }

                    const chip = event.target.closest('.js-bulk-delete-date-chip');
                    if (chip) {
                        if (bulkDeleteKelasSelect && chip.dataset.kelasId) {
                            bulkDeleteKelasSelect.value = chip.dataset.kelasId;
                        }
                        if (bulkDeleteTanggalInput && chip.dataset.tanggal) {
                            bulkDeleteTanggalInput.value = chip.dataset.tanggal;
                        }
                    }
                });
            }

            if (bulkDeleteSubmitBtn && bulkDeleteForm) {
                bulkDeleteSubmitBtn.addEventListener('click', function (event) {
                    if (!bulkDeleteForm.reportValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                }, true);
            }

            document.addEventListener('keydown', function (event) {
                if (event.key !== 'Escape') {
                    return;
                }

                hideInstantTooltip();
                if (statusModal && statusModal.classList.contains('is-open')) {
                    closeStatusDetailModal();
                } else if (bulkDeleteModal && bulkDeleteModal.classList.contains('is-open')) {
                    closeBulkDeleteModal();
                }
            });
        })();
    </script>
@endpush
