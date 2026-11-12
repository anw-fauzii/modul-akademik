@extends('layouts.appui')

@section('title')
    <title>Detail Kelas - {{ $kelas->nama_kelas }}</title>
@endsection

@include('admin.dataMaster.partials._group_show_styles')

@section('content')
    @php
        $jenjangBadge = $kelas->jenjang_badge_class;
        $shouldOpenModal = $errors->any();
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-id" style="color: white;"></i>
                    </div>
                    <div>
                        Detail Kelas &amp; Anggota Rombel
                        <div class="page-title-subheading">
                            Daftar siswa anggota kelas {{ $kelas->nama_kelas }}. Klik Tambah Anggota untuk memasukkan siswa baru.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                    <a href="{{ route('kelas.edit', $kelas->id) }}" class="btn btn-primary shadow-sm"
                        style="border-radius: 8px;">
                        <i class="fa fa-edit mr-1"></i> Edit Kelas
                    </a>
                    <a href="{{ route('kelas.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>

        <!-- CLASS HERO SUMMARY -->
        <div class="kelas-hero mb-4">
            <div class="row align-items-center">
                <div class="col-md-auto mb-3 mb-md-0 d-flex justify-content-center">
                    <div class="kelas-hero-icon">
                        {{ $kelas->romawi ?: $kelas->tingkatan_kelas }}
                    </div>
                </div>

                <div class="col-md text-center text-md-left mb-3 mb-md-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start mb-2" style="gap: 0.4rem;">
                        <span class="badge {{ $jenjangBadge }} px-2 py-1 font-weight-bold">
                            {{ strtoupper((string) $kelas->jenjang_kelas) }}
                        </span>
                        <span class="badge badge-light text-dark px-2 py-1 font-weight-bold">
                            Tingkat {{ $kelas->tingkatan_kelas }}
                            @if ($kelas->romawi)
                                ({{ $kelas->romawi }})
                            @endif
                        </span>
                        @if ($kelas->tahunAjaran)
                            <span class="badge {{ $kelas->tahunAjaran->is_active ? 'badge-success' : 'badge-secondary' }} px-2 py-1 font-weight-bold">
                                {{ $kelas->tahunAjaran->nama_tahun_ajaran }} • Smt {{ strtoupper((string) $kelas->tahunAjaran->semester) }}
                                {{ $kelas->tahunAjaran->is_active ? '(Aktif)' : '' }}
                            </span>
                        @endif
                    </div>

                    <h3 class="font-weight-bold text-white mb-2">
                        {{ $kelas->nama_kelas }}
                    </h3>

                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start text-white-50" style="font-size: 0.85rem; gap: 1rem;">
                        <span>
                            <i class="fa fa-user-tie text-warning mr-1"></i>
                            Wali Kelas: <strong class="text-white">{{ $kelas->waliKelas?->nama_gelar ?? 'Belum ditentukan' }}</strong>
                        </span>
                        <span>
                            <i class="fa fa-user-friends text-info mr-1"></i>
                            Pendamping: <strong class="text-white">{{ $kelas->pendamping?->nama_gelar ?? 'Belum ditentukan' }}</strong>
                        </span>
                    </div>
                </div>

                <div class="col-lg-auto">
                    <div class="kelas-stat-grid">
                        <div class="kelas-stat-pill">
                            <div class="kelas-stat-value">{{ $stats['total'] }}</div>
                            <div class="kelas-stat-label">Total Siswa</div>
                        </div>
                        <div class="kelas-stat-pill">
                            <div class="kelas-stat-value">{{ $stats['laki_laki'] }}</div>
                            <div class="kelas-stat-label">Laki-laki</div>
                        </div>
                        <div class="kelas-stat-pill">
                            <div class="kelas-stat-value">{{ $stats['perempuan'] }}</div>
                            <div class="kelas-stat-label">Perempuan</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CURRENT CLASS MEMBERS TABLE (PRIMARY VIEW) -->
        <div class="main-card card mb-3 shadow-sm border-0" id="daftar-anggota">
            <div class="card-body">
                <div class="kelas-section-title">
                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                        <span>
                            <i class="fa fa-users mr-2"></i> Daftar Anggota Kelas {{ $kelas->nama_kelas }}
                        </span>
                        <span class="badge badge-primary px-2 py-1" style="font-size: 0.78rem; text-transform: none;">
                            {{ $stats['total'] }} Anggota
                        </span>
                    </div>

                    <button type="button" id="openTambahAnggotaModalBtn"
                        data-open-ak-modal="tambahAnggotaModal"
                        class="btn btn-sm text-white shadow-sm font-weight-bold px-3"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; text-transform: none;">
                        <i class="fa fa-user-plus mr-1"></i> Tambah Anggota ({{ $kandidatSiswa->count() }} tersedia)
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="mb-0 table table-hover table-striped align-middle" id="myTable3"
                        data-empty-table="Belum ada siswa terdaftar di kelas ini. Klik Tambah Anggota untuk menambahkan siswa.">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Siswa</th>
                                <th class="text-center" style="width: 14%;">Jenis Kelamin</th>
                                <th class="text-center" style="width: 16%;">Pendaftaran</th>
                                <th class="text-center" style="width: 14%;">Status Siswa</th>
                                <th style="width: 16%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($anggotaKelas as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800" style="font-size: 0.95rem;">
                                            {{ $item->siswa?->nama_lengkap ?? '-' }}
                                        </div>
                                        <div class="small text-muted" style="font-size: 11px;">
                                            NIS: <span class="font-weight-bold">{{ $item->siswa_nis }}</span>
                                            @if ($item->siswa?->nisn)
                                                • NISN: {{ $item->siswa->nisn }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if ($item->siswa?->jenis_kelamin)
                                            <span class="badge badge-info px-2 py-1" style="font-size: 0.75rem;">
                                                {{ $item->siswa->jenis_kelamin_label }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->pendaftaran_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ $item->pendaftaran }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($item->siswa?->status)
                                            <span class="badge {{ $item->siswa->status_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                                {{ $item->siswa->status }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <a href="{{ route('siswa.show', $item->siswa_nis) }}"
                                                class="btn btn-sm btn-info text-white shadow-sm mx-1"
                                                style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                data-toggle="tooltip" data-placement="top" title="Detail Siswa">
                                                <i class="fa fa-eye" style="font-size: 0.75rem;"></i>
                                            </a>

                                            <a href="{{ route('anggota-kelas.edit', ['anggota_kelas' => $item->id, 'from_kelas' => $kelas->id]) }}"
                                                class="btn btn-sm btn-primary shadow-sm mx-1"
                                                style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                data-toggle="tooltip" data-placement="top" title="Edit Anggota Kelas">
                                                <i class="fa fa-edit" style="font-size: 0.75rem;"></i>
                                            </a>

                                            <form action="{{ route('anggota-kelas.destroy', $item->id) }}" method="POST"
                                                class="delete-form d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="redirect_to_kelas" value="{{ $kelas->id }}">
                                                <button type="button"
                                                    class="btn btn-sm btn-warning shadow-sm delete-button mx-1 p-0 text-white"
                                                    style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                    data-toggle="tooltip" data-placement="top" title="Keluarkan dari Kelas">
                                                    <i class="fa fa-trash" style="font-size: 0.75rem;"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL TAMBAH ANGGOTA KELAS -->
    <div id="tambahAnggotaModal"
        class="ak-modal-overlay {{ $shouldOpenModal ? 'is-open' : '' }}"
        role="dialog" aria-modal="true" aria-labelledby="tambahAnggotaModalLabel"
        aria-hidden="{{ $shouldOpenModal ? 'false' : 'true' }}">
        <div class="ak-modal-dialog">
            <form method="post" action="{{ route('anggota-kelas.store') }}" id="kelasAddAnggotaForm"
                class="d-flex flex-column" style="min-height: 0; max-height: 92vh; overflow: hidden;">
                @csrf
                <input type="hidden" name="akademik_master_kelas_id" id="akademik_master_kelas_id"
                    value="{{ $kelas->id }}" data-tahun-ajaran-id="{{ $kelas->akademik_master_tahun_ajaran_id }}">
                <input type="hidden" name="redirect_to_kelas" value="{{ $kelas->id }}">

                <div class="ak-modal-header">
                    <div>
                        <h5 class="font-weight-bold text-slate-900 mb-1" id="tambahAnggotaModalLabel" style="font-size: 1.05rem;">
                            <i class="fa fa-user-plus text-warning mr-2"></i>
                            Tambah Anggota ke Kelas {{ $kelas->nama_kelas }}
                        </h5>
                        <div class="small text-muted">
                            Pilih satu atau beberapa siswa aktif yang akan dimasukkan ke rombongan belajar ini.
                        </div>
                    </div>
                    <button type="button" class="ak-modal-close" data-close-ak-modal="tambahAnggotaModal"
                        aria-label="Tutup modal">
                        &times;
                    </button>
                </div>

                <div class="ak-modal-body">
                    @if ($errors->any())
                        <div class="ak-alert-danger shadow-sm">
                            <div class="font-weight-bold mb-1" style="font-size: 0.875rem;">
                                <i class="fa fa-exclamation-triangle mr-1"></i> Periksa kembali isian berikut:
                            </div>
                            <ul class="mb-0" style="padding-left: 1.25rem; font-size: 0.82rem;">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <div class="row align-items-end">
                        <div class="col-md-6">
                            <div class="position-relative form-group">
                                <label class="font-weight-bold">Kelas &amp; Tahun Ajaran Tujuan</label>
                                <div class="kelas-target-box">
                                    <i class="fa fa-school mr-1"></i>
                                    {{ $kelas->nama_kelas }}
                                    ({{ strtoupper((string) $kelas->jenjang_kelas) }} - Tingkat {{ $kelas->tingkatan_kelas }})
                                    @if ($kelas->tahunAjaran)
                                        — {{ $kelas->tahunAjaran->nama_tahun_ajaran }} Smt {{ strtoupper((string) $kelas->tahunAjaran->semester) }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="position-relative form-group">
                                <label for="pendaftaran" class="font-weight-bold">
                                    Jenis Pendaftaran <span class="text-danger">*</span>
                                </label>
                                <select name="pendaftaran" id="pendaftaran"
                                    class="form-control @error('pendaftaran') is-invalid @enderror" required>
                                    <option value="">-- Pilih Jenis Pendaftaran --</option>
                                    @foreach (\App\Models\Admin\DataMaster\AnggotaKelas::PENDAFTARAN_OPTIONS as $opsi)
                                        <option value="{{ $opsi }}" @selected((string) old('pendaftaran') === (string) $opsi)>
                                            {{ $opsi }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('pendaftaran')
                                    <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="position-relative form-group mt-1 mb-0">
                        <label class="font-weight-bold">
                            Pilih Siswa Aktif <span class="text-danger">*</span>
                        </label>
                        @include('admin.dataMaster.anggotaKelas._siswa_picker', [
                            'siswa' => $kandidatSiswa,
                            'excludedTahunAjaranIds' => $excludedTahunAjaranIds,
                            'hiddenRegisteredCount' => $hiddenRegisteredCount,
                            'hiddenInactiveCount' => $hiddenInactiveCount,
                            'showHistoryFilter' => false,
                            'createRedirectUrl' => route('kelas.show', $kelas->id),
                        ])
                    </div>
                </div>

                <div class="ak-modal-footer">
                    <span class="small text-muted">
                        <i class="fa fa-check-circle text-success mr-1"></i>
                        {{ $kandidatSiswa->count() }} siswa aktif siap dipilih
                    </span>
                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                        <button type="button" class="btn btn-secondary shadow-sm font-weight-bold"
                            data-close-ak-modal="tambahAnggotaModal" style="border-radius: 8px;">
                            Batal
                        </button>
                        <button type="submit" class="btn text-white shadow-sm font-weight-bold px-4"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
                            <i class="fa fa-save mr-1"></i> Tambahkan ke Kelas {{ $kelas->nama_kelas }}
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    @include('admin.dataMaster.partials._ak_modal_script', [
        'modalId' => 'tambahAnggotaModal',
        'searchInputId' => 'filterSiswaInput',
    ])
@endsection
