@extends('layouts.appui')

@section('title')
    <title>Mutasi Keluar Siswa</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-next-2" style="color: white;"></i>
                    </div>
                    <div>
                        Mutasi Keluar Siswa
                        <div class="page-title-subheading">
                            Catat siswa pindah sekolah, mengundurkan diri/keluar, atau nonaktif tanpa menghapus histori rapor &amp; presensi.
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

        <form method="POST" action="{{ route('siswa-mutasi.keluar.store') }}" id="mutasiKeluarForm">
            @csrf
            <div class="row">
                <!-- Kolom Kiri: Detail Mutasi -->
                <div class="col-lg-5 mb-3">
                    <div class="main-card card shadow-sm h-100">
                        <div class="card-header bg-white font-weight-bold">
                            <i class="fa fa-file-alt text-warning mr-1"></i> Detail Peristiwa Mutasi Keluar
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label for="kategori" class="font-weight-bold small">
                                    Jenis Mutasi Keluar <span class="text-danger">*</span>
                                </label>
                                <select name="kategori" id="kategori"
                                    class="form-control @error('kategori') is-invalid @enderror" required>
                                    @foreach ($kategoriOptions as $key => $label)
                                        <option value="{{ $key }}" @selected(old('kategori', 'pindah') === $key)>
                                            {{ $label }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('kategori')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="akademik_master_tahun_ajaran_id" class="font-weight-bold small">
                                    Tahun Ajaran <span class="text-danger">*</span>
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

                            <div class="form-group">
                                <label for="tanggal_efektif" class="font-weight-bold small">
                                    Tanggal Efektif Keluar / Pindah <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tanggal_efektif" id="tanggal_efektif"
                                    class="form-control @error('tanggal_efektif') is-invalid @enderror"
                                    value="{{ old('tanggal_efektif', now()->toDateString()) }}" required>
                                @error('tanggal_efektif')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group" id="sekolahTujuanGroup">
                                <label for="sekolah_asal_tujuan" class="font-weight-bold small">
                                    Sekolah Tujuan <span class="text-danger" id="sekolahTujuanRequiredMark">*</span>
                                </label>
                                <input type="text" name="sekolah_asal_tujuan" id="sekolah_asal_tujuan"
                                    class="form-control @error('sekolah_asal_tujuan') is-invalid @enderror"
                                    placeholder="Contoh: SD Negeri 1 Sukamaju"
                                    value="{{ old('sekolah_asal_tujuan') }}">
                                <small class="form-text text-muted">
                                    Wajib diisi bila kategori adalah Pindah Sekolah.
                                </small>
                                @error('sekolah_asal_tujuan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group">
                                <label for="nomor_surat" class="font-weight-bold small">Nomor Surat Keterangan Pindah / Mutasi</label>
                                <input type="text" name="nomor_surat" id="nomor_surat"
                                    class="form-control @error('nomor_surat') is-invalid @enderror"
                                    placeholder="Opsional, mis: 421/SKP/2026"
                                    value="{{ old('nomor_surat') }}">
                                @error('nomor_surat')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group mb-0">
                                <label for="alasan" class="font-weight-bold small">
                                    Alasan Mutasi Keluar <span class="text-danger">*</span>
                                </label>
                                <textarea name="alasan" id="alasan" rows="3"
                                    class="form-control @error('alasan') is-invalid @enderror"
                                    placeholder="Tuliskan alasan resmi (mis: Mengikuti kepindahan dinas orang tua ke luar kota)" required>{{ old('alasan') }}</textarea>
                                @error('alasan')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Pilih Siswa Aktif -->
                <div class="col-lg-7 mb-3">
                    <div class="main-card card shadow-sm h-100">
                        <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center" style="gap: 0.5rem;">
                            <div>
                                <span class="font-weight-bold">Pilih Siswa Aktif</span>
                                <span class="badge badge-light border ml-1">{{ $activeStudents->count() }} Aktif</span>
                            </div>
                            <span class="badge badge-dark py-2 px-3">
                                Dipilih: <span id="selectedOutCount">0</span> siswa
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6 mb-2 mb-md-0">
                                    <select id="clientFilterKelas" class="form-control form-control-sm">
                                        <option value="">-- Filter Kelas (Semua) --</option>
                                        @foreach ($kelasOptions as $itemKelas)
                                            <option value="{{ $itemKelas->id }}">
                                                {{ $itemKelas->nama_kelas }} ({{ $itemKelas->jenjang_kelas }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <input type="text" id="clientSearchSiswa" class="form-control form-control-sm"
                                        placeholder="Cari nama atau NIS siswa...">
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <small class="text-muted">Centang satu atau lebih siswa aktif:</small>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="toggleVisibleOutBtn">
                                    <i class="fa fa-check-double mr-1"></i> Pilih Semua Tampak
                                </button>
                            </div>

                            @if ($activeStudents->isEmpty())
                                <div class="text-center py-4 text-muted">
                                    Tidak ada siswa berstatus Aktif saat ini.
                                </div>
                            @else
                                @php
                                    $oldNis = array_map('strval', (array) old('siswa_nis', []));
                                @endphp
                                <div class="table-responsive border rounded" style="max-height: 410px; overflow-y: auto;">
                                    <table class="table table-sm table-hover mb-0" style="font-size: 0.85rem;">
                                        <thead class="thead-light">
                                            <tr>
                                                <th class="text-center" style="width: 46px;">Pilih</th>
                                                <th>NIS</th>
                                                <th>Nama Siswa</th>
                                                <th>Kelas Saat Ini</th>
                                            </tr>
                                        </thead>
                                        <tbody id="mutasiKeluarStudentRows">
                                            @foreach ($activeStudents as $siswa)
                                                @php
                                                    $kelasSiswa = $siswa->kelas;
                                                    $isChecked = in_array((string) $siswa->nis, $oldNis, true) || in_array((string) $siswa->sqid, $oldNis, true);
                                                @endphp
                                                <tr class="js-student-row"
                                                    data-kelas-id="{{ $siswa->akademik_master_kelas_id ?? '' }}"
                                                    data-search="{{ strtolower($siswa->nis.' '.$siswa->nama_lengkap) }}">
                                                    <td class="text-center align-middle">
                                                        <input type="checkbox"
                                                            class="js-out-checkbox"
                                                            name="siswa_nis[]"
                                                            value="{{ $siswa->sqid }}"
                                                            @checked($isChecked)>
                                                    </td>
                                                    <td class="align-middle text-nowrap font-weight-bold">{{ $siswa->nis }}</td>
                                                    <td class="align-middle">{{ $siswa->nama_lengkap }}</td>
                                                    <td class="align-middle">
                                                        @if ($kelasSiswa)
                                                            <span class="badge {{ $kelasSiswa->jenjang_badge_class }}">
                                                                {{ $kelasSiswa->nama_kelas }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted small">Belum ditempatkan</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif

                            <div class="d-flex justify-content-end mt-3">
                                <button type="submit" class="btn text-white font-weight-bold px-4 shadow-sm"
                                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border: none; border-radius: 8px;">
                                    <i class="fa fa-save mr-1"></i> Simpan Mutasi Keluar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const kategoriEl = document.getElementById('kategori');
            const sekolahInput = document.getElementById('sekolah_asal_tujuan');
            const requiredMark = document.getElementById('sekolahTujuanRequiredMark');

            function syncSekolahRequirement() {
                if (!kategoriEl || !sekolahInput || !requiredMark) return;
                const isPindah = kategoriEl.value === 'pindah';
                sekolahInput.required = isPindah;
                requiredMark.style.display = isPindah ? 'inline' : 'none';
            }

            if (kategoriEl) {
                kategoriEl.addEventListener('change', syncSekolahRequirement);
                syncSekolahRequirement();
            }

            const filterKelas = document.getElementById('clientFilterKelas');
            const searchInput = document.getElementById('clientSearchSiswa');
            const rows = Array.from(document.querySelectorAll('.js-student-row'));
            const checkboxes = Array.from(document.querySelectorAll('.js-out-checkbox'));
            const countBadge = document.getElementById('selectedOutCount');
            const toggleVisibleBtn = document.getElementById('toggleVisibleOutBtn');

            function syncOutCount() {
                if (!countBadge) return;
                countBadge.textContent = String(checkboxes.filter((cb) => cb.checked).length);
            }

            function filterRows() {
                const kelasVal = filterKelas ? filterKelas.value : '';
                const keyword = searchInput ? searchInput.value.trim().toLowerCase() : '';

                rows.forEach((row) => {
                    const matchKelas = !kelasVal || row.getAttribute('data-kelas-id') === kelasVal;
                    const matchKeyword = !keyword || (row.getAttribute('data-search') || '').includes(keyword);
                    row.style.display = (matchKelas && matchKeyword) ? '' : 'none';
                });
            }

            if (filterKelas) filterKelas.addEventListener('change', filterRows);
            if (searchInput) searchInput.addEventListener('input', filterRows);

            checkboxes.forEach((cb) => cb.addEventListener('change', syncOutCount));
            syncOutCount();

            if (toggleVisibleBtn) {
                toggleVisibleBtn.addEventListener('click', function() {
                    const visibleCheckboxes = rows
                        .filter((r) => r.style.display !== 'none')
                        .map((r) => r.querySelector('.js-out-checkbox'))
                        .filter(Boolean);

                    const allChecked = visibleCheckboxes.length > 0 && visibleCheckboxes.every((cb) => cb.checked);
                    visibleCheckboxes.forEach((cb) => {
                        cb.checked = !allChecked;
                    });
                    syncOutCount();
                });
            }
        });
    </script>
@endpush
