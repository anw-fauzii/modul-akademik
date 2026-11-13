@php
    $isEdit = isset($kelas);
    $val = fn($field) => old($field, $isEdit ? $kelas->$field : null);
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

    <!-- Jenjang Kelas -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="jenjang_kelas" class="font-weight-bold">
                Jenjang Kelas <span class="text-danger">*</span>
            </label>
            <select name="jenjang_kelas" id="jenjang_kelas"
                class="form-control @error('jenjang_kelas') is-invalid @enderror" required>
                <option value="">-- Pilih Jenjang --</option>
                @foreach (['PG', 'TK', 'SD'] as $jenjang)
                    <option value="{{ $jenjang }}" @selected($val('jenjang_kelas') === $jenjang)>
                        {{ $jenjang }}
                    </option>
                @endforeach
            </select>
            @error('jenjang_kelas')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Tingkatan Kelas -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="tingkatan_kelas" class="font-weight-bold">
                Tingkatan Kelas <span class="text-danger">*</span>
            </label>
            <input name="tingkatan_kelas" id="tingkatan_kelas" placeholder="Contoh: 1, 2, 7, atau TK-A" type="text"
                class="form-control @error('tingkatan_kelas') is-invalid @enderror"
                value="{{ $val('tingkatan_kelas') }}" required>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Tingkatan angka atau tingkat rombel (misal: 1, 2, 3, 7, A, B).
            </small>
            @error('tingkatan_kelas')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Format Romawi -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="romawi" class="font-weight-bold">
                Romawi <span class="text-danger">*</span>
            </label>
            <input name="romawi" id="romawi" placeholder="Contoh: I, II, VII, atau -" type="text"
                class="form-control @error('romawi') is-invalid @enderror"
                value="{{ $val('romawi') }}" required>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Format angka romawi tingkatan kelas (misal: I, II, III, VII).
            </small>
            @error('romawi')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Nama Kelas -->
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="nama_kelas" class="font-weight-bold">
                Nama Kelas <span class="text-danger">*</span>
            </label>
            <input name="nama_kelas" id="nama_kelas" placeholder="Contoh: 1 Al-Fatihah, 7 Abu Bakar, atau TK-A Bintang" type="text"
                class="form-control @error('nama_kelas') is-invalid @enderror"
                value="{{ $val('nama_kelas') }}" required>
            @error('nama_kelas')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Guru Wali Kelas / Guru 1 -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="karyawan_nipy" class="font-weight-bold">
                Wali Kelas / Guru Utama
            </label>
            <select name="karyawan_nipy" id="karyawan_nipy"
                class="form-control @error('karyawan_nipy') is-invalid @enderror">
                <option value="">-- Pilih Guru Wali Kelas --</option>
                @foreach ($karyawan as $guru)
                    <option value="{{ $guru->nipy }}" @selected($val('karyawan_nipy') == $guru->nipy)>
                        {{ $guru->nama_gelar }} ({{ $guru->nipy }})
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Guru pertama / penanggung jawab utama kelas.
            </small>
            @error('karyawan_nipy')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Guru Pendamping / Guru 2 -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="pendamping_nipy" class="font-weight-bold">
                Guru Pendamping
            </label>
            <select name="pendamping_nipy" id="pendamping_nipy"
                class="form-control @error('pendamping_nipy') is-invalid @enderror">
                <option value="">-- Pilih Guru Pendamping --</option>
                @foreach ($karyawan as $guru)
                    <option value="{{ $guru->nipy }}" @selected($val('pendamping_nipy') == $guru->nipy)>
                        {{ $guru->nama_gelar }} ({{ $guru->nipy }})
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Guru pendamping kelas (setiap kelas memiliki 2 guru).
            </small>
            @error('pendamping_nipy')
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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const tingkatanInput = document.getElementById('tingkatan_kelas');
        const romawiInput = document.getElementById('romawi');

        const romawiMap = {
            '1': 'I', '2': 'II', '3': 'III', '4': 'IV', '5': 'V',
            '6': 'VI', '7': 'VII', '8': 'VIII', '9': 'IX', '10': 'X',
            '11': 'XI', '12': 'XII'
        };

        if (tingkatanInput && romawiInput) {
            tingkatanInput.addEventListener('input', function () {
                const val = this.value.trim();
                if (romawiMap[val] && (!romawiInput.value || romawiInput.dataset.autoFilled === 'true')) {
                    romawiInput.value = romawiMap[val];
                    romawiInput.dataset.autoFilled = 'true';
                }
            });

            romawiInput.addEventListener('input', function () {
                romawiInput.dataset.autoFilled = 'false';
            });
        }
    });
</script>
