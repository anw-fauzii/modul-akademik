@extends('layouts.appui')

@section('title')
    <title>Master Data Kelas</title>
@endsection

@section('content')
    @pushOnce('css')
        <style>
            .kelas-summary-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(195px, 1fr));
                gap: 0.9rem;
            }

            .kelas-stat-card {
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

            .kelas-stat-card:hover {
                transform: translateY(-2px);
                border-color: #f97316;
                box-shadow: 0 10px 20px -6px rgba(234, 88, 12, 0.16);
            }

            .kelas-stat-card.is-active-filter {
                border-color: #ea580c;
                background: #fff7ed;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.14);
            }

            .kelas-stat-icon {
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

            .kelas-stat-value {
                font-size: 1.5rem;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.15;
                margin-top: 4px;
            }

            .kelas-stat-footer {
                margin-top: 10px;
                padding-top: 8px;
                border-top: 1px solid #f1f5f9;
                font-size: 0.75rem;
                color: #64748b;
            }
        </style>
    @endPushOnce

    @php
        $allTaKey = \App\Services\Admin\DataMaster\KelasService::FILTER_TAHUN_AJARAN_ALL;
        $activeFilters = $filters ?? [
            'tahun_ajaran_id' => $allTaKey,
            'jenjang' => '',
        ];
        $tahunAjaranList = $tahunAjaranOptions ?? collect();
        $activeTa = $activeTahunAjaran ?? $tahunAjaranList->firstWhere('is_active', true);
        $activeTaIdStr = $activeTa?->id ? (string) $activeTa->id : '';

        if (isset($statistics)) {
            $jenjangStats = $statistics;
        } else {
            $fallbackPerJenjang = [];
            $fallbackTotalSiswa = 0;

            foreach ($kelas as $kelasRow) {
                $rawJenjang = strtoupper(trim((string) ($kelasRow->jenjang_kelas ?? '')));
                $key = $rawJenjang !== '' ? $rawJenjang : 'LAINNYA';
                $siswaCount = (int) ($kelasRow->anggota_kelas_count ?? 0);

                if (! isset($fallbackPerJenjang[$key])) {
                    $fallbackPerJenjang[$key] = ['kelas' => 0, 'siswa' => 0];
                }

                $fallbackPerJenjang[$key]['kelas']++;
                $fallbackPerJenjang[$key]['siswa'] += $siswaCount;
                $fallbackTotalSiswa += $siswaCount;
            }

            uksort($fallbackPerJenjang, static fn (string $a, string $b): int => \App\Models\Admin\DataMaster\Kelas::compareJenjangOrder($a, $b));

            $jenjangStats = [
                'total_kelas' => $kelas->count(),
                'total_siswa' => $fallbackTotalSiswa,
                'per_jenjang' => $fallbackPerJenjang,
            ];
        }

        $jenjangFilterOptions = $jenjangOptions ?? array_keys($jenjangStats['per_jenjang']);
        $selectedTaModel = ctype_digit((string) $activeFilters['tahun_ajaran_id'])
            ? $tahunAjaranList->firstWhere('id', (int) $activeFilters['tahun_ajaran_id'])
            : null;
        $isDefaultState = $activeFilters['jenjang'] === ''
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
                        <i class="pe-7s-id" style="color: white;"></i>
                    </div>
                    <div>
                        Data Kelas (Rombel)
                        <div class="page-title-subheading">
                            Menampilkan rombongan belajar tahun ajaran aktif secara default beserta ringkasan &amp; filter tiap jenjang.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('kelas.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH KELAS
                    </a>
                </div>
            </div>
        </div>

        <!-- RINGKASAN JUMLAH TIAP JENJANG (KLIK UNTUK FILTER) -->
        <div class="kelas-summary-grid mb-3">
            <a href="{{ route('kelas.index', ['tahun_ajaran_id' => $activeFilters['tahun_ajaran_id']]) }}"
                class="kelas-stat-card {{ $activeFilters['jenjang'] === '' ? 'is-active-filter' : '' }}">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.4px;">
                            Semua Jenjang
                        </div>
                        <div class="kelas-stat-value">{{ number_format($jenjangStats['total_kelas']) }} Kelas</div>
                    </div>
                    <span class="kelas-stat-icon" style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);">
                        <i class="fa fa-layer-group"></i>
                    </span>
                </div>
                <div class="kelas-stat-footer d-flex justify-content-between align-items-center">
                    <span class="font-weight-bold text-slate-700">{{ number_format($jenjangStats['total_siswa']) }} Siswa</span>
                    <span class="badge badge-light border text-muted">{{ $scopeLabel }}</span>
                </div>
            </a>

            @foreach ($jenjangStats['per_jenjang'] as $jenjangKey => $jenjangRow)
                @php
                    [$iconBg, $iconClass] = match ($jenjangKey) {
                        'PG' => ['linear-gradient(135deg, #06b6d4 0%, #0891b2 100%)', 'fa-child'],
                        'PAUD', 'TK' => ['linear-gradient(135deg, #f59e0b 0%, #d97706 100%)', 'fa-shapes'],
                        'SD' => ['linear-gradient(135deg, #10b981 0%, #047857 100%)', 'fa-book-open'],
                        'SMP' => ['linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%)', 'fa-graduation-cap'],
                        'SMA', 'SMK' => ['linear-gradient(135deg, #0284c7 0%, #0369a1 100%)', 'fa-university'],
                        default => ['linear-gradient(135deg, #64748b 0%, #475569 100%)', 'fa-school'],
                    };
                @endphp
                <a href="{{ route('kelas.index', ['tahun_ajaran_id' => $activeFilters['tahun_ajaran_id'], 'jenjang' => $jenjangKey]) }}"
                    class="kelas-stat-card {{ $activeFilters['jenjang'] === $jenjangKey ? 'is-active-filter' : '' }}">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-uppercase text-muted font-weight-bold" style="font-size: 11px; letter-spacing: 0.4px;">
                                Jenjang {{ $jenjangKey }}
                            </div>
                            <div class="kelas-stat-value">{{ number_format($jenjangRow['kelas']) }} Kelas</div>
                        </div>
                        <span class="kelas-stat-icon" style="background: {{ $iconBg }};">
                            <i class="fa {{ $iconClass }}"></i>
                        </span>
                    </div>
                    <div class="kelas-stat-footer d-flex justify-content-between align-items-center">
                        <span class="font-weight-bold text-primary">{{ number_format($jenjangRow['siswa']) }} Siswa</span>
                        <span>{{ $activeFilters['jenjang'] === $jenjangKey ? 'Filter aktif' : 'Klik filter' }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        <!-- FILTER CARD -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter Daftar Kelas</span>
                    @if (! $isDefaultState)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <span class="filter-count-pill">
                    <i class="fa fa-chalkboard text-primary"></i>
                    {{ number_format($kelas->count()) }} Kelas • {{ number_format($kelas->sum(static fn ($k): int => (int) ($k->anggota_kelas_count ?? 0))) }} Siswa
                </span>
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('kelas.index') }}" id="kelasFilterForm">
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
                            <label for="filter_jenjang" class="filter-label">
                                Filter Jenjang (Jumlah Kelas &amp; Siswa)
                            </label>
                            <select name="jenjang" id="filter_jenjang" class="filter-control">
                                <option value="">-- Semua Jenjang ({{ number_format($jenjangStats['total_kelas']) }} Kelas) --</option>
                                @foreach ($jenjangFilterOptions as $jenjangOpt)
                                    @php
                                        $optCount = $jenjangStats['per_jenjang'][$jenjangOpt] ?? null;
                                    @endphp
                                    <option value="{{ $jenjangOpt }}" @selected($activeFilters['jenjang'] === $jenjangOpt)>
                                        {{ $jenjangOpt }}@if ($optCount) ({{ $optCount['kelas'] }} Kelas • {{ $optCount['siswa'] }} Siswa)@endif
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap lg:justify-end gap-2">
                            <button type="submit" class="filter-btn filter-btn-primary flex-1 sm:flex-initial">
                                <i class="fa fa-filter"></i> Filter
                            </button>
                            @if ($activeFilters['tahun_ajaran_id'] !== $allTaKey)
                                <a href="{{ route('kelas.index', array_filter(['tahun_ajaran_id' => $allTaKey, 'jenjang' => $activeFilters['jenjang'] !== '' ? $activeFilters['jenjang'] : null])) }}"
                                    class="filter-btn filter-btn-secondary" title="Tampilkan Semua Tahun Ajaran">
                                    Semua TA
                                </a>
                            @endif
                            @if (! $isDefaultState)
                                <a href="{{ route('kelas.index') }}" class="filter-btn filter-btn-secondary filter-btn-icon"
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
                                Menampilkan data kelas untuk <strong>Tahun Ajaran Aktif ({{ $scopeLabel }})</strong>{{ $activeFilters['jenjang'] !== '' ? ' pada jenjang ' . $activeFilters['jenjang'] : '' }}.
                            @else
                                Menampilkan data kelas untuk <strong>{{ $scopeLabel }}</strong>{{ $activeFilters['jenjang'] !== '' ? ' pada jenjang ' . $activeFilters['jenjang'] : '' }}.
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
                    <table class="mb-0 table table-hover table-striped align-middle" id="myTable3">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Nama Kelas</th>
                                <th class="text-center" style="width: 12%;">Jenjang</th>
                                <th>Tahun Ajaran</th>
                                <th>Wali Kelas &amp; Pendamping</th>
                                <th class="text-center" style="width: 13%;">Jumlah Siswa</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kelas as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('kelas.show', $item->id) }}"
                                            class="font-weight-bold text-slate-800 text-decoration-none"
                                            style="font-size: 0.95rem;"
                                            title="Detail & Anggota Kelas {{ $item->nama_kelas }}">
                                            {{ $item->nama_kelas }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->jenjang_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ strtoupper((string) $item->jenjang_kelas) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($item->tahunAjaran)
                                            <div class="font-weight-600 text-slate-700">
                                                {{ $item->tahunAjaran->nama_tahun_ajaran }}
                                            </div>
                                            <div class="small text-muted">
                                                Semester {{ strtoupper($item->tahunAjaran->semester) }}
                                                @if ($item->tahunAjaran->is_active)
                                                    <span class="text-success font-weight-bold ml-1">• Aktif</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted italic">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <!-- Wali Kelas -->
                                            <div class="small">
                                                <i class="fa fa-user-tie text-primary mr-1" title="Wali Kelas"></i>
                                                @if ($item->waliKelas)
                                                    <strong>{{ $item->waliKelas->nama_gelar }}</strong>
                                                    <span class="text-muted font-italic" style="font-size: 11px;">(Wali Kelas)</span>
                                                @else
                                                    <span class="text-muted font-italic">Wali Kelas belum ditentukan</span>
                                                @endif
                                            </div>
                                            <!-- Guru Pendamping -->
                                            <div class="small text-muted">
                                                <i class="fa fa-user-friends text-info mr-1" title="Guru Pendamping"></i>
                                                @if ($item->pendamping)
                                                    <span>{{ $item->pendamping->nama_gelar }}</span>
                                                    <span class="font-italic" style="font-size: 11px;">(Pendamping)</span>
                                                @else
                                                    <span class="font-italic">Pendamping belum ditentukan</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $jumlahSiswa = (int) ($item->anggota_kelas_count ?? 0);
                                        @endphp
                                        <a href="{{ route('kelas.show', $item->id) }}"
                                            class="badge {{ $jumlahSiswa > 0 ? 'badge-primary' : 'badge-secondary' }} px-2 py-1 text-decoration-none"
                                            style="font-size: 0.78rem;"
                                            data-toggle="tooltip" data-placement="top"
                                            title="{{ $jumlahSiswa > 0 ? "Lihat {$jumlahSiswa} siswa & tambah anggota kelas {$item->nama_kelas}" : "Belum ada anggota, klik untuk tambah siswa ke kelas {$item->nama_kelas}" }}">
                                            <i class="fa {{ $jumlahSiswa > 0 ? 'fa-users' : 'fa-user-plus' }} mr-1"></i>
                                            {{ $jumlahSiswa }} Siswa
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('kelas.show', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-eye text-info action-item-icon"></i>
                                                <span>Detail &amp; Anggota Kelas</span>
                                            </a>
                                            <a href="{{ route('kelas.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('kelas.destroy', $item->id) }}" method="POST" class="delete-form">
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
