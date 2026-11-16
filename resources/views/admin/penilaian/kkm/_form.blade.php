@php
    $isEdit = isset($kkm);
    $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
    $mataPelajaranOptions = $mataPelajaranOptions ?? collect();
    $tingkatanOptions = $tingkatanOptions ?? \App\Models\Admin\Penilaian\Kkm::SD_TINGKATAN_OPTIONS;
    $defaultPredikats = $defaultPredikats ?? \App\Models\Admin\Penilaian\Kkm::generateDefaultPredikats(\App\Models\Admin\Penilaian\Kkm::DEFAULT_KKM);
    $defaults = $defaults ?? ['tahun_ajaran_id' => '', 'mata_pelajaran_id' => '', 'tingkatan_kelas' => '1'];

    $val = fn (string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $kkm->$field : $default);

    $existingPredikats = old('predikats');
    if (! is_array($existingPredikats)) {
        if ($isEdit && $kkm->predikats->isNotEmpty()) {
            $existingPredikats = $kkm->predikats->map(static fn ($p): array => [
                'predikat' => $p->predikat,
                'keterangan' => $p->keterangan,
                'nilai_min' => rtrim(rtrim(number_format((float) $p->nilai_min, 2, '.', ''), '0'), '.'),
                'nilai_max' => rtrim(rtrim(number_format((float) $p->nilai_max, 2, '.', ''), '0'), '.'),
                'is_tuntas' => $p->is_tuntas ? '1' : '0',
            ])->all();
        } else {
            $existingPredikats = array_map(static fn (array $p): array => [
                'predikat' => $p['predikat'],
                'keterangan' => $p['keterangan'],
                'nilai_min' => $p['nilai_min'],
                'nilai_max' => $p['nilai_max'],
                'is_tuntas' => $p['is_tuntas'] ? '1' : '0',
            ], $defaultPredikats);
        }
    }

    $currentKkm = $val('nilai_kkm', \App\Models\Admin\Penilaian\Kkm::DEFAULT_KKM);
    $currentKkmFormatted = rtrim(rtrim(number_format((float) $currentKkm, 2, '.', ''), '0'), '.');
    $currentBobotTp = (int) $val('bobot_tp', \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_TP);
    $currentBobotUjian = (int) $val('bobot_ujian', \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_UJIAN);
    $currentStatus = filter_var($val('status', true), FILTER_VALIDATE_BOOLEAN);
    $selectedTingkat = (string) $val('tingkatan_kelas', $defaults['tingkatan_kelas']);
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
            padding: 1.5rem 1.75rem;
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
            .tp-col-3 { grid-column: span 3 / span 3; }
            .tp-col-4 { grid-column: span 4 / span 4; }
            .tp-col-6 { grid-column: span 6 / span 6; }
            .tp-col-12 { grid-column: span 12 / span 12; }
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
            gap: 0.4rem;
            min-height: 1.35rem;
            margin: 0 0 0.4rem 0;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #1e293b;
        }

        .tp-control {
            display: block;
            width: 100%;
            box-sizing: border-box;
            height: 42px;
            padding: 0 0.85rem;
            font-size: 0.875rem;
            color: #0f172a;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 10px;
        }

        .tp-control:focus {
            border-color: #ea580c;
            outline: 0;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.15);
        }

        .tp-control.is-invalid {
            border-color: #ef4444 !important;
            background: #fef2f2 !important;
        }

        .tp-error-msg {
            margin-top: 0.35rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: #dc2626;
        }

        .kkm-range-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0.75rem;
            font-size: 0.82rem;
        }

        .kkm-range-table th {
            text-align: left;
            padding: 0.55rem 0.65rem;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 0.74rem;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }

        .kkm-range-table td {
            padding: 0.45rem 0.55rem;
            border: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        .kkm-copy-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.4rem;
        }

        .kkm-copy-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.32rem 0.7rem;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            font-size: 0.78rem;
            font-weight: 600;
            cursor: pointer;
        }

        .tp-form-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.85rem;
            padding: 1rem 1.75rem;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }
    </style>
@endpush

<div class="tp-form-body">
    @if ($errors->any())
        <div class="mb-4 p-3 rounded" style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; font-size: 0.82rem;">
            <strong>Periksa kembali input KKM &amp; rentang predikat:</strong>
            <ul class="mb-0 mt-1 pl-3">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="tp-grid-12 mb-4 pb-4" style="border-bottom: 1px solid #f1f5f9;">
        <div class="tp-field tp-col-6">
            <label for="akademik_master_tahun_ajaran_id" class="tp-label">
                <span>Tahun Ajaran / Semester <span class="text-danger">*</span></span>
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

        <div class="tp-field tp-col-6">
            <label for="akademik_pelajaran_mata_pelajaran_id" class="tp-label">
                <span>Mata Pelajaran (SD) <span class="text-danger">*</span></span>
            </label>
            <select name="akademik_pelajaran_mata_pelajaran_id" id="akademik_pelajaran_mata_pelajaran_id"
                class="form-control tp-control @error('akademik_pelajaran_mata_pelajaran_id') is-invalid @enderror" required>
                <option value="">-- Pilih Mata Pelajaran SD --</option>
                @foreach ($mataPelajaranOptions as $mp)
                    <option value="{{ $mp->id }}"
                        @selected((string) $val('akademik_pelajaran_mata_pelajaran_id', $defaults['mata_pelajaran_id']) === (string) $mp->id)>
                        [{{ $mp->singkatan }}] {{ $mp->nama_mata_pelajaran }}
                    </option>
                @endforeach
            </select>
            @error('akademik_pelajaran_mata_pelajaran_id')
                <div class="tp-error-msg">{{ $message }}</div>
            @enderror
        </div>

        <div class="tp-field tp-col-4">
            <label for="tingkatan_kelas" class="tp-label">
                <span>Tingkatan Kelas (Jenjang SD) <span class="text-danger">*</span></span>
            </label>
            <select name="tingkatan_kelas" id="tingkatan_kelas"
                class="form-control tp-control @error('tingkatan_kelas') is-invalid @enderror" required>
                @foreach ($tingkatanOptions as $tingkat)
                    <option value="{{ $tingkat }}" @selected($selectedTingkat === (string) $tingkat)>
                        Kelas {{ $tingkat }} SD
                    </option>
                @endforeach
            </select>
            @error('tingkatan_kelas')
                <div class="tp-error-msg">{{ $message }}</div>
            @enderror
        </div>

        <div class="tp-field tp-col-4">
            <label for="nilai_kkm" class="tp-label">
                <span>Nilai KKM <span class="text-danger">*</span></span>
            </label>
            <input type="number" step="0.01" min="1" max="100" name="nilai_kkm" id="nilai_kkm"
                class="form-control tp-control @error('nilai_kkm') is-invalid @enderror"
                value="{{ $currentKkmFormatted }}" required>
            @error('nilai_kkm')
                <div class="tp-error-msg">{{ $message }}</div>
            @enderror
        </div>

        <div class="tp-field tp-col-4">
            <label for="status" class="tp-label">
                <span>Status <span class="text-danger">*</span></span>
            </label>
            <select name="status" id="status" class="form-control tp-control" required>
                <option value="1" @selected($currentStatus)>Aktif</option>
                <option value="0" @selected(! $currentStatus)>Nonaktif</option>
            </select>
        </div>

        <div class="tp-field tp-col-4">
            <label for="bobot_tp" class="tp-label">
                <span>Bobot Rata-rata NA TP (%) <span class="text-danger">*</span></span>
            </label>
            <input type="number" min="0" max="100" step="1" name="bobot_tp" id="bobot_tp"
                class="form-control tp-control @error('bobot_tp') is-invalid @enderror"
                value="{{ $currentBobotTp }}" required>
            @error('bobot_tp')
                <div class="tp-error-msg">{{ $message }}</div>
            @enderror
        </div>

        <div class="tp-field tp-col-4">
            <label for="bobot_ujian" class="tp-label">
                <span>Bobot Nilai Ujian STS/SAS (%) <span class="text-danger">*</span></span>
            </label>
            <input type="number" min="0" max="100" step="1" name="bobot_ujian" id="bobot_ujian"
                class="form-control tp-control @error('bobot_ujian') is-invalid @enderror"
                value="{{ $currentBobotUjian }}" required>
            @error('bobot_ujian')
                <div class="tp-error-msg">{{ $message }}</div>
            @enderror
        </div>

        <div class="tp-field tp-col-4">
            <span class="tp-label">
                <span>Preset Cepat Bobot Rapor</span>
            </span>
            <div class="d-flex flex-wrap gap-1 pt-1">
                <button type="button" class="btn btn-sm btn-light js-kkm-bobot-preset" data-tp="100" data-ujian="0"
                    style="border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.74rem; font-weight: 600;">
                    100% TP
                </button>
                <button type="button" class="btn btn-sm btn-light js-kkm-bobot-preset" data-tp="70" data-ujian="30"
                    style="border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.74rem; font-weight: 600;">
                    70% TP / 30% Ujian
                </button>
                <button type="button" class="btn btn-sm btn-light js-kkm-bobot-preset" data-tp="60" data-ujian="40"
                    style="border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.74rem; font-weight: 600;">
                    60% / 40%
                </button>
            </div>
        </div>

        @if (! $isEdit)
            <div class="tp-field tp-col-12">
                <span class="tp-label">
                    <span>Terapkan Sekaligus ke Tingkatan Kelas Lain <span class="text-muted small">(opsional)</span></span>
                </span>
                <div class="kkm-copy-grid">
                    @foreach ($tingkatanOptions as $tingkat)
                        <label class="kkm-copy-pill">
                            <input type="checkbox" name="copy_to_tingkatan[]" value="{{ $tingkat }}">
                            Kelas {{ $tingkat }}
                        </label>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    <div>
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
            <div>
                <h3 class="mb-0" style="font-size: 0.92rem; font-weight: 700; color: #0f172a;">
                    Rentang Nilai &amp; Predikat
                </h3>
                <div class="small text-muted">
                    Atur rentang dari <strong>0</strong> sampai <strong>100</strong> tanpa jeda kosong atau tumpang tindih.
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" id="btnAutoGeneratePredikats" class="btn btn-sm btn-outline-warning"
                    style="border-radius: 8px; font-weight: 600; color: #c2410c; border-color: #fdba74; background: #fff7ed;">
                    <i class="fa fa-magic mr-1"></i> Generate Rentang dari KKM
                </button>
                <button type="button" id="btnAddPredikatRow" class="btn btn-sm btn-light"
                    style="border-radius: 8px; font-weight: 600;">
                    <i class="fa fa-plus mr-1"></i> Tambah Baris
                </button>
            </div>
        </div>

        <table class="kkm-range-table">
            <thead>
                <tr>
                    <th style="width: 110px;">Predikat</th>
                    <th>Keterangan Rapor</th>
                    <th style="width: 120px;">Nilai Min</th>
                    <th style="width: 120px;">Nilai Max</th>
                    <th style="width: 125px;">Tuntas KKM</th>
                    <th style="width: 60px;" class="text-center">Hapus</th>
                </tr>
            </thead>
            <tbody id="kkmPredikatBody">
                @foreach ($existingPredikats as $idx => $pred)
                    <tr data-row-index="{{ $idx }}">
                        <td>
                            <input type="text" name="predikats[{{ $idx }}][predikat]" maxlength="20" required
                                class="form-control form-control-sm text-uppercase font-weight-bold"
                                value="{{ $pred['predikat'] ?? '' }}" placeholder="A">
                        </td>
                        <td>
                            <input type="text" name="predikats[{{ $idx }}][keterangan]" maxlength="100" required
                                class="form-control form-control-sm"
                                value="{{ $pred['keterangan'] ?? '' }}" placeholder="Sangat Baik">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="100" required
                                name="predikats[{{ $idx }}][nilai_min]"
                                class="form-control form-control-sm text-center"
                                value="{{ $pred['nilai_min'] ?? '' }}">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="100" required
                                name="predikats[{{ $idx }}][nilai_max]"
                                class="form-control form-control-sm text-center"
                                value="{{ $pred['nilai_max'] ?? '' }}">
                        </td>
                        <td>
                            <select name="predikats[{{ $idx }}][is_tuntas]" class="form-control form-control-sm" required>
                                <option value="1" @selected(filter_var($pred['is_tuntas'] ?? true, FILTER_VALIDATE_BOOLEAN))>Tuntas</option>
                                <option value="0" @selected(! filter_var($pred['is_tuntas'] ?? true, FILTER_VALIDATE_BOOLEAN))>Belum Tuntas</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-light text-danger btn-remove-predikat" title="Hapus baris">
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="tp-form-footer">
    <span class="small text-muted">
        Nilai <span class="text-danger">*</span> wajib diisi. Perubahan rentang otomatis menyegarkan predikat rapor terkait.
    </span>
    <div class="d-flex gap-2">
        <a href="{{ route('penilaian-kkm.index') }}" class="btn btn-light" style="border-radius: 10px;">Batal</a>
        <button type="submit" class="btn text-white"
            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px; font-weight: 600;">
            <i class="fa fa-save mr-1"></i> {{ $isEdit ? 'Perbarui KKM' : 'Simpan KKM' }}
        </button>
    </div>
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tbody = document.getElementById('kkmPredikatBody');
            const kkmInput = document.getElementById('nilai_kkm');
            const bobotTpInput = document.getElementById('bobot_tp');
            const bobotUjianInput = document.getElementById('bobot_ujian');
            const autoBtn = document.getElementById('btnAutoGeneratePredikats');
            const addBtn = document.getElementById('btnAddPredikatRow');

            if (bobotTpInput && bobotUjianInput) {
                bobotTpInput.addEventListener('input', function () {
                    const tp = Math.max(0, Math.min(100, parseInt(bobotTpInput.value || '0', 10)));
                    bobotUjianInput.value = 100 - tp;
                });
                bobotUjianInput.addEventListener('input', function () {
                    const ujian = Math.max(0, Math.min(100, parseInt(bobotUjianInput.value || '0', 10)));
                    bobotTpInput.value = 100 - ujian;
                });
                document.querySelectorAll('.js-kkm-bobot-preset').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        bobotTpInput.value = btn.dataset.tp;
                        bobotUjianInput.value = btn.dataset.ujian;
                    });
                });
            }

            if (!tbody) {
                return;
            }

            let nextIndex = tbody.querySelectorAll('tr').length;

            const buildRowHtml = function (idx, item) {
                const isTuntas = String(item.is_tuntas) === '1' || item.is_tuntas === true;
                return `
                    <tr data-row-index="${idx}">
                        <td>
                            <input type="text" name="predikats[${idx}][predikat]" maxlength="20" required
                                class="form-control form-control-sm text-uppercase font-weight-bold"
                                value="${item.predikat || ''}" placeholder="A">
                        </td>
                        <td>
                            <input type="text" name="predikats[${idx}][keterangan]" maxlength="100" required
                                class="form-control form-control-sm"
                                value="${item.keterangan || ''}" placeholder="Keterangan">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="100" required
                                name="predikats[${idx}][nilai_min]"
                                class="form-control form-control-sm text-center"
                                value="${item.nilai_min ?? ''}">
                        </td>
                        <td>
                            <input type="number" step="0.01" min="0" max="100" required
                                name="predikats[${idx}][nilai_max]"
                                class="form-control form-control-sm text-center"
                                value="${item.nilai_max ?? ''}">
                        </td>
                        <td>
                            <select name="predikats[${idx}][is_tuntas]" class="form-control form-control-sm" required>
                                <option value="1" ${isTuntas ? 'selected' : ''}>Tuntas</option>
                                <option value="0" ${!isTuntas ? 'selected' : ''}>Belum Tuntas</option>
                            </select>
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-light text-danger btn-remove-predikat" title="Hapus baris">
                                <i class="fa fa-times"></i>
                            </button>
                        </td>
                    </tr>
                `;
            };

            const generateFromKkm = function (kkmValue) {
                const rounded = Math.max(1, Math.min(100, Math.round(Number(kkmValue) || 75)));
                if (rounded >= 98) {
                    return [
                        { predikat: 'A', keterangan: 'Sangat Baik', nilai_min: rounded, nilai_max: 100, is_tuntas: true },
                        { predikat: 'D', keterangan: 'Perlu Bimbingan', nilai_min: 0, nilai_max: rounded - 1, is_tuntas: false },
                    ];
                }

                const totalPassing = 100 - rounded + 1;
                const baseSpan = Math.floor(totalPassing / 3);
                const rem = totalPassing % 3;
                const spanA = baseSpan + (rem >= 1 ? 1 : 0);
                const spanB = baseSpan + (rem >= 2 ? 1 : 0);

                const minA = 100 - spanA + 1;
                const maxB = minA - 1;
                const minB = maxB - spanB + 1;
                const maxC = minB - 1;

                return [
                    { predikat: 'A', keterangan: 'Sangat Baik', nilai_min: minA, nilai_max: 100, is_tuntas: true },
                    { predikat: 'B', keterangan: 'Baik', nilai_min: minB, nilai_max: maxB, is_tuntas: true },
                    { predikat: 'C', keterangan: 'Cukup', nilai_min: rounded, nilai_max: maxC, is_tuntas: true },
                    { predikat: 'D', keterangan: 'Perlu Bimbingan', nilai_min: 0, nilai_max: rounded - 1, is_tuntas: false },
                ];
            };

            if (autoBtn && kkmInput) {
                autoBtn.addEventListener('click', function () {
                    const items = generateFromKkm(kkmInput.value);
                    tbody.innerHTML = '';
                    items.forEach(function (item) {
                        tbody.insertAdjacentHTML('beforeend', buildRowHtml(nextIndex++, item));
                    });
                });
            }

            if (addBtn) {
                addBtn.addEventListener('click', function () {
                    tbody.insertAdjacentHTML('beforeend', buildRowHtml(nextIndex++, {
                        predikat: '',
                        keterangan: '',
                        nilai_min: '',
                        nilai_max: '',
                        is_tuntas: true,
                    }));
                });
            }

            tbody.addEventListener('click', function (e) {
                const btn = e.target.closest('.btn-remove-predikat');
                if (!btn) {
                    return;
                }
                if (tbody.querySelectorAll('tr').length <= 2) {
                    return;
                }
                btn.closest('tr').remove();
            });
        });
    </script>
@endpush

