@php
    $isEdit = isset($sekolah);
    $val = fn (string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $sekolah->$field : $default);
    $jenjangOptions = $jenjangOptions ?? \App\Models\Admin\DataMaster\Sekolah::JENJANG_OPTIONS;
    $currentJenjang = strtoupper((string) $val('jenjang_kelas', 'SD'));
@endphp

<div class="row">
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="jenjang_kelas" class="font-weight-bold">
                Jenjang Pendidikan <span class="text-danger">*</span>
            </label>
            <select name="jenjang_kelas" id="jenjang_kelas"
                class="form-control @error('jenjang_kelas') is-invalid @enderror" required>
                <option value="">-- Pilih Jenjang --</option>
                @foreach ($jenjangOptions as $jenjang)
                    <option value="{{ $jenjang }}" @selected($currentJenjang === $jenjang)>
                        {{ $jenjang }}
                    </option>
                @endforeach
                @if ($currentJenjang !== '' && ! in_array($currentJenjang, $jenjangOptions, true))
                    <option value="{{ $currentJenjang }}" selected>{{ $currentJenjang }}</option>
                @endif
            </select>
            @error('jenjang_kelas')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="nama_yayasan" class="font-weight-bold">Nama Yayasan</label>
            <input name="nama_yayasan" id="nama_yayasan" placeholder="Contoh: Yayasan Prima Insani" type="text"
                class="form-control @error('nama_yayasan') is-invalid @enderror"
                value="{{ $val('nama_yayasan', 'Yayasan Prima Insani') }}">
            @error('nama_yayasan')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="nama_sekolah" class="font-weight-bold">
                Nama Resmi Sekolah <span class="text-danger">*</span>
            </label>
            <input name="nama_sekolah" id="nama_sekolah" placeholder="Contoh: SD Prima Insani / TK Prima Insani" type="text"
                class="form-control @error('nama_sekolah') is-invalid @enderror"
                value="{{ $val('nama_sekolah') }}" required>
            @error('nama_sekolah')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="npsn" class="font-weight-bold">NPSN</label>
            <input name="npsn" id="npsn" placeholder="Contoh: 20209000" type="text"
                class="form-control @error('npsn') is-invalid @enderror" value="{{ $val('npsn') }}">
            @error('npsn')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="nss" class="font-weight-bold">NSS / NSM</label>
            <input name="nss" id="nss" placeholder="Opsional" type="text"
                class="form-control @error('nss') is-invalid @enderror" value="{{ $val('nss') }}">
            @error('nss')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="kode_pos" class="font-weight-bold">Kode Pos</label>
            <input name="kode_pos" id="kode_pos" placeholder="Contoh: 44116" type="text"
                class="form-control @error('kode_pos') is-invalid @enderror" value="{{ $val('kode_pos') }}">
            @error('kode_pos')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="position-relative form-group">
            <label for="alamat" class="font-weight-bold">
                Alamat Jalan Sekolah <span class="text-danger">*</span>
            </label>
            <input name="alamat" id="alamat" placeholder="Contoh: Jl. Cimanuk No. 123" type="text"
                class="form-control @error('alamat') is-invalid @enderror" value="{{ $val('alamat') }}" required>
            @error('alamat')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="position-relative form-group">
            <label for="rt" class="font-weight-bold">RT</label>
            <input name="rt" id="rt" placeholder="01" type="text"
                class="form-control @error('rt') is-invalid @enderror" value="{{ $val('rt') }}">
        </div>
    </div>

    <div class="col-md-2">
        <div class="position-relative form-group">
            <label for="rw" class="font-weight-bold">RW</label>
            <input name="rw" id="rw" placeholder="02" type="text"
                class="form-control @error('rw') is-invalid @enderror" value="{{ $val('rw') }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="kelurahan" class="font-weight-bold">Kelurahan / Desa</label>
            <input name="kelurahan" id="kelurahan" type="text"
                class="form-control @error('kelurahan') is-invalid @enderror" value="{{ $val('kelurahan') }}">
        </div>
    </div>

    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="kecamatan" class="font-weight-bold">Kecamatan</label>
            <input name="kecamatan" id="kecamatan" type="text"
                class="form-control @error('kecamatan') is-invalid @enderror" value="{{ $val('kecamatan') }}">
        </div>
    </div>

    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="kabupaten" class="font-weight-bold">Kabupaten / Kota</label>
            <input name="kabupaten" id="kabupaten" placeholder="Contoh: Kabupaten Garut" type="text"
                class="form-control @error('kabupaten') is-invalid @enderror" value="{{ $val('kabupaten') }}">
        </div>
    </div>

    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="provinsi" class="font-weight-bold">Provinsi</label>
            <input name="provinsi" id="provinsi" placeholder="Contoh: Jawa Barat" type="text"
                class="form-control @error('provinsi') is-invalid @enderror" value="{{ $val('provinsi') }}">
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="telepon" class="font-weight-bold">Telepon</label>
            <input name="telepon" id="telepon" placeholder="Contoh: (0262) 234567" type="text"
                class="form-control @error('telepon') is-invalid @enderror" value="{{ $val('telepon') }}">
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="email" class="font-weight-bold">Email</label>
            <input name="email" id="email" placeholder="Contoh: sd@primainsani.sch.id" type="email"
                class="form-control @error('email') is-invalid @enderror" value="{{ $val('email') }}">
            @error('email')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="website" class="font-weight-bold">Website</label>
            <input name="website" id="website" placeholder="Contoh: https://primainsani.sch.id" type="text"
                class="form-control @error('website') is-invalid @enderror" value="{{ $val('website') }}">
        </div>
    </div>
</div>

<div class="position-relative form-group mt-1">
    <input type="hidden" name="status" value="0">
    <div class="custom-control custom-switch">
        <input type="checkbox" class="custom-control-input" id="sekolahStatus" name="status" value="1"
            @checked((bool) $val('status', true))>
        <label class="custom-control-label font-weight-bold" for="sekolahStatus">
            Aktif digunakan sebagai identitas rapor jenjang ini
        </label>
    </div>
</div>

<div class="form-group mt-3">
    <button type="submit" class="btn btn-xl text-white shadow-sm font-weight-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
        <i class="fa fa-save mr-1"></i> Simpan
    </button>
</div>

