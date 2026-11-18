@php
    $isEdit = isset($titimangsa);
    $val = fn (string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $titimangsa->$field : $default);

    $jenjangOptions = $jenjangOptions ?? \App\Models\Admin\DataMaster\Titimangsa::JENJANG_OPTIONS;
    $periodeOptions = $periodeOptions ?? \App\Models\Admin\DataMaster\Titimangsa::PERIODE_OPTIONS;
    $currentJenjang = strtoupper((string) $val('jenjang_kelas', 'SD'));
    $rawTingkatan = trim((string) $val('tingkatan_kelas', \App\Models\Admin\DataMaster\Titimangsa::TINGKATAN_SEMUA));
    $defaultScope = old('tingkatan_scope', ($rawTingkatan === '' || $rawTingkatan === \App\Models\Admin\DataMaster\Titimangsa::TINGKATAN_SEMUA) ? 'semua' : 'spesifik');
    $tanggalValue = $val('tanggal_titimangsa');
    if ($tanggalValue instanceof \Carbon\CarbonInterface) {
        $tanggalValue = $tanggalValue->format('Y-m-d');
    }
@endphp

<div class="row">
    <!-- Tahun Ajaran -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="akademik_master_tahun_ajaran_id" class="font-weight-bold">
                Tahun Ajaran <span class="text-danger">*</span>
            </label>
            <select name="akademik_master_tahun_ajaran_id" id="akademik_master_tahun_ajaran_id"
                class="form-control @error('akademik_master_tahun_ajaran_id') is-invalid @enderror" required>
                <option value="">-- Pilih Tahun Ajaran --</option>
                @foreach ($tahunAjaran as $ta)
                    <option value="{{ $ta->id }}" @selected((string) $val('akademik_master_tahun_ajaran_id', $tahunAjaran->firstWhere('is_active', true)?->id) === (string) $ta->id)>
                        {{ $ta->nama_tahun_ajaran }} - Semester {{ strtoupper((string) $ta->semester) }} {{ $ta->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('akademik_master_tahun_ajaran_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Jenjang Kelas -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="jenjang_kelas" class="font-weight-bold">
                Jenjang Kelas <span class="text-danger">*</span>
            </label>
            <select name="jenjang_kelas" id="jenjang_kelas"
                class="form-control @error('jenjang_kelas') is-invalid @enderror" required>
                <option value="">-- Pilih Jenjang --</option>
                @foreach ($jenjangOptions as $jenjang)
                    <option value="{{ $jenjang }}" @selected($currentJenjang === $jenjang)>
                        {{ $jenjang }}
                    </option>
                @endforeach
                @if ($currentJenjang !== '' && ! in_array($currentJenjang, $jenjangOptions, true))
                    <option value="{{ $currentJenjang }}" selected>{{ $currentJenjang }}</option>
                @endif
            </select>
            @error('jenjang_kelas')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Periode Rapor -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="periode" class="font-weight-bold">
                Periode Rapor <span class="text-danger">*</span>
            </label>
            <select name="periode" id="periode"
                class="form-control @error('periode') is-invalid @enderror" required>
                @foreach ($periodeOptions as $kode => $label)
                    <option value="{{ $kode }}" @selected((string) $val('periode', 'mid') === $kode)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('periode')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<!-- Cakupan Tingkatan Kelas -->
<div class="card border bg-light mb-3" style="border-radius: 10px;">
    <div class="card-body py-3">
        <label class="font-weight-bold d-block mb-2">
            Cakupan Tingkatan Kelas <span class="text-danger">*</span>
        </label>
        <div class="row align-items-center">
            <div class="col-md-6 mb-2 mb-md-0">
                <div class="custom-control custom-radio custom-control-inline">
                    <input type="radio" id="scopeSemua" name="tingkatan_scope" value="semua"
                        class="custom-control-input js-tingkatan-scope"
                        @checked($defaultScope === 'semua')>
                    <label class="custom-control-label font-weight-semibold" for="scopeSemua">
                        Semua Tingkat (*) <span class="text-muted small font-weight-normal">(Standar jenjang)</span>
                    </label>
                </div>
                <div class="custom-control custom-radio custom-control-inline mt-1 mt-md-0">
                    <input type="radio" id="scopeSpesifik" name="tingkatan_scope" value="spesifik"
                        class="custom-control-input js-tingkatan-scope"
                        @checked($defaultScope === 'spesifik')>
                    <label class="custom-control-label font-weight-semibold" for="scopeSpesifik">
                        Tingkatan Tertentu <span class="text-muted small font-weight-normal">(Misal khusus Kelas 6)</span>
                    </label>
                </div>
            </div>

            <div class="col-md-6 {{ $defaultScope === 'semua' ? 'd-none' : '' }}" id="tingkatanSpesifikWrapper">
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        <span class="input-group-text font-weight-bold">Tingkat</span>
                    </div>
                    <input type="text" name="tingkatan_kelas" id="tingkatan_kelas"
                        class="form-control @error('tingkatan_kelas') is-invalid @enderror"
                        placeholder="Contoh: 6, 5, A, B"
                        value="{{ $defaultScope === 'spesifik' ? $rawTingkatan : '' }}">
                    <div class="input-group-append">
                        @foreach (['1', '2', '3', '4', '5', '6'] as $preset)
                            <button type="button" class="btn btn-outline-secondary js-tingkat-preset" data-preset="{{ $preset }}">
                                {{ $preset }}
                            </button>
                        @endforeach
                    </div>
                </div>
                @error('tingkatan_kelas')
                    <div class="text-danger small mt-1 font-italic">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Kota / Tempat Terbit -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="tempat" class="font-weight-bold">
                Kota / Tempat Terbit <span class="text-danger">*</span>
            </label>
            <input name="tempat" id="tempat" placeholder="Contoh: Garut" type="text"
                class="form-control @error('tempat') is-invalid @enderror"
                value="{{ $val('tempat') }}" required>
            @error('tempat')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Tanggal Titimangsa -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="tanggal_titimangsa" class="font-weight-bold">
                Tanggal Titimangsa Rapor <span class="text-danger">*</span>
            </label>
            <input name="tanggal_titimangsa" id="tanggal_titimangsa" type="date"
                class="form-control @error('tanggal_titimangsa') is-invalid @enderror"
                value="{{ $tanggalValue }}" required>
            @error('tanggal_titimangsa')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Kepala Sekolah (Data Karyawan) -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="kepala_sekolah_nipy" class="font-weight-bold">
                Kepala Sekolah (Data Karyawan)
            </label>
            <select name="kepala_sekolah_nipy" id="kepala_sekolah_nipy"
                class="form-control @error('kepala_sekolah_nipy') is-invalid @enderror">
                <option value="">-- Pilih Kepala Sekolah dari Karyawan --</option>
                @foreach ($karyawan as $guru)
                    <option value="{{ $guru->nipy }}"
                        data-nama="{{ $guru->nama_gelar }}"
                        @selected((string) $val('kepala_sekolah_nipy') === (string) $guru->nipy)>
                        {{ $guru->nama_gelar }} ({{ $guru->nipy }})
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Nama &amp; NIPY akan dicetak di blok tanda tangan Kepala Sekolah.
            </small>
            @error('kepala_sekolah_nipy')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Nama Kepala Sekolah Manual / Snapshot -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="nama_kepala_sekolah_snapshot" class="font-weight-bold">
                Nama Kepsek Manual / Arsip Snapshot
            </label>
            <input name="nama_kepala_sekolah_snapshot" id="nama_kepala_sekolah_snapshot"
                placeholder="Terisi otomatis dari Karyawan atau isi manual jika belum terdaftar" type="text"
                class="form-control @error('nama_kepala_sekolah_snapshot') is-invalid @enderror"
                value="{{ $val('nama_kepala_sekolah_snapshot') }}">
            @error('nama_kepala_sekolah_snapshot')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Jabatan Penandatangan -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="jabatan_kepala_sekolah" class="font-weight-bold">
                Jabatan Penandatangan <span class="text-danger">*</span>
            </label>
            <input name="jabatan_kepala_sekolah" id="jabatan_kepala_sekolah"
                placeholder="Contoh: Kepala Sekolah, Kepala SD Prima Insani, Plt. Kepala Sekolah" type="text"
                class="form-control @error('jabatan_kepala_sekolah') is-invalid @enderror"
                value="{{ $val('jabatan_kepala_sekolah', \App\Models\Admin\DataMaster\Titimangsa::DEFAULT_JABATAN) }}" required>
            @error('jabatan_kepala_sekolah')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Status Aktif -->
    <div class="col-md-6 d-flex align-items-center">
        <div class="position-relative form-group mb-0 mt-md-3">
            <input type="hidden" name="status" value="0">
            <div class="custom-control custom-switch">
                <input type="checkbox" class="custom-control-input" id="titimangsaStatus" name="status" value="1"
                    @checked((bool) $val('status', true))>
                <label class="custom-control-label font-weight-bold" for="titimangsaStatus">
                    Aktif digunakan saat cetak rapor
                </label>
            </div>
        </div>
    </div>
</div>

<div class="form-group mt-3">
    <button type="submit" class="btn btn-xl text-white shadow-sm font-weight-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
        <i class="fa fa-save mr-1"></i> Simpan
    </button>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const wrapper = document.getElementById('tingkatanSpesifikWrapper');
            const inputTingkat = document.getElementById('tingkatan_kelas');
            const scopeRadios = document.querySelectorAll('.js-tingkatan-scope');
            const kepsekSelect = document.getElementById('kepala_sekolah_nipy');
            const snapshotInput = document.getElementById('nama_kepala_sekolah_snapshot');

            function syncTingkatanVisibility() {
                const selected = document.querySelector('.js-tingkatan-scope:checked');
                const isSpecific = selected && selected.value === 'spesifik';

                if (wrapper && inputTingkat) {
                    wrapper.classList.toggle('d-none', !isSpecific);
                    inputTingkat.disabled = !isSpecific;
                    inputTingkat.required = isSpecific;
                }
            }

            scopeRadios.forEach(function (radio) {
                radio.addEventListener('change', syncTingkatanVisibility);
            });
            syncTingkatanVisibility();

            document.querySelectorAll('.js-tingkat-preset').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (inputTingkat) {
                        inputTingkat.value = btn.getAttribute('data-preset') || '';
                        inputTingkat.focus();
                    }
                });
            });

            if (kepsekSelect && snapshotInput) {
                kepsekSelect.addEventListener('change', function () {
                    const selectedOption = kepsekSelect.options[kepsekSelect.selectedIndex];
                    const nama = selectedOption ? (selectedOption.getAttribute('data-nama') || '') : '';
                    if (nama !== '') {
                        snapshotInput.value = nama;
                    }
                });
            }
        });
    </script>
@endpush

