@php
    $isEdit = isset($targetCapaian);
    $activeTahunAjaran = $activeTahunAjaran ?? null;
    $tingkatOptions = $tingkatOptions ?? \App\Models\Admin\DataMaster\AnggotaT2q::DEFAULT_TINGKAT_OPTIONS;
    $daftarJilid = $daftarJilid ?? collect();
    $surahList = $surahList ?? collect();

    $val = fn (string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $targetCapaian->$field : $default);

    $displayTahunAjaran = $isEdit && $targetCapaian->relationLoaded('tahunAjaran') && $targetCapaian->tahunAjaran
        ? $targetCapaian->tahunAjaran
        : $activeTahunAjaran;
    $selectedTahunAjaranId = (string) $val('akademik_master_tahun_ajaran_id', $displayTahunAjaran?->id);
    $selectedTingkat = \App\Models\Admin\DataMaster\TargetCapaian::normalizeTingkat($val('tingkat', ''));
    $selectedJilidId = (string) $val('akademik_master_t2q_daftar_jilid_id', '');
    $selectedSurahId = (string) $val('akademik_master_t2q_surah_id', '');
@endphp

@if (! $displayTahunAjaran)
    <div class="alert alert-warning border mb-4" style="border-radius: 10px;">
        <i class="fa fa-exclamation-triangle mr-1"></i>
        Belum ada <strong>Tahun Ajaran</strong> yang berstatus aktif. Silakan aktifkan satu Tahun Ajaran terlebih dahulu sebelum menyimpan Target Capaian.
    </div>
@endif

<div class="row">
    <!-- Tahun Ajaran Aktif (Tunggal) -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label class="font-weight-bold">
                Tahun Ajaran Aktif <span class="text-danger">*</span>
            </label>
            <input type="hidden" name="akademik_master_tahun_ajaran_id" value="{{ $selectedTahunAjaranId }}">
            <div class="form-control d-flex align-items-center justify-content-between bg-light"
                style="height: auto; min-height: calc(2.25rem + 2px); cursor: not-allowed;">
                @if ($displayTahunAjaran)
                    <span class="font-weight-semibold text-slate-800">{{ $displayTahunAjaran->full_label }}</span>
                    @if ($displayTahunAjaran->is_active)
                        <span class="badge badge-success px-2 py-1" style="font-size: 0.7rem;">Aktif</span>
                    @else
                        <span class="badge badge-secondary px-2 py-1" style="font-size: 0.7rem;">Arsip</span>
                    @endif
                @else
                    <span class="text-danger font-weight-semibold">Belum ada Tahun Ajaran aktif</span>
                @endif
            </div>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Target capaian otomatis mengikuti satu Tahun Ajaran yang sedang aktif.
            </small>
            @error('akademik_master_tahun_ajaran_id')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Tingkat -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="tingkat" class="font-weight-bold">
                Tingkat <span class="text-danger">*</span>
            </label>
            <select name="tingkat" id="tingkat" class="form-control @error('tingkat') is-invalid @enderror" required>
                <option value="">-- Pilih Tingkat --</option>
                @foreach ($tingkatOptions as $opsiTingkat)
                    <option value="{{ $opsiTingkat }}" @selected($selectedTingkat === (string) $opsiTingkat)>
                        Tingkat {{ $opsiTingkat }}
                    </option>
                @endforeach
                @if ($selectedTingkat !== '' && ! in_array($selectedTingkat, $tingkatOptions, true))
                    <option value="{{ $selectedTingkat }}" selected>Tingkat {{ $selectedTingkat }}</option>
                @endif
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Pilih tingkatan siswa T2Q (misal PG, A, B, atau 1 s.d. 6).
            </small>
            @error('tingkat')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Daftar Jilid -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_master_t2q_daftar_jilid_id" class="font-weight-bold">
                Daftar Jilid T2Q
            </label>
            <select name="akademik_master_t2q_daftar_jilid_id" id="akademik_master_t2q_daftar_jilid_id"
                class="form-control @error('akademik_master_t2q_daftar_jilid_id') is-invalid @enderror">
                <option value="">-- Tidak Ada / Opsional --</option>
                @foreach ($daftarJilid as $jilid)
                    <option value="{{ $jilid->id }}" @selected($selectedJilidId === (string) $jilid->id)>
                        {{ $jilid->jilid_latin }} ({{ ucfirst((string) $jilid->jenis) }})
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Target bacaan jilid / Al-Qur'an untuk tingkat terkait.
            </small>
            @error('akademik_master_t2q_daftar_jilid_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Surah Al-Qur'an -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_master_t2q_surah_id" class="font-weight-bold">
                Surah Al-Qur'an
            </label>
            <select name="akademik_master_t2q_surah_id" id="akademik_master_t2q_surah_id"
                class="form-control @error('akademik_master_t2q_surah_id') is-invalid @enderror">
                <option value="">-- Tidak Ada / Opsional --</option>
                @foreach ($surahList as $surah)
                    <option value="{{ $surah->id }}" @selected($selectedSurahId === (string) $surah->id)>
                        {{ $surah->label_tampilan }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Target hafalan surah Al-Qur'an. Minimal pilih salah satu (Daftar Jilid atau Surah).
            </small>
            @error('akademik_master_t2q_surah_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mt-3">
    <button type="submit" class="btn btn-xl text-white shadow-sm font-weight-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;"
        @disabled(! $displayTahunAjaran)>
        <i class="fa fa-save mr-1"></i> Simpan
    </button>
</div>
