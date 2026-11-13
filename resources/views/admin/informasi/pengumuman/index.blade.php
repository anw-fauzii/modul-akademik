@extends('layouts.appui')

@section('title')
    <title>Pengumuman Sekolah</title>
@endsection

@push('css')
    <style>
        .pengumuman-rich-content p {
            margin-bottom: 0.55rem;
        }

        .pengumuman-rich-content p:last-child {
            margin-bottom: 0;
        }

        .pengumuman-rich-content ul {
            list-style-type: disc;
            padding-left: 1.35rem;
            margin-bottom: 0.55rem;
        }

        .pengumuman-rich-content ol {
            list-style-type: decimal;
            padding-left: 1.35rem;
            margin-bottom: 0.55rem;
        }

        .pengumuman-rich-content h1,
        .pengumuman-rich-content h2,
        .pengumuman-rich-content h3,
        .pengumuman-rich-content h4 {
            font-weight: 700;
            color: #1e293b;
            margin: 0.65rem 0 0.35rem;
            line-height: 1.35;
        }

        .pengumuman-rich-content h1 {
            font-size: 1.15rem;
        }

        .pengumuman-rich-content h2 {
            font-size: 1.05rem;
        }

        .pengumuman-rich-content h3,
        .pengumuman-rich-content h4 {
            font-size: 0.95rem;
        }

        .pengumuman-rich-content blockquote {
            border-left: 3px solid #ea580c;
            padding-left: 0.75rem;
            color: #475569;
            margin: 0.5rem 0;
        }

        .pengumuman-rich-content table {
            width: 100%;
            border-collapse: collapse;
            margin: 0.5rem 0;
        }

        .pengumuman-rich-content td,
        .pengumuman-rich-content th {
            border: 1px solid #cbd5e1;
            padding: 4px 8px;
        }

        .pengumuman-rich-content a {
            color: #ea580c;
            text-decoration: underline;
        }
    </style>
@endpush

@section('content')
    @php
        $pengumuman = $pengumuman ?? collect();
        $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
        $filters = $filters ?? [
            'tahun_ajaran_id' => \App\Services\Admin\Informasi\PengumumanService::FILTER_TAHUN_AJARAN_ALL,
            'search' => '',
        ];
        $totalPengumuman = $totalPengumuman ?? $pengumuman->count();
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-bell" style="color: white;"></i>
                    </div>
                    <div>
                        Pengumuman Sekolah
                        <div class="page-title-subheading">
                            Kelola daftar pengumuman sekolah untuk siswa beserta tahun ajaran dan isi informasi resmi.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('pengumuman.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH PENGUMUMAN
                    </a>
                </div>
            </div>
        </div>

        <!-- FILTER & RINGKASAN -->
        @php
            $allTaPengumumanKey = \App\Services\Admin\Informasi\PengumumanService::FILTER_TAHUN_AJARAN_ALL;
            $hasActivePengumumanFilter = (($filters['tahun_ajaran_id'] ?? $allTaPengumumanKey) !== $allTaPengumumanKey)
                || (($filters['search'] ?? '') !== '');
        @endphp
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter &amp; Pencarian Pengumuman</span>
                    @if ($hasActivePengumumanFilter)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <span class="filter-count-pill">
                    <i class="fa fa-bullhorn text-primary"></i>
                    Total Pengumuman: <strong>{{ $totalPengumuman }}</strong>
                </span>
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('pengumuman.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 items-end">
                    <div class="lg:col-span-4">
                        <label for="filter_tahun_ajaran_id" class="filter-label">
                            Tahun Ajaran
                        </label>
                        <select name="tahun_ajaran_id" id="filter_tahun_ajaran_id" class="filter-control">
                            <option value="{{ $allTaPengumumanKey }}"
                                @selected(($filters['tahun_ajaran_id'] ?? '') === $allTaPengumumanKey)>
                                Semua Tahun Ajaran
                            </option>
                            @foreach ($tahunAjaranOptions as $ta)
                                <option value="{{ $ta->id }}"
                                    @selected((string) ($filters['tahun_ajaran_id'] ?? '') === (string) $ta->id)>
                                    {{ $ta->nama_tahun_ajaran }} - Semester {{ strtoupper($ta->semester) }}
                                    {{ $ta->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="lg:col-span-5">
                        <label for="filter_search" class="filter-label">
                            Cari Judul / Isi
                        </label>
                        <input type="text" name="search" id="filter_search" class="filter-control"
                            placeholder="Ketik kata kunci pengumuman..." value="{{ $filters['search'] ?? '' }}">
                    </div>

                    <div class="sm:col-span-2 lg:col-span-3 flex flex-wrap lg:justify-end gap-2">
                        <button type="submit" class="filter-btn filter-btn-primary flex-1 sm:flex-initial">
                            <i class="fa fa-filter"></i> Tampilkan
                        </button>
                        <a href="{{ route('pengumuman.index', ['tahun_ajaran_id' => $allTaPengumumanKey]) }}"
                            class="filter-btn filter-btn-secondary">
                            <i class="fa fa-list"></i> Semua Data
                        </a>
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
                                <th>Judul &amp; Isi Pengumuman</th>
                                <th class="text-center" style="width: 12%;">Target</th>
                                <th style="width: 20%;">Tahun Ajaran</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pengumuman as $item)
                                @php
                                    $rawIsi = (string) ($item->isi ?? '');
                                    $plainIsi = \App\Support\HtmlSanitizer::extractPlainText($rawIsi);
                                    $hasMoreContent =
                                        \Illuminate\Support\Str::length($plainIsi) > 140 ||
                                        str_contains($rawIsi, "\n") ||
                                        $rawIsi !== strip_tags($rawIsi);
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800" style="font-size: 0.95rem;">
                                            {{ $item->judul }}
                                        </div>
                                        <div class="small text-muted mt-1">
                                            <i class="fa fa-clock-o mr-1"></i>{{ $item->tanggal_dibuat_formatted }}
                                        </div>
                                        <div class="small text-slate-600 mt-1">
                                            {{ $item->ringkasan_isi }}
                                        </div>
                                        @if ($hasMoreContent)
                                            <details class="mt-1">
                                                <summary class="small text-primary font-weight-600"
                                                    style="cursor: pointer;">
                                                    Baca selengkapnya
                                                </summary>
                                                <div class="pengumuman-rich-content small text-slate-700 mt-2 p-3 rounded border bg-white">{!! $item->isi_aman !!}</div>
                                            </details>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-info px-2 py-1" style="font-size: 0.75rem;">
                                            <i class="fa fa-graduation-cap mr-1"></i>
                                            {{ ucfirst((string) ($item->jenis ?? \App\Models\Admin\Informasi\Pengumuman::JENIS_SISWA)) }}
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
                                                    <span class="text-success font-weight-bold ml-1">&bull; Aktif</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted italic">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('pengumuman.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Pengumuman</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('pengumuman.destroy', $item->id) }}" method="POST" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="action-dropdown-item action-dropdown-item--danger delete-button" role="menuitem">
                                                    <i class="fa fa-trash action-item-icon"></i>
                                                    <span>Hapus Pengumuman</span>
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

