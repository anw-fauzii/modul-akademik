@php
    $isEdit = isset($jenjangPendidikan);
    $val = fn($field) => old($field, $isEdit ? $jenjangPendidikan->$field : null);
@endphp

<div class="form-row">
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="nama_jenjang_pendidikan">Nama Jenjang Pendidikan</label>
            <input name="nama_jenjang_pendidikan" id="nama_jenjang_pendidikan"
                placeholder="Masukkan Nama Jenjang Pendidikan (Contoh: SD/MI, SMP/MTs, SMA/SMK, S1)" type="text"
                class="form-control @error('nama_jenjang_pendidikan') is-invalid @enderror"
                value="{{ $val('nama_jenjang_pendidikan') }}">
            @error('nama_jenjang_pendidikan')
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
