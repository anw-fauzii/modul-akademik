@pushOnce('css')
    <style>
        .ak-alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            margin-bottom: 1.25rem;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
        }

        .invalid-feedback {
            display: block;
            margin-top: 0.35rem;
            color: #dc2626;
        }

        .form-text {
            display: block;
            margin-top: 0.3rem;
        }
    </style>
@endPushOnce

@php
    $isEdit = isset($anggotaT2q);
    $excludeId = $isEdit ? (int) $anggotaT2q->id : null;
    $preselectedKeterangan = $preselectedKeterangan ?? (request()->string('keterangan')->toString() ?: null);
    $val = fn (string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $anggotaT2q->$field : $default);

    $resolvedTingkatList = $tingkatOptions ?? \App\Models\Admin\DataMaster\AnggotaT2q::DEFAULT_TINGKAT_OPTIONS;
    $currentTingkat = \App\Models\Admin\DataMaster\AnggotaT2q::normalizeTingkat(
        $val('tingkat', $preselectedTingkat ?? null)
    );
    $isCustomTingkatSelected = $currentTingkat !== ''
        && $currentTingkat !== \App\Models\Admin\DataMaster\AnggotaT2q::TINGKAT_AUTO
        && ($currentTingkat === \App\Models\Admin\DataMaster\AnggotaT2q::TINGKAT_CUSTOM
            || ! in_array($currentTingkat, $resolvedTingkatList, true));
    $customTingkatValue = session()->hasOldInput('tingkat_custom')
        ? (string) old('tingkat_custom')
        : ($isCustomTingkatSelected && $currentTingkat !== \App\Models\Admin\DataMaster\AnggotaT2q::TINGKAT_CUSTOM
            ? $currentTingkat
            : '');
@endphp

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

<div class="row">
    @if (! $isEdit)
        <!-- Tahun Ajaran & Semester (Konteks Kandidat Siswa) -->
        <div class="col-md-4">
            <div class="position-relative form-group">
                <label for="filter_tahun_ajaran_id" class="font-weight-bold">
                    Tahun Ajaran &amp; Semester <span class="text-danger">*</span>
                </label>
                <select name="tahun_ajaran_id" id="filter_tahun_ajaran_id"
                    class="form-control @error('tahun_ajaran_id') is-invalid @enderror" required>
                    @foreach ($tahunAjaran ?? [] as $ta)
                        <option value="{{ $ta->id }}"
                            data-tahun-ajaran-id="{{ $ta->id }}"
                            @selected((string) $val('tahun_ajaran_id', $selectedTahunAjaranId ?? null) === (string) $ta->id)>
                            {{ $ta->nama_tahun_ajaran }} - Semester {{ strtoupper((string) $ta->semester) }} {{ $ta->is_active ? '[Aktif]' : '' }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted" style="font-size: 0.75rem;">
                    Menampilkan daftar Anggota Kelas pada tahun ajaran &amp; semester terpilih.
                </small>
                @error('tahun_ajaran_id')
                    <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>
    @endif

    <!-- Guru Pembimbing T2Q -->
    <div class="{{ $isEdit ? 'col-md-5' : 'col-md-4' }}">
        <div class="position-relative form-group">
            <label for="karyawan_nipy" class="font-weight-bold">
                Guru Pembimbing T2Q <span class="text-danger">*</span>
            </label>
            <select name="karyawan_nipy" id="karyawan_nipy"
                class="form-control @error('karyawan_nipy') is-invalid @enderror" required>
                <option value="">-- Pilih Guru Pembimbing T2Q --</option>
                @foreach ($karyawan as $guru)
                    <option value="{{ $guru->nipy }}"
                        @selected((string) $val('karyawan_nipy', $preselectedKaryawanNipy ?? null) === (string) $guru->nipy)>
                        {{ $guru->nama_gelar }} ({{ $guru->nipy }})
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Guru / ustadz pembimbing T2Q untuk siswa terpilih.
            </small>
            @error('karyawan_nipy')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Tingkat T2Q -->
    <div class="{{ $isEdit ? 'col-md-4' : 'col-md-4' }}">
        <div class="position-relative form-group">
            <div class="d-flex justify-content-between align-items-center mb-1">
                <label for="tingkat" class="font-weight-bold mb-0">
                    Tingkat <span class="text-danger">*</span>
                </label>
                @if ($isEdit)
                    <button type="button" id="syncTingkatKelasBtn"
                        class="btn btn-xs btn-outline-warning font-weight-bold py-0 px-2"
                        style="font-size: 0.7rem; border-radius: 6px;">
                        <i class="fa fa-magic mr-1"></i> Samakan dg Kelas
                    </button>
                @endif
            </div>
            <select name="tingkat" id="tingkat"
                class="form-control @error('tingkat') is-invalid @enderror" required>
                <option value="">-- Pilih Tingkat --</option>
                @if (! $isEdit)
                    <option value="{{ \App\Models\Admin\DataMaster\AnggotaT2q::TINGKAT_AUTO }}"
                        @selected($currentTingkat === \App\Models\Admin\DataMaster\AnggotaT2q::TINGKAT_AUTO)>
                        Otomatis (Ikuti Tingkatan Kelas Siswa)
                    </option>
                @endif
                <optgroup label="Tingkat Jenjang Kelas">
                    @foreach ($resolvedTingkatList as $opsiTingkat)
                        <option value="{{ $opsiTingkat }}"
                            @selected(! $isCustomTingkatSelected && $currentTingkat === (string) $opsiTingkat)>
                            Tingkat {{ $opsiTingkat }}
                        </option>
                    @endforeach
                </optgroup>
                <option value="{{ \App\Models\Admin\DataMaster\AnggotaT2q::TINGKAT_CUSTOM }}"
                    @selected($isCustomTingkatSelected)>
                    Lainnya (Ketik Manual)...
                </option>
            </select>

            <div id="tingkatCustomWrapper" class="mt-2 {{ $isCustomTingkatSelected ? '' : 'd-none' }}">
                <input type="text" name="tingkat_custom" id="tingkat_custom" maxlength="50"
                    value="{{ $customTingkatValue }}"
                    placeholder="Contoh: PG, A, B, 1, TK-A"
                    class="form-control @error('tingkat') is-invalid @enderror">
            </div>

            <small class="form-text text-muted" style="font-size: 0.75rem;">
                @if ($isEdit)
                    Pilih tingkat (PG, A, B, 1..9) atau sesuaikan dengan tingkatan kelas siswa.
                @else
                    Bisa mengikuti tingkatan kelas masing-masing siswa atau dipilih manual.
                @endif
            </small>
            @error('tingkat')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    @if ($isEdit)
        <!-- Pendaftaran -->
        <div class="col-md-3">
            <div class="position-relative form-group">
                <label for="pendaftaran" class="font-weight-bold">
                    Jenis Pendaftaran <span class="text-danger">*</span>
                </label>
                <select name="pendaftaran" id="pendaftaran"
                    class="form-control @error('pendaftaran') is-invalid @enderror" required>
                    <option value="">-- Pilih Pendaftaran --</option>
                    @foreach (\App\Models\Admin\DataMaster\AnggotaT2q::PENDAFTARAN_OPTIONS as $opsi)
                        <option value="{{ $opsi }}" @selected((string) $val('pendaftaran', $preselectedPendaftaran ?? null) === (string) $opsi)>
                            {{ $opsi }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted" style="font-size: 0.75rem;">
                    Baru / Lanjutan di tahun ajaran ini.
                </small>
                @error('pendaftaran')
                    <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>
    @endif
</div>

<div class="row">
    @if (! $isEdit)
        <!-- Pendaftaran -->
        <div class="col-md-4">
            <div class="position-relative form-group">
                <label for="pendaftaran" class="font-weight-bold">
                    Jenis Pendaftaran <span class="text-danger">*</span>
                </label>
                <select name="pendaftaran" id="pendaftaran"
                    class="form-control @error('pendaftaran') is-invalid @enderror" required>
                    <option value="">-- Pilih Pendaftaran --</option>
                    @foreach (\App\Models\Admin\DataMaster\AnggotaT2q::PENDAFTARAN_OPTIONS as $opsi)
                        <option value="{{ $opsi }}" @selected((string) $val('pendaftaran', $preselectedPendaftaran ?? null) === (string) $opsi)>
                            {{ $opsi }}
                        </option>
                    @endforeach
                </select>
                <small class="form-text text-muted" style="font-size: 0.75rem;">
                    Baru / Lanjutan di tahun ajaran ini.
                </small>
                @error('pendaftaran')
                    <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                        {{ $message }}
                    </div>
                @enderror
            </div>
        </div>
    @endif

    <!-- Keterangan -->
    <div class="{{ $isEdit ? 'col-md-12' : 'col-md-8' }}">
        <div class="position-relative form-group">
            <label for="keterangan" class="font-weight-bold">Keterangan</label>
            <input type="text" name="keterangan" id="keterangan"
                maxlength="255"
                value="{{ $val('keterangan', $preselectedKeterangan) }}"
                placeholder="Opsional (mis. Tilawati Jilid 2 / Tahfidz Juz 30 / Catatan)"
                class="form-control @error('keterangan') is-invalid @enderror">
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Catatan tambahan keanggotaan T2Q (maks. 255 karakter).
            </small>
            @error('keterangan')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<!-- Anggota Kelas -->
<div class="position-relative form-group mt-2">
    <label for="{{ $isEdit ? 'anggota_kelas_id' : 'filterSiswaInput' }}" class="font-weight-bold">
        Pilih Anggota Kelas Siswa <span class="text-danger">*</span>
    </label>

    @if ($isEdit)
        <select name="anggota_kelas_id" id="anggota_kelas_id"
            class="form-control @error('anggota_kelas_id') is-invalid @enderror" required>
            <option value="" data-tingkatan-kelas="">-- Pilih Anggota Kelas --</option>
            @foreach ($anggotaKelas as $ak)
                @php
                    $siswaRow = $ak->siswa;
                    $kelasRow = $ak->kelas;
                    $prevT2q = $siswaRow?->resolvePreviousT2qSummary($excludeId);
                    $prevLabel = $prevT2q
                        ? ' | T2Q Lain: ' . $prevT2q['guru'] . ' (Tingkat ' . $prevT2q['tingkat'] . ($prevT2q['tahun_ajaran'] ? ' - ' . $prevT2q['tahun_ajaran'] . ' Smt ' . strtoupper((string) $prevT2q['semester']) : '') . ')'
                        : '';
                @endphp
                <option value="{{ $ak->id }}"
                    data-tingkatan-kelas="{{ $kelasRow?->tingkatan_kelas ?? '' }}"
                    @selected((string) $val('anggota_kelas_id') === (string) $ak->id)>
                    {{ $siswaRow?->nama_lengkap ?: 'Tanpa Nama' }} — NIS: {{ $ak->siswa_nis }}
                    @if ($kelasRow)
                        | Kelas: {{ $kelasRow->nama_kelas }} (Tingkat {{ $kelasRow->tingkatan_kelas }})
                    @endif
                    {{ $prevLabel }}
                    @if ($siswaRow?->status)
                        [{{ $siswaRow->status }}]
                    @endif
                </option>
            @endforeach
        </select>
        @error('anggota_kelas_id')
            <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                {{ $message }}
            </div>
        @enderror
    @else
        @include('admin.dataMaster.partials._siswa_picker', [
            'pickerMode' => 't2q',
            'anggotaKelas' => $anggotaKelas,
            'excludedTahunAjaranIds' => $excludedTahunAjaranIds ?? [],
            'hiddenRegisteredCount' => $hiddenRegisteredCount ?? 0,
            'hiddenInactiveCount' => $hiddenInactiveCount ?? 0,
            'hiddenWithoutKelasCount' => $hiddenWithoutKelasCount ?? 0,
            'showHistoryFilter' => true,
        ])
    @endif
</div>

<div class="form-group mt-4">
    <button type="submit" class="btn btn-xl text-white shadow-sm font-weight-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
        <i class="fa fa-save mr-1"></i> Simpan
    </button>
</div>

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tingkatSelect = document.getElementById('tingkat');
            const customWrapper = document.getElementById('tingkatCustomWrapper');
            const customInput = document.getElementById('tingkat_custom');
            const anggotaKelasSelect = document.getElementById('anggota_kelas_id');
            const syncTingkatBtn = document.getElementById('syncTingkatKelasBtn');
            const customSentinel = @json(\App\Models\Admin\DataMaster\AnggotaT2q::TINGKAT_CUSTOM);

            const toggleCustomTingkat = () => {
                if (!tingkatSelect || !customWrapper || !customInput) {
                    return;
                }
                const isCustom = tingkatSelect.value === customSentinel;
                customWrapper.classList.toggle('d-none', !isCustom);
                customInput.required = isCustom;
                if (isCustom) {
                    customInput.focus();
                }
            };

            if (tingkatSelect) {
                tingkatSelect.addEventListener('change', toggleCustomTingkat);
                toggleCustomTingkat();
            }

            if (syncTingkatBtn && anggotaKelasSelect && tingkatSelect) {
                syncTingkatBtn.addEventListener('click', () => {
                    const selectedOption = anggotaKelasSelect.options[anggotaKelasSelect.selectedIndex];
                    const kelasTingkat = (selectedOption?.dataset.tingkatanKelas || '').trim();
                    if (!kelasTingkat) {
                        return;
                    }

                    const matchingOption = Array.from(tingkatSelect.options).find(
                        (option) => option.value.toUpperCase() === kelasTingkat.toUpperCase()
                    );

                    if (matchingOption) {
                        tingkatSelect.value = matchingOption.value;
                    } else if (customInput) {
                        tingkatSelect.value = customSentinel;
                        customInput.value = kelasTingkat;
                    }

                    toggleCustomTingkat();
                });
            }
        });
    </script>
@endonce

