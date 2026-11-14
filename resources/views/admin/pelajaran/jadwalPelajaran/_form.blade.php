@php
    $isEdit = isset($jadwalPelajaran);
    $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
    $kelasOptions = $kelasOptions ?? collect();
    $jamPelajaranOptions = $jamPelajaranOptions ?? collect();
    $mataPelajaranOptions = $mataPelajaranOptions ?? collect();
    $guruOptions = $guruOptions ?? collect();
    $hariOptions = $hariOptions ?? \App\Models\Admin\Pelajaran\JadwalPelajaran::HARI_OPTIONS;
    $nonMapelPresets = $nonMapelPresets ?? \App\Models\Admin\Pelajaran\JadwalPelajaran::NON_MAPEL_PRESETS;
    $defaults = $defaults ?? [
        'tahun_ajaran_id' => '',
        'kelas_id' => '',
        'jam_pelajaran_id' => '',
        'hari' => '',
    ];

    $val = fn (string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $jadwalPelajaran->$field : $default);

    $currentStatus = $val('status', true);
    $isStatusActive = filter_var($currentStatus, FILTER_VALIDATE_BOOLEAN);
    $selectedMapelId = (string) ($val('akademik_pelajaran_mata_pelajaran_id') ?? '');
    $isNonMapelSelected = $selectedMapelId === '';
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
                @foreach ($tahunAjaranOptions as $ta)
                    <option value="{{ $ta->id }}"
                        @selected((string) $val('akademik_master_tahun_ajaran_id', $defaults['tahun_ajaran_id']) === (string) $ta->id)>
                        {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
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

    <!-- Kelas -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="akademik_master_kelas_id" class="font-weight-bold">
                Kelas <span class="text-danger">*</span>
            </label>
            <select name="akademik_master_kelas_id" id="akademik_master_kelas_id"
                class="form-control @error('akademik_master_kelas_id') is-invalid @enderror" required>
                <option value="">-- Pilih Kelas --</option>
                @foreach ($kelasOptions as $kelas)
                    <option value="{{ $kelas->id }}"
                        data-tahun-ajaran-id="{{ $kelas->akademik_master_tahun_ajaran_id }}"
                        data-unit="{{ strtoupper((string) $kelas->jenjang_kelas) }}"
                        @selected((string) $val('akademik_master_kelas_id', $defaults['kelas_id']) === (string) $kelas->id)>
                        [{{ strtoupper((string) $kelas->jenjang_kelas) }}] {{ $kelas->nama_kelas }}
                    </option>
                @endforeach
            </select>
            @error('akademik_master_kelas_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Hari -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="hari" class="font-weight-bold">
                Hari Pembelajaran <span class="text-danger">*</span>
            </label>
            <select name="hari" id="hari" class="form-control @error('hari') is-invalid @enderror" required>
                <option value="">-- Pilih Hari --</option>
                @foreach ($hariOptions as $hariCode => $hariNama)
                    <option value="{{ $hariCode }}"
                        @selected((string) $val('hari', $defaults['hari']) === (string) $hariCode)>
                        {{ $hariNama }}
                    </option>
                @endforeach
            </select>
            @error('hari')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Jam Pelajaran -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_pelajaran_jam_pelajaran_id" class="font-weight-bold">
                Slot Jam Pelajaran <span class="text-danger">*</span>
            </label>
            <select name="akademik_pelajaran_jam_pelajaran_id" id="akademik_pelajaran_jam_pelajaran_id"
                class="form-control @error('akademik_pelajaran_jam_pelajaran_id') is-invalid @enderror" required>
                <option value="">-- Pilih Jam Pelajaran --</option>
                @foreach ($jamPelajaranOptions as $jam)
                    <option value="{{ $jam->id }}"
                        data-unit="{{ strtoupper((string) $jam->unit) }}"
                        @selected((string) $val('akademik_pelajaran_jam_pelajaran_id', $defaults['jam_pelajaran_id']) === (string) $jam->id)>
                        [{{ strtoupper((string) $jam->unit) }}] Jam Ke-{{ $jam->jam_ke }} ({{ $jam->rentang_waktu }})
                        @if (filled($jam->keterangan))
                            - {{ $jam->keterangan }}
                        @endif
                    </option>
                @endforeach
            </select>
            @error('akademik_pelajaran_jam_pelajaran_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Mata Pelajaran (Nullable untuk slot Non-Mapel) -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_pelajaran_mata_pelajaran_id" class="font-weight-bold">
                Mata Pelajaran
                <span class="text-muted font-weight-normal small">(kosongkan untuk Istirahat / Upacara / Makan / Pulang)</span>
            </label>
            <select name="akademik_pelajaran_mata_pelajaran_id" id="akademik_pelajaran_mata_pelajaran_id"
                class="form-control @error('akademik_pelajaran_mata_pelajaran_id') is-invalid @enderror">
                <option value="">-- Slot Non-Mapel (Istirahat / Upacara / Lainnya) --</option>
                @foreach ($mataPelajaranOptions as $mapel)
                    <option value="{{ $mapel->id }}"
                        data-unit="{{ strtoupper((string) $mapel->unit) }}"
                        @selected($selectedMapelId === (string) $mapel->id)>
                        [{{ strtoupper((string) $mapel->unit) }}] {{ $mapel->singkatan }} - {{ $mapel->nama_mata_pelajaran }}{{ ! $mapel->status ? ' (Nonaktif)' : '' }}
                    </option>
                @endforeach
            </select>
            @error('akademik_pelajaran_mata_pelajaran_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Guru Pengampu -->
    <div class="col-md-5">
        <div class="position-relative form-group">
            <label for="karyawan_nipy" class="font-weight-bold">
                Guru Pengampu
                <span class="text-muted font-weight-normal small">(opsional)</span>
            </label>
            <select name="karyawan_nipy" id="karyawan_nipy"
                class="form-control @error('karyawan_nipy') is-invalid @enderror">
                <option value="">-- Tanpa Guru Pengampu --</option>
                @foreach ($guruOptions as $guru)
                    <option value="{{ $guru->nipy }}"
                        @selected((string) ($val('karyawan_nipy') ?? '') === (string) $guru->nipy)>
                        {{ $guru->nama_gelar }} ({{ $guru->nipy }})
                    </option>
                @endforeach
            </select>
            @error('karyawan_nipy')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Ruang -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="ruang" class="font-weight-bold">
                Ruang <span class="text-muted font-weight-normal small">(opsional)</span>
            </label>
            <input name="ruang" id="ruang" type="text" maxlength="50"
                placeholder="Contoh: Ruang Kelas, Lab Komputer, Lapangan"
                class="form-control @error('ruang') is-invalid @enderror"
                value="{{ $val('ruang') }}">
            @error('ruang')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Status -->
    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="status" class="font-weight-bold">
                Status <span class="text-danger">*</span>
            </label>
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                <option value="1" @selected($isStatusActive)>Aktif (Ditampilkan)</option>
                <option value="0" @selected(! $isStatusActive)>Nonaktif</option>
            </select>
            @error('status')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Keterangan -->
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="keterangan" class="font-weight-bold">
                Keterangan Kegiatan / Catatan
                <span id="keteranganRequiredMark" class="text-danger {{ $isNonMapelSelected ? '' : 'd-none' }}">*</span>
            </label>
            <textarea name="keterangan" id="keterangan" rows="3" maxlength="1000"
                placeholder="Isi nama kegiatan bila non-mapel (Istirahat, Upacara, Makan, Pulang) atau catatan khusus..."
                class="form-control @error('keterangan') is-invalid @enderror">{{ $val('keterangan') }}</textarea>
            <div id="nonMapelPresetBox" class="mt-2 {{ $isNonMapelSelected ? '' : 'd-none' }}">
                <span class="small text-muted mr-1">Isi cepat Non-Mapel:</span>
                @foreach ($nonMapelPresets as $presetLabel)
                    <button type="button" class="btn btn-sm btn-outline-warning text-dark mr-1 mb-1 js-nonmapel-preset"
                        data-preset="{{ $presetLabel }}" style="border-radius: 999px; font-size: 0.72rem;">
                        + {{ $presetLabel }}
                    </button>
                @endforeach
            </div>
            @error('keterangan')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
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
            const taSelect = document.getElementById('akademik_master_tahun_ajaran_id');
            const kelasSelect = document.getElementById('akademik_master_kelas_id');
            const jamSelect = document.getElementById('akademik_pelajaran_jam_pelajaran_id');
            const mapelSelect = document.getElementById('akademik_pelajaran_mata_pelajaran_id');
            const keteranganInput = document.getElementById('keterangan');
            const keteranganMark = document.getElementById('keteranganRequiredMark');
            const presetBox = document.getElementById('nonMapelPresetBox');

            const filterSelectByDataset = (selectEl, attrName, expectedValue) => {
                if (!selectEl) {
                    return;
                }

                Array.from(selectEl.options).forEach((opt) => {
                    if (!opt.value) {
                        opt.hidden = false;
                        opt.disabled = false;
                        return;
                    }

                    const attrVal = opt.getAttribute(attrName) || '';
                    const matches = !expectedValue || attrVal === expectedValue;
                    opt.hidden = !matches;
                    opt.disabled = !matches;
                });

                const currentOpt = selectEl.options[selectEl.selectedIndex];
                if (currentOpt && currentOpt.value && currentOpt.disabled) {
                    selectEl.value = '';
                }
            };

            const syncKelasOptions = () => {
                if (!taSelect || !kelasSelect) {
                    return;
                }
                filterSelectByDataset(kelasSelect, 'data-tahun-ajaran-id', taSelect.value);
                syncUnitDependents();
            };

            const syncUnitDependents = () => {
                if (!kelasSelect) {
                    return;
                }
                const selectedOpt = kelasSelect.options[kelasSelect.selectedIndex];
                const unit = selectedOpt && selectedOpt.value ? (selectedOpt.getAttribute('data-unit') || '') : '';
                filterSelectByDataset(jamSelect, 'data-unit', unit);
                filterSelectByDataset(mapelSelect, 'data-unit', unit);
            };

            const syncNonMapelMode = () => {
                if (!mapelSelect) {
                    return;
                }
                const isNonMapel = mapelSelect.value === '';
                if (keteranganMark) {
                    keteranganMark.classList.toggle('d-none', !isNonMapel);
                }
                if (presetBox) {
                    presetBox.classList.toggle('d-none', !isNonMapel);
                }
                if (keteranganInput) {
                    keteranganInput.required = isNonMapel;
                }
            };

            if (taSelect) {
                taSelect.addEventListener('change', syncKelasOptions);
            }
            if (kelasSelect) {
                kelasSelect.addEventListener('change', syncUnitDependents);
            }
            if (mapelSelect) {
                mapelSelect.addEventListener('change', syncNonMapelMode);
            }

            document.querySelectorAll('.js-nonmapel-preset').forEach((btn) => {
                btn.addEventListener('click', () => {
                    if (keteranganInput) {
                        keteranganInput.value = btn.getAttribute('data-preset') || '';
                        keteranganInput.focus();
                    }
                });
            });

            syncKelasOptions();
            syncNonMapelMode();
        });
    </script>
@endpush

