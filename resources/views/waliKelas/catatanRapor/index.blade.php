@extends('layouts.appui')

@section('title')
    <title>Catatan &amp; Komentar Rapor Wali Kelas - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @pushOnce('css')
        <style>
            .cr-controlbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                margin-bottom: 1rem;
            }

            .cr-periode-seg {
                display: inline-flex;
                align-items: center;
                padding: 0.24rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                gap: 0.25rem;
            }

            .cr-periode-btn {
                padding: 0.34rem 0.85rem;
                border-radius: 7px;
                font-size: 0.76rem;
                font-weight: 700;
                color: #64748b;
                text-decoration: none !important;
                transition: all 0.15s ease;
                white-space: nowrap;
            }

            .cr-periode-btn:hover {
                color: #0f172a;
                background: rgba(255, 255, 255, 0.7);
            }

            .cr-periode-btn.is-active {
                background: #ffffff;
                color: #c2410c;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            }

            .cr-filter-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.85rem;
                align-items: end;
            }

            @media (min-width: 768px) {
                .cr-filter-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .cr-col-bulk {
                    grid-column: span 2;
                }
            }

            @media (min-width: 1100px) {
                .cr-filter-grid {
                    grid-template-columns: repeat(12, minmax(0, 1fr));
                }

                .cr-col-status {
                    grid-column: span 2;
                }

                .cr-col-search {
                    grid-column: span 4;
                }

                .cr-col-bulk {
                    grid-column: span 6;
                }
            }

            .cr-list-head {
                display: none;
            }

            @media (min-width: 992px) {
                .cr-list-head {
                    display: grid;
                    grid-template-columns: minmax(260px, 0.88fr) minmax(0, 1.35fr);
                    gap: 1.25rem;
                    padding: 0.7rem 1.15rem;
                    background: #f8fafc;
                    border-bottom: 1px solid #e2e8f0;
                    font-size: 0.7rem;
                    font-weight: 700;
                    text-transform: uppercase;
                    letter-spacing: 0.04em;
                    color: #475569;
                }
            }

            .cr-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.9rem;
                padding: 1rem 1.15rem;
                border-bottom: 1px solid #f1f5f9;
                transition: background-color 0.15s ease;
            }

            .cr-row:last-child {
                border-bottom: 0;
            }

            .cr-row:hover {
                background: #fffcf9;
            }

            @media (min-width: 992px) {
                .cr-row {
                    grid-template-columns: minmax(260px, 0.88fr) minmax(0, 1.35fr);
                    gap: 1.25rem;
                    align-items: start;
                }
            }

            .cr-student-box {
                display: flex;
                align-items: flex-start;
                gap: 0.75rem;
                min-width: 0;
            }

            .cr-meta-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 0.35rem;
                margin-top: 0.5rem;
            }

            .cr-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                padding: 0.18rem 0.5rem;
                border-radius: 6px;
                font-size: 0.69rem;
                font-weight: 600;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                color: #475569;
                font-variant-numeric: tabular-nums;
            }

            .cr-chip--t2q {
                background: #f0fdf4;
                border-color: #bbf7d0;
                color: #166534;
            }

            .cr-editor-head {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.45rem;
                margin-bottom: 0.45rem;
            }

            .cr-textarea {
                width: 100%;
                border-radius: 10px;
                border: 1px solid #cbd5e1;
                padding: 0.55rem 0.75rem;
                font-size: 0.83rem;
                line-height: 1.5;
                color: #0f172a;
                background: #ffffff;
                resize: vertical;
                min-height: 74px;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .cr-textarea:focus {
                outline: 0;
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
            }

            .cr-audit-line {
                font-size: 0.7rem;
                color: #64748b;
                margin-top: 0.35rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.4rem;
            }

            .cr-save-bar {
                position: sticky;
                bottom: 0.85rem;
                z-index: 20;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.85rem 1.15rem;
                margin-top: 1rem;
                background: rgba(255, 255, 255, 0.96);
                backdrop-filter: blur(8px);
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                box-shadow: 0 12px 26px -10px rgba(15, 23, 42, 0.18);
            }

            .cr-dirty-pill {
                display: none;
                align-items: center;
                gap: 0.35rem;
                padding: 0.22rem 0.65rem;
                border-radius: 999px;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #c2410c;
                font-size: 0.72rem;
                font-weight: 700;
            }

            .cr-dirty-pill.is-visible {
                display: inline-flex;
            }
        </style>
    @endPushOnce

    @php
        $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $periodeOptions = $periodeOptions ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_OPTIONS;
        $isMid = $periode === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $isSd = $isSd ?? false;
        $assignedClasses = $assignedClasses ?? collect();
        $templates = $templates ?? collect();
        $studentRows = $studentRows ?? collect();
        $stats = $stats ?? ['total' => 0, 'filled' => 0, 'empty' => 0, 'pct' => 0];
        $actorRoleLabel = $actorRoleLabel ?? ($peranDiKelas ?? 'Wali Kelas');
    @endphp

    <div class="app-main__inner">
        @include('waliKelas.partials._hero', [
            'heroTitle' => $isEligible && $selectedKelas ? 'Catatan Rapor Kelas '.$selectedKelas->nama_kelas : 'Catatan & Komentar Rapor Siswa',
            'heroBadgeText' => 'CATATAN RAPOR • '.strtoupper((string) $actorRoleLabel),
            'heroSubtitle' => $isEligible && $selectedKelas
                ? "Kelola catatan wali kelas/pendamping untuk {$selectedKelas->nama_kelas} periode ".($periodeOptions[$periode] ?? $periode).". Bersifat opsional & tidak memblokir cetak PDF Rapor."
                : null,
            'heroActionUrl' => $isEligible && $selectedKelas
                ? route('wali-kelas.perkembangan-siswa.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $periode])
                : null,
            'heroActionLabel' => 'Leger Perkembangan',
            'heroActionIcon' => 'fa-chart-line',
        ])

        @if (! $isEligible)
            @include('waliKelas.partials._ineligible_guard')
        @else
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 10px;">
                    <i class="fa fa-check-circle mr-1"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert" aria-label="Tutup">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (! ($isStorageReady ?? true))
                <div class="alert alert-warning shadow-sm" style="border-radius: 10px;">
                    <i class="fa fa-database mr-1"></i>
                    <strong>Migrasi Database Belum Dijalankan:</strong> Tabel <code>akademik_rapor_catatan_wali</code> belum ada di database. Jalankan <code>php artisan migrate</code> agar catatan dapat disimpan.
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger shadow-sm" style="border-radius: 10px;">
                    <i class="fa fa-exclamation-triangle mr-1"></i>
                    <strong>Gagal menyimpan catatan:</strong> {{ $errors->first() }}
                </div>
            @endif

            <!-- CONTROL BAR: SWITCHER KELAS & PERIODE -->
            <div class="cr-controlbar">
                @if ($assignedClasses->count() > 1)
                    @include('waliKelas.partials._class_switcher', [
                        'switcherRoute' => 'wali-kelas.catatan-rapor.index',
                        'switcherParams' => ['periode' => $periode],
                        'switcherInline' => true,
                    ])
                @else
                    <div class="small text-muted font-weight-semibold">
                        <i class="fa fa-user-check text-warning mr-1"></i>
                        Mengelola catatan sebagai <strong>{{ $actorRoleLabel }}</strong> &bull; Bersifat opsional di Mid Semester &amp; Semester.
                    </div>
                @endif

                <div class="cr-periode-seg" role="group" aria-label="Pilih Periode Rapor">
                    <a href="{{ route('wali-kelas.catatan-rapor.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => 'mid']) }}"
                        class="cr-periode-btn {{ $isMid ? 'is-active' : '' }}">
                        Mid Semester
                    </a>
                    <a href="{{ route('wali-kelas.catatan-rapor.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => 'semester']) }}"
                        class="cr-periode-btn {{ ! $isMid ? 'is-active' : '' }}">
                        Semester
                    </a>
                </div>
            </div>

            <!-- KPI GRID 4 KOLOM SEIMBANG -->
            <div class="wk-kpi-grid">
                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #2563eb;"></span> Total Siswa
                        </div>
                        <div class="wk-kpi-value">{{ $stats['total'] }}</div>
                        <div class="wk-kpi-sub">{{ $selectedKelas->nama_kelas }} ({{ $selectedKelas->jenjang_kelas }})</div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-users"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #10b981;"></span> Catatan Terisi
                        </div>
                        <div class="wk-kpi-value" style="color: #047857;">{{ $stats['filled'] }}</div>
                        <div class="wk-kpi-sub">{{ $stats['pct'] }}% terisi pada periode aktif</div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa fa-comment-dots"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #94a3b8;"></span> Belum Diisi
                        </div>
                        <div class="wk-kpi-value" style="color: #475569;">{{ $stats['empty'] }}</div>
                        <div class="wk-kpi-sub">Opsional (tidak blokir cetak PDF)</div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #f1f5f9; color: #64748b;">
                        <i class="fa fa-edit"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #ea580c;"></span> Periode &amp; Peran
                        </div>
                        <div class="wk-kpi-value" style="font-size: 1.12rem; color: #c2410c;">
                            {{ $isMid ? 'Mid Semester' : 'Semester' }}
                        </div>
                        <div class="wk-kpi-sub">{{ $actorRoleLabel }}</div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-calendar-alt"></i>
                    </span>
                </div>
            </div>

            @if ($studentRows->isEmpty())
                <div class="wk-surface p-4 text-center text-muted small">
                    Belum ada siswa aktif terdaftar pada kelas ini.
                </div>
            @else
                <form method="POST" action="{{ route('wali-kelas.catatan-rapor.store') }}" id="catatanRaporBatchForm">
                    @csrf
                    <input type="hidden" name="kelas_id" value="{{ $selectedKelas->sqid }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">

                    <!-- FILTER & AKSI TEMPLATE CEPAT -->
                    <div class="filter-card">
                        <div class="filter-card-header">
                            <div class="filter-card-title">
                                <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                                <span>Filter Siswa &amp; Template Komentar Cepat</span>
                            </div>
                            <span class="filter-count-pill">
                                <i class="fa fa-users text-warning"></i>
                                <strong id="visibleStudentsCount">{{ $studentRows->count() }}</strong> dari {{ $studentRows->count() }} siswa
                            </span>
                        </div>
                        <div class="filter-card-body">
                            <div class="cr-filter-grid">
                                <div class="cr-col-status">
                                    <label for="catatanStatusFilter" class="filter-label">Status Catatan</label>
                                    <select id="catatanStatusFilter" class="filter-control">
                                        <option value="all">Semua ({{ $stats['total'] }})</option>
                                        <option value="filled">Terisi ({{ $stats['filled'] }})</option>
                                        <option value="empty">Kosong ({{ $stats['empty'] }})</option>
                                    </select>
                                </div>

                                <div class="cr-col-search">
                                    <label for="catatanSearchInput" class="filter-label">Cari Nama atau NIS</label>
                                    <div class="filter-search-group">
                                        <input type="text" id="catatanSearchInput" class="filter-control"
                                            placeholder="Ketik nama atau NIS siswa...">
                                        <button type="button" id="clearCatatanFilterBtn" class="filter-btn filter-btn-secondary filter-btn-icon"
                                            title="Reset Filter">
                                            <i class="fa fa-undo"></i>
                                        </button>
                                    </div>
                                </div>

                                <div class="cr-col-bulk">
                                    <label for="bulkTemplateSelect" class="filter-label">Isi Otomatis Baris yang Kosong</label>
                                    <div class="filter-search-group">
                                        @if ($templates->isNotEmpty())
                                            <select id="bulkTemplateSelect" class="filter-control">
                                                <option value="">-- Pilih Template Komentar Cepat --</option>
                                                @foreach ($templates as $tpl)
                                                    <option value="{{ $tpl->sqid ?? '' }}" data-body="{{ $tpl->isi_komentar }}">
                                                        {{ $tpl->judul }} ({{ $tpl->kategori_label }})
                                                    </option>
                                                @endforeach
                                            </select>
                                            <button type="button" id="applyBulkEmptyBtn" class="filter-btn filter-btn-primary text-nowrap">
                                                <i class="fa fa-magic mr-1"></i> Terapkan
                                            </button>
                                        @else
                                            <div class="small text-muted py-1">Belum ada template komentar aktif.</div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- DAFTAR TERPADU SISWA & EDITOR CATATAN -->
                    <div class="wk-surface mb-0">
                        <div class="cr-list-head">
                            <div>Identitas &amp; Ringkasan Capaian Siswa</div>
                            <div>Catatan / Komentar Wali Kelas &amp; Pendamping ({{ $isMid ? 'Mid Semester' : 'Semester' }})</div>
                        </div>

                        <div id="catatanFilterEmpty" class="text-center py-4 text-muted small d-none">
                            <i class="fa fa-search mb-1 d-block" style="font-size: 1.25rem;"></i>
                            Tidak ada siswa yang sesuai dengan pencarian atau filter status.
                        </div>

                        <div id="catatanStudentList">
                            @foreach ($studentRows as $idx => $row)
                                @php
                                    /** @var \App\Models\Admin\DataMaster\AnggotaKelas $anggota */
                                    $anggota = $row['anggota'];
                                    $siswa = $row['siswa'];
                                    $namaSiswa = $siswa?->nama_lengkap ?? $anggota->siswa_nis;
                                    $catatanObj = $row['catatan'];
                                    $catatanVal = old("catatan.{$anggota->id}", $catatanObj?->catatan ?? '');
                                    $isRowFilled = trim((string) $catatanVal) !== '';
                                    $templateSelectedId = old("komentar_rapor_id.{$anggota->id}", $catatanObj?->komentar_rapor_id);
                                    $absen = $row['presensi'];
                                    $avgRapor = $row['avg_rapor'];
                                    $t2qSum = $row['t2q_summary'];
                                @endphp
                                <div class="cr-row js-catatan-row"
                                    data-search="{{ mb_strtolower($namaSiswa.' '.$anggota->siswa_nis) }}"
                                    data-nama="{{ $namaSiswa }}"
                                    data-filled="{{ $isRowFilled ? '1' : '0' }}">
                                    <div>
                                        <div class="cr-student-box">
                                            @if ($siswa?->avatar_url)
                                                <img src="{{ $siswa->avatar_url }}" alt="{{ $namaSiswa }}" class="wk-avatar-thumb">
                                            @else
                                                <div class="wk-avatar-fallback">
                                                    {{ $siswa?->inisial_nama ?? mb_strtoupper(mb_substr($namaSiswa, 0, 1)) }}
                                                </div>
                                            @endif
                                            <div style="min-width: 0; flex: 1;">
                                                <div class="d-flex flex-wrap align-items-center" style="gap: 0.4rem;">
                                                    <span class="font-weight-bold text-dark" style="font-size: 0.88rem;">
                                                        {{ $idx + 1 }}. {{ $namaSiswa }}
                                                    </span>
                                                    <span class="wk-badge-status js-row-status-badge"
                                                        style="{{ $isRowFilled ? 'background: #dcfce7; color: #166534;' : 'background: #f1f5f9; color: #64748b;' }}">
                                                        {{ $isRowFilled ? 'Terisi' : 'Kosong' }}
                                                    </span>
                                                </div>
                                                <div class="small text-muted" style="font-size: 0.74rem;">
                                                    NIS {{ $anggota->siswa_nis }}
                                                </div>

                                                <div class="cr-meta-chips">
                                                    @if ($isSd && $avgRapor !== null)
                                                        <span class="cr-chip" title="Rata-rata Nilai Rapor">
                                                            <i class="fa fa-star text-warning"></i>
                                                            Rata-rata {{ number_format((float) $avgRapor['avg'], 1) }}
                                                        </span>
                                                    @endif
                                                    <span class="cr-chip" title="Ketidakhadiran (Sakit / Izin / Alpa)">
                                                        S:{{ $absen['sakit'] }} &bull; I:{{ $absen['izin'] }} &bull; A:{{ $absen['alpa'] }}
                                                    </span>
                                                    @if ($t2qSum !== null && ($t2qSum['tahsin_nilai'] !== null || $t2qSum['tahfizh_nilai'] !== null))
                                                        <span class="cr-chip cr-chip--t2q" title="{{ $t2qSum['catatan_t2q'] ?? 'Rekap T2Q' }}">
                                                            <i class="fa fa-quran"></i>
                                                            Tahsin {{ $t2qSum['tahsin_nilai'] !== null ? number_format((float) $t2qSum['tahsin_nilai'], 0) : '-' }} /
                                                            Tahfidz {{ $t2qSum['tahfizh_nilai'] !== null ? number_format((float) $t2qSum['tahfizh_nilai'], 0) : '-' }}
                                                        </span>
                                                    @endif
                                                </div>

                                                @if ($t2qSum !== null && filled($t2qSum['catatan_t2q'] ?? null))
                                                    <div class="small text-success mt-1" style="font-size: 0.72rem; line-height: 1.35;">
                                                        <i class="fa fa-comment-alt mr-1"></i>
                                                        Catatan T2Q: <em>"{{ \Illuminate\Support\Str::limit($t2qSum['catatan_t2q'], 85) }}"</em>
                                                    </div>
                                                @elseif ($t2qSum !== null)
                                                    <div class="small text-muted mt-1" style="font-size: 0.72rem; line-height: 1.35;">
                                                        <i class="fa fa-comment-slash mr-1 text-warning"></i>
                                                        T2Q Tingkat {{ $t2qSum['tingkat'] ?: '-' }} &bull; Catatan Guru T2Q belum diisi
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="cr-editor-head">
                                            @if ($templates->isNotEmpty())
                                                <select name="komentar_rapor_id[{{ $anggota->sqid }}]"
                                                    class="form-control form-control-sm js-row-template-select"
                                                    style="max-width: 280px; font-size: 0.75rem; border-radius: 8px;">
                                                    <option value="">-- Gunakan Template Komentar --</option>
                                                    @foreach ($templates as $tpl)
                                                        <option value="{{ $tpl->sqid ?? '' }}"
                                                            data-body="{{ $tpl->isi_komentar }}"
                                                            @selected($tpl->id !== null && ((string) $templateSelectedId === (string) $tpl->id || (string) $templateSelectedId === (string) $tpl->sqid))>
                                                            {{ $tpl->judul }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                <span></span>
                                            @endif

                                            <button type="button"
                                                class="btn btn-link btn-sm p-0 text-muted js-clear-row-btn"
                                                style="font-size: 0.72rem; text-decoration: none;">
                                                <i class="fa fa-eraser mr-1"></i> Kosongkan
                                            </button>
                                        </div>

                                        <textarea name="catatan[{{ $anggota->sqid }}]"
                                            rows="2"
                                            class="cr-textarea js-catatan-textarea"
                                            placeholder="Tuliskan catatan untuk {{ $namaSiswa }} pada periode {{ $isMid ? 'Mid Semester' : 'Semester' }} (opsional)...">{{ $catatanVal }}</textarea>

                                        <div class="cr-audit-line">
                                            <span>
                                                @if ($catatanObj?->updated_by_name)
                                                    <i class="fa fa-user-check text-success mr-1"></i>
                                                    Terakhir oleh <strong>{{ $catatanObj->updated_by_name }}</strong>
                                                    ({{ $catatanObj->updated_by_role_label ?? 'Wali Kelas' }})
                                                    @if ($catatanObj->updated_at)
                                                        &bull; {{ $catatanObj->updated_at->format('d/m/Y H:i') }}
                                                    @endif
                                                @else
                                                    Belum ada catatan tersimpan pada periode ini.
                                                @endif
                                            </span>
                                            <span class="js-char-counter">0/2000</span>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- STICKY SAVE BAR -->
                    <div class="cr-save-bar">
                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.6rem;">
                            <span class="small text-muted">
                                Total <strong>{{ $studentRows->count() }}</strong> siswa &bull;
                                <strong id="liveFilledCount">{{ $stats['filled'] }}</strong> catatan terisi
                            </span>
                            <span id="crDirtyPill" class="cr-dirty-pill">
                                <i class="fa fa-circle" style="font-size: 0.45rem;"></i> Ada perubahan belum disimpan
                            </span>
                        </div>

                        <button type="submit" class="btn text-white font-weight-bold px-4 shadow-sm"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border: none; border-radius: 10px; font-size: 0.84rem;">
                            <i class="fa fa-save mr-1"></i> Simpan Catatan Rapor ({{ $isMid ? 'Mid Semester' : 'Semester' }})
                        </button>
                    </div>
                </form>
            @endif
        @endif
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('catatanSearchInput');
            const statusFilter = document.getElementById('catatanStatusFilter');
            const clearFilterBtn = document.getElementById('clearCatatanFilterBtn');
            const rows = Array.from(document.querySelectorAll('.js-catatan-row'));
            const visibleCountEl = document.getElementById('visibleStudentsCount');
            const liveFilledCountEl = document.getElementById('liveFilledCount');
            const emptyStateEl = document.getElementById('catatanFilterEmpty');
            const dirtyPill = document.getElementById('crDirtyPill');

            function renderTemplateForStudent(rawBody, studentName) {
                const name = (studentName || 'Ananda').trim();
                return String(rawBody || '').replace(/\{nama\}/gi, name);
            }

            function markDirty() {
                if (dirtyPill) dirtyPill.classList.add('is-visible');
            }

            function autoResize(el) {
                if (!el) return;
                el.style.height = 'auto';
                el.style.height = Math.min(220, Math.max(74, el.scrollHeight + 2)) + 'px';
            }

            function refreshRowState(row) {
                const textarea = row.querySelector('.js-catatan-textarea');
                const badge = row.querySelector('.js-row-status-badge');
                const counter = row.querySelector('.js-char-counter');
                const val = textarea ? textarea.value : '';
                const isFilled = val.trim() !== '';

                row.setAttribute('data-filled', isFilled ? '1' : '0');

                if (badge) {
                    badge.textContent = isFilled ? 'Terisi' : 'Kosong';
                    badge.style.background = isFilled ? '#dcfce7' : '#f1f5f9';
                    badge.style.color = isFilled ? '#166534' : '#64748b';
                }

                if (counter) {
                    counter.textContent = val.length + '/2000';
                }
            }

            function syncLiveFilledTotal() {
                if (!liveFilledCountEl) return;
                const count = rows.filter((r) => r.getAttribute('data-filled') === '1').length;
                liveFilledCountEl.textContent = String(count);
            }

            function applyFilters() {
                const kw = searchInput ? searchInput.value.trim().toLowerCase() : '';
                const statusVal = statusFilter ? statusFilter.value : 'all';
                let visible = 0;

                rows.forEach((row) => {
                    const hay = row.getAttribute('data-search') || '';
                    const isFilled = row.getAttribute('data-filled') === '1';
                    const matchKw = !kw || hay.includes(kw);
                    const matchStatus = statusVal === 'all'
                        || (statusVal === 'filled' && isFilled)
                        || (statusVal === 'empty' && !isFilled);

                    const show = matchKw && matchStatus;
                    row.style.display = show ? '' : 'none';
                    if (show) visible++;
                });

                if (visibleCountEl) visibleCountEl.textContent = String(visible);
                if (emptyStateEl) emptyStateEl.classList.toggle('d-none', visible > 0 || rows.length === 0);
            }

            rows.forEach((row) => {
                const selectEl = row.querySelector('.js-row-template-select');
                const textarea = row.querySelector('.js-catatan-textarea');
                const clearBtn = row.querySelector('.js-clear-row-btn');
                const studentName = row.getAttribute('data-nama') || 'Ananda';

                refreshRowState(row);
                autoResize(textarea);

                if (textarea) {
                    textarea.addEventListener('input', function() {
                        autoResize(this);
                        refreshRowState(row);
                        syncLiveFilledTotal();
                        markDirty();
                    });
                }

                if (selectEl && textarea) {
                    selectEl.addEventListener('change', function() {
                        const opt = this.options[this.selectedIndex];
                        const body = opt ? opt.getAttribute('data-body') : '';
                        if (body) {
                            textarea.value = renderTemplateForStudent(body, studentName);
                            autoResize(textarea);
                            refreshRowState(row);
                            syncLiveFilledTotal();
                            markDirty();
                        }
                    });
                }

                if (clearBtn && textarea) {
                    clearBtn.addEventListener('click', function() {
                        if (textarea.value === '' && (!selectEl || selectEl.value === '')) return;
                        textarea.value = '';
                        if (selectEl) selectEl.value = '';
                        autoResize(textarea);
                        refreshRowState(row);
                        syncLiveFilledTotal();
                        markDirty();
                    });
                }
            });

            if (searchInput) searchInput.addEventListener('input', applyFilters);
            if (statusFilter) statusFilter.addEventListener('change', applyFilters);
            if (clearFilterBtn) {
                clearFilterBtn.addEventListener('click', function() {
                    if (searchInput) searchInput.value = '';
                    if (statusFilter) statusFilter.value = 'all';
                    applyFilters();
                });
            }

            const bulkSelect = document.getElementById('bulkTemplateSelect');
            const applyBulkBtn = document.getElementById('applyBulkEmptyBtn');

            if (bulkSelect && applyBulkBtn) {
                applyBulkBtn.addEventListener('click', function() {
                    const opt = bulkSelect.options[bulkSelect.selectedIndex];
                    const body = opt ? opt.getAttribute('data-body') : '';
                    const tplSqid = bulkSelect.value;
                    if (!body) {
                        if (window.toastr) window.toastr.warning('Pilih template komentar terlebih dahulu.');
                        return;
                    }

                    let filled = 0;
                    rows.forEach((row) => {
                        if (row.style.display === 'none') return;
                        const textarea = row.querySelector('.js-catatan-textarea');
                        const rowSelect = row.querySelector('.js-row-template-select');
                        const studentName = row.getAttribute('data-nama') || 'Ananda';
                        if (textarea && textarea.value.trim() === '') {
                            textarea.value = renderTemplateForStudent(body, studentName);
                            if (rowSelect && tplSqid) rowSelect.value = tplSqid;
                            autoResize(textarea);
                            refreshRowState(row);
                            filled++;
                        }
                    });

                    if (filled > 0) {
                        syncLiveFilledTotal();
                        applyFilters();
                        markDirty();
                    }

                    if (window.toastr) {
                        window.toastr.info(filled + ' baris komentar kosong berhasil diisi template.');
                    }
                });
            }
        });
    </script>
@endpush
