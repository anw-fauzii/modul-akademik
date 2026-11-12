@extends('layouts.appui')

@section('title')
    @php
        $petugasTitle = $jemputan->petugas?->nama_gelar ?? ('NIPY: ' . $jemputan->karyawan_nipy);
    @endphp
    <title>Detail Jemputan - {{ $petugasTitle }}</title>
@endsection

@include('admin.dataMaster.partials._group_show_styles')

@section('content')
    @php
        $petugasNama = $jemputan->petugas?->nama_gelar ?? ('Petugas #' . $jemputan->karyawan_nipy);
        $shouldOpenModal = $errors->any();
        $isGratisPp = (int) $jemputan->harga_pp <= 0;
        $isGratisSetengah = (int) $jemputan->harga_setengah <= 0;
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-car" style="color: white;"></i>
                    </div>
                    <div>
                        Detail Layanan Jemputan &amp; Anggota
                        <div class="page-title-subheading">
                            Daftar anggota jemputan {{ $petugasNama }} berbasis keanggotaan kelas aktif. Klik Tambah Anggota untuk mendaftarkan siswa.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                    <a href="{{ route('jemputan.edit', $jemputan->id) }}" class="btn btn-primary shadow-sm"
                        style="border-radius: 8px;">
                        <i class="fa fa-edit mr-1"></i> Edit Jemputan
                    </a>
                    <a href="{{ route('jemputan.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>

        <!-- JEMPUTAN HERO SUMMARY -->
        <div class="kelas-hero mb-4">
            <div class="row align-items-center">
                <div class="col-md-auto mb-3 mb-md-0 d-flex justify-content-center">
                    <div class="kelas-hero-icon">
                        <i class="fa fa-bus" aria-hidden="true"></i>
                    </div>
                </div>

                <div class="col-md text-center text-md-left mb-3 mb-md-0">
                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start mb-2" style="gap: 0.4rem;">
                        <span class="badge {{ $isGratisPp ? 'badge-success' : 'badge-light text-dark' }} px-2 py-1 font-weight-bold">
                            <i class="fa fa-exchange-alt mr-1"></i>
                            Harga PP: {{ $jemputan->harga_pp_formatted }}
                        </span>
                        <span class="badge {{ $isGratisSetengah ? 'badge-success' : 'badge-light text-dark' }} px-2 py-1 font-weight-bold">
                            <i class="fa fa-route mr-1"></i>
                            Harga Setengah: {{ $jemputan->harga_setengah_formatted }}
                        </span>
                        @if ($jemputan->tahunAjaran)
                            <span class="badge {{ $jemputan->tahunAjaran->is_active ? 'badge-success' : 'badge-secondary' }} px-2 py-1 font-weight-bold">
                                {{ $jemputan->tahunAjaran->nama_tahun_ajaran }} • Smt {{ strtoupper((string) $jemputan->tahunAjaran->semester) }}
                                {{ $jemputan->tahunAjaran->is_active ? '(Aktif)' : '' }}
                            </span>
                        @endif
                    </div>

                    <h3 class="font-weight-bold text-white mb-2">
                        {{ $petugasNama }}
                    </h3>

                    <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start text-white-50" style="font-size: 0.85rem; gap: 1rem;">
                        <span>
                            <i class="fa fa-user-tie text-warning mr-1"></i>
                            Petugas / Driver: <strong class="text-white">{{ $petugasNama }}</strong>
                        </span>
                        @if ($jemputan->karyawan_nipy)
                            <span>
                                <i class="fa fa-id-badge text-info mr-1"></i>
                                NIPY: <strong class="text-white">{{ $jemputan->karyawan_nipy }}</strong>
                            </span>
                        @endif
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

        <!-- CURRENT JEMPUTAN MEMBERS TABLE (PRIMARY VIEW) -->
        <div class="main-card card mb-3 shadow-sm border-0" id="daftar-anggota">
            <div class="card-body">
                <div class="kelas-section-title">
                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                        <span>
                            <i class="fa fa-users mr-2"></i> Daftar Anggota Jemputan {{ $petugasNama }}
                        </span>
                        <span class="badge badge-primary px-2 py-1" style="font-size: 0.78rem; text-transform: none;">
                            {{ $stats['total'] }} Anggota
                        </span>
                    </div>

                    <button type="button" id="openTambahAnggotaModalBtn"
                        data-open-ak-modal="tambahAnggotaModal"
                        class="btn btn-sm text-white shadow-sm font-weight-bold px-3"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; text-transform: none;">
                        <i class="fa fa-user-plus mr-1"></i> Tambah Anggota ({{ $kandidatAnggotaKelas->count() }} tersedia)
                    </button>
                </div>

                <div class="table-responsive">
                    <table class="mb-0 table table-hover table-striped align-middle" id="myTable3"
                        data-empty-table="Belum ada siswa terdaftar di layanan jemputan ini. Klik Tambah Anggota untuk mendaftarkan siswa.">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th class="text-center" style="width: 12%;">Jenis Kelamin</th>
                                <th class="text-center" style="width: 12%;">Pendaftaran</th>
                                <th>Keterangan</th>
                                <th class="text-center" style="width: 11%;">Status Siswa</th>
                                <th style="width: 15%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($anggotaJemputan as $item)
                                @php
                                    $akModel = $item->anggotaKelas;
                                    $siswaModel = $akModel?->siswa;
                                    $kelasModel = $akModel?->kelas;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800" style="font-size: 0.95rem;">
                                            {{ $siswaModel?->nama_lengkap ?? '-' }}
                                        </div>
                                        <div class="small text-muted" style="font-size: 11px;">
                                            NIS: <span class="font-weight-bold">{{ $akModel?->siswa_nis ?? '-' }}</span>
                                            @if ($siswaModel?->nisn)
                                                • NISN: {{ $siswaModel->nisn }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($kelasModel)
                                            @if (Route::has('kelas.show') && $kelasModel->id)
                                                <a href="{{ route('kelas.show', $kelasModel->id) }}"
                                                    class="font-weight-bold text-slate-700 text-decoration-none">
                                                    {{ $kelasModel->nama_kelas }}
                                                </a>
                                            @else
                                                <div class="font-weight-bold text-slate-700">{{ $kelasModel->nama_kelas }}</div>
                                            @endif
                                            <div class="small text-muted">
                                                {{ strtoupper((string) $kelasModel->jenjang_kelas) }} • Tingkat {{ $kelasModel->tingkatan_kelas }}
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($siswaModel?->jenis_kelamin)
                                            <span class="badge badge-info px-2 py-1" style="font-size: 0.75rem;">
                                                {{ $siswaModel->jenis_kelamin_label }}
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
                                    <td>
                                        @if ($item->keterangan)
                                            <span class="small text-slate-700">{{ $item->keterangan }}</span>
                                        @else
                                            <span class="text-muted small">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($siswaModel?->status)
                                            <span class="badge {{ $siswaModel->status_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                                {{ $siswaModel->status }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center">
                                            @if ($akModel?->siswa_nis)
                                                <a href="{{ route('siswa.show', $akModel->siswa_nis) }}"
                                                    class="btn btn-sm btn-info text-white shadow-sm mx-1"
                                                    style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                    data-toggle="tooltip" data-placement="top" title="Detail Siswa">
                                                    <i class="fa fa-eye" style="font-size: 0.75rem;"></i>
                                                </a>
                                            @endif

                                            <a href="{{ route('anggota-jemputan.edit', ['anggota_jemputan' => $item->id, 'from_jemputan' => $jemputan->id]) }}"
                                                class="btn btn-sm btn-primary shadow-sm mx-1"
                                                style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                data-toggle="tooltip" data-placement="top" title="Edit Anggota Jemputan">
                                                <i class="fa fa-edit" style="font-size: 0.75rem;"></i>
                                            </a>

                                            <form action="{{ route('anggota-jemputan.destroy', $item->id) }}" method="POST"
                                                class="delete-form d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="redirect_to_jemputan" value="{{ $jemputan->id }}">
                                                <button type="button"
                                                    class="btn btn-sm btn-warning shadow-sm delete-button mx-1 p-0 text-white"
                                                    style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                    data-toggle="tooltip" data-placement="top" title="Keluarkan dari Jemputan">
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

    <!-- MODAL TAMBAH ANGGOTA JEMPUTAN -->
    <div id="tambahAnggotaModal"
        class="ak-modal-overlay {{ $shouldOpenModal ? 'is-open' : '' }}"
        role="dialog" aria-modal="true" aria-labelledby="tambahAnggotaModalLabel"
        aria-hidden="{{ $shouldOpenModal ? 'false' : 'true' }}">
        <div class="ak-modal-dialog">
            <form method="post" action="{{ route('anggota-jemputan.store') }}" id="jemputanAddAnggotaForm"
                class="d-flex flex-column" style="min-height: 0; max-height: 92vh; overflow: hidden;">
                @csrf
                <input type="hidden" name="akademik_master_jemputan_id" id="akademik_master_jemputan_id"
                    value="{{ $jemputan->id }}" data-tahun-ajaran-id="{{ $jemputan->akademik_master_tahun_ajaran_id }}">
                <input type="hidden" name="redirect_to_jemputan" value="{{ $jemputan->id }}">

                <div class="ak-modal-header">
                    <div>
                        <h5 class="font-weight-bold text-slate-900 mb-1" id="tambahAnggotaModalLabel" style="font-size: 1.05rem;">
                            <i class="fa fa-user-plus text-warning mr-2"></i>
                            Tambah Anggota ke Jemputan {{ $petugasNama }}
                        </h5>
                        <div class="small text-muted">
                            Pilih satu atau beberapa siswa aktif yang sudah memiliki Anggota Kelas pada tahun ajaran ini.
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
                        <div class="col-md-5">
                            <div class="position-relative form-group">
                                <label class="font-weight-bold">Layanan Jemputan &amp; Tahun Ajaran</label>
                                <div class="kelas-target-box">
                                    <i class="fa fa-bus mr-1"></i>
                                    {{ $petugasNama }}
                                    @if ($jemputan->tahunAjaran)
                                        — {{ $jemputan->tahunAjaran->nama_tahun_ajaran }} Smt {{ strtoupper((string) $jemputan->tahunAjaran->semester) }}
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="position-relative form-group">
                                <label for="pendaftaran" class="font-weight-bold">
                                    Jenis Pendaftaran <span class="text-danger">*</span>
                                </label>
                                <select name="pendaftaran" id="pendaftaran"
                                    class="form-control @error('pendaftaran') is-invalid @enderror" required>
                                    <option value="">-- Pilih --</option>
                                    @foreach (\App\Models\Admin\DataMaster\AnggotaJemputan::PENDAFTARAN_OPTIONS as $opsi)
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

                        <div class="col-md-4">
                            <div class="position-relative form-group">
                                <label for="keterangan" class="font-weight-bold">Keterangan</label>
                                <input type="text" name="keterangan" id="keterangan"
                                    maxlength="255"
                                    value="{{ old('keterangan') }}"
                                    placeholder="Opsional (mis. PP / 1/2 / Titik Jemput)"
                                    class="form-control @error('keterangan') is-invalid @enderror">
                                @error('keterangan')
                                    <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">
                                        {{ $message }}
                                    </div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="position-relative form-group mt-1 mb-0">
                        <label class="font-weight-bold">
                            Pilih Anggota Kelas Aktif <span class="text-danger">*</span>
                        </label>
                        @include('admin.dataMaster.partials._siswa_picker', [
                            'pickerMode' => 'jemputan',
                            'anggotaKelas' => $kandidatAnggotaKelas,
                            'excludedTahunAjaranIds' => $excludedTahunAjaranIds,
                            'hiddenRegisteredCount' => $hiddenRegisteredCount,
                            'hiddenInactiveCount' => $hiddenInactiveCount,
                            'hiddenWithoutKelasCount' => $hiddenWithoutKelasCount ?? 0,
                            'showHistoryFilter' => false,
                            'createRedirectUrl' => route('jemputan.show', $jemputan->id),
                        ])
                    </div>
                </div>

                <div class="ak-modal-footer">
                    <span class="small text-muted">
                        <i class="fa fa-check-circle text-success mr-1"></i>
                        {{ $kandidatAnggotaKelas->count() }} siswa aktif siap dipilih
                    </span>
                    <div class="d-flex align-items-center" style="gap: 0.5rem;">
                        <button type="button" class="btn btn-secondary shadow-sm font-weight-bold"
                            data-close-ak-modal="tambahAnggotaModal" style="border-radius: 8px;">
                            Batal
                        </button>
                        <button type="submit" class="btn text-white shadow-sm font-weight-bold px-4"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
                            <i class="fa fa-save mr-1"></i> Tambahkan ke Jemputan
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

