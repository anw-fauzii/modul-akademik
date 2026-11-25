@extends('layouts.appui')

@section('title')
    <title>Bank Komentar Rapor &amp; T2Q</title>
@endsection

@pushOnce('css')
    <style>
        .bk-tab-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            margin-bottom: 1rem;
        }

        .bk-tab-pills {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.35rem;
            padding: 0.32rem;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .bk-tab-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.42rem 0.85rem;
            border-radius: 8px;
            font-size: 0.78rem;
            font-weight: 600;
            color: #475569;
            text-decoration: none !important;
            border: 1px solid transparent;
            transition: all 0.15s ease;
        }

        .bk-tab-pill:hover {
            color: #0f172a;
            background: rgba(255, 255, 255, 0.75);
        }

        .bk-tab-pill.is-active {
            background: #ffffff;
            color: #0f172a;
            border-color: #e2e8f0;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.08);
        }

        .bk-tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 1.35rem;
            height: 1.25rem;
            padding: 0 0.4rem;
            border-radius: 999px;
            font-size: 0.69rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
            background: #e2e8f0;
            color: #475569;
        }

        .bk-tab-pill.is-active .bk-tab-count {
            background: #fff7ed;
            color: #ea580c;
        }

        .bk-summary-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            padding: 0.38rem 0.85rem;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            font-size: 0.74rem;
            font-weight: 600;
            color: #475569;
            font-variant-numeric: tabular-nums;
        }

        .bk-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            display: inline-block;
            flex-shrink: 0;
        }

        .bk-filter-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.85rem;
            align-items: end;
        }

        .bk-col-kategori,
        .bk-col-jenjang,
        .bk-col-status,
        .bk-col-sort,
        .bk-col-search,
        .bk-col-search-wide {
            min-width: 0;
        }

        @media (min-width: 640px) {
            .bk-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .bk-col-search,
            .bk-col-search-wide {
                grid-column: span 2;
            }
        }

        @media (min-width: 992px) {
            .bk-filter-grid {
                grid-template-columns: repeat(12, minmax(0, 1fr));
            }

            .bk-col-kategori {
                grid-column: span 3;
            }

            .bk-col-jenjang {
                grid-column: span 2;
            }

            .bk-col-status {
                grid-column: span 2;
            }

            .bk-col-search {
                grid-column: span 5;
            }

            .bk-col-sort {
                grid-column: span 3;
            }

            .bk-col-search-wide {
                grid-column: span 9;
            }
        }

        .bk-table thead th {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            color: #475569;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 0.9rem;
            white-space: nowrap;
        }

        .bk-table tbody td {
            padding: 0.82rem 0.9rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .bk-status-pill-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.65rem;
            border-radius: 999px;
            font-size: 0.72rem;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            color: #475569;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .bk-status-pill-btn.is-active {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #047857;
        }

        .bk-modal-overlay {
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

        .bk-modal-overlay.is-open {
            display: flex;
        }

        .bk-modal-dialog {
            width: 100%;
            max-width: 540px;
            background: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 24px 48px -12px rgba(15, 23, 42, 0.35);
            overflow: hidden;
        }
    </style>
@endPushOnce

@section('content')
    @php
        $tab = ($tab ?? 'wali') === 't2q' ? 't2q' : 'wali';
        $filters = $filters ?? [
            'tab' => $tab,
            'kategori' => '',
            'jenjang' => '',
            'status' => 'all',
            'search' => '',
            'sort' => 'urutan',
            'hasActiveFilter' => false,
        ];
        $hasActiveFilter = $hasActiveFilter ?? (bool) ($filters['hasActiveFilter'] ?? false);
        $waliStats = $waliStats ?? [
            'total' => $templates->count(),
            'aktif' => $templates->where('is_active', true)->count(),
            'nonaktif' => $templates->where('is_active', false)->count(),
        ];
        $t2qTotal = $t2qTotal ?? $t2qComments->count();
        $jenjangOptions = $jenjangOptions ?? ['PG', 'TK', 'SD'];
        $kategoriOptions = $kategoriOptions ?? \App\Models\Admin\DataMaster\KomentarRapor::KATEGORI_OPTIONS;
    @endphp

    <div class="app-main__inner">
        <!-- HEADER -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-comment" style="color: white;"></i>
                    </div>
                    <div>
                        Bank Komentar Rapor &amp; T2Q
                        <div class="page-title-subheading">
                            Kelola template komentar cepat Wali Kelas / Pendamping serta poin aspek capaian T2Q (Tahsin &amp; Tahfidz).
                        </div>
                    </div>
                </div>

                <div class="page-title-actions d-flex align-items-center" style="gap: 0.5rem;">
                    <button type="button" id="openCreateBankModalBtn"
                        class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 9px; border: none;">
                        <i class="fa fa-plus mr-1"></i>
                        {{ $tab === 'wali' ? 'Tambah Template Wali Kelas' : 'Tambah Poin T2Q' }}
                    </button>
                </div>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">
                <i class="fa fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger shadow-sm" style="border-radius: 10px;">
                <i class="fa fa-exclamation-triangle mr-1"></i>
                <strong>Periksa kembali input Anda:</strong> {{ $errors->first() }}
            </div>
        @endif

        <!-- SEGMENTED TAB STRIP -->
        <div class="bk-tab-strip">
            <div class="bk-tab-pills">
                <a href="{{ route('bank-komentar.index', ['tab' => 'wali']) }}"
                    class="bk-tab-pill {{ $tab === 'wali' ? 'is-active' : '' }}">
                    <i class="fa fa-comments text-warning"></i>
                    <span>Template Wali Kelas</span>
                    <span class="bk-tab-count">{{ $waliStats['total'] }}</span>
                </a>
                <a href="{{ route('bank-komentar.index', ['tab' => 't2q']) }}"
                    class="bk-tab-pill {{ $tab === 't2q' ? 'is-active' : '' }}">
                    <i class="fa fa-quran text-success"></i>
                    <span>Poin Komentar T2Q</span>
                    <span class="bk-tab-count">{{ $t2qTotal }}</span>
                </a>
            </div>

            <div class="bk-summary-pill">
                @if ($tab === 'wali')
                    <span class="d-inline-flex align-items-center" style="gap: 0.35rem;">
                        <span class="bk-dot" style="background: #10b981;"></span>
                        {{ $waliStats['aktif'] }} Aktif
                    </span>
                    @if ($waliStats['nonaktif'] > 0)
                        <span style="color: #cbd5e1;">|</span>
                        <span class="d-inline-flex align-items-center" style="gap: 0.35rem;">
                            <span class="bk-dot" style="background: #94a3b8;"></span>
                            {{ $waliStats['nonaktif'] }} Nonaktif
                        </span>
                    @endif
                @else
                    <span class="d-inline-flex align-items-center" style="gap: 0.35rem;">
                        <span class="bk-dot" style="background: #10b981;"></span>
                        {{ $t2qTotal }} Poin Kelebihan &amp; Peningkatan T2Q
                    </span>
                @endif
            </div>
        </div>

        <!-- FILTER CARD -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>{{ $tab === 'wali' ? 'Filter & Pencarian Template Wali Kelas' : 'Filter & Pencarian Poin T2Q' }}</span>
                    @if ($hasActiveFilter)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <span class="filter-count-pill">
                    <i class="fa fa-check-circle text-warning"></i>
                    <strong>{{ number_format($tab === 'wali' ? $templates->count() : $t2qComments->count()) }}</strong>
                    {{ $tab === 'wali' ? 'template' : 'poin' }}
                </span>
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('bank-komentar.index') }}" id="bkFilterForm" class="bk-filter-grid">
                    <input type="hidden" name="tab" value="{{ $tab }}">

                    @if ($tab === 'wali')
                        <div class="bk-col-kategori">
                            <label for="filter_kategori" class="filter-label">Kategori</label>
                            <select name="kategori" id="filter_kategori" class="filter-control js-bk-autosubmit">
                                <option value="">Semua Kategori</option>
                                @foreach ($kategoriOptions as $key => $label)
                                    <option value="{{ $key }}" @selected(($filters['kategori'] ?? '') === $key)>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bk-col-jenjang">
                            <label for="filter_jenjang" class="filter-label">Jenjang</label>
                            <select name="jenjang" id="filter_jenjang" class="filter-control js-bk-autosubmit">
                                <option value="">Semua Jenjang</option>
                                <option value="{{ \App\Services\Admin\DataMaster\BankKomentarService::JENJANG_UMUM }}"
                                    @selected(($filters['jenjang'] ?? '') === \App\Services\Admin\DataMaster\BankKomentarService::JENJANG_UMUM)>
                                    Umum (PG/TK/SD)
                                </option>
                                @foreach ($jenjangOptions as $j)
                                    <option value="{{ $j }}" @selected(($filters['jenjang'] ?? '') === $j)>{{ $j }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="bk-col-status">
                            <label for="filter_status" class="filter-label">Status</label>
                            <select name="status" id="filter_status" class="filter-control js-bk-autosubmit">
                                <option value="all" @selected(($filters['status'] ?? 'all') === 'all')>Semua</option>
                                <option value="1" @selected(($filters['status'] ?? '') === '1')>Aktif</option>
                                <option value="0" @selected(($filters['status'] ?? '') === '0')>Nonaktif</option>
                            </select>
                        </div>

                        <div class="bk-col-search">
                            <label for="filter_search" class="filter-label">Cari Judul atau Isi Template</label>
                            <div class="filter-search-group">
                                <input type="text" name="search" id="filter_search" class="filter-control"
                                    placeholder="Kata kunci judul atau teks komentar..." value="{{ $filters['search'] ?? '' }}">
                                <button type="submit" class="filter-btn filter-btn-primary filter-btn-icon" title="Terapkan Filter">
                                    <i class="fa fa-search"></i>
                                </button>
                                @if ($hasActiveFilter)
                                    <a href="{{ route('bank-komentar.index', ['tab' => 'wali']) }}"
                                        class="filter-btn filter-btn-secondary filter-btn-icon" title="Reset Filter">
                                        <i class="fa fa-undo"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @else
                        <div class="bk-col-sort">
                            <label for="filter_sort" class="filter-label">Urutan Tampil</label>
                            <select name="sort" id="filter_sort" class="filter-control js-bk-autosubmit">
                                <option value="urutan" @selected(($filters['sort'] ?? 'urutan') === 'urutan')>Default (Urutan Master)</option>
                                <option value="az" @selected(($filters['sort'] ?? '') === 'az')>Abjad A &rarr; Z</option>
                                <option value="za" @selected(($filters['sort'] ?? '') === 'za')>Abjad Z &rarr; A</option>
                                <option value="terbaru" @selected(($filters['sort'] ?? '') === 'terbaru')>Terbaru Ditambahkan</option>
                            </select>
                        </div>

                        <div class="bk-col-search-wide">
                            <label for="filter_search" class="filter-label">Cari Poin Komentar T2Q</label>
                            <div class="filter-search-group">
                                <input type="text" name="search" id="filter_search" class="filter-control"
                                    placeholder="Contoh: ghunnah, mad, makhraj, qolqolah..." value="{{ $filters['search'] ?? '' }}">
                                <button type="submit" class="filter-btn filter-btn-primary filter-btn-icon" title="Terapkan Filter">
                                    <i class="fa fa-search"></i>
                                </button>
                                @if ($hasActiveFilter)
                                    <a href="{{ route('bank-komentar.index', ['tab' => 't2q']) }}"
                                        class="filter-btn filter-btn-secondary filter-btn-icon" title="Reset Filter">
                                        <i class="fa fa-undo"></i>
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endif
                </form>
            </div>
        </div>

        <!-- TABEL DATA FULL-WIDTH -->
        <div class="main-card card shadow-sm border-0" style="border-radius: 14px;">
            <div class="card-body">
                @if ($tab === 'wali')
                    @if ($templates->isEmpty())
                        <div class="text-center py-5 text-muted">
                            <i class="fa fa-comments mb-2" style="font-size: 2rem; opacity: 0.4;"></i>
                            <div class="font-weight-bold text-dark">
                                {{ $hasActiveFilter ? 'Tidak Ada Template yang Sesuai Filter' : 'Belum Ada Template Komentar Wali Kelas' }}
                            </div>
                            <div class="small mt-1 mb-3">
                                {{ $hasActiveFilter ? 'Coba ubah filter kategori/jenjang atau reset pencarian.' : 'Klik tombol Tambah Template Wali Kelas di kanan atas atau jalankan KomentarRaporSeeder.' }}
                            </div>
                            @if ($hasActiveFilter)
                                <a href="{{ route('bank-komentar.index', ['tab' => 'wali']) }}" class="btn btn-sm btn-outline-secondary">
                                    <i class="fa fa-undo mr-1"></i> Reset Filter
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 bk-table" id="myTable6">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 6%;">Urut</th>
                                        <th style="width: 24%;">Judul &amp; Kategori</th>
                                        <th style="width: 11%;" class="text-center">Jenjang</th>
                                        <th>Isi Template Komentar</th>
                                        <th style="width: 12%;" class="text-center">Status</th>
                                        <th style="width: 12%;" class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($templates as $tpl)
                                        <tr>
                                            <td class="text-center font-weight-bold text-muted">{{ $tpl->urutan }}</td>
                                            <td>
                                                <div class="font-weight-bold text-dark">{{ $tpl->judul }}</div>
                                                <span class="badge {{ $tpl->kategori_badge_class }} mt-1">{{ $tpl->kategori_label }}</span>
                                            </td>
                                            <td class="text-center">
                                                @if ($tpl->jenjang)
                                                    <span class="badge badge-light border font-weight-bold">{{ $tpl->jenjang }}</span>
                                                @else
                                                    <span class="badge badge-light border text-muted">Semua</span>
                                                @endif
                                            </td>
                                            <td style="font-size: 0.84rem; line-height: 1.5; color: #334155;">
                                                {{ $tpl->isi_komentar }}
                                            </td>
                                            <td class="text-center">
                                                <form method="POST" action="{{ route('bank-komentar.wali.toggle', $tpl) }}">
                                                    @csrf
                                                    <button type="submit"
                                                        class="bk-status-pill-btn {{ $tpl->is_active ? 'is-active' : '' }}"
                                                        title="Klik untuk ubah status">
                                                        <span class="bk-dot" style="background: {{ $tpl->is_active ? '#10b981' : '#94a3b8' }};"></span>
                                                        {{ $tpl->is_active ? 'Aktif' : 'Nonaktif' }}
                                                    </button>
                                                </form>
                                            </td>
                                            <td class="text-center text-nowrap">
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-primary py-0 px-2 js-edit-wali-tpl"
                                                    data-action="{{ route('bank-komentar.wali.update', $tpl) }}"
                                                    data-judul="{{ $tpl->judul }}"
                                                    data-kategori="{{ $tpl->kategori }}"
                                                    data-jenjang="{{ $tpl->jenjang ?? '' }}"
                                                    data-urutan="{{ $tpl->urutan }}"
                                                    data-isi="{{ $tpl->isi_komentar }}"
                                                    title="Edit Template">
                                                    <i class="fa fa-edit"></i>
                                                </button>
                                                <form method="POST" action="{{ route('bank-komentar.wali.destroy', $tpl) }}" class="d-inline js-confirm-delete-form">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                @else
                    <div class="table-responsive">
                        <table class="table table-hover mb-0 bk-table" id="myTable6"
                            data-empty-table="{{ $hasActiveFilter ? 'Tidak ada poin T2Q yang cocok dengan filter.' : 'Belum ada poin komentar T2Q.' }}">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 8%;">No</th>
                                    <th>Poin Komentar Aspek T2Q (Kelebihan &amp; Peningkatan)</th>
                                    <th style="width: 14%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($t2qComments as $idx => $itemT2q)
                                    <tr>
                                        <td class="text-center font-weight-bold text-muted">{{ $idx + 1 }}</td>
                                        <td style="font-size: 0.86rem; color: #1e293b;">{{ $itemT2q->komentar }}</td>
                                        <td class="text-center text-nowrap">
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary py-0 px-2 js-edit-t2q-btn"
                                                data-action="{{ route('bank-komentar.t2q.update', $itemT2q) }}"
                                                data-komentar="{{ $itemT2q->komentar }}"
                                                title="Edit Poin">
                                                <i class="fa fa-edit"></i>
                                            </button>
                                            <form method="POST" action="{{ route('bank-komentar.t2q.destroy', $itemT2q) }}" class="d-inline js-confirm-delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Hapus">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH TEMPLATE WALI KELAS / POIN T2Q -->
    <div id="createBankModalOverlay" class="bk-modal-overlay {{ (isset($errors) && $errors->any()) ? 'is-open' : '' }}">
        <div class="bk-modal-dialog">
            <div class="px-4 py-3 border-bottom d-flex justify-content-between align-items-center bg-light">
                <div class="font-weight-bold text-dark">
                    <i class="fa fa-plus-circle text-warning mr-1"></i>
                    {{ $tab === 'wali' ? 'Tambah Template Komentar Wali Kelas' : 'Tambah Poin Komentar T2Q' }}
                </div>
                <button type="button" class="btn btn-sm btn-light border js-close-bank-modal" aria-label="Tutup">
                    <i class="fa fa-times"></i>
                </button>
            </div>
            <div class="p-4">
                @if ($tab === 'wali')
                    <form method="POST" action="{{ route('bank-komentar.wali.store') }}">
                        @csrf
                        <div class="form-group">
                            <label for="modal_judul" class="small font-weight-bold">Judul Template <span class="text-danger">*</span></label>
                            <input type="text" name="judul" id="modal_judul" class="form-control form-control-sm"
                                placeholder="Contoh: Apresiasi Capaian & Kemandirian" value="{{ old('judul') }}" required>
                        </div>
                        <div class="row">
                            <div class="col-md-5 form-group">
                                <label for="modal_kategori" class="small font-weight-bold">Kategori <span class="text-danger">*</span></label>
                                <select name="kategori" id="modal_kategori" class="form-control form-control-sm" required>
                                    @foreach ($kategoriOptions as $key => $label)
                                        <option value="{{ $key }}" @selected(old('kategori', 'umum') === $key)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4 form-group">
                                <label for="modal_jenjang" class="small font-weight-bold">Jenjang</label>
                                <select name="jenjang" id="modal_jenjang" class="form-control form-control-sm">
                                    <option value="">Semua Jenjang</option>
                                    @foreach ($jenjangOptions as $j)
                                        <option value="{{ $j }}" @selected(old('jenjang') === $j)>{{ $j }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-3 form-group">
                                <label for="modal_urutan" class="small font-weight-bold">Urutan</label>
                                <input type="number" name="urutan" id="modal_urutan" min="0" class="form-control form-control-sm"
                                    value="{{ old('urutan', $waliStats['total'] + 1) }}">
                            </div>
                        </div>
                        <div class="form-group">
                            <label for="modal_isi_komentar" class="small font-weight-bold">
                                Isi Komentar <span class="text-danger">*</span>
                            </label>
                            <textarea name="isi_komentar" id="modal_isi_komentar" rows="4" class="form-control form-control-sm"
                                placeholder="Gunakan {nama} untuk otomatis diganti nama siswa..." required>{{ old('isi_komentar') }}</textarea>
                            <small class="form-text text-muted">
                                Placeholder <code>{nama}</code> otomatis terisi nama siswa saat wali kelas memilih template.
                            </small>
                        </div>
                        <input type="hidden" name="is_active" value="1">
                        <div class="d-flex justify-content-end pt-2 border-top" style="gap: 0.5rem;">
                            <button type="button" class="btn btn-sm btn-light border js-close-bank-modal">Batal</button>
                            <button type="submit" class="btn btn-sm text-white font-weight-bold px-3"
                                style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border: none; border-radius: 8px;">
                                <i class="fa fa-save mr-1"></i> Simpan Template
                            </button>
                        </div>
                    </form>
                @else
                    <form method="POST" action="{{ route('bank-komentar.t2q.store') }}">
                        @csrf
                        <div class="form-group">
                            <label for="modal_komentar" class="small font-weight-bold">
                                Teks Poin Aspek T2Q <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="komentar" id="modal_komentar" class="form-control form-control-sm"
                                placeholder="Contoh: konsistensi bacaan ghunnah" value="{{ old('komentar') }}" required>
                            <small class="form-text text-muted">
                                Poin ini muncul pada pilihan <em>Kelebihan</em> &amp; <em>Perlu Ditingkatkan</em> di Rapor T2Q.
                            </small>
                        </div>
                        <div class="d-flex justify-content-end pt-2 border-top" style="gap: 0.5rem;">
                            <button type="button" class="btn btn-sm btn-light border js-close-bank-modal">Batal</button>
                            <button type="submit" class="btn btn-sm text-white font-weight-bold px-3"
                                style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border: none; border-radius: 8px;">
                                <i class="fa fa-save mr-1"></i> Simpan Poin T2Q
                            </button>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </div>

    <form id="bankKomentarEditWaliForm" method="POST" style="display: none;">
        @csrf
        @method('PUT')
        <input type="hidden" name="judul" id="editWaliJudul">
        <input type="hidden" name="kategori" id="editWaliKategori">
        <input type="hidden" name="jenjang" id="editWaliJenjang">
        <input type="hidden" name="urutan" id="editWaliUrutan">
        <input type="hidden" name="isi_komentar" id="editWaliIsi">
        <input type="hidden" name="is_active" value="1">
    </form>

    <form id="bankKomentarEditT2qForm" method="POST" style="display: none;">
        @csrf
        @method('PUT')
        <input type="hidden" name="komentar" id="editT2qKomentar">
    </form>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            const filterForm = document.getElementById('bkFilterForm');
            document.querySelectorAll('.js-bk-autosubmit').forEach(function(el) {
                el.addEventListener('change', function() {
                    if (filterForm) filterForm.submit();
                });
            });

            const modalOverlay = document.getElementById('createBankModalOverlay');
            const openModalBtn = document.getElementById('openCreateBankModalBtn');
            if (openModalBtn && modalOverlay) {
                openModalBtn.addEventListener('click', function() {
                    modalOverlay.classList.add('is-open');
                });
            }
            document.querySelectorAll('.js-close-bank-modal').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    if (modalOverlay) modalOverlay.classList.remove('is-open');
                });
            });
            if (modalOverlay) {
                modalOverlay.addEventListener('click', function(e) {
                    if (e.target === modalOverlay) modalOverlay.classList.remove('is-open');
                });
            }

            function escapeHtml(str) {
                return $('<div>').text(String(str ?? '')).html();
            }

            $(document).on('click', '.js-edit-wali-tpl', function() {
                const btn = $(this);
                const action = btn.data('action');
                const judul = btn.data('judul') || '';
                const kategori = btn.data('kategori') || 'umum';
                const jenjang = btn.data('jenjang') || '';
                const urutan = btn.data('urutan') || 0;
                const isi = btn.data('isi') || '';

                Swal.fire({
                    title: 'Edit Template Komentar',
                    html:
                        '<div class="text-left">' +
                        '<label class="small font-weight-bold">Judul</label>' +
                        '<input id="swalWaliJudul" class="form-control form-control-sm mb-2" value="' + escapeHtml(judul) + '">' +
                        '<label class="small font-weight-bold">Kategori</label>' +
                        '<select id="swalWaliKategori" class="form-control form-control-sm mb-2">' +
                        '<option value="positif"' + (kategori === 'positif' ? ' selected' : '') + '>Apresiasi & Capaian Baik</option>' +
                        '<option value="motivasi"' + (kategori === 'motivasi' ? ' selected' : '') + '>Dorongan & Motivasi Belajar</option>' +
                        '<option value="perhatian"' + (kategori === 'perhatian' ? ' selected' : '') + '>Perlu Perhatian & Pendampingan</option>' +
                        '<option value="umum"' + (kategori === 'umum' ? ' selected' : '') + '>Catatan Umum / Mid Semester</option>' +
                        '</select>' +
                        '<label class="small font-weight-bold">Jenjang</label>' +
                        '<select id="swalWaliJenjang" class="form-control form-control-sm mb-2">' +
                        '<option value=""' + (jenjang === '' ? ' selected' : '') + '>Semua Jenjang</option>' +
                        '<option value="PG"' + (jenjang === 'PG' ? ' selected' : '') + '>PG</option>' +
                        '<option value="TK"' + (jenjang === 'TK' ? ' selected' : '') + '>TK</option>' +
                        '<option value="SD"' + (jenjang === 'SD' ? ' selected' : '') + '>SD</option>' +
                        '</select>' +
                        '<label class="small font-weight-bold">Isi Komentar</label>' +
                        '<textarea id="swalWaliIsi" rows="4" class="form-control form-control-sm">' + escapeHtml(isi) + '</textarea>' +
                        '</div>',
                    showCancelButton: true,
                    confirmButtonText: 'Simpan Perubahan',
                    cancelButtonText: 'Batal',
                    preConfirm: () => {
                        const j = document.getElementById('swalWaliJudul').value.trim();
                        const i = document.getElementById('swalWaliIsi').value.trim();
                        if (!j || !i) {
                            Swal.showValidationMessage('Judul dan isi komentar wajib diisi.');
                            return false;
                        }
                        return {
                            judul: j,
                            kategori: document.getElementById('swalWaliKategori').value,
                            jenjang: document.getElementById('swalWaliJenjang').value,
                            isi: i
                        };
                    }
                }).then((res) => {
                    if (res.isConfirmed && res.value) {
                        const form = document.getElementById('bankKomentarEditWaliForm');
                        document.getElementById('editWaliJudul').value = res.value.judul;
                        document.getElementById('editWaliKategori').value = res.value.kategori;
                        document.getElementById('editWaliJenjang').value = res.value.jenjang;
                        document.getElementById('editWaliUrutan').value = urutan;
                        document.getElementById('editWaliIsi').value = res.value.isi;
                        form.action = action;
                        form.submit();
                    }
                });
            });

            $(document).on('click', '.js-edit-t2q-btn', function() {
                const action = $(this).data('action');
                const komentar = $(this).data('komentar') || '';

                Swal.fire({
                    title: 'Edit Poin Komentar T2Q',
                    input: 'text',
                    inputValue: komentar,
                    showCancelButton: true,
                    confirmButtonText: 'Simpan',
                    cancelButtonText: 'Batal',
                    inputValidator: (v) => (!v || !v.trim()) ? 'Teks komentar T2Q wajib diisi.' : null
                }).then((res) => {
                    if (res.isConfirmed) {
                        const form = document.getElementById('bankKomentarEditT2qForm');
                        document.getElementById('editT2qKomentar').value = res.value.trim();
                        form.action = action;
                        form.submit();
                    }
                });
            });

            $(document).on('submit', '.js-confirm-delete-form', function(e) {
                e.preventDefault();
                const form = this;
                Swal.fire({
                    title: 'Hapus Item?',
                    text: 'Data yang dihapus tidak dapat dikembalikan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    confirmButtonText: 'Ya, Hapus',
                    cancelButtonText: 'Batal'
                }).then((res) => {
                    if (res.isConfirmed) form.submit();
                });
            });
        });
    </script>
@endpush
