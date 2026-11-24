@extends('layouts.appui')

@section('title')
    <title>Pendataan Prestasi Siswa</title>
@endsection

@pushOnce('css')
    <style>
        .prestasi-stat-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            padding: 15px 18px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.04);
            height: 100%;
        }

        .prestasi-stat-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .prestasi-stat-value {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.15;
            margin-top: 3px;
            font-variant-numeric: tabular-nums;
        }

        .prestasi-stat-sub {
            font-size: 0.72rem;
            color: #64748b;
            margin-top: 3px;
        }

        .prestasi-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .prestasi-filter-grid {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 0.85rem;
            align-items: end;
        }

        @media (min-width: 640px) {
            .prestasi-filter-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 992px) {
            .prestasi-filter-grid {
                grid-template-columns: minmax(210px, 1.35fr) minmax(150px, 0.85fr) minmax(160px, 0.9fr) minmax(210px, 1.3fr);
            }
        }

        .prestasi-thumb-btn {
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            padding: 2px;
            border-radius: 10px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.15s ease, border-color 0.15s ease;
        }

        .prestasi-thumb-btn:hover {
            transform: scale(1.04);
            border-color: #ea580c;
        }

        .prestasi-thumb-img {
            width: 46px;
            height: 46px;
            border-radius: 8px;
            object-fit: cover;
            display: block;
        }

        .prestasi-rank-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #c2410c;
            font-size: 0.75rem;
            font-weight: 700;
        }

        .prestasi-beregu-trigger {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.12rem 0.5rem;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: 0.69rem;
            font-weight: 700;
            cursor: pointer;
            transition: background-color 0.14s ease, border-color 0.14s ease;
        }

        .prestasi-beregu-trigger:hover {
            background: #ffedd5;
            border-color: #fdba74;
        }

        .prestasi-team-note {
            margin-top: 0.3rem;
            padding: 0.25rem 0.55rem;
            border-radius: 8px;
            background: #f8fafc;
            border-left: 3px solid #f97316;
            font-size: 0.74rem;
            color: #475569;
            max-width: 360px;
        }

        .prestasi-member-modal-list {
            max-height: 300px;
            overflow-y: auto;
            text-align: left;
            display: flex;
            flex-direction: column;
            gap: 0.45rem;
            padding-right: 2px;
        }

        .prestasi-member-modal-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.6rem;
            padding: 0.5rem 0.75rem;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            text-decoration: none !important;
            color: #0f172a;
            transition: border-color 0.14s ease, background-color 0.14s ease;
        }

        .prestasi-member-modal-item:hover {
            border-color: #fdba74;
            background: #fff7ed;
        }
    </style>
@endPushOnce

@section('content')
    @php
        $prestasi = $prestasi ?? collect();
        $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
        $kategoriOptions = $kategoriOptions ?? \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_OPTIONS;
        $tingkatOptions = $tingkatOptions ?? \App\Models\Admin\Prestasi\PrestasiSiswa::TINGKAT_PRESETS;
        $allFilterKey = \App\Services\Admin\Prestasi\PrestasiSiswaService::FILTER_ALL;
        $filters = $filters ?? [
            'tahun_ajaran_id' => $allFilterKey,
            'kategori' => $allFilterKey,
            'tingkat' => $allFilterKey,
            'siswa_nis' => '',
            'search' => '',
        ];
        $hasActiveFilter = $hasActiveFilter ?? false;
        $statistics = $statistics ?? [
            'total' => $prestasi->count(),
            'akademik' => $prestasi->where('kategori', \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_AKADEMIK)->count(),
            'non_akademik' => $prestasi->where('kategori', \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_NON_AKADEMIK)->count(),
            'beregu' => $prestasi->filter(fn ($p) => (bool) $p->is_beregu)->count(),
            'dengan_foto' => $prestasi->filter(fn ($p) => filled($p->foto))->count(),
        ];
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="fa fa-trophy" style="color: white;"></i>
                    </div>
                    <div>
                        Pendataan Prestasi Siswa
                        <div class="page-title-subheading">
                            Kelola rekam jejak prestasi akademik &amp; non-akademik siswa beserta catatan tim/beregu dan dokumentasi foto.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('prestasi-siswa.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH PRESTASI
                    </a>
                </div>
            </div>
        </div>

        <!-- STATISTIK RINGKAS -->
        <div class="row mb-3">
            <div class="col-sm-6 col-lg-3 mb-2 mb-lg-0">
                <div class="prestasi-stat-card">
                    <div>
                        <div class="prestasi-stat-label">Total Prestasi</div>
                        <div class="prestasi-stat-value">{{ $statistics['total'] }}</div>
                        <div class="prestasi-stat-sub">{{ $statistics['beregu'] }} beregu / {{ max(0, $statistics['total'] - $statistics['beregu']) }} individu</div>
                    </div>
                    <span class="prestasi-stat-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-trophy"></i>
                    </span>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3 mb-2 mb-lg-0">
                <div class="prestasi-stat-card">
                    <div>
                        <div class="prestasi-stat-label">Kategori Akademik</div>
                        <div class="prestasi-stat-value" style="color: #1d4ed8;">{{ $statistics['akademik'] }}</div>
                        <div class="prestasi-stat-sub">Sains, olimpiade &amp; mapel</div>
                    </div>
                    <span class="prestasi-stat-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-graduation-cap"></i>
                    </span>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3 mb-2 mb-sm-0">
                <div class="prestasi-stat-card">
                    <div>
                        <div class="prestasi-stat-label">Non-Akademik</div>
                        <div class="prestasi-stat-value" style="color: #047857;">{{ $statistics['non_akademik'] }}</div>
                        <div class="prestasi-stat-sub">Olahraga, seni, tahfidz &amp; ekskul</div>
                    </div>
                    <span class="prestasi-stat-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa fa-medal"></i>
                    </span>
                </div>
            </div>

            <div class="col-sm-6 col-lg-3">
                <div class="prestasi-stat-card">
                    <div>
                        <div class="prestasi-stat-label">Dokumentasi Foto</div>
                        <div class="prestasi-stat-value" style="color: #6d28d9;">{{ $statistics['dengan_foto'] }}</div>
                        <div class="prestasi-stat-sub">Piagam / foto penyerahan</div>
                    </div>
                    <span class="prestasi-stat-icon" style="background: #f5f3ff; color: #7c3aed;">
                        <i class="fa fa-camera"></i>
                    </span>
                </div>
            </div>
        </div>

        <!-- FILTER & PENCARIAN -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter &amp; Pencarian Prestasi Siswa</span>
                    @if ($hasActiveFilter)
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <span class="filter-count-pill">
                    <i class="fa fa-trophy text-warning"></i>
                    Menampilkan: <strong>{{ $prestasi->count() }}</strong> prestasi
                </span>
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('prestasi-siswa.index') }}">
                    <div class="prestasi-filter-grid">
                        <div>
                            <label for="filter_tahun_ajaran_id" class="filter-label">Tahun Ajaran</label>
                            <select name="tahun_ajaran_id" id="filter_tahun_ajaran_id" class="filter-control">
                                <option value="{{ $allFilterKey }}"
                                    @selected(($filters['tahun_ajaran_id'] ?? $allFilterKey) === $allFilterKey)>
                                    Semua Tahun Ajaran
                                </option>
                                @foreach ($tahunAjaranOptions as $ta)
                                    <option value="{{ $ta->sqid }}"
                                        @selected((string) ($filters['tahun_ajaran_id'] ?? '') === (string) $ta->id)>
                                        {{ $ta->nama_tahun_ajaran }} - Sem {{ strtoupper($ta->semester) }}
                                        {{ $ta->is_active ? '(Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="filter_kategori" class="filter-label">Kategori</label>
                            <select name="kategori" id="filter_kategori" class="filter-control">
                                <option value="{{ $allFilterKey }}"
                                    @selected(($filters['kategori'] ?? $allFilterKey) === $allFilterKey)>
                                    Semua Kategori
                                </option>
                                @foreach ($kategoriOptions as $katKey => $katLabel)
                                    <option value="{{ $katKey }}"
                                        @selected(($filters['kategori'] ?? '') === $katKey)>
                                        {{ $katLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="filter_tingkat" class="filter-label">Tingkat</label>
                            <select name="tingkat" id="filter_tingkat" class="filter-control">
                                <option value="{{ $allFilterKey }}"
                                    @selected(($filters['tingkat'] ?? $allFilterKey) === $allFilterKey)>
                                    Semua Tingkat
                                </option>
                                @foreach ($tingkatOptions as $tOpt)
                                    <option value="{{ $tOpt }}"
                                        @selected(($filters['tingkat'] ?? '') === $tOpt)>
                                        {{ $tOpt }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="filter_search" class="filter-label">Cari Siswa / Tim / Prestasi</label>
                            <input type="text" name="search" id="filter_search" class="filter-control"
                                placeholder="Nama siswa, tim, lomba, penyelenggara..."
                                value="{{ $filters['search'] ?? '' }}">
                        </div>
                    </div>

                    <div class="filter-footer">
                        <div class="small text-muted">
                            @if ($hasActiveFilter)
                                Filter aktif diterapkan pada daftar prestasi di bawah.
                            @else
                                Saring data berdasarkan tahun ajaran, kategori, tingkat, atau kata kunci.
                            @endif
                        </div>

                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                            @if ($hasActiveFilter)
                                <a href="{{ route('prestasi-siswa.index') }}" class="filter-btn filter-btn-secondary">
                                    <i class="fa fa-sync-alt"></i> Reset Filter
                                </a>
                            @else
                                <a href="{{ route('prestasi-siswa.index') }}" class="filter-btn filter-btn-secondary">
                                    <i class="fa fa-list"></i> Semua Data
                                </a>
                            @endif
                            <button type="submit" class="filter-btn filter-btn-primary">
                                <i class="fa fa-filter"></i> Terapkan Filter
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- TABEL DAFTAR PRESTASI -->
        <div class="main-card card mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover table-striped align-middle" id="myTable3">
                        <thead>
                            <tr>
                                <th style="width: 4%;">No</th>
                                <th style="width: 24%;">Siswa / Tim</th>
                                <th>Nama Prestasi &amp; Penyelenggara</th>
                                <th class="text-center" style="width: 11%;">Kategori</th>
                                <th style="width: 15%;">Peringkat &amp; Tingkat</th>
                                <th style="width: 11%;">Tanggal</th>
                                <th class="text-center" style="width: 8%;">Foto</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($prestasi as $item)
                                @php
                                    $siswaCollection = $item->relationLoaded('siswa') ? $item->siswa : collect();
                                    $siswaCount = $siswaCollection->count();
                                    $primarySiswa = $siswaCollection->first();
                                    $taModel = $item->relationLoaded('tahunAjaran') ? $item->tahunAjaran : null;
                                    $fotoUrl = $item->foto_url;
                                    $cleanKeterangan = trim(preg_replace('/\s+/', ' ', (string) $item->keterangan) ?? '');
                                    $teamDisplayTitle = $cleanKeterangan !== ''
                                        ? \Illuminate\Support\Str::limit($cleanKeterangan, 44)
                                        : 'Tim Beregu';
                                    $membersPayload = $siswaCount > 1
                                        ? $siswaCollection->map(static function ($anggota): array {
                                            $kelasModel = $anggota->relationLoaded('kelas') ? $anggota->kelas : null;

                                            return [
                                                'nis' => (string) $anggota->nis,
                                                'nama' => (string) $anggota->nama_lengkap,
                                                'kelas' => $kelasModel
                                                    ? $kelasModel->nama_kelas.' ('.$kelasModel->jenjang_kelas.')'
                                                    : '-',
                                                'url' => route('siswa.show', $anggota),
                                            ];
                                        })->values()->all()
                                        : [];
                                    $searchCellText = trim($cleanKeterangan.' '.$item->daftar_siswa_ringkas.' '.$siswaCollection->pluck('nis')->implode(' '));
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td data-search="{{ $searchCellText }}">
                                        @if ($siswaCount > 1)
                                            <div class="d-flex align-items-center" style="gap: 0.65rem;">
                                                <div class="d-inline-flex align-items-center justify-content-center font-weight-bold"
                                                    style="width: 36px; height: 36px; border-radius: 999px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.85rem; flex-shrink: 0;">
                                                    <i class="fa fa-users"></i>
                                                </div>
                                                <div style="min-width: 0; flex: 1;">
                                                    <a href="{{ route('prestasi-siswa.show', $item) }}"
                                                        class="font-weight-bold text-slate-800 text-decoration-none d-block text-truncate"
                                                        style="max-width: 220px;"
                                                        title="{{ $cleanKeterangan !== '' ? $cleanKeterangan : $item->daftar_siswa_ringkas }}">
                                                        {{ $teamDisplayTitle }}
                                                    </a>
                                                    <div class="mt-1">
                                                        <button type="button"
                                                            class="prestasi-beregu-trigger js-prestasi-members"
                                                            data-title="{{ $item->nama_prestasi }}"
                                                            data-members="{{ json_encode($membersPayload) }}"
                                                            title="Klik untuk melihat daftar {{ $siswaCount }} anggota tim">
                                                            <i class="fa fa-users"></i>
                                                            <span>Beregu &bull; {{ $siswaCount }} Siswa</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @elseif ($primarySiswa)
                                            @php
                                                $kelasPrimary = $primarySiswa->relationLoaded('kelas') ? $primarySiswa->kelas : null;
                                            @endphp
                                            <div class="d-flex align-items-center" style="gap: 0.65rem;">
                                                @if ($primarySiswa->avatar_url)
                                                    <img src="{{ $primarySiswa->avatar_url }}" alt="{{ $primarySiswa->nama_lengkap }}"
                                                        style="width: 36px; height: 36px; border-radius: 999px; object-fit: cover; flex-shrink: 0;">
                                                @else
                                                    <div class="d-inline-flex align-items-center justify-content-center font-weight-bold"
                                                        style="width: 36px; height: 36px; border-radius: 999px; background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; font-size: 0.8rem; flex-shrink: 0;">
                                                        {{ $primarySiswa->inisial_nama }}
                                                    </div>
                                                @endif
                                                <div style="min-width: 0;">
                                                    <a href="{{ route('siswa.show', $primarySiswa) }}"
                                                        class="font-weight-bold text-slate-800 text-decoration-none d-block text-truncate"
                                                        title="Lihat profil {{ $primarySiswa->nama_lengkap }}">
                                                        {{ $primarySiswa->nama_lengkap }}
                                                    </a>
                                                    <div class="small text-muted">
                                                        NIS: <span class="font-mono">{{ $primarySiswa->nis }}</span>
                                                        @if ($kelasPrimary)
                                                            &bull; {{ $kelasPrimary->nama_kelas }} ({{ $kelasPrimary->jenjang_kelas }})
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('prestasi-siswa.show', $item) }}"
                                            class="font-weight-bold text-slate-800 text-decoration-none"
                                            style="font-size: 0.92rem;">
                                            {{ $item->nama_prestasi }}
                                        </a>
                                        <div class="small text-muted mt-1">
                                            <i class="fa fa-building mr-1 text-secondary"></i>{{ $item->penyelenggara }}
                                            @if ($taModel)
                                                &bull; <span class="text-slate-600">{{ $taModel->nama_tahun_ajaran }} (Sem {{ strtoupper($taModel->semester) }})</span>
                                            @endif
                                        </div>
                                        @if ($siswaCount <= 1 && filled($item->keterangan))
                                            <div class="prestasi-team-note text-truncate" title="{{ $cleanKeterangan }}">
                                                <i class="fa fa-info-circle text-warning mr-1"></i>{{ $item->ringkasan_keterangan }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->kategori_badge_class }} px-2 py-1" style="font-size: 0.74rem;">
                                            {{ $item->kategori_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="mb-1">
                                            <span class="prestasi-rank-pill">
                                                <i class="fa fa-award"></i> {{ $item->peringkat }}
                                            </span>
                                        </div>
                                        <div>
                                            <span class="badge {{ $item->tingkat_badge_class }} px-2 py-1" style="font-size: 0.71rem;">
                                                Tingkat {{ $item->tingkat }}
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-weight-600 text-slate-700 small">
                                            {{ $item->tanggal_formatted }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($fotoUrl)
                                            <button type="button" class="prestasi-thumb-btn js-prestasi-lightbox"
                                                data-foto-url="{{ $fotoUrl }}"
                                                data-foto-title="{{ $item->nama_prestasi }} — {{ $siswaCount > 1 ? $teamDisplayTitle : $item->daftar_siswa_ringkas }}"
                                                title="Klik untuk memperbesar foto">
                                                <img src="{{ $fotoUrl }}" alt="Foto {{ $item->nama_prestasi }}" class="prestasi-thumb-img">
                                            </button>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('prestasi-siswa.show', $item) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-eye text-info action-item-icon"></i>
                                                <span>Detail Prestasi</span>
                                            </a>
                                            <a href="{{ route('prestasi-siswa.edit', $item) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Prestasi</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('prestasi-siswa.destroy', $item) }}" method="post" class="m-0">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="action-dropdown-item is-danger delete-button" role="menuitem"
                                                    data-nama="{{ $item->nama_prestasi }} ({{ $siswaCount > 1 ? $teamDisplayTitle : $item->daftar_siswa_ringkas }})">
                                                    <i class="fa fa-trash text-danger action-item-icon"></i>
                                                    <span>Hapus Prestasi</span>
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

@push('js')
    <script>
        $(document).ready(function() {
            initDataTables('#myTable3');

            function escapeHtml(str) {
                return $('<div>').text(String(str ?? '')).html();
            }

            $(document).on('click', '.js-prestasi-members', function() {
                const title = $(this).data('title') || 'Anggota Tim Prestasi';
                let members = [];
                try {
                    const raw = $(this).attr('data-members');
                    members = raw ? JSON.parse(raw) : [];
                } catch (err) {
                    members = [];
                }

                const itemsHtml = Array.isArray(members) && members.length > 0
                    ? '<div class="prestasi-member-modal-list">' + members.map(function(m, idx) {
                        return '<a href="' + escapeHtml(m.url) + '" class="prestasi-member-modal-item">'
                            + '<div>'
                            + '<div class="font-weight-bold small text-slate-800">' + (idx + 1) + '. ' + escapeHtml(m.nama) + '</div>'
                            + '<div class="text-muted" style="font-size: 0.72rem;">NIS ' + escapeHtml(m.nis) + ' &bull; ' + escapeHtml(m.kelas) + '</div>'
                            + '</div>'
                            + '<i class="fa fa-chevron-right text-muted" style="font-size: 0.7rem;"></i>'
                            + '</a>';
                    }).join('') + '</div>'
                    : '<div class="text-muted small">Belum ada anggota terdaftar.</div>';

                Swal.fire({
                    title: 'Anggota Tim (' + members.length + ' Siswa)',
                    html: '<div class="small text-muted mb-2">' + escapeHtml(title) + '</div>' + itemsHtml,
                    showCloseButton: true,
                    showConfirmButton: false,
                    width: '520px'
                });
            });

            $(document).on('click', '.js-prestasi-lightbox', function() {
                const url = $(this).data('foto-url');
                const title = $(this).data('foto-title') || 'Dokumentasi Prestasi Siswa';

                Swal.fire({
                    title: title,
                    imageUrl: url,
                    imageAlt: title,
                    showCloseButton: true,
                    showConfirmButton: false,
                    width: '680px'
                });
            });

            $(document).on('click', '.delete-button', function(e) {
                e.preventDefault();
                const form = $(this).closest('form');
                const nama = $(this).data('nama') || 'prestasi ini';

                Swal.fire({
                    title: 'Konfirmasi Hapus',
                    html: 'Apakah Anda yakin ingin menghapus prestasi <strong>' + escapeHtml(nama) + '</strong> beserta foto dokumentasinya?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
