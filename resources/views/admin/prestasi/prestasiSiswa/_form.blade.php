@php
    $isEdit = isset($prestasiSiswa);
    $currentPrestasi = $prestasiSiswa ?? null;
    $siswaOptions = $siswaOptions ?? collect();
    $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
    $activeTahunAjaran = $activeTahunAjaran ?? $tahunAjaranOptions->firstWhere('is_active', true);
    $kategoriOptions = $kategoriOptions ?? \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_OPTIONS;
    $tingkatPresets = $tingkatPresets ?? \App\Models\Admin\Prestasi\PrestasiSiswa::TINGKAT_PRESETS;
    $peringkatPresets = $peringkatPresets ?? \App\Models\Admin\Prestasi\PrestasiSiswa::PERINGKAT_PRESETS;
    $prefillSiswaNis = $prefillSiswaNis ?? null;

    $val = function (string $field, mixed $default = null) use ($isEdit, $currentPrestasi) {
        if (session()->hasOldInput($field)) {
            return old($field);
        }

        if ($isEdit && $currentPrestasi !== null) {
            $value = $currentPrestasi->$field;

            return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
        }

        return $default;
    };

    $selectedSiswaNisList = [];
    if (session()->hasOldInput('siswa_nis')) {
        $oldSiswa = old('siswa_nis', []);
        $selectedSiswaNisList = is_array($oldSiswa) ? $oldSiswa : [$oldSiswa];
    } elseif ($isEdit && $currentPrestasi !== null && $currentPrestasi->relationLoaded('siswa')) {
        $selectedSiswaNisList = $currentPrestasi->siswa->pluck('nis')->all();
    } elseif (filled($prefillSiswaNis)) {
        $selectedSiswaNisList = [(string) $prefillSiswaNis];
    }
    $selectedSiswaNisList = array_values(array_unique(array_filter(array_map('strval', $selectedSiswaNisList))));

    $siswaPickerPayload = $siswaOptions
        ->map(static function ($itemSiswa): array {
            $kelasSiswa = $itemSiswa->relationLoaded('kelas') ? $itemSiswa->kelas : null;
            $kelasLabel = $kelasSiswa
                ? $kelasSiswa->nama_kelas.' ('.$kelasSiswa->jenjang_kelas.')'
                : 'Belum ada kelas';

            return [
                'nis' => (string) $itemSiswa->nis,
                'nama' => (string) $itemSiswa->nama_lengkap,
                'kelas' => $kelasLabel,
                'search' => mb_strtolower($itemSiswa->nama_lengkap.' '.$itemSiswa->nis.' '.$kelasLabel),
            ];
        })
        ->values()
        ->all();

    $selectedTahunAjaranId = (string) ($val('akademik_master_tahun_ajaran_id', $activeTahunAjaran?->id) ?? '');
    $selectedKategori = (string) ($val('kategori', \App\Models\Admin\Prestasi\PrestasiSiswa::KATEGORI_AKADEMIK) ?? '');
    $selectedTingkat = (string) ($val('tingkat', '') ?? '');
    $selectedPeringkat = (string) ($val('peringkat', '') ?? '');
    $existingFotoUrl = $isEdit ? $currentPrestasi?->foto_url : null;
@endphp

@pushOnce('css')
    <style>
        .prestasi-preset-strip {
            display: flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            margin-top: 0.42rem;
        }

        .prestasi-preset-chip {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            border-radius: 999px;
            padding: 0.18rem 0.62rem;
            font-size: 0.72rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.14s ease;
        }

        .prestasi-preset-chip:hover {
            border-color: #fdba74;
            background: #fff7ed;
            color: #c2410c;
        }

        .prestasi-preset-chip.is-active {
            background: #ea580c;
            border-color: #ea580c;
            color: #ffffff;
        }

        /* Minimalist 1-Baris Multi-Siswa Dropdown (Sejajar Select Tahun Ajaran) */
        .prestasi-ms {
            position: relative;
        }

        .prestasi-ms-trigger {
            width: 100%;
            height: 2.45rem;
            padding: 0 2.15rem 0 0.72rem;
            display: flex;
            align-items: center;
            gap: 0.45rem;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 0.625rem;
            color: #0f172a;
            font-size: 0.85rem;
            text-align: left;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .prestasi-ms-trigger:hover {
            border-color: #94a3b8;
        }

        .prestasi-ms.is-open .prestasi-ms-trigger,
        .prestasi-ms-trigger:focus {
            border-color: #ea580c;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.16);
        }

        .prestasi-ms.is-invalid .prestasi-ms-trigger {
            border-color: #ef4444;
            box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.12);
        }

        .prestasi-ms-summary {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            min-width: 0;
            flex: 1 1 auto;
            overflow: hidden;
        }

        .prestasi-ms-placeholder {
            color: #64748b;
            font-size: 0.84rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .prestasi-ms-count-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #ea580c;
            color: #ffffff;
            font-size: 0.7rem;
            font-weight: 800;
            border-radius: 6px;
            padding: 0.12rem 0.48rem;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .prestasi-ms-preview {
            font-size: 0.83rem;
            font-weight: 600;
            color: #0f172a;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            min-width: 0;
        }

        .prestasi-ms-caret {
            position: absolute;
            right: 0.78rem;
            top: 50%;
            transform: translateY(-50%);
            color: #64748b;
            font-size: 0.72rem;
            pointer-events: none;
            transition: transform 0.15s ease;
        }

        .prestasi-ms.is-open .prestasi-ms-caret {
            transform: translateY(-50%) rotate(180deg);
            color: #ea580c;
        }

        .prestasi-ms-menu {
            position: absolute;
            left: 0;
            right: 0;
            top: calc(100% + 6px);
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 12px;
            box-shadow: 0 20px 38px -10px rgba(15, 23, 42, 0.22);
            z-index: 1050;
            display: none;
            overflow: hidden;
        }

        .prestasi-ms.is-open .prestasi-ms-menu {
            display: block;
        }

        .prestasi-ms-selected {
            display: flex;
            flex-wrap: wrap;
            gap: 0.3rem;
            padding: 0.5rem 0.65rem;
            background: #fffaf5;
            border-bottom: 1px solid #f1f5f9;
            max-height: 86px;
            overflow-y: auto;
        }

        .prestasi-siswa-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.32rem;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            border-radius: 7px;
            padding: 0.14rem 0.45rem;
            font-size: 0.72rem;
            font-weight: 600;
            line-height: 1.25;
        }

        .prestasi-siswa-chip-remove {
            border: 0;
            background: transparent;
            color: #c2410c;
            font-size: 0.85rem;
            line-height: 1;
            font-weight: 800;
            cursor: pointer;
            padding: 0 0.08rem;
        }

        .prestasi-siswa-chip-remove:hover {
            color: #7c2d12;
        }

        .prestasi-ms-search-wrap {
            position: relative;
            padding: 0.5rem 0.6rem;
            background: #f8fafc;
            border-bottom: 1px solid #f1f5f9;
        }

        .prestasi-ms-search-wrap i {
            position: absolute;
            left: 1.15rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.75rem;
            pointer-events: none;
        }

        .prestasi-ms-search {
            width: 100%;
            height: 33px;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            background: #ffffff;
            padding: 0.22rem 0.65rem 0.22rem 1.85rem;
            font-size: 0.78rem;
            color: #0f172a;
            outline: 0;
        }

        .prestasi-ms-search:focus {
            border-color: #ea580c;
            box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.12);
        }

        .prestasi-ms-list {
            max-height: 210px;
            overflow-y: auto;
            padding: 0.3rem;
        }

        .prestasi-ms-option {
            width: 100%;
            border: 0;
            background: transparent;
            border-radius: 7px;
            padding: 0.38rem 0.55rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.55rem;
            text-align: left;
            cursor: pointer;
            transition: background-color 0.1s ease;
        }

        .prestasi-ms-option:hover {
            background: #f8fafc;
        }

        .prestasi-ms-option.is-selected {
            background: #fff7ed;
        }

        .prestasi-ms-check {
            width: 15px;
            height: 15px;
            border-radius: 4px;
            border: 1.5px solid #cbd5e1;
            background: #ffffff;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.58rem;
            color: #ffffff;
            flex-shrink: 0;
        }

        .prestasi-ms-option.is-selected .prestasi-ms-check {
            background: #ea580c;
            border-color: #ea580c;
        }

        .prestasi-ms-footer {
            padding: 0.42rem 0.68rem;
            border-top: 1px solid #f1f5f9;
            background: #f8fafc;
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 0.72rem;
        }

        .prestasi-photo-box {
            border: 1.5px dashed #cbd5e1;
            border-radius: 12px;
            padding: 0.9rem 1rem;
            background: #f8fafc;
        }

        .prestasi-photo-preview {
            max-height: 160px;
            border-radius: 10px;
            object-fit: contain;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            padding: 4px;
        }
    </style>
@endPushOnce

<h6 class="font-weight-bold text-uppercase text-warning border-bottom pb-2 mb-3">
    <i class="fa fa-user-graduate mr-1"></i> Siswa Penerima Prestasi (Individu / Beregu) &amp; Periode
</h6>
<div class="row">
    <div class="col-md-7">
        <div class="position-relative form-group">
            <div class="d-flex flex-wrap align-items-center justify-content-between mb-1" style="gap: 0.4rem;">
                <label for="prestasiSiswaTrigger" class="font-weight-bold mb-0">
                    Siswa Penerima Prestasi <span class="text-danger">*</span>
                </label>
                <span id="prestasiPesertaModeBadge" class="badge badge-secondary px-2 py-1" style="font-size: 0.69rem; border-radius: 999px;">
                    Individu / Beregu
                </span>
            </div>

            <div id="prestasiSiswaPickerBox"
                class="prestasi-ms js-prestasi-siswa-picker @error('siswa_nis') is-invalid @enderror @error('siswa_nis.*') is-invalid @enderror">
                <div class="js-prestasi-siswa-inputs d-none">
                    @foreach ($selectedSiswaNisList as $initNis)
                        <input type="hidden" name="siswa_nis[]" value="{{ $initNis }}">
                    @endforeach
                </div>

                <button type="button" id="prestasiSiswaTrigger" class="prestasi-ms-trigger" aria-expanded="false">
                    <span id="prestasiSiswaSummary" class="prestasi-ms-summary"></span>
                    <i class="fa fa-chevron-down prestasi-ms-caret"></i>
                </button>

                <div class="prestasi-ms-menu">
                    <div id="prestasiSelectedChips" class="prestasi-ms-selected {{ $selectedSiswaNisList === [] ? 'd-none' : '' }}"></div>

                    <div class="prestasi-ms-search-wrap">
                        <i class="fa fa-search"></i>
                        <input type="text" id="prestasiSiswaSearch" class="prestasi-ms-search"
                            placeholder="Cari nama siswa, NIS, atau kelas...">
                    </div>

                    <div id="prestasiSiswaList" class="prestasi-ms-list"></div>

                    <div class="prestasi-ms-footer">
                        <span id="prestasiMsFooterCount" class="text-muted">0 siswa dipilih</span>
                        <div class="d-flex align-items-center" style="gap: 0.65rem;">
                            <button type="button" id="prestasiClearSiswaBtn" class="btn btn-link btn-sm p-0 text-danger font-weight-bold" style="font-size: 0.72rem;">
                                Kosongkan
                            </button>
                            <button type="button" id="prestasiDoneSiswaBtn" class="btn btn-link btn-sm p-0 text-primary font-weight-bold" style="font-size: 0.72rem;">
                                Selesai
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <small class="form-text text-muted" style="font-size: 0.73rem;">
                Klik untuk memilih <strong>1 siswa</strong> (individu) atau <strong>beberapa siswa</strong> sekaligus (beregu).
            </small>
            <div id="prestasiSiswaClientError" class="invalid-feedback d-none" style="font-style: italic; font-size: 0.75rem;">
                Pilih minimal satu siswa penerima prestasi.
            </div>
            @error('siswa_nis')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
            @error('siswa_nis.*')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-5">
        <div class="position-relative form-group">
            <label for="akademik_master_tahun_ajaran_id" class="font-weight-bold">
                Tahun Ajaran
            </label>
            <select name="akademik_master_tahun_ajaran_id" id="akademik_master_tahun_ajaran_id"
                class="form-control @error('akademik_master_tahun_ajaran_id') is-invalid @enderror">
                <option value="">-- Tanpa Tahun Ajaran Spesifik --</option>
                @foreach ($tahunAjaranOptions as $ta)
                    <option value="{{ $ta->id }}"
                        @selected($selectedTahunAjaranId === (string) $ta->id)>
                        {{ $ta->nama_tahun_ajaran }} - Semester {{ strtoupper($ta->semester) }}
                        {{ $ta->is_active ? '(Aktif)' : '' }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.73rem;">
                Otomatis memilih Tahun Ajaran aktif agar rekap prestasi mudah difilter per periode.
            </small>
            @error('akademik_master_tahun_ajaran_id')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<h6 class="font-weight-bold text-uppercase text-warning border-bottom pb-2 mt-3 mb-3">
    <i class="fa fa-trophy mr-1"></i> Rincian Kejuaraan &amp; Capaian Prestasi
</h6>
<div class="row">
    <div class="col-md-8">
        <div class="position-relative form-group">
            <label for="nama_prestasi" class="font-weight-bold">
                Nama Prestasi / Kejuaraan <span class="text-danger">*</span>
            </label>
            <input type="text" name="nama_prestasi" id="nama_prestasi" maxlength="255"
                placeholder="Contoh: Olimpiade Sains Nasional (OSN) Matematika SD / Turnamen Futsal Antar SD"
                class="form-control @error('nama_prestasi') is-invalid @enderror"
                value="{{ $val('nama_prestasi') }}" required>
            @error('nama_prestasi')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="kategori" class="font-weight-bold">
                Kategori Prestasi <span class="text-danger">*</span>
            </label>
            <select name="kategori" id="kategori"
                class="form-control @error('kategori') is-invalid @enderror" required>
                @foreach ($kategoriOptions as $katKey => $katLabel)
                    <option value="{{ $katKey }}" @selected($selectedKategori === $katKey)>
                        {{ $katLabel }}
                    </option>
                @endforeach
            </select>
            @error('kategori')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Tingkat (Preset + Ketik Manual) -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="tingkat" class="font-weight-bold">
                Tingkat Kejuaraan <span class="text-danger">*</span>
            </label>
            <input type="text" name="tingkat" id="tingkat" list="tingkatPresetList" maxlength="255"
                placeholder="Klik preset di bawah atau ketik tingkat lainnya..."
                class="form-control @error('tingkat') is-invalid @enderror"
                value="{{ $selectedTingkat }}" required>
            <datalist id="tingkatPresetList">
                @foreach ($tingkatPresets as $tPreset)
                    <option value="{{ $tPreset }}"></option>
                @endforeach
            </datalist>
            <div class="prestasi-preset-strip" data-preset-target="tingkat">
                @foreach ($tingkatPresets as $tPreset)
                    <button type="button"
                        class="prestasi-preset-chip js-prestasi-preset-btn {{ $selectedTingkat === $tPreset ? 'is-active' : '' }}"
                        data-target-input="tingkat"
                        data-preset-value="{{ $tPreset }}">
                        {{ $tPreset }}
                    </button>
                @endforeach
            </div>
            @error('tingkat')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Peringkat (Preset + Ketik Manual) -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="peringkat" class="font-weight-bold">
                Peringkat / Capaian <span class="text-danger">*</span>
            </label>
            <input type="text" name="peringkat" id="peringkat" list="peringkatPresetList" maxlength="255"
                placeholder="Klik preset di bawah atau ketik peringkat lainnya..."
                class="form-control @error('peringkat') is-invalid @enderror"
                value="{{ $selectedPeringkat }}" required>
            <datalist id="peringkatPresetList">
                @foreach ($peringkatPresets as $pPreset)
                    <option value="{{ $pPreset }}"></option>
                @endforeach
            </datalist>
            <div class="prestasi-preset-strip" data-preset-target="peringkat">
                @foreach ($peringkatPresets as $pPreset)
                    <button type="button"
                        class="prestasi-preset-chip js-prestasi-preset-btn {{ $selectedPeringkat === $pPreset ? 'is-active' : '' }}"
                        data-target-input="peringkat"
                        data-preset-value="{{ $pPreset }}">
                        {{ $pPreset }}
                    </button>
                @endforeach
            </div>
            @error('peringkat')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-8">
        <div class="position-relative form-group">
            <label for="penyelenggara" class="font-weight-bold">
                Penyelenggara <span class="text-danger">*</span>
            </label>
            <input type="text" name="penyelenggara" id="penyelenggara" maxlength="255"
                placeholder="Contoh: Dinas Pendidikan Kab. Garut / Kementerian Agama RI / JSIT Jawa Barat"
                class="form-control @error('penyelenggara') is-invalid @enderror"
                value="{{ $val('penyelenggara') }}" required>
            @error('penyelenggara')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="tanggal" class="font-weight-bold">
                Tanggal Perolehan <span class="text-danger">*</span>
            </label>
            <input type="date" name="tanggal" id="tanggal"
                class="form-control @error('tanggal') is-invalid @enderror"
                value="{{ $val('tanggal', now()->format('Y-m-d')) }}" required>
            @error('tanggal')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<h6 class="font-weight-bold text-uppercase text-warning border-bottom pb-2 mt-3 mb-3">
    <i class="fa fa-camera mr-1"></i> Keterangan Tambahan &amp; Dokumentasi Foto
</h6>
<div class="row">
    <div class="col-md-7">
        <div class="position-relative form-group">
            <label for="keterangan" class="font-weight-bold">
                Keterangan / Nama Tim (Opsional)
            </label>
            <textarea name="keterangan" id="keterangan" rows="4"
                placeholder="Contoh: Tim Futsal Garuda SD (Coach Budi). Nomor Sertifikat: 042/DISDIK/XI/2026..."
                class="form-control @error('keterangan') is-invalid @enderror">{{ $val('keterangan') }}</textarea>
            <small class="form-text text-muted" style="font-size: 0.74rem;">
                Tuliskan nama tim/regu, pembina/pelatih, nomor piagam, atau catatan khusus lomba lainnya.
            </small>
            @error('keterangan')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-5">
        <div class="position-relative form-group">
            <label for="foto" class="font-weight-bold">
                Foto Dokumentasi / Sertifikat (Opsional)
            </label>
            <div class="prestasi-photo-box">
                <input type="file" name="foto" id="foto" accept=".jpg,.jpeg,.png,.webp"
                    class="form-control-file @error('foto') is-invalid @enderror">
                <small class="form-text text-muted mt-1" style="font-size: 0.73rem;">
                    Format: <strong>JPG, JPEG, PNG, WEBP</strong> (Maksimal <strong>2 MB</strong>).
                </small>

                <div id="prestasiFotoPreviewWrap" class="mt-2 {{ $existingFotoUrl ? '' : 'd-none' }}">
                    <img id="prestasiFotoPreviewImg"
                        src="{{ $existingFotoUrl ?: '' }}"
                        alt="Preview Foto Prestasi"
                        class="prestasi-photo-preview">
                </div>

                @if ($isEdit && $existingFotoUrl)
                    <div class="custom-control custom-checkbox mt-2">
                        <input type="checkbox" class="custom-control-input" id="remove_foto" name="remove_foto" value="1"
                            @checked((bool) old('remove_foto'))>
                        <label class="custom-control-label text-danger small font-weight-bold" for="remove_foto">
                            Hapus foto dokumentasi saat ini
                        </label>
                    </div>
                @endif
            </div>
            @error('foto')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mt-3">
    <button type="submit" class="btn btn-xl text-white shadow-sm font-weight-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
        <i class="fa fa-save mr-1"></i> Simpan Data Prestasi
    </button>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const SISWA_OPTIONS = @json($siswaPickerPayload);
            let selectedNis = @json($selectedSiswaNisList);

            const pickerBox = document.getElementById('prestasiSiswaPickerBox');
            const inputsWrap = pickerBox ? pickerBox.querySelector('.js-prestasi-siswa-inputs') : null;
            const triggerBtn = document.getElementById('prestasiSiswaTrigger');
            const summaryWrap = document.getElementById('prestasiSiswaSummary');
            const chipsWrap = document.getElementById('prestasiSelectedChips');
            const searchInput = document.getElementById('prestasiSiswaSearch');
            const listWrap = document.getElementById('prestasiSiswaList');
            const modeBadge = document.getElementById('prestasiPesertaModeBadge');
            const footerCount = document.getElementById('prestasiMsFooterCount');
            const clearBtn = document.getElementById('prestasiClearSiswaBtn');
            const doneBtn = document.getElementById('prestasiDoneSiswaBtn');
            const clientError = document.getElementById('prestasiSiswaClientError');

            const siswaByNis = new Map();
            SISWA_OPTIONS.forEach(function (s) {
                siswaByNis.set(String(s.nis), s);
            });

            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function setDropdownOpen(open) {
                if (!pickerBox) return;
                pickerBox.classList.toggle('is-open', open);
                if (triggerBtn) triggerBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
                if (open && searchInput) {
                    searchInput.value = '';
                    renderSiswaList();
                    setTimeout(function () {
                        searchInput.focus();
                    }, 10);
                }
            }

            function renderSelectedState() {
                if (inputsWrap) {
                    inputsWrap.innerHTML = selectedNis.map(function (nis) {
                        return '<input type="hidden" name="siswa_nis[]" value="' + escapeHtml(nis) + '">';
                    }).join('');
                }

                const count = selectedNis.length;

                if (modeBadge) {
                    if (count === 0) {
                        modeBadge.className = 'badge badge-secondary px-2 py-1';
                        modeBadge.textContent = 'Belum dipilih';
                    } else if (count === 1) {
                        modeBadge.className = 'badge badge-primary px-2 py-1';
                        modeBadge.textContent = 'Individu (1 Siswa)';
                    } else {
                        modeBadge.className = 'badge badge-warning text-dark px-2 py-1 font-weight-bold';
                        modeBadge.textContent = 'Beregu (' + count + ' Siswa)';
                    }
                }

                if (footerCount) {
                    footerCount.textContent = count + ' siswa dipilih';
                }

                if (summaryWrap) {
                    if (count === 0) {
                        summaryWrap.innerHTML = '<span class="prestasi-ms-placeholder">Pilih atau cari siswa (bisa &gt;1 siswa)...</span>';
                    } else {
                        const names = selectedNis.map(function (nis) {
                            const info = siswaByNis.get(String(nis));
                            return info ? info.nama : ('NIS ' + nis);
                        });
                        if (count === 1) {
                            const info0 = siswaByNis.get(String(selectedNis[0]));
                            const label = info0 ? (info0.nama + ' (' + info0.kelas + ')') : names[0];
                            summaryWrap.innerHTML = '<span class="prestasi-ms-count-pill">1</span>'
                                + '<span class="prestasi-ms-preview">' + escapeHtml(label) + '</span>';
                        } else {
                            summaryWrap.innerHTML = '<span class="prestasi-ms-count-pill">' + count + ' siswa</span>'
                                + '<span class="prestasi-ms-preview">' + escapeHtml(names.join(', ')) + '</span>';
                        }
                    }
                }

                if (chipsWrap) {
                    if (count === 0) {
                        chipsWrap.classList.add('d-none');
                        chipsWrap.innerHTML = '';
                    } else {
                        chipsWrap.classList.remove('d-none');
                        chipsWrap.innerHTML = selectedNis.map(function (nis) {
                            const info = siswaByNis.get(String(nis));
                            const nama = info ? info.nama : ('NIS ' + nis);
                            const tip = info ? (info.nis + ' • ' + info.kelas) : '';
                            return '<span class="prestasi-siswa-chip" title="' + escapeHtml(tip) + '">'
                                + '<span>' + escapeHtml(nama) + '</span>'
                                + '<button type="button" class="prestasi-siswa-chip-remove js-remove-siswa-chip" data-nis="' + escapeHtml(nis) + '" title="Hapus siswa">&times;</button>'
                                + '</span>';
                        }).join('');
                    }
                }

                if (count > 0 && clientError && pickerBox) {
                    pickerBox.classList.remove('is-invalid');
                    clientError.classList.add('d-none');
                    clientError.classList.remove('d-block');
                }
            }

            function renderSiswaList() {
                if (!listWrap) return;
                const q = String(searchInput ? searchInput.value : '').trim().toLowerCase();
                const selectedSet = new Set(selectedNis.map(String));

                const matched = SISWA_OPTIONS.filter(function (item) {
                    return q === '' || item.search.includes(q);
                });

                const maxVisible = 60;
                const slice = matched.slice(0, maxVisible);

                if (slice.length === 0) {
                    listWrap.innerHTML = '<div class="text-center text-muted small py-3">Tidak ada siswa yang sesuai pencarian.</div>';
                    return;
                }

                let html = slice.map(function (item) {
                    const isSelected = selectedSet.has(item.nis);
                    return '<button type="button" class="prestasi-ms-option js-toggle-siswa-opt ' + (isSelected ? 'is-selected' : '') + '" data-nis="' + escapeHtml(item.nis) + '">'
                        + '<div class="d-flex align-items-center" style="gap: 0.5rem; min-width: 0; flex: 1;">'
                        + '<span class="prestasi-ms-check">' + (isSelected ? '<i class="fa fa-check"></i>' : '') + '</span>'
                        + '<span class="font-weight-semibold text-slate-800 small text-truncate">' + escapeHtml(item.nama) + '</span>'
                        + '</div>'
                        + '<span class="text-muted text-nowrap" style="font-size: 0.7rem;">' + escapeHtml(item.nis + ' • ' + item.kelas) + '</span>'
                        + '</button>';
                }).join('');

                if (matched.length > maxVisible) {
                    html += '<div class="text-center text-muted py-1" style="font-size: 0.71rem;">Menampilkan ' + maxVisible + ' dari ' + matched.length + ' siswa. Ketik nama atau NIS untuk menyaring lebih spesifik.</div>';
                }

                listWrap.innerHTML = html;
            }

            function toggleSiswa(nis) {
                const key = String(nis || '').trim();
                if (!key) return;
                if (selectedNis.includes(key)) {
                    selectedNis = selectedNis.filter(function (v) { return v !== key; });
                } else {
                    selectedNis.push(key);
                }
                renderSelectedState();
                renderSiswaList();
            }

            renderSelectedState();
            renderSiswaList();

            if (triggerBtn) {
                triggerBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    setDropdownOpen(!pickerBox.classList.contains('is-open'));
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', renderSiswaList);
                searchInput.addEventListener('click', function (e) { e.stopPropagation(); });
                searchInput.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') {
                        setDropdownOpen(false);
                    }
                });
            }

            if (listWrap) {
                listWrap.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const opt = e.target.closest('.js-toggle-siswa-opt');
                    if (opt) {
                        toggleSiswa(opt.getAttribute('data-nis'));
                    }
                });
            }

            if (chipsWrap) {
                chipsWrap.addEventListener('click', function (e) {
                    e.stopPropagation();
                    const rm = e.target.closest('.js-remove-siswa-chip');
                    if (rm) {
                        toggleSiswa(rm.getAttribute('data-nis'));
                    }
                });
            }

            if (clearBtn) {
                clearBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    selectedNis = [];
                    renderSelectedState();
                    renderSiswaList();
                });
            }

            if (doneBtn) {
                doneBtn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    setDropdownOpen(false);
                });
            }

            document.addEventListener('click', function (e) {
                if (pickerBox && !pickerBox.contains(e.target)) {
                    setDropdownOpen(false);
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    setDropdownOpen(false);
                }
            });

            const form = pickerBox ? pickerBox.closest('form') : null;
            if (form) {
                form.addEventListener('submit', function (e) {
                    if (selectedNis.length === 0) {
                        e.preventDefault();
                        if (pickerBox) pickerBox.classList.add('is-invalid');
                        if (clientError) {
                            clientError.classList.remove('d-none');
                            clientError.classList.add('d-block');
                        }
                        if (window.toastr) {
                            window.toastr.warning('Pilih minimal satu siswa penerima prestasi.');
                        }
                        setDropdownOpen(true);
                    }
                });
            }

            function syncPresetChips(inputId) {
                const inputEl = document.getElementById(inputId);
                if (!inputEl) return;
                const currentVal = String(inputEl.value || '').trim().toLowerCase();
                document.querySelectorAll('.js-prestasi-preset-btn[data-target-input="' + inputId + '"]').forEach(function (btn) {
                    const presetVal = String(btn.getAttribute('data-preset-value') || '').trim().toLowerCase();
                    btn.classList.toggle('is-active', currentVal !== '' && currentVal === presetVal);
                });
            }

            document.querySelectorAll('.js-prestasi-preset-btn').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const targetId = this.getAttribute('data-target-input');
                    const presetVal = this.getAttribute('data-preset-value') || '';
                    const inputEl = targetId ? document.getElementById(targetId) : null;
                    if (!inputEl) return;
                    inputEl.value = presetVal;
                    syncPresetChips(targetId);
                    inputEl.focus();
                });
            });

            ['tingkat', 'peringkat'].forEach(function (inputId) {
                const el = document.getElementById(inputId);
                if (el) {
                    el.addEventListener('input', function () {
                        syncPresetChips(inputId);
                    });
                }
            });

            const fotoInput = document.getElementById('foto');
            const previewWrap = document.getElementById('prestasiFotoPreviewWrap');
            const previewImg = document.getElementById('prestasiFotoPreviewImg');
            if (fotoInput && previewWrap && previewImg) {
                fotoInput.addEventListener('change', function () {
                    const file = this.files && this.files[0] ? this.files[0] : null;
                    if (!file) return;
                    const reader = new FileReader();
                    reader.onload = function (ev) {
                        previewImg.src = ev.target.result;
                        previewWrap.classList.remove('d-none');
                    };
                    reader.readAsDataURL(file);
                });
            }
        });
    </script>
@endpush
