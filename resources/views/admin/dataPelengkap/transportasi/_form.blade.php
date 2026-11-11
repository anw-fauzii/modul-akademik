@php
    $isEdit = isset($transportasi);
    $val = fn($field) => old($field, $isEdit ? $transportasi->$field : null);
@endphp

<div class="form-row">
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="nama_transportasi">Moda Transportasi</label>
            <input name="nama_transportasi" id="nama_transportasi"
                placeholder="Masukkan Nama/Moda Transportasi (Contoh: Jalan Kaki, Sepeda Motor, Mobil Pribadi, Angkutan Umum)" type="text"
                class="form-control @error('nama_transportasi') is-invalid @enderror"
                value="{{ $val('nama_transportasi') }}">
            @error('nama_transportasi')
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
