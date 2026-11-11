@php
    $isEdit = isset($jemputan);
    $val = fn(string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $jemputan->$field : $default);
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

    <!-- Petugas / Driver Jemputan -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="karyawan_nipy" class="font-weight-bold">
                Petugas / Driver Jemputan <span class="text-danger">*</span>
            </label>
            <select name="karyawan_nipy" id="karyawan_nipy"
                class="form-control @error('karyawan_nipy') is-invalid @enderror" required>
                <option value="">-- Pilih Petugas / Driver --</option>
                @foreach ($karyawan as $guru)
                    <option value="{{ $guru->nipy }}" @selected((string) $val('karyawan_nipy') === (string) $guru->nipy)>
                        {{ $guru->nama_gelar }} ({{ $guru->nipy }})
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Karyawan atau driver penanggung jawab layanan jemputan.
            </small>
            @error('karyawan_nipy')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Harga PP -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="harga_pp" class="font-weight-bold">
                Harga PP (Rp) <span class="text-danger">*</span>
            </label>
            <input name="harga_pp" id="harga_pp" placeholder="Contoh: 200000" type="number" min="0" step="1"
                class="form-control @error('harga_pp') is-invalid @enderror"
                value="{{ $val('harga_pp', 0) }}" required>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Tarif pulang-pergi (PP) dalam Rupiah. Isi <strong>0</strong> jika gratis.
            </small>
            @error('harga_pp')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Harga Setengah -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="harga_setengah" class="font-weight-bold">
                Harga Setengah (Rp) <span class="text-danger">*</span>
            </label>
            <input name="harga_setengah" id="harga_setengah" placeholder="Contoh: 120000" type="number" min="0" step="1"
                class="form-control @error('harga_setengah') is-invalid @enderror"
                value="{{ $val('harga_setengah', 0) }}" required>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Tarif sekali jalan (berangkat atau pulang saja) dalam Rupiah. Isi <strong>0</strong> jika gratis.
            </small>
            @error('harga_setengah')
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

