@extends('layouts.appui')

@section('title')
    <title>KKM &amp; Rentang Predikat (SD)</title>
@endsection

@section('content')
    @push('css')
        <style>
            .kkm-kpi-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.75rem;
                margin-bottom: 1rem;
            }

            @media (min-width: 992px) {
                .kkm-kpi-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .kkm-kpi-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 0.85rem 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .kkm-kpi-label {
                font-size: 0.71rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
            }

            .kkm-kpi-dot {
                width: 7px;
                height: 7px;
                border-radius: 999px;
                display: inline-block;
            }

            .kkm-kpi-value {
                font-size: 1.3rem;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.2;
                margin-top: 0.18rem;
                font-variant-numeric: tabular-nums;
            }

            .kkm-kpi-sub {
                font-size: 0.71rem;
                color: #64748b;
                margin-top: 0.12rem;
            }

            .kkm-kpi-icon {
                width: 38px;
                height: 38px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.92rem;
                flex-shrink: 0;
            }

            .kkm-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                font-size: 0.81rem;
                font-variant-numeric: tabular-nums;
            }

            .kkm-table thead th {
                background: #f8fafc;
                color: #475569;
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                padding: 0.75rem 0.85rem;
                border-bottom: 1px solid #e2e8f0;
                white-space: nowrap;
            }

            .kkm-table tbody td {
                padding: 0.78rem 0.85rem;
                border-bottom: 1px solid #f1f5f9;
                vertical-align: middle;
                background: #ffffff;
            }

            .kkm-table tbody tr:hover td {
                background: #f8fafc;
            }

            .kkm-score-pill {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 42px;
                padding: 0.2rem 0.55rem;
                border-radius: 8px;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #c2410c;
                font-weight: 800;
                font-size: 0.85rem;
            }

            .kkm-bobot-sub {
                margin-top: 0.25rem;
                font-size: 0.69rem;
                font-weight: 600;
                line-height: 1.3;
                white-space: nowrap;
            }

            .kkm-pred-list {
                display: flex;
                flex-wrap: wrap;
                gap: 0.35rem;
            }

            .kkm-pred-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                padding: 0.2rem 0.55rem;
                border-radius: 999px;
                font-size: 0.71rem;
                font-weight: 600;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #334155;
                font-variant-numeric: tabular-nums;
            }

            .kkm-pred-chip.is-fail {
                background: #fef2f2;
                border-color: #fecaca;
                color: #b91c1c;
            }
        </style>
    @endpush

    @php
        $kkmList = $kkmList ?? collect();
        $filters = $filters ?? ['tahun_ajaran_id' => '', 'mata_pelajaran_id' => '', 'tingkatan_kelas' => ''];
        $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
        $mataPelajaranOptions = $mataPelajaranOptions ?? collect();
        $tingkatanOptions = $tingkatanOptions ?? \App\Models\Admin\Penilaian\Kkm::SD_TINGKATAN_OPTIONS;

        $hasActiveFilter = ($filters['tahun_ajaran_id'] ?? '') !== ''
            || ($filters['mata_pelajaran_id'] ?? '') !== ''
            || ($filters['tingkatan_kelas'] ?? '') !== '';
        $uniqueMapelCount = $kkmList->pluck('akademik_pelajaran_mata_pelajaran_id')->filter()->unique()->count();
        $avgKkm = $kkmList->isNotEmpty() ? round((float) $kkmList->avg('nilai_kkm'), 1) : null;
        $mixedWeightCount = $kkmList->filter(
            static fn ($k): bool => (int) ($k->bobot_sumatif ?? 0) > 0 || (int) ($k->bobot_ujian ?? 0) > 0
        )->count();
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-ribbon" style="color: white;"></i>
                    </div>
                    <div>
                        KKM &amp; Rentang Predikat (SD)
                        <div class="page-title-subheading">
                            Atur nilai ketuntasan minimal, komposisi bobot rapor, dan rentang predikat per jenjang kelas SD.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions flex-wrap">
                    <a href="{{ route('penilaian-tp.index') }}" class="btn btn-light shadow-sm"
                        style="border-radius: 10px; font-weight: 600;">
                        <i class="fa fa-th mr-1"></i> Input Nilai TP
                    </a>
                    <a href="{{ route('penilaian-kkm.create', array_filter($filters)) }}"
                        class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px;">
                        <i class="fa fa-plus mr-1"></i> Tambah KKM
                    </a>
                </div>
            </div>
        </div>

        <!-- RINGKASAN KPI -->
        <div class="kkm-kpi-grid">
            <div class="kkm-kpi-card">
                <div>
                    <div class="kkm-kpi-label">
                        <span class="kkm-kpi-dot" style="background: #ea580c;"></span> Total Aturan KKM
                    </div>
                    <div class="kkm-kpi-value">{{ $kkmList->count() }}</div>
                    <div class="kkm-kpi-sub">Konfigurasi SD tampil</div>
                </div>
                <span class="kkm-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                    <i class="fa fa-sliders-h"></i>
                </span>
            </div>

            <div class="kkm-kpi-card">
                <div>
                    <div class="kkm-kpi-label">
                        <span class="kkm-kpi-dot" style="background: #3b82f6;"></span> Mata Pelajaran
                    </div>
                    <div class="kkm-kpi-value">{{ $uniqueMapelCount }}</div>
                    <div class="kkm-kpi-sub">Mapel SD terkonfigurasi</div>
                </div>
                <span class="kkm-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                    <i class="fa fa-book-open"></i>
                </span>
            </div>

            <div class="kkm-kpi-card">
                <div>
                    <div class="kkm-kpi-label">
                        <span class="kkm-kpi-dot" style="background: #10b981;"></span> Rata-rata KKM
                    </div>
                    <div class="kkm-kpi-value" style="color: #047857;">
                        {{ $avgKkm !== null ? rtrim(rtrim(number_format($avgKkm, 1, '.', ''), '0'), '.') : '-' }}
                    </div>
                    <div class="kkm-kpi-sub">Default 75 bila belum diatur</div>
                </div>
                <span class="kkm-kpi-icon" style="background: #ecfdf5; color: #059669;">
                    <i class="fa fa-check-double"></i>
                </span>
            </div>

            <div class="kkm-kpi-card">
                <div>
                    <div class="kkm-kpi-label">
                        <span class="kkm-kpi-dot" style="background: #6366f1;"></span> Bobot Gabungan
                    </div>
                    <div class="kkm-kpi-value" style="color: #4338ca;">{{ $mixedWeightCount }}</div>
                    <div class="kkm-kpi-sub">Memakai Sumatif / Ujian</div>
                </div>
                <span class="kkm-kpi-icon" style="background: #eef2ff; color: #4f46e5;">
                    <i class="fa fa-percentage"></i>
                </span>
            </div>
        </div>

        <!-- FILTER -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-filter"></i></span>
                    <span>Filter Konfigurasi KKM &amp; Predikat</span>
                    @if ($hasActiveFilter)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="filter-count-pill">
                        Menampilkan <strong>{{ $kkmList->count() }}</strong> KKM
                    </span>
                    @if ($hasActiveFilter)
                        <a href="{{ route('penilaian-kkm.index') }}"
                            class="filter-btn filter-btn-secondary"
                            style="height: 1.9rem; min-height: 1.9rem; padding: 0 0.7rem; font-size: 0.75rem;">
                            <i class="fa fa-undo"></i> Reset
                        </a>
                    @endif
                </div>
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('penilaian-kkm.index') }}"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <div class="lg:col-span-4">
                        <label for="filter_ta" class="filter-label">Tahun Ajaran / Semester</label>
                        <select name="tahun_ajaran_id" id="filter_ta" class="filter-control">
                            <option value="all" @selected($filters['tahun_ajaran_id'] === '')>Semua Tahun Ajaran</option>
                            @foreach ($tahunAjaranOptions as $ta)
                                <option value="{{ $ta->id }}" @selected((string) $filters['tahun_ajaran_id'] === (string) $ta->id)>
                                    {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-4">
                        <label for="filter_mp" class="filter-label">Mata Pelajaran (SD)</label>
                        <select name="mata_pelajaran_id" id="filter_mp" class="filter-control">
                            <option value="">Semua Mata Pelajaran SD</option>
                            @foreach ($mataPelajaranOptions as $mp)
                                <option value="{{ $mp->id }}" @selected((string) $filters['mata_pelajaran_id'] === (string) $mp->id)>
                                    [{{ $mp->singkatan }}] {{ $mp->nama_mata_pelajaran }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-4">
                        <label for="filter_tingkat" class="filter-label">Tingkatan Kelas</label>
                        <div class="filter-search-group">
                            <select name="tingkatan_kelas" id="filter_tingkat" class="filter-control">
                                <option value="">Semua Kelas SD</option>
                                @foreach ($tingkatanOptions as $tingkat)
                                    <option value="{{ $tingkat }}" @selected($filters['tingkatan_kelas'] === $tingkat)>
                                        Kelas {{ $tingkat }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="filter-btn filter-btn-primary filter-btn-icon" title="Terapkan Filter">
                                <i class="fa fa-search"></i>
                            </button>
                            <a href="{{ route('penilaian-kkm.index') }}" class="filter-btn filter-btn-secondary filter-btn-icon"
                                title="Reset Filter">
                                <i class="fa fa-sync-alt"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABEL KKM -->
        <div class="main-card card mb-3 shadow-sm border-0" style="border-radius: 16px;">
            <div class="card-body">
                <div class="table-responsive">
                    <table style="width: 100%;" id="myTable3" data-empty-table="Belum ada pengaturan KKM SD."
                        class="mb-0 table table-hover align-middle kkm-table">
                        <thead>
                            <tr>
                                <th class="text-center" style="width: 52px;">No</th>
                                <th>Tahun Ajaran</th>
                                <th>Mata Pelajaran</th>
                                <th class="text-center" style="width: 105px;">Tingkat</th>
                                <th class="text-center" style="width: 155px;">KKM &amp; Bobot</th>
                                <th>Rentang Predikat</th>
                                <th class="text-center" style="width: 72px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kkmList as $index => $item)
                                @php
                                    $bobotParts = array_filter([
                                        ($item->bobot_tp ?? 100) > 0 ? ($item->bobot_tp ?? 100).'% TP' : null,
                                        ($item->bobot_sumatif ?? 0) > 0 ? $item->bobot_sumatif.'% Sumatif' : null,
                                        ($item->bobot_ujian ?? 0) > 0 ? $item->bobot_ujian.'% Ujian' : null,
                                    ]);
                                    $hasNonTpBobot = ($item->bobot_sumatif ?? 0) > 0 || ($item->bobot_ujian ?? 0) > 0;
                                @endphp
                                <tr>
                                    <td class="text-center text-muted">{{ $index + 1 }}</td>
                                    <td>
                                        <span class="text-slate-700 font-weight-semibold">
                                            {{ $item->tahunAjaran?->full_label ?? '-' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-dark">{{ $item->mataPelajaran?->nama_mata_pelajaran ?? '-' }}</div>
                                        @if ($item->mataPelajaran?->singkatan)
                                            <span class="badge badge-light mt-1"
                                                style="font-size: 0.69rem; color: #475569; border: 1px solid #e2e8f0;">
                                                {{ $item->mataPelajaran->singkatan }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge px-2 py-1"
                                            style="background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; font-size: 0.74rem; font-weight: 700;">
                                            Kelas {{ $item->tingkatan_kelas }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div>
                                            <span class="kkm-score-pill">
                                                {{ rtrim(rtrim(number_format((float) $item->nilai_kkm, 2, '.', ''), '0'), '.') }}
                                            </span>
                                        </div>
                                        <div class="kkm-bobot-sub" style="color: {{ $hasNonTpBobot ? '#4338ca' : '#64748b' }};">
                                            {{ $bobotParts !== [] ? implode(' • ', $bobotParts) : '100% TP' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="kkm-pred-list">
                                            @foreach ($item->predikats as $pred)
                                                @php
                                                    $minLabel = rtrim(rtrim(number_format((float) $pred->nilai_min, 2, '.', ''), '0'), '.');
                                                    $maxLabel = rtrim(rtrim(number_format((float) $pred->nilai_max, 2, '.', ''), '0'), '.');
                                                @endphp
                                                <span class="kkm-pred-chip {{ $pred->is_tuntas ? '' : 'is-fail' }}"
                                                    title="{{ $pred->keterangan }}">
                                                    <strong>{{ $pred->predikat }}</strong>: {{ $minLabel }}–{{ $maxLabel }}
                                                </span>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('penilaian-kkm.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Ubah KKM &amp; Rentang</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form method="POST" action="{{ route('penilaian-kkm.destroy', $item->id) }}" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="action-dropdown-item action-dropdown-item--danger delete-button" role="menuitem">
                                                    <i class="fa fa-trash action-item-icon"></i>
                                                    <span>Hapus KKM</span>
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
