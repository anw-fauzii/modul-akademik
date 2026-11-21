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

        .t2q-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.15rem 1.25rem;
            align-items: start;
        }

        .t2q-form-field {
            min-width: 0;
            display: flex;
            flex-direction: column;
        }

        .t2q-form-field--full {
            grid-column: 1 / -1;
        }

        .t2q-form-label-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            min-height: 1.65rem;
            margin-bottom: 0.4rem;
        }

        .t2q-form-label-bar label {
            margin-bottom: 0 !important;
            font-size: 0.84rem;
            font-weight: 700;
            color: #334155;
            line-height: 1.25;
        }

        .t2q-sync-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.22rem 0.6rem;
            border-radius: 9999px;
            border: 1px solid #fcd34d;
            background-color: #fffbeb;
            color: #b45309;
            font-size: 0.71rem;
            font-weight: 700;
            line-height: 1.1;
            cursor: pointer;
            white-space: nowrap;
            transition: background-color 0.15s ease, border-color 0.15s ease;
        }

        .t2q-sync-btn:hover {
            background-color: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
        }

        .t2q-form-grid .form-control,
        .t2q-form-grid .s2-wrapper,
        .t2q-form-grid .s2-trigger {
            width: 100% !important;
            box-sizing: border-box;
        }

        .t2q-form-grid .form-control,
        .t2q-form-grid .s2-trigger {
            min-height: 2.5rem;
        }

        .t2q-form-grid input.form-control {
            height: 2.5rem;
        }

        .t2q-form-help {
            display: block;
            margin-top: 0.35rem;
            font-size: 0.74rem;
            color: #64748b;
            line-height: 1.35;
        }

        .t2q-form-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: flex-start;
            gap: 0.65rem;
            margin-top: 1.5rem;
            padding-top: 1.15rem;
            border-top: 1px solid #f1f5f9;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
        }

        .invalid-feedback {
            display: block;
            margin-top: 0.35rem;
            color: #dc2626;
        }

        @media (max-width: 767.98px) {
            .t2q-form-grid {
                grid-template-columns: 1fr;
            }
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

<div class="t2q-form-grid">
    @if ($isEdit)
        <!-- Anggota Kelas Siswa (Baris Penuh di Bagian Atas Saat Edit) -->
        <div class="t2q-form-field t2q-form-field--full">
            <div class="t2q-form-label-bar">
                <label for="anggota_kelas_id">
                    Pilih Anggota Kelas Siswa <span class="text-danger">*</span>
                </label>
            </div>
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
            <small class="t2q-form-help">
                Siswa dan rombel kelas asal yang terhubung ke keanggotaan T2Q ini.
            </small>
            @error('anggota_kelas_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    @else
        <!-- Tahun Ajaran & Semester (Konteks Kandidat Siswa Saat Tambah) -->
        <div class="t2q-form-field">
            <div class="t2q-form-label-bar">
                <label for="filter_tahun_ajaran_id">
                    Tahun Ajaran &amp; Semester <span class="text-danger">*</span>
                </label>
            </div>
            <select name="tahun_ajaran_id" id="filter_tahun_ajaran_id"
                class="form-control @error('tahun_ajaran_id') is-invalid @enderror" required>
                @foreach ($tahunAjaran ?? [] as $ta)
                    <option value="{{ $ta->sqid }}"
                        data-tahun-ajaran-id="{{ $ta->id }}"
                        @selected((string) $val('tahun_ajaran_id', $selectedTahunAjaranId ?? null) === (string) $ta->id)>
                        {{ $ta->nama_tahun_ajaran }} - Semester {{ strtoupper((string) $ta->semester) }} {{ $ta->is_active ? '[Aktif]' : '' }}
                    </option>
                @endforeach
            </select>
            <small class="t2q-form-help">
                Menampilkan daftar Anggota Kelas pada tahun ajaran &amp; semester terpilih.
            </small>
            @error('tahun_ajaran_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    @endif

    <!-- Guru Pembimbing T2Q -->
    <div class="t2q-form-field">
        <div class="t2q-form-label-bar">
            <label for="karyawan_nipy">
                Guru Pembimbing T2Q <span class="text-danger">*</span>
            </label>
        </div>
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
        <small class="t2q-form-help">
            Guru / ustadz pembimbing T2Q untuk siswa terpilih.
        </small>
        @error('karyawan_nipy')
            <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                {{ $message }}
            </div>
        @enderror
    </div>

    <!-- Tingkat T2Q -->
    <div class="t2q-form-field">
        <div class="t2q-form-label-bar">
            <label for="tingkat">
                Tingkat <span class="text-danger">*</span>
            </label>
            @if ($isEdit)
                <button type="button" id="syncTingkatKelasBtn" class="t2q-sync-btn"
                    title="Sesuaikan tingkat dengan kelas siswa terpilih">
                    <i class="fa fa-magic"></i> Samakan dg Kelas
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

        <small class="t2q-form-help">
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

    <!-- Pendaftaran -->
    <div class="t2q-form-field">
        <div class="t2q-form-label-bar">
            <label for="pendaftaran">
                Jenis Pendaftaran <span class="text-danger">*</span>
            </label>
        </div>
        <select name="pendaftaran" id="pendaftaran"
            class="form-control @error('pendaftaran') is-invalid @enderror" required>
            <option value="">-- Pilih Pendaftaran --</option>
            @foreach (\App\Models\Admin\DataMaster\AnggotaT2q::PENDAFTARAN_OPTIONS as $opsi)
                <option value="{{ $opsi }}" @selected((string) $val('pendaftaran', $preselectedPendaftaran ?? null) === (string) $opsi)>
                    {{ $opsi }}
                </option>
            @endforeach
        </select>
        <small class="t2q-form-help">
            Baru / Lanjutan di tahun ajaran ini.
        </small>
        @error('pendaftaran')
            <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                {{ $message }}
            </div>
        @enderror
    </div>

    <!-- Keterangan -->
    <div class="t2q-form-field {{ $isEdit ? '' : 't2q-form-field--full' }}">
        <div class="t2q-form-label-bar">
            <label for="keterangan">Keterangan</label>
        </div>
        <input type="text" name="keterangan" id="keterangan"
            maxlength="255"
            value="{{ $val('keterangan', $preselectedKeterangan) }}"
            placeholder="Opsional (mis. Tilawati Jilid 2 / Tahfidz Juz 30 / Catatan)"
            class="form-control @error('keterangan') is-invalid @enderror">
        <small class="t2q-form-help">
            Catatan tambahan keanggotaan T2Q (maks. 255 karakter).
        </small>
        @error('keterangan')
            <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                {{ $message }}
            </div>
        @enderror
    </div>

    @if (! $isEdit)
        <!-- Multi-Select Siswa Picker Saat Tambah -->
        <div class="t2q-form-field t2q-form-field--full">
            <div class="t2q-form-label-bar">
                <label for="filterSiswaInput">
                    Pilih Anggota Kelas Siswa <span class="text-danger">*</span>
                </label>
            </div>
            @include('admin.dataMaster.partials._siswa_picker', [
                'pickerMode' => 't2q',
                'anggotaKelas' => $anggotaKelas,
                'excludedTahunAjaranIds' => $excludedTahunAjaranIds ?? [],
                'hiddenRegisteredCount' => $hiddenRegisteredCount ?? 0,
                'hiddenInactiveCount' => $hiddenInactiveCount ?? 0,
                'hiddenWithoutKelasCount' => $hiddenWithoutKelasCount ?? 0,
                'showHistoryFilter' => true,
            ])
        </div>
    @endif
</div>

<div class="t2q-form-actions">
    @if (! empty($t2qMidIsFinal) && empty($t2qSemIsFinal))
        <div class="w-100 mb-2 p-2 px-3 rounded" style="background: #fffbeb; border: 1px solid #fde68a; font-size: 0.78rem; color: #92400e;">
            <div class="custom-control custom-checkbox">
                <input type="checkbox" class="custom-control-input" id="for_semester_update" name="for_semester_update" value="1" @checked(old('for_semester_update'))>
                <label class="custom-control-label font-weight-semibold" for="for_semester_update">
                    Terapkan perubahan ini untuk periode <strong>Semester</strong> (T2Q Mid Semester kelas ini sudah berstatus Final &amp; dikunci).
                </label>
            </div>
        </div>
    @endif
    <button type="submit" class="btn text-white shadow-sm font-weight-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
        <i class="fa fa-save mr-1"></i> Simpan
    </button>
    <a href="{{ route('anggota-t2q.index') }}" class="btn btn-light border font-weight-semibold px-3"
        style="border-radius: 8px;">
        Batal
    </a>
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

                    tingkatSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    if (window.PIONSelect && typeof window.PIONSelect.refresh === 'function') {
                        window.PIONSelect.refresh(tingkatSelect);
                    }
                });
            }
        });
    </script>
@endonce
