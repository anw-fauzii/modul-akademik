@php
    $isEdit = isset($jamPelajaran);
    $unitOptions = $unitOptions ?? \App\Models\Admin\Pelajaran\JamPelajaran::UNIT_OPTIONS;
    $nextJamKeByUnit = $nextJamKeByUnit ?? array_fill_keys($unitOptions, 1);
    $defaultNextJamKe = $defaultNextJamKe ?? 1;
    $val = fn(string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $jamPelajaran->$field : $default);
    $formatTimeInput = static function (mixed $timeValue): string {
        $formatted = \App\Models\Admin\Pelajaran\JamPelajaran::formatTimeShort($timeValue);

        return $formatted === '-' ? '' : $formatted;
    };
    $jamMulaiVal = $formatTimeInput($val('jam_mulai'));
    $jamSelesaiVal = $formatTimeInput($val('jam_selesai'));
@endphp

<div class="row">
    <!-- Unit -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="unit" class="font-weight-bold">
                Unit Pendidikan <span class="text-danger">*</span>
            </label>
            <select name="unit" id="unit" class="form-control @error('unit') is-invalid @enderror"
                data-next-jam-by-unit='@json($nextJamKeByUnit)' required>
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

    <!-- Jam Ke -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="jam_ke" class="font-weight-bold">
                Jam Ke <span class="text-danger">*</span>
            </label>
            <input name="jam_ke" id="jam_ke" placeholder="Contoh: 1"
                type="number" min="1" max="99" step="1"
                class="form-control @error('jam_ke') is-invalid @enderror"
                value="{{ $val('jam_ke', $defaultNextJamKe) }}" required>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Urutan jam ke bersifat unik untuk masing-masing unit pendidikan.
            </small>
            @error('jam_ke')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Jam Mulai -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="jam_mulai" class="font-weight-bold">
                Jam Mulai <span class="text-danger">*</span>
            </label>
            <x-time24-input
                name="jam_mulai"
                id="jam_mulai"
                placeholder="07:00"
                @class(['form-control', 'is-invalid' => $errors->has('jam_mulai')])
                :value="$jamMulaiVal"
                required
            />
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Gunakan format 24 jam (00:00 - 23:59), contoh: 07:00 atau 13:30.
            </small>
            @error('jam_mulai')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Jam Selesai -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="jam_selesai" class="font-weight-bold">
                Jam Selesai <span class="text-danger">*</span>
            </label>
            <x-time24-input
                name="jam_selesai"
                id="jam_selesai"
                placeholder="07:35"
                @class(['form-control', 'is-invalid' => $errors->has('jam_selesai')])
                :value="$jamSelesaiVal"
                required
            />
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Format 24 jam (00:00 - 23:59) dan harus lebih besar dari jam mulai.
            </small>
            @error('jam_selesai')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">
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
                placeholder="Contoh: Pembiasaan Pagi, Jam Inti, atau Istirahat (opsional)..."
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

@if (! $isEdit && ! session()->hasOldInput('jam_ke'))
    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const unitSelect = document.getElementById('unit');
                const jamKeInput = document.getElementById('jam_ke');
                if (!unitSelect || !jamKeInput) {
                    return;
                }

                let nextMap = {};
                try {
                    nextMap = JSON.parse(unitSelect.dataset.nextJamByUnit || '{}');
                } catch (e) {
                    nextMap = {};
                }

                let userEdited = false;
                jamKeInput.addEventListener('input', function () {
                    userEdited = true;
                });

                unitSelect.addEventListener('change', function () {
                    if (userEdited) {
                        return;
                    }
                    const nextVal = nextMap[unitSelect.value];
                    if (nextVal) {
                        jamKeInput.value = nextVal;
                    }
                });
            });
        </script>
    @endpush
@endif
