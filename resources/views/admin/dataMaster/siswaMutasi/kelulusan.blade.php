@extends('layouts.appui')

@section('title')
    <title>Kelulusan Massal Siswa</title>
@endsection

@section('content')
    @php
        $selectedTaSqid = $selectedTahunAjaranId
            ? (\App\Support\Sqid::encodeFor(\App\Models\Admin\DataMaster\TahunAjaran::class, (int) $selectedTahunAjaranId) ?? $selectedTahunAjaranId)
            : '';
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-study" style="color: white;"></i>
                    </div>
                    <div>
                        Kelulusan Massal Siswa
                        <div class="page-title-subheading">
                            Luluskan siswa aktif per kelas akhir menjadi Alumni tanpa menghapus riwayat kelas maupun nilai rapor.
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

        <!-- Langkah 1: Pilih Tahun Ajaran & Kelas -->
        <div class="main-card card mb-3 shadow-sm">
            <div class="card-header bg-white font-weight-bold">
                <i class="fa fa-filter text-info mr-1"></i> 1. Pilih Tahun Ajaran &amp; Kelas Akhir
            </div>
            <div class="card-body">
                <form method="GET" action="{{ route('siswa-mutasi.kelulusan.create') }}" id="kelulusanFilterForm">
                    <div class="row align-items-end">
                        <div class="col-md-5 mb-2 mb-md-0">
                            <label for="tahun_ajaran_id" class="small font-weight-bold text-muted">Tahun Ajaran Kelulusan</label>
                            <select name="tahun_ajaran_id" id="tahun_ajaran_id" class="form-control">
                                @foreach ($tahunAjaranOptions as $ta)
                                    <option value="{{ $ta->sqid }}" @selected((int) $selectedTahunAjaranId === (int) $ta->id)>
                                        {{ $ta->nama_tahun_ajaran }} ({{ $ta->semester }}){{ $ta->is_active ? ' ★ Aktif' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-5 mb-2 mb-md-0">
                            <label for="kelas_id" class="small font-weight-bold text-muted">Pilih Kelas</label>
                            <select name="kelas_id" id="kelas_id" class="form-control">
                                <option value="">-- Pilih Kelas --</option>
                                @foreach ($kelasOptions as $itemKelas)
                                    <option value="{{ $itemKelas->sqid }}" @selected($selectedKelas && (int) $selectedKelas->id === (int) $itemKelas->id)>
                                        {{ $itemKelas->nama_kelas }} ({{ $itemKelas->jenjang_kelas }} - Tingkat {{ $itemKelas->tingkatan_kelas }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-info text-white btn-block font-weight-bold" style="border-radius: 8px;">
                                <i class="fa fa-users mr-1"></i> Muat Siswa
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($selectedKelas !== null)
            @if ($finalisasiSummary['is_sd'])
                @if (($finalisasiSummary['mapel_final_count'] ?? 0) > 0)
                    <div class="alert alert-info shadow-sm" style="border-radius: 10px;">
                        <i class="fa fa-check-circle mr-1"></i>
                        <strong>Kesiapan Rapor SD:</strong> Terdapat <strong>{{ $finalisasiSummary['mapel_final_count'] }}</strong> catatan finalisasi mata pelajaran pada kelas <strong>{{ $selectedKelas->nama_kelas }}</strong> di Tahun Ajaran ini.
                    </div>
                @else
                    <div class="alert alert-warning shadow-sm" style="border-radius: 10px;">
                        <i class="fa fa-exclamation-circle mr-1"></i>
                        <strong>Perhatian Guardrail Rapor SD:</strong> Belum ditemukan mata pelajaran yang berstatus <em>Final</em> pada kelas <strong>{{ $selectedKelas->nama_kelas }}</strong> untuk Tahun Ajaran ini. Pastikan nilai rapor sudah difinalisasi sebelum mencetak SK Kelulusan (Anda tetap dapat melanjutkan jika diperlukan).
                    </div>
                @endif
            @endif

            <form method="POST" action="{{ route('siswa-mutasi.kelulusan.store') }}" id="kelulusanBatchForm">
                @csrf
                <input type="hidden" name="akademik_master_tahun_ajaran_id" value="{{ $selectedTaSqid }}">
                <input type="hidden" name="kelas_id" value="{{ $selectedKelas->sqid }}">

                <div class="main-card card mb-3 shadow-sm">
                    <div class="card-header bg-white font-weight-bold">
                        <i class="fa fa-file-signature text-info mr-1"></i> 2. Data SK Kelulusan Batch
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label for="tanggal_efektif" class="font-weight-bold small">
                                    Tanggal Kelulusan / SK <span class="text-danger">*</span>
                                </label>
                                <input type="date" name="tanggal_efektif" id="tanggal_efektif" class="form-control"
                                    value="{{ old('tanggal_efektif', now()->toDateString()) }}" required>
                            </div>
                            <div class="col-md-4 mb-2">
                                <label for="nomor_surat" class="font-weight-bold small">Nomor SK Kelulusan</label>
                                <input type="text" name="nomor_surat" id="nomor_surat" class="form-control"
                                    placeholder="Contoh: 421/SK-LULUS/2026" value="{{ old('nomor_surat') }}">
                            </div>
                            <div class="col-md-5 mb-2">
                                <label for="alasan" class="font-weight-bold small">Catatan Kelulusan (Opsional)</label>
                                <input type="text" name="alasan" id="alasan" class="form-control"
                                    placeholder="Contoh: Lulus Tahun Pelajaran 2025/2026" value="{{ old('alasan') }}">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="main-card card shadow-sm">
                    <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center" style="gap: 0.5rem;">
                        <div>
                            <span class="font-weight-bold">3. Daftar Siswa Aktif — {{ $selectedKelas->nama_kelas }}</span>
                            <span class="badge badge-info ml-2">{{ $candidates->count() }} Kandidat Aktif</span>
                        </div>
                        @if ($candidates->isNotEmpty())
                            <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                <button type="button" class="btn btn-sm btn-outline-info font-weight-bold" id="toggleAllGraduates">
                                    <i class="fa fa-check-square mr-1"></i> Pilih Semua / Lepas Semua
                                </button>
                                <span class="badge badge-dark py-2 px-3">
                                    Dipilih: <span id="selectedGraduatesCount">0</span>
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="card-body">
                        @if ($candidates->isEmpty())
                            <div class="text-center py-4 text-muted">
                                <i class="fa fa-info-circle mb-2" style="font-size: 1.75rem;"></i>
                                <div class="font-weight-bold">Tidak ada siswa berstatus Aktif di kelas ini.</div>
                            </div>
                        @else
                            <div class="table-responsive">
                                <table class="table table-bordered table-hover table-sm mb-3" style="font-size: 0.86rem;">
                                    <thead class="thead-light">
                                        <tr>
                                            <th class="text-center" style="width: 5%;">Pilih</th>
                                            <th style="width: 16%;">NIS / NISN</th>
                                            <th style="width: 35%;">Nama Siswa</th>
                                            <th style="width: 14%;">L/P &amp; Status</th>
                                            <th style="width: 30%;">Nomor Ijazah / SKL (Opsional)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $oldSelected = array_map('strval', (array) old('siswa_nis', []));
                                            $hasOldInput = session()->hasOldInput('siswa_nis');
                                        @endphp
                                        @foreach ($candidates as $siswa)
                                            @php
                                                $isChecked = $hasOldInput
                                                    ? (in_array((string) $siswa->nis, $oldSelected, true) || in_array((string) $siswa->sqid, $oldSelected, true))
                                                    : true;
                                                $oldIjazah = old('nomor_ijazah.'.$siswa->nis, old('nomor_ijazah.'.$siswa->sqid));
                                            @endphp
                                            <tr>
                                                <td class="text-center align-middle">
                                                    <input type="checkbox"
                                                        class="js-graduate-checkbox"
                                                        name="siswa_nis[]"
                                                        value="{{ $siswa->sqid }}"
                                                        @checked($isChecked)>
                                                </td>
                                                <td class="align-middle">
                                                    <div class="font-weight-bold">{{ $siswa->nis }}</div>
                                                    @if ($siswa->nisn)
                                                        <div class="small text-muted">NISN: {{ $siswa->nisn }}</div>
                                                    @endif
                                                </td>
                                                <td class="align-middle font-weight-bold text-slate-800">
                                                    {{ $siswa->nama_lengkap }}
                                                </td>
                                                <td class="align-middle">
                                                    <span class="badge badge-light border">{{ $siswa->jenis_kelamin }}</span>
                                                    <span class="badge {{ $siswa->status_badge_class }} ml-1">{{ $siswa->status }}</span>
                                                </td>
                                                <td class="align-middle">
                                                    <input type="text"
                                                        name="nomor_ijazah[{{ $siswa->sqid }}]"
                                                        class="form-control form-control-sm"
                                                        placeholder="Opsional nomor ijazah / SKL"
                                                        value="{{ $oldIjazah }}">
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-info text-white font-weight-bold px-4 shadow-sm"
                                    style="border-radius: 8px;">
                                    <i class="fa fa-graduation-cap mr-1"></i> Proses Kelulusan Massal
                                </button>
                            </div>
                        @endif
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const taSelect = document.getElementById('tahun_ajaran_id');
            const kelasSelect = document.getElementById('kelas_id');
            const filterForm = document.getElementById('kelulusanFilterForm');

            if (taSelect && kelasSelect && filterForm) {
                taSelect.addEventListener('change', function() {
                    kelasSelect.value = '';
                    filterForm.submit();
                });
            }

            const checkboxes = Array.from(document.querySelectorAll('.js-graduate-checkbox'));
            const countBadge = document.getElementById('selectedGraduatesCount');
            const toggleBtn = document.getElementById('toggleAllGraduates');

            function syncCount() {
                if (!countBadge) return;
                const checkedCount = checkboxes.filter((cb) => cb.checked).length;
                countBadge.textContent = String(checkedCount);
            }

            checkboxes.forEach((cb) => cb.addEventListener('change', syncCount));
            syncCount();

            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    const allChecked = checkboxes.every((cb) => cb.checked);
                    checkboxes.forEach((cb) => {
                        cb.checked = !allChecked;
                    });
                    syncCount();
                });
            }
        });
    </script>
@endpush
