@extends('layouts.appui')

@section('title')
    <title>Jam Pelajaran</title>
@endsection

@section('content')
    @push('css')
        <style>
            .jp-summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(175px, 1fr));
                gap: 0.85rem;
            }

            .jp-stat-card {
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

            .jp-stat-card:hover {
                transform: translateY(-2px);
                border-color: #f97316;
                box-shadow: 0 10px 20px -6px rgba(234, 88, 12, 0.16);
            }

            .jp-stat-card.is-active-filter {
                border-color: #ea580c;
                background: #fff7ed;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.14);
            }

            .jp-stat-icon {
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

            .jp-stat-value {
                font-size: 1.4rem;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.15;
                margin-top: 4px;
            }

            .jp-stat-footer {
                margin-top: 8px;
                padding-top: 8px;
                border-top: 1px solid #f1f5f9;
                font-size: 0.74rem;
                color: #64748b;
            }

            .jp-time-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.25rem 0.6rem;
                border-radius: 8px;
                font-size: 0.8rem;
                font-weight: 700;
                font-variant-numeric: tabular-nums;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                color: #0f172a;
            }
        </style>
    @endpush

    @php
        $jamPelajaran = $jamPelajaran ?? collect();
        $unitOptions = $unitOptions ?? \App\Models\Admin\Pelajaran\JamPelajaran::UNIT_OPTIONS;
        $filters = $filters ?? [
            'unit' => '',
            'search' => '',
        ];

        if (isset($summary)) {
            $stats = $summary;
        } else {
            $fallbackPerUnit = [];

            foreach ($jamPelajaran as $jpRow) {
                $unitKey = strtoupper(trim((string) ($jpRow->unit ?? '')));
                if ($unitKey === '') {
                    continue;
                }

                $mulai = \App\Models\Admin\Pelajaran\JamPelajaran::formatTimeShort($jpRow->jam_mulai ?? null);
                $selesai = \App\Models\Admin\Pelajaran\JamPelajaran::formatTimeShort($jpRow->jam_selesai ?? null);

                if (! isset($fallbackPerUnit[$unitKey])) {
                    $fallbackPerUnit[$unitKey] = [
                        'total' => 0,
                        'jam_pertama' => null,
                        'jam_terakhir' => null,
                    ];
                }

                $fallbackPerUnit[$unitKey]['total']++;
                if ($mulai !== '-' && ($fallbackPerUnit[$unitKey]['jam_pertama'] === null || $mulai < $fallbackPerUnit[$unitKey]['jam_pertama'])) {
                    $fallbackPerUnit[$unitKey]['jam_pertama'] = $mulai;
                }
                if ($selesai !== '-' && ($fallbackPerUnit[$unitKey]['jam_terakhir'] === null || $selesai > $fallbackPerUnit[$unitKey]['jam_terakhir'])) {
                    $fallbackPerUnit[$unitKey]['jam_terakhir'] = $selesai;
                }
            }

            uksort($fallbackPerUnit, static fn (string $a, string $b): int => \App\Models\Admin\Pelajaran\JamPelajaran::compareUnitOrder($a, $b));

            $stats = [
                'total' => $jamPelajaran->count(),
                'per_unit' => $fallbackPerUnit,
            ];
        }

        $selectedUnit = strtoupper(trim((string) ($filters['unit'] ?? '')));
        $hasActiveFilter = ($selectedUnit !== '') || (($filters['search'] ?? '') !== '');
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-clock" style="color: white;"></i>
                    </div>
                    <div>
                        Jam Pelajaran
                        <div class="page-title-subheading">
                            Kelola daftar jam ke, unit pendidikan, jam mulai, jam selesai, dan keterangan jadwal pelajaran.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('jam-pelajaran.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH JAM PELAJARAN
                    </a>
                </div>
            </div>
        </div>

        <!-- RINGKASAN PER UNIT -->
        <div class="jp-summary-grid mb-3">
            <a href="{{ route('jam-pelajaran.index', array_filter(['search' => $filters['search'] ?? ''])) }}"
                class="jp-stat-card {{ $selectedUnit === '' ? 'is-active-filter' : '' }}">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="small font-weight-bold text-uppercase text-slate-500">Semua Unit</div>
                        <div class="jp-stat-value">{{ $stats['total'] }}</div>
                    </div>
                    <span class="jp-stat-icon" style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);">
                        <i class="fa fa-clock"></i>
                    </span>
                </div>
                <div class="jp-stat-footer">
                    Total slot pada <strong>{{ count($unitOptions) }} unit</strong> pendidikan
                </div>
            </a>

            @foreach ($unitOptions as $unitName)
                @php
                    $unitData = $stats['per_unit'][$unitName] ?? [
                        'total' => 0,
                        'jam_pertama' => null,
                        'jam_terakhir' => null,
                    ];
                    $unitGradient = match ($unitName) {
                        'PG' => 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
                        'TK' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                        'SD' => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                        default => 'linear-gradient(135deg, #64748b 0%, #475569 100%)',
                    };
                    $hasRange = filled($unitData['jam_pertama'] ?? null) && filled($unitData['jam_terakhir'] ?? null);
                @endphp
                <a href="{{ route('jam-pelajaran.index', array_filter(['unit' => $unitName, 'search' => $filters['search'] ?? ''])) }}"
                    class="jp-stat-card {{ $selectedUnit === $unitName ? 'is-active-filter' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="small font-weight-bold text-uppercase text-slate-500">Unit {{ $unitName }}</div>
                            <div class="jp-stat-value">{{ $unitData['total'] }}</div>
                        </div>
                        <span class="jp-stat-icon" style="background: {{ $unitGradient }};">
                            <i class="fa fa-hourglass-half"></i>
                        </span>
                    </div>
                    <div class="jp-stat-footer">
                        @if ($hasRange)
                            Rentang: <strong>{{ $unitData['jam_pertama'] }} - {{ $unitData['jam_terakhir'] }}</strong>
                        @else
                            Belum ada jam pelajaran
                        @endif
                    </div>
                </a>
            @endforeach
        </div>

        <!-- FILTER -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter &amp; Pencarian Jam Pelajaran</span>
                    @if ($hasActiveFilter)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="filter-count-pill">
                        <i class="fa fa-clock text-primary"></i>
                        Menampilkan <strong>{{ $jamPelajaran->count() }}</strong> Jam Pelajaran
                    </span>
                    @if ($hasActiveFilter)
                        <a href="{{ route('jam-pelajaran.index') }}" class="filter-btn filter-btn-secondary"
                            style="height: 1.9rem; min-height: 1.9rem; padding: 0 0.7rem; font-size: 0.75rem;">
                            <i class="fa fa-undo"></i> Reset
                        </a>
                    @endif
                </div>
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('jam-pelajaran.index') }}"
                    class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <div class="lg:col-span-4">
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

                    <div class="lg:col-span-8">
                        <label for="filter_search" class="filter-label">Cari Jam Ke / Keterangan</label>
                        <div class="filter-search-group">
                            <input type="text" name="search" id="filter_search" class="filter-control"
                                placeholder="Contoh: 1, Istirahat, Upacara, 07:30..." value="{{ $filters['search'] ?? '' }}">
                            <button type="submit" class="filter-btn filter-btn-primary filter-btn-icon" title="Terapkan Filter">
                                <i class="fa fa-search"></i>
                            </button>
                            <a href="{{ route('jam-pelajaran.index') }}" class="filter-btn filter-btn-secondary filter-btn-icon"
                                title="Reset Filter">
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
                                <th style="width: 6%;">No</th>
                                <th class="text-center" style="width: 12%;">Jam Ke</th>
                                <th class="text-center" style="width: 10%;">Unit</th>
                                <th class="text-center" style="width: 15%;">Jam Mulai</th>
                                <th class="text-center" style="width: 18%;">Jam Selesai</th>
                                <th>Keterangan</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jamPelajaran as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-warning text-dark font-weight-bold px-2 py-1"
                                            style="font-size: 0.8rem;">
                                            Jam Ke-{{ $item->jam_ke }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->unit_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ strtoupper((string) $item->unit) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="jp-time-pill">
                                            <i class="fa fa-play-circle text-success" style="font-size: 0.72rem;"></i>
                                            {{ $item->jam_mulai_formatted }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="jp-time-pill">
                                            <i class="fa fa-stop-circle text-danger" style="font-size: 0.72rem;"></i>
                                            {{ $item->jam_selesai_formatted }}
                                        </span>
                                        @if ($item->durasi_label !== '-')
                                            <div class="small text-muted mt-1">{{ $item->durasi_label }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if (filled($item->keterangan))
                                            <span class="text-slate-700">{{ $item->keterangan }}</span>
                                        @else
                                            <span class="text-muted font-italic">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('jam-pelajaran.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('jam-pelajaran.destroy', $item->id) }}" method="POST" class="delete-form">
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
