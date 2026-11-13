@extends('layouts.appui')

@section('title')
    <title>Mata Pelajaran</title>
@endsection

@section('content')
    @push('css')
        <style>
            .mp-summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(170px, 1fr));
                gap: 0.85rem;
            }

            .mp-stat-card {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                height: 100%;
                padding: 14px 16px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                text-decoration: none !important;
                color: inherit;
                transition: all 0.2s ease;
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            }

            .mp-stat-card:hover {
                transform: translateY(-2px);
                border-color: #f97316;
                box-shadow: 0 10px 20px -6px rgba(234, 88, 12, 0.16);
            }

            .mp-stat-card.is-active-filter {
                border-color: #ea580c;
                background: #fff7ed;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.14);
            }

            .mp-stat-icon {
                width: 40px;
                height: 40px;
                border-radius: 12px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 1rem;
                flex-shrink: 0;
            }

            .mp-stat-value {
                font-size: 1.4rem;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.15;
                margin-top: 4px;
            }

            .mp-stat-footer {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px solid #f1f5f9;
                font-size: 0.74rem;
                color: #64748b;
            }
        </style>
    @endpush

    @php
        $mataPelajaran = $mataPelajaran ?? collect();
        $kelompokPelajaranOptions = $kelompokPelajaranOptions ?? collect();
        $unitOptions = $unitOptions ?? \App\Models\Admin\Pelajaran\MataPelajaran::UNIT_OPTIONS;
        $sifatOptions = $sifatOptions ?? \App\Models\Admin\Pelajaran\MataPelajaran::SIFAT_OPTIONS;
        $filters = $filters ?? [
            'unit' => '',
            'kelompok_pelajaran_id' => '',
            'sifat' => '',
            'status' => \App\Services\Admin\Pelajaran\MataPelajaranService::FILTER_ALL,
            'search' => '',
        ];

        if (isset($summary)) {
            $stats = $summary;
        } else {
            $fallbackPerUnit = [];
            $fallbackAktif = 0;

            foreach ($mataPelajaran as $mpRow) {
                $unitKey = strtoupper(trim((string) ($mpRow->unit ?? '')));
                if ($unitKey === '') {
                    continue;
                }
                if (! isset($fallbackPerUnit[$unitKey])) {
                    $fallbackPerUnit[$unitKey] = ['total' => 0, 'aktif' => 0];
                }
                $fallbackPerUnit[$unitKey]['total']++;
                if ((bool) $mpRow->status) {
                    $fallbackPerUnit[$unitKey]['aktif']++;
                    $fallbackAktif++;
                }
            }

            uksort($fallbackPerUnit, static fn (string $a, string $b): int => \App\Models\Admin\Pelajaran\MataPelajaran::compareUnitOrder($a, $b));

            $stats = [
                'total' => $mataPelajaran->count(),
                'aktif' => $fallbackAktif,
                'nonaktif' => max(0, $mataPelajaran->count() - $fallbackAktif),
                'per_unit' => $fallbackPerUnit,
            ];
        }

        $selectedUnit = strtoupper(trim((string) ($filters['unit'] ?? '')));
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-notebook" style="color: white;"></i>
                    </div>
                    <div>
                        Mata Pelajaran
                        <div class="page-title-subheading">
                            Kelola daftar mata pelajaran beserta singkatan, sifat, unit, kelompok pelajaran, urutan, dan status aktif.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('mata-pelajaran.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH MATA PELAJARAN
                    </a>
                </div>
            </div>
        </div>

        <!-- RINGKASAN PER UNIT -->
        <div class="mp-summary-grid mb-3">
            <a href="{{ route('mata-pelajaran.index', array_filter(['kelompok_pelajaran_id' => $filters['kelompok_pelajaran_id'], 'sifat' => $filters['sifat'], 'status' => $filters['status'] !== 'all' ? $filters['status'] : null, 'search' => $filters['search']])) }}"
                class="mp-stat-card {{ $selectedUnit === '' ? 'is-active-filter' : '' }}">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small font-weight-bold text-uppercase text-slate-500">Semua Unit</div>
                        <div class="mp-stat-value">{{ $stats['total'] }}</div>
                    </div>
                    <span class="mp-stat-icon" style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);">
                        <i class="fa fa-book"></i>
                    </span>
                </div>
                <div class="mp-stat-footer">
                    Aktif: <strong class="text-success">{{ $stats['aktif'] }}</strong> &bull; Nonaktif: <strong>{{ $stats['nonaktif'] }}</strong>
                </div>
            </a>

            @foreach ($unitOptions as $unitName)
                @php
                    $unitData = $stats['per_unit'][$unitName] ?? ['total' => 0, 'aktif' => 0];
                    $unitGradient = match ($unitName) {
                        'PG' => 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
                        'TK' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                        'SD' => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                        default => 'linear-gradient(135deg, #64748b 0%, #475569 100%)',
                    };
                @endphp
                <a href="{{ route('mata-pelajaran.index', array_filter(['unit' => $unitName, 'kelompok_pelajaran_id' => $filters['kelompok_pelajaran_id'], 'sifat' => $filters['sifat'], 'status' => $filters['status'] !== 'all' ? $filters['status'] : null, 'search' => $filters['search']])) }}"
                    class="mp-stat-card {{ $selectedUnit === $unitName ? 'is-active-filter' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small font-weight-bold text-uppercase text-slate-500">Unit {{ $unitName }}</div>
                            <div class="mp-stat-value">{{ $unitData['total'] }}</div>
                        </div>
                        <span class="mp-stat-icon" style="background: {{ $unitGradient }};">
                            <i class="fa fa-graduation-cap"></i>
                        </span>
                    </div>
                    <div class="mp-stat-footer">
                        Mapel Aktif: <strong class="text-success">{{ $unitData['aktif'] }}</strong>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- FILTER -->
        @php
            $hasActiveFilter = ($selectedUnit !== '')
                || (($filters['kelompok_pelajaran_id'] ?? '') !== '')
                || (($filters['sifat'] ?? '') !== '')
                || (($filters['status'] ?? 'all') !== 'all')
                || (($filters['search'] ?? '') !== '');
        @endphp
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter &amp; Pencarian Mata Pelajaran</span>
                    @if ($hasActiveFilter)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="filter-count-pill">
                        <i class="fa fa-book text-primary"></i>
                        Menampilkan <strong>{{ $mataPelajaran->count() }}</strong> Mapel
                    </span>
                    @if ($hasActiveFilter)
                        <a href="{{ route('mata-pelajaran.index') }}" class="filter-btn filter-btn-secondary" style="height: 1.9rem; min-height: 1.9rem; padding: 0 0.7rem; font-size: 0.75rem;">
                            <i class="fa fa-undo"></i> Reset
                        </a>
                    @endif
                </div>
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('mata-pelajaran.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <div class="lg:col-span-2">
                        <label for="filter_unit" class="filter-label">Unit</label>
                        <select name="unit" id="filter_unit" class="filter-control">
                            <option value="">Semua Unit</option>
                            @foreach ($unitOptions as $unitOption)
                                <option value="{{ $unitOption }}" @selected($selectedUnit === $unitOption)>
                                    {{ $unitOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-3">
                        <label for="filter_kelompok" class="filter-label">Kelompok Pelajaran</label>
                        <select name="kelompok_pelajaran_id" id="filter_kelompok" class="filter-control">
                            <option value="">Semua Kelompok</option>
                            @foreach ($kelompokPelajaranOptions as $kelompokOption)
                                <option value="{{ $kelompokOption->id }}"
                                    @selected((string) ($filters['kelompok_pelajaran_id'] ?? '') === (string) $kelompokOption->id)>
                                    [{{ $kelompokOption->kode }}] {{ $kelompokOption->kelompok }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="filter_sifat" class="filter-label">Sifat</label>
                        <select name="sifat" id="filter_sifat" class="filter-control">
                            <option value="">Semua Sifat</option>
                            @foreach ($sifatOptions as $sifatOption)
                                <option value="{{ $sifatOption }}" @selected(($filters['sifat'] ?? '') === $sifatOption)>
                                    {{ ucfirst($sifatOption) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-2">
                        <label for="filter_status" class="filter-label">Status</label>
                        <select name="status" id="filter_status" class="filter-control">
                            <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Semua Status</option>
                            <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                            <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                        </select>
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3">
                        <label for="filter_search" class="filter-label">Cari Mapel / Singkatan</label>
                        <div class="filter-search-group">
                            <input type="text" name="search" id="filter_search" class="filter-control"
                                placeholder="Contoh: MTK / Matematika..." value="{{ $filters['search'] ?? '' }}">
                            <button type="submit" class="filter-btn filter-btn-primary filter-btn-icon" title="Terapkan Filter">
                                <i class="fa fa-search"></i>
                            </button>
                            <a href="{{ route('mata-pelajaran.index') }}" class="filter-btn filter-btn-secondary filter-btn-icon" title="Reset Filter">
                                <i class="fa fa-sync-alt"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="main-card card mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover table-striped align-middle" id="myTable3">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th class="text-center" style="width: 7%;">Urutan</th>
                                <th class="text-center" style="width: 10%;">Singkatan</th>
                                <th>Nama Mata Pelajaran</th>
                                <th style="width: 16%;">Kelompok</th>
                                <th class="text-center" style="width: 8%;">Unit</th>
                                <th class="text-center" style="width: 10%;">Sifat</th>
                                <th class="text-center" style="width: 11%;">Status</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mataPelajaran as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-light border font-weight-bold px-2 py-1 text-slate-700"
                                            style="font-size: 0.78rem;">
                                            #{{ $item->urutan }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-warning text-dark font-weight-bold px-2 py-1"
                                            style="font-size: 0.78rem; letter-spacing: 0.03em;">
                                            {{ $item->singkatan }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800" style="font-size: 0.94rem;">
                                            {{ $item->nama_mata_pelajaran }}
                                        </div>
                                        @if (filled($item->keterangan))
                                            <div class="small text-muted mt-0.5">
                                                {{ \Illuminate\Support\Str::limit($item->keterangan, 100) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->kelompokPelajaran)
                                            <span class="badge badge-light border text-slate-700 font-weight-bold mr-1">
                                                {{ $item->kelompokPelajaran->kode }}
                                            </span>
                                            <span class="small text-slate-700">{{ $item->kelompokPelajaran->kelompok }}</span>
                                        @else
                                            <span class="text-muted font-italic">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->unit_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ strtoupper((string) $item->unit) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->sifat_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ $item->sifat_label }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('mata-pelajaran.toggle-status', $item->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-sm {{ $item->status ? 'btn-success' : 'btn-outline-secondary' }} shadow-sm"
                                                style="border-radius: 6px; padding: 2px 10px; font-size: 0.74rem;"
                                                data-toggle="tooltip" data-placement="top"
                                                title="{{ $item->status ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan' }}">
                                                <i class="fa {{ $item->status ? 'fa-check-circle' : 'fa-power-off' }} mr-1"></i>
                                                {{ $item->status_label }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('mata-pelajaran.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('mata-pelajaran.destroy', $item->id) }}" method="POST" class="delete-form">
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

