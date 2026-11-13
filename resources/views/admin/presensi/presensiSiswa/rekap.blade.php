@extends('layouts.appui')

@section('title')
    <title>Rekap &amp; Persentase Presensi Siswa - SIPIT Akademik</title>
@endsection

@push('css')
    <style>
        .rekap-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
            border-radius: 18px;
            padding: 22px 26px;
            color: #ffffff;
            box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.3);
        }

        .rekap-kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 14px 16px;
            box-shadow: 0 2px 10px -4px rgba(15, 23, 42, 0.06);
        }

        .rekap-progress-track {
            width: 100%;
            height: 8px;
            border-radius: 999px;
            background: #e2e8f0;
            overflow: hidden;
        }

        .rekap-progress-fill {
            height: 100%;
            border-radius: 999px;
            transition: width 0.25s ease;
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
        }
    </style>
@endpush

@section('content')
    @php
        $totalCatatan = (int) ($summary['total_catatan'] ?? 0);
        $pctHadirBar = $totalCatatan > 0 ? round(($summary['hadir'] / $totalCatatan) * 100, 1) : 0;
        $pctTelatBar = $totalCatatan > 0 ? round(($summary['terlambat'] / $totalCatatan) * 100, 1) : 0;
        $pctIzinBar = $totalCatatan > 0 ? round(($summary['izin'] / $totalCatatan) * 100, 1) : 0;
        $pctSakitBar = $totalCatatan > 0 ? round(($summary['sakit'] / $totalCatatan) * 100, 1) : 0;
        $pctAlpaBar = $totalCatatan > 0 ? round(($summary['alpa'] / $totalCatatan) * 100, 1) : 0;
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title no-print">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-graph2" style="color: white;"></i>
                    </div>
                    <div>
                        Rekap &amp; Persentase Presensi Siswa
                        <div class="page-title-subheading">
                            Ringkasan total kehadiran, keterlambatan, dan persentase per siswa maupun antar-kelas.
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

        <!-- JUDUL & INFORMASI PERIODE REKAP -->
        <div class="rekap-hero mb-4">
            <div class="flex flex-wrap items-center gap-2 mb-2">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-orange-500/20 text-orange-300 border border-orange-400/30">
                    <i class="fa fa-calendar-alt mr-1.5"></i> {{ $period['label'] }}
                </span>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-white/10 text-slate-200 border border-white/15">
                    {{ $filters['include_weekend'] ? 'Semua Hari (Sen–Min)' : 'Hari Sekolah (Sen–Jum)' }}
                </span>
                @if ($selectedKelas && $selectedKelas->tahunAjaran)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-400/30">
                        {{ $selectedKelas->tahunAjaran->nama_tahun_ajaran }} ({{ $selectedKelas->tahunAjaran->semester }})
                    </span>
                @endif
            </div>

            <h2 class="text-xl sm:text-2xl font-extrabold text-white tracking-tight">
                @if ($mode === 'class_detail' && $selectedKelas)
                    Rekap Kehadiran — {{ $selectedKelas->jenjang_kelas }} {{ $selectedKelas->nama_kelas }}
                @elseif ($mode === 'class_detail')
                    Rekap Kehadiran Kelas
                @else
                    Rekap Kehadiran Seluruh Kelas
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
                    Hari Efektif: <strong class="text-white">{{ $period['weekdays_count'] }} hari Sen–Jum</strong>
                </span>
                <span>
                    <i class="fa fa-clock text-rose-300 mr-1"></i>
                    Akumulasi Telat: <strong class="text-white">{{ $summary['total_telat_menit'] }} menit</strong>
                </span>
            </p>
        </div>

        <!-- KARTU STATUS & PERSENTASE (TERPISAH DARI JUDUL) -->
        @php
            $batasJamMasuk = \App\Models\Admin\Presensi\PresensiSiswa::BATAS_JAM_MASUK;
            $rekapPctTerlambat = $summary['persentase_terlambat'] ?? $pctTelatBar;
        @endphp

        <!-- KARTU KPI REKAP & PERSENTASE (TERPISAH DARI JUDUL) -->
        @include('admin.presensi.presensiSiswa._status_stats', [
            'statusSummary' => $summary,
            'totalCatatan' => $totalCatatan,
            'statusMeta' => [
                'hadir' => ['subtext' => ($summary['persentase_tepat_waktu'] ?? 0).'% tepat waktu'],
                'terlambat' => ['subtext' => $summary['total_telat_menit'].' mnt akumulasi'],
                'izin' => ['subtext' => $pctIzinBar.'% catatan'],
                'sakit' => ['subtext' => $pctSakitBar.'% catatan'],
                'alpa' => ['subtext' => ($summary['persentase_alpa'] ?? 0).'% catatan'],
            ],
            'rateCards' => [
                [
                    'label' => '% Kehadiran',
                    'value' => $summary['persentase_hadir'],
                    'percent' => $summary['persentase_hadir'],
                    'badge' => 'Hadir Efektif',
                    'subtitle' => ($summary['hadir_efektif'] ?? ($summary['hadir'] + $summary['terlambat'])).'/'.$totalCatatan.' hadir efektif',
                    'icon' => 'fa fa-chart-line',
                    'tone' => 'indigo',
                ],
                [
                    'label' => '% Keterlambatan',
                    'value' => $rekapPctTerlambat,
                    'percent' => $rekapPctTerlambat,
                    'badge' => 'Batas '.$batasJamMasuk,
                    'subtitle' => $summary['terlambat'].'/'.$totalCatatan.' terlambat',
                    'icon' => 'fa fa-stopwatch',
                    'tone' => 'amber',
                ],
            ],
        ])

        @if ($totalCatatan > 0)
            <div class="rekap-kpi-card mb-4">
                <div class="flex flex-wrap items-center justify-between text-xs text-slate-600 mb-2 gap-2">
                    <span class="font-bold text-slate-800">Distribusi Status ({{ $totalCatatan }} catatan):</span>
                    <span class="flex flex-wrap gap-3">
                        <span>Tepat Waktu: <strong class="text-emerald-700">{{ $pctHadirBar }}%</strong></span>
                        <span>Keterlambatan: <strong class="text-amber-700">{{ $pctTelatBar }}%</strong></span>
                        <span>Izin: <strong class="text-sky-700">{{ $pctIzinBar }}%</strong></span>
                        <span>Sakit: <strong class="text-indigo-700">{{ $pctSakitBar }}%</strong></span>
                        <span>Alpa: <strong class="text-rose-700">{{ $pctAlpaBar }}%</strong></span>
                    </span>
                </div>
                <div class="w-full h-2.5 rounded-full bg-slate-100 overflow-hidden flex">
                    <div style="width: {{ $pctHadirBar }}%" class="bg-emerald-500"></div>
                    <div style="width: {{ $pctTelatBar }}%" class="bg-amber-500"></div>
                    <div style="width: {{ $pctIzinBar }}%" class="bg-sky-500"></div>
                    <div style="width: {{ $pctSakitBar }}%" class="bg-indigo-500"></div>
                    <div style="width: {{ $pctAlpaBar }}%" class="bg-rose-500"></div>
                </div>
            </div>
        @endif

        <!-- PANEL FILTER REKAP -->
        <div class="filter-card no-print">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-chart-pie"></i></span>
                    <span>Filter Rekap &amp; Persentase</span>
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
                        <a href="{{ route('presensi-siswa.rekap') }}" class="filter-btn filter-btn-secondary"
                            style="height: 1.9rem; min-height: 1.9rem; padding: 0 0.7rem; font-size: 0.75rem;">
                            <i class="fa fa-undo"></i> Reset
                        </a>
                    @endif
                </div>
            </div>

            <div class="filter-card-body">
                <form method="GET" action="{{ route('presensi-siswa.rekap') }}"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <div class="lg:col-span-4">
                        <label for="rekap_tahun_ajaran_id" class="filter-label">Tahun Ajaran</label>
                        <select id="rekap_tahun_ajaran_id" name="tahun_ajaran_id" class="filter-control">
                            <option value="">Semua Tahun Ajaran</option>
                            @foreach ($tahunAjaranOptions as $tahunAjaran)
                                <option value="{{ $tahunAjaran->id }}" @selected($filters['tahun_ajaran_id'] === $tahunAjaran->id)>
                                    {{ $tahunAjaran->nama_tahun_ajaran }} ({{ $tahunAjaran->semester }})@if ($tahunAjaran->is_active) (Aktif)@endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-4">
                        <label for="rekap_kelas_id" class="filter-label">Kelas (Opsional)</label>
                        <select id="rekap_kelas_id" name="kelas_id" class="filter-control">
                            <option value="">Semua Kelas (Rekap Per Kelas)</option>
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
                        <label for="rekap_bulan" class="filter-label">Bulan</label>
                        <input id="rekap_bulan" type="month" name="bulan" value="{{ $filters['bulan'] }}"
                            class="filter-control">
                    </div>

                    <div class="lg:col-span-2">
                        <button type="submit" class="filter-btn filter-btn-primary w-100 justify-content-center">
                            <i class="fa fa-chart-bar"></i> Tampilkan
                        </button>
                    </div>
                </form>
            </div>
        </div>

            <!-- TABEL REKAP -->
            <div class="main-card card shadow-sm border-0 mb-4" style="border-radius: 16px;">
                <div class="card-header bg-white border-bottom py-3 px-4 d-flex flex-wrap align-items-center justify-content-between gap-2 no-print">
                    <div>
                        <span class="font-weight-bold text-slate-800">
                            {{ $mode === 'class_detail' ? 'Rincian Rekap & Persentase Per Siswa' : 'Rincian Rekap & Persentase Per Kelas' }}
                        </span>
                        <div class="text-xs text-slate-500">
                            Persentase Kehadiran dihitung dari (Hadir + Terlambat) dibagi total catatan presensi pada periode terpilih.
                        </div>
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <input type="search" id="rekapSearchInput" class="form-control form-control-sm"
                            style="min-width: 220px;"
                            placeholder="{{ $mode === 'class_detail' ? 'Cari nama atau NIS siswa...' : 'Cari nama kelas atau jenjang...' }}">
                        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary"
                            style="border-radius: 8px;">
                            <i class="fa fa-print mr-1"></i> Cetak
                        </button>
                    </div>
                </div>

                <div class="card-body p-0">
                    @if ($mode === 'class_detail')
                        @if (count($studentRows) === 0)
                            <div class="text-center py-12 text-slate-500">
                                <i class="fa fa-user-graduate text-3xl text-slate-300 mb-3"></i>
                                <p class="font-bold text-slate-700 mb-1">Belum ada anggota siswa pada kelas ini</p>
                                <p class="text-xs">Pilih kelas lain atau tambahkan anggota kelas terlebih dahulu.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="rekapStudentTable">
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
                                            <th style="min-width: 155px;">% Keterlambatan</th>
                                            <th class="text-center" style="width: 140px;">Predikat</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($studentRows as $index => $row)
                                            @php
                                                $anggota = $row['anggota'];
                                                $siswa = $anggota->siswa;
                                                $counts = $row['counts'];
                                                $pct = (float) $counts['persentase_hadir'];
                                                $pctTelat = (float) ($counts['persentase_terlambat'] ?? 0);
                                                $barColor = $counts['total_catatan'] === 0
                                                    ? 'bg-slate-300'
                                                    : ($pct >= 95 ? 'bg-emerald-500' : ($pct >= 85 ? 'bg-sky-500' : ($pct >= 75 ? 'bg-amber-500' : 'bg-rose-500')));
                                            @endphp
                                            <tr class="js-rekap-row"
                                                data-search="{{ mb_strtolower(trim(($siswa?->nama_lengkap ?? '') . ' ' . $anggota->siswa_nis)) }}">
                                                <td class="text-center font-weight-bold text-slate-500">{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="font-weight-bold text-slate-800">{{ $siswa?->nama_lengkap ?? '-' }}</div>
                                                    <div class="text-xs text-slate-500">
                                                        NIS: {{ $anggota->siswa_nis }}
                                                        @if ($siswa?->jenis_kelamin)
                                                            &bull; {{ $siswa->jenis_kelamin }}
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-center font-weight-bold text-emerald-700">{{ $counts['hadir'] }}</td>
                                                <td class="text-center font-weight-bold text-amber-700">{{ $counts['terlambat'] }}</td>
                                                <td class="text-center font-weight-bold text-sky-700">{{ $counts['izin'] }}</td>
                                                <td class="text-center font-weight-bold text-indigo-700">{{ $counts['sakit'] }}</td>
                                                <td class="text-center font-weight-bold text-rose-700">{{ $counts['alpa'] }}</td>
                                                <td class="text-center font-semibold text-slate-700">{{ $counts['total_catatan'] }}</td>
                                                <td class="text-center text-slate-600 whitespace-nowrap">
                                                    {{ $counts['total_telat_menit'] > 0 ? $counts['total_telat_menit'].' mnt' : '-' }}
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-between text-xs font-weight-bold mb-1">
                                                        <span class="text-slate-700">{{ $pct }}%</span>
                                                        <span class="text-slate-400">{{ $counts['hadir_efektif'] }}/{{ $counts['total_catatan'] }}</span>
                                                    </div>
                                                    <div class="rekap-progress-track">
                                                        <div class="rekap-progress-fill {{ $barColor }}"
                                                            style="width: {{ min(100, max(0, $pct)) }}%;"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-between text-xs font-weight-bold mb-1">
                                                        <span class="text-amber-700">{{ $pctTelat }}%</span>
                                                        <span class="text-slate-400">{{ $counts['terlambat'] }}/{{ $counts['total_catatan'] }}</span>
                                                    </div>
                                                    <div class="rekap-progress-track">
                                                        <div class="rekap-progress-fill bg-amber-500"
                                                            style="width: {{ min(100, max(0, $pctTelat)) }}%;"></div>
                                                    </div>
                                                </td>
                                                <td class="text-center">
                                                    <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $counts['predikat']['class'] }}">
                                                        {{ $counts['predikat']['label'] }}
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-slate-50 font-weight-bold text-slate-800">
                                            <td colspan="2" class="px-4 py-3">Total Kelas ({{ count($studentRows) }} Siswa)</td>
                                            <td class="text-center text-emerald-700">{{ $summary['hadir'] }}</td>
                                            <td class="text-center text-amber-700">{{ $summary['terlambat'] }}</td>
                                            <td class="text-center text-sky-700">{{ $summary['izin'] }}</td>
                                            <td class="text-center text-indigo-700">{{ $summary['sakit'] }}</td>
                                            <td class="text-center text-rose-700">{{ $summary['alpa'] }}</td>
                                            <td class="text-center">{{ $summary['total_catatan'] }}</td>
                                            <td class="text-center">{{ $summary['total_telat_menit'] > 0 ? $summary['total_telat_menit'].' mnt' : '-' }}</td>
                                            <td><span class="text-indigo-700">{{ $summary['persentase_hadir'] }}% Hadir</span></td>
                                            <td><span class="text-amber-700">{{ $summary['persentase_terlambat'] ?? $pctTelatBar }}% Telat</span></td>
                                            <td class="text-center">
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-[11px] font-bold border {{ $summary['predikat']['class'] }}">
                                                    {{ $summary['predikat']['label'] }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    @else
                        @if (count($classRows) === 0)
                            <div class="text-center py-12 text-slate-500">
                                <i class="fa fa-school text-3xl text-slate-300 mb-3"></i>
                                <p class="font-bold text-slate-700 mb-1">Belum ada data kelas yang sesuai filter</p>
                                <p class="text-xs">Silakan ubah Tahun Ajaran atau tambahkan kelas terlebih dahulu.</p>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0" id="rekapClassTable">
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
                                            <th style="min-width: 155px;">% Keterlambatan</th>
                                            <th class="text-center no-print" style="width: 170px;">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($classRows as $index => $row)
                                            @php
                                                $kelas = $row['kelas'];
                                                $counts = $row['counts'];
                                                $pct = (float) $counts['persentase_hadir'];
                                                $pctTelat = (float) ($counts['persentase_terlambat'] ?? 0);
                                                $barColor = $counts['total_catatan'] === 0
                                                    ? 'bg-slate-300'
                                                    : ($pct >= 95 ? 'bg-emerald-500' : ($pct >= 85 ? 'bg-sky-500' : ($pct >= 75 ? 'bg-amber-500' : 'bg-rose-500')));
                                            @endphp
                                            <tr class="js-rekap-row"
                                                data-search="{{ mb_strtolower(trim($kelas->jenjang_kelas.' '.$kelas->nama_kelas.' '.($kelas->waliKelas?->nama_lengkap ?? ''))) }}">
                                                <td class="text-center font-weight-bold text-slate-500">{{ $index + 1 }}</td>
                                                <td>
                                                    <div class="font-weight-bold text-slate-800">
                                                        {{ $kelas->jenjang_kelas }} - {{ $kelas->nama_kelas }}
                                                    </div>
                                                    <div class="text-xs text-slate-500">
                                                        @if ($kelas->tahunAjaran)
                                                            {{ $kelas->tahunAjaran->nama_tahun_ajaran }} ({{ $kelas->tahunAjaran->semester }})
                                                        @endif
                                                        @if ($kelas->waliKelas)
                                                            &bull; {{ $kelas->waliKelas->nama_gelar }}
                                                        @endif
                                                    </div>
                                                </td>
                                                <td class="text-center font-semibold text-slate-700">{{ $row['siswa_count'] }}</td>
                                                <td class="text-center font-weight-bold text-emerald-700">{{ $counts['hadir'] }}</td>
                                                <td class="text-center font-weight-bold text-amber-700">{{ $counts['terlambat'] }}</td>
                                                <td class="text-center font-weight-bold text-sky-700">{{ $counts['izin'] }}</td>
                                                <td class="text-center font-weight-bold text-indigo-700">{{ $counts['sakit'] }}</td>
                                                <td class="text-center font-weight-bold text-rose-700">{{ $counts['alpa'] }}</td>
                                                <td class="text-center font-semibold text-slate-700">{{ $counts['total_catatan'] }}</td>
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-between text-xs font-weight-bold mb-1">
                                                        <span class="text-slate-700">{{ $pct }}%</span>
                                                        <span class="text-slate-400">{{ $counts['hadir_efektif'] }}/{{ $counts['total_catatan'] }}</span>
                                                    </div>
                                                    <div class="rekap-progress-track">
                                                        <div class="rekap-progress-fill {{ $barColor }}"
                                                            style="width: {{ min(100, max(0, $pct)) }}%;"></div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center justify-content-between text-xs font-weight-bold mb-1">
                                                        <span class="text-amber-700">{{ $pctTelat }}%</span>
                                                        <span class="text-slate-400">{{ $counts['terlambat'] }}/{{ $counts['total_catatan'] }}</span>
                                                    </div>
                                                    <div class="rekap-progress-track">
                                                        <div class="rekap-progress-fill bg-amber-500"
                                                            style="width: {{ min(100, max(0, $pctTelat)) }}%;"></div>
                                                    </div>
                                                </td>
                                                <td class="text-center no-print">
                                                    <div class="d-inline-flex align-items-center gap-1">
                                                        <a href="{{ route('presensi-siswa.rekap', array_filter([
                                                            'tahun_ajaran_id' => $filters['tahun_ajaran_id'],
                                                            'kelas_id' => $kelas->id,
                                                            'bulan' => $filters['bulan'],
                                                            'include_weekend' => $filters['include_weekend'] ? '1' : null,
                                                        ])) }}"
                                                            class="btn btn-sm btn-primary" style="border-radius: 7px;">
                                                            <i class="fa fa-users mr-1"></i> Siswa
                                                        </a>
                                                        <a href="{{ route('presensi-siswa.index', [
                                                            'kelas_id' => $kelas->id,
                                                            'bulan' => $filters['bulan'],
                                                        ]) }}"
                                                            class="btn btn-sm btn-outline-secondary" style="border-radius: 7px;"
                                                            title="Lihat Matriks Senin-Jumat">
                                                            <i class="fa fa-th"></i> Matriks
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="bg-slate-50 font-weight-bold text-slate-800">
                                            <td colspan="2" class="px-4 py-3">Total Keseluruhan ({{ count($classRows) }} Kelas)</td>
                                            <td class="text-center">{{ $summary['siswa'] }}</td>
                                            <td class="text-center text-emerald-700">{{ $summary['hadir'] }}</td>
                                            <td class="text-center text-amber-700">{{ $summary['terlambat'] }}</td>
                                            <td class="text-center text-sky-700">{{ $summary['izin'] }}</td>
                                            <td class="text-center text-indigo-700">{{ $summary['sakit'] }}</td>
                                            <td class="text-center text-rose-700">{{ $summary['alpa'] }}</td>
                                            <td class="text-center">{{ $summary['total_catatan'] }}</td>
                                            <td><span class="text-indigo-700">{{ $summary['persentase_hadir'] }}% Hadir</span></td>
                                            <td><span class="text-amber-700">{{ $summary['persentase_terlambat'] ?? $pctTelatBar }}% Telat</span></td>
                                            <td class="no-print"></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
    </div>
@endsection

@push('js')
    <script>
        (function () {
            const tahunSelect = document.getElementById('rekap_tahun_ajaran_id');
            const kelasSelect = document.getElementById('rekap_kelas_id');

            function syncKelasOptions() {
                if (!tahunSelect || !kelasSelect) {
                    return;
                }

                const selectedTahunId = tahunSelect.value;
                let selectedVisible = true;

                Array.from(kelasSelect.options).forEach(function (option, index) {
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
                tahunSelect.addEventListener('change', syncKelasOptions);
                syncKelasOptions();
            }

            const searchInput = document.getElementById('rekapSearchInput');
            const rows = Array.from(document.querySelectorAll('.js-rekap-row'));
            if (searchInput && rows.length > 0) {
                searchInput.addEventListener('input', function () {
                    const keyword = searchInput.value.trim().toLowerCase();
                    rows.forEach(function (row) {
                        const haystack = row.getAttribute('data-search') || '';
                        row.style.display = keyword === '' || haystack.indexOf(keyword) !== -1 ? '' : 'none';
                    });
                });
            }
        })();
    </script>
@endpush

