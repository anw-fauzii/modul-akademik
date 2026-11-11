@php
    $isEdit = isset($ekstrakurikuler);
    $val = fn(string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $ekstrakurikuler->$field : $default);
    $unitOptions = \App\Models\Admin\DataMaster\Ekstrakurikuler::UNIT_OPTIONS;
    $currentUnit = $val('unit');
@endphp

<div class="row">
    <!-- Tahun Ajaran -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_master_tahun_ajaran_id" class="font-weight-bold">
                Tahun Ajaran <span class="text-danger">*</span>
            </label>
            <select name="akademik_master_tahun_ajaran_id" id="akademik_master_tahun_ajaran_id"
                class="form-control @error('akademik_master_tahun_ajaran_id') is-invalid @enderror" required>
                <option value="">-- Pilih Tahun Ajaran --</option>
                @foreach ($tahunAjaran as $ta)
                    <option value="{{ $ta->id }}" @selected((string) $val('akademik_master_tahun_ajaran_id') === (string) $ta->id)>
                        {{ $ta->nama_tahun_ajaran }} - Semester {{ strtoupper($ta->semester) }} {{ $ta->is_active ? '(Aktif)' : '' }}
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

    <!-- Unit -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="unit" class="font-weight-bold">
                Unit <span class="text-danger">*</span>
            </label>
            <select name="unit" id="unit"
                class="form-control @error('unit') is-invalid @enderror" required>
                <option value="">-- Pilih Unit --</option>
                @foreach ($unitOptions as $unit)
                    <option value="{{ $unit }}" @selected((string) $currentUnit === $unit)>
                        {{ $unit }}
                    </option>
                @endforeach
                @if ($currentUnit && !in_array($currentUnit, $unitOptions, true))
                    <option value="{{ $currentUnit }}" selected>{{ $currentUnit }}</option>
                @endif
            </select>
            @error('unit')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Nama Ekstrakurikuler -->
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="nama_ekstrakurikuler" class="font-weight-bold">
                Nama Ekstrakurikuler <span class="text-danger">*</span>
            </label>
            <input name="nama_ekstrakurikuler" id="nama_ekstrakurikuler"
                placeholder="Contoh: Pramuka, Futsal, Panahan, atau Tahfidz" type="text"
                class="form-control @error('nama_ekstrakurikuler') is-invalid @enderror"
                value="{{ $val('nama_ekstrakurikuler') }}" required>
            @error('nama_ekstrakurikuler')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Pembina / Pelatih -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="karyawan_nipy" class="font-weight-bold">
                Pembina / Pelatih
            </label>
            <select name="karyawan_nipy" id="karyawan_nipy"
                class="form-control @error('karyawan_nipy') is-invalid @enderror">
                <option value="">-- Pilih Pembina / Pelatih --</option>
                @foreach ($karyawan as $guru)
                    <option value="{{ $guru->nipy }}" @selected((string) $val('karyawan_nipy') === (string) $guru->nipy)>
                        {{ $guru->nama_gelar }} ({{ $guru->nipy }})
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Guru atau karyawan penanggung jawab kegiatan ekstrakurikuler (opsional).
            </small>
            @error('karyawan_nipy')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Biaya -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="biaya" class="font-weight-bold">
                Biaya (Rp) <span class="text-danger">*</span>
            </label>
            <input name="biaya" id="biaya" placeholder="Contoh: 50000" type="number" min="0" step="1"
                class="form-control @error('biaya') is-invalid @enderror"
                value="{{ $val('biaya', 0) }}" required>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Masukkan nominal biaya dalam Rupiah. Isi <strong>0</strong> jika kegiatan gratis.
            </small>
            @error('biaya')
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

