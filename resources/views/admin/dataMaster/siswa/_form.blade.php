@php
    $isEdit = isset($siswa);
    $currentSiswa = $siswa ?? null;
    $val = function (string $field, mixed $default = null) use ($isEdit, $currentSiswa) {
        if (session()->hasOldInput($field)) {
            return old($field);
        }

        if ($isEdit && $currentSiswa !== null) {
            $value = $currentSiswa->$field;

            return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
        }

        return $default;
    };
@endphp

<!-- BAGIAN 1: AKADEMIK & LAYANAN -->
<h6 class="font-weight-bold text-uppercase text-warning border-bottom pb-2 mb-3">
    <i class="pe-7s-notebook mr-1"></i> Data Akademik & Layanan
</h6>
<div class="row">
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="nis" class="font-weight-bold">
                NIS <span class="text-danger">*</span>
            </label>
            <input name="nis" id="nis" placeholder="Contoh: 202601001" type="text"
                class="form-control @error('nis') is-invalid @enderror"
                value="{{ $val('nis') }}" required>
            @error('nis')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="jenis_pendaftaran" class="font-weight-bold">Jenis Pendaftaran</label>
            <select name="jenis_pendaftaran" id="jenis_pendaftaran"
                class="form-control @error('jenis_pendaftaran') is-invalid @enderror">
                <option value="">-- Pilih Jenis Pendaftaran --</option>
                @foreach (\App\Models\Admin\DataMaster\Siswa::JENIS_PENDAFTARAN_OPTIONS as $option)
                    <option value="{{ $option }}" @selected((string) $val('jenis_pendaftaran') === $option)>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
            @error('jenis_pendaftaran')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="status" class="font-weight-bold">
                Status Siswa <span class="text-danger">*</span>
            </label>
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                @foreach (\App\Models\Admin\DataMaster\Siswa::STATUS_OPTIONS as $option)
                    <option value="{{ $option }}" @selected((string) $val('status', 'Aktif') === $option)>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
            @error('status')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_master_kelas_id" class="font-weight-bold">Kelas</label>
            <select name="akademik_master_kelas_id" id="akademik_master_kelas_id"
                class="form-control @error('akademik_master_kelas_id') is-invalid @enderror">
                <option value="">-- Pilih Kelas --</option>
                @foreach ($kelas as $itemKelas)
                    <option value="{{ $itemKelas->id }}" @selected((string) $val('akademik_master_kelas_id') === (string) $itemKelas->id)>
                        {{ $itemKelas->nama_kelas }} ({{ $itemKelas->jenjang_kelas }})
                        @if ($itemKelas->tahunAjaran)
                            - {{ $itemKelas->tahunAjaran->nama_tahun_ajaran }}
                        @endif
                    </option>
                @endforeach
            </select>
            @error('akademik_master_kelas_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="karyawan_nipy" class="font-weight-bold">Guru Pembimbing / Wali</label>
            <select name="karyawan_nipy" id="karyawan_nipy"
                class="form-control @error('karyawan_nipy') is-invalid @enderror">
                <option value="">-- Pilih Guru Pembimbing --</option>
                @foreach ($karyawan as $guru)
                    <option value="{{ $guru->nipy }}" @selected((string) $val('karyawan_nipy') === (string) $guru->nipy)>
                        {{ $guru->nama_gelar }} ({{ $guru->nipy }})
                    </option>
                @endforeach
            </select>
            @error('karyawan_nipy')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_master_ekstrakuler_id" class="font-weight-bold">Ekstrakurikuler</label>
            <select name="akademik_master_ekstrakuler_id" id="akademik_master_ekstrakuler_id"
                class="form-control @error('akademik_master_ekstrakuler_id') is-invalid @enderror">
                <option value="">-- Pilih Ekstrakurikuler --</option>
                @foreach ($ekstrakurikuler as $ekskul)
                    <option value="{{ $ekskul->id }}" @selected((string) $val('akademik_master_ekstrakuler_id') === (string) $ekskul->id)>
                        {{ $ekskul->nama_ekstrakurikuler }} ({{ $ekskul->unit }})
                    </option>
                @endforeach
            </select>
            @error('akademik_master_ekstrakuler_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_master_jemputan_id" class="font-weight-bold">Layanan Jemputan</label>
            <select name="akademik_master_jemputan_id" id="akademik_master_jemputan_id"
                class="form-control @error('akademik_master_jemputan_id') is-invalid @enderror">
                <option value="">-- Pilih Layanan Jemputan --</option>
                @foreach ($jemputan as $itemJemputan)
                    <option value="{{ $itemJemputan->id }}" @selected((string) $val('akademik_master_jemputan_id') === (string) $itemJemputan->id)>
                        {{ $itemJemputan->petugas?->nama_gelar ?? 'NIPY: ' . $itemJemputan->karyawan_nipy }}
                        (PP: {{ $itemJemputan->harga_pp_formatted }})
                    </option>
                @endforeach
            </select>
            @error('akademik_master_jemputan_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<!-- BAGIAN 2: BIODATA PRIBADI -->
<h6 class="font-weight-bold text-uppercase text-warning border-bottom pb-2 mt-3 mb-3">
    <i class="pe-7s-user mr-1"></i> Biodata & Identitas Pribadi
</h6>
<div class="row">
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="nama_lengkap" class="font-weight-bold">
                Nama Lengkap <span class="text-danger">*</span>
            </label>
            <input name="nama_lengkap" id="nama_lengkap" type="text"
                class="form-control @error('nama_lengkap') is-invalid @enderror"
                value="{{ $val('nama_lengkap') }}" required>
            @error('nama_lengkap')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="jenis_kelamin" class="font-weight-bold">
                Jenis Kelamin <span class="text-danger">*</span>
            </label>
            <select name="jenis_kelamin" id="jenis_kelamin"
                class="form-control @error('jenis_kelamin') is-invalid @enderror" required>
                <option value="">-- Pilih --</option>
                @foreach (\App\Models\Admin\DataMaster\Siswa::JENIS_KELAMIN_OPTIONS as $key => $label)
                    <option value="{{ $key }}" @selected((string) $val('jenis_kelamin') === $key)>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @error('jenis_kelamin')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="nisn" class="font-weight-bold">NISN</label>
            <input name="nisn" id="nisn" type="text"
                class="form-control @error('nisn') is-invalid @enderror" value="{{ $val('nisn') }}">
            @error('nisn')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="nik" class="font-weight-bold">NIK</label>
            <input name="nik" id="nik" type="text"
                class="form-control @error('nik') is-invalid @enderror" value="{{ $val('nik') }}">
            @error('nik')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="no_kk" class="font-weight-bold">No. Kartu Keluarga (KK)</label>
            <input name="no_kk" id="no_kk" type="text"
                class="form-control @error('no_kk') is-invalid @enderror" value="{{ $val('no_kk') }}">
            @error('no_kk')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="akta_lahir" class="font-weight-bold">No. Akta Lahir</label>
            <input name="akta_lahir" id="akta_lahir" type="text"
                class="form-control @error('akta_lahir') is-invalid @enderror" value="{{ $val('akta_lahir') }}">
            @error('akta_lahir')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="tempat_lahir" class="font-weight-bold">Tempat Lahir</label>
            <input name="tempat_lahir" id="tempat_lahir" type="text"
                class="form-control @error('tempat_lahir') is-invalid @enderror" value="{{ $val('tempat_lahir') }}">
            @error('tempat_lahir')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="tanggal_lahir" class="font-weight-bold">Tanggal Lahir</label>
            <input name="tanggal_lahir" id="tanggal_lahir" type="date"
                class="form-control @error('tanggal_lahir') is-invalid @enderror" value="{{ $val('tanggal_lahir') }}">
            @error('tanggal_lahir')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="agama" class="font-weight-bold">Agama</label>
            <select name="agama" id="agama" class="form-control @error('agama') is-invalid @enderror">
                <option value="">-- Pilih Agama --</option>
                @foreach (\App\Models\Admin\DataMaster\Siswa::AGAMA_OPTIONS as $option)
                    <option value="{{ $option }}" @selected((string) $val('agama') === $option)>{{ $option }}</option>
                @endforeach
            </select>
            @error('agama')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="kewarganegaraan" class="font-weight-bold">Kewarganegaraan</label>
            <select name="kewarganegaraan" id="kewarganegaraan"
                class="form-control @error('kewarganegaraan') is-invalid @enderror">
                <option value="">-- Pilih --</option>
                @foreach (\App\Models\Admin\DataMaster\Siswa::KEWARGANEGARAAN_OPTIONS as $option)
                    <option value="{{ $option }}" @selected((string) $val('kewarganegaraan', 'WNI') === $option)>
                        {{ $option }}
                    </option>
                @endforeach
            </select>
            @error('kewarganegaraan')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="nama_negara" class="font-weight-bold">Nama Negara</label>
            <input name="nama_negara" id="nama_negara" type="text"
                class="form-control @error('nama_negara') is-invalid @enderror"
                value="{{ $val('nama_negara', 'Indonesia') }}">
            @error('nama_negara')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="anak_ke" class="font-weight-bold">Anak Ke</label>
            <input name="anak_ke" id="anak_ke" type="number" min="1"
                class="form-control @error('anak_ke') is-invalid @enderror" value="{{ $val('anak_ke') }}">
            @error('anak_ke')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="jumlah_saudara" class="font-weight-bold">Jumlah Saudara</label>
            <input name="jumlah_saudara" id="jumlah_saudara" type="number" min="0"
                class="form-control @error('jumlah_saudara') is-invalid @enderror" value="{{ $val('jumlah_saudara') }}">
            @error('jumlah_saudara')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_pelengkap_berkebutuhan_khusus_id" class="font-weight-bold">Berkebutuhan Khusus</label>
            <select name="akademik_pelengkap_berkebutuhan_khusus_id" id="akademik_pelengkap_berkebutuhan_khusus_id"
                class="form-control @error('akademik_pelengkap_berkebutuhan_khusus_id') is-invalid @enderror">
                <option value="">-- Pilih Berkebutuhan Khusus --</option>
                @foreach ($berkebutuhanKhusus as $itemKhusus)
                    <option value="{{ $itemKhusus->id }}" @selected((string) $val('akademik_pelengkap_berkebutuhan_khusus_id') === (string) $itemKhusus->id)>
                        {{ $itemKhusus->nama_berkebutuhan_khusus }}
                    </option>
                @endforeach
            </select>
            @error('akademik_pelengkap_berkebutuhan_khusus_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="avatar" class="font-weight-bold">Avatar / Foto Siswa</label>
            <input name="avatar" id="avatar" type="file" accept="image/*"
                class="form-control-file @error('avatar') is-invalid @enderror">
            <small class="form-text text-muted" style="font-size: 0.75rem;">Format JPG/PNG/WEBP maksimal 2 MB.</small>
            @if ($isEdit && $siswa->avatar)
                <div class="form-check mt-1">
                    <input type="checkbox" class="form-check-input" id="remove_avatar" name="remove_avatar" value="1">
                    <label class="form-check-label small text-danger" for="remove_avatar">Hapus foto saat ini</label>
                </div>
            @endif
            @error('avatar')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<!-- BAGIAN 3: ALAMAT, KONTAK & TRANSPORTASI -->
<h6 class="font-weight-bold text-uppercase text-warning border-bottom pb-2 mt-3 mb-3">
    <i class="pe-7s-map-marker mr-1"></i> Alamat, Kontak & Transportasi
</h6>
<div class="row">
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="alamat" class="font-weight-bold">Alamat Lengkap</label>
            <textarea name="alamat" id="alamat" rows="2"
                class="form-control @error('alamat') is-invalid @enderror">{{ $val('alamat') }}</textarea>
            @error('alamat')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-2">
        <div class="position-relative form-group">
            <label for="rt" class="font-weight-bold">RT</label>
            <input name="rt" id="rt" type="text" class="form-control @error('rt') is-invalid @enderror" value="{{ $val('rt') }}">
        </div>
    </div>
    <div class="col-md-2">
        <div class="position-relative form-group">
            <label for="rw" class="font-weight-bold">RW</label>
            <input name="rw" id="rw" type="text" class="form-control @error('rw') is-invalid @enderror" value="{{ $val('rw') }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="desa" class="font-weight-bold">Desa / Kelurahan</label>
            <input name="desa" id="desa" type="text" class="form-control @error('desa') is-invalid @enderror" value="{{ $val('desa') }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="kecamatan" class="font-weight-bold">Kecamatan</label>
            <input name="kecamatan" id="kecamatan" type="text" class="form-control @error('kecamatan') is-invalid @enderror" value="{{ $val('kecamatan') }}">
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="kabupaten" class="font-weight-bold">Kabupaten / Kota</label>
            <input name="kabupaten" id="kabupaten" type="text" class="form-control @error('kabupaten') is-invalid @enderror" value="{{ $val('kabupaten') }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="provinsi" class="font-weight-bold">Provinsi</label>
            <input name="provinsi" id="provinsi" type="text" class="form-control @error('provinsi') is-invalid @enderror" value="{{ $val('provinsi') }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="kode_pos" class="font-weight-bold">Kode Pos</label>
            <input name="kode_pos" id="kode_pos" type="text" class="form-control @error('kode_pos') is-invalid @enderror" value="{{ $val('kode_pos') }}">
        </div>
    </div>

    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="lintang" class="font-weight-bold">Lintang (Latitude)</label>
            <input name="lintang" id="lintang" type="text" class="form-control @error('lintang') is-invalid @enderror" value="{{ $val('lintang') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="bujur" class="font-weight-bold">Bujur (Longitude)</label>
            <input name="bujur" id="bujur" type="text" class="form-control @error('bujur') is-invalid @enderror" value="{{ $val('bujur') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="tempat_tinggal" class="font-weight-bold">Tempat Tinggal</label>
            <select name="tempat_tinggal" id="tempat_tinggal" class="form-control @error('tempat_tinggal') is-invalid @enderror">
                <option value="">-- Pilih Tempat Tinggal --</option>
                @foreach (\App\Models\Admin\DataMaster\Siswa::TEMPAT_TINGGAL_OPTIONS as $option)
                    <option value="{{ $option }}" @selected((string) $val('tempat_tinggal') === $option)>{{ $option }}</option>
                @endforeach
            </select>
        </div>
    </div>
    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="akademik_pelengkap_transportasi_id" class="font-weight-bold">Moda Transportasi</label>
            <select name="akademik_pelengkap_transportasi_id" id="akademik_pelengkap_transportasi_id"
                class="form-control @error('akademik_pelengkap_transportasi_id') is-invalid @enderror">
                <option value="">-- Pilih Transportasi --</option>
                @foreach ($transportasi as $itemTransportasi)
                    <option value="{{ $itemTransportasi->id }}" @selected((string) $val('akademik_pelengkap_transportasi_id') === (string) $itemTransportasi->id)>
                        {{ $itemTransportasi->nama_transportasi }}
                    </option>
                @endforeach
            </select>
            @error('akademik_pelengkap_transportasi_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="nomor_hp" class="font-weight-bold">Nomor HP</label>
            <input name="nomor_hp" id="nomor_hp" type="text" class="form-control @error('nomor_hp') is-invalid @enderror" value="{{ $val('nomor_hp') }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="whatsapp" class="font-weight-bold">Nomor WhatsApp</label>
            <input name="whatsapp" id="whatsapp" type="text" class="form-control @error('whatsapp') is-invalid @enderror" value="{{ $val('whatsapp') }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="email" class="font-weight-bold">Email</label>
            <input name="email" id="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ $val('email') }}">
            @error('email')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">{{ $message }}</div>
            @enderror
        </div>
    </div>
</div>

<!-- BAGIAN 4: DATA PERIODIK & KESEHATAN -->
<h6 class="font-weight-bold text-uppercase text-warning border-bottom pb-2 mt-3 mb-3">
    <i class="pe-7s-graph2 mr-1"></i> Data Periodik & Kesehatan
</h6>
<div class="row">
    <div class="col-md-2">
        <div class="position-relative form-group">
            <label for="tinggi_badan" class="font-weight-bold">Tinggi Badan (cm)</label>
            <input name="tinggi_badan" id="tinggi_badan" type="number" min="0"
                class="form-control @error('tinggi_badan') is-invalid @enderror" value="{{ $val('tinggi_badan') }}">
        </div>
    </div>
    <div class="col-md-2">
        <div class="position-relative form-group">
            <label for="berat_badan" class="font-weight-bold">Berat Badan (kg)</label>
            <input name="berat_badan" id="berat_badan" type="number" min="0"
                class="form-control @error('berat_badan') is-invalid @enderror" value="{{ $val('berat_badan') }}">
        </div>
    </div>
    <div class="col-md-2">
        <div class="position-relative form-group">
            <label for="lingkar_kepala" class="font-weight-bold">Lingkar Kepala (cm)</label>
            <input name="lingkar_kepala" id="lingkar_kepala" type="number" min="0"
                class="form-control @error('lingkar_kepala') is-invalid @enderror" value="{{ $val('lingkar_kepala') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="jarak" class="font-weight-bold">Jarak ke Sekolah (km)</label>
            <input name="jarak" id="jarak" type="number" min="0" step="0.01"
                class="form-control @error('jarak') is-invalid @enderror" value="{{ $val('jarak') }}">
        </div>
    </div>
    <div class="col-md-3">
        <div class="position-relative form-group">
            <label for="waktu_tempuh" class="font-weight-bold">Waktu Tempuh (menit)</label>
            <input name="waktu_tempuh" id="waktu_tempuh" type="number" min="0"
                class="form-control @error('waktu_tempuh') is-invalid @enderror" value="{{ $val('waktu_tempuh') }}">
        </div>
    </div>
</div>

<!-- BAGIAN 5: DATA ORANG TUA & WALI -->
@foreach (['ayah' => 'Data Ayah', 'ibu' => 'Data Ibu', 'wali' => 'Data Wali'] as $peran => $judul)
    <h6 class="font-weight-bold text-uppercase text-warning border-bottom pb-2 mt-3 mb-3">
        <i class="pe-7s-users mr-1"></i> {{ $judul }}
    </h6>
    <div class="row">
        <div class="col-md-4">
            <div class="position-relative form-group">
                <label for="nik_{{ $peran }}" class="font-weight-bold">NIK {{ ucfirst($peran) }}</label>
                <input name="nik_{{ $peran }}" id="nik_{{ $peran }}" type="text"
                    class="form-control @error("nik_{$peran}") is-invalid @enderror"
                    value="{{ $val("nik_{$peran}") }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="position-relative form-group">
                <label for="nama_{{ $peran }}" class="font-weight-bold">Nama {{ ucfirst($peran) }}</label>
                <input name="nama_{{ $peran }}" id="nama_{{ $peran }}" type="text"
                    class="form-control @error("nama_{$peran}") is-invalid @enderror"
                    value="{{ $val("nama_{$peran}") }}">
            </div>
        </div>
        <div class="col-md-4">
            <div class="position-relative form-group">
                <label for="lahir_{{ $peran }}" class="font-weight-bold">Tahun / Tanggal Lahir {{ ucfirst($peran) }}</label>
                <input name="lahir_{{ $peran }}" id="lahir_{{ $peran }}" type="text" placeholder="Contoh: 1982"
                    class="form-control @error("lahir_{$peran}") is-invalid @enderror"
                    value="{{ $val("lahir_{$peran}") }}">
            </div>
        </div>
        <div class="col-md-3">
            <div class="position-relative form-group">
                <label for="akademik_pelengkap_jenjang_pendidikan_{{ $peran }}_id" class="font-weight-bold">Pendidikan</label>
                <select name="akademik_pelengkap_jenjang_pendidikan_{{ $peran }}_id"
                    id="akademik_pelengkap_jenjang_pendidikan_{{ $peran }}_id"
                    class="form-control @error("akademik_pelengkap_jenjang_pendidikan_{$peran}_id") is-invalid @enderror">
                    <option value="">-- Pilih Pendidikan --</option>
                    @foreach ($jenjangPendidikan as $itemPendidikan)
                        <option value="{{ $itemPendidikan->id }}" @selected((string) $val("akademik_pelengkap_jenjang_pendidikan_{$peran}_id") === (string) $itemPendidikan->id)>
                            {{ $itemPendidikan->nama_jenjang_pendidikan }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="position-relative form-group">
                <label for="akademik_pelengkap_pekerjaan_{{ $peran }}_id" class="font-weight-bold">Pekerjaan</label>
                <select name="akademik_pelengkap_pekerjaan_{{ $peran }}_id"
                    id="akademik_pelengkap_pekerjaan_{{ $peran }}_id"
                    class="form-control @error("akademik_pelengkap_pekerjaan_{$peran}_id") is-invalid @enderror">
                    <option value="">-- Pilih Pekerjaan --</option>
                    @foreach ($pekerjaan as $itemPekerjaan)
                        <option value="{{ $itemPekerjaan->id }}" @selected((string) $val("akademik_pelengkap_pekerjaan_{$peran}_id") === (string) $itemPekerjaan->id)>
                            {{ $itemPekerjaan->nama_pekerjaan }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="position-relative form-group">
                <label for="akademik_pelengkap_penghasilan_{{ $peran }}_id" class="font-weight-bold">Penghasilan</label>
                <select name="akademik_pelengkap_penghasilan_{{ $peran }}_id"
                    id="akademik_pelengkap_penghasilan_{{ $peran }}_id"
                    class="form-control @error("akademik_pelengkap_penghasilan_{$peran}_id") is-invalid @enderror">
                    <option value="">-- Pilih Penghasilan --</option>
                    @foreach ($penghasilan as $itemPenghasilan)
                        <option value="{{ $itemPenghasilan->id }}" @selected((string) $val("akademik_pelengkap_penghasilan_{$peran}_id") === (string) $itemPenghasilan->id)>
                            {{ $itemPenghasilan->nama_penghasilan }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="col-md-3">
            <div class="position-relative form-group">
                <label for="akademik_pelengkap_berkebutuhan_khusus_{{ $peran }}_id" class="font-weight-bold">Kebutuhan Khusus</label>
                <select name="akademik_pelengkap_berkebutuhan_khusus_{{ $peran }}_id"
                    id="akademik_pelengkap_berkebutuhan_khusus_{{ $peran }}_id"
                    class="form-control @error("akademik_pelengkap_berkebutuhan_khusus_{$peran}_id") is-invalid @enderror">
                    <option value="">-- Pilih Kebutuhan Khusus --</option>
                    @foreach ($berkebutuhanKhusus as $itemKhusus)
                        <option value="{{ $itemKhusus->id }}" @selected((string) $val("akademik_pelengkap_berkebutuhan_khusus_{$peran}_id") === (string) $itemKhusus->id)>
                            {{ $itemKhusus->nama_berkebutuhan_khusus }}
                        </option>
                    @endforeach
                </select>
            </div>
        </div>
    </div>
@endforeach

<div class="form-group mt-3">
    <button type="submit" class="btn btn-xl text-white shadow-sm font-weight-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
        <i class="fa fa-save mr-1"></i> Simpan
    </button>
</div>
