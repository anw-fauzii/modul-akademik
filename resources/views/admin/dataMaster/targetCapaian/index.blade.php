@extends('layouts.appui')

@section('title')
    <title>Target Capaian T2Q - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('admin.dataMaster.partials._t2q_arab_styles')

    @push('css')
        <style>
            .tc-ta-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.35rem 0.75rem;
                border-radius: 9999px;
                font-size: 0.76rem;
                font-weight: 700;
                background: #ecfdf5;
                color: #047857;
                border: 1px solid #a7f3d0;
            }

            .tc-table thead th {
                background: #f8fafc;
                color: #475569;
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                border-top: none;
                border-bottom: 1px solid #e2e8f0;
                padding: 0.8rem 0.9rem;
            }

            .tc-table tbody td {
                padding: 0.85rem 0.9rem;
                border-top: 1px solid #f1f5f9;
                vertical-align: middle;
            }

            .tc-table tbody tr:hover {
                background-color: #fffbf7;
            }

            .tc-tingkat-pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 78px;
                padding: 0.3rem 0.7rem;
                border-radius: 9999px;
                font-size: 0.75rem;
                font-weight: 700;
            }

            .tc-tingkat-pill--tk {
                background: #fef3c7;
                color: #92400e;
                border: 1px solid #fde68a;
            }

            .tc-tingkat-pill--sd {
                background: #ecfdf5;
                color: #047857;
                border: 1px solid #a7f3d0;
            }

            .tc-tingkat-pill--smp {
                background: #eff6ff;
                color: #1d4ed8;
                border: 1px solid #bfdbfe;
            }

            .tc-tingkat-pill--default {
                background: #f1f5f9;
                color: #475569;
                border: 1px solid #e2e8f0;
            }

            .tc-item-box {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.45rem 0.75rem;
                border-radius: 10px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
            }

            .tc-item-meta {
                display: inline-flex;
                align-items: center;
                padding: 0.12rem 0.48rem;
                border-radius: 6px;
                font-size: 0.69rem;
                font-weight: 600;
                background: #ffffff;
                color: #475569;
                border: 1px solid #cbd5e1;
            }

            .tc-empty-cell {
                color: #94a3b8;
                font-size: 0.8rem;
                font-style: italic;
            }
        </style>
    @endpush

    @php
        $targetCapaian = $targetCapaian ?? collect();
        $activeTahunAjaran = $activeTahunAjaran ?? null;
        $tingkatOptions = $tingkatOptions ?? \App\Models\Admin\DataMaster\AnggotaT2q::DEFAULT_TINGKAT_OPTIONS;
        $filters = $filters ?? [
            'tingkat' => '',
            'search' => '',
        ];

        $selectedTingkat = \App\Models\Admin\DataMaster\TargetCapaian::normalizeTingkat($filters['tingkat'] ?? '');
        $hasActiveFilter = $selectedTingkat !== '' || ($filters['search'] ?? '') !== '';
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-target" style="color: white;"></i>
                    </div>
                    <div>
                        Target Capaian T2Q
                        <div class="page-title-subheading">
                            Kelola target capaian jilid dan hafalan surah Al-Qur'an per tingkat pada Tahun Ajaran aktif.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('target-capaian.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH TARGET CAPAIAN
                    </a>
                </div>
            </div>
        </div>

        @if (! $activeTahunAjaran)
            <div class="alert alert-warning border shadow-sm mb-3" style="border-radius: 12px;">
                <i class="fa fa-exclamation-triangle mr-1"></i>
                Belum ada <strong>Tahun Ajaran aktif</strong>. Aktifkan satu Tahun Ajaran terlebih dahulu pada menu Data Master &rsaquo; Tahun Ajaran.
            </div>
        @endif

        <!-- FILTER -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter &amp; Pencarian Target Capaian</span>
                    @if ($hasActiveFilter)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                    @if ($activeTahunAjaran)
                        <span class="tc-ta-badge">
                            <i class="fa fa-calendar-check"></i>
                            {{ $activeTahunAjaran->full_label }}
                        </span>
                    @endif
                    <span class="filter-count-pill">
                        <i class="fa fa-bullseye text-primary"></i>
                        <strong>{{ $targetCapaian->count() }}</strong> Target
                    </span>
                    @if ($hasActiveFilter)
                        <a href="{{ route('target-capaian.index') }}" class="filter-btn filter-btn-secondary"
                            style="height: 1.9rem; min-height: 1.9rem; padding: 0 0.7rem; font-size: 0.75rem;">
                            <i class="fa fa-undo"></i> Reset
                        </a>
                    @endif
                </div>
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('target-capaian.index') }}"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <div class="lg:col-span-4">
                        <label for="filter_tingkat" class="filter-label">Tingkat</label>
                        <select name="tingkat" id="filter_tingkat" class="filter-control">
                            <option value="">Semua Tingkat</option>
                            @foreach ($tingkatOptions as $opsiTingkat)
                                <option value="{{ $opsiTingkat }}" @selected($selectedTingkat === (string) $opsiTingkat)>
                                    Tingkat {{ $opsiTingkat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-8">
                        <label for="filter_search" class="filter-label">Cari Jilid / Surah / Tingkat</label>
                        <div class="filter-search-group">
                            <input type="text" name="search" id="filter_search" class="filter-control"
                                placeholder="Contoh: Jilid 2, An-Naba, 1..." value="{{ $filters['search'] ?? '' }}">
                            <button type="submit" class="filter-btn filter-btn-primary filter-btn-icon" title="Terapkan Filter">
                                <i class="fa fa-search"></i>
                            </button>
                            <a href="{{ route('target-capaian.index') }}" class="filter-btn filter-btn-secondary filter-btn-icon"
                                title="Reset Filter">
                                <i class="fa fa-sync-alt"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="main-card card mb-3 shadow-sm border-0" style="border-radius: 14px;">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover align-middle tc-table" id="myTable3"
                        data-search-placeholder="Cari tingkat, jilid, atau surah..."
                        data-empty-table="Belum ada data target capaian T2Q pada Tahun Ajaran aktif.">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 52px;">No</th>
                                <th class="text-center" style="width: 120px;">Tingkat</th>
                                <th style="width: 34%;">Target Jilid</th>
                                <th>Target Surah Al-Qur'an</th>
                                <th style="width: 72px; text-align: center;" data-orderable="false" data-searchable="false">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($targetCapaian as $item)
                                @php
                                    $jilid = $item->relationLoaded('daftarJilid') ? $item->daftarJilid : null;
                                    $surah = $item->relationLoaded('surah') ? $item->surah : null;
                                @endphp
                                <tr>
                                    <td class="text-center text-muted font-weight-semibold">{{ $loop->iteration }}</td>
                                    <td class="text-center">
                                        <span class="tc-tingkat-pill {{ $item->tingkat_pill_class }}">
                                            Tingkat {{ $item->tingkat }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($jilid)
                                            <div class="tc-item-box">
                                                <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                                                    <span class="font-weight-bold text-slate-800" style="font-size: 0.85rem;">
                                                        {{ $jilid->jilid_latin }}
                                                    </span>
                                                    <span class="tc-item-meta">
                                                        {{ ucfirst((string) $jilid->jenis) }}
                                                    </span>
                                                </div>
                                                @if (filled($jilid->jilid_arab))
                                                    <span class="t2q-arab-text" lang="ar">{{ $jilid->jilid_arab }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="tc-empty-cell">&mdash; Tidak ditargetkan</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($surah)
                                            <div class="tc-item-box">
                                                <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                                                    <span class="font-weight-bold text-slate-800" style="font-size: 0.85rem;">
                                                        {{ $surah->nama_surah }}
                                                    </span>
                                                    <span class="tc-item-meta">
                                                        {{ $surah->jumlah_ayat }} Ayat
                                                    </span>
                                                </div>
                                                @if (filled($surah->nama_surah_arab))
                                                    <span class="t2q-arab-text" lang="ar">{{ $surah->nama_surah_arab }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="tc-empty-cell">&mdash; Tidak ditargetkan</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('target-capaian.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('target-capaian.destroy', $item->id) }}" method="POST" class="delete-form">
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
