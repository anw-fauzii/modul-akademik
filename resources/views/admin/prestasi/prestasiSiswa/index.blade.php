@extends('layouts.appui')

@section('title')
    <title>Pendataan Prestasi Siswa</title>
@endsection

@pushOnce('css')
    <style>
        .prestasi-stat-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            padding: 15px 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            height: 100%;
            text-decoration: none !important;
            color: inherit;
            transition: all 0.2s ease;
        }

        .prestasi-stat-card:hover {
            transform: translateY(-2px);
            border-color: #f97316;
            box-shadow: 0 10px 20px -6px rgba(234, 88, 12, 0.16);
        }

        .prestasi-stat-card.is-active-filter {
            border-color: #ea580c;
            background: #fff7ed;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.14);
        }

        .prestasi-prompt-card {
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            background: #ffffff;
            padding: 2.5rem 1.5rem;
            text-align: center;
        }

        .prestasi-stat-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .prestasi-stat-value {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.15;
            margin-top: 3px;
            font-variant-numeric: tabular-nums;
        }

        .prestasi-stat-sub {
            font-size: 0.72rem;
            color: #64748b;
            margin-top: 3px;
        }

        .prestasi-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .prestasi-unit-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .prestasi-unit-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none !important;
            transition: all 0.15s ease;
        }

        .prestasi-unit-pill:hover,
        .prestasi-unit-pill.is-active {
            background: #ea580c;
            border-color: #ea580c;
            color: #ffffff;
        }

        .prestasi-filter-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.85rem;
            align-items: end;
        }

        @media (min-width: 640px) {
            .prestasi-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .prestasi-filter-search {
                grid-column: span 2;
            }
        }

        @media (min-width: 992px) {
            .prestasi-filter-grid {
                grid-template-columns: minmax(185px, 1.2fr) minmax(115px, 0.7fr) minmax(135px, 0.8fr) minmax(145px, 0.85fr) minmax(195px, 1.25fr);
            }

            .prestasi-filter-search {
                grid-column: auto;
            }
        }

        .prestasi-thumb-btn {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 2px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease, border-color 0.15s ease;
        }

        .prestasi-thumb-btn:hover {
            transform: scale(1.04);
            border-color: #ea580c;
        }

        .prestasi-thumb-img {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            object-fit: cover;
            display: block;
        }

        .prestasi-rank-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #c2410c;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .prestasi-beregu-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.12rem 0.5rem;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: 0.69rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.14s ease, border-color 0.14s ease;
        }

        .prestasi-beregu-trigger:hover {
            background: #ffedd5;
            border-color: #fdba74;
        }

        .prestasi-team-note {
            margin-top: 0.3rem;
            padding: 0.25rem 0.55rem;
            border-radius: 8px;
            background: #f8fafc;
            border-left: 3px solid #f97316;
            font-size: 0.74rem;
            color: #475569;
            max-width: 360px;
        }

        .prestasi-member-modal-list {
            max-height: 300px;
            overflow-y: auto;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            padding-right: 2px;
        }

        .prestasi-member-modal-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem;
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            text-decoration: none !important;
            color: #0f172a;
            transition: border-color 0.14s ease, background-color 0.14s ease;
        }

        .prestasi-member-modal-item:hover {
            border-color: #fdba74;
            background: #fff7ed;
        }
    </style>
@endPushOnce

@section('content')
    @php
        $prestasi = $prestasi ?? collect();
        $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
        $activeTaModel = $activeTahunAjaran ?? $tahunAjaranOptions->firstWhere('is_active', true);
        $unitOptions = $unitOptions ?? \App\Models\Admin\Pelajaran\MataPelajaran::UNIT_OPTIONS;
        $kategoriOptions = $kategoriOptions ?? \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_OPTIONS;
        $tingkatOptions = $tingkatOptions ?? \App\Models\Admin\Prestasi\PrestasiSiswa::TINGKAT_PRESETS;
        $allFilterKey = \App\Services\Admin\Prestasi\PrestasiSiswaService::FILTER_ALL;
        $filters = $filters ?? [
            'tahun_ajaran_id' => $allFilterKey,
            'unit' => $allFilterKey,
            'kategori' => $allFilterKey,
            'tingkat' => $allFilterKey,
            'siswa_nis' => '',
            'search' => '',
            'has_foto' => false,
            'show_all' => false,
        ];
        $hasFilter = $hasActiveFilter ?? true;
        $statistics = $statistics ?? [
            'total' => $prestasi->count(),
            'akademik' => $prestasi->where('kategori', \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_AKADEMIK)->count(),
            'non_akademik' => $prestasi->where('kategori', \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_NON_AKADEMIK)->count(),
            'beregu' => $prestasi->filter(fn ($p) => (bool) $p->is_beregu)->count(),
            'dengan_foto' => $prestasi->filter(fn ($p) => filled($p->foto))->count(),
        ];
        $unitStats = isset($statistics['per_unit']) && is_array($statistics['per_unit'])
            ? $statistics['per_unit']
            : array_fill_keys($unitOptions, 0);
        $isShowAllActive = ! empty($filters['show_all'])
            && ($filters['tahun_ajaran_id'] ?? $allFilterKey) === $allFilterKey
            && ($filters['unit'] ?? $allFilterKey) === $allFilterKey
            && ($filters['kategori'] ?? $allFilterKey) === $allFilterKey
            && ($filters['tingkat'] ?? $allFilterKey) === $allFilterKey
            && ($filters['siswa_nis'] ?? '') === ''
            && ($filters['search'] ?? '') === ''
            && empty($filters['has_foto']);
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="fa fa-trophy" style="color: white;"></i>
                    </div>
                    <div>
                        Pendataan Prestasi Siswa
                        <div class="page-title-subheading">
                            Kelola rekam jejak prestasi akademik &amp; non-akademik siswa beserta catatan tim/beregu dan dokumentasi foto.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('prestasi-siswa.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH PRESTASI
                    </a>
                </div>
            </div>
        </div>

        <!-- STATISTIK RINGKAS -->
        <div class="row mb-3">
            <div class="col-sm-6 col-lg-3 mb-2 mb-lg-0">
                <a href="{{ route('prestasi-siswa.index', ['show_all' => 1]) }}"
                    class="prestasi-stat-card {{ $isShowAllActive ? 'is-active-filter' : '' }}">
                    <div>
                        <div class="prestasi-stat-label">Total Prestasi</div>
                        <div class="prestasi-stat-value">{{ number_format($statistics['total']) }}</div>
                        <div class="prestasi-stat-sub">{{ $statistics['beregu'] }} beregu / {{ max(0, $statistics['total'] - $statistics['beregu']) }} individu</div>
                    </div>
                    <span class="prestasi-stat-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-trophy"></i>
                    </span>
                </a>
            </div>

            <div class="col-sm-6 col-lg-3 mb-2 mb-lg-0">
                <a href="{{ route('prestasi-siswa.index', ['kategori' => \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_AKADEMIK]) }}"
                    class="prestasi-stat-card {{ ($filters['kategori'] ?? $allFilterKey) === \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_AKADEMIK ? 'is-active-filter' : '' }}">
                    <div>
                        <div class="prestasi-stat-label">Kategori Akademik</div>
                        <div class="prestasi-stat-value" style="color: #1d4ed8;">{{ number_format($statistics['akademik']) }}</div>
                        <div class="prestasi-stat-sub">Sains, olimpiade &amp; mapel</div>
                    </div>
                    <span class="prestasi-stat-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-graduation-cap"></i>
                    </span>
                </a>
            </div>

            <div class="col-sm-6 col-lg-3 mb-2 mb-sm-0">
                <a href="{{ route('prestasi-siswa.index', ['kategori' => \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_NON_AKADEMIK]) }}"
                    class="prestasi-stat-card {{ ($filters['kategori'] ?? $allFilterKey) === \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_NON_AKADEMIK ? 'is-active-filter' : '' }}">
                    <div>
                        <div class="prestasi-stat-label">Non-Akademik</div>
                        <div class="prestasi-stat-value" style="color: #047857;">{{ number_format($statistics['non_akademik']) }}</div>
                        <div class="prestasi-stat-sub">Olahraga, seni, tahfidz &amp; ekskul</div>
                    </div>
                    <span class="prestasi-stat-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa fa-medal"></i>
                    </span>
                </a>
            </div>

            <div class="col-sm-6 col-lg-3">
                <a href="{{ route('prestasi-siswa.index', ['has_foto' => 1]) }}"
                    class="prestasi-stat-card {{ ! empty($filters['has_foto']) ? 'is-active-filter' : '' }}">
                    <div>
                        <div class="prestasi-stat-label">Dokumentasi Foto</div>
                        <div class="prestasi-stat-value" style="color: #6d28d9;">{{ number_format($statistics['dengan_foto']) }}</div>
                        <div class="prestasi-stat-sub">Piagam / foto penyerahan</div>
                    </div>
                    <span class="prestasi-stat-icon" style="background: #f5f3ff; color: #7c3aed;">
                        <i class="fa fa-camera"></i>
                    </span>
                </a>
            </div>
        </div>

        @if (! empty($unitStats))
            <div class="prestasi-unit-strip mb-3 shadow-sm">
                <span class="small font-weight-bold text-uppercase text-muted mr-1">
                    <i class="fa fa-school text-warning mr-1"></i> Prestasi per Unit:
                </span>
                @foreach ($unitStats as $unitKey => $unitCount)
                    <a href="{{ route('prestasi-siswa.index', ['unit' => $unitKey]) }}"
                        class="prestasi-unit-pill {{ ($filters['unit'] ?? $allFilterKey) === $unitKey ? 'is-active' : '' }}">
                        <span>{{ $unitKey }}</span>
                        <span class="badge badge-light">{{ number_format($unitCount) }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <!-- FILTER & PENCARIAN -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter &amp; Pencarian Prestasi Siswa</span>
                    @if ($hasFilter)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                    @if (! empty($filters['has_foto']))
                        <span class="badge badge-info">Dengan Foto</span>
                    @endif
                    @if (($filters['siswa_nis'] ?? '') !== '')
                        <span class="badge badge-warning text-dark">NIS: {{ $filters['siswa_nis'] }}</span>
                    @endif
                </div>
                @if ($hasFilter)
                    <span class="filter-count-pill">
                        <i class="fa fa-trophy text-warning"></i>
                        Menampilkan: <strong>{{ number_format($prestasi->count()) }}</strong> prestasi
                    </span>
                @endif
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('prestasi-siswa.index') }}">
                    <div class="prestasi-filter-grid">
                        <div>
                            <label for="filter_tahun_ajaran_id" class="filter-label">Tahun Ajaran</label>
                            <select name="tahun_ajaran_id" id="filter_tahun_ajaran_id" class="filter-control">
                                <option value="{{ $allFilterKey }}"
                                    @selected(($filters['tahun_ajaran_id'] ?? $allFilterKey) === $allFilterKey)>
                                    Semua Tahun Ajaran
                                </option>
                                @foreach ($tahunAjaranOptions as $ta)
                                    <option value="{{ $ta->sqid }}"
                                        @selected((string) ($filters['tahun_ajaran_id'] ?? '') === (string) $ta->id)>
                                        {{ $ta->nama_tahun_ajaran }} - Sem {{ strtoupper($ta->semester) }}
                                        {{ $ta->is_active ? '(Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="filter_unit" class="filter-label">Unit</label>
                            <select name="unit" id="filter_unit" class="filter-control">
                                <option value="{{ $allFilterKey }}"
                                    @selected(($filters['unit'] ?? $allFilterKey) === $allFilterKey)>
                                    Semua Unit
                                </option>
                                @foreach ($unitOptions as $uOpt)
                                    <option value="{{ $uOpt }}"
                                        @selected(($filters['unit'] ?? $allFilterKey) === $uOpt)>
                                        {{ $uOpt }}@if (isset($unitStats[$uOpt])) ({{ number_format($unitStats[$uOpt]) }})@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="filter_kategori" class="filter-label">Kategori</label>
                            <select name="kategori" id="filter_kategori" class="filter-control">
                                <option value="{{ $allFilterKey }}"
                                    @selected(($filters['kategori'] ?? $allFilterKey) === $allFilterKey)>
                                    Semua Kategori
                                </option>
                                @foreach ($kategoriOptions as $katKey => $katLabel)
                                    <option value="{{ $katKey }}"
                                        @selected(($filters['kategori'] ?? '') === $katKey)>
                                        {{ $katLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="filter_tingkat" class="filter-label">Tingkat</label>
                            <select name="tingkat" id="filter_tingkat" class="filter-control">
                                <option value="{{ $allFilterKey }}"
                                    @selected(($filters['tingkat'] ?? $allFilterKey) === $allFilterKey)>
                                    Semua Tingkat
                                </option>
                                @foreach ($tingkatOptions as $tOpt)
                                    <option value="{{ $tOpt }}"
                                        @selected(($filters['tingkat'] ?? '') === $tOpt)>
                                        {{ $tOpt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="prestasi-filter-search">
                            <label for="filter_search" class="filter-label">Cari Siswa / Tim / Prestasi</label>
                            <input type="text" name="search" id="filter_search" class="filter-control"
                                placeholder="Nama siswa, tim, lomba, penyelenggara..."
                                value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>

                    <div class="filter-footer">
                        <div class="small text-muted">
                            @if ($hasFilter)
                                <i class="fa fa-check-circle text-success mr-1"></i>
                                Filter aktif diterapkan pada daftar prestasi di bawah.
                            @else
                                <i class="fa fa-info-circle text-primary mr-1"></i>
                                Pilih filter di atas atau klik salah satu unit / kartu statistik untuk menampilkan daftar prestasi.
                            @endif
                        </div>

                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                            <button type="submit" class="filter-btn filter-btn-primary">
                                <i class="fa fa-filter"></i> Terapkan Filter
                            </button>
                            <a href="{{ route('prestasi-siswa.index', ['show_all' => 1]) }}" class="filter-btn filter-btn-secondary">
                                <i class="fa fa-list"></i> Tampilkan Semua
                            </a>
                            @if ($hasFilter)
                                <a href="{{ route('prestasi-siswa.index') }}" class="filter-btn filter-btn-secondary">
                                    <i class="fa fa-sync-alt"></i> Reset Filter
                                </a>
                            @endif
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if (! $hasFilter)
            <div class="prestasi-prompt-card shadow-sm mb-3">
                <div class="mb-2" style="font-size: 2rem; color: #ea580c;">
                    <i class="fa fa-filter"></i>
                </div>
                <h5 class="font-weight-bold text-slate-800 mb-1">
                    Pilih Filter untuk Menampilkan Daftar Prestasi
                </h5>
                <p class="text-muted small mb-3" style="max-width: 560px; margin-left: auto; margin-right: auto;">
                    Agar halaman tetap ringan saat data prestasi siswa semakin banyak, daftar tabel belum dimuat otomatis. Gunakan filter di atas, klik pill unit, atau gunakan pintasan cepat di bawah.
                </p>
                <div class="d-flex flex-wrap justify-content-center" style="gap: 0.5rem;">
                    @if ($activeTaModel)
                        <a href="{{ route('prestasi-siswa.index', ['tahun_ajaran_id' => $activeTaModel->sqid]) }}"
                            class="btn text-white font-weight-bold shadow-sm"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                            <i class="fa fa-calendar-check mr-1"></i>
                            Tampilkan TA Aktif ({{ $activeTaModel->nama_tahun_ajaran }} - Sem {{ strtoupper($activeTaModel->semester) }})
                        </a>
                    @endif
                    <a href="{{ route('prestasi-siswa.index', ['show_all' => 1]) }}"
                        class="btn btn-light border font-weight-bold shadow-sm" style="border-radius: 8px;">
                        <i class="fa fa-trophy mr-1 text-warning"></i> Tampilkan Semua ({{ number_format($statistics['total']) }})
                    </a>
                </div>
            </div>
        @else
            <!-- TABEL DAFTAR PRESTASI -->
            <div class="main-card card mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover table-striped align-middle" id="myTable3">
                        <thead>
                            <tr>
                                <th style="width: 4%;">No</th>
                                <th style="width: 24%;">Siswa / Tim</th>
                                <th>Nama Prestasi &amp; Penyelenggara</th>
                                <th class="text-center" style="width: 11%;">Kategori</th>
                                <th style="width: 15%;">Peringkat &amp; Tingkat</th>
                                <th style="width: 11%;">Tanggal</th>
                                <th class="text-center" style="width: 8%;">Foto</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prestasi as $item)
                                @php
                                    $siswaCollection = $item->relationLoaded('siswa') ? $item->siswa : collect();
                                    $siswaCount = $siswaCollection->count();
                                    $primarySiswa = $siswaCollection->first();
                                    $taModel = $item->relationLoaded('tahunAjaran') ? $item->tahunAjaran : null;
                                    $fotoUrl = $item->foto_url;
                                    $cleanKeterangan = trim(preg_replace('/\s+/', ' ', (string) $item->keterangan) ?? '');
                                    $teamDisplayTitle = $cleanKeterangan !== ''
                                        ? \Illuminate\Support\Str::limit($cleanKeterangan, 44)
                                        : 'Tim Beregu';
                                    $teamUnits = $siswaCollection
                                        ->map(static fn ($anggota): string => strtoupper(trim((string) ($anggota->relationLoaded('kelas') ? $anggota->kelas?->jenjang_kelas : ''))))
                                        ->filter()
                                        ->unique()
                                        ->sort(static fn (string $a, string $b): int => \App\Models\Admin\DataMaster\Kelas::compareJenjangOrder($a, $b))
                                        ->values();
                                    $membersPayload = $siswaCount > 1
                                        ? $siswaCollection->map(static function ($anggota): array {
                                            $kelasModel = $anggota->relationLoaded('kelas') ? $anggota->kelas : null;

                                            return [
                                                'nis' => (string) $anggota->nis,
                                                'nama' => (string) $anggota->nama_lengkap,
                                                'kelas' => $kelasModel
                                                    ? $kelasModel->nama_kelas.' ('.$kelasModel->jenjang_kelas.')'
                                                    : '-',
                                                'url' => route('siswa.show', $anggota),
                                            ];
                                        })->values()->all()
                                        : [];
                                    $searchCellText = trim($cleanKeterangan.' '.$item->daftar_siswa_ringkas.' '.$siswaCollection->pluck('nis')->implode(' ').' '.$teamUnits->implode(' '));
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td data-search="{{ $searchCellText }}">
                                        @if ($siswaCount > 1)
                                            <div class="d-flex align-items-center" style="gap: 0.65rem;">
                                                <div class="d-inline-flex align-items-center justify-content-center font-weight-bold"
                                                    style="width: 36px; height: 36px; border-radius: 999px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.85rem; flex-shrink: 0;">
                                                    <i class="fa fa-users"></i>
                                                </div>
                                                <div style="min-width: 0; flex: 1;">
                                                    <a href="{{ route('prestasi-siswa.show', $item) }}"
                                                        class="font-weight-bold text-slate-800 text-decoration-none d-block text-truncate"
                                                        style="max-width: 220px;"
                                                        title="{{ $cleanKeterangan !== '' ? $cleanKeterangan : $item->daftar_siswa_ringkas }}">
                                                        {{ $teamDisplayTitle }}
                                                    </a>
                                                    <div class="d-flex flex-wrap align-items-center mt-1" style="gap: 0.3rem;">
                                                        <button type="button"
                                                            class="prestasi-beregu-trigger js-prestasi-members"
                                                            data-title="{{ $item->nama_prestasi }}"
                                                            data-members="{{ json_encode($membersPayload) }}"
                                                            title="Klik untuk melihat daftar {{ $siswaCount }} anggota tim">
                                                            <i class="fa fa-users"></i>
                                                            <span>Beregu &bull; {{ $siswaCount }} Siswa</span>
                                                        </button>
                                                        @foreach ($teamUnits as $teamUnit)
                                                            <span class="badge badge-light border text-slate-700" style="font-size: 0.67rem;">
                                                                {{ $teamUnit }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif ($primarySiswa)
                                            @php
                                                $kelasPrimary = $primarySiswa->relationLoaded('kelas') ? $primarySiswa->kelas : null;
                                            @endphp
                                            <div class="d-flex align-items-center" style="gap: 0.65rem;">
                                                @if ($primarySiswa->avatar_url)
                                                    <img src="{{ $primarySiswa->avatar_url }}" alt="{{ $primarySiswa->nama_lengkap }}"
                                                        style="width: 36px; height: 36px; border-radius: 999px; object-fit: cover; flex-shrink: 0;">
                                                @else
                                                    <div class="d-inline-flex align-items-center justify-content-center font-weight-bold"
                                                        style="width: 36px; height: 36px; border-radius: 999px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.8rem; flex-shrink: 0;">
                                                        {{ $primarySiswa->inisial_nama }}
                                                    </div>
                                                @endif
                                                <div style="min-width: 0;">
                                                    <a href="{{ route('siswa.show', $primarySiswa) }}"
                                                        class="font-weight-bold text-slate-800 text-decoration-none d-block text-truncate"
                                                        title="Lihat profil {{ $primarySiswa->nama_lengkap }}">
                                                        {{ $primarySiswa->nama_lengkap }}
                                                    </a>
                                                    <div class="small text-muted">
                                                        NIS: <span class="font-mono">{{ $primarySiswa->nis }}</span>
                                                        @if ($kelasPrimary)
                                                            &bull; {{ $kelasPrimary->nama_kelas }} ({{ $kelasPrimary->jenjang_kelas }})
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('prestasi-siswa.show', $item) }}"
                                            class="font-weight-bold text-slate-800 text-decoration-none"
                                            style="font-size: 0.92rem;">
                                            {{ $item->nama_prestasi }}
                                        </a>
                                        <div class="small text-muted mt-1">
                                            <i class="fa fa-building mr-1 text-secondary"></i>{{ $item->penyelenggara }}
                                            @if ($taModel)
                                                &bull; <span class="text-slate-600">{{ $taModel->nama_tahun_ajaran }} (Sem {{ strtoupper($taModel->semester) }})</span>
                                            @endif
                                        </div>
                                        @if ($siswaCount <= 1 && filled($item->keterangan))
                                            <div class="prestasi-team-note text-truncate" title="{{ $cleanKeterangan }}">
                                                <i class="fa fa-info-circle text-warning mr-1"></i>{{ $item->ringkasan_keterangan }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->kategori_badge_class }} px-2 py-1" style="font-size: 0.74rem;">
                                            {{ $item->kategori_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="mb-1">
                                            <span class="prestasi-rank-pill">
                                                <i class="fa fa-award"></i> {{ $item->peringkat }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="badge {{ $item->tingkat_badge_class }} px-2 py-1" style="font-size: 0.71rem;">
                                                Tingkat {{ $item->tingkat }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-weight-600 text-slate-700 small">
                                            {{ $item->tanggal_formatted }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($fotoUrl)
                                            <button type="button" class="prestasi-thumb-btn js-prestasi-lightbox"
                                                data-foto-url="{{ $fotoUrl }}"
                                                data-foto-title="{{ $item->nama_prestasi }} — {{ $siswaCount > 1 ? $teamDisplayTitle : $item->daftar_siswa_ringkas }}"
                                                title="Klik untuk memperbesar foto">
                                                <img src="{{ $fotoUrl }}" alt="Foto {{ $item->nama_prestasi }}" class="prestasi-thumb-img">
                                            </button>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('prestasi-siswa.show', $item) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-eye text-info action-item-icon"></i>
                                                <span>Detail Prestasi</span>
                                            </a>
                                            <a href="{{ route('prestasi-siswa.edit', $item) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Prestasi</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('prestasi-siswa.destroy', $item) }}" method="post" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="action-dropdown-item is-danger delete-button" role="menuitem"
                                                    data-nama="{{ $item->nama_prestasi }} ({{ $siswaCount > 1 ? $teamDisplayTitle : $item->daftar_siswa_ringkas }})">
                                                    <i class="fa fa-trash text-danger action-item-icon"></i>
                                                    <span>Hapus Prestasi</span>
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
        @endif
    </div>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            @if ($hasFilter)
                initDataTables('#myTable3');
            @endif

            function escapeHtml(str) {
                return $('<div>').text(String(str ?? '')).html();
            }

            $(document).on('click', '.js-prestasi-members', function() {
                const title = $(this).data('title') || 'Anggota Tim Prestasi';
                let members = [];
                try {
                    const raw = $(this).attr('data-members');
                    members = raw ? JSON.parse(raw) : [];
                } catch (err) {
                    members = [];
                }

                const itemsHtml = Array.isArray(members) && members.length > 0
                    ? '<div class="prestasi-member-modal-list">' + members.map(function(m, idx) {
                        return '<a href="' + escapeHtml(m.url) + '" class="prestasi-member-modal-item">'
                            + '<div>'
                            + '<div class="font-weight-bold small text-slate-800">' + (idx + 1) + '. ' + escapeHtml(m.nama) + '</div>'
                            + '<div class="text-muted" style="font-size: 0.72rem;">NIS ' + escapeHtml(m.nis) + ' &bull; ' + escapeHtml(m.kelas) + '</div>'
                            + '</div>'
                            + '<i class="fa fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>'
                            + '</a>';
                    }).join('') + '</div>'
                    : '<div class="text-muted small">Belum ada anggota terdaftar.</div>';

                Swal.fire({
                    title: 'Anggota Tim (' + members.length + ' Siswa)',
                    html: '<div class="small text-muted mb-2">' + escapeHtml(title) + '</div>' + itemsHtml,
                    showCloseButton: true,
                    showConfirmButton: false,
                    width: '520px'
                });
            });

            $(document).on('click', '.js-prestasi-lightbox', function() {
                const url = $(this).data('foto-url');
                const title = $(this).data('foto-title') || 'Dokumentasi Prestasi Siswa';

                Swal.fire({
                    title: title,
                    imageUrl: url,
                    imageAlt: title,
                    showCloseButton: true,
                    showConfirmButton: false,
                    width: '680px'
                });
            });

            $(document).on('click', '.delete-button', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const nama = $(this).data('nama') || 'prestasi ini';

                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    html: 'Apakah Anda yakin ingin menghapus prestasi <strong>' + escapeHtml(nama) + '</strong> beserta foto dokumentasinya?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
