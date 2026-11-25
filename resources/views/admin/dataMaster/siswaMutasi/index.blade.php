@extends('layouts.appui')

@section('title')
    <title>Mutasi &amp; Kelulusan Siswa</title>
@endsection

@pushOnce('css')
    <style>
        .mutasi-stat-card {
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
            text-decoration: none !important;
            color: inherit;
            transition: all 0.2s ease;
        }

        .mutasi-stat-card:hover {
            transform: translateY(-2px);
            border-color: #f97316;
            box-shadow: 0 10px 20px -6px rgba(234, 88, 12, 0.16);
        }

        .mutasi-stat-card.is-active-filter {
            border-color: #ea580c;
            background: #fff7ed;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.14);
        }

        .mutasi-stat-label {
            font-size: 0.72rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .mutasi-stat-value {
            font-size: 1.55rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.15;
            margin-top: 3px;
            font-variant-numeric: tabular-nums;
        }

        .mutasi-stat-sub {
            font-size: 0.72rem;
            color: #64748b;
            margin-top: 3px;
        }

        .mutasi-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        .mutasi-unit-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .mutasi-unit-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 5px 12px;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            text-decoration: none !important;
            transition: all 0.15s ease;
        }

        .mutasi-unit-pill:hover {
            border-color: #ea580c;
            color: #c2410c;
            background: #fff7ed;
        }

        .mutasi-unit-pill.is-active {
            background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);
            border-color: #ea580c;
            color: #ffffff;
        }

        .mutasi-prompt-card {
            border: 2px dashed #cbd5e1;
            border-radius: 14px;
            background: #ffffff;
            padding: 2.5rem 1.5rem;
            text-align: center;
        }
    </style>
@endPushOnce

@section('content')
    @php
        $queryTaParam = '';
        if ($filters['tahun_ajaran_id'] !== '') {
            $queryTaParam = \App\Support\Sqid::encodeFor(
                \App\Models\Admin\DataMaster\TahunAjaran::class,
                (int) $filters['tahun_ajaran_id']
            ) ?? $filters['tahun_ajaran_id'];
        }

        $keluarGabunganTotal = ($statistics['total_keluar'] ?? 0) + ($statistics['total_nonaktif'] ?? 0);
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-shuffle" style="color: white;"></i>
                    </div>
                    <div>
                        Mutasi &amp; Kelulusan Siswa
                        <div class="page-title-subheading">
                            Pusat transaksi kelulusan massal, mutasi pindah/keluar/nonaktif, dan pencatatan siswa pindahan masuk.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                    <a href="{{ route('siswa-mutasi.kelulusan.create') }}"
                        class="btn btn-info text-white shadow-sm font-weight-bold" style="border-radius: 8px;">
                        <i class="fa fa-graduation-cap mr-1"></i> Kelulusan Massal
                    </a>
                    <a href="{{ route('siswa-mutasi.keluar.create') }}"
                        class="btn btn-warning text-dark shadow-sm font-weight-bold" style="border-radius: 8px;">
                        <i class="fa fa-sign-out-alt mr-1"></i> Mutasi Keluar
                    </a>
                    <a href="{{ route('siswa-mutasi.masuk.create') }}"
                        class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
                        <i class="fa fa-user-plus mr-1"></i> Mutasi Masuk
                    </a>
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
            <div class="alert alert-danger shadow-sm" role="alert" style="border-radius: 10px;">
                <i class="fa fa-exclamation-triangle mr-1"></i>
                <strong>Gagal memproses pembatalan:</strong> {{ $errors->first() }}
            </div>
        @endif

        <!-- Kartu Statistik Ringkas -->
        <div class="row mb-3">
            <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                <a href="{{ route('siswa-mutasi.index', array_filter(['kategori' => 'lulus', 'tahun_ajaran_id' => $queryTaParam, 'unit' => $filters['unit']])) }}"
                    class="mutasi-stat-card {{ $filters['kategori'] === 'lulus' ? 'is-active-filter' : '' }}">
                    <div>
                        <div class="mutasi-stat-label">Kelulusan (Alumni)</div>
                        <div class="mutasi-stat-value">{{ number_format($statistics['total_lulus']) }}</div>
                        <div class="mutasi-stat-sub">Klik untuk filter Lulus</div>
                    </div>
                    <div class="mutasi-stat-icon bg-info text-white">
                        <i class="fa fa-graduation-cap"></i>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6 mb-3 mb-md-0">
                <a href="{{ route('siswa-mutasi.index', array_filter(['kategori' => 'pindah', 'tahun_ajaran_id' => $queryTaParam, 'unit' => $filters['unit']])) }}"
                    class="mutasi-stat-card {{ $filters['kategori'] === 'pindah' ? 'is-active-filter' : '' }}">
                    <div>
                        <div class="mutasi-stat-label">Pindah Sekolah</div>
                        <div class="mutasi-stat-value">{{ number_format($statistics['total_pindah']) }}</div>
                        <div class="mutasi-stat-sub">Klik untuk filter Pindah</div>
                    </div>
                    <div class="mutasi-stat-icon bg-warning text-dark">
                        <i class="fa fa-exchange-alt"></i>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6 mb-3 mb-sm-0">
                <a href="{{ route('siswa-mutasi.index', array_filter(['kategori' => 'keluar', 'tahun_ajaran_id' => $queryTaParam, 'unit' => $filters['unit']])) }}"
                    class="mutasi-stat-card {{ in_array($filters['kategori'], ['keluar', 'nonaktif'], true) ? 'is-active-filter' : '' }}">
                    <div>
                        <div class="mutasi-stat-label">Keluar / Nonaktif</div>
                        <div class="mutasi-stat-value">{{ number_format($keluarGabunganTotal) }}</div>
                        <div class="mutasi-stat-sub">{{ $statistics['total_keluar'] }} Keluar &bull; {{ $statistics['total_nonaktif'] }} Nonaktif</div>
                    </div>
                    <div class="mutasi-stat-icon bg-danger text-white">
                        <i class="fa fa-user-times"></i>
                    </div>
                </a>
            </div>
            <div class="col-md-3 col-sm-6">
                <a href="{{ route('siswa-mutasi.index', array_filter(['kategori' => 'masuk', 'tahun_ajaran_id' => $queryTaParam, 'unit' => $filters['unit']])) }}"
                    class="mutasi-stat-card {{ $filters['kategori'] === 'masuk' ? 'is-active-filter' : '' }}">
                    <div>
                        <div class="mutasi-stat-label">Mutasi Masuk</div>
                        <div class="mutasi-stat-value">{{ number_format($statistics['total_masuk']) }}</div>
                        <div class="mutasi-stat-sub">{{ $statistics['total_dibatalkan'] }} catatan dibatalkan</div>
                    </div>
                    <div class="mutasi-stat-icon bg-success text-white">
                        <i class="fa fa-user-plus"></i>
                    </div>
                </a>
            </div>
        </div>

        <!-- Strip Filter Unit -->
        @if (! empty($unitOptions))
            <div class="mutasi-unit-strip mb-3 shadow-sm">
                <span class="small font-weight-bold text-uppercase text-muted mr-1">
                    <i class="fa fa-school mr-1 text-warning"></i> Unit:
                </span>
                <a href="{{ route('siswa-mutasi.index', array_filter(['tahun_ajaran_id' => $queryTaParam, 'kategori' => $filters['kategori'], 'status_log' => $filters['status_log'] !== 'aktif' ? $filters['status_log'] : null, 'show_all' => $filters['show_all'] ? 1 : null])) }}"
                    class="mutasi-unit-pill {{ $filters['unit'] === '' ? 'is-active' : '' }}">
                    Semua ({{ number_format($statistics['total_aktif']) }})
                </a>
                @foreach ($unitOptions as $unitCode)
                    <a href="{{ route('siswa-mutasi.index', array_filter(['unit' => $unitCode, 'tahun_ajaran_id' => $queryTaParam, 'kategori' => $filters['kategori'], 'status_log' => $filters['status_log'] !== 'aktif' ? $filters['status_log'] : null])) }}"
                        class="mutasi-unit-pill {{ $filters['unit'] === $unitCode ? 'is-active' : '' }}">
                        {{ $unitCode }}
                        <span class="badge {{ $filters['unit'] === $unitCode ? 'badge-light text-dark' : 'badge-secondary' }}">
                            {{ number_format($statistics['by_unit'][$unitCode] ?? 0) }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif

        <!-- Panel Filter Ledger -->
        <div class="main-card card mb-3 shadow-sm">
            <div class="card-body pb-2">
                <form method="GET" action="{{ route('siswa-mutasi.index') }}">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <label for="filter_tahun_ajaran_id" class="small font-weight-bold text-muted mb-1">Tahun Ajaran</label>
                            <select name="tahun_ajaran_id" id="filter_tahun_ajaran_id" class="form-control form-control-sm">
                                <option value="">-- Semua Tahun Ajaran --</option>
                                @foreach ($tahunAjaranOptions as $ta)
                                    <option value="{{ $ta->sqid }}" @selected((string) $filters['tahun_ajaran_id'] === (string) $ta->id)>
                                        {{ $ta->nama_tahun_ajaran }} ({{ $ta->semester }}){{ $ta->is_active ? ' ★ Aktif' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label for="filter_unit" class="small font-weight-bold text-muted mb-1">Unit Jenjang</label>
                            <select name="unit" id="filter_unit" class="form-control form-control-sm">
                                <option value="">-- Semua Unit --</option>
                                @foreach ($unitOptions as $unitCode)
                                    <option value="{{ $unitCode }}" @selected($filters['unit'] === $unitCode)>{{ $unitCode }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label for="filter_kategori" class="small font-weight-bold text-muted mb-1">Kategori</label>
                            <select name="kategori" id="filter_kategori" class="form-control form-control-sm">
                                <option value="">-- Semua Kategori --</option>
                                @foreach ($kategoriOptions as $key => $label)
                                    <option value="{{ $key }}" @selected($filters['kategori'] === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2 mb-2">
                            <label for="filter_status_log" class="small font-weight-bold text-muted mb-1">Status Audit</label>
                            <select name="status_log" id="filter_status_log" class="form-control form-control-sm">
                                <option value="aktif" @selected($filters['status_log'] === 'aktif')>Aktif Berlaku</option>
                                <option value="dibatalkan" @selected($filters['status_log'] === 'dibatalkan')>Sudah Dibatalkan</option>
                                <option value="all" @selected($filters['status_log'] === 'all')>Semua Catatan</option>
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label for="filter_search" class="small font-weight-bold text-muted mb-1">Cari Siswa / SK / Sekolah</label>
                            <input type="text" name="search" id="filter_search" class="form-control form-control-sm"
                                placeholder="Nama, NIS, nomor surat..." value="{{ $filters['search'] }}">
                        </div>
                    </div>
                    <div class="d-flex flex-wrap align-items-center justify-content-between pt-2 pb-1 border-top mt-1" style="gap: 0.5rem;">
                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                            <button type="submit" class="btn btn-sm text-white font-weight-bold"
                                style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border: none; border-radius: 7px;">
                                <i class="fa fa-filter mr-1"></i> Terapkan Filter
                            </button>
                            <a href="{{ route('siswa-mutasi.index', ['show_all' => 1]) }}"
                                class="btn btn-sm btn-outline-secondary font-weight-bold" style="border-radius: 7px;">
                                <i class="fa fa-list mr-1"></i> Tampilkan Semua
                            </a>
                            @if ($hasActiveFilter)
                                <a href="{{ route('siswa-mutasi.index') }}"
                                    class="btn btn-sm btn-light border font-weight-bold" style="border-radius: 7px;">
                                    <i class="fa fa-undo mr-1"></i> Reset
                                </a>
                            @endif
                        </div>
                        <span class="small text-muted">
                            Menampilkan {{ $hasActiveFilter ? $mutasiList->count().' catatan' : 'ringkasan KPI (Filter-First)' }}
                        </span>
                    </div>
                </form>
            </div>
        </div>

        @if (! $hasActiveFilter)
            <div class="mutasi-prompt-card shadow-sm">
                <i class="fa fa-search text-warning mb-2" style="font-size: 2rem;"></i>
                <h5 class="font-weight-bold text-slate-800 mb-1">Pilih Filter atau Tampilkan Semua Catatan</h5>
                <p class="small text-muted mb-3">
                    Gunakan filter di atas atau klik salah satu kartu kategori / strip unit untuk menampilkan buku besar mutasi &amp; kelulusan siswa.
                </p>
                <div class="d-flex flex-wrap justify-content-center" style="gap: 0.5rem;">
                    <a href="{{ route('siswa-mutasi.index', ['show_all' => 1]) }}"
                        class="btn btn-sm text-white font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border: none; border-radius: 8px;">
                        <i class="fa fa-list mr-1"></i> Tampilkan Semua Riwayat Aktif
                    </a>
                </div>
            </div>
        @else
            <div class="main-card card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="myTable3" class="table table-striped table-bordered table-hover w-100" style="font-size: 0.85rem;">
                            <thead class="thead-light">
                                <tr>
                                    <th style="width: 4%;">#</th>
                                    <th style="width: 13%;">Tanggal &amp; TA</th>
                                    <th style="width: 19%;">Siswa</th>
                                    <th style="width: 13%;">Kategori</th>
                                    <th style="width: 13%;">Kelas</th>
                                    <th style="width: 18%;">Dokumen &amp; Sekolah</th>
                                    <th style="width: 12%;">Status Audit</th>
                                    <th style="width: 8%;" class="text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($mutasiList as $index => $item)
                                    <tr class="{{ $item->is_dibatalkan ? 'table-secondary' : '' }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <div class="font-weight-bold text-nowrap">
                                                {{ $item->tanggal_efektif?->translatedFormat('d M Y') ?? '-' }}
                                            </div>
                                            @if ($item->tahunAjaran)
                                                <div class="small text-muted">
                                                    {{ $item->tahunAjaran->nama_tahun_ajaran }} ({{ $item->tahunAjaran->semester }})
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->siswa)
                                                <a href="{{ route('siswa.show', $item->siswa) }}" class="font-weight-bold text-primary">
                                                    {{ $item->siswa->nama_lengkap }}
                                                </a>
                                                <div class="small text-muted">NIS: {{ $item->siswa_nis }}</div>
                                                <span class="badge {{ $item->siswa->status_badge_class }} mt-1">
                                                    Status saat ini: {{ $item->siswa->status }}
                                                </span>
                                            @else
                                                <span class="font-weight-bold">{{ $item->siswa_nis }}</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="badge {{ $item->kategori_badge_class }}">{{ $item->kategori_label }}</span>
                                            @if ($item->unit)
                                                <span class="badge badge-light border ml-1">{{ $item->unit }}</span>
                                            @endif
                                            <div class="small text-muted mt-1">
                                                {{ $item->status_sebelum ?? 'Baru' }} &rarr; {{ $item->status_sesudah }}
                                            </div>
                                        </td>
                                        <td>
                                            @if ($item->kelasAsal)
                                                <div><span class="text-muted small">Asal:</span> {{ $item->kelasAsal->nama_kelas }}</div>
                                            @endif
                                            @if ($item->kelasTujuan)
                                                <div><span class="text-muted small">Tujuan:</span> {{ $item->kelasTujuan->nama_kelas }}</div>
                                            @endif
                                            @if (! $item->kelasAsal && ! $item->kelasTujuan)
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->sekolah_asal_tujuan)
                                                <div class="font-weight-bold">{{ $item->sekolah_asal_tujuan }}</div>
                                            @endif
                                            @if ($item->nomor_surat)
                                                <div class="small text-muted">SK/Surat: {{ $item->nomor_surat }}</div>
                                            @endif
                                            @if ($item->nomor_ijazah)
                                                <div class="small text-muted">Ijazah: {{ $item->nomor_ijazah }}</div>
                                            @endif
                                            @if ($item->alasan)
                                                <div class="small text-dark mt-1">{{ \Illuminate\Support\Str::limit($item->alasan, 90) }}</div>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($item->is_dibatalkan)
                                                <span class="badge badge-danger">Dibatalkan</span>
                                                <div class="small text-muted mt-1">
                                                    {{ $item->dibatalkan_by_name ?? 'Admin' }}<br>
                                                    {{ $item->dibatalkan_at?->format('d/m/Y H:i') }}
                                                </div>
                                                @if ($item->alasan_batal)
                                                    <div class="small text-danger mt-1">{{ $item->alasan_batal }}</div>
                                                @endif
                                            @else
                                                <span class="badge badge-success">Aktif</span>
                                                @if ($item->created_by_name)
                                                    <div class="small text-muted mt-1">Oleh {{ $item->created_by_name }}</div>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="text-center text-nowrap">
                                            @if (! $item->is_dibatalkan)
                                                <button type="button"
                                                    class="btn btn-sm btn-outline-danger js-batalkan-mutasi"
                                                    data-action="{{ route('siswa-mutasi.batalkan', $item) }}"
                                                    data-nama="{{ $item->siswa?->nama_lengkap ?? $item->siswa_nis }}"
                                                    data-kategori="{{ $item->kategori_label }}"
                                                    style="border-radius: 6px;">
                                                    <i class="fa fa-undo mr-1"></i> Batalkan
                                                </button>
                                            @else
                                                <span class="small text-muted font-italic">Terkunci</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>

    <form id="batalkanMutasiForm" method="POST" style="display: none;">
        @csrf
        <input type="hidden" name="alasan_batal" id="batalkanAlasanInput">
    </form>
@endsection

@push('js')
    <script>
        $(document).ready(function() {
            @if ($hasActiveFilter)
                initDataTables('#myTable3');
            @endif

            function escapeHtml(str) {
                return $('<div>').text(String(str ?? '')).html();
            }

            $(document).on('click', '.js-batalkan-mutasi', function() {
                const actionUrl = $(this).data('action');
                const nama = $(this).data('nama') || 'siswa ini';
                const kategori = $(this).data('kategori') || 'mutasi';

                Swal.fire({
                    title: 'Batalkan Transaksi Mutasi?',
                    html: 'Status <strong>' + escapeHtml(nama) + '</strong> untuk catatan <strong>' + escapeHtml(kategori) + '</strong> akan dipulihkan kembali secara aman.<br><small class="text-muted">Riwayat akademik &amp; log pembatalan tetap tersimpan untuk audit.</small>',
                    icon: 'warning',
                    input: 'textarea',
                    inputLabel: 'Alasan Pembatalan (Wajib)',
                    inputPlaceholder: 'Contoh: Salah pilih kelas / siswa mengurungkan niat pindah...',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Ya, Batalkan & Pulihkan',
                    cancelButtonText: 'Tutup',
                    inputValidator: (value) => {
                        if (!value || value.trim().length < 5) {
                            return 'Tuliskan alasan pembatalan minimal 5 karakter.';
                        }
                        return null;
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const form = document.getElementById('batalkanMutasiForm');
                        document.getElementById('batalkanAlasanInput').value = result.value.trim();
                        form.action = actionUrl;
                        form.submit();
                    }
                });
            });
        });
    </script>
@endpush
