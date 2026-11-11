@php
    $isEdit = isset($tahunAjaran);
    $val = fn($field) => old($field, $isEdit ? $tahunAjaran->$field : null);
@endphp

<div class="form-row">
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="nama_tahun_ajaran">Nama Tahun Ajaran</label>
            <input name="nama_tahun_ajaran" id="nama_tahun_ajaran" placeholder="Contoh: 2024/2025" type="text"
                class="form-control @error('nama_tahun_ajaran') is-invalid @enderror"
                value="{{ $val('nama_tahun_ajaran') }}">
            @error('nama_tahun_ajaran')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.7rem;">
                    {{ strtolower($message) }}
                </div>
            @enderror
        </div>
    </div>
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="semester">Semester</label>
            <select name="semester" id="semester"
                class="form-control @error('semester') is-invalid @enderror">
                <option value="">-- Pilih Semester --</option>
                <option value="1" @selected((string) $val('semester') === '1')>Semester 1</option>
                <option value="mid 1" @selected((string) $val('semester') === 'mid 1')>Mid Semester 1</option>
                <option value="2" @selected((string) $val('semester') === '2')>Semester 2</option>
                <option value="mid 2" @selected((string) $val('semester') === 'mid 2')>Mid Semester 2</option>
            </select>
            @error('semester')
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
