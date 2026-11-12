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
    $isEdit = isset($anggotaKelas);
    $excludeId = $isEdit ? (int) $anggotaKelas->id : null;
    $val = fn (string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $anggotaKelas->$field : $default);
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
    <!-- Kelas (terikat Tahun Ajaran & Semester) -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_master_kelas_id" class="font-weight-bold">
                Kelas &amp; Tahun Ajaran Tujuan <span class="text-danger">*</span>
            </label>
            <select name="akademik_master_kelas_id" id="akademik_master_kelas_id"
                class="form-control @error('akademik_master_kelas_id') is-invalid @enderror" required>
                <option value="" data-tahun-ajaran-id="">-- Pilih Kelas --</option>
                @foreach ($kelas as $k)
                    <option value="{{ $k->id }}"
                        data-tahun-ajaran-id="{{ $k->akademik_master_tahun_ajaran_id }}"
                        @selected((string) $val('akademik_master_kelas_id', $preselectedKelasId ?? null) === (string) $k->id)>
                        {{ $k->nama_kelas }} ({{ strtoupper($k->jenjang_kelas) }} - Tingkat {{ $k->tingkatan_kelas }})
                        @if ($k->tahunAjaran)
                            — {{ $k->tahunAjaran->nama_tahun_ajaran }} Semester {{ strtoupper($k->tahunAjaran->semester) }} {{ $k->tahunAjaran->is_active ? '[Aktif]' : '' }}
                        @endif
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Tahun ajaran dan semester otomatis mengikuti kelas yang dipilih.
            </small>
            @error('akademik_master_kelas_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Pendaftaran -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="pendaftaran" class="font-weight-bold">
                Jenis Pendaftaran <span class="text-danger">*</span>
            </label>
            <select name="pendaftaran" id="pendaftaran"
                class="form-control @error('pendaftaran') is-invalid @enderror" required>
                <option value="">-- Pilih Jenis Pendaftaran --</option>
                @foreach (\App\Models\Admin\DataMaster\AnggotaKelas::PENDAFTARAN_OPTIONS as $opsi)
                    <option value="{{ $opsi }}" @selected((string) $val('pendaftaran', $preselectedPendaftaran ?? null) === (string) $opsi)>
                        {{ $opsi }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Status masuk siswa ke dalam rombongan belajar tersebut.
            </small>
            @error('pendaftaran')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<!-- Siswa -->
<div class="position-relative form-group mt-2">
    <label for="siswa_nis" class="font-weight-bold">
        Pilih Siswa <span class="text-danger">*</span>
    </label>

    @if ($isEdit)
        <select name="siswa_nis" id="siswa_nis"
            class="form-control @error('siswa_nis') is-invalid @enderror" required>
            <option value="">-- Pilih Siswa --</option>
            @foreach ($siswa as $s)
                @php
                    $prevKelas = $s->resolvePreviousKelasSummary($excludeId);
                    $prevLabel = $prevKelas
                        ? ' | Kelas Sebelumnya: ' . $prevKelas['nama_kelas'] . ($prevKelas['tahun_ajaran'] ? ' (' . $prevKelas['tahun_ajaran'] . ' Smt ' . strtoupper((string) $prevKelas['semester']) . ')' : '')
                        : ' | Belum ada kelas sebelumnya';
                @endphp
                <option value="{{ $s->nis }}" @selected((string) $val('siswa_nis') === (string) $s->nis)>
                    {{ $s->nama_lengkap ?: 'Tanpa Nama' }} — NIS: {{ $s->nis }}{{ $s->nisn ? ' | NISN: ' . $s->nisn : '' }}{{ $prevLabel }} [{{ $s->status }}]
                </option>
            @endforeach
        </select>
        @error('siswa_nis')
            <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                {{ $message }}
            </div>
        @enderror
    @else
        @include('admin.dataMaster.anggotaKelas._siswa_picker', [
            'siswa' => $siswa,
            'excludedTahunAjaranIds' => $excludedTahunAjaranIds ?? [],
            'hiddenRegisteredCount' => $hiddenRegisteredCount ?? 0,
            'hiddenInactiveCount' => $hiddenInactiveCount ?? 0,
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
