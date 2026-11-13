@php
    $isEdit = isset($mataPelajaran);
    $kelompokPelajaran = $kelompokPelajaran ?? collect();
    $unitOptions = $unitOptions ?? \App\Models\Admin\Pelajaran\MataPelajaran::UNIT_OPTIONS;
    $sifatOptions = $sifatOptions ?? \App\Models\Admin\Pelajaran\MataPelajaran::SIFAT_OPTIONS;
    $nextUrutan = $nextUrutan ?? 1;
    $val = fn(string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $mataPelajaran->$field : $default);
    $currentStatus = $val('status', true);
    $isStatusActive = filter_var($currentStatus, FILTER_VALIDATE_BOOLEAN);
@endphp

<div class="row">
    <!-- Unit -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="unit" class="font-weight-bold">
                Unit Pendidikan <span class="text-danger">*</span>
            </label>
            <select name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror" required>
                <option value="">-- Pilih Unit --</option>
                @foreach ($unitOptions as $unitOption)
                    <option value="{{ $unitOption }}" @selected((string) $val('unit') === $unitOption)>
                        {{ $unitOption }}
                    </option>
                @endforeach
            </select>
            @error('unit')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Kelompok Pelajaran -->
    <div class="col-md-8">
        <div class="position-relative form-group">
            <label for="akademik_pelajaran_kelompok_pelajaran_id" class="font-weight-bold">
                Kelompok Pelajaran <span class="text-danger">*</span>
            </label>
            <select name="akademik_pelajaran_kelompok_pelajaran_id" id="akademik_pelajaran_kelompok_pelajaran_id"
                class="form-control @error('akademik_pelajaran_kelompok_pelajaran_id') is-invalid @enderror" required>
                <option value="">-- Pilih Kelompok Pelajaran --</option>
                @foreach ($kelompokPelajaran as $kelompok)
                    <option value="{{ $kelompok->id }}"
                        @selected((string) $val('akademik_pelajaran_kelompok_pelajaran_id') === (string) $kelompok->id)>
                        [{{ $kelompok->kode }}] {{ $kelompok->kelompok }}
                    </option>
                @endforeach
            </select>
            @if ($kelompokPelajaran->isEmpty())
                <small class="form-text text-warning" style="font-size: 0.75rem;">
                    Belum ada kelompok pelajaran. Silakan tambahkan terlebih dahulu di menu
                    <a href="{{ route('kelompok-pelajaran.create') }}">Kelompok Pelajaran</a>.
                </small>
            @endif
            @error('akademik_pelajaran_kelompok_pelajaran_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Singkatan -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="singkatan" class="font-weight-bold">
                Singkatan <span class="text-danger">*</span>
            </label>
            <input name="singkatan" id="singkatan" placeholder="Contoh: MTK, BIND, IPA"
                type="text" maxlength="30"
                class="form-control text-uppercase @error('singkatan') is-invalid @enderror"
                value="{{ $val('singkatan') }}" required>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Singkatan unik per unit (otomatis disimpan dalam huruf kapital).
            </small>
            @error('singkatan')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Nama Mata Pelajaran -->
    <div class="col-md-8">
        <div class="position-relative form-group">
            <label for="nama_mata_pelajaran" class="font-weight-bold">
                Nama Mata Pelajaran <span class="text-danger">*</span>
            </label>
            <input name="nama_mata_pelajaran" id="nama_mata_pelajaran"
                placeholder="Contoh: Matematika, Bahasa Indonesia, Pendidikan Agama Islam"
                type="text" maxlength="255"
                class="form-control @error('nama_mata_pelajaran') is-invalid @enderror"
                value="{{ $val('nama_mata_pelajaran') }}" required>
            @error('nama_mata_pelajaran')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Sifat -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="sifat" class="font-weight-bold">
                Sifat Mata Pelajaran <span class="text-danger">*</span>
            </label>
            <select name="sifat" id="sifat" class="form-control @error('sifat') is-invalid @enderror" required>
                @foreach ($sifatOptions as $sifatOption)
                    <option value="{{ $sifatOption }}"
                        @selected((string) $val('sifat', \App\Models\Admin\Pelajaran\MataPelajaran::SIFAT_WAJIB) === $sifatOption)>
                        {{ ucfirst($sifatOption) }}
                    </option>
                @endforeach
            </select>
            @error('sifat')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Urutan -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="urutan" class="font-weight-bold">
                Urutan Tampil <span class="text-danger">*</span>
            </label>
            <input name="urutan" id="urutan" placeholder="Contoh: 1"
                type="number" min="1" max="9999" step="1"
                class="form-control @error('urutan') is-invalid @enderror"
                value="{{ $val('urutan', $nextUrutan) }}" required>
            @error('urutan')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Status -->
    <div class="col-md-4">
        <div class="position-relative form-group">
            <label for="status" class="font-weight-bold">
                Status <span class="text-danger">*</span>
            </label>
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                <option value="1" @selected($isStatusActive)>Aktif (Ditampilkan)</option>
                <option value="0" @selected(! $isStatusActive)>Nonaktif</option>
            </select>
            @error('status')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Keterangan -->
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="keterangan" class="font-weight-bold">
                Keterangan
            </label>
            <textarea name="keterangan" id="keterangan" rows="3" maxlength="1000"
                placeholder="Catatan tambahan mengenai mata pelajaran (opsional)..."
                class="form-control @error('keterangan') is-invalid @enderror">{{ $val('keterangan') }}</textarea>
            @error('keterangan')
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

