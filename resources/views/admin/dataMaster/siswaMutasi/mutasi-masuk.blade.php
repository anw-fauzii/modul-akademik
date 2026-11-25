@extends('layouts.appui')

@section('title')
    <title>Mutasi Masuk (Siswa Pindahan)</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #16a34a 0%, #15803d 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-add-user" style="color: white;"></i>
                    </div>
                    <div>
                        Mutasi Masuk (Siswa Pindahan Baru)
                        <div class="page-title-subheading">
                            Buat biodata siswa pindahan, tempatkan ke kelas tujuan secara opsional, dan catat riwayat sekolah asal sekaligus.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('siswa-mutasi.index') }}" class="btn btn-light border shadow-sm font-weight-bold"
                        style="border-radius: 8px;">
                        <i class="fa fa-arrow-left mr-1"></i> Kembali ke Ledger
                    </a>
                </div>
            </div>
        </div>

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger shadow-sm" style="border-radius: 10px;">
                <i class="fa fa-exclamation-triangle mr-1"></i>
                <strong>Periksa kembali input Anda:</strong>
                <ul class="mb-0 mt-1 pl-3 small">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('siswa-mutasi.masuk.store') }}" id="mutasiMasukForm" enctype="multipart/form-data">
            @csrf

            <!-- Bagian Khusus Dokumen Mutasi Masuk -->
            <div class="main-card card mb-3 shadow-sm">
                <div class="card-header bg-white font-weight-bold text-success">
                    <i class="fa fa-exchange-alt mr-1"></i> Dokumen &amp; Penempatan Mutasi Masuk
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="akademik_master_tahun_ajaran_id" class="font-weight-bold small">
                                    Tahun Ajaran Masuk <span class="text-danger">*</span>
                                </label>
                                <select name="akademik_master_tahun_ajaran_id" id="akademik_master_tahun_ajaran_id"
                                    class="form-control @error('akademik_master_tahun_ajaran_id') is-invalid @enderror" required>
                                    @foreach ($tahunAjaranOptions as $ta)
                                        @php
                                            $isTaSelected = old('akademik_master_tahun_ajaran_id')
                                                ? ((string) old('akademik_master_tahun_ajaran_id') === (string) $ta->id || (string) old('akademik_master_tahun_ajaran_id') === (string) $ta->sqid)
                                                : ((int) $activeTahunAjaranId === (int) $ta->id);
                                        @endphp
                                        <option value="{{ $ta->sqid }}" @selected($isTaSelected)>
                                            {{ $ta->nama_tahun_ajaran }} ({{ $ta->semester }}){{ $ta->is_active ? ' ★ Aktif' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('akademik_master_tahun_ajaran_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="tanggal_efektif" class="font-weight-bold small">
                                    Tanggal Efektif Masuk <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tanggal_efektif" id="tanggal_efektif"
                                    class="form-control @error('tanggal_efektif') is-invalid @enderror"
                                    value="{{ old('tanggal_efektif', now()->toDateString()) }}" required>
                                @error('tanggal_efektif')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="kelas_tujuan_id" class="font-weight-bold small">Kelas Tujuan (Opsional)</label>
                                <select name="kelas_tujuan_id" id="kelas_tujuan_id"
                                    class="form-control @error('kelas_tujuan_id') is-invalid @enderror">
                                    <option value="">-- Belum Ditempatkan / Atur Nanti --</option>
                                    @foreach ($kelas as $itemKelas)
                                        @php
                                            $oldKelas = (string) old('kelas_tujuan_id');
                                            $isKelasSelected = $oldKelas !== '' && ($oldKelas === (string) $itemKelas->id || $oldKelas === (string) $itemKelas->sqid);
                                        @endphp
                                        <option value="{{ $itemKelas->sqid }}" @selected($isKelasSelected)>
                                            {{ $itemKelas->nama_kelas }} ({{ $itemKelas->jenjang_kelas }})
                                            @if ($itemKelas->tahunAjaran)
                                                - {{ $itemKelas->tahunAjaran->nama_tahun_ajaran }} ({{ $itemKelas->tahunAjaran->semester }})
                                            @endif
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted">
                                    Bila dipilih, siswa otomatis tercatat di Anggota Kelas dengan status <em>Pindahan</em>.
                                </small>
                                @error('kelas_tujuan_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="sekolah_asal_tujuan" class="font-weight-bold small">
                                    Nama Sekolah Asal <span class="text-danger">*</span>
                                </label>
                                <input type="text" name="sekolah_asal_tujuan" id="sekolah_asal_tujuan"
                                    class="form-control @error('sekolah_asal_tujuan') is-invalid @enderror"
                                    placeholder="Contoh: SDIT Al-Hikmah Bandung"
                                    value="{{ old('sekolah_asal_tujuan') }}" required>
                                @error('sekolah_asal_tujuan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="nomor_surat" class="font-weight-bold small">Nomor Surat Pindah Dari Sekolah Asal</label>
                                <input type="text" name="nomor_surat" id="nomor_surat"
                                    class="form-control @error('nomor_surat') is-invalid @enderror"
                                    placeholder="Opsional, mis: 421.2/102/SD/2026"
                                    value="{{ old('nomor_surat') }}">
                                @error('nomor_surat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="col-md-12">
                            <div class="form-group mb-0">
                                <label for="alasan" class="font-weight-bold small">Catatan / Alasan Mutasi Masuk</label>
                                <input type="text" name="alasan" id="alasan"
                                    class="form-control @error('alasan') is-invalid @enderror"
                                    placeholder="Opsional, mis: Pindah domisili orang tua"
                                    value="{{ old('alasan') }}">
                                @error('alasan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reuse Form Biodata Siswa Secara DRY -->
            <div class="main-card card shadow-sm">
                <div class="card-header bg-white font-weight-bold">
                    <i class="pe-7s-id text-warning mr-1"></i> Biodata Lengkap Siswa Pindahan
                </div>
                <div class="card-body">
                    @include('admin.dataMaster.siswa._form', [
                        'hideMutasiHandledFields' => true,
                        'submitLabel' => 'Simpan Mutasi Masuk & Biodata Siswa',
                    ])
                </div>
            </div>
        </form>
    </div>
@endsection
