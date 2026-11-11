@php
    $isEdit = isset($pekerjaan);
    $val = fn($field) => old($field, $isEdit ? $pekerjaan->$field : null);
@endphp

<div class="form-row">
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="nama_pekerjaan">Nama Pekerjaan</label>
            <input name="nama_pekerjaan" id="nama_pekerjaan"
                placeholder="Masukkan Nama Pekerjaan (Contoh: PNS, Wiraswasta, Pegawai Swasta, Dokter)" type="text"
                class="form-control @error('nama_pekerjaan') is-invalid @enderror"
                value="{{ $val('nama_pekerjaan') }}">
            @error('nama_pekerjaan')
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
