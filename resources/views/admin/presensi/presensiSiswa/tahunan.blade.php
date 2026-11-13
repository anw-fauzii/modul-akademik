@extends('layouts.appui')

@section('title')
    <title>Statistik Presensi Tahunan - SIPIT Akademik</title>
@endsection

@push('css')
    <style>
        .tahunan-bento-top,
        .tahunan-bento-analytics {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.9rem;
            margin-bottom: 1rem;
            align-items: stretch;
        }

        @media (min-width: 1024px) {
            .tahunan-bento-top {
                grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
            }
        }

        @media (min-width: 1200px) {
            .tahunan-bento-analytics {
                grid-template-columns: minmax(0, 7fr) minmax(0, 5fr);
            }
        }

        .tahunan-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
            border-radius: 16px;
            padding: 20px 24px;
            color: #ffffff;
            box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.3);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 1rem;
            height: 100%;
        }

        .tahunan-hero-ratio {
            padding-top: 0.85rem;
            border-top: 1px solid rgba(255, 255, 255, 0.12);
        }

        .tahunan-bento-top .presensi-rate-grid.tahunan-rate-stack {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
            margin-bottom: 0;
            height: 100%;
        }

        .tahunan-bento-top .presensi-rate-grid.tahunan-rate-stack .presensi-rate-card {
            flex: 1;
        }

        .tahunan-chart-card,
        .tahunan-side-panel {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 16px -6px rgba(15, 23, 42, 0.06);
            display: flex;
            flex-direction: column;
            height: 100%;
            overflow: hidden;
        }

        .tahunan-chart-scroll {
            overflow-x: auto;
            padding-bottom: 2px;
        }

        .tahunan-chart-canvas {
            position: relative;
            min-width: 440px;
            padding: 16px 12px 6px;
            border-radius: 12px;
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border: 1px solid #f1f5f9;
        }

        .tahunan-chart-bars-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(30px, 1fr));
            gap: 6px;
            align-items: end;
            height: 186px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 0;
        }

        .tahunan-chart-col {
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100%;
            justify-content: flex-end;
            gap: 6px;
        }

        .tahunan-chart-pair {
            display: flex;
            align-items: flex-end;
            justify-content: center;
            gap: 3px;
            width: 100%;
            height: 144px;
        }

        .tahunan-bar-wrap {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: flex-end;
            height: 100%;
            width: 12px;
        }

        .tahunan-bar {
            width: 100%;
            border-radius: 5px 5px 2px 2px;
            transition: height 0.25s ease, opacity 0.2s ease;
            min-height: 2px;
        }

        .tahunan-bar--hadir {
            background: linear-gradient(180deg, #10b981 0%, #059669 100%);
            box-shadow: 0 4px 10px -4px rgba(16, 185, 129, 0.5);
        }

        .tahunan-bar--telat {
            background: linear-gradient(180deg, #fbbf24 0%, #d97706 100%);
            box-shadow: 0 4px 10px -4px rgba(217, 119, 6, 0.45);
        }

        .tahunan-bar--muted {
            background: #e2e8f0;
            box-shadow: none;
        }

        .tahunan-bar-val {
            font-size: 9px;
            font-weight: 800;
            color: #475569;
            margin-bottom: 3px;
            line-height: 1;
            white-space: nowrap;
        }

        .tahunan-month-label {
            font-size: 11px;
            font-weight: 700;
            color: #334155;
            padding: 4px 0 2px;
            text-align: center;
        }

        .tahunan-mini-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.65rem;
        }

        @media (min-width: 540px) {
            .tahunan-mini-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        .tahunan-mini-card {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 0.8rem 0.9rem;
            transition: border-color 0.18s ease, background-color 0.18s ease, box-shadow 0.18s ease;
        }

        .tahunan-mini-card--link:hover {
            background: #ffffff;
            border-color: #fb923c;
            box-shadow: 0 6px 16px -8px rgba(234, 88, 12, 0.28);
        }

        .tahunan-tab-group {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 4px;
            padding: 4px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
        }

        .tahunan-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 0.42rem 0.85rem;
            font-size: 0.8125rem;
            font-weight: 700;
            color: #475569;
            background: transparent;
            border: none;
            border-radius: 7px;
            cursor: pointer;
            transition: all 0.16s ease;
        }

        .tahunan-tab-btn:hover {
            color: #0f172a;
        }

        .tahunan-tab-btn.is-active {
            color: #ffffff;
            background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);
            box-shadow: 0 4px 10px -4px rgba(234, 88, 12, 0.45);
        }

        .tahunan-tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            padding: 0 0.38rem;
            height: 1.2rem;
            border-radius: 999px;
            font-size: 0.6875rem;
            font-weight: 800;
            background: rgba(15, 23, 42, 0.08);
            color: inherit;
        }

        .tahunan-tab-btn.is-active .tahunan-tab-count {
            background: rgba(255, 255, 255, 0.22);
            color: #ffffff;
        }

        .tahunan-tab-panel {
            display: none;
        }

        .tahunan-tab-panel.is-active {
            display: block;
        }

        .tahunan-table-scroll {
            max-height: 460px;
            overflow: auto;
        }

        .tahunan-table-scroll thead th {
            position: sticky;
            top: 0;
            z-index: 5;
            background: #f8fafc !important;
            box-shadow: inset 0 -1px 0 #e2e8f0;
        }

        @media print {
            #appSidebar,
            header,
            .no-print {
                display: none !important;
            }

            .app-main__inner {
                padding: 0 !important;
            }

            .tahunan-tab-panel {
                display: block !important;
            }

            .tahunan-table-scroll {
                max-height: none !important;
                overflow: visible !important;
            }
        }
    </style>
@endpush

@section('content')
    @php
        $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
        $namaTahunAjaranOptions = $namaTahunAjaranOptions ?? collect();
        $semesterOptions = $semesterOptions ?? \App\Models\Admin\DataMaster\TahunAjaran::SEMESTER_OPTIONS;
        $semesterRows = $semesterRows ?? [];

        $selectedNamaTa = trim((string) ($filters['nama_tahun_ajaran'] ?? ''));
        $selectedSemesterFilter = strtolower(trim((string) ($filters['semester'] ?? '')));

        $drillTahunAjaranId = $selectedKelas?->akademik_master_tahun_ajaran_id
            ?? ($tahunAjaranOptions->first(function ($ta) use ($selectedNamaTa, $selectedSemesterFilter): bool {
                if ($selectedNamaTa !== '' && trim((string) $ta->nama_tahun_ajaran) !== $selectedNamaTa) {
                    return false;
                }
                if ($selectedSemesterFilter !== '') {
                    return strtolower(trim((string) $ta->semester)) === $selectedSemesterFilter;
                }

                return (bool) $ta->is_active;
            })?->id)
            ?? ($tahunAjaranOptions->first(
                fn ($ta): bool => $selectedNamaTa === '' || trim((string) $ta->nama_tahun_ajaran) === $selectedNamaTa
            )?->id)
            ?? ($activeTahunAjaran?->id ?? null);

        $totalCatatan = (int) ($summary['total_catatan'] ?? 0);
        $batasJamMasuk = \App\Models\Admin\Presensi\PresensiSiswa::BATAS_JAM_MASUK;
        $pctHadirBar = $totalCatatan > 0 ? round(($summary['hadir'] / $totalCatatan) * 100, 1) : 0;
        $pctTelatBar = $totalCatatan > 0 ? round(($summary['terlambat'] / $totalCatatan) * 100, 1) : 0;
        $pctIzinBar = $totalCatatan > 0 ? round(($summary['izin'] / $totalCatatan) * 100, 1) : 0;
        $pctSakitBar = $totalCatatan > 0 ? round(($summary['sakit'] / $totalCatatan) * 100, 1) : 0;
        $pctAlpaBar = $totalCatatan > 0 ? round(($summary['alpa'] / $totalCatatan) * 100, 1) : 0;
        $totalTidakHadir = (int) ($summary['izin'] ?? 0) + (int) ($summary['sakit'] ?? 0) + (int) ($summary['alpa'] ?? 0);
        $pctTidakHadirBar = $totalCatatan > 0 ? round(($totalTidakHadir / $totalCatatan) * 100, 1) : 0;
        $summaryPredikat = $summary['predikat'] ?? [
            'label' => 'Belum Ada Data',
            'class' => 'bg-slate-100 text-slate-600 border-slate-200',
        ];
        $hasComparisonCards = $mode === 'all_classes' && (count($semesterRows) > 1 || count($jenjangRows) > 1);
        $rekapTabCount = $mode === 'class_detail' ? count($studentRows) : count($classRows);
        $rekapTabLabel = $mode === 'class_detail' ? 'Rekap Per Siswa' : 'Rekap Per Kelas';

        $bulanTerbaik = $highlights['bulan_terbaik'] ?? null;
        $bulanTelat = $highlights['bulan_telat_tertinggi'] ?? null;
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title no-print">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-display1" style="color: white;"></i>
                    </div>
                    <div>
                        Statistik Presensi Tahunan
                        <div class="page-title-subheading">
                            Analisis tren kehadiran, keterlambatan, dan perbandingan performa siswa berdasarkan Tahun Ajaran.
                        </div>
                    </div>
                </div>
                <div class="page-title-actions d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                    <a href="{{ route('presensi-siswa.bulk', array_filter([
                        'kelas_id' => $filters['kelas_id'],
                    ])) }}"
                        class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-layer-group mr-1"></i> Input Data
                    </a>
                </div>
            </div>
        </div>

        <!-- GRID BARIS 1: HERO PERIODE & 2x2 INSIGHT UTAMA -->
        <div class="tahunan-bento-top">
            <div class="tahunan-hero">
                <div>
                    <div class="flex flex-wrap items-center gap-2 mb-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-500/20 text-orange-300 border border-orange-400/30">
                            <i class="fa fa-calendar-alt mr-1.5"></i> {{ $period['label'] }}
                        </span>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/10 text-slate-200 border border-white/15">
                            {{ $filters['include_weekend'] ? 'Semua Hari (Sen–Min)' : 'Hari Sekolah (Sen–Jum)' }}
                        </span>
                        @if ($filters['jenjang'])
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-500/20 text-amber-200 border border-amber-400/30">
                                Jenjang {{ $filters['jenjang'] }}
                            </span>
                        @endif
                        @if ($selectedKelas && $selectedKelas->tahunAjaran)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                                {{ $selectedKelas->tahunAjaran->full_label }}
                            </span>
                        @endif
                    </div>

                    <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight mb-0">
                        @if ($mode === 'class_detail' && $selectedKelas)
                            Statistik Presensi Tahunan — {{ $selectedKelas->jenjang_kelas }} {{ $selectedKelas->nama_kelas }}
                        @elseif ($mode === 'class_detail')
                            Statistik Presensi Tahunan Kelas
                        @else
                            Statistik Presensi Tahunan Seluruh Kelas
                        @endif
                    </h2>
                    <p class="mt-1.5 text-xs sm:text-sm text-slate-300 flex flex-wrap items-center gap-x-4 gap-y-1 mb-0">
                        <span>
                            <i class="fa fa-users text-indigo-300 mr-1"></i>
                            Total Siswa: <strong class="text-white">{{ $summary['siswa'] }}</strong>
                        </span>
                        @if ($selectedKelas?->waliKelas)
                            <span>
                                <i class="fa fa-user-tie text-amber-300 mr-1"></i>
                                Wali Kelas: <strong class="text-white">{{ $selectedKelas->waliKelas->nama_gelar }}</strong>
                            </span>
                        @elseif ($mode === 'all_classes')
                            <span>
                                <i class="fa fa-school text-amber-300 mr-1"></i>
                                Jumlah Kelas: <strong class="text-white">{{ $summary['jumlah_kelas'] ?? count($classRows) }}</strong>
                            </span>
                        @endif
                        <span>
                            <i class="fa fa-calendar-week text-emerald-300 mr-1"></i>
                            Hari Sekolah: <strong class="text-white">{{ $period['weekdays_count'] }} hari Sen–Jum</strong>
                        </span>
                        <span>
                            <i class="fa fa-clock text-rose-300 mr-1"></i>
                            Akumulasi Telat: <strong class="text-white">{{ $summary['total_telat_menit'] }} menit</strong>
                        </span>
                    </p>
                </div>

                @if ($totalCatatan > 0)
                    <div class="tahunan-hero-ratio">
                        <div class="flex flex-wrap items-center justify-between text-[11px] text-slate-300 mb-1.5 gap-2">
                            <span class="font-bold text-slate-200">Proporsi Status ({{ $totalCatatan }} catatan)</span>
                            <span class="flex flex-wrap gap-x-3 gap-y-1">
                                <span>Tepat: <strong class="text-emerald-300">{{ $pctHadirBar }}%</strong></span>
                                <span>Telat: <strong class="text-amber-300">{{ $pctTelatBar }}%</strong></span>
                                <span>Izin: <strong class="text-sky-300">{{ $pctIzinBar }}%</strong></span>
                                <span>Sakit: <strong class="text-indigo-300">{{ $pctSakitBar }}%</strong></span>
                                <span>Alpa: <strong class="text-rose-300">{{ $pctAlpaBar }}%</strong></span>
                            </span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-white/15 overflow-hidden flex">
                            <div style="width: {{ $pctHadirBar }}%" class="bg-emerald-400"></div>
                            <div style="width: {{ $pctTelatBar }}%" class="bg-amber-400"></div>
                            <div style="width: {{ $pctIzinBar }}%" class="bg-sky-400"></div>
                            <div style="width: {{ $pctSakitBar }}%" class="bg-indigo-400"></div>
                            <div style="width: {{ $pctAlpaBar }}%" class="bg-rose-400"></div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- 2 KARTU PERSENTASE UTAMA TAHUNAN -->
            <div class="h-full">
                @include('admin.presensi.presensiSiswa._status_stats', [
                    'showStatusCards' => false,
                    'rateGridClass' => 'tahunan-rate-stack',
                    'rateCards' => [
                        [
                            'label' => '% Kehadiran Tahunan',
                            'value' => $summary['persentase_hadir'],
                            'percent' => $summary['persentase_hadir'],
                            'badge' => 'Hadir Efektif',
                            'subtitle' => ($summary['hadir_efektif'] ?? ($summary['hadir'] + $summary['terlambat'])).' dari '.$totalCatatan.' catatan • Rata-rata bulanan '.($highlights['rata_kehadiran_bulanan'] ?? 0).'%',
                            'icon' => 'fa fa-chart-line',
                            'tone' => 'indigo',
                        ],
                        [
                            'label' => '% Keterlambatan Tahunan',
                            'value' => $summary['persentase_terlambat'] ?? $pctTelatBar,
                            'percent' => $summary['persentase_terlambat'] ?? $pctTelatBar,
                            'badge' => 'Batas '.$batasJamMasuk,
                            'subtitle' => $summary['terlambat'].' dari '.$totalCatatan.' catatan • Rata-rata '.($highlights['rata_menit_telat'] ?? 0).' mnt/kejadian',
                            'icon' => 'fa fa-stopwatch',
                            'tone' => 'amber',
                        ],
                    ],
                ])
            </div>
        </div>

        <!-- PANEL FILTER STATISTIK TAHUNAN BERBASIS TAHUN AJARAN -->
        <div class="filter-card no-print">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-calendar-alt"></i></span>
                    <span>Filter Statistik Presensi Tahun Ajaran</span>
                    @if ($hasExplicitFilter ?? false)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="filter-count-pill">
                        <i class="fa fa-calendar-week text-primary"></i>
                        Hari Kerja: <strong>{{ $period['weekdays_count'] }}</strong> Sen–Jum
                    </span>
                    <span class="filter-count-pill">
                        <i class="fa fa-clipboard-check text-success"></i>
                        Total Catatan: <strong>{{ $totalCatatan }}</strong>
                    </span>
                    @if ($hasExplicitFilter ?? false)
                        <a href="{{ route('presensi-siswa.tahunan') }}" class="filter-btn filter-btn-secondary"
                            style="height: 1.9rem; min-height: 1.9rem; padding: 0 0.7rem; font-size: 0.75rem;">
                            <i class="fa fa-undo"></i> Reset
                        </a>
                    @endif
                </div>
            </div>

            <div class="filter-card-body">
                <form method="GET" action="{{ route('presensi-siswa.tahunan') }}"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <div class="lg:col-span-3">
                        <label for="tahunan_nama_tahun_ajaran" class="filter-label">Tahun Ajaran</label>
                        <select id="tahunan_nama_tahun_ajaran" name="nama_tahun_ajaran" class="filter-control">
                            @if ($namaTahunAjaranOptions->isEmpty())
                                <option value="">Belum Ada Tahun Ajaran</option>
                            @else
                                @foreach ($namaTahunAjaranOptions as $namaTa)
                                    <option value="{{ $namaTa }}" @selected($selectedNamaTa === (string) $namaTa)>
                                        TA {{ $namaTa }}@if ($activeTahunAjaran && trim((string) $activeTahunAjaran->nama_tahun_ajaran) === (string) $namaTa) (Aktif)@endif
                                    </option>
                                @endforeach
                                @if ($selectedNamaTa !== '' && ! $namaTahunAjaranOptions->contains($selectedNamaTa))
                                    <option value="{{ $selectedNamaTa }}" selected>TA {{ $selectedNamaTa }}</option>
                                @endif
                            @endif
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <label for="tahunan_semester" class="filter-label">Semester</label>
                        <select id="tahunan_semester" name="semester" class="filter-control">
                            <option value="" @selected($selectedSemesterFilter === '')>Semua Semester (1 Tahun Penuh)</option>
                            @foreach ($semesterOptions as $semCode => $semLabel)
                                <option value="{{ $semCode }}" @selected($selectedSemesterFilter === (string) $semCode)>
                                    {{ $semLabel }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="tahunan_jenjang" class="filter-label">Jenjang</label>
                        <select id="tahunan_jenjang" name="jenjang" class="filter-control">
                            <option value="">Semua Jenjang</option>
                            @foreach ($jenjangOptions as $jenjangOpt)
                                <option value="{{ $jenjangOpt }}" @selected($filters['jenjang'] === $jenjangOpt)>
                                    {{ $jenjangOpt }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="tahunan_kelas_id" class="filter-label">Kelas (Opsional)</label>
                        <select id="tahunan_kelas_id" name="kelas_id" class="filter-control">
                            <option value="">Semua Kelas</option>
                            @foreach ($kelasOptions as $kelas)
                                <option value="{{ $kelas->id }}"
                                    data-nama-tahun-ajaran="{{ trim((string) ($kelas->tahunAjaran?->nama_tahun_ajaran ?? '')) }}"
                                    data-semester="{{ strtolower(trim((string) ($kelas->tahunAjaran?->semester ?? ''))) }}"
                                    data-jenjang="{{ strtoupper(trim((string) $kelas->jenjang_kelas)) }}"
                                    @selected($filters['kelas_id'] === $kelas->id)>
                                    {{ $kelas->jenjang_kelas }} - {{ $kelas->nama_kelas }}
                                    @if ($kelas->tahunAjaran)
                                        (Smt {{ strtoupper((string) $kelas->tahunAjaran->semester) }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <button type="submit" class="filter-btn filter-btn-primary w-100 justify-content-center">
                            <i class="fa fa-chart-line"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

            <!-- GRID BARIS 2: GRAFIK TREN BULANAN + PERBANDINGAN SEMESTER / JENJANG -->
            <div class="tahunan-bento-analytics">
                <div class="tahunan-chart-card">
                    <div class="px-4 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <h5 class="mb-0 font-weight-bold text-slate-800 text-base">
                                <i class="fa fa-chart-bar text-orange-600 mr-1.5"></i>
                                Grafik Tren Bulanan
                            </h5>
                            <div class="text-xs text-slate-500 mt-0.5">
                                @if ($bulanTerbaik || $bulanTelat)
                                    @if ($bulanTerbaik)
                                        Terbaik: <strong class="text-emerald-700">{{ $bulanTerbaik['label'] }} ({{ $bulanTerbaik['persentase_hadir'] }}%)</strong>
                                    @endif
                                    @if ($bulanTerbaik && $bulanTelat)
                                        <span class="mx-1">&bull;</span>
                                    @endif
                                    @if ($bulanTelat)
                                        Puncak Telat: <strong class="text-amber-700">{{ $bulanTelat['label'] }} ({{ $bulanTelat['terlambat'] }}x)</strong>
                                    @endif
                                @else
                                    Kehadiran efektif vs keterlambatan sepanjang {{ $period['label'] }}.
                                @endif
                            </div>
                        </div>
                        <div class="d-flex flex-wrap align-items-center gap-3 text-xs font-bold">
                            <span class="inline-flex items-center gap-1.5 text-emerald-700">
                                <span class="inline-block w-2.5 h-2.5 rounded bg-emerald-500"></span> % Hadir
                            </span>
                            <span class="inline-flex items-center gap-1.5 text-amber-700">
                                <span class="inline-block w-2.5 h-2.5 rounded bg-amber-500"></span> % Telat
                            </span>
                        </div>
                    </div>

                    <div class="p-3 p-sm-4 flex-1 d-flex flex-column justify-content-center">
                        @if (count($monthlyRows) === 0)
                            <div class="text-center py-8 text-slate-500 text-sm">
                                Belum ada data presensi tercatat untuk menampilkan tren bulanan pada filter ini.
                            </div>
                        @else
                            <div class="tahunan-chart-scroll">
                                <div class="tahunan-chart-canvas">
                                    <div class="tahunan-chart-bars-row">
                                        @foreach ($monthlyRows as $mRow)
                                            @php
                                                $mCounts = $mRow['counts'];
                                                $mHasData = ((int) $mCounts['total_catatan']) > 0;
                                                $mHadirHeight = $mHasData ? max(4, (float) $mCounts['persentase_hadir']) : 2;
                                                $mTelatHeight = $mHasData && ((float) $mCounts['persentase_terlambat']) > 0
                                                    ? max(4, (float) $mCounts['persentase_terlambat'])
                                                    : 2;
                                                $barTooltip = "{$mRow['label']} • Hadir Efektif: {$mCounts['persentase_hadir']}% ({$mCounts['hadir_efektif']}/{$mCounts['total_catatan']}), Terlambat: {$mCounts['persentase_terlambat']}% ({$mCounts['terlambat']}x)";
                                            @endphp
                                            <div class="tahunan-chart-col" title="{{ $barTooltip }}">
                                                <div class="tahunan-chart-pair">
                                                    <div class="tahunan-bar-wrap">
                                                        <span class="tahunan-bar-val">
                                                            {{ $mHasData ? $mCounts['persentase_hadir'].'%' : '-' }}
                                                        </span>
                                                        <div class="tahunan-bar {{ $mHasData ? 'tahunan-bar--hadir' : 'tahunan-bar--muted' }}"
                                                            style="height: {{ $mHadirHeight }}%;"></div>
                                                    </div>
                                                    <div class="tahunan-bar-wrap">
                                                        <span class="tahunan-bar-val text-amber-700">
                                                            {{ $mHasData && $mCounts['terlambat'] > 0 ? $mCounts['persentase_terlambat'].'%' : '' }}
                                                        </span>
                                                        <div class="tahunan-bar {{ $mHasData && $mCounts['terlambat'] > 0 ? 'tahunan-bar--telat' : 'tahunan-bar--muted' }}"
                                                            style="height: {{ $mTelatHeight }}%;"></div>
                                                    </div>
                                                </div>
                                                <div class="tahunan-month-label">
                                                    {{ $mRow['short_label'] }}
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- PANEL KANAN: PERBANDINGAN SEMESTER & JENJANG / RINGKASAN TARGET -->
                <div class="tahunan-side-panel">
                    @if ($hasComparisonCards)
                        <div class="px-4 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h5 class="mb-0 font-weight-bold text-slate-800 text-base">
                                    <i class="fa fa-layer-group text-orange-600 mr-1.5"></i>
                                    Perbandingan Semester &amp; Jenjang
                                </h5>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Klik semester untuk memfokuskan analisis TA {{ $selectedNamaTa }}.
                                </div>
                            </div>
                        </div>

                        <div class="p-3 p-sm-4 d-flex flex-column gap-3 flex-1 justify-content-between">
                            @if (count($semesterRows) > 1)
                                <div>
                                    <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">
                                        Perbandingan Per Semester (TA {{ $selectedNamaTa }})
                                    </div>
                                    <div class="tahunan-mini-grid">
                                        @foreach ($semesterRows as $sRow)
                                            @php
                                                $sCounts = $sRow['counts'];
                                            @endphp
                                            <a href="{{ route('presensi-siswa.tahunan', array_filter([
                                                'nama_tahun_ajaran' => $sRow['nama_tahun_ajaran'],
                                                'semester' => $sRow['semester'],
                                                'jenjang' => $filters['jenjang'],
                                            ], fn ($v): bool => $v !== null && $v !== '')) }}"
                                                class="tahunan-mini-card tahunan-mini-card--link d-block text-decoration-none">
                                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                                    <div>
                                                        <span class="badge badge-primary px-2 py-0.5 text-[11px]" style="border-radius: 6px;">
                                                            {{ $sRow['semester_label'] }}
                                                        </span>
                                                        <div class="text-[11px] text-slate-500 mt-1">
                                                            {{ $sRow['kelas_count'] }} kelas &bull; {{ $sRow['siswa_count'] }} siswa
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-base font-extrabold text-indigo-700 leading-tight">{{ $sCounts['persentase_hadir'] }}%</div>
                                                        <div class="text-[10px] font-bold text-amber-700">{{ $sCounts['persentase_terlambat'] }}% telat</div>
                                                    </div>
                                                </div>
                                                <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                                    <div style="width: {{ min(100, (float) $sCounts['persentase_hadir']) }}%"
                                                        class="h-full bg-emerald-500 rounded-full"></div>
                                                </div>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            @if (count($jenjangRows) > 1)
                                <div>
                                    <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400 mb-2">
                                        Ringkasan Tahunan Per Jenjang
                                    </div>
                                    <div class="tahunan-mini-grid">
                                        @foreach ($jenjangRows as $jRow)
                                            @php
                                                $jCounts = $jRow['counts'];
                                            @endphp
                                            <div class="tahunan-mini-card">
                                                <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                                                    <div>
                                                        <span class="badge badge-dark px-2 py-0.5 text-[11px]" style="border-radius: 6px;">
                                                            Jenjang {{ $jRow['jenjang'] }}
                                                        </span>
                                                        <div class="text-[11px] text-slate-500 mt-1">
                                                            {{ $jRow['kelas_count'] }} kelas &bull; {{ $jRow['siswa_count'] }} siswa
                                                        </div>
                                                    </div>
                                                    <div class="text-right">
                                                        <div class="text-base font-extrabold text-indigo-700 leading-tight">{{ $jCounts['persentase_hadir'] }}%</div>
                                                        <div class="text-[10px] font-bold text-amber-700">{{ $jCounts['persentase_terlambat'] }}% telat</div>
                                                    </div>
                                                </div>
                                                <div class="w-full h-1.5 rounded-full bg-slate-200 overflow-hidden">
                                                    <div style="width: {{ min(100, (float) $jCounts['persentase_hadir']) }}%"
                                                        class="h-full bg-indigo-500 rounded-full"></div>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="px-4 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <div>
                                <h5 class="mb-0 font-weight-bold text-slate-800 text-base">
                                    <i class="fa fa-award text-orange-600 mr-1.5"></i>
                                    Ringkasan Performa &amp; Predikat
                                </h5>
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Evaluasi rasio kehadiran efektif pada cakupan filter aktif.
                                </div>
                            </div>
                        </div>

                        <div class="p-3 p-sm-4 d-flex flex-column justify-content-between gap-3 flex-1">
                            <div class="d-flex align-items-center justify-content-between p-3 rounded-xl bg-slate-50 border border-slate-200">
                                <div>
                                    <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Predikat Periode</div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 mt-1 rounded-full text-xs font-bold border {{ $summaryPredikat['class'] }}">
                                        {{ $summaryPredikat['label'] }}
                                    </span>
                                </div>
                                <div class="text-right">
                                    <div class="text-2xl font-extrabold text-slate-800 leading-none">{{ $summary['persentase_hadir'] }}%</div>
                                    <div class="text-[11px] font-semibold text-slate-500 mt-1">Hadir Efektif</div>
                                </div>
                            </div>

                            <div class="d-flex flex-column gap-2.5">
                                <div>
                                    <div class="d-flex justify-content-between text-xs mb-1">
                                        <span class="font-bold text-slate-700">Masuk Tepat Waktu (&le; {{ $batasJamMasuk }})</span>
                                        <span class="font-extrabold text-emerald-700">{{ $summary['hadir'] }} ({{ $pctHadirBar }}%)</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-emerald-500 rounded-full" style="width: {{ min(100, $pctHadirBar) }}%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between text-xs mb-1">
                                        <span class="font-bold text-slate-700">Terlambat (&gt; {{ $batasJamMasuk }})</span>
                                        <span class="font-extrabold text-amber-700">{{ $summary['terlambat'] }} ({{ $pctTelatBar }}%)</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-amber-500 rounded-full" style="width: {{ min(100, $pctTelatBar) }}%"></div>
                                    </div>
                                </div>

                                <div>
                                    <div class="d-flex justify-content-between text-xs mb-1">
                                        <span class="font-bold text-slate-700">Ketidakhadiran (Izin / Sakit / Alpa)</span>
                                        <span class="font-extrabold text-rose-700">{{ $totalTidakHadir }} ({{ $pctTidakHadirBar }}%)</span>
                                    </div>
                                    <div class="w-full h-2 rounded-full bg-slate-100 overflow-hidden">
                                        <div class="h-full bg-rose-500 rounded-full" style="width: {{ min(100, $pctTidakHadirBar) }}%"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap gap-2 pt-1 no-print">
                                <a href="{{ route('presensi-siswa.rekap', array_filter([
                                    'tahun_ajaran_id' => $drillTahunAjaranId,
                                    'kelas_id' => $filters['kelas_id'],
                                ])) }}"
                                    class="btn btn-sm btn-light border shadow-sm flex-1 font-weight-bold" style="border-radius: 8px;">
                                    <i class="fa fa-chart-bar mr-1"></i> Rekap Bulanan
                                </a>
                                <a href="{{ route('presensi-siswa.bulk', array_filter([
                                    'kelas_id' => $filters['kelas_id'],
                                ])) }}"
                                    class="btn btn-sm text-white shadow-sm flex-1 font-weight-bold"
                                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                                    <i class="fa fa-layer-group mr-1"></i> Input Massal
                                </a>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <!-- EXPLORER DATA TABBED: REKAP PER KELAS/SISWA & RINCIAN BULANAN -->
            <div class="main-card card shadow-sm border-0 mb-4" style="border-radius: 16px; overflow: hidden;">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                    <div class="tahunan-tab-group" role="tablist" aria-label="Tampilan Rincian Data Tahunan">
                        <button type="button"
                            role="tab"
                            aria-selected="true"
                            aria-controls="tahunanPanelRekap"
                            data-tahunan-tab="rekap"
                            class="tahunan-tab-btn is-active">
                            <i class="fa fa-table"></i>
                            <span>{{ $rekapTabLabel }}</span>
                            <span class="tahunan-tab-count">{{ $rekapTabCount }}</span>
                        </button>
                        <button type="button"
                            role="tab"
                            aria-selected="false"
                            aria-controls="tahunanPanelBulanan"
                            data-tahunan-tab="bulanan"
                            class="tahunan-tab-btn">
                            <i class="fa fa-calendar-alt"></i>
                            <span>Rincian Bulanan</span>
                            <span class="tahunan-tab-count">{{ count($monthlyRows) }}</span>
                        </button>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <input type="search" id="tahunanSearchInput" class="form-control form-control-sm"
                            style="min-width: 220px; border-radius: 8px;"
                            data-placeholder-rekap="{{ $mode === 'class_detail' ? 'Cari nama atau NIS siswa...' : 'Cari nama kelas atau jenjang...' }}"
                            data-placeholder-bulanan="Cari nama bulan (mis. November)..."
                            placeholder="{{ $mode === 'class_detail' ? 'Cari nama atau NIS siswa...' : 'Cari nama kelas atau jenjang...' }}">
                        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary"
                            style="border-radius: 8px;">
                            <i class="fa fa-print mr-1"></i> Cetak
                        </button>
                    </div>
                </div>

                <!-- TAB 1 PANEL: REKAP PER SISWA ATAU PER KELAS -->
                <div id="tahunanPanelRekap" class="tahunan-tab-panel is-active" data-tahunan-panel="rekap" role="tabpanel">
                    <div class="px-4 py-2 bg-slate-50/70 border-bottom text-xs text-slate-500 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span>
                            <strong class="text-slate-700">{{ $mode === 'class_detail' ? 'Rekap Tahunan Per Siswa' : 'Rekap Tahunan Per Kelas' }}</strong>
                            &mdash; Akumulasi catatan kehadiran &amp; keterlambatan periode {{ $period['label'] }}.
                        </span>
                    </div>

                    <div class="card-body p-0">
                        @if ($mode === 'class_detail')
                            @if (count($studentRows) === 0)
                                <div class="text-center py-10 text-slate-500 text-sm">
                                    Belum ada anggota siswa terdaftar pada kelas ini.
                                </div>
                            @else
                                <div class="table-responsive tahunan-table-scroll">
                                    <table class="table table-hover align-middle mb-0" id="tahunanStudentTable">
                                        <thead class="bg-light text-slate-600">
                                            <tr>
                                                <th class="text-center" style="width: 52px;">No</th>
                                                <th style="min-width: 220px;">Siswa</th>
                                                <th class="text-center text-emerald-700">H</th>
                                                <th class="text-center text-amber-700">T</th>
                                                <th class="text-center text-sky-700">I</th>
                                                <th class="text-center text-indigo-700">S</th>
                                                <th class="text-center text-rose-700">A</th>
                                                <th class="text-center">Total</th>
                                                <th class="text-center">Telat</th>
                                                <th style="min-width: 155px;">% Kehadiran</th>
                                                <th class="text-center">% Telat</th>
                                                <th class="text-center">Predikat</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($studentRows as $index => $row)
                                                @php
                                                    $anggota = $row['anggota'];
                                                    $siswa = $anggota->siswa;
                                                    $counts = $row['counts'];
                                                    $pct = (float) $counts['persentase_hadir'];
                                                    $barColor = $pct >= 95 ? 'bg-emerald-500' : ($pct >= 85 ? 'bg-sky-500' : ($pct >= 75 ? 'bg-amber-500' : 'bg-rose-500'));
                                                @endphp
                                                <tr class="js-tahunan-search-row"
                                                    data-search="{{ mb_strtolower(trim(($siswa?->nama_lengkap ?? '').' '.$anggota->siswa_nis)) }}">
                                                    <td class="text-center font-weight-bold text-slate-500">{{ $index + 1 }}</td>
                                                    <td>
                                                        <div class="font-weight-bold text-slate-800">{{ $siswa?->nama_lengkap ?? '-' }}</div>
                                                        <div class="text-xs text-slate-500">NIS: {{ $anggota->siswa_nis }}</div>
                                                    </td>
                                                    <td class="text-center font-weight-bold text-emerald-700">{{ $counts['hadir'] }}</td>
                                                    <td class="text-center font-weight-bold text-amber-700">{{ $counts['terlambat'] }}</td>
                                                    <td class="text-center font-weight-bold text-sky-700">{{ $counts['izin'] }}</td>
                                                    <td class="text-center font-weight-bold text-indigo-700">{{ $counts['sakit'] }}</td>
                                                    <td class="text-center font-weight-bold text-rose-700">{{ $counts['alpa'] }}</td>
                                                    <td class="text-center font-weight-bold text-slate-700">{{ $counts['total_catatan'] }}</td>
                                                    <td class="text-center text-xs text-slate-600">
                                                        {{ $counts['total_telat_menit'] > 0 ? $counts['total_telat_menit'].' mnt' : '-' }}
                                                    </td>
                                                    <td>
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                                                <div class="h-full {{ $barColor }}" style="width: {{ min(100, $pct) }}%"></div>
                                                            </div>
                                                            <span class="text-xs font-extrabold text-slate-800 w-12 text-right">{{ $counts['persentase_hadir'] }}%</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center text-xs font-extrabold text-amber-700">
                                                        {{ $counts['persentase_terlambat'] }}%
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $counts['predikat']['class'] }}">
                                                            {{ $counts['predikat']['label'] }}
                                                        </span>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @else
                            @if (count($classRows) === 0)
                                <div class="text-center py-10 text-slate-500 text-sm">
                                    Belum ada kelas yang sesuai dengan filter tahunan ini.
                                </div>
                            @else
                                <div class="table-responsive tahunan-table-scroll">
                                    <table class="table table-hover align-middle mb-0" id="tahunanClassTable">
                                        <thead class="bg-light text-slate-600">
                                            <tr>
                                                <th class="text-center" style="width: 52px;">No</th>
                                                <th style="min-width: 210px;">Kelas</th>
                                                <th class="text-center">Siswa</th>
                                                <th class="text-center text-emerald-700">H</th>
                                                <th class="text-center text-amber-700">T</th>
                                                <th class="text-center text-sky-700">I</th>
                                                <th class="text-center text-indigo-700">S</th>
                                                <th class="text-center text-rose-700">A</th>
                                                <th class="text-center">Total</th>
                                                <th style="min-width: 155px;">% Kehadiran</th>
                                                <th class="text-center">% Telat</th>
                                                <th class="text-center">Predikat</th>
                                                <th class="text-center no-print">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach ($classRows as $index => $row)
                                                @php
                                                    $kelas = $row['kelas'];
                                                    $counts = $row['counts'];
                                                    $pct = (float) $counts['persentase_hadir'];
                                                    $barColor = $pct >= 95 ? 'bg-emerald-500' : ($pct >= 85 ? 'bg-sky-500' : ($pct >= 75 ? 'bg-amber-500' : 'bg-rose-500'));
                                                @endphp
                                                <tr class="js-tahunan-search-row"
                                                    data-search="{{ mb_strtolower(trim($kelas->jenjang_kelas.' '.$kelas->nama_kelas.' '.($kelas->tahunAjaran?->full_label ?? '').' '.($kelas->waliKelas?->nama_gelar ?? ''))) }}">
                                                    <td class="text-center font-weight-bold text-slate-500">{{ $index + 1 }}</td>
                                                    <td>
                                                        <div class="font-weight-bold text-slate-800">
                                                            {{ $kelas->jenjang_kelas }} - {{ $kelas->nama_kelas }}
                                                        </div>
                                                        <div class="text-xs text-slate-500">
                                                            {{ $kelas->tahunAjaran?->full_label ?? '-' }}
                                                            @if ($kelas->waliKelas)
                                                                &bull; {{ $kelas->waliKelas->nama_gelar }}
                                                            @endif
                                                        </div>
                                                    </td>
                                                    <td class="text-center font-weight-bold text-slate-700">{{ $row['siswa_count'] }}</td>
                                                    <td class="text-center font-weight-bold text-emerald-700">{{ $counts['hadir'] }}</td>
                                                    <td class="text-center font-weight-bold text-amber-700">{{ $counts['terlambat'] }}</td>
                                                    <td class="text-center font-weight-bold text-sky-700">{{ $counts['izin'] }}</td>
                                                    <td class="text-center font-weight-bold text-indigo-700">{{ $counts['sakit'] }}</td>
                                                    <td class="text-center font-weight-bold text-rose-700">{{ $counts['alpa'] }}</td>
                                                    <td class="text-center font-weight-bold text-slate-700">{{ $counts['total_catatan'] }}</td>
                                                    <td>
                                                        <div class="flex items-center gap-2">
                                                            <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                                                <div class="h-full {{ $barColor }}" style="width: {{ min(100, $pct) }}%"></div>
                                                            </div>
                                                            <span class="text-xs font-extrabold text-slate-800 w-12 text-right">{{ $counts['persentase_hadir'] }}%</span>
                                                        </div>
                                                    </td>
                                                    <td class="text-center text-xs font-extrabold text-amber-700">
                                                        {{ $counts['persentase_terlambat'] }}%
                                                    </td>
                                                    <td class="text-center">
                                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $counts['predikat']['class'] }}">
                                                            {{ $counts['predikat']['label'] }}
                                                        </span>
                                                    </td>
                                                    <td class="text-center no-print">
                                                        <a href="{{ route('presensi-siswa.tahunan', array_filter([
                                                            'nama_tahun_ajaran' => $kelas->tahunAjaran?->nama_tahun_ajaran ?? $filters['nama_tahun_ajaran'],
                                                            'semester' => $kelas->tahunAjaran?->semester ?? ($selectedSemesterFilter !== '' ? $selectedSemesterFilter : null),
                                                            'jenjang' => $filters['jenjang'],
                                                            'kelas_id' => $kelas->id,
                                                        ], fn ($v): bool => $v !== null && $v !== '')) }}"
                                                            class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">
                                                            Siswa
                                                        </a>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- TAB 2 PANEL: RINCIAN STATISTIK BULANAN -->
                <div id="tahunanPanelBulanan" class="tahunan-tab-panel" data-tahunan-panel="bulanan" role="tabpanel">
                    <div class="px-4 py-2 bg-slate-50/70 border-bottom text-xs text-slate-500 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <span>
                            <strong class="text-slate-700">Rincian Statistik Bulanan</strong>
                            &mdash; Klik tombol <strong>Detail</strong> untuk melihat rekap lengkap siswa/kelas pada bulan tersebut.
                        </span>
                    </div>

                    <div class="card-body p-0">
                        @if (count($monthlyRows) === 0)
                            <div class="text-center py-8 text-slate-500 text-sm">
                                Belum ada bulan dengan catatan presensi pada periode ini.
                            </div>
                        @else
                            <div class="table-responsive tahunan-table-scroll">
                                <table class="table table-hover align-middle mb-0" id="tahunanMonthlyTable">
                                    <thead class="bg-light text-slate-600">
                                        <tr>
                                            <th style="min-width: 150px;">Bulan</th>
                                            <th class="text-center">Hari Kerja</th>
                                            <th class="text-center text-emerald-700">H</th>
                                            <th class="text-center text-amber-700">T</th>
                                            <th class="text-center text-sky-700">I</th>
                                            <th class="text-center text-indigo-700">S</th>
                                            <th class="text-center text-rose-700">A</th>
                                            <th class="text-center">Total</th>
                                            <th class="text-center">Telat (Mnt)</th>
                                            <th style="min-width: 150px;">% Kehadiran</th>
                                            <th class="text-center">% Telat</th>
                                            <th class="text-center">Predikat</th>
                                            <th class="text-center no-print">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($monthlyRows as $mRow)
                                            @php
                                                $mc = $mRow['counts'];
                                                $mPct = (float) $mc['persentase_hadir'];
                                                $mBarColor = $mPct >= 95 ? 'bg-emerald-500' : ($mPct >= 85 ? 'bg-sky-500' : ($mPct >= 75 ? 'bg-amber-500' : 'bg-rose-500'));
                                            @endphp
                                            <tr class="js-tahunan-month-row"
                                                data-month-search="{{ mb_strtolower(trim($mRow['label'].' '.$mRow['short_label'].' '.$mRow['bulan'])) }}">
                                                <td class="font-weight-bold text-slate-800">{{ $mRow['label'] }}</td>
                                                <td class="text-center text-xs text-slate-500">{{ $mRow['weekdays_count'] }}</td>
                                                <td class="text-center font-weight-bold text-emerald-700">{{ $mc['hadir'] }}</td>
                                                <td class="text-center font-weight-bold text-amber-700">{{ $mc['terlambat'] }}</td>
                                                <td class="text-center font-weight-bold text-sky-700">{{ $mc['izin'] }}</td>
                                                <td class="text-center font-weight-bold text-indigo-700">{{ $mc['sakit'] }}</td>
                                                <td class="text-center font-weight-bold text-rose-700">{{ $mc['alpa'] }}</td>
                                                <td class="text-center font-weight-bold text-slate-700">{{ $mc['total_catatan'] }}</td>
                                                <td class="text-center text-xs text-slate-600">
                                                    {{ $mc['total_telat_menit'] > 0 ? $mc['total_telat_menit'].' mnt' : '-' }}
                                                </td>
                                                <td>
                                                    <div class="flex items-center gap-2">
                                                        <div class="flex-1 h-2 rounded-full bg-slate-100 overflow-hidden">
                                                            <div class="h-full {{ $mBarColor }}" style="width: {{ min(100, $mPct) }}%"></div>
                                                        </div>
                                                        <span class="text-xs font-extrabold text-slate-800 w-12 text-right">{{ $mc['persentase_hadir'] }}%</span>
                                                    </div>
                                                </td>
                                                <td class="text-center text-xs font-extrabold text-amber-700">
                                                    {{ $mc['persentase_terlambat'] }}%
                                                </td>
                                                <td class="text-center">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-bold border {{ $mc['predikat']['class'] }}">
                                                        {{ $mc['predikat']['label'] }}
                                                    </span>
                                                </td>
                                                <td class="text-center no-print">
                                                    @php
                                                        $mMonthNum = (int) substr((string) $mRow['bulan'], 5, 2);
                                                        $mPreferredSemesters = $mMonthNum >= 7 ? ['1', 'mid 1'] : ['2', 'mid 2'];
                                                        $mTargetTaId = $selectedKelas?->akademik_master_tahun_ajaran_id
                                                            ?? ($selectedSemesterFilter !== ''
                                                                ? $drillTahunAjaranId
                                                                : ($tahunAjaranOptions->first(function ($ta) use ($selectedNamaTa, $mPreferredSemesters): bool {
                                                                    if ($selectedNamaTa !== '' && trim((string) $ta->nama_tahun_ajaran) !== $selectedNamaTa) {
                                                                        return false;
                                                                    }

                                                                    return in_array(strtolower(trim((string) $ta->semester)), $mPreferredSemesters, true);
                                                                })?->id ?? $drillTahunAjaranId));
                                                    @endphp
                                                    <a href="{{ route('presensi-siswa.rekap', array_filter([
                                                        'tahun_ajaran_id' => $mTargetTaId,
                                                        'kelas_id' => $filters['kelas_id'],
                                                        'bulan' => $mRow['bulan'],
                                                    ])) }}"
                                                        class="btn btn-sm btn-outline-primary" style="border-radius: 8px;">
                                                        Detail
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
    </div>
@endsection

@push('js')
    <script>
        (function () {
            const namaTaSelect = document.getElementById('tahunan_nama_tahun_ajaran');
            const semesterSelect = document.getElementById('tahunan_semester');
            const jenjangSelect = document.getElementById('tahunan_jenjang');
            const kelasSelect = document.getElementById('tahunan_kelas_id');

            function filterKelasOptions() {
                if (!kelasSelect) {
                    return;
                }

                const namaTa = namaTaSelect ? namaTaSelect.value : '';
                const semester = semesterSelect ? semesterSelect.value : '';
                const jenjang = jenjangSelect ? jenjangSelect.value : '';

                Array.from(kelasSelect.options).forEach(function (option) {
                    if (!option.value) {
                        return;
                    }

                    const matchesTa = !namaTa || option.dataset.namaTahunAjaran === namaTa;
                    const matchesSemester = !semester || option.dataset.semester === semester;
                    const matchesJenjang = !jenjang || option.dataset.jenjang === jenjang;
                    const visible = matchesTa && matchesSemester && matchesJenjang;

                    option.hidden = !visible;
                    option.disabled = !visible;
                });

                const currentOpt = kelasSelect.options[kelasSelect.selectedIndex];
                if (currentOpt && currentOpt.value && currentOpt.hidden) {
                    kelasSelect.value = '';
                }
            }

            if (namaTaSelect) {
                namaTaSelect.addEventListener('change', filterKelasOptions);
            }
            if (semesterSelect) {
                semesterSelect.addEventListener('change', filterKelasOptions);
            }
            if (jenjangSelect) {
                jenjangSelect.addEventListener('change', filterKelasOptions);
            }
            filterKelasOptions();

            const tabButtons = Array.from(document.querySelectorAll('[data-tahunan-tab]'));
            const tabPanels = Array.from(document.querySelectorAll('[data-tahunan-panel]'));
            const searchInput = document.getElementById('tahunanSearchInput');
            const rekapRows = Array.from(document.querySelectorAll('.js-tahunan-search-row'));
            const monthRows = Array.from(document.querySelectorAll('.js-tahunan-month-row'));
            let activeTab = 'rekap';

            function applySearchFilter() {
                if (!searchInput) {
                    return;
                }

                const q = searchInput.value.trim().toLowerCase();
                if (activeTab === 'bulanan') {
                    monthRows.forEach(function (row) {
                        const haystack = row.dataset.monthSearch || '';
                        row.style.display = q === '' || haystack.includes(q) ? '' : 'none';
                    });
                } else {
                    rekapRows.forEach(function (row) {
                        const haystack = row.dataset.search || '';
                        row.style.display = q === '' || haystack.includes(q) ? '' : 'none';
                    });
                }
            }

            if (tabButtons.length > 0 && tabPanels.length > 0) {
                tabButtons.forEach(function (button) {
                    button.addEventListener('click', function () {
                        const targetTab = button.dataset.tahunanTab || 'rekap';
                        activeTab = targetTab;

                        tabButtons.forEach(function (btn) {
                            const isCurrent = (btn.dataset.tahunanTab || '') === targetTab;
                            btn.classList.toggle('is-active', isCurrent);
                            btn.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
                        });

                        tabPanels.forEach(function (panel) {
                            const isCurrent = (panel.dataset.tahunanPanel || '') === targetTab;
                            panel.classList.toggle('is-active', isCurrent);
                        });

                        if (searchInput) {
                            searchInput.placeholder = targetTab === 'bulanan'
                                ? (searchInput.dataset.placeholderBulanan || searchInput.placeholder)
                                : (searchInput.dataset.placeholderRekap || searchInput.placeholder);
                        }

                        applySearchFilter();
                    });
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', applySearchFilter);
            }
        })();
    </script>
@endpush

