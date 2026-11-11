@php
    $isEdit = isset($penghasilan);
    $val = fn($field) => old($field, $isEdit ? $penghasilan->$field : null);
@endphp

<div class="form-row">
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="nama_penghasilan">Rentang Penghasilan</label>
            <input name="nama_penghasilan" id="nama_penghasilan"
                placeholder="Masukkan Rentang Penghasilan (Contoh: Rp 1.000.000 - Rp 2.000.000, > Rp 5.000.000)" type="text"
                class="form-control @error('nama_penghasilan') is-invalid @enderror"
                value="{{ $val('nama_penghasilan') }}">
            @error('nama_penghasilan')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.7rem;">
                    {{ strtolower($message) }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group">
    <button type="submit" class="btn btn-xl text-white shadow-sm fw-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
        <i class="fa fa-save mr-1"></i> Simpan
    </button>
</div>
