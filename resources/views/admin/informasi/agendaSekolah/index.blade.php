@extends('layouts.appui')

@section('title')
    <title>Agenda Sekolah - Informasi</title>
@endsection

@section('content')
    @pushOnce('css')
        <style>
            .agenda-summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(185px, 1fr));
                gap: 0.9rem;
            }

            .agenda-stat-card {
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                height: 100%;
                padding: 15px 17px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                text-decoration: none !important;
                color: inherit;
                transition: all 0.2s ease;
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            }

            .agenda-stat-card:hover {
                transform: translateY(-2px);
                border-color: #f97316;
                box-shadow: 0 10px 20px -6px rgba(234, 88, 12, 0.16);
            }

            .agenda-stat-card.is-active-filter {
                border-color: #ea580c;
                background: #fff7ed;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.14);
            }

            .agenda-stat-icon {
                width: 42px;
                height: 42px;
                border-radius: 12px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: #ffffff;
                font-size: 1.05rem;
                flex-shrink: 0;
            }

            .agenda-stat-value {
                font-size: 1.45rem;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.15;
                margin-top: 4px;
            }

            .agenda-stat-footer {
                margin-top: 10px;
                padding-top: 8px;
                border-top: 1px solid #f1f5f9;
                font-size: 0.75rem;
                color: #64748b;
            }
        </style>
    @endPushOnce

    @php
        $allTaKey = \App\Services\Admin\Informasi\AgendaSekolahService::FILTER_TAHUN_AJARAN_ALL;
        $activeFilters = $filters ?? [
            'tahun_ajaran_id' => $allTaKey,
            'unit' => '',
        ];
        $tahunAjaranList = $tahunAjaranOptions ?? collect();
        $activeTa = $activeTahunAjaran ?? $tahunAjaranList->firstWhere('is_active', true);
        $activeTaIdStr = $activeTa?->id ? (string) $activeTa->id : '';

        if (isset($unitSummary)) {
            $summary = $unitSummary;
        } else {
            $fallbackPerUnit = [];
            foreach ($agendaSekolah as $agendaRow) {
                $unitKey = trim((string) ($agendaRow->unit ?? ''));
                $key = $unitKey !== '' ? $unitKey : 'Lainnya';
                $fallbackPerUnit[$key] = ($fallbackPerUnit[$key] ?? 0) + 1;
            }

            uksort($fallbackPerUnit, static fn (string $a, string $b): int => \App\Models\Admin\Informasi\AgendaSekolah::compareUnitOrder($a, $b));

            $summary = [
                'total_agenda' => $agendaSekolah->count(),
                'per_unit' => $fallbackPerUnit,
            ];
        }

        $unitFilterOptions = $unitOptions ?? \App\Models\Admin\Informasi\AgendaSekolah::UNIT_OPTIONS;
        $selectedTaModel = ctype_digit((string) $activeFilters['tahun_ajaran_id'])
            ? $tahunAjaranList->firstWhere('id', (int) $activeFilters['tahun_ajaran_id'])
            : null;
        $isDefaultState = $activeFilters['unit'] === ''
            && ($activeTaIdStr === '' ? $activeFilters['tahun_ajaran_id'] === $allTaKey : $activeFilters['tahun_ajaran_id'] === $activeTaIdStr);
        $scopeLabel = $selectedTaModel
            ? $selectedTaModel->nama_tahun_ajaran . ' Smt ' . strtoupper((string) $selectedTaModel->semester) . ($selectedTaModel->is_active ? ' (Aktif)' : '')
            : 'Semua Tahun Ajaran';
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-date" style="color: white;"></i>
                    </div>
                    <div>
                        Agenda Sekolah
                        <div class="page-title-subheading">
                            Kelola jadwal agenda sekolah per tahun ajaran dan unit pendidikan.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('agenda-sekolah.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH AGENDA
                    </a>
                </div>
            </div>
        </div>

        <!-- RINGKASAN JUMLAH AGENDA PER UNIT -->
        <div class="agenda-summary-grid mb-3">
            <a href="{{ route('agenda-sekolah.index', ['tahun_ajaran_id' => $activeFilters['tahun_ajaran_id']]) }}"
                class="agenda-stat-card {{ $activeFilters['unit'] === '' ? 'is-active-filter' : '' }}">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.4px;">
                            Semua Unit
                        </div>
                        <div class="agenda-stat-value">{{ number_format($summary['total_agenda']) }} Agenda</div>
                    </div>
                    <span class="agenda-stat-icon" style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);">
                        <i class="fa fa-calendar-check-o"></i>
                    </span>
                </div>
                <div class="agenda-stat-footer d-flex justify-content-between align-items-center">
                    <span>Total jadwal tercatat</span>
                    <span class="font-weight-bold text-dark">{{ $activeFilters['unit'] === '' ? 'Aktif' : 'Lihat semua' }}</span>
                </div>
            </a>

            @foreach ($summary['per_unit'] as $unitKey => $unitCount)
                @php
                    $iconBg = match (strtoupper((string) $unitKey)) {
                        'SEMUA UNIT' => 'linear-gradient(135deg, #334155 0%, #1e293b 100%)',
                        'PG' => 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
                        'TK', 'PAUD' => 'linear-gradient(135deg, #f59e0b 0%, #d97706 100%)',
                        'SD' => 'linear-gradient(135deg, #10b981 0%, #059669 100%)',
                        'SMP' => 'linear-gradient(135deg, #3b82f6 0%, #2563eb 100%)',
                        'SMA', 'SMK' => 'linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)',
                        default => 'linear-gradient(135deg, #64748b 0%, #475569 100%)',
                    };
                @endphp
                <a href="{{ route('agenda-sekolah.index', ['tahun_ajaran_id' => $activeFilters['tahun_ajaran_id'], 'unit' => $unitKey]) }}"
                    class="agenda-stat-card {{ strcasecmp($activeFilters['unit'], $unitKey) === 0 ? 'is-active-filter' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.4px;">
                                {{ $unitKey }}
                            </div>
                            <div class="agenda-stat-value">{{ number_format($unitCount) }} Agenda</div>
                        </div>
                        <span class="agenda-stat-icon" style="background: {{ $iconBg }};">
                            <i class="fa fa-calendar"></i>
                        </span>
                    </div>
                    <div class="agenda-stat-footer d-flex justify-content-between align-items-center">
                        <span>Agenda unit {{ $unitKey }}</span>
                        <span>{{ strcasecmp($activeFilters['unit'], $unitKey) === 0 ? 'Filter aktif' : 'Klik filter' }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- FILTER CARD -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter Agenda Sekolah</span>
                    @if (! $isDefaultState)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <span class="filter-count-pill">
                    <i class="fa fa-calendar-check text-primary"></i>
                    {{ number_format($agendaSekolah->count()) }} Agenda
                </span>
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('agenda-sekolah.index') }}" id="agendaFilterForm">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                        <div class="lg:col-span-5">
                            <label for="filter_tahun_ajaran_id" class="filter-label">
                                Tahun Ajaran / Semester
                            </label>
                            <select name="tahun_ajaran_id" id="filter_tahun_ajaran_id" class="filter-control">
                                <option value="{{ $allTaKey }}" @selected($activeFilters['tahun_ajaran_id'] === $allTaKey)>
                                    -- Semua Tahun Ajaran --
                                </option>
                                @foreach ($tahunAjaranList as $ta)
                                    <option value="{{ $ta->id }}" @selected((string) $activeFilters['tahun_ajaran_id'] === (string) $ta->id)>
                                        {{ $ta->nama_tahun_ajaran }} - Semester {{ strtoupper((string) $ta->semester) }} {{ $ta->is_active ? '(Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="lg:col-span-4">
                            <label for="filter_unit" class="filter-label">
                                Filter Unit
                            </label>
                            <select name="unit" id="filter_unit" class="filter-control">
                                <option value="">-- Semua Unit ({{ number_format($summary['total_agenda']) }} Agenda) --</option>
                                @foreach ($unitFilterOptions as $unitOpt)
                                    @php
                                        $optTotal = $summary['per_unit'][$unitOpt] ?? null;
                                    @endphp
                                    <option value="{{ $unitOpt }}" @selected(strcasecmp($activeFilters['unit'], $unitOpt) === 0)>
                                        {{ $unitOpt }}@if ($optTotal !== null) ({{ $optTotal }} Agenda)@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap lg:justify-end gap-2">
                            <button type="submit" class="filter-btn filter-btn-primary flex-1 sm:flex-initial">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                            @if ($activeFilters['tahun_ajaran_id'] !== $allTaKey)
                                <a href="{{ route('agenda-sekolah.index', array_filter(['tahun_ajaran_id' => $allTaKey, 'unit' => $activeFilters['unit'] !== '' ? $activeFilters['unit'] : null])) }}"
                                    class="filter-btn filter-btn-secondary" title="Tampilkan Semua Tahun Ajaran">
                                    Semua TA
                                </a>
                            @endif
                            @if (! $isDefaultState)
                                <a href="{{ route('agenda-sekolah.index') }}" class="filter-btn filter-btn-secondary filter-btn-icon"
                                    title="Kembali ke Tahun Ajaran Aktif">
                                    <i class="fa fa-undo"></i>
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="filter-footer small text-muted">
                        <span>
                            <i class="fa fa-info-circle text-primary mr-1"></i>
                            @if ($selectedTaModel?->is_active)
                                Menampilkan agenda untuk <strong>Tahun Ajaran Aktif ({{ $scopeLabel }})</strong>{{ $activeFilters['unit'] !== '' ? ' pada unit ' . $activeFilters['unit'] : '' }}.
                            @else
                                Menampilkan agenda untuk <strong>{{ $scopeLabel }}</strong>{{ $activeFilters['unit'] !== '' ? ' pada unit ' . $activeFilters['unit'] : '' }}.
                            @endif
                        </span>
                    </div>
                </form>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="main-card card mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover table-striped align-middle" id="myTable3"
                        data-empty-message="Belum ada data agenda sekolah tersedia">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Nama Agenda</th>
                                <th style="width: 18%;">Hari / Tanggal</th>
                                <th class="text-center" style="width: 12%;">Jam</th>
                                <th class="text-center" style="width: 12%;">Unit</th>
                                <th style="width: 18%;">Tahun Ajaran</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($agendaSekolah as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800" style="font-size: 0.95rem;">
                                            {{ $item->nama_agenda }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800">
                                            {{ $item->tanggal_formatted }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $item->hari_formatted }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($item->jam_formatted !== '-')
                                            <span class="badge badge-light border text-dark px-2 py-1 font-weight-bold" style="font-size: 0.78rem;">
                                                <i class="fa fa-clock-o text-warning mr-1"></i>{{ $item->jam_formatted }} WIB
                                            </span>
                                        @else
                                            <span class="text-muted font-italic">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->unit_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ strtoupper((string) $item->unit) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($item->tahunAjaran)
                                            <div class="font-weight-600 text-slate-700">
                                                {{ $item->tahunAjaran->nama_tahun_ajaran }}
                                            </div>
                                            <div class="small text-muted">
                                                Semester {{ strtoupper((string) $item->tahunAjaran->semester) }}
                                                @if ($item->tahunAjaran->is_active)
                                                    <span class="text-success font-weight-bold ml-1">• Aktif</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted font-italic">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('agenda-sekolah.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Agenda</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('agenda-sekolah.destroy', $item->id) }}" method="POST" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="action-dropdown-item action-dropdown-item--danger delete-button" role="menuitem">
                                                    <i class="fa fa-trash action-item-icon"></i>
                                                    <span>Hapus Agenda</span>
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

