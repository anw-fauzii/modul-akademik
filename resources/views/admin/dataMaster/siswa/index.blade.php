@extends('layouts.appui')

@section('title')
    <title>Master Data Siswa</title>
@endsection

@section('content')
    @pushOnce('css')
        <style>
            .siswa-stat-card {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                height: 100%;
                padding: 16px 18px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                text-decoration: none !important;
                color: inherit;
                transition: all 0.2s ease;
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            }

            .siswa-stat-card:hover {
                transform: translateY(-2px);
                border-color: #f97316;
                box-shadow: 0 10px 20px -6px rgba(234, 88, 12, 0.16);
            }

            .siswa-stat-card.is-active-filter {
                border-color: #ea580c;
                background: #fff7ed;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.14);
            }

            .siswa-stat-icon {
                width: 44px;
                height: 44px;
                border-radius: 12px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 1.1rem;
                flex-shrink: 0;
            }

            .siswa-stat-value {
                font-size: 1.65rem;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.15;
                margin-top: 4px;
            }

            .siswa-stat-footer {
                margin-top: 10px;
                padding-top: 8px;
                border-top: 1px solid #f1f5f9;
                font-size: 0.75rem;
                color: #64748b;
            }

            .siswa-jenjang-strip {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 8px;
                padding: 12px 16px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
            }

            .siswa-jenjang-pill {
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

            .siswa-jenjang-pill:hover,
            .siswa-jenjang-pill.is-active {
                background: #ea580c;
                border-color: #ea580c;
                color: #ffffff;
            }

            .siswa-prompt-card {
                border: 2px dashed #cbd5e1;
                border-radius: 14px;
                background: #ffffff;
                padding: 2.5rem 1.5rem;
                text-align: center;
            }
        </style>
    @endPushOnce

    @php
        $inactiveFilterKey = \App\Services\Admin\DataMaster\SiswaService::FILTER_STATUS_INACTIVE;
        $unassignedFilterKey = \App\Services\Admin\DataMaster\SiswaService::FILTER_KELAS_UNASSIGNED;

        $stats = $statistics ?? [
            'total' => $siswa->count(),
            'aktif' => $siswa->filter(static fn ($s): bool => strtolower(trim((string) $s->status)) === 'aktif')->count(),
            'lulus' => $siswa->filter(static fn ($s): bool => strtolower(trim((string) $s->status)) === 'lulus')->count(),
            'pindah' => $siswa->filter(static fn ($s): bool => strtolower(trim((string) $s->status)) === 'pindah')->count(),
            'nonaktif_keluar' => $siswa->filter(static fn ($s): bool => in_array(strtolower(trim((string) $s->status)), ['nonaktif', 'keluar'], true))->count(),
            'laki_laki' => $siswa->filter(static fn ($s): bool => strtoupper(trim((string) $s->jenis_kelamin)) === 'L')->count(),
            'perempuan' => $siswa->filter(static fn ($s): bool => strtoupper(trim((string) $s->jenis_kelamin)) === 'P')->count(),
            'aktif_laki_laki' => $siswa->filter(static fn ($s): bool => strtolower(trim((string) $s->status)) === 'aktif' && strtoupper(trim((string) $s->jenis_kelamin)) === 'L')->count(),
            'aktif_perempuan' => $siswa->filter(static fn ($s): bool => strtolower(trim((string) $s->status)) === 'aktif' && strtoupper(trim((string) $s->jenis_kelamin)) === 'P')->count(),
            'punya_kelas' => $siswa->filter(static fn ($s): bool => ($s->akademik_master_kelas_id ?? ($s->relationLoaded('kelas') ? $s->kelas?->id : null)) !== null)->count(),
            'belum_punya_kelas' => $siswa->filter(static fn ($s): bool => ($s->akademik_master_kelas_id ?? ($s->relationLoaded('kelas') ? $s->kelas?->id : null)) === null)->count(),
            'jenjang_aktif' => [],
        ];

        $inactiveTotal = (int) $stats['lulus'] + (int) $stats['pindah'] + (int) $stats['nonaktif_keluar'];
        $activeFilters = $filters ?? [
            'search' => '',
            'status' => '',
            'kelas_id' => '',
            'jenjang' => '',
            'jenis_kelamin' => '',
            'show_all' => false,
        ];
        $hasFilter = $hasActiveFilter ?? true;
        $kelasFilterOptions = $kelasOptions ?? collect();
        $jenjangFilterOptions = $jenjangOptions ?? [];
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-study" style="color: white;"></i>
                    </div>
                    <div>
                        Data Siswa
                        <div class="page-title-subheading">
                            Ringkasan statistik peserta didik, filter pencarian cepat, dan pengelolaan data siswa.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('siswa.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH SISWA
                    </a>
                </div>
            </div>
        </div>

        <!-- STATISTIK SISWA -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-3 mb-3">
            <div>
                <a href="{{ route('siswa.index', ['show_all' => 1]) }}"
                    class="siswa-stat-card {{ !empty($activeFilters['show_all']) && $activeFilters['status'] === '' && $activeFilters['kelas_id'] === '' ? 'is-active-filter' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.4px;">
                                Total Seluruh Siswa
                            </div>
                            <div class="siswa-stat-value">{{ number_format($stats['total']) }}</div>
                        </div>
                        <span class="siswa-stat-icon" style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);">
                            <i class="fa fa-users"></i>
                        </span>
                    </div>
                    <div class="siswa-stat-footer d-flex justify-content-between">
                        <span>{{ $stats['laki_laki'] }} L • {{ $stats['perempuan'] }} P</span>
                        <span class="font-weight-bold text-primary">{{ $stats['punya_kelas'] }} Sudah Kelas</span>
                    </div>
                </a>
            </div>

            <div>
                <a href="{{ route('siswa.index', ['status' => \App\Models\Admin\DataMaster\Siswa::STATUS_AKTIF]) }}"
                    class="siswa-stat-card {{ strcasecmp($activeFilters['status'], 'Aktif') === 0 && $activeFilters['jenjang'] === '' && $activeFilters['kelas_id'] === '' ? 'is-active-filter' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.4px;">
                                Siswa Aktif
                            </div>
                            <div class="siswa-stat-value text-success">{{ number_format($stats['aktif']) }}</div>
                        </div>
                        <span class="siswa-stat-icon" style="background: linear-gradient(135deg, #10b981 0%, #047857 100%);">
                            <i class="fa fa-user-check"></i>
                        </span>
                    </div>
                    <div class="siswa-stat-footer d-flex justify-content-between">
                        <span>{{ $stats['aktif_laki_laki'] }} Laki-laki</span>
                        <span>{{ $stats['aktif_perempuan'] }} Perempuan</span>
                    </div>
                </a>
            </div>

            <div>
                <a href="{{ route('siswa.index', ['status' => $inactiveFilterKey]) }}"
                    class="siswa-stat-card {{ $activeFilters['status'] === $inactiveFilterKey ? 'is-active-filter' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.4px;">
                                Lulus / Pindah / Nonaktif
                            </div>
                            <div class="siswa-stat-value text-slate-700">{{ number_format($inactiveTotal) }}</div>
                        </div>
                        <span class="siswa-stat-icon" style="background: linear-gradient(135deg, #64748b 0%, #334155 100%);">
                            <i class="fa fa-user-clock"></i>
                        </span>
                    </div>
                    <div class="siswa-stat-footer">
                        {{ $stats['lulus'] }} Lulus • {{ $stats['pindah'] }} Pindah • {{ $stats['nonaktif_keluar'] }} Nonaktif/Keluar
                    </div>
                </a>
            </div>

            <div>
                <a href="{{ route('siswa.index', ['kelas_id' => $unassignedFilterKey]) }}"
                    class="siswa-stat-card {{ $activeFilters['kelas_id'] === $unassignedFilterKey ? 'is-active-filter' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.4px;">
                                Belum Punya Kelas
                            </div>
                            <div class="siswa-stat-value text-warning">{{ number_format($stats['belum_punya_kelas']) }}</div>
                        </div>
                        <span class="siswa-stat-icon" style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);">
                            <i class="fa fa-chalkboard"></i>
                        </span>
                    </div>
                    <div class="siswa-stat-footer">
                        Klik untuk melihat siswa yang belum ditempatkan
                    </div>
                </a>
            </div>
        </div>

        @if (! empty($stats['jenjang_aktif']))
            <div class="siswa-jenjang-strip mb-3 shadow-sm">
                <span class="small font-weight-bold text-uppercase text-muted mr-1">
                    <i class="fa fa-layer-group text-primary mr-1"></i> Siswa Aktif per Jenjang:
                </span>
                @foreach ($stats['jenjang_aktif'] as $jenjangKey => $jenjangCount)
                    @if ($jenjangKey === 'BELUM_DITEMPATKAN')
                        <a href="{{ route('siswa.index', ['status' => \App\Models\Admin\DataMaster\Siswa::STATUS_AKTIF, 'kelas_id' => $unassignedFilterKey]) }}"
                            class="siswa-jenjang-pill {{ strcasecmp($activeFilters['status'], 'Aktif') === 0 && $activeFilters['kelas_id'] === $unassignedFilterKey ? 'is-active' : '' }}">
                            <span>Belum Punya Kelas</span>
                            <span class="badge badge-light">{{ $jenjangCount }}</span>
                        </a>
                    @else
                        <a href="{{ route('siswa.index', ['status' => \App\Models\Admin\DataMaster\Siswa::STATUS_AKTIF, 'jenjang' => $jenjangKey]) }}"
                            class="siswa-jenjang-pill {{ $activeFilters['jenjang'] === $jenjangKey ? 'is-active' : '' }}">
                            <span>{{ $jenjangKey }}</span>
                            <span class="badge badge-light">{{ $jenjangCount }}</span>
                        </a>
                    @endif
                @endforeach
            </div>
        @endif

        <!-- FILTER CARD -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter &amp; Pencarian Siswa</span>
                    @if ($hasFilter)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                @if ($hasFilter)
                    <span class="filter-count-pill">
                        <i class="fa fa-check-circle text-success"></i>
                        Menampilkan {{ number_format($siswa->count()) }} siswa sesuai filter
                    </span>
                @endif
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('siswa.index') }}">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                        <div class="sm:col-span-2 lg:col-span-3">
                            <label for="filter_search" class="filter-label">
                                Cari Nama / NIS / NISN
                            </label>
                            <input type="text" name="search" id="filter_search" class="filter-control"
                                value="{{ $activeFilters['search'] }}" placeholder="Ketik nama, NIS, atau NISN...">
                        </div>

                        <div class="lg:col-span-2">
                            <label for="filter_status" class="filter-label">
                                Status
                            </label>
                            <select name="status" id="filter_status" class="filter-control">
                                <option value="">-- Semua Status --</option>
                                @foreach (\App\Models\Admin\DataMaster\Siswa::STATUS_OPTIONS as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($activeFilters['status'] === $statusOption)>
                                        {{ $statusOption }}
                                    </option>
                                @endforeach
                                <option value="{{ $inactiveFilterKey }}" @selected($activeFilters['status'] === $inactiveFilterKey)>
                                    Nonaktif / Lulus / Pindah / Keluar
                                </option>
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <label for="filter_jenjang" class="filter-label">
                                Jenjang
                            </label>
                            <select name="jenjang" id="filter_jenjang" class="filter-control">
                                <option value="">-- Semua Jenjang --</option>
                                @foreach ($jenjangFilterOptions as $jenjangOption)
                                    <option value="{{ $jenjangOption }}" @selected($activeFilters['jenjang'] === $jenjangOption)>
                                        {{ $jenjangOption }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lg:col-span-3">
                            <label for="filter_kelas_id" class="filter-label">
                                Kelas
                            </label>
                            <select name="kelas_id" id="filter_kelas_id" class="filter-control">
                                <option value="">-- Semua Kelas --</option>
                                <option value="{{ $unassignedFilterKey }}" @selected($activeFilters['kelas_id'] === $unassignedFilterKey)>
                                    Belum Ditempatkan di Kelas
                                </option>
                                @foreach ($kelasFilterOptions as $kelasOption)
                                    <option value="{{ $kelasOption->id }}" @selected((string) $activeFilters['kelas_id'] === (string) $kelasOption->id)>
                                        {{ $kelasOption->nama_kelas }} ({{ strtoupper($kelasOption->jenjang_kelas) }})
                                        @if ($kelasOption->tahunAjaran)
                                            — {{ $kelasOption->tahunAjaran->nama_tahun_ajaran }} Smt {{ strtoupper($kelasOption->tahunAjaran->semester) }}
                                        @endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lg:col-span-2">
                            <label for="filter_jenis_kelamin" class="filter-label">
                                Jenis Kelamin
                            </label>
                            <select name="jenis_kelamin" id="filter_jenis_kelamin" class="filter-control">
                                <option value="">-- Semua --</option>
                                @foreach (\App\Models\Admin\DataMaster\Siswa::JENIS_KELAMIN_OPTIONS as $jkKey => $jkLabel)
                                    <option value="{{ $jkKey }}" @selected($activeFilters['jenis_kelamin'] === $jkKey)>
                                        {{ $jkLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="filter-footer">
                        <div class="flex flex-wrap items-center gap-2 w-full sm:w-auto">
                            <button type="submit" class="filter-btn filter-btn-primary flex-1 sm:flex-initial">
                                <i class="fa fa-filter"></i> Terapkan Filter
                            </button>
                            <a href="{{ route('siswa.index', ['show_all' => 1]) }}" class="filter-btn filter-btn-secondary flex-1 sm:flex-initial">
                                <i class="fa fa-list"></i> Tampilkan Semua
                            </a>
                            @if ($hasFilter)
                                <a href="{{ route('siswa.index') }}" class="filter-btn filter-btn-secondary" title="Reset Filter">
                                    <i class="fa fa-undo"></i> Reset
                                </a>
                            @endif
                        </div>

                        <span class="small text-muted">
                            <i class="fa fa-info-circle text-primary mr-1"></i>
                            Gunakan filter di atas atau klik kartu statistik untuk pencarian cepat.
                        </span>
                    </div>
                </form>
            </div>
        </div>

        @if (! $hasFilter)
            <div class="siswa-prompt-card shadow-sm mb-3">
                <div class="mb-2" style="font-size: 2rem; color: #ea580c;">
                    <i class="fa fa-filter"></i>
                </div>
                <h5 class="font-weight-bold text-slate-800 mb-1">
                    Pilih Filter untuk Menampilkan Daftar Siswa
                </h5>
                <p class="text-muted small mb-3" style="max-width: 560px; margin-left: auto; margin-right: auto;">
                    Agar halaman tetap ringan saat data siswa banyak, daftar tabel belum dimuat otomatis. Gunakan filter di atas atau klik salah satu kartu statistik.
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('siswa.index', ['status' => \App\Models\Admin\DataMaster\Siswa::STATUS_AKTIF]) }}"
                        class="btn btn-primary font-weight-bold shadow-sm" style="border-radius: 8px;">
                        <i class="fa fa-user-check mr-1"></i> Tampilkan Siswa Aktif ({{ number_format($stats['aktif']) }})
                    </a>
                    <a href="{{ route('siswa.index', ['show_all' => 1]) }}"
                        class="btn btn-light border font-weight-bold shadow-sm" style="border-radius: 8px;">
                        <i class="fa fa-users mr-1"></i> Tampilkan Semua ({{ number_format($stats['total']) }})
                    </a>
                </div>
            </div>
        @else
            <!-- MAIN CARD -->
            <div class="main-card card mb-3 shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="mb-0 table table-hover table-striped align-middle" id="myTable3">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th>Siswa</th>
                                    <th class="text-center" style="width: 10%;">JK</th>
                                    <th>Kelas</th>
                                    <th>Pembimbing & Layanan</th>
                                    <th class="text-center" style="width: 11%;">Status</th>
                                    <th style="width: 72px; text-align: center;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($siswa as $item)
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-bold text-slate-800" style="font-size: 0.95rem;">
                                                {{ $item->nama_lengkap }}
                                            </div>
                                            <div class="small text-muted" style="font-size: 11px;">
                                                NIS: <span class="font-weight-bold">{{ $item->nis }}</span>
                                                @if ($item->nisn)
                                                    • NISN: {{ $item->nisn }}
                                                @endif
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            @if (strtoupper((string) $item->jenis_kelamin) === 'L')
                                                <span class="badge badge-info px-2 py-1">Laki-laki</span>
                                            @elseif (strtoupper((string) $item->jenis_kelamin) === 'P')
                                                <span class="badge text-white px-2 py-1" style="background-color: #ec4899;">Perempuan</span>
                                            @else
                                                <span class="badge badge-secondary px-2 py-1">{{ $item->jenis_kelamin_label }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->kelas)
                                                <div class="font-weight-bold text-slate-700">{{ $item->kelas->nama_kelas }}</div>
                                                <div class="small text-muted">{{ strtoupper($item->kelas->jenjang_kelas) }}</div>
                                            @else
                                                <span class="text-muted font-italic">Belum ditempatkan</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="small">
                                                <div>
                                                    <i class="fa fa-user-tie text-primary mr-1"></i>
                                                    {{ $item->karyawan?->nama_gelar ?? 'Pembimbing belum ditentukan' }}
                                                </div>
                                                <div class="text-muted">
                                                    <i class="fa fa-medal text-warning mr-1"></i>
                                                    {{ $item->ekstrakurikuler?->nama_ekstrakurikuler ?? 'Tanpa Ekskul' }}
                                                    <span class="mx-1">•</span>
                                                    <i class="fa fa-car text-info mr-1"></i>
                                                    {{ $item->jemputan?->petugas?->nama_gelar ?? 'Tanpa Jemputan' }}
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $item->status_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                                {{ $item->status }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <x-action-dropdown>
                                                <a href="{{ route('siswa.show', $item->nis) }}" class="action-dropdown-item" role="menuitem">
                                                    <i class="fa fa-eye text-info action-item-icon"></i>
                                                    <span>Detail Data</span>
                                                </a>
                                                <a href="{{ route('siswa.edit', $item->nis) }}" class="action-dropdown-item" role="menuitem">
                                                    <i class="fa fa-edit text-primary action-item-icon"></i>
                                                    <span>Edit Data</span>
                                                </a>
                                                <div class="action-dropdown-divider"></div>
                                                <form action="{{ route('siswa.destroy', $item->nis) }}" method="POST" class="delete-form">
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
        @endif
    </div>
@endsection

