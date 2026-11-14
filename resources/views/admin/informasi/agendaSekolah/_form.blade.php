@php
    $isEdit = isset($agendaSekolah);
    $val = fn(string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $agendaSekolah->$field : $default);

    $availableUnits = $unitOptions ?? \App\Models\Admin\Informasi\AgendaSekolah::UNIT_OPTIONS;
    $activeTahunAjaranId = collect($tahunAjaran ?? [])->firstWhere('is_active', true)?->id;
    $selectedTahunAjaran = $val('akademik_master_tahun_ajaran_id', $activeTahunAjaranId);
    $selectedUnit = $val('unit');

    $rawTanggal = $val('tanggal');
    $formattedTanggal = $rawTanggal instanceof \Carbon\CarbonInterface
        ? $rawTanggal->format('Y-m-d')
        : (string) ($rawTanggal ?? '');

    $rawJam = trim((string) ($val('jam') ?? ''));
    $formattedJam = preg_match('/^(\d{2}:\d{2})/', $rawJam, $matches) === 1
        ? $matches[1]
        : $rawJam;
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
                    <option value="{{ $ta->id }}" @selected((string) $selectedTahunAjaran === (string) $ta->id)>
                        {{ $ta->nama_tahun_ajaran }} - Semester {{ strtoupper((string) $ta->semester) }} {{ $ta->is_active ? '(Aktif)' : '' }}
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

    <!-- Unit -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="unit" class="font-weight-bold">
                Unit <span class="text-danger">*</span>
            </label>
            <select name="unit" id="unit"
                class="form-control @error('unit') is-invalid @enderror" required>
                <option value="">-- Pilih Unit --</option>
                @foreach ($availableUnits as $unit)
                    <option value="{{ $unit }}" @selected((string) $selectedUnit === $unit)>
                        {{ $unit }}
                    </option>
                @endforeach
                @if ($selectedUnit && ! in_array($selectedUnit, $availableUnits, true))
                    <option value="{{ $selectedUnit }}" selected>{{ $selectedUnit }}</option>
                @endif
            </select>
            @error('unit')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Nama Agenda -->
    <div class="col-md-12">
        <div class="position-relative form-group">
            <label for="nama_agenda" class="font-weight-bold">
                Nama Agenda <span class="text-danger">*</span>
            </label>
            <input name="nama_agenda" id="nama_agenda"
                placeholder="Contoh: Upacara Peringatan Hari Pendidikan Nasional" type="text"
                class="form-control @error('nama_agenda') is-invalid @enderror"
                value="{{ $val('nama_agenda') }}" required>
            @error('nama_agenda')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="row">
    <!-- Tanggal -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="tanggal" class="font-weight-bold">
                Tanggal <span class="text-danger">*</span>
            </label>
            <input name="tanggal" id="tanggal" type="date"
                class="form-control @error('tanggal') is-invalid @enderror"
                value="{{ $formattedTanggal }}" required>
            @error('tanggal')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Jam -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="jam" class="font-weight-bold">
                Jam <span class="text-danger">*</span>
            </label>
            <x-time24-input
                name="jam"
                id="jam"
                placeholder="07:30"
                @class(['form-control', 'is-invalid' => $errors->has('jam')])
                :value="$formattedJam"
                required
            />
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Gunakan format 24 jam (contoh: 07:30 atau 13:00 WIB).
            </small>
            @error('jam')
                <div class="invalid-feedback d-block" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<div class="form-group mt-2">
    <button type="submit" class="btn btn-xl text-white shadow-sm fw-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
        <i class="fa fa-save mr-1"></i> Simpan
    </button>
</div>

