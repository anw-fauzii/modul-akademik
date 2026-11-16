@php
    $isEdit = isset($tujuanPembelajaran);
    $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
    $mataPelajaranOptions = $mataPelajaranOptions ?? collect();
    $tingkatanByUnit = $tingkatanByUnit ?? \App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran::TINGKATAN_DEFAULTS_BY_UNIT;
    $faseOptions = $faseOptions ?? \App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran::FASE_OPTIONS;
    $defaults = $defaults ?? [
        'tahun_ajaran_id' => '',
        'mata_pelajaran_id' => '',
        'tingkatan_kelas' => '',
    ];

    $val = fn (string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $tujuanPembelajaran->$field : $default);

    $currentStatus = $val('status', true);
    $isStatusActive = filter_var($currentStatus, FILTER_VALIDATE_BOOLEAN);
    $selectedTingkatan = (string) ($val('tingkatan_kelas', $defaults['tingkatan_kelas']) ?? '');
@endphp

@push('css')
    <style>
        .tp-form-shell {
            margin: 0 auto;
        }

        .tp-form-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .tp-form-body {
            padding: 1.5rem;
        }

        @media (min-width: 768px) {
            .tp-form-body {
                padding: 1.75rem 2rem;
            }
        }

        .tp-alert-errors {
            margin-bottom: 1.35rem;
            padding: 0.9rem 1.05rem;
            border-radius: 12px;
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            font-size: 0.8125rem;
        }

        .tp-alert-errors ul {
            margin: 0.35rem 0 0 1.15rem;
            padding: 0;
        }

        .tp-section {
            padding-bottom: 1.35rem;
            margin-bottom: 1.35rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .tp-section:last-of-type {
            padding-bottom: 0;
            margin-bottom: 0;
            border-bottom: none;
        }

        .tp-section-header {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-bottom: 1rem;
        }

        .tp-section-step {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.45rem;
            height: 1.45rem;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #c2410c;
            font-size: 0.72rem;
            font-weight: 800;
            flex-shrink: 0;
        }

        .tp-section-title {
            font-size: 0.875rem;
            font-weight: 700;
            color: #0f172a;
            margin: 0;
        }

        .tp-grid-12 {
            display: grid;
            grid-template-columns: minmax(0, 1fr);
            gap: 1rem 1.25rem;
            align-items: start;
        }

        @media (min-width: 768px) {
            .tp-grid-12 {
                grid-template-columns: repeat(12, minmax(0, 1fr));
            }

            .tp-col-3 {
                grid-column: span 3 / span 3;
            }

            .tp-col-4 {
                grid-column: span 4 / span 4;
            }

            .tp-col-5 {
                grid-column: span 5 / span 5;
            }

            .tp-col-6 {
                grid-column: span 6 / span 6;
            }

            .tp-col-12 {
                grid-column: span 12 / span 12;
            }
        }

        .tp-field {
            display: flex;
            flex-direction: column;
            min-width: 0;
        }

        .tp-label {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            min-height: 1.35rem;
            margin: 0 0 0.42rem 0;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1e293b;
            line-height: 1.3;
        }

        .tp-label-main {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            min-width: 0;
            white-space: nowrap;
        }

        .tp-req {
            color: #dc2626;
            font-weight: 700;
        }

        .tp-opt-pill {
            display: inline-flex;
            align-items: center;
            padding: 0.1rem 0.45rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            font-size: 0.675rem;
            font-weight: 600;
            letter-spacing: 0.01em;
            flex-shrink: 0;
        }

        .tp-control {
            display: block;
            width: 100%;
            box-sizing: border-box;
            height: 42px;
            padding: 0 0.85rem;
            font-size: 0.875rem;
            font-weight: 400;
            line-height: 1.4;
            color: #0f172a;
            background-color: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        select.tp-control {
            padding-right: 2rem;
        }

        textarea.tp-control {
            height: auto;
            min-height: 108px;
            padding: 0.7rem 0.85rem;
            line-height: 1.55;
            resize: vertical;
        }

        .tp-control:focus {
            border-color: #ea580c;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.15);
        }

        .tp-control::placeholder {
            color: #94a3b8;
            text-transform: none;
        }

        .tp-control.is-invalid {
            border-color: #ef4444 !important;
            background-color: #fef2f2 !important;
        }

        .tp-control.is-invalid:focus {
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.16);
        }

        .tp-hint {
            margin-top: 0.35rem;
            font-size: 0.725rem;
            color: #64748b;
            line-height: 1.35;
        }

        .tp-error-msg {
            margin-top: 0.35rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: #dc2626;
            line-height: 1.35;
        }

        .tp-form-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            padding: 1rem 1.5rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        @media (min-width: 768px) {
            .tp-form-footer {
                padding: 1.05rem 2rem;
            }
        }

        .tp-form-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.65rem;
            margin-left: auto;
        }

        .tp-submit-btn {
            background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important;
            border: none;
            border-radius: 10px;
            padding: 0.58rem 1.25rem;
            font-size: 0.875rem;
            font-weight: 600;
            color: #ffffff;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(234, 88, 12, 0.25);
        }

        .tp-submit-btn:hover {
            filter: brightness(0.96);
        }
    </style>
@endpush

<div class="tp-form-body">
    @if ($errors->any())
        <div class="tp-alert-errors" role="alert">
            <div class="font-weight-bold">Periksa kembali isian berikut sebelum menyimpan:</div>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Bagian 1: Konteks Semester, Mapel & Penempatan -->
    <div class="tp-section">
        <div class="tp-section-header">
            <span class="tp-section-step">1</span>
            <h2 class="tp-section-title">Konteks Kurikulum &amp; Penempatan</h2>
        </div>

        <div class="tp-grid-12">
            <!-- Tahun Ajaran / Semester (6/12) -->
            <div class="tp-field tp-col-6">
                <label for="akademik_master_tahun_ajaran_id" class="tp-label">
                    <span class="tp-label-main">
                        Tahun Ajaran / Semester <span class="tp-req">*</span>
                    </span>
                </label>
                <select name="akademik_master_tahun_ajaran_id" id="akademik_master_tahun_ajaran_id"
                    class="form-control tp-control @error('akademik_master_tahun_ajaran_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Tahun Ajaran / Semester --</option>
                    @foreach ($tahunAjaranOptions as $ta)
                        <option value="{{ $ta->id }}"
                            @selected((string) $val('akademik_master_tahun_ajaran_id', $defaults['tahun_ajaran_id']) === (string) $ta->id)>
                            {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                        </option>
                    @endforeach
                </select>
                @error('akademik_master_tahun_ajaran_id')
                    <div class="tp-error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Mata Pelajaran (6/12) -->
            <div class="tp-field tp-col-6">
                <label for="akademik_pelajaran_mata_pelajaran_id" class="tp-label">
                    <span class="tp-label-main">
                        Mata Pelajaran <span class="tp-req">*</span>
                    </span>
                </label>
                <select name="akademik_pelajaran_mata_pelajaran_id" id="akademik_pelajaran_mata_pelajaran_id"
                    class="form-control tp-control @error('akademik_pelajaran_mata_pelajaran_id') is-invalid @enderror" required>
                    <option value="">-- Pilih Mata Pelajaran --</option>
                    @foreach ($mataPelajaranOptions as $mapel)
                        <option value="{{ $mapel->id }}"
                            data-unit="{{ strtoupper((string) $mapel->unit) }}"
                            @selected((string) $val('akademik_pelajaran_mata_pelajaran_id', $defaults['mata_pelajaran_id']) === (string) $mapel->id)>
                            [{{ strtoupper((string) $mapel->unit) }}] {{ $mapel->singkatan }} - {{ $mapel->nama_mata_pelajaran }}
                        </option>
                    @endforeach
                </select>
                @error('akademik_pelajaran_mata_pelajaran_id')
                    <div class="tp-error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Tingkatan Kelas (4/12) -->
            <div class="tp-field tp-col-4">
                <label for="tingkatan_kelas" class="tp-label">
                    <span class="tp-label-main">
                        Tingkatan Kelas <span class="tp-req">*</span>
                    </span>
                </label>
                <select name="tingkatan_kelas" id="tingkatan_kelas"
                    class="form-control tp-control @error('tingkatan_kelas') is-invalid @enderror" required>
                    <option value="">-- Pilih Tingkatan --</option>
                    @foreach ($tingkatanByUnit as $unitCode => $tingkatList)
                        @foreach ($tingkatList as $tingkatVal)
                            <option value="{{ $tingkatVal }}" data-unit="{{ $unitCode }}"
                                @selected($selectedTingkatan === (string) $tingkatVal)>
                                {{ ctype_digit((string) $tingkatVal) ? 'Kelas '.$tingkatVal : $tingkatVal }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
                @error('tingkatan_kelas')
                    <div class="tp-error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Fase (4/12) -->
            <div class="tp-field tp-col-4">
                <label for="fase" class="tp-label">
                    <span class="tp-label-main">Fase</span>
                    <span class="tp-opt-pill">Opsional</span>
                </label>
                <select name="fase" id="fase" class="form-control tp-control @error('fase') is-invalid @enderror">
                    <option value="">Otomatis (sesuai unit &amp; kelas)</option>
                    @foreach ($faseOptions as $faseKey => $faseLabel)
                        <option value="{{ $faseKey }}" @selected((string) $val('fase') === (string) $faseKey)>
                            {{ $faseLabel }}
                        </option>
                    @endforeach
                </select>
                @error('fase')
                    <div class="tp-error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Status (4/12) -->
            <div class="tp-field tp-col-4">
                <label for="status" class="tp-label">
                    <span class="tp-label-main">
                        Status <span class="tp-req">*</span>
                    </span>
                </label>
                <select name="status" id="status" class="form-control tp-control @error('status') is-invalid @enderror" required>
                    <option value="1" @selected($isStatusActive)>Aktif</option>
                    <option value="0" @selected(! $isStatusActive)>Nonaktif</option>
                </select>
                @error('status')
                    <div class="tp-error-msg">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>

    <!-- Bagian 2: Identitas & Rumusan TP -->
    <div class="tp-section">
        <div class="tp-section-header">
            <span class="tp-section-step">2</span>
            <h2 class="tp-section-title">Identitas &amp; Rumusan Tujuan Pembelajaran</h2>
        </div>

        <div class="tp-grid-12">
            <!-- Kode TP (4/12) -->
            <div class="tp-field tp-col-4">
                <label for="kode_tp" class="tp-label">
                    <span class="tp-label-main">
                        Kode TP <span class="tp-req">*</span>
                    </span>
                </label>
                <input name="kode_tp" id="kode_tp" type="text" maxlength="30"
                    placeholder="Contoh: TP-01 / MTK.1.1"
                    class="form-control tp-control text-uppercase @error('kode_tp') is-invalid @enderror"
                    value="{{ $val('kode_tp') }}" required>
                @error('kode_tp')
                    <div class="tp-error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Elemen (5/12) -->
            <div class="tp-field tp-col-5">
                <label for="elemen" class="tp-label">
                    <span class="tp-label-main">Elemen</span>
                    <span class="tp-opt-pill">Opsional</span>
                </label>
                <input name="elemen" id="elemen" type="text" maxlength="150"
                    placeholder="Contoh: Bilangan / Jati Diri"
                    class="form-control tp-control @error('elemen') is-invalid @enderror"
                    value="{{ $val('elemen') }}">
                @error('elemen')
                    <div class="tp-error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Urutan (3/12) -->
            <div class="tp-field tp-col-3">
                <label for="urutan" class="tp-label">
                    <span class="tp-label-main">
                        Urutan <span class="tp-req">*</span>
                    </span>
                </label>
                <input name="urutan" id="urutan" type="number" min="1" max="999"
                    class="form-control tp-control @error('urutan') is-invalid @enderror"
                    value="{{ $val('urutan', 1) }}" required>
                @error('urutan')
                    <div class="tp-error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Lingkup Materi (12/12) -->
            <div class="tp-field tp-col-12">
                <label for="lingkup_materi" class="tp-label">
                    <span class="tp-label-main">Lingkup Materi</span>
                    <span class="tp-opt-pill">Opsional</span>
                </label>
                <input name="lingkup_materi" id="lingkup_materi" type="text" maxlength="255"
                    placeholder="Contoh: Bilangan Cacah 1-20, Operasi Penjumlahan Sederhana"
                    class="form-control tp-control @error('lingkup_materi') is-invalid @enderror"
                    value="{{ $val('lingkup_materi') }}">
                @error('lingkup_materi')
                    <div class="tp-error-msg">{{ $message }}</div>
                @enderror
            </div>

            <!-- Rumusan Tujuan Pembelajaran (12/12) -->
            <div class="tp-field tp-col-12">
                <label for="tujuan_pembelajaran" class="tp-label">
                    <span class="tp-label-main">
                        Rumusan Tujuan Pembelajaran <span class="tp-req">*</span>
                    </span>
                </label>
                <textarea name="tujuan_pembelajaran" id="tujuan_pembelajaran" rows="4" maxlength="2000" required
                    placeholder="Contoh: Peserta didik mampu membilang, membaca, dan menuliskan bilangan cacah sampai 20 dengan tepat."
                    class="form-control tp-control @error('tujuan_pembelajaran') is-invalid @enderror">{{ $val('tujuan_pembelajaran') }}</textarea>
                <div class="tp-hint">Maksimal 2000 karakter. Gunakan kalimat kompetensi + lingkup materi yang jelas dan terukur.</div>
                @error('tujuan_pembelajaran')
                    <div class="tp-error-msg">{{ $message }}</div>
                @enderror
            </div>
        </div>
    </div>
</div>

<div class="tp-form-footer">
    <span class="tp-hint" style="margin-top: 0;">
        Tanda <span class="tp-req">*</span> menandakan kolom wajib diisi.
    </span>

    <div class="tp-form-actions">
        <a href="{{ route('tujuan-pembelajaran.index') }}" class="btn btn-light" style="border-radius: 10px;">
            Batal
        </a>
        <button type="submit" class="btn tp-submit-btn" id="submitBtn">
            <i class="fa fa-save mr-1"></i> {{ $isEdit ? 'Perbarui TP' : 'Simpan TP' }}
        </button>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const mapelSelect = document.getElementById('akademik_pelajaran_mata_pelajaran_id');
            const tingkatanSelect = document.getElementById('tingkatan_kelas');
            const faseSelect = document.getElementById('fase');

            if (!mapelSelect || !tingkatanSelect) {
                return;
            }

            const filterTingkatanByUnit = function () {
                const selectedMapelOption = mapelSelect.options[mapelSelect.selectedIndex];
                const selectedUnit = selectedMapelOption ? (selectedMapelOption.dataset.unit || '') : '';

                Array.from(tingkatanSelect.options).forEach(function (option) {
                    if (option.value === '') {
                        option.hidden = false;
                        return;
                    }

                    const matchesUnit = !selectedUnit || option.dataset.unit === selectedUnit;
                    option.hidden = !matchesUnit;
                    option.disabled = !matchesUnit;
                });

                const currentSelected = tingkatanSelect.options[tingkatanSelect.selectedIndex];
                if (currentSelected && currentSelected.value !== '' && currentSelected.disabled) {
                    tingkatanSelect.value = '';
                }
            };

            const suggestDefaultFase = function () {
                if (!faseSelect || faseSelect.value !== '') {
                    return;
                }

                const selectedMapelOption = mapelSelect.options[mapelSelect.selectedIndex];
                const unit = selectedMapelOption ? (selectedMapelOption.dataset.unit || '') : '';
                const tingkat = (tingkatanSelect.value || '').toUpperCase().trim();

                if (unit === 'PG' || unit === 'TK' || unit === 'PAUD') {
                    faseSelect.value = 'Fondasi';
                } else if (tingkat === '1' || tingkat === '2') {
                    faseSelect.value = 'A';
                } else if (tingkat === '3' || tingkat === '4') {
                    faseSelect.value = 'B';
                } else if (tingkat === '5' || tingkat === '6') {
                    faseSelect.value = 'C';
                }
            };

            mapelSelect.addEventListener('change', function () {
                filterTingkatanByUnit();
                suggestDefaultFase();
            });

            tingkatanSelect.addEventListener('change', suggestDefaultFase);

            filterTingkatanByUnit();
        });
    </script>
@endpush
