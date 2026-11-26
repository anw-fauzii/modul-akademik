@extends('layouts.appui')

@section('title')
    <title>Catatan &amp; Komentar Guru T2Q - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @pushOnce('css')
        <style>
            .t2q-c-wrap {
                margin: 0 auto;
            }

            .t2q-c-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                margin-bottom: 0.9rem;
            }

            .t2q-c-periode-seg {
                display: inline-flex;
                align-items: center;
                padding: 0.24rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                gap: 0.25rem;
            }

            .t2q-c-periode-btn {
                padding: 0.36rem 0.9rem;
                border-radius: 7px;
                font-size: 0.76rem;
                font-weight: 700;
                color: #64748b;
                text-decoration: none !important;
                transition: all 0.15s ease;
                white-space: nowrap;
            }

            .t2q-c-periode-btn:hover {
                color: #0f172a;
                background: rgba(255, 255, 255, 0.7);
            }

            .t2q-c-periode-btn.is-active {
                background: #ffffff;
                color: #c2410c;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            }

            .t2q-c-tingkat-pills {
                display: flex;
                flex-wrap: wrap;
                gap: 0.35rem;
                margin-bottom: 0.85rem;
            }

            .t2q-c-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
                padding: 0.34rem 0.78rem;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
                background: #ffffff;
                color: #475569;
                font-size: 0.75rem;
                font-weight: 700;
                text-decoration: none !important;
                transition: all 0.15s ease;
            }

            .t2q-c-pill:hover {
                border-color: #cbd5e1;
                color: #0f172a;
            }

            .t2q-c-pill.is-active {
                background: #fff7ed;
                border-color: #fdba74;
                color: #c2410c;
            }

            .t2q-c-pill-count {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 20px;
                height: 20px;
                padding: 0 0.38rem;
                border-radius: 999px;
                background: #f1f5f9;
                color: #334155;
                font-size: 0.68rem;
                font-weight: 800;
            }

            .t2q-c-pill.is-active .t2q-c-pill-count {
                background: #ffedd5;
                color: #9a3412;
            }

            .t2q-c-filter-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.85rem;
                align-items: end;
            }

            @media (min-width: 768px) {
                .t2q-c-filter-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .t2q-c-col-bulk {
                    grid-column: span 2;
                }
            }

            @media (min-width: 1100px) {
                .t2q-c-filter-grid {
                    grid-template-columns: repeat(12, minmax(0, 1fr));
                }

                .t2q-c-col-status {
                    grid-column: span 2;
                }

                .t2q-c-col-search {
                    grid-column: span 4;
                }

                .t2q-c-col-bulk {
                    grid-column: span 6;
                }
            }

            .t2q-c-list-head {
                display: none;
            }

            @media (min-width: 992px) {
                .t2q-c-list-head {
                    display: grid;
                    grid-template-columns: minmax(270px, 0.9fr) minmax(0, 1.35fr);
                    gap: 1.25rem;
                    padding: 0.7rem 1.15rem;
                    background: #f8fafc;
                    border-bottom: 1px solid #e2e8f0;
                    font-size: 0.7rem;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    color: #64748b;
                }
            }

            .t2q-c-row {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.9rem;
                padding: 1rem 1.15rem;
                border-bottom: 1px solid #f1f5f9;
                background: #ffffff;
                transition: background-color 0.15s ease;
            }

            .t2q-c-row:hover {
                background: #fafafa;
            }

            .t2q-c-row:last-child {
                border-bottom: 0;
            }

            @media (min-width: 992px) {
                .t2q-c-row {
                    grid-template-columns: minmax(270px, 0.9fr) minmax(0, 1.35fr);
                    gap: 1.25rem;
                    align-items: start;
                }
            }

            .t2q-c-student-box {
                display: flex;
                align-items: flex-start;
                gap: 0.78rem;
            }

            .t2q-c-chips {
                display: flex;
                flex-wrap: wrap;
                gap: 0.35rem;
                margin-top: 0.45rem;
            }

            .t2q-c-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.14rem 0.5rem;
                border-radius: 999px;
                font-size: 0.69rem;
                font-weight: 700;
                background: #f1f5f9;
                color: #475569;
            }

            .t2q-c-chip--tahsin {
                background: #fff7ed;
                color: #c2410c;
            }

            .t2q-c-chip--tahfizh {
                background: #ecfdf5;
                color: #047857;
            }

            .t2q-c-editor-head {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                margin-bottom: 0.45rem;
            }

            .t2q-c-textarea {
                width: 100%;
                min-height: 92px;
                padding: 0.65rem 0.85rem;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                font-size: 0.84rem;
                line-height: 1.5;
                color: #0f172a;
                background: #ffffff;
                resize: vertical;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .t2q-c-textarea:focus {
                outline: none;
                border-color: #f97316;
                box-shadow: 0 0 0 3px rgba(249, 115, 22, 0.12);
            }

            .t2q-c-textarea:disabled {
                background: #f8fafc;
                color: #64748b;
                cursor: not-allowed;
            }

            .t2q-c-sticky-bar {
                position: sticky;
                bottom: 0.75rem;
                z-index: 20;
                margin-top: 1rem;
                padding: 0.85rem 1.15rem;
                background: rgba(255, 255, 255, 0.96);
                backdrop-filter: blur(8px);
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                box-shadow: 0 10px 25px rgba(15, 23, 42, 0.09);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }
        </style>
    @endPushOnce

    @php
        $isMid = $periode === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $periodeLabel = $periodeOptions[$periode] ?? strtoupper($periode);
        $finalInfo = $t2qFinalisasi[$periode] ?? null;
        $guruDisplayName = $karyawanProfile?->nama_lengkap_gelar ?: auth()->user()?->name ?: 'Guru T2Q';
    @endphp

    <div class="t2q-c-wrap">
        <div class="wk-hero">
            <div class="wk-hero-top">
                <div>
                    <div class="wk-badge-role">
                        <i class="fa fa-comment-dots"></i> Catatan Guru T2Q &bull; {{ $guruDisplayName }}
                    </div>
                    <h3 class="mt-2">Catatan &amp; Komentar Rapor Guru T2Q</h3>
                    <p>
                        Tuliskan catatan perkembangan bacaan dan hafalan Al-Qur&apos;an secara langsung untuk setiap siswa bimbingan T2Q
                        (<strong>Mid Semester</strong> maupun <strong>Semester</strong>). Komentar siswa SD otomatis terintegrasi ke
                        Rekap Rapor T2Q dan cetakan PDF Rapor.
                    </p>
                </div>
                <div class="wk-hero-actions">
                    <a href="{{ route('guru-t2q.rapor.index', array_filter(['periode' => $periode, 'tingkat' => $selectedTingkat])) }}"
                        class="wk-btn-ghost">
                        <i class="fa fa-file-signature"></i> Buka Rekap Rapor SD
                    </a>
                </div>
            </div>
        </div>

        @if (! $catatanStorageReady)
            <div class="alert alert-warning border-warning mb-3" style="border-radius: 12px;">
                <div class="d-flex align-items-start" style="gap: 0.65rem;">
                    <i class="fa fa-database mt-1 text-warning" style="font-size: 1.05rem;"></i>
                    <div>
                        <strong class="d-block">Kolom penyimpanan Catatan Guru T2Q belum tersedia di database.</strong>
                        <span class="small text-dark">
                            Halaman sudah siap dipakai, namun penyimpanan tidak akan masuk sebelum migrasi dijalankan. Jalankan
                            <code>php artisan migrate</code> pada terminal server/aplikasi terlebih dahulu.
                        </span>
                    </div>
                </div>
            </div>
        @endif

        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show" style="border-radius: 12px;">
                <i class="fa fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
            </div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger" style="border-radius: 12px;">
                <strong><i class="fa fa-exclamation-triangle mr-1"></i> Terdapat kendala saat menyimpan:</strong>
                <ul class="mb-0 mt-1 pl-3 small">
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (! $isEligible)
            <div class="wk-surface">
                <div class="wk-surface-body text-center py-5">
                    <i class="fa fa-user-slash text-muted mb-3" style="font-size: 2rem;"></i>
                    <h5 class="font-weight-bold text-dark">Belum Ada Penugasan Siswa T2Q Aktif</h5>
                    <p class="text-muted mb-0">
                        {{ $ineligibleReason === 'missing_nipy' ? 'Akun Anda belum terhubung dengan NIPY Guru T2Q.' : 'Belum ada siswa bimbingan T2Q yang ditugaskan untuk Anda pada Tahun Ajaran aktif.' }}
                    </p>
                </div>
            </div>
        @else
            <div class="t2q-c-toolbar">
                <div class="t2q-c-periode-seg" role="tablist" aria-label="Pilih Periode Catatan T2Q">
                    <a href="{{ route('guru-t2q.catatan-rapor.index', array_filter(['periode' => 'mid', 'tingkat' => $selectedTingkat])) }}"
                        class="t2q-c-periode-btn {{ $isMid ? 'is-active' : '' }}">
                        <i class="fa fa-clipboard-check mr-1"></i> Mid Semester
                    </a>
                    <a href="{{ route('guru-t2q.catatan-rapor.index', array_filter(['periode' => 'semester', 'tingkat' => $selectedTingkat])) }}"
                        class="t2q-c-periode-btn {{ ! $isMid ? 'is-active' : '' }}">
                        <i class="fa fa-book-reader mr-1"></i> Semester
                    </a>
                </div>

                <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                    <span class="wk-pill-chip">
                        <i class="fa fa-calendar-alt text-primary"></i>
                        {{ $activeTahunAjaran?->nama_tahun_ajaran }} (Sem. {{ $activeTahunAjaran?->semester }})
                    </span>
                    <span class="wk-pill-chip">
                        <i class="fa fa-users text-info"></i>
                        <strong id="t2qCatatanFilledSummary">{{ $stats['filled_count'] }}</strong> / {{ $stats['total_students'] }} Terisi
                    </span>
                    @if ($finalInfo !== null)
                        <span class="wk-pill-chip"
                            style="{{ ($finalInfo['is_final'] ?? false) ? 'background: #fee2e2; color: #b91c1c; border-color: #fecaca;' : 'background: #ecfdf5; color: #047857; border-color: #a7f3d0;' }}">
                            <i class="fa {{ ($finalInfo['is_final'] ?? false) ? 'fa-lock' : 'fa-lock-open' }}"></i>
                            {{ $finalInfo['status_label'] }}
                        </span>
                    @endif
                </div>
            </div>

            @if ($tingkatTabs !== [])
                <div class="t2q-c-tingkat-pills">
                    <a href="{{ route('guru-t2q.catatan-rapor.index', ['periode' => $periode]) }}"
                        class="t2q-c-pill {{ $selectedTingkat === '' ? 'is-active' : '' }}">
                        <span>Semua Tingkat</span>
                        <span class="t2q-c-pill-count">{{ array_sum($tingkatTabs) }}</span>
                    </a>
                    @foreach ($tingkatTabs as $tKey => $tCount)
                        <a href="{{ route('guru-t2q.catatan-rapor.index', ['periode' => $periode, 'tingkat' => $tKey]) }}"
                            class="t2q-c-pill {{ $selectedTingkat === $tKey ? 'is-active' : '' }}">
                            <span>Tingkat {{ $tKey }}</span>
                            <span class="t2q-c-pill-count">{{ $tCount }}</span>
                        </a>
                    @endforeach
                </div>
            @endif

            @if ($stats['locked_count'] > 0 && $stats['unlocked_count'] === 0)
                <div class="alert alert-info mb-3" style="border-radius: 12px;">
                    <i class="fa fa-lock mr-1"></i>
                    Seluruh kelas bimbingan T2Q periode <strong>{{ $periodeLabel }}</strong> berstatus
                    <strong>Final &amp; Terkunci</strong>. Catatan hanya bisa dibaca; buka kunci finalisasi dari
                    <a href="{{ route('guru-t2q.rapor.index', ['periode' => $periode]) }}" class="font-weight-bold">Rekap Rapor T2Q</a>
                    jika perlu merevisi.
                </div>
            @endif

            @if ($rows->isEmpty())
                <div class="wk-surface">
                    <div class="wk-surface-body text-center py-5">
                        <i class="fa fa-inbox text-muted mb-2" style="font-size: 2rem;"></i>
                        <h6 class="font-weight-bold text-dark">Belum Ada Siswa Bimbingan Pada Filter Ini</h6>
                        <p class="small text-muted mb-0">Pilih tab "Semua Tingkat" untuk melihat seluruh siswa bimbingan Anda.</p>
                    </div>
                </div>
            @else
                <div class="wk-surface mb-3">
                    <div class="wk-surface-body">
                        <div class="t2q-c-filter-grid">
                            <div class="t2q-c-col-status">
                                <label for="t2qCatatanStatusFilter" class="small font-weight-bold text-muted mb-1 d-block">Filter Status</label>
                                <select id="t2qCatatanStatusFilter" class="form-control form-control-sm" style="border-radius: 8px; height: 38px;">
                                    <option value="all">Semua ({{ $stats['total_students'] }})</option>
                                    <option value="filled">Sudah Diisi ({{ $stats['filled_count'] }})</option>
                                    <option value="empty">Belum Diisi ({{ $stats['empty_count'] }})</option>
                                </select>
                            </div>

                            <div class="t2q-c-col-search">
                                <label for="t2qCatatanSearch" class="small font-weight-bold text-muted mb-1 d-block">Cari Siswa / Kelas</label>
                                <input type="text" id="t2qCatatanSearch" class="form-control form-control-sm"
                                    style="border-radius: 8px; height: 38px;"
                                    placeholder="Ketik nama siswa, NIS, atau nama kelas...">
                            </div>

                            <div class="t2q-c-col-bulk">
                                <label for="t2qCatatanBulkSelect" class="small font-weight-bold text-muted mb-1 d-block">
                                    Isi Cepat ke Siswa yang Masih Kosong
                                </label>
                                <div class="d-flex" style="gap: 0.5rem;">
                                    <select id="t2qCatatanBulkSelect" class="form-control form-control-sm"
                                        style="border-radius: 8px; height: 38px;">
                                        <option value="">-- Pilih Preset / Template Komentar T2Q --</option>
                                        @foreach ($templates as $tpl)
                                            <option value="{{ $tpl->isi_komentar }}">[{{ $tpl->kategori }}] {{ $tpl->judul }}</option>
                                        @endforeach
                                    </select>
                                    <button type="button" id="t2qCatatanBulkApplyBtn"
                                        class="btn btn-sm btn-outline-warning font-weight-bold"
                                        style="border-radius: 8px; white-space: nowrap; height: 38px;"
                                        {{ $stats['unlocked_count'] === 0 || ! $catatanStorageReady ? 'disabled' : '' }}>
                                        <i class="fa fa-magic mr-1"></i> Terapkan
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <form method="POST" action="{{ route('guru-t2q.catatan-rapor.store') }}" id="t2qCatatanForm">
                    @csrf
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <input type="hidden" name="tingkat" value="{{ $selectedTingkat }}">

                    <div class="wk-surface mb-0">
                        <div class="t2q-c-list-head">
                            <div>Identitas Siswa &amp; Ringkasan Tahsin / Tahfidz</div>
                            <div>Catatan / Komentar Guru T2Q ({{ $periodeLabel }})</div>
                        </div>

                        <div id="t2qCatatanFilterEmpty" class="text-center py-4 text-muted small d-none">
                            <i class="fa fa-search mb-1 d-block" style="font-size: 1.25rem;"></i>
                            Tidak ada siswa yang sesuai dengan filter pencarian.
                        </div>

                        <div id="t2qCatatanList">
                            @foreach ($rows as $idx => $row)
                                @php
                                    /** @var \App\Models\Admin\DataMaster\AnggotaT2q $anggotaT2q */
                                    $anggotaT2q = $row['anggota_t2q'];
                                    $siswa = $row['siswa'];
                                    $kelas = $row['kelas'];
                                    $namaSiswa = $siswa?->nama_lengkap ?? $row['anggota_kelas']?->siswa_nis ?? "Siswa #{$anggotaT2q->id}";
                                    $nisSiswa = $row['anggota_kelas']?->siswa_nis ?? '-';
                                    $namaKelas = $kelas?->nama_kelas ?? '-';
                                    $jenjangLabel = strtoupper(trim((string) ($kelas?->jenjang_kelas ?? '')));
                                    $sqidKey = $anggotaT2q->sqid ?: (string) $anggotaT2q->id;
                                    $rawOld = old("catatan_t2q.{$anggotaT2q->id}", old("catatan_t2q.{$sqidKey}", $row['catatan_t2q'] ?? ''));
                                    $catatanVal = (string) ($rawOld ?? '');
                                    $isFilled = trim($catatanVal) !== '';
                                    $rowLocked = (bool) $row['is_locked'];
                                    $tahsin = $row['tahsin'];
                                    $tahfizh = $row['tahfizh'];
                                @endphp
                                <div class="t2q-c-row js-t2q-catatan-row"
                                    data-search="{{ mb_strtolower($namaSiswa.' '.$nisSiswa.' '.$namaKelas.' '.$anggotaT2q->tingkat) }}"
                                    data-nama="{{ $namaSiswa }}"
                                    data-filled="{{ $isFilled ? '1' : '0' }}"
                                    data-locked="{{ $rowLocked ? '1' : '0' }}">
                                    <div>
                                        <div class="t2q-c-student-box">
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
                                                    <span class="wk-badge-status js-t2q-row-badge"
                                                        style="{{ $isFilled ? 'background: #dcfce7; color: #166534;' : 'background: #f1f5f9; color: #64748b;' }}">
                                                        {{ $isFilled ? 'Terisi' : 'Kosong' }}
                                                    </span>
                                                    @if ($rowLocked)
                                                        <span class="wk-badge-status" style="background: #fee2e2; color: #b91c1c;">
                                                            <i class="fa fa-lock mr-1"></i> Final
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="small text-muted" style="font-size: 0.74rem;">
                                                    NIS {{ $nisSiswa }} &bull; Kelas {{ $namaKelas }}
                                                    @if ($jenjangLabel !== '')
                                                        ({{ $jenjangLabel }})
                                                    @endif
                                                    &bull; Tingkat T2Q {{ $anggotaT2q->tingkat ?: '-' }}
                                                </div>

                                                <div class="t2q-c-chips">
                                                    <span class="t2q-c-chip t2q-c-chip--tahsin">
                                                        Tahsin: {{ ($tahsin['nilai'] ?? null) !== null ? number_format((float) $tahsin['nilai'], 0).' ('.($tahsin['predikat'] ?? '-').')' : 'Belum ada nilai' }}
                                                    </span>
                                                    <span class="t2q-c-chip t2q-c-chip--tahfizh">
                                                        Tahfidz: {{ ($tahfizh['nilai'] ?? null) !== null ? number_format((float) $tahfizh['nilai'], 0).' ('.($tahfizh['predikat'] ?? '-').')' : 'Belum ada nilai' }}
                                                    </span>
                                                    @if ($row['is_sd'])
                                                        <span class="t2q-c-chip" style="background: #eff6ff; color: #1d4ed8;">
                                                            <i class="fa fa-file-pdf"></i> Tampil di Rapor PDF
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="t2q-c-editor-head">
                                            <select class="form-control form-control-sm js-t2q-row-template"
                                                style="max-width: 310px; font-size: 0.75rem; border-radius: 8px;"
                                                {{ $rowLocked || ! $catatanStorageReady ? 'disabled' : '' }}>
                                                <option value="">-- Gunakan Preset / Template Komentar --</option>
                                                @foreach ($templates as $tpl)
                                                    <option value="{{ $tpl->isi_komentar }}">[{{ $tpl->kategori }}] {{ $tpl->judul }}</option>
                                                @endforeach
                                            </select>

                                            <div class="d-flex align-items-center" style="gap: 0.55rem;">
                                                <span class="small text-muted js-t2q-char-count" style="font-size: 0.72rem;">
                                                    {{ mb_strlen($catatanVal) }} / 1500 karakter
                                                </span>
                                                @if (! $rowLocked && $catatanStorageReady)
                                                    <button type="button" class="btn btn-xs btn-link text-danger p-0 js-t2q-clear-btn"
                                                        style="font-size: 0.74rem; font-weight: 700;">
                                                        Kosongkan
                                                    </button>
                                                @endif
                                            </div>
                                        </div>

                                        <textarea name="catatan_t2q[{{ $sqidKey }}]"
                                            rows="3"
                                            maxlength="1500"
                                            class="t2q-c-textarea js-t2q-textarea"
                                            placeholder="Tuliskan catatan atau pesan khusus Guru T2Q untuk ananda {{ $namaSiswa }} pada periode {{ $periodeLabel }}..."
                                            {{ $rowLocked || ! $catatanStorageReady ? 'disabled' : '' }}>{{ $catatanVal }}</textarea>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="t2q-c-sticky-bar">
                        <div class="small text-muted">
                            <span class="font-weight-bold text-dark js-t2q-filled-live">{{ $stats['filled_count'] }}</span> dari
                            <strong>{{ $stats['total_students'] }}</strong> siswa terisi catatan ({{ $periodeLabel }}).
                        </div>
                        <button type="submit" class="btn btn-primary font-weight-bold px-4"
                            style="border-radius: 10px; background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%); border: 0;"
                            {{ $stats['unlocked_count'] === 0 || ! $catatanStorageReady ? 'disabled' : '' }}>
                            <i class="fa fa-save mr-1"></i> Simpan Catatan Guru T2Q
                        </button>
                    </div>
                </form>
            @endif
        @endif
    </div>

    @pushOnce('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const rows = Array.from(document.querySelectorAll('.js-t2q-catatan-row'));
                if (rows.length === 0) {
                    return;
                }

                const searchInput = document.getElementById('t2qCatatanSearch');
                const statusFilter = document.getElementById('t2qCatatanStatusFilter');
                const filterEmpty = document.getElementById('t2qCatatanFilterEmpty');
                const bulkSelect = document.getElementById('t2qCatatanBulkSelect');
                const bulkApplyBtn = document.getElementById('t2qCatatanBulkApplyBtn');
                const liveCounter = document.querySelector('.js-t2q-filled-live');
                const summaryCounter = document.getElementById('t2qCatatanFilledSummary');

                const renderTemplateText = (rawTemplate, studentName) => {
                    return String(rawTemplate || '').replace(/\{nama\}/gi, studentName || 'Ananda');
                };

                const updateStats = () => {
                    const filledCount = rows.filter((row) => row.dataset.filled === '1').length;
                    if (liveCounter) liveCounter.textContent = String(filledCount);
                    if (summaryCounter) summaryCounter.textContent = String(filledCount);
                };

                const updateRowState = (row, textarea) => {
                    const val = String(textarea?.value || '').trim();
                    const isFilled = val.length > 0;
                    row.dataset.filled = isFilled ? '1' : '0';

                    const badge = row.querySelector('.js-t2q-row-badge');
                    if (badge) {
                        badge.textContent = isFilled ? 'Terisi' : 'Kosong';
                        badge.style.background = isFilled ? '#dcfce7' : '#f1f5f9';
                        badge.style.color = isFilled ? '#166534' : '#64748b';
                    }

                    const counter = row.querySelector('.js-t2q-char-count');
                    if (counter && textarea) {
                        counter.textContent = `${textarea.value.length} / 1500 karakter`;
                    }

                    updateStats();
                };

                const applyFilters = () => {
                    const q = String(searchInput?.value || '').trim().toLowerCase();
                    const statusVal = String(statusFilter?.value || 'all');
                    let visible = 0;

                    rows.forEach((row) => {
                        const matchesSearch = q === '' || String(row.dataset.search || '').includes(q);
                        const matchesStatus = statusVal === 'all' ||
                            (statusVal === 'filled' && row.dataset.filled === '1') ||
                            (statusVal === 'empty' && row.dataset.filled === '0');
                        const show = matchesSearch && matchesStatus;
                        row.classList.toggle('d-none', !show);
                        if (show) visible++;
                    });

                    if (filterEmpty) {
                        filterEmpty.classList.toggle('d-none', visible > 0);
                    }
                };

                rows.forEach((row) => {
                    const textarea = row.querySelector('.js-t2q-textarea');
                    const tplSelect = row.querySelector('.js-t2q-row-template');
                    const clearBtn = row.querySelector('.js-t2q-clear-btn');

                    textarea?.addEventListener('input', () => {
                        updateRowState(row, textarea);
                    });

                    tplSelect?.addEventListener('change', () => {
                        if (!textarea || textarea.disabled || !tplSelect.value) {
                            return;
                        }
                        textarea.value = renderTemplateText(tplSelect.value, row.dataset.nama);
                        updateRowState(row, textarea);
                        applyFilters();
                    });

                    clearBtn?.addEventListener('click', () => {
                        if (!textarea || textarea.disabled) {
                            return;
                        }
                        textarea.value = '';
                        if (tplSelect) tplSelect.value = '';
                        updateRowState(row, textarea);
                        applyFilters();
                    });
                });

                bulkApplyBtn?.addEventListener('click', () => {
                    const template = bulkSelect?.value || '';
                    if (!template) {
                        return;
                    }
                    rows.forEach((row) => {
                        if (row.dataset.locked === '1' || row.dataset.filled === '1') {
                            return;
                        }
                        const textarea = row.querySelector('.js-t2q-textarea');
                        if (!textarea || textarea.disabled) {
                            return;
                        }
                        textarea.value = renderTemplateText(template, row.dataset.nama);
                        updateRowState(row, textarea);
                    });
                    applyFilters();
                });

                searchInput?.addEventListener('input', applyFilters);
                statusFilter?.addEventListener('change', applyFilters);
            });
        </script>
    @endPushOnce
@endsection

