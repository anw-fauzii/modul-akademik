@pushOnce('css')
    <style>
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
    $pickerMode = in_array($pickerMode ?? 'kelas', ['kelas', 'ekstrakurikuler', 'jemputan', 't2q'], true)
        ? $pickerMode
        : 'kelas';
    $isEkstrakurikulerMode = $pickerMode === 'ekstrakurikuler';
    $isJemputanMode = $pickerMode === 'jemputan';
    $isT2qMode = $pickerMode === 't2q';
    $usesAnggotaKelas = $isEkstrakurikulerMode || $isJemputanMode || $isT2qMode;
    $items = $usesAnggotaKelas ? ($anggotaKelas ?? collect()) : ($siswa ?? collect());
    $inputName = $usesAnggotaKelas ? 'anggota_kelas_id' : 'siswa_nis';
    $groupControlId = match ($pickerMode) {
        'ekstrakurikuler' => 'akademik_master_ekstrakuler_id',
        'jemputan' => 'akademik_master_jemputan_id',
        't2q' => 'filter_tahun_ajaran_id',
        default => 'akademik_master_kelas_id',
    };
    $redirectQueryParam = match ($pickerMode) {
        'ekstrakurikuler' => 'ekstrakurikuler_id',
        'jemputan' => 'jemputan_id',
        't2q' => 'tahun_ajaran_id',
        default => 'kelas_id',
    };
    $showHistoryFilter = $showHistoryFilter ?? true;
    $hiddenCount = (int) ($hiddenRegisteredCount ?? 0);
    $inactiveCount = (int) ($hiddenInactiveCount ?? 0);
    $withoutKelasCount = (int) ($hiddenWithoutKelasCount ?? 0);
    $filteredTahunAjaranIds = $excludedTahunAjaranIds ?? [];
    $defaultCreateUrl = match ($pickerMode) {
        'ekstrakurikuler' => route('anggota-ekstrakurikuler.create'),
        'jemputan' => route('anggota-jemputan.create'),
        't2q' => route('anggota-t2q.create'),
        default => route('anggota-kelas.create'),
    };
    $searchPlaceholder = match ($pickerMode) {
        'ekstrakurikuler' => 'Cari nama lengkap, NIS, NISN, kelas, atau ekskul sebelumnya...',
        'jemputan' => 'Cari nama lengkap, NIS, NISN, kelas, atau jemputan sebelumnya...',
        't2q' => 'Cari nama lengkap, NIS, NISN, kelas, tingkat, atau guru T2Q sebelumnya...',
        default => 'Cari nama lengkap, NIS, NISN, atau kelas sebelumnya...',
    };
    $historyFilterAriaLabel = match ($pickerMode) {
        'ekstrakurikuler' => 'Filter riwayat ekstrakurikuler siswa',
        'jemputan' => 'Filter riwayat jemputan siswa',
        't2q' => 'Filter riwayat T2Q siswa',
        default => 'Filter riwayat kelas siswa',
    };
    $withoutPrevOptionLabel = match ($pickerMode) {
        'ekstrakurikuler' => 'Belum Ada Ekskul Sebelumnya',
        'jemputan' => 'Belum Ada Jemputan Sebelumnya',
        't2q' => 'Belum Ada T2Q Sebelumnya',
        default => 'Belum Ada Kelas Sebelumnya',
    };
    $withPrevOptionLabel = match ($pickerMode) {
        'ekstrakurikuler' => 'Sudah Punya Ekskul Sebelumnya',
        'jemputan' => 'Sudah Punya Jemputan Sebelumnya',
        't2q' => 'Sudah Punya T2Q Sebelumnya',
        default => 'Sudah Punya Kelas Sebelumnya',
    };
    $oldValues = old($inputName);
    $selectedIds = is_array($oldValues)
        ? array_map('strval', $oldValues)
        : ($oldValues !== null && $oldValues !== '' ? [(string) $oldValues] : []);
@endphp

<div class="ak-picker-shell shadow-sm {{ $errors->has($inputName) || $errors->has($inputName . '.*') ? 'is-invalid-shell' : '' }}">
    <div class="ak-picker-toolbar">
        <div class="ak-search-group">
            <i class="fa fa-search"></i>
            <input type="text" id="filterSiswaInput" class="ak-search-input"
                placeholder="{{ $searchPlaceholder }}">
        </div>

        @if ($showHistoryFilter)
            <select id="filterKelasSebelumnya" class="ak-filter-select"
                aria-label="{{ $historyFilterAriaLabel }}">
                <option value="all">Semua Siswa Aktif ({{ $items->count() }})</option>
                <option value="without_prev">{{ $withoutPrevOptionLabel }}</option>
                <option value="with_prev">{{ $withPrevOptionLabel }}</option>
            </select>
        @endif

        <div class="ak-toolbar-actions">
            <span class="ak-count-pill" id="selectedSiswaCount">
                <i class="fa fa-check-circle"></i>
                <span>{{ count($selectedIds) }} dipilih</span>
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
        data-create-url="{{ $createRedirectUrl ?? $defaultCreateUrl }}"
        data-group-control-id="{{ $groupControlId }}"
        data-redirect-param="{{ $redirectQueryParam }}">
        @forelse ($items as $row)
            @php
                if ($usesAnggotaKelas) {
                    $optionValue = (string) $row->id;
                    $student = $row->siswa;
                    $kelasModel = $row->kelas;
                    $studentNis = (string) ($student?->nis ?? $row->siswa_nis);
                    $kelasBadgeLabel = $kelasModel
                        ? trim($kelasModel->nama_kelas . ($kelasModel->jenjang_kelas ? ' (' . strtoupper((string) $kelasModel->jenjang_kelas) . ')' : ''))
                        : null;
                    $itemTahunAjaranId = (string) ($kelasModel?->akademik_master_tahun_ajaran_id ?? '');

                    if ($isJemputanMode) {
                        $prevSummary = $student?->resolvePreviousJemputanSummary();
                        $registeredTahunAjaran = $student?->registeredJemputanTahunAjaranIds() ?? [];
                        $historyTitle = 'Jemputan Sebelumnya';
                        $historyIcon = 'fa-bus';
                        $historyEmptyText = 'Belum ada jemputan sebelumnya';
                        $historyPrimary = $prevSummary['petugas'] ?? null;
                        $historySecondary = null;
                        $historyExtraYear = $prevSummary['tarif_label'] ?? null;
                    } elseif ($isT2qMode) {
                        $prevSummary = $student?->resolvePreviousT2qSummary();
                        $registeredTahunAjaran = $student?->registeredT2qTahunAjaranIds() ?? [];
                        $historyTitle = 'T2Q Sebelumnya';
                        $historyIcon = 'fa-book-open';
                        $historyEmptyText = 'Belum ada T2Q sebelumnya';
                        $historyPrimary = $prevSummary['guru'] ?? null;
                        $historySecondary = null;
                        $historyExtraYear = $prevSummary ? ('Tingkat ' . $prevSummary['tingkat']) : null;
                    } else {
                        $prevSummary = $student?->resolvePreviousEkstrakurikulerSummary();
                        $registeredTahunAjaran = $student?->registeredEkstrakurikulerTahunAjaranIds() ?? [];
                        $historyTitle = 'Ekskul Sebelumnya';
                        $historyIcon = 'fa-futbol';
                        $historyEmptyText = 'Belum ada ekskul sebelumnya';
                        $historyPrimary = $prevSummary['nama_ekstrakurikuler'] ?? null;
                        $historySecondary = $prevSummary['unit'] ?? null;
                        $historyExtraYear = null;
                    }

                    $historyYear = $prevSummary['tahun_ajaran'] ?? null;
                    $historySemester = $prevSummary['semester'] ?? null;
                    $searchHaystack = strtolower(trim(implode(' ', array_filter([
                        $student?->nama_lengkap,
                        $studentNis,
                        $student?->nisn,
                        $student?->status,
                        $kelasModel?->nama_kelas,
                        $kelasModel?->jenjang_kelas,
                        $kelasModel?->tingkatan_kelas,
                        $historyPrimary,
                        $historySecondary,
                        $historyExtraYear,
                        $historyYear,
                    ]))));
                } else {
                    $optionValue = (string) $row->nis;
                    $student = $row;
                    $kelasModel = null;
                    $studentNis = (string) $row->nis;
                    $prevSummary = $student->resolvePreviousKelasSummary();
                    $registeredTahunAjaran = $student->registeredTahunAjaranIds();
                    $kelasBadgeLabel = null;
                    $itemTahunAjaranId = '';
                    $historyTitle = 'Kelas Sebelumnya';
                    $historyIcon = 'fa-chalkboard-teacher';
                    $historyEmptyText = 'Belum ada kelas sebelumnya';
                    $historyPrimary = $prevSummary['nama_kelas'] ?? null;
                    $historySecondary = $prevSummary['jenjang_kelas'] ?? null;
                    $historyExtraYear = null;
                    $historyYear = $prevSummary['tahun_ajaran'] ?? null;
                    $historySemester = $prevSummary['semester'] ?? null;
                    $searchHaystack = strtolower(trim(implode(' ', array_filter([
                        $student->nama_lengkap,
                        $studentNis,
                        $student->nisn,
                        $student->status,
                        $historyPrimary,
                        $historySecondary,
                        $historyYear,
                    ]))));
                }

                $avatarClass = match (strtoupper((string) ($student?->jenis_kelamin ?? ''))) {
                    'L' => 'ak-avatar-male',
                    'P' => 'ak-avatar-female',
                    default => 'ak-avatar-default',
                };
                $isChecked = in_array($optionValue, $selectedIds, true);
            @endphp

            <label class="ak-student-card siswa-option-item {{ $isChecked ? 'is-selected' : '' }}"
                for="{{ $inputName }}_{{ $loop->index }}"
                data-search="{{ $searchHaystack }}"
                data-has-prev="{{ $prevSummary !== null ? '1' : '0' }}"
                data-registered-years="{{ implode(',', $registeredTahunAjaran) }}"
                data-kelas-tahun-ajaran-id="{{ $itemTahunAjaranId }}">
                <div class="ak-student-left">
                    <input type="checkbox" class="ak-student-checkbox siswa-checkbox"
                        id="{{ $inputName }}_{{ $loop->index }}" name="{{ $inputName }}[]" value="{{ $optionValue }}"
                        @checked($isChecked)>

                    <span class="ak-student-avatar {{ $avatarClass }}" aria-hidden="true">
                        {{ $student?->inisial_nama ?? 'S' }}
                    </span>

                    <div class="ak-student-identity">
                        <div class="ak-student-name-row">
                            <span class="ak-student-name">{{ $student?->nama_lengkap ?: 'Nama Belum Diisi' }}</span>
                            @if ($student?->jenis_kelamin)
                                <span class="badge badge-info">{{ $student->jenis_kelamin_label }}</span>
                            @endif
                            @if ($student?->status)
                                <span class="badge {{ $student->status_badge_class }}">{{ $student->status }}</span>
                            @endif
                            @if ($kelasBadgeLabel)
                                <span class="badge badge-light border text-slate-700">
                                    <i class="fa fa-school text-warning mr-1" aria-hidden="true"></i>{{ $kelasBadgeLabel }}
                                </span>
                            @endif
                            @if ($isT2qMode && $kelasModel?->tingkatan_kelas)
                                <span class="badge badge-warning text-dark">
                                    Tingkat {{ $kelasModel->tingkatan_kelas }}
                                </span>
                            @endif
                        </div>
                        <div class="ak-student-meta">
                            <span class="ak-nis-chip">
                                <i class="fa fa-id-card text-primary" aria-hidden="true"></i>
                                NIS: {{ $studentNis }}
                            </span>
                            @if ($student?->nisn)
                                <span>NISN: {{ $student->nisn }}</span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="ak-student-history">
                    @if ($prevSummary !== null)
                        <span class="ak-history-label">{{ $historyTitle }}</span>
                        <span class="ak-history-badge">
                            <i class="fa {{ $historyIcon }}" aria-hidden="true"></i>
                            {{ $historyPrimary }}
                            @if ($historySecondary)
                                ({{ strtoupper((string) $historySecondary) }})
                            @endif
                        </span>
                        @if ($historyYear)
                            <span class="ak-history-year">
                                {{ $historyYear }} • Smt {{ strtoupper((string) $historySemester) }}
                            </span>
                        @endif
                        @if ($historyExtraYear)
                            <span class="ak-history-year">
                                {{ $historyExtraYear }}
                            </span>
                        @endif
                    @else
                        <span class="ak-history-empty">
                            <i class="fa fa-history" aria-hidden="true"></i>
                            {{ $historyEmptyText }}
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
                @if ($usesAnggotaKelas)
                    @php
                        $groupNoun = match ($pickerMode) {
                            'jemputan' => 'layanan jemputan',
                            't2q' => 'anggota T2Q',
                            default => 'ekstrakurikuler',
                        };
                    @endphp
                    @if ($hiddenCount > 0)
                        <i class="fa fa-check-circle text-success mb-2" style="font-size: 1.5rem;"></i>
                        <div class="font-weight-bold text-slate-700">
                            Seluruh siswa aktif dengan kelas aktif ({{ $hiddenCount }}) sudah terdaftar pada {{ $groupNoun }} di tahun ajaran/semester ini.
                        </div>
                    @elseif ($withoutKelasCount > 0)
                        <i class="fa fa-info-circle text-warning mb-2" style="font-size: 1.5rem;"></i>
                        <div class="font-weight-bold text-slate-700">
                            Belum ada anggota kelas aktif yang tersedia pada tahun ajaran ini ({{ $withoutKelasCount }} siswa aktif belum memiliki Anggota Kelas).
                        </div>
                    @elseif ($inactiveCount > 0)
                        <i class="fa fa-info-circle text-warning mb-2" style="font-size: 1.5rem;"></i>
                        <div class="font-weight-bold text-slate-700">
                            Tidak ada siswa berstatus Aktif yang tersedia ({{ $inactiveCount }} siswa berstatus Nonaktif/Pindah/Lulus/Keluar disembunyikan).
                        </div>
                    @else
                        Belum ada data anggota kelas aktif yang tersedia. Silakan masukkan siswa ke Anggota Kelas terlebih dahulu.
                    @endif
                @else
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
                @endif
            </div>
        @endforelse

        @if ($items->isNotEmpty())
            <div class="ak-empty-state" id="emptySearchSiswa">
                <i class="fa fa-search mb-2" style="font-size: 1.25rem; color: #94a3b8;"></i>
                <div>Tidak ada siswa yang sesuai dengan pencarian yang dimasukkan.</div>
            </div>
        @endif
    </div>

    <div class="ak-picker-footer">
        <div class="d-flex flex-wrap align-items-center gap-2">
            <span>
                <i class="fa fa-info-circle text-primary mr-1"></i>
                @if ($isJemputanMode)
                    Menampilkan hanya siswa berstatus <strong>Aktif</strong> yang sudah punya anggota kelas &amp; belum terdaftar layanan jemputan pada tahun ajaran ini.
                @elseif ($isT2qMode)
                    Menampilkan hanya siswa berstatus <strong>Aktif</strong> yang sudah punya anggota kelas &amp; belum terdaftar anggota T2Q pada tahun ajaran ini.
                @elseif ($isEkstrakurikulerMode)
                    Menampilkan hanya siswa berstatus <strong>Aktif</strong> yang sudah punya anggota kelas &amp; belum terdaftar ekstrakurikuler pada tahun ajaran ini.
                @else
                    Menampilkan hanya siswa berstatus <strong>Aktif</strong> yang belum terdaftar pada tahun ajaran ini.
                @endif
            </span>
            @if ($hiddenCount > 0)
                <span class="badge badge-info">
                    <i class="fa fa-user-check"></i>
                    @if ($isJemputanMode)
                        {{ $hiddenCount }} sudah punya jemputan (disembunyikan)
                    @elseif ($isT2qMode)
                        {{ $hiddenCount }} sudah terdaftar T2Q (disembunyikan)
                    @elseif ($isEkstrakurikulerMode)
                        {{ $hiddenCount }} sudah punya ekskul (disembunyikan)
                    @else
                        {{ $hiddenCount }} sudah masuk anggota kelas (disembunyikan)
                    @endif
                </span>
            @endif
            @if ($usesAnggotaKelas && $withoutKelasCount > 0)
                <span class="badge badge-warning text-dark">
                    <i class="fa fa-school"></i>
                    {{ $withoutKelasCount }} belum punya anggota kelas (disembunyikan)
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
            Menampilkan {{ $items->count() }} dari {{ $items->count() }} siswa aktif
        </span>
    </div>
</div>

@if ($errors->has($inputName))
    <div class="text-danger mt-1" style="font-style: italic; font-size: 0.75rem;">
        {{ $errors->first($inputName) }}
    </div>
@endif
@if ($errors->has($inputName . '.*'))
    <div class="text-danger mt-1" style="font-style: italic; font-size: 0.75rem;">
        {{ $errors->first($inputName . '.*') }}
    </div>
@endif

@once
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const container = document.getElementById('siswaCheckboxContainer');
            const groupControlId = container?.dataset.groupControlId || 'akademik_master_kelas_id';
            const redirectParam = container?.dataset.redirectParam || 'kelas_id';
            const groupControl = document.getElementById(groupControlId);
            const karyawanSelect = document.getElementById('karyawan_nipy');
            const tingkatSelect = document.getElementById('tingkat');
            const pendaftaranSelect = document.getElementById('pendaftaran');
            const keteranganInput = document.getElementById('keterangan');
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
                if (!groupControl) {
                    return '';
                }

                if (groupControl.tagName === 'SELECT') {
                    if (groupControl.selectedIndex < 0) {
                        return '';
                    }

                    return groupControl.options[groupControl.selectedIndex]?.dataset.tahunAjaranId || '';
                }

                return groupControl.dataset.tahunAjaranId || '';
            };

            const updateConflictIndicators = () => {
                const targetTahunAjaranId = getTargetTahunAjaranId();

                items.forEach((item) => {
                    const registeredYears = (item.dataset.registeredYears || '').split(',').filter(Boolean);
                    const itemKelasYear = item.dataset.kelasTahunAjaranId || '';
                    const hasDuplicateYear = targetTahunAjaranId !== '' && registeredYears.includes(targetTahunAjaranId);
                    const hasMismatchedKelasYear = itemKelasYear !== '' && targetTahunAjaranId !== '' && itemKelasYear !== targetTahunAjaranId;
                    const hasConflict = hasDuplicateYear || hasMismatchedKelasYear;
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

            if (groupControl && groupControl.tagName === 'SELECT') {
                groupControl.addEventListener('change', () => {
                    const selectedYear = getTargetTahunAjaranId();

                    if (createUrl && selectedYear !== '' && filteredYears.length > 0 && !filteredYears.includes(selectedYear)) {
                        const url = new URL(createUrl, window.location.origin);
                        url.searchParams.set(redirectParam, groupControl.value);
                        if (karyawanSelect?.value) {
                            url.searchParams.set('karyawan_nipy', karyawanSelect.value);
                        }
                        if (tingkatSelect?.value && tingkatSelect.value !== '__custom__') {
                            url.searchParams.set('tingkat', tingkatSelect.value);
                        }
                        if (pendaftaranSelect?.value) {
                            url.searchParams.set('pendaftaran', pendaftaranSelect.value);
                        }
                        if (keteranganInput?.value) {
                            url.searchParams.set('keterangan', keteranganInput.value);
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
@endonce

