@extends('layouts.appui')

@section('title')
    <title>Tujuan Pembelajaran (TP)</title>
@endsection

@section('content')
    @push('css')
        <style>
            .tp-unit-strip {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }

            .tp-unit-pills {
                display: inline-flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
                padding: 0.3rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
            }

            .tp-unit-pill {
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

            .tp-unit-pill:hover {
                color: #0f172a;
                background: rgba(255, 255, 255, 0.7);
            }

            .tp-unit-pill.is-active {
                background: #ffffff;
                color: #0f172a;
                border-color: #e2e8f0;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
            }

            .tp-unit-count {
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

            .tp-unit-pill.is-active .tp-unit-count {
                background: #fff7ed;
                color: #ea580c;
            }

            .tp-status-summary {
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

            .tp-dot {
                width: 6px;
                height: 6px;
                border-radius: 999px;
                display: inline-block;
                flex-shrink: 0;
            }

            .tp-filter-bar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 0.9rem 1.1rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            }

            .tp-filter-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 0.75rem;
                align-items: end;
            }

            @media (min-width: 640px) {
                .tp-filter-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1120px) {
                .tp-filter-grid {
                    grid-template-columns: 1.3fr 0.68fr 1.25fr 0.78fr 0.75fr 1.45fr;
                }
            }

            .tp-code-chip {
                display: inline-flex;
                align-items: center;
                padding: 0.2rem 0.55rem;
                border-radius: 7px;
                font-size: 0.72rem;
                font-weight: 700;
                letter-spacing: 0.02em;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #c2410c;
                font-variant-numeric: tabular-nums;
            }

            .tp-order-pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 1.75rem;
                height: 1.65rem;
                padding: 0 0.45rem;
                border-radius: 8px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                color: #334155;
                font-size: 0.74rem;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
            }

            .tp-status-pill-btn {
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

            .tp-status-pill-btn.is-active {
                background: #ecfdf5;
                border-color: #a7f3d0;
                color: #047857;
            }

            .tp-status-pill-btn:hover {
                transform: translateY(-1px);
            }

            .tp-modal-overlay {
                position: fixed;
                inset: 0;
                z-index: 1050;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                background: rgba(15, 23, 42, 0.52);
                backdrop-filter: blur(2px);
            }

            .tp-modal-overlay.is-open {
                display: flex;
            }

            .tp-modal-dialog {
                width: 100%;
                max-width: 520px;
                background: #ffffff;
                border-radius: 18px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.35);
                overflow: hidden;
            }
        </style>
    @endpush

    @php
        $tujuanPembelajaran = $tujuanPembelajaran ?? collect();
        $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
        $mataPelajaranOptions = $mataPelajaranOptions ?? collect();
        $unitOptions = $unitOptions ?? \App\Models\Admin\Pelajaran\MataPelajaran::UNIT_OPTIONS;
        $tingkatanOptions = $tingkatanOptions ?? [];
        $filters = $filters ?? [
            'tahun_ajaran_id' => \App\Services\Admin\TujuanPembelajaran\TujuanPembelajaranService::FILTER_ALL,
            'unit' => '',
            'mata_pelajaran_id' => '',
            'tingkatan_kelas' => '',
            'fase' => '',
            'status' => \App\Services\Admin\TujuanPembelajaran\TujuanPembelajaranService::FILTER_ALL,
            'search' => '',
        ];

        if (isset($summary)) {
            $stats = $summary;
        } else {
            $fallbackPerUnit = array_fill_keys($unitOptions, 0);
            $fallbackAktif = 0;

            foreach ($tujuanPembelajaran as $tpRow) {
                $mpRel = $tpRow->relationLoaded('mataPelajaran') ? $tpRow->mataPelajaran : null;
                $unitCode = strtoupper(trim((string) ($mpRel?->unit ?? '')));
                if ($unitCode !== '') {
                    $fallbackPerUnit[$unitCode] = ($fallbackPerUnit[$unitCode] ?? 0) + 1;
                }
                if ((bool) $tpRow->status) {
                    $fallbackAktif++;
                }
            }

            $stats = [
                'total' => $tujuanPembelajaran->count(),
                'aktif' => $fallbackAktif,
                'nonaktif' => max(0, $tujuanPembelajaran->count() - $fallbackAktif),
                'per_unit' => $fallbackPerUnit,
            ];
        }

        $selectedUnit = strtoupper(trim((string) ($filters['unit'] ?? '')));
        $hasActiveFilter = ($selectedUnit !== '')
            || (($filters['mata_pelajaran_id'] ?? '') !== '')
            || (($filters['tingkatan_kelas'] ?? '') !== '')
            || (($filters['fase'] ?? '') !== '')
            || (($filters['status'] ?? 'all') !== 'all')
            || (($filters['search'] ?? '') !== '');

        $baseQueryParams = [
            'tahun_ajaran_id' => $filters['tahun_ajaran_id'] ?? 'all',
            'mata_pelajaran_id' => $filters['mata_pelajaran_id'] ?? '',
            'tingkatan_kelas' => $filters['tingkatan_kelas'] ?? '',
            'fase' => $filters['fase'] ?? '',
            'status' => ($filters['status'] ?? 'all') !== 'all' ? $filters['status'] : '',
            'search' => $filters['search'] ?? '',
        ];

        $createParams = array_filter([
            'tahun_ajaran_id' => ($filters['tahun_ajaran_id'] ?? 'all') !== 'all' ? $filters['tahun_ajaran_id'] : null,
            'mata_pelajaran_id' => ($filters['mata_pelajaran_id'] ?? '') !== '' ? $filters['mata_pelajaran_id'] : null,
            'tingkatan_kelas' => ($filters['tingkatan_kelas'] ?? '') !== '' ? $filters['tingkatan_kelas'] : null,
        ]);
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-check" style="color: white;"></i>
                    </div>
                    <div>
                        Tujuan Pembelajaran (TP)
                        <div class="page-title-subheading">
                            Kelola daftar Tujuan Pembelajaran per tahun ajaran / semester, mata pelajaran, dan tingkatan kelas.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                    <a href="{{ route('tujuan-pembelajaran.export', request()->query()) }}"
                        class="btn shadow-sm font-weight-bold"
                        style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; border-radius: 10px;">
                        <i class="fa fa-file-excel text-success mr-1"></i> Export Excel
                    </a>
                    <button type="button" id="tpOpenImportModal"
                        class="btn shadow-sm font-weight-bold"
                        style="background: #ffffff; border: 1px solid #cbd5e1; color: #334155; border-radius: 10px;">
                        <i class="fa fa-upload text-primary mr-1"></i> Import Excel
                    </button>
                    <a href="{{ route('tujuan-pembelajaran.create', $createParams) }}"
                        class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px;">
                        <i class="fa fa-plus mr-1"></i> Tambah TP
                    </a>
                </div>
            </div>
        </div>

        @if (session()->has('import_errors') || (isset($errors) && ($errors->has('file') || $errors->has('default_tahun_ajaran_id'))))
            <div class="mb-3 p-4 rounded-xl"
                style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 0.82rem;">
                <div class="font-weight-bold mb-1">
                    <i class="fa fa-exclamation-triangle mr-1"></i> Detail Kendala Import Excel:
                </div>
                <ul class="mb-0 pl-4">
                    @if (isset($errors))
                        @foreach ($errors->all() as $formErr)
                            <li>{{ $formErr }}</li>
                        @endforeach
                    @endif
                    @foreach ((array) session('import_errors', []) as $rowErr)
                        <li>{{ $rowErr }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- RINGKASAN PER UNIT -->
        <div class="tp-unit-strip">
            <div class="tp-unit-pills">
                <a href="{{ route('tujuan-pembelajaran.index', array_filter($baseQueryParams)) }}"
                    class="tp-unit-pill {{ $selectedUnit === '' ? 'is-active' : '' }}">
                    <span>Semua Unit</span>
                    <span class="tp-unit-count">{{ $stats['total'] }}</span>
                </a>
                @foreach ($unitOptions as $unitOption)
                    <a href="{{ route('tujuan-pembelajaran.index', array_filter([...$baseQueryParams, 'unit' => $unitOption, 'mata_pelajaran_id' => ''])) }}"
                        class="tp-unit-pill {{ $selectedUnit === $unitOption ? 'is-active' : '' }}">
                        <span>{{ $unitOption }}</span>
                        <span class="tp-unit-count">{{ $stats['per_unit'][$unitOption] ?? 0 }}</span>
                    </a>
                @endforeach
            </div>

            <div class="tp-status-summary">
                <span class="d-inline-flex align-items-center" style="gap: 0.35rem;">
                    <span class="tp-dot" style="background: #10b981;"></span>
                    {{ $stats['aktif'] }} Aktif
                </span>
                @if ($stats['nonaktif'] > 0)
                    <span style="color: #cbd5e1;">|</span>
                    <span class="d-inline-flex align-items-center" style="gap: 0.35rem;">
                        <span class="tp-dot" style="background: #94a3b8;"></span>
                        {{ $stats['nonaktif'] }} Nonaktif
                    </span>
                @endif
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="tp-filter-bar">
            <form method="GET" action="{{ route('tujuan-pembelajaran.index') }}" id="tpFilterForm" class="tp-filter-grid">
                <div>
                    <label for="filter_tahun_ajaran" class="filter-label">Tahun Ajaran / Semester</label>
                    <select name="tahun_ajaran_id" id="filter_tahun_ajaran" class="filter-control js-tp-autosubmit">
                        <option value="all" @selected(($filters['tahun_ajaran_id'] ?? 'all') === 'all')>
                            Semua Semester
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
                    <select name="unit" id="filter_unit" class="filter-control js-tp-unit-change">
                        <option value="">Semua</option>
                        @foreach ($unitOptions as $unitOption)
                            <option value="{{ $unitOption }}" @selected($selectedUnit === $unitOption)>
                                {{ $unitOption }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_mapel" class="filter-label">Mata Pelajaran</label>
                    <select name="mata_pelajaran_id" id="filter_mapel" class="filter-control js-tp-autosubmit">
                        <option value="">Semua Mata Pelajaran</option>
                        @foreach ($mataPelajaranOptions as $mapelOpt)
                            <option value="{{ $mapelOpt->id }}"
                                @selected((string) ($filters['mata_pelajaran_id'] ?? '') === (string) $mapelOpt->id)>
                                [{{ strtoupper((string) $mapelOpt->unit) }}] {{ $mapelOpt->singkatan }} - {{ $mapelOpt->nama_mata_pelajaran }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_tingkatan" class="filter-label">Tingkatan</label>
                    <select name="tingkatan_kelas" id="filter_tingkatan" class="filter-control js-tp-autosubmit">
                        <option value="">Semua</option>
                        @foreach ($tingkatanOptions as $tingkatOpt)
                            <option value="{{ $tingkatOpt }}"
                                @selected((string) ($filters['tingkatan_kelas'] ?? '') === (string) $tingkatOpt)>
                                {{ ctype_digit((string) $tingkatOpt) ? 'Kelas '.$tingkatOpt : $tingkatOpt }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="filter_status" class="filter-label">Status</label>
                    <select name="status" id="filter_status" class="filter-control js-tp-autosubmit">
                        <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Semua</option>
                        <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                        <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                    </select>
                </div>

                <div>
                    <label for="filter_search" class="filter-label">Cari Kode / Elemen / Materi / TP</label>
                    <div class="filter-search-group">
                        <input type="text" name="search" id="filter_search" class="filter-control"
                            placeholder="Kode TP, elemen, lingkup materi..." value="{{ $filters['search'] ?? '' }}">
                        <button type="submit" class="filter-btn filter-btn-primary filter-btn-icon" title="Terapkan Filter">
                            <i class="fa fa-search"></i>
                        </button>
                        @if ($hasActiveFilter)
                            <a href="{{ route('tujuan-pembelajaran.index', ['tahun_ajaran_id' => $filters['tahun_ajaran_id'] ?? 'all']) }}"
                                class="filter-btn filter-btn-secondary filter-btn-icon" title="Reset Filter">
                                <i class="fa fa-undo"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>

        <!-- TABEL DAFTAR TP -->
        <div class="main-card card mb-3 border-0 shadow-sm" style="border-radius: 18px !important;">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center"
                style="border-bottom: 1px solid #f1f5f9;">
                <span style="font-size: 0.92rem; font-weight: 700; color: #0f172a;">
                    Daftar Tujuan Pembelajaran
                </span>
                <span style="font-size: 0.76rem; color: #64748b; font-weight: 600;">
                    {{ $tujuanPembelajaran->count() }} butir TP
                </span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover align-middle" id="myTable3">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th style="width: 10%;">Kode TP</th>
                                <th style="width: 16%;">Mata Pelajaran</th>
                                <th class="text-center" style="width: 11%;">Tingkat &amp; Fase</th>
                                <th style="width: 18%;">Elemen &amp; Lingkup Materi</th>
                                <th>Tujuan Pembelajaran</th>
                                <th class="text-center" style="width: 7%;">Urutan</th>
                                <th class="text-center" style="width: 10%;">Status</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tujuanPembelajaran as $item)
                                @php
                                    $mapel = $item->relationLoaded('mataPelajaran') ? $item->mataPelajaran : null;
                                    $tahunAjaran = $item->relationLoaded('tahunAjaran') ? $item->tahunAjaran : null;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <span class="tp-code-chip">{{ $item->kode_tp }}</span>
                                    </td>
                                    <td>
                                        @if ($mapel)
                                            <div class="font-weight-bold text-slate-800" style="font-size: 0.83rem;">
                                                {{ $mapel->nama_mata_pelajaran }}
                                            </div>
                                            <div class="small text-muted">
                                                {{ strtoupper((string) $mapel->unit) }}
                                                @if ($tahunAjaran)
                                                    &middot; {{ $tahunAjaran->full_label }}
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="font-weight-bold text-slate-800" style="font-size: 0.82rem;">
                                            {{ $item->tingkatan_label }}
                                        </div>
                                        @if (filled($item->fase))
                                            <span class="badge {{ $item->fase_badge_class }} mt-1" style="font-size: 0.68rem;">
                                                {{ $item->fase_label }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if (filled($item->elemen))
                                            <div class="font-weight-bold text-slate-800" style="font-size: 0.8rem;">
                                                {{ $item->elemen }}
                                            </div>
                                        @endif
                                        @if (filled($item->lingkup_materi))
                                            <div class="small text-muted {{ filled($item->elemen) ? 'mt-0.5' : '' }}">
                                                {{ $item->lingkup_materi }}
                                            </div>
                                        @endif
                                        @if (! filled($item->elemen) && ! filled($item->lingkup_materi))
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-slate-700" style="font-size: 0.84rem; line-height: 1.45;">
                                            {{ $item->tujuan_pembelajaran }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="tp-order-pill">{{ $item->urutan }}</span>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('tujuan-pembelajaran.toggle-status', $item->id) }}" method="POST"
                                            class="d-inline">
                                            @csrf
                                            <button type="submit"
                                                class="tp-status-pill-btn {{ $item->status ? 'is-active' : '' }}"
                                                data-toggle="tooltip" data-placement="top"
                                                title="{{ $item->status ? 'Klik untuk nonaktifkan' : 'Klik untuk aktifkan' }}">
                                                <span class="tp-dot"
                                                    style="background: {{ $item->status ? '#10b981' : '#94a3b8' }};"></span>
                                                <span>{{ $item->status_label }}</span>
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('tujuan-pembelajaran.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('tujuan-pembelajaran.destroy', $item->id) }}" method="POST" class="delete-form">
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
        <!-- MODAL IMPORT EXCEL -->
        <div id="tpImportModal" class="tp-modal-overlay" role="dialog" aria-modal="true" aria-labelledby="tpImportTitle">
            <div class="tp-modal-dialog">
                <div class="d-flex align-items-center justify-content-between px-4 py-3"
                    style="border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <h3 id="tpImportTitle" class="m-0 font-weight-bold" style="font-size: 0.98rem; color: #0f172a;">
                            Import Tujuan Pembelajaran (Excel)
                        </h3>
                        <div class="small text-muted">
                            Mendukung format .xlsx, .xls, dan .csv (maksimal 5 MB)
                        </div>
                    </div>
                    <button type="button" id="tpCloseImportModal" class="btn btn-sm"
                        style="color: #64748b; background: transparent; border: none; font-size: 1.1rem;"
                        aria-label="Tutup">
                        &times;
                    </button>
                </div>

                <form action="{{ route('tujuan-pembelajaran.import') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="p-4">
                        <div class="p-3 rounded-xl mb-3 d-flex align-items-center justify-content-between"
                            style="background: #f8fafc; border: 1px solid #e2e8f0; gap: 0.75rem;">
                            <div style="font-size: 0.78rem; color: #475569; line-height: 1.4;">
                                Gunakan template resmi agar urutan kolom serta daftar referensi Tahun Ajaran &amp; Mapel sesuai.
                            </div>
                            <a href="{{ route('tujuan-pembelajaran.template') }}" class="btn btn-sm font-weight-bold shrink-0"
                                style="background: #ffffff; border: 1px solid #cbd5e1; color: #0f172a; border-radius: 8px; white-space: nowrap;">
                                <i class="fa fa-download text-warning mr-1"></i> Template
                            </a>
                        </div>

                        <div class="form-group mb-3">
                            <label for="import_default_ta" class="filter-label">
                                Tahun Ajaran / Semester Default <span class="text-muted font-weight-normal">(opsional)</span>
                            </label>
                            <select name="default_tahun_ajaran_id" id="import_default_ta" class="filter-control">
                                <option value="">Ikuti kolom file Excel</option>
                                @foreach ($tahunAjaranOptions as $ta)
                                    <option value="{{ $ta->id }}"
                                        @selected((string) ($filters['tahun_ajaran_id'] ?? '') === (string) $ta->id)>
                                        {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="small text-muted mt-1" style="font-size: 0.72rem;">
                                Dipakai otomatis bila kolom Tahun Ajaran / Semester pada file dikosongkan.
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label for="import_tp_file" class="filter-label">
                                Pilih File Excel / CSV <span class="text-danger">*</span>
                            </label>
                            <input type="file" name="file" id="import_tp_file" accept=".xlsx,.xls,.csv" required
                                class="filter-control" style="padding-top: 0.35rem;">
                        </div>
                    </div>

                    <div class="px-4 py-3 d-flex justify-content-end" style="background: #f8fafc; border-top: 1px solid #f1f5f9; gap: 0.5rem;">
                        <button type="button" id="tpCancelImportModal" class="filter-btn filter-btn-secondary">
                            Batal
                        </button>
                        <button type="submit" class="filter-btn filter-btn-primary">
                            <i class="fa fa-upload"></i> Import Data
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const importModal = document.getElementById('tpImportModal');
            const openImportBtn = document.getElementById('tpOpenImportModal');
            const closeImportBtn = document.getElementById('tpCloseImportModal');
            const cancelImportBtn = document.getElementById('tpCancelImportModal');

            const toggleImportModal = function (open) {
                if (!importModal) {
                    return;
                }
                importModal.classList.toggle('is-open', open);
            };

            if (openImportBtn) {
                openImportBtn.addEventListener('click', function () {
                    toggleImportModal(true);
                });
            }

            [closeImportBtn, cancelImportBtn].forEach(function (btn) {
                if (btn) {
                    btn.addEventListener('click', function () {
                        toggleImportModal(false);
                    });
                }
            });

            if (importModal) {
                importModal.addEventListener('click', function (e) {
                    if (e.target === importModal) {
                        toggleImportModal(false);
                    }
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && importModal.classList.contains('is-open')) {
                        toggleImportModal(false);
                    }
                });
            }

            const filterForm = document.getElementById('tpFilterForm');
            if (!filterForm) {
                return;
            }

            const mapelSelect = document.getElementById('filter_mapel');
            const tingkatanSelect = document.getElementById('filter_tingkatan');

            filterForm.querySelectorAll('.js-tp-unit-change').forEach(function (selectEl) {
                selectEl.addEventListener('change', function () {
                    if (mapelSelect) {
                        mapelSelect.value = '';
                    }
                    if (tingkatanSelect) {
                        tingkatanSelect.value = '';
                    }
                    filterForm.submit();
                });
            });

            filterForm.querySelectorAll('.js-tp-autosubmit').forEach(function (selectEl) {
                selectEl.addEventListener('change', function () {
                    filterForm.submit();
                });
            });
        });
    </script>
@endpush

