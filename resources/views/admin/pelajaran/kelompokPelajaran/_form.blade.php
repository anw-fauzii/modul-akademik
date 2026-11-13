@php
    $isEdit = isset($kelompokPelajaran);
    $nextUrutan = $nextUrutan ?? 1;
    $val = fn(string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $kelompokPelajaran->$field : $default);
@endphp

<div class="row">
    <!-- Kode -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="kode" class="font-weight-bold">
                Kode Kelompok <span class="text-danger">*</span>
            </label>
            <input name="kode" id="kode" placeholder="Contoh: A, B, C, atau MULOK"
                type="text" maxlength="50"
                class="form-control text-uppercase @error('kode') is-invalid @enderror"
                value="{{ $val('kode') }}" required>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Kode unik kelompok pelajaran (otomatis disimpan dalam huruf kapital).
            </small>
            @error('kode')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Urutan -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="urutan" class="font-weight-bold">
                Urutan Tampil <span class="text-danger">*</span>
            </label>
            <input name="urutan" id="urutan" placeholder="Contoh: 1"
                type="number" min="1" max="9999" step="1"
                class="form-control @error('urutan') is-invalid @enderror"
                value="{{ $val('urutan', $nextUrutan) }}" required>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Menentukan urutan tampilan kelompok pelajaran pada daftar dan rapor.
            </small>
            @error('urutan')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Kelompok -->
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="kelompok" class="font-weight-bold">
                Kelompok Pelajaran <span class="text-danger">*</span>
            </label>
            <input name="kelompok" id="kelompok"
                placeholder="Contoh: Kelompok A (Umum), Kelompok B (Wajib), atau Muatan Lokal"
                type="text" maxlength="255"
                class="form-control @error('kelompok') is-invalid @enderror"
                value="{{ $val('kelompok') }}" required>
            @error('kelompok')
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

