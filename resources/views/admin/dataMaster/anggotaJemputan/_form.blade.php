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
    $isEdit = isset($anggotaJemputan);
    $excludeId = $isEdit ? (int) $anggotaJemputan->id : null;
    $preselectedKeterangan = $preselectedKeterangan ?? (request()->string('keterangan')->toString() ?: null);
    $val = fn (string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $anggotaJemputan->$field : $default);
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
    <!-- Jemputan (terikat Tahun Ajaran & Semester) -->
    <div class="col-md-5">
        <div class="position-relative form-group">
            <label for="akademik_master_jemputan_id" class="font-weight-bold">
                Layanan Jemputan &amp; Tahun Ajaran <span class="text-danger">*</span>
            </label>
            <select name="akademik_master_jemputan_id" id="akademik_master_jemputan_id"
                class="form-control @error('akademik_master_jemputan_id') is-invalid @enderror" required>
                <option value="" data-tahun-ajaran-id="">-- Pilih Layanan Jemputan --</option>
                @foreach ($jemputan as $item)
                    <option value="{{ $item->id }}"
                        data-tahun-ajaran-id="{{ $item->akademik_master_tahun_ajaran_id }}"
                        @selected((string) $val('akademik_master_jemputan_id', $preselectedJemputanId ?? null) === (string) $item->id)>
                        {{ $item->petugas?->nama_gelar ?? ('Petugas #' . $item->karyawan_nipy) }}
                        — PP: {{ $item->harga_pp_formatted }}
                        @if ($item->tahunAjaran)
                            — {{ $item->tahunAjaran->nama_tahun_ajaran }} Semester {{ strtoupper((string) $item->tahunAjaran->semester) }} {{ $item->tahunAjaran->is_active ? '[Aktif]' : '' }}
                        @endif
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Tahun ajaran dan semester otomatis mengikuti layanan jemputan yang dipilih.
            </small>
            @error('akademik_master_jemputan_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Pendaftaran -->
    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="pendaftaran" class="font-weight-bold">
                Jenis Pendaftaran <span class="text-danger">*</span>
            </label>
            <select name="pendaftaran" id="pendaftaran"
                class="form-control @error('pendaftaran') is-invalid @enderror" required>
                <option value="">-- Pilih Pendaftaran --</option>
                @foreach (\App\Models\Admin\DataMaster\AnggotaJemputan::PENDAFTARAN_OPTIONS as $opsi)
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

    <!-- Keterangan -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="keterangan" class="font-weight-bold">Keterangan</label>
            <input type="text" name="keterangan" id="keterangan"
                maxlength="255"
                value="{{ $val('keterangan', $preselectedKeterangan) }}"
                placeholder="Opsional (mis. PP / 1/2 / Titik Jemput)"
                class="form-control @error('keterangan') is-invalid @enderror">
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Catatan tambahan keanggotaan jemputan (maks. 255 karakter).
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
            <option value="">-- Pilih Anggota Kelas --</option>
            @foreach ($anggotaKelas as $ak)
                @php
                    $siswaRow = $ak->siswa;
                    $kelasRow = $ak->kelas;
                    $prevJemputan = $siswaRow?->resolvePreviousJemputanSummary($excludeId);
                    $prevLabel = $prevJemputan
                        ? ' | Jemputan Lain: ' . $prevJemputan['petugas'] . ($prevJemputan['tahun_ajaran'] ? ' (' . $prevJemputan['tahun_ajaran'] . ' Smt ' . strtoupper((string) $prevJemputan['semester']) . ')' : '')
                        : '';
                @endphp
                <option value="{{ $ak->id }}" @selected((string) $val('anggota_kelas_id') === (string) $ak->id)>
                    {{ $siswaRow?->nama_lengkap ?: 'Tanpa Nama' }} — NIS: {{ $ak->siswa_nis }}
                    @if ($kelasRow)
                        | Kelas: {{ $kelasRow->nama_kelas }}
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
            'pickerMode' => 'jemputan',
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

