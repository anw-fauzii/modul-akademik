@include('admin.t2q.partials._yaumiyah_shared_styles')

@push('css')
    <style>
        .t2q-filter-grid {
            display: grid;
            grid-template-columns: minmax(290px, 1.4fr) minmax(220px, 1fr) auto;
            gap: 0.9rem;
            align-items: end;
        }

        .t2q-filter-field {
            min-width: 0;
        }

        .t2q-date-group {
            display: flex;
            flex-wrap: nowrap;
            align-items: center;
            gap: 0.45rem;
        }

        .t2q-date-group .filter-control {
            flex: 1 1 150px;
            min-width: 145px;
        }

        .t2q-filter-actions {
            display: flex;
            align-items: center;
            gap: 0.45rem;
        }

        @media (max-width: 991.98px) {
            .t2q-filter-grid {
                grid-template-columns: 1fr;
            }

            .t2q-date-group {
                flex-wrap: wrap;
            }
        }

        .t2q-workspace-table thead th {
            background: #f8fafc;
            color: #64748b;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            border-top: none;
            border-bottom: 1px solid #e2e8f0;
            padding: 0.75rem 0.85rem;
            white-space: nowrap;
        }

        .t2q-workspace-table tbody td {
            padding: 0.75rem 0.85rem;
            border-top: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .t2q-workspace-table tbody tr {
            transition: background-color 0.12s ease;
        }

        .t2q-workspace-table tbody tr:hover {
            background-color: #f8fafc;
        }

        .t2q-workspace-table tbody tr.is-recorded td:first-child {
            box-shadow: inset 3px 0 0 #16a34a;
        }

        .t2q-workspace-table .s2-wrapper {
            width: 100%;
        }

        .t2q-input {
            width: 100%;
            height: 2.25rem;
            padding: 0.35rem 0.65rem;
            font-size: 0.81rem;
            font-weight: 500;
            color: #0f172a;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .t2q-input:focus {
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
        }

        .t2q-input.is-invalid {
            border-color: #dc2626;
        }

        .t2q-prev-meta {
            display: inline-block;
            font-size: 0.76rem;
            font-weight: 600;
            color: #334155;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 7px;
            padding: 0.22rem 0.55rem;
        }

        .t2q-dot-status {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            font-size: 0.69rem;
            font-weight: 700;
            padding: 0.14rem 0.5rem;
            border-radius: 9999px;
        }

        .t2q-dot-status--done {
            background: #dcfce7;
            color: #15803d;
        }

        .t2q-dot-status--pending {
            background: #f1f5f9;
            color: #64748b;
        }

        .t2q-ghost-delete {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            border: 1px solid transparent;
            background: transparent;
            color: #94a3b8;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .t2q-ghost-delete:hover {
            background: #fef2f2;
            border-color: #fecaca;
            color: #dc2626;
        }
    </style>
@endpush

@php
    $errors ??= new \Illuminate\Support\ViewErrorBag;
    $isTahsin = ($mode ?? 'tahsin') === 'tahsin';
    $routePrefix = (string) ($routePrefix ?? 't2q');
    $isGuruT2qScope = (bool) ($isGuruT2qScope ?? false);
    $karyawanProfile = $karyawanProfile ?? null;
    $t2qFinalisasi = $t2qFinalisasi ?? null;

    $indexRoute = $routePrefix . '.' . ($isTahsin ? 'yaumiyah-tahsin.index' : 'yaumiyah-tahfizh.index');
    $matriksRoute = $routePrefix . '.' . ($isTahsin ? 'yaumiyah-tahsin.matriks' : 'yaumiyah-tahfizh.matriks');
    $statistikRoute = $routePrefix . '.' . ($isTahsin ? 'yaumiyah-tahsin.statistik' : 'yaumiyah-tahfizh.statistik');
    $storeRoute = $routePrefix . '.' . ($isTahsin ? 'yaumiyah-tahsin.store' : 'yaumiyah-tahfizh.store');
    $destroyRoute = $routePrefix . '.' . ($isTahsin ? 'yaumiyah-tahsin.destroy' : 'yaumiyah-tahfizh.destroy');
    $pageTitle = $isTahsin ? 'Yaumiyah Tahsin' : 'Yaumiyah Tahfizh';
    $pageSubtitle = $isTahsin
        ? 'Catat setoran harian bacaan jilid / Al-Qur\'an per tingkat (1 setoran per hari).'
        : 'Catat setoran harian hafalan surah Al-Qur\'an per tingkat (1 setoran per hari).';

    $activeTahunAjaran = $activeTahunAjaran ?? null;
    $tanggal = $tanggal ?? now()->toDateString();
    $tanggalFormatted = $tanggalFormatted ?? $tanggal;
    $yesterdayDate = \Carbon\Carbon::parse($tanggal)->subDay()->toDateString();
    $todayDate = now()->toDateString();
    $guruOptions = $guruOptions ?? collect();
    $selectedGuruNipy = (string) ($selectedGuruNipy ?? '');
    $tingkatTabs = $tingkatTabs ?? [];
    $selectedTingkat = (string) ($selectedTingkat ?? '');
    $students = $students ?? collect();
    $todayEntries = $todayEntries ?? collect();
    $previousEntries = $previousEntries ?? collect();
    $targetCapaian = $targetCapaian ?? collect();
    $daftarJilidList = $daftarJilidList ?? collect();
    $surahList = $surahList ?? collect();
    $angkaArabList = $angkaArabList ?? collect();
    $savedCount = (int) ($savedCount ?? $todayEntries->count());

    $jilidJenisById = [];
    foreach ($daftarJilidList as $jItem) {
        $jilidJenisById[(string) $jItem->id] = strtolower(trim((string) $jItem->jenis));
    }
@endphp

<div class="app-main__inner">
    <!-- PAGE HEADER -->
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon text-white shadow-sm"
                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                    <i class="{{ $isTahsin ? 'pe-7s-bookmarks' : 'pe-7s-medal' }}" style="color: white;"></i>
                </div>
                <div>
                    {{ $pageTitle }}
                    <div class="page-title-subheading">
                        {{ $pageSubtitle }}
                    </div>
                </div>
            </div>

            <div class="page-title-actions d-flex flex-wrap align-items-center" style="gap: 0.65rem;">
                @php
                    $sharedViewQuery = array_filter([
                        'tanggal' => $tanggal,
                        'karyawan_nipy' => $isGuruT2qScope ? null : $selectedGuruNipy,
                        'tingkat' => $selectedTingkat,
                    ]);
                @endphp
                <div class="t2q-view-switch" role="navigation" aria-label="Mode tampilan">
                    <a href="{{ route($indexRoute, $sharedViewQuery) }}" class="t2q-view-pill is-active">
                        <i class="fa fa-edit"></i>
                        <span>Input Harian</span>
                    </a>
                    <a href="{{ route($matriksRoute, $sharedViewQuery) }}" class="t2q-view-pill">
                        <i class="fa fa-th"></i>
                        <span>Matriks (Sen–Kam)</span>
                    </a>
                    <a href="{{ route($statistikRoute, array_filter(['tahun_ajaran_id' => $activeTahunAjaran?->sqid, 'karyawan_nipy' => $isGuruT2qScope ? null : $selectedGuruNipy, 'tingkat' => $selectedTingkat])) }}" class="t2q-view-pill">
                        <i class="fa fa-chart-bar"></i>
                        <span>Statistik</span>
                    </a>
                </div>
                @if ($activeTahunAjaran)
                    <span class="badge badge-light border px-3 py-2 font-weight-semibold text-slate-700" style="font-size: 0.78rem; border-radius: 9999px;">
                        <i class="fa fa-calendar-check text-success mr-1"></i>
                        {{ $activeTahunAjaran->full_label }}
                    </span>
                @endif
            </div>
        </div>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger border shadow-sm mb-3" style="border-radius: 12px;">
            <div class="font-weight-bold mb-1">
                <i class="fa fa-exclamation-circle mr-1"></i> Periksa kembali input setoran Anda:
            </div>
            <ul class="mb-0 pl-3 small">
                @foreach ($errors->all() as $errorMessage)
                    <li>{{ $errorMessage }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @if (! $activeTahunAjaran)
        <div class="alert alert-warning border shadow-sm" style="border-radius: 12px;">
            <i class="fa fa-exclamation-triangle mr-1"></i>
            Belum ada <strong>Tahun Ajaran aktif</strong>. Aktifkan Tahun Ajaran terlebih dahulu untuk mulai mencatat {{ $pageTitle }}.
        </div>
    @else
        <!-- FILTER BAR (ALIGNED & MINIMALIST) -->
        <div class="filter-card mb-3">
            <div class="filter-card-body">
                <form method="GET" action="{{ route($indexRoute) }}" class="t2q-filter-grid">
                    <input type="hidden" name="tingkat" value="{{ $selectedTingkat }}">

                    <div class="t2q-filter-field">
                        <label for="filter_tanggal" class="filter-label">Tanggal Setoran</label>
                        <div class="t2q-date-group">
                            <input type="date" name="tanggal" id="filter_tanggal" class="filter-control" value="{{ $tanggal }}">
                            <a href="{{ route($indexRoute, array_filter(['tanggal' => $yesterdayDate, 'karyawan_nipy' => $selectedGuruNipy, 'tingkat' => $selectedTingkat])) }}"
                                class="filter-btn filter-btn-secondary t2q-chip-btn {{ $tanggal === $yesterdayDate ? 'is-current' : '' }}"
                                title="Lihat setoran kemarin">
                                Kemarin
                            </a>
                            <a href="{{ route($indexRoute, array_filter(['tanggal' => $todayDate, 'karyawan_nipy' => $selectedGuruNipy, 'tingkat' => $selectedTingkat])) }}"
                                class="filter-btn filter-btn-secondary t2q-chip-btn {{ $tanggal === $todayDate ? 'is-current' : '' }}"
                                title="Kembali ke hari ini">
                                Hari Ini
                            </a>
                        </div>
                    </div>

                    <div class="t2q-filter-field">
                        <label for="filter_karyawan_nipy" class="filter-label">Guru Pembimbing T2Q</label>
                        @if ($isGuruT2qScope)
                            <input type="hidden" name="karyawan_nipy" value="{{ $selectedGuruNipy }}">
                            <div class="filter-control d-flex align-items-center font-weight-semibold text-slate-700"
                                style="background: #f8fafc; color: #334155;">
                                <i class="fa fa-user-check text-success mr-2"></i>
                                <span class="text-truncate">{{ $karyawanProfile?->nama_gelar ?? $selectedGuruNipy }}</span>
                            </div>
                        @else
                            <select name="karyawan_nipy" id="filter_karyawan_nipy" class="filter-control">
                                <option value="">Semua Guru T2Q</option>
                                @foreach ($guruOptions as $guru)
                                    <option value="{{ $guru->nipy }}" @selected($selectedGuruNipy === (string) $guru->nipy)>
                                        {{ $guru->nama_gelar }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <div class="t2q-filter-actions">
                        <button type="submit" class="filter-btn filter-btn-primary">
                            <i class="fa fa-sync-alt"></i> Terapkan
                        </button>
                        @if ((! $isGuruT2qScope && $selectedGuruNipy !== '') || $tanggal !== $todayDate)
                            <a href="{{ route($indexRoute, array_filter(['tingkat' => $selectedTingkat])) }}"
                                class="filter-btn filter-btn-secondary filter-btn-icon" title="Reset filter">
                                <i class="fa fa-undo"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        @if ($isGuruT2qScope && is_array($t2qFinalisasi) && $t2qFinalisasi !== [])
            @include('guruT2q.partials._finalisasi_card', [
                't2qFinalisasi' => $t2qFinalisasi,
                'compact' => true,
            ])
        @endif

        <!-- TAB TINGKAT & REFERENSI TARGET CAPAIAN -->
        @include('admin.t2q.partials._tingkat_strip', [
            'tabRoute' => $indexRoute,
            'tabBaseQuery' => [
                'tanggal' => $tanggal,
                'karyawan_nipy' => $isGuruT2qScope ? null : $selectedGuruNipy,
            ],
            'tingkatTabs' => $tingkatTabs,
            'selectedTingkat' => $selectedTingkat,
            'targetCapaian' => $targetCapaian,
        ])

        <!-- WORKSPACE TABLE -->
        <div class="t2q-surface mb-3 overflow-hidden">
            @if ($students->isEmpty())
                <div class="text-center py-5 px-4 text-muted">
                    <i class="fa fa-book-reader fa-2x mb-2 text-slate-300 d-block"></i>
                    Belum ada anggota T2Q yang terdaftar untuk filter tingkat / guru pada Tahun Ajaran aktif.
                </div>
            @else
                <div class="px-4 py-3 border-bottom d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem; border-color: #f1f5f9 !important;">
                    <div class="d-flex flex-wrap align-items-center" style="gap: 0.65rem;">
                        <h6 class="font-weight-bold text-slate-800 mb-0" style="font-size: 0.92rem;">
                            Tingkat {{ $selectedTingkat }} &bull; {{ $tanggalFormatted }}
                        </h6>
                        <span class="filter-count-pill">
                            <i class="fa fa-check-circle text-success"></i>
                            <strong>{{ $savedCount }} / {{ $students->count() }}</strong> Tercatat
                        </span>
                    </div>
                    <div class="t2q-search-box">
                        <i class="fa fa-search"></i>
                        <input type="text" id="t2qStudentSearch" placeholder="Cari siswa / NIS / kelas...">
                    </div>
                </div>

                <form method="POST" action="{{ route($storeRoute) }}" id="yaumiyahBatchForm">
                    @csrf
                    <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                    <input type="hidden" name="karyawan_nipy" value="{{ $selectedGuruNipy }}">
                    <input type="hidden" name="tingkat" value="{{ $selectedTingkat }}">

                    <div class="table-responsive">
                        <table class="mb-0 table align-middle t2q-workspace-table" id="yaumiyahInputTable">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 48px;">No</th>
                                    <th style="min-width: 215px;">Siswa</th>
                                    <th style="min-width: 175px;">Setoran Sebelumnya</th>
                                    @if ($isTahsin)
                                        <th style="min-width: 245px;">Bacaan / Jilid</th>
                                    @else
                                        <th style="min-width: 215px;">Surah Al-Qur'an</th>
                                    @endif
                                    <th style="min-width: 145px;">{{ $isTahsin ? 'Hal. / Ayat' : 'Ayat' }} (Arab)</th>
                                    <th class="text-center" style="width: 110px;">Nilai</th>
                                    <th class="text-center" style="width: 56px;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $anggota)
                                    @php
                                        $ak = $anggota->relationLoaded('anggotaKelas') ? $anggota->anggotaKelas : null;
                                        $siswa = ($ak && $ak->relationLoaded('siswa')) ? $ak->siswa : null;
                                        $kelas = ($ak && $ak->relationLoaded('kelas')) ? $ak->kelas : null;
                                        $guru = $anggota->relationLoaded('guruT2q') ? $anggota->guruT2q : null;

                                        $namaSiswa = $siswa?->nama_lengkap ?? ($ak?->siswa_nis ?? ('Anggota #' . $anggota->id));
                                        $nisSiswa = $ak?->siswa_nis ?? '';

                                        $todayEntry = $todayEntries->get($anggota->id);
                                        $prevEntry = $previousEntries->get($anggota->id);
                                        $sourceEntry = $todayEntry ?? $prevEntry;

                                        $oldPrefix = "rows.{$anggota->id}";
                                        $selectedJilidId = old("{$oldPrefix}.akademik_master_t2q_daftar_jilid_id", $sourceEntry?->akademik_master_t2q_daftar_jilid_id ?? '');
                                        $selectedSurahId = old("{$oldPrefix}.akademik_master_t2q_surah_id", $sourceEntry?->akademik_master_t2q_surah_id ?? '');
                                        $selectedAngkaId = old("{$oldPrefix}.akademik_master_t2q_angka_arab_id", $sourceEntry?->akademik_master_t2q_angka_arab_id ?? '');
                                        $nilaiInput = old("{$oldPrefix}.nilai", $todayEntry ? $todayEntry->nilai_formatted : '');

                                        $initialJilidJenis = $jilidJenisById[(string) $selectedJilidId] ?? '';
                                        $showSurahForRow = ! $isTahsin || $initialJilidJenis === 'quran';

                                        $searchText = strtolower(trim("{$namaSiswa} {$nisSiswa} " . ($kelas?->nama_kelas ?? '')));
                                    @endphp
                                    <tr class="{{ $todayEntry ? 'is-recorded' : '' }}" data-search-text="{{ $searchText }}" data-t2q-row>
                                        <td class="text-center text-muted font-weight-semibold">{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.35rem;">
                                                <span class="font-weight-bold text-slate-800" style="font-size: 0.84rem;">
                                                    {{ $namaSiswa }}
                                                </span>
                                                @if ($todayEntry)
                                                    <span class="t2q-dot-status t2q-dot-status--done">
                                                        <i class="fa fa-check"></i> Tercatat
                                                    </span>
                                                @else
                                                    <span class="t2q-dot-status t2q-dot-status--pending">Belum</span>
                                                @endif
                                            </div>
                                            <div class="small text-muted" style="font-size: 0.71rem;">
                                                {{ $nisSiswa }}
                                                @if (! $isGuruT2qScope && $kelas)
                                                    &bull; {{ $kelas->nama_kelas }}
                                                @endif
                                                @if (! $isGuruT2qScope && $guru)
                                                    &bull; {{ $guru->nama_gelar }}
                                                @endif
                                            </div>
                                        </td>
                                        <td>
                                            @if ($prevEntry)
                                                <span class="t2q-prev-meta">
                                                    {{ $prevEntry->ringkasan_capaian }}
                                                    @if ($prevEntry->nilai !== null)
                                                        <strong class="ml-1 text-slate-900">({{ $prevEntry->nilai_formatted }})</strong>
                                                    @endif
                                                </span>
                                                <div class="small text-muted mt-1" style="font-size: 0.68rem;">
                                                    {{ $prevEntry->tanggal?->copy()->locale('id')->translatedFormat('d M Y') }}
                                                </div>
                                            @else
                                                <span class="small text-muted font-italic">Belum pernah setor</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if ($isTahsin)
                                                <select name="rows[{{ $anggota->id }}][akademik_master_t2q_daftar_jilid_id]"
                                                    data-select2="true"
                                                    class="t2q-input form-control-sm js-t2q-jilid @error("{$oldPrefix}.akademik_master_t2q_daftar_jilid_id") is-invalid @enderror">
                                                    <option value="" data-jenis="">-- Pilih Jilid --</option>
                                                    @foreach ($daftarJilidList as $jilid)
                                                        <option value="{{ $jilid->id }}"
                                                            data-jenis="{{ strtolower(trim((string) $jilid->jenis)) }}"
                                                            @selected((string) $selectedJilidId === (string) $jilid->id)>
                                                            {{ $jilid->jilid_latin }} ({{ ucfirst((string) $jilid->jenis) }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error("{$oldPrefix}.akademik_master_t2q_daftar_jilid_id")
                                                    <div class="invalid-feedback d-block" style="font-size: 0.7rem;">{{ $message }}</div>
                                                @enderror

                                                <div class="js-t2q-surah-wrap {{ $showSurahForRow ? '' : 'd-none' }} mt-2">
                                                    <select name="rows[{{ $anggota->id }}][akademik_master_t2q_surah_id]"
                                                        data-select2="true"
                                                        class="t2q-input form-control-sm js-t2q-surah @error("{$oldPrefix}.akademik_master_t2q_surah_id") is-invalid @enderror"
                                                        @disabled(! $showSurahForRow)>
                                                        <option value="" data-max-ayat="0">-- Pilih Surah Al-Qur'an --</option>
                                                        @foreach ($surahList as $surah)
                                                            <option value="{{ $surah->id }}"
                                                                data-max-ayat="{{ (int) $surah->jumlah_ayat }}"
                                                                @selected((string) $selectedSurahId === (string) $surah->id)>
                                                                {{ $surah->label_tampilan }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                    @error("{$oldPrefix}.akademik_master_t2q_surah_id")
                                                        <div class="invalid-feedback d-block" style="font-size: 0.7rem;">{{ $message }}</div>
                                                    @enderror
                                                </div>
                                            @else
                                                <select name="rows[{{ $anggota->id }}][akademik_master_t2q_surah_id]"
                                                    data-select2="true"
                                                    class="t2q-input form-control-sm js-t2q-surah @error("{$oldPrefix}.akademik_master_t2q_surah_id") is-invalid @enderror">
                                                    <option value="" data-max-ayat="0">-- Pilih Surah --</option>
                                                    @foreach ($surahList as $surah)
                                                        <option value="{{ $surah->id }}"
                                                            data-max-ayat="{{ (int) $surah->jumlah_ayat }}"
                                                            @selected((string) $selectedSurahId === (string) $surah->id)>
                                                            {{ $surah->label_tampilan }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error("{$oldPrefix}.akademik_master_t2q_surah_id")
                                                    <div class="invalid-feedback d-block" style="font-size: 0.7rem;">{{ $message }}</div>
                                                @enderror
                                            @endif
                                        </td>
                                        <td>
                                            <select name="rows[{{ $anggota->id }}][akademik_master_t2q_angka_arab_id]"
                                                data-select2="true"
                                                class="t2q-input form-control-sm js-t2q-angka @error("{$oldPrefix}.akademik_master_t2q_angka_arab_id") is-invalid @enderror">
                                                <option value="">-- Pilih Angka --</option>
                                                @foreach ($angkaArabList as $angka)
                                                    <option value="{{ $angka->id }}"
                                                        data-latin-num="{{ ctype_digit((string) $angka->angka_latin) ? (int) $angka->angka_latin : 0 }}"
                                                        @selected((string) $selectedAngkaId === (string) $angka->id)>
                                                        {{ $angka->angka_latin }} ({{ $angka->angka_arab }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            @error("{$oldPrefix}.akademik_master_t2q_angka_arab_id")
                                                <div class="invalid-feedback d-block" style="font-size: 0.7rem;">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" min="0" max="100"
                                                name="rows[{{ $anggota->id }}][nilai]"
                                                value="{{ $nilaiInput }}"
                                                placeholder="0 - 100"
                                                class="t2q-input text-center font-weight-bold js-t2q-nilai @error("{$oldPrefix}.nilai") is-invalid @enderror">
                                            @error("{$oldPrefix}.nilai")
                                                <div class="invalid-feedback d-block" style="font-size: 0.7rem;">{{ $message }}</div>
                                            @enderror
                                        </td>
                                        <td class="text-center">
                                            @if ($todayEntry)
                                                <button type="button"
                                                    class="t2q-ghost-delete js-t2q-delete"
                                                    data-delete-url="{{ route($destroyRoute, $todayEntry) }}"
                                                    data-student-name="{{ $namaSiswa }}"
                                                    title="Hapus setoran tanggal {{ $tanggal }}">
                                                    <i class="fa fa-trash-alt"></i>
                                                </button>
                                            @else
                                                <span class="text-slate-300">&mdash;</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="px-4 py-3 bg-slate-50/70 border-top d-flex flex-wrap align-items-center justify-content-between"
                        style="gap: 0.75rem; border-color: #f1f5f9 !important;">
                        <span class="small text-muted">
                            <i class="fa fa-info-circle mr-1 text-primary"></i>
                            Baris tanpa nilai dilewati otomatis. Mengubah nilai pada tanggal sama memperbarui setoran hari tersebut.
                        </span>
                        <button type="submit" class="filter-btn filter-btn-primary px-4">
                            <i class="fa fa-save"></i> Simpan {{ $pageTitle }}
                        </button>
                    </div>
                </form>

                <!-- Isolated Single Delete Form (avoids nested form bugs) -->
                <form id="t2qSingleDeleteForm" method="POST" class="d-none">
                    @csrf
                    @method('DELETE')
                    <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                    <input type="hidden" name="karyawan_nipy" value="{{ $selectedGuruNipy }}">
                    <input type="hidden" name="tingkat" value="{{ $selectedTingkat }}">
                </form>
            @endif
        </div>
    @endif
</div>

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const isTahsinMode = {{ $isTahsin ? 'true' : 'false' }};
            const searchInput = document.getElementById('t2qStudentSearch');
            const deleteForm = document.getElementById('t2qSingleDeleteForm');

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const term = searchInput.value.trim().toLowerCase();
                    document.querySelectorAll('tr[data-t2q-row]').forEach(function (row) {
                        const haystack = row.getAttribute('data-search-text') || '';
                        row.style.display = (term === '' || haystack.includes(term)) ? '' : 'none';
                    });
                });
            }

            function syncRowControls(row) {
                const jilidSelect = row.querySelector('.js-t2q-jilid');
                const surahWrap = row.querySelector('.js-t2q-surah-wrap');
                const surahSelect = row.querySelector('.js-t2q-surah');
                const angkaSelect = row.querySelector('.js-t2q-angka');
                if (!surahSelect || !angkaSelect) {
                    return;
                }

                let enforceAyatLimit = !isTahsinMode;
                if (isTahsinMode && jilidSelect) {
                    const selectedOption = jilidSelect.options[jilidSelect.selectedIndex];
                    const jenis = selectedOption ? (selectedOption.getAttribute('data-jenis') || '') : '';
                    const isQuran = jenis === 'quran';

                    if (surahWrap) {
                        surahWrap.classList.toggle('d-none', !isQuran);
                    }
                    surahSelect.disabled = !isQuran;
                    if (!isQuran) {
                        surahSelect.value = '';
                    } else if (window.PIONSelect && typeof window.PIONSelect.init === 'function') {
                        window.PIONSelect.init(surahSelect);
                    }
                    enforceAyatLimit = isQuran;
                }

                const selectedSurahOpt = surahSelect.options[surahSelect.selectedIndex];
                const maxAyat = selectedSurahOpt ? parseInt(selectedSurahOpt.getAttribute('data-max-ayat') || '0', 10) : 0;
                const limitActive = enforceAyatLimit && maxAyat > 0;

                Array.from(angkaSelect.options).forEach(function (opt) {
                    if (opt.value === '') {
                        return;
                    }
                    const num = parseInt(opt.getAttribute('data-latin-num') || '0', 10);
                    const over = limitActive && num > maxAyat;
                    opt.disabled = over;
                    opt.hidden = over;
                });

                const currentAngkaOpt = angkaSelect.options[angkaSelect.selectedIndex];
                if (currentAngkaOpt && currentAngkaOpt.disabled) {
                    angkaSelect.value = '';
                }

                if (window.PIONSelect && typeof window.PIONSelect.refresh === 'function') {
                    window.PIONSelect.refresh(surahSelect);
                    window.PIONSelect.refresh(angkaSelect);
                }
            }

            document.querySelectorAll('tr[data-t2q-row]').forEach(function (row) {
                syncRowControls(row);
                const jilidSelect = row.querySelector('.js-t2q-jilid');
                const surahSelect = row.querySelector('.js-t2q-surah');
                if (jilidSelect) {
                    jilidSelect.addEventListener('change', function () {
                        syncRowControls(row);
                    });
                }
                if (surahSelect) {
                    surahSelect.addEventListener('change', function () {
                        syncRowControls(row);
                    });
                }
            });

            document.querySelectorAll('.js-t2q-nilai').forEach(function (input) {
                input.addEventListener('input', function () {
                    if (input.value === '') {
                        return;
                    }
                    const parsed = parseFloat(input.value);
                    if (!Number.isNaN(parsed) && parsed > 100) {
                        input.value = '100';
                    } else if (!Number.isNaN(parsed) && parsed < 0) {
                        input.value = '0';
                    }
                });
            });

            document.querySelectorAll('.js-t2q-delete').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!deleteForm) {
                        return;
                    }
                    const targetUrl = btn.getAttribute('data-delete-url');
                    const studentName = btn.getAttribute('data-student-name') || 'siswa ini';

                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            title: 'Hapus Setoran?',
                            text: 'Hapus catatan setoran ' + studentName + ' pada tanggal terpilih?',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Ya, Hapus',
                            cancelButtonText: 'Batal',
                            buttonsStyling: false,
                            customClass: {
                                confirmButton: 'btn btn-danger mx-1 px-4 py-2 text-sm rounded-lg',
                                cancelButton: 'btn btn-secondary mx-1 px-4 py-2 text-sm rounded-lg'
                            }
                        }).then(function (result) {
                            if (result.isConfirmed) {
                                deleteForm.setAttribute('action', targetUrl);
                                deleteForm.submit();
                            }
                        });
                    } else if (window.confirm('Hapus catatan setoran ' + studentName + '?')) {
                        deleteForm.setAttribute('action', targetUrl);
                        deleteForm.submit();
                    }
                });
            });
        });
    </script>
@endpush
