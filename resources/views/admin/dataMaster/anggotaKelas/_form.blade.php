@pushOnce('css')
    <style>
        .ak-alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            margin-bottom: 1.25rem;
        }

        .ak-picker-shell {
            border: 1px solid #e2e8f0;
            border-radius: 0.875rem;
            background-color: #f8fafc;
            overflow: hidden;
        }

        .ak-picker-shell.is-invalid-shell {
            border-color: #ef4444;
        }

        .ak-picker-toolbar {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.875rem 1rem;
            background-color: #ffffff;
            border-bottom: 1px solid #e2e8f0;
        }

        .ak-search-group {
            position: relative;
            flex: 1 1 260px;
            min-width: 220px;
        }

        .ak-search-group i {
            position: absolute;
            left: 0.85rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.85rem;
            pointer-events: none;
        }

        .ak-search-input {
            width: 100%;
            padding: 0.5rem 0.85rem 0.5rem 2.25rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.625rem;
            font-size: 0.84rem;
            color: #0f172a;
            background-color: #f8fafc;
            transition: border-color 0.15s ease, box-shadow 0.15s ease, background-color 0.15s ease;
        }

        .ak-search-input:focus {
            outline: none;
            background-color: #ffffff;
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.16);
        }

        .ak-filter-select {
            flex: 0 1 220px;
            padding: 0.5rem 0.75rem;
            border: 1px solid #cbd5e1;
            border-radius: 0.625rem;
            font-size: 0.82rem;
            color: #334155;
            background-color: #ffffff;
        }

        .ak-filter-select:focus {
            outline: none;
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.16);
        }

        .ak-toolbar-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
        }

        .ak-count-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.35rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.78rem;
            font-weight: 700;
            background-color: #ffedd5;
            color: #c2410c;
        }

        .ak-btn-soft {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.42rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.78rem;
            font-weight: 600;
            border: 1px solid #cbd5e1;
            background-color: #ffffff;
            color: #334155;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .ak-btn-soft:hover {
            background-color: #f1f5f9;
            border-color: #94a3b8;
        }

        .ak-btn-reset {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.42rem 0.75rem;
            border-radius: 0.5rem;
            font-size: 0.78rem;
            font-weight: 600;
            border: 1px solid #fecaca;
            background-color: #fef2f2;
            color: #b91c1c;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .ak-btn-reset:hover {
            background-color: #fee2e2;
        }

        .ak-student-list {
            max-height: 410px;
            overflow-y: auto;
            padding: 0.75rem;
            display: flex;
            flex-direction: column;
            gap: 0.55rem;
        }

        label.ak-student-card {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.875rem;
            width: 100%;
            margin-bottom: 0;
            font-weight: 400;
            padding: 0.75rem 0.9rem;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            cursor: pointer;
            transition: border-color 0.15s ease, background-color 0.15s ease, box-shadow 0.15s ease;
        }

        label.ak-student-card.is-hidden {
            display: none !important;
        }

        .form-control.is-invalid {
            border-color: #ef4444;
        }

        .invalid-feedback {
            display: block;
            margin-top: 0.35rem;
            color: #dc2626;
        }

        .form-text {
            display: block;
            margin-top: 0.3rem;
        }

        .ak-student-card:hover {
            border-color: #fdba74;
            background-color: #fffbf7;
            box-shadow: 0 2px 6px rgba(234, 88, 12, 0.08);
        }

        .ak-student-card.is-selected {
            border-color: #ea580c;
            background-color: #fff7ed;
            box-shadow: 0 2px 8px rgba(234, 88, 12, 0.14);
        }

        .ak-student-left {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            min-width: 0;
            flex: 1 1 auto;
        }

        .ak-student-checkbox {
            width: 1.125rem;
            height: 1.125rem;
            accent-color: #ea580c;
            cursor: pointer;
            flex-shrink: 0;
        }

        .ak-student-avatar {
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .ak-avatar-male {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .ak-avatar-female {
            background-color: #fce7f3;
            color: #be185d;
        }

        .ak-avatar-default {
            background-color: #f1f5f9;
            color: #475569;
        }

        .ak-student-identity {
            min-width: 0;
            flex: 1 1 auto;
        }

        .ak-student-name-row {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
            margin-bottom: 0.2rem;
        }

        .ak-student-name {
            font-size: 0.92rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
        }

        .ak-student-meta {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.78rem;
            color: #64748b;
            font-weight: 500;
        }

        .ak-nis-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.15rem 0.5rem;
            border-radius: 0.375rem;
            background-color: #f1f5f9;
            color: #334155;
            font-weight: 600;
        }

        .ak-student-history {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.25rem;
            flex-shrink: 0;
            text-align: right;
        }

        .ak-history-label {
            font-size: 0.68rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
        }

        .ak-history-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.28rem 0.65rem;
            border-radius: 0.5rem;
            font-size: 0.78rem;
            font-weight: 700;
            background-color: #eef2ff;
            color: #3730a3;
            border: 1px solid #c7d2fe;
        }

        .ak-history-year {
            font-size: 0.73rem;
            color: #64748b;
            font-weight: 500;
        }

        .ak-history-empty {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.28rem 0.65rem;
            border-radius: 0.5rem;
            font-size: 0.75rem;
            font-weight: 500;
            color: #64748b;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
        }

        .ak-conflict-pill {
            display: none;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.55rem;
            border-radius: 9999px;
            font-size: 0.72rem;
            font-weight: 700;
            background-color: #fef3c7;
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .ak-conflict-pill.is-visible {
            display: inline-flex;
        }

        .ak-picker-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            padding: 0.65rem 1rem;
            background-color: #ffffff;
            border-top: 1px solid #e2e8f0;
            font-size: 0.76rem;
            color: #64748b;
        }

        .ak-empty-state {
            display: none;
            text-align: center;
            padding: 2rem 1rem;
            color: #64748b;
            font-size: 0.85rem;
        }

        @media (max-width: 767.98px) {
            .ak-student-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .ak-student-history {
                align-items: flex-start;
                text-align: left;
                padding-left: 1.9rem;
            }
        }
    </style>
@endPushOnce

@php
    $isEdit = isset($anggotaKelas);
    $excludeId = $isEdit ? (int) $anggotaKelas->id : null;
    $hiddenCount = (int) ($hiddenRegisteredCount ?? 0);
    $inactiveCount = (int) ($hiddenInactiveCount ?? 0);
    $filteredTahunAjaranIds = $excludedTahunAjaranIds ?? [];
    $val = fn (string $field, mixed $default = null) => session()->hasOldInput($field)
        ? old($field)
        : ($isEdit ? $anggotaKelas->$field : $default);
@endphp

@if ($errors->any())
    <div class="ak-alert-danger shadow-sm">
        <div class="font-weight-bold mb-1" style="font-size: 0.875rem;">
            <i class="fa fa-exclamation-triangle mr-1"></i> Periksa kembali isian berikut:
        </div>
        <ul class="mb-0" style="padding-left: 1.25rem; font-size: 0.82rem;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

<div class="row">
    <!-- Kelas (terikat Tahun Ajaran & Semester) -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="akademik_master_kelas_id" class="font-weight-bold">
                Kelas &amp; Tahun Ajaran Tujuan <span class="text-danger">*</span>
            </label>
            <select name="akademik_master_kelas_id" id="akademik_master_kelas_id"
                class="form-control @error('akademik_master_kelas_id') is-invalid @enderror" required>
                <option value="" data-tahun-ajaran-id="">-- Pilih Kelas --</option>
                @foreach ($kelas as $k)
                    <option value="{{ $k->id }}"
                        data-tahun-ajaran-id="{{ $k->akademik_master_tahun_ajaran_id }}"
                        @selected((string) $val('akademik_master_kelas_id', $preselectedKelasId ?? null) === (string) $k->id)>
                        {{ $k->nama_kelas }} ({{ strtoupper($k->jenjang_kelas) }} - Tingkat {{ $k->tingkatan_kelas }})
                        @if ($k->tahunAjaran)
                            — {{ $k->tahunAjaran->nama_tahun_ajaran }} Semester {{ strtoupper($k->tahunAjaran->semester) }} {{ $k->tahunAjaran->is_active ? '[Aktif]' : '' }}
                        @endif
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Tahun ajaran dan semester otomatis mengikuti kelas yang dipilih.
            </small>
            @error('akademik_master_kelas_id')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>

    <!-- Pendaftaran -->
    <div class="col-md-6">
        <div class="position-relative form-group">
            <label for="pendaftaran" class="font-weight-bold">
                Jenis Pendaftaran <span class="text-danger">*</span>
            </label>
            <select name="pendaftaran" id="pendaftaran"
                class="form-control @error('pendaftaran') is-invalid @enderror" required>
                <option value="">-- Pilih Jenis Pendaftaran --</option>
                @foreach (\App\Models\Admin\DataMaster\AnggotaKelas::PENDAFTARAN_OPTIONS as $opsi)
                    <option value="{{ $opsi }}" @selected((string) $val('pendaftaran', $preselectedPendaftaran ?? null) === (string) $opsi)>
                        {{ $opsi }}
                    </option>
                @endforeach
            </select>
            <small class="form-text text-muted" style="font-size: 0.75rem;">
                Status masuk siswa ke dalam rombongan belajar tersebut.
            </small>
            @error('pendaftaran')
                <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                    {{ $message }}
                </div>
            @enderror
        </div>
    </div>
</div>

<!-- Siswa -->
<div class="position-relative form-group mt-2">
    <label for="siswa_nis" class="font-weight-bold">
        Pilih Siswa <span class="text-danger">*</span>
    </label>

    @if ($isEdit)
        <select name="siswa_nis" id="siswa_nis"
            class="form-control @error('siswa_nis') is-invalid @enderror" required>
            <option value="">-- Pilih Siswa --</option>
            @foreach ($siswa as $s)
                @php
                    $prevKelas = $s->resolvePreviousKelasSummary($excludeId);
                    $prevLabel = $prevKelas
                        ? ' | Kelas Sebelumnya: ' . $prevKelas['nama_kelas'] . ($prevKelas['tahun_ajaran'] ? ' (' . $prevKelas['tahun_ajaran'] . ' Smt ' . strtoupper((string) $prevKelas['semester']) . ')' : '')
                        : ' | Belum ada kelas sebelumnya';
                @endphp
                <option value="{{ $s->nis }}" @selected((string) $val('siswa_nis') === (string) $s->nis)>
                    {{ $s->nama_lengkap ?: 'Tanpa Nama' }} — NIS: {{ $s->nis }}{{ $s->nisn ? ' | NISN: ' . $s->nisn : '' }}{{ $prevLabel }} [{{ $s->status }}]
                </option>
            @endforeach
        </select>
        @error('siswa_nis')
            <div class="invalid-feedback" style="font-style: italic; font-size: 0.75rem;">
                {{ $message }}
            </div>
        @enderror
    @else
        @php
            $oldNis = old('siswa_nis');
            $selectedNisList = is_array($oldNis)
                ? array_map('strval', $oldNis)
                : ($oldNis !== null && $oldNis !== '' ? [(string) $oldNis] : []);
        @endphp

        <div class="ak-picker-shell shadow-sm {{ $errors->has('siswa_nis') || $errors->has('siswa_nis.*') ? 'is-invalid-shell' : '' }}">
            <div class="ak-picker-toolbar">
                <div class="ak-search-group">
                    <i class="fa fa-search"></i>
                    <input type="text" id="filterSiswaInput" class="ak-search-input"
                        placeholder="Cari nama lengkap, NIS, NISN, atau kelas sebelumnya...">
                </div>

                <select id="filterKelasSebelumnya" class="ak-filter-select" aria-label="Filter riwayat kelas siswa">
                    <option value="all">Semua Siswa Aktif ({{ $siswa->count() }})</option>
                    <option value="without_prev">Belum Ada Kelas Sebelumnya</option>
                    <option value="with_prev">Sudah Punya Kelas Sebelumnya</option>
                </select>

                <div class="ak-toolbar-actions">
                    <span class="ak-count-pill" id="selectedSiswaCount">
                        <i class="fa fa-check-circle"></i>
                        <span>{{ count($selectedNisList) }} dipilih</span>
                    </span>
                    <button type="button" class="ak-btn-soft" id="selectVisibleSiswaBtn">
                        <i class="fa fa-check-double"></i> Pilih Terlihat
                    </button>
                    <button type="button" class="ak-btn-reset" id="clearSiswaBtn">
                        <i class="fa fa-times"></i> Reset
                    </button>
                </div>
            </div>

            <div class="ak-student-list" id="siswaCheckboxContainer"
                data-filtered-years="{{ implode(',', $filteredTahunAjaranIds) }}"
                data-create-url="{{ route('anggota-kelas.create') }}">
                @forelse ($siswa as $s)
                    @php
                        $prevKelas = $s->resolvePreviousKelasSummary();
                        $registeredTahunAjaran = $s->registeredTahunAjaranIds();
                        $avatarClass = match (strtoupper((string) $s->jenis_kelamin)) {
                            'L' => 'ak-avatar-male',
                            'P' => 'ak-avatar-female',
                            default => 'ak-avatar-default',
                        };
                        $isChecked = in_array((string) $s->nis, $selectedNisList, true);
                        $searchHaystack = strtolower(trim(implode(' ', array_filter([
                            $s->nama_lengkap,
                            $s->nis,
                            $s->nisn,
                            $s->status,
                            $prevKelas['nama_kelas'] ?? null,
                            $prevKelas['jenjang_kelas'] ?? null,
                            $prevKelas['tahun_ajaran'] ?? null,
                        ]))));
                    @endphp

                    <label class="ak-student-card siswa-option-item {{ $isChecked ? 'is-selected' : '' }}"
                        for="siswa_nis_{{ $loop->index }}"
                        data-search="{{ $searchHaystack }}"
                        data-has-prev="{{ $prevKelas !== null ? '1' : '0' }}"
                        data-registered-years="{{ implode(',', $registeredTahunAjaran) }}">
                        <div class="ak-student-left">
                            <input type="checkbox" class="ak-student-checkbox siswa-checkbox"
                                id="siswa_nis_{{ $loop->index }}" name="siswa_nis[]" value="{{ $s->nis }}"
                                @checked($isChecked)>

                            <span class="ak-student-avatar {{ $avatarClass }}" aria-hidden="true">
                                {{ $s->inisial_nama }}
                            </span>

                            <div class="ak-student-identity">
                                <div class="ak-student-name-row">
                                    <span class="ak-student-name">{{ $s->nama_lengkap ?: 'Nama Belum Diisi' }}</span>
                                    @if ($s->jenis_kelamin)
                                        <span class="badge badge-info">{{ $s->jenis_kelamin_label }}</span>
                                    @endif
                                    @if ($s->status)
                                        <span class="badge {{ $s->status_badge_class }}">{{ $s->status }}</span>
                                    @endif
                                </div>
                                <div class="ak-student-meta">
                                    <span class="ak-nis-chip">
                                        <i class="fa fa-id-card text-primary" aria-hidden="true"></i>
                                        NIS: {{ $s->nis }}
                                    </span>
                                    @if ($s->nisn)
                                        <span>NISN: {{ $s->nisn }}</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="ak-student-history">
                            @if ($prevKelas !== null)
                                <span class="ak-history-label">Kelas Sebelumnya</span>
                                <span class="ak-history-badge">
                                    <i class="fa fa-chalkboard-teacher" aria-hidden="true"></i>
                                    {{ $prevKelas['nama_kelas'] }}
                                    @if ($prevKelas['jenjang_kelas'])
                                        ({{ strtoupper($prevKelas['jenjang_kelas']) }})
                                    @endif
                                </span>
                                @if ($prevKelas['tahun_ajaran'])
                                    <span class="ak-history-year">
                                        {{ $prevKelas['tahun_ajaran'] }} • Smt {{ strtoupper((string) $prevKelas['semester']) }}
                                    </span>
                                @endif
                            @else
                                <span class="ak-history-empty">
                                    <i class="fa fa-history" aria-hidden="true"></i>
                                    Belum ada kelas sebelumnya
                                </span>
                            @endif

                            <span class="ak-conflict-pill">
                                <i class="fa fa-exclamation-circle" aria-hidden="true"></i>
                                Sudah terdaftar di TA tujuan
                            </span>
                        </div>
                    </label>
                @empty
                    <div class="text-muted small p-4 text-center">
                        @if ($hiddenCount > 0)
                            <i class="fa fa-check-circle text-success mb-2" style="font-size: 1.5rem;"></i>
                            <div class="font-weight-bold text-slate-700">
                                Seluruh siswa aktif ({{ $hiddenCount }}) sudah masuk daftar Anggota Kelas pada tahun ajaran/semester ini.
                            </div>
                        @elseif ($inactiveCount > 0)
                            <i class="fa fa-info-circle text-warning mb-2" style="font-size: 1.5rem;"></i>
                            <div class="font-weight-bold text-slate-700">
                                Tidak ada siswa berstatus Aktif yang tersedia ({{ $inactiveCount }} siswa berstatus Nonaktif/Pindah/Lulus/Keluar disembunyikan).
                            </div>
                        @else
                            Belum ada data siswa tersedia. Silakan tambahkan atau seed data siswa terlebih dahulu.
                        @endif
                    </div>
                @endforelse

                @if ($siswa->isNotEmpty())
                    <div class="ak-empty-state" id="emptySearchSiswa">
                        <i class="fa fa-search mb-2" style="font-size: 1.25rem; color: #94a3b8;"></i>
                        <div>Tidak ada siswa yang sesuai dengan kata kunci atau filter yang dipilih.</div>
                    </div>
                @endif
            </div>

            <div class="ak-picker-footer">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span>
                        <i class="fa fa-info-circle text-primary mr-1"></i>
                        Menampilkan hanya siswa berstatus <strong>Aktif</strong> yang belum terdaftar pada tahun ajaran tujuan.
                    </span>
                    @if ($hiddenCount > 0)
                        <span class="badge badge-info">
                            <i class="fa fa-user-check"></i>
                            {{ $hiddenCount }} sudah masuk anggota kelas (disembunyikan)
                        </span>
                    @endif
                    @if ($inactiveCount > 0)
                        <span class="badge badge-secondary">
                            <i class="fa fa-user-slash"></i>
                            {{ $inactiveCount }} non-aktif/pindah/lulus (disembunyikan)
                        </span>
                    @endif
                </div>
                <span id="visibleSiswaCounter" class="font-weight-bold text-slate-700">
                    Menampilkan {{ $siswa->count() }} dari {{ $siswa->count() }} siswa aktif
                </span>
            </div>
        </div>

        @if ($errors->has('siswa_nis'))
            <div class="text-danger mt-1" style="font-style: italic; font-size: 0.75rem;">
                {{ $errors->first('siswa_nis') }}
            </div>
        @endif
        @if ($errors->has('siswa_nis.*'))
            <div class="text-danger mt-1" style="font-style: italic; font-size: 0.75rem;">
                {{ $errors->first('siswa_nis.*') }}
            </div>
        @endif

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const container = document.getElementById('siswaCheckboxContainer');
                const kelasSelect = document.getElementById('akademik_master_kelas_id');
                const pendaftaranSelect = document.getElementById('pendaftaran');
                const searchInput = document.getElementById('filterSiswaInput');
                const historyFilter = document.getElementById('filterKelasSebelumnya');
                const items = Array.from(document.querySelectorAll('.siswa-option-item'));
                const checkboxes = Array.from(document.querySelectorAll('.siswa-checkbox'));
                const countBadge = document.getElementById('selectedSiswaCount');
                const visibleCounter = document.getElementById('visibleSiswaCounter');
                const emptyState = document.getElementById('emptySearchSiswa');
                const selectVisibleBtn = document.getElementById('selectVisibleSiswaBtn');
                const clearBtn = document.getElementById('clearSiswaBtn');

                const filteredYears = (container?.dataset.filteredYears || '').split(',').filter(Boolean);
                const createUrl = container?.dataset.createUrl || '';

                const getTargetTahunAjaranId = () => {
                    if (!kelasSelect || kelasSelect.selectedIndex < 0) {
                        return '';
                    }

                    return kelasSelect.options[kelasSelect.selectedIndex]?.dataset.tahunAjaranId || '';
                };

                const updateConflictIndicators = () => {
                    const targetTahunAjaranId = getTargetTahunAjaranId();

                    items.forEach((item) => {
                        const registeredYears = (item.dataset.registeredYears || '').split(',').filter(Boolean);
                        const hasConflict = targetTahunAjaranId !== '' && registeredYears.includes(targetTahunAjaranId);
                        const cb = item.querySelector('.siswa-checkbox');

                        item.dataset.hasTargetConflict = hasConflict ? '1' : '0';

                        if (cb) {
                            cb.disabled = hasConflict;
                            if (hasConflict) {
                                cb.checked = false;
                            }
                        }
                    });
                };

                const applyFilters = () => {
                    const keyword = (searchInput?.value || '').trim().toLowerCase();
                    const filterMode = historyFilter?.value || 'all';
                    let visibleCount = 0;

                    items.forEach((item) => {
                        if (item.dataset.hasTargetConflict === '1') {
                            item.classList.add('is-hidden');

                            return;
                        }

                        const hay = item.dataset.search || '';
                        const matchesKeyword = keyword === '' || hay.includes(keyword);
                        let matchesMode = true;

                        if (filterMode === 'without_prev') {
                            matchesMode = item.dataset.hasPrev === '0';
                        } else if (filterMode === 'with_prev') {
                            matchesMode = item.dataset.hasPrev === '1';
                        }

                        const show = matchesKeyword && matchesMode;
                        item.classList.toggle('is-hidden', !show);

                        if (show) {
                            visibleCount++;
                        }
                    });

                    if (visibleCounter) {
                        visibleCounter.textContent = `Menampilkan ${visibleCount} dari ${items.length} siswa aktif`;
                    }

                    if (emptyState) {
                        emptyState.style.display = visibleCount === 0 && items.length > 0 ? 'block' : 'none';
                    }
                };

                const updateSelectionUI = () => {
                    let selectedCount = 0;

                    checkboxes.forEach((cb) => {
                        const card = cb.closest('.ak-student-card');
                        if (cb.checked && !cb.disabled) {
                            selectedCount++;
                        }
                        if (card) {
                            card.classList.toggle('is-selected', cb.checked && !cb.disabled);
                        }
                    });

                    if (countBadge) {
                        const labelSpan = countBadge.querySelector('span');
                        if (labelSpan) {
                            labelSpan.textContent = `${selectedCount} dipilih`;
                        }
                    }
                };

                if (kelasSelect) {
                    kelasSelect.addEventListener('change', () => {
                        const selectedYear = getTargetTahunAjaranId();

                        if (createUrl && selectedYear !== '' && filteredYears.length > 0 && !filteredYears.includes(selectedYear)) {
                            const url = new URL(createUrl, window.location.origin);
                            url.searchParams.set('kelas_id', kelasSelect.value);
                            if (pendaftaranSelect?.value) {
                                url.searchParams.set('pendaftaran', pendaftaranSelect.value);
                            }
                            window.location.assign(url.toString());

                            return;
                        }

                        updateConflictIndicators();
                        applyFilters();
                        updateSelectionUI();
                    });
                }

                if (searchInput) {
                    searchInput.addEventListener('input', applyFilters);
                }

                if (historyFilter) {
                    historyFilter.addEventListener('change', applyFilters);
                }

                checkboxes.forEach((cb) => cb.addEventListener('change', updateSelectionUI));

                if (selectVisibleBtn) {
                    selectVisibleBtn.addEventListener('click', () => {
                        items.forEach((item) => {
                            if (!item.classList.contains('is-hidden')) {
                                const cb = item.querySelector('.siswa-checkbox');
                                if (cb && !cb.disabled) {
                                    cb.checked = true;
                                }
                            }
                        });
                        updateSelectionUI();
                    });
                }

                if (clearBtn) {
                    clearBtn.addEventListener('click', () => {
                        checkboxes.forEach((cb) => {
                            cb.checked = false;
                        });
                        updateSelectionUI();
                    });
                }

                updateConflictIndicators();
                applyFilters();
                updateSelectionUI();
            });
        </script>
    @endif
</div>

<div class="form-group mt-4">
    <button type="submit" class="btn btn-xl text-white shadow-sm font-weight-bold px-4" id="submitBtn"
        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
        <i class="fa fa-save mr-1"></i> Simpan
    </button>
</div>
