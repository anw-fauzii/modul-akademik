@extends('layouts.appui')

@section('title')
    <title>Rekap &amp; Deskripsi Rapor T2Q (SD) - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @pushOnce('css')
        <style>
            .t2q-rapor-card {
                position: relative;
                z-index: 1;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                margin-bottom: 0.9rem;
                overflow: visible;
                box-shadow: 0 2px 6px rgba(15, 23, 42, 0.03);
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .t2q-rapor-card.has-open-ms {
                z-index: 60;
            }

            .t2q-rapor-card:hover {
                border-color: #cbd5e1;
                box-shadow: 0 6px 16px -6px rgba(15, 23, 42, 0.08);
            }

            .t2q-rapor-card.is-locked {
                background: #fcfcfd;
                border-color: #bbf7d0;
            }

            .t2q-rapor-head {
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                border-radius: 13px 13px 0 0;
                padding: 0.72rem 1.05rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem;
            }

            .t2q-rapor-body {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0;
            }

            @media (min-width: 992px) {
                .t2q-rapor-body {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }

                .t2q-rapor-panel + .t2q-rapor-panel {
                    border-left: 1px solid #e2e8f0;
                }
            }

            .t2q-rapor-panel {
                padding: 0.95rem 1.05rem;
                min-width: 0;
            }

            @media (max-width: 991.98px) {
                .t2q-rapor-panel + .t2q-rapor-panel {
                    border-top: 1px solid #e2e8f0;
                }
            }

            .t2q-panel-header {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                padding-bottom: 0.62rem;
                margin-bottom: 0.72rem;
                border-bottom: 1px dashed #e2e8f0;
            }

            .t2q-materi-box {
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 0.52rem 0.75rem;
                margin-bottom: 0.72rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem;
            }

            /* Searchable Multi-Select Dropdown */
            .t2q-ms {
                position: relative;
            }

            .t2q-ms-trigger {
                width: 100%;
                min-height: 38px;
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 10px;
                padding: 0.34rem 2rem 0.34rem 0.58rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: flex-start;
                gap: 0.28rem;
                cursor: pointer;
                text-align: left;
                position: relative;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .t2q-ms-trigger:hover:not(.is-disabled) {
                border-color: #94a3b8;
            }

            .t2q-ms.is-open .t2q-ms-trigger {
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.14);
            }

            .t2q-ms-trigger.is-disabled {
                background: #f8fafc;
                cursor: not-allowed;
                opacity: 0.78;
            }

            .t2q-ms-placeholder {
                font-size: 0.74rem;
                color: #94a3b8;
                padding: 0.08rem 0.15rem;
                user-select: none;
            }

            .t2q-ms-tag {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #9a3412;
                font-size: 0.69rem;
                font-weight: 600;
                border-radius: 7px;
                padding: 0.14rem 0.42rem;
                line-height: 1.25;
                max-width: 100%;
            }

            .t2q-ms-tag-remove {
                border: 0;
                background: transparent;
                color: #c2410c;
                font-size: 0.78rem;
                line-height: 1;
                padding: 0 0.08rem;
                cursor: pointer;
                font-weight: 800;
            }

            .t2q-ms-tag-remove:hover {
                color: #7c2d12;
            }

            .t2q-ms-caret {
                position: absolute;
                right: 0.65rem;
                top: 50%;
                transform: translateY(-50%);
                color: #64748b;
                font-size: 0.72rem;
                pointer-events: none;
                transition: transform 0.15s ease;
            }

            .t2q-ms.is-open .t2q-ms-caret {
                transform: translateY(-50%) rotate(180deg);
                color: #ea580c;
            }

            .t2q-ms-menu {
                position: absolute;
                left: 0;
                right: 0;
                top: calc(100% + 5px);
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 12px;
                box-shadow: 0 18px 34px -10px rgba(15, 23, 42, 0.22);
                z-index: 1050;
                display: none;
                overflow: hidden;
            }

            .t2q-ms.is-open .t2q-ms-menu {
                display: block;
            }

            .t2q-ms.is-open-up .t2q-ms-menu {
                top: auto;
                bottom: calc(100% + 5px);
            }

            .t2q-ms-search-wrap {
                padding: 0.5rem 0.58rem;
                border-bottom: 1px solid #f1f5f9;
                background: #f8fafc;
                position: relative;
            }

            .t2q-ms-search-wrap i {
                position: absolute;
                left: 1.15rem;
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8;
                font-size: 0.72rem;
                pointer-events: none;
            }

            .t2q-ms-search {
                width: 100%;
                height: 31px;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                background: #ffffff;
                padding: 0.25rem 0.6rem 0.25rem 1.85rem;
                font-size: 0.74rem;
                color: #0f172a;
                outline: 0;
            }

            .t2q-ms-search:focus {
                border-color: #ea580c;
                box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.12);
            }

            .t2q-ms-list {
                max-height: 205px;
                overflow-y: auto;
                padding: 0.3rem;
            }

            .t2q-ms-option {
                width: 100%;
                border: 0;
                background: transparent;
                border-radius: 8px;
                padding: 0.38rem 0.55rem;
                display: flex;
                align-items: center;
                gap: 0.5rem;
                font-size: 0.74rem;
                color: #1e293b;
                text-align: left;
                cursor: pointer;
                transition: background-color 0.1s ease;
            }

            .t2q-ms-option:hover {
                background: #f8fafc;
            }

            .t2q-ms-option.is-selected {
                background: #fff7ed;
                color: #9a3412;
                font-weight: 600;
            }

            .t2q-ms-check {
                width: 15px;
                height: 15px;
                border-radius: 4px;
                border: 1.5px solid #cbd5e1;
                background: #ffffff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: 0.58rem;
                color: #ffffff;
            }

            .t2q-ms-option.is-selected .t2q-ms-check {
                background: #ea580c;
                border-color: #ea580c;
            }

            .t2q-ms-custom-add {
                width: 100%;
                border: 1px dashed #fdba74;
                background: #fff7ed;
                color: #c2410c;
                border-radius: 8px;
                padding: 0.4rem 0.55rem;
                font-size: 0.73rem;
                font-weight: 600;
                text-align: left;
                cursor: pointer;
                margin-bottom: 0.25rem;
            }

            .t2q-ms-custom-add:hover {
                background: #ffedd5;
            }

            .t2q-ms-footer {
                padding: 0.38rem 0.6rem;
                border-top: 1px solid #f1f5f9;
                background: #f8fafc;
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 0.68rem;
            }

            .t2q-desc-textarea {
                font-size: 0.79rem;
                line-height: 1.48;
                border-radius: 10px;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #0f172a;
                resize: vertical;
            }

            .t2q-desc-textarea:focus {
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
            }

            .t2q-desc-textarea:disabled {
                background: #f8fafc;
                color: #475569;
            }

            .t2q-scale-strip {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.45rem;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.6rem 0.9rem;
                margin-bottom: 0.95rem;
            }

            .t2q-scale-pill {
                font-size: 0.7rem;
                font-weight: 700;
                border-radius: 8px;
                padding: 0.24rem 0.58rem;
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
            }

            .t2q-save-bar {
                position: sticky;
                bottom: 0.75rem;
                z-index: 20;
                background: rgba(255, 255, 255, 0.96);
                backdrop-filter: blur(8px);
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 0.8rem 1.15rem;
                box-shadow: 0 12px 28px -10px rgba(15, 23, 42, 0.18);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                margin-top: 0.5rem;
            }
        </style>
    @endPushOnce

    @php
        $todayDate = \Carbon\Carbon::now();
        $displayName = $karyawanProfile?->nama_gelar ?? Auth::user()?->name ?? 'Guru T2Q';
        $isEligible = $isEligible ?? false;
        $ineligibleReason = $ineligibleReason ?? null;
        $nipy = (string) ($nipy ?? '');
        $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $isMid = $periode === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $periodeLabel = $periodeOptions[$periode] ?? ($isMid ? 'Mid Semester' : 'Semester');
        $tingkatTabs = $tingkatTabs ?? [];
        $selectedTingkat = (string) ($selectedTingkat ?? '');
        $rows = $rows ?? collect();
        $nonSdCount = (int) ($nonSdCount ?? 0);
        $predikatRanges = $predikatRanges ?? [];
        $t2qFinalisasi = $t2qFinalisasi ?? [];
        $stats = $stats ?? [
            'total_sd' => 0,
            'tahsin_ready' => 0,
            'tahfizh_ready' => 0,
            'deskripsi_saved' => 0,
            'locked_count' => 0,
            'unlocked_count' => 0,
        ];
        $tahsinKeunggulanPresets = \App\Models\Admin\T2q\RaporT2q::TAHSIN_KEUNGGULAN_PRESETS;
        $tahsinPeningkatanPresets = \App\Models\Admin\T2q\RaporT2q::TAHSIN_PENINGKATAN_PRESETS;
        $tahfizhKeunggulanPresets = \App\Models\Admin\T2q\RaporT2q::TAHFIDZ_KEUNGGULAN_PRESETS;
        $tahfizhPeningkatanPresets = \App\Models\Admin\T2q\RaporT2q::TAHFIDZ_PENINGKATAN_PRESETS;
    @endphp

    <div class="app-main__inner">
        <!-- HERO BANNER -->
        <div class="wk-hero">
            <div class="wk-hero-glow"></div>
            <div class="row align-items-center position-relative" style="z-index: 1;">
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge text-white px-2 py-1 font-weight-bold"
                            style="background: rgba(234, 88, 12, 0.92); font-size: 11px; border-radius: 6px;">
                            <i class="fa fa-file-signature mr-1"></i> GURU T2Q &bull; REKAP RAPOR SD
                        </span>
                        <span class="small" style="color: rgba(255, 255, 255, 0.72);">
                            <i class="fa fa-calendar-alt mr-1"></i>
                            {{ $todayDate->copy()->locale('id')->translatedFormat('l, d F Y') }}
                        </span>
                    </div>

                    <h3 class="font-weight-bold text-white mb-2">
                        Rekap Nilai Terakhir &amp; Deskripsi Rapor T2Q ({{ $periodeLabel }})
                    </h3>

                    <p class="mb-3" style="color: rgba(255, 255, 255, 0.78); max-width: 680px; font-size: 0.86rem; line-height: 1.5;">
                        Nilai dan materi <strong>Tahsin &amp; Tahfidz Al-Qur'an</strong> diambil otomatis dari catatan <strong>Yaumiyah terakhir</strong> siswa jenjang <strong>SD</strong> (skala predikat minimal 75: MB–SAB). Pilih satu atau beberapa aspek kelebihan &amp; peningkatan lewat dropdown pencarian sebelum finalisasi rapor.
                    </p>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if ($activeTahunAjaran)
                            <span class="badge px-3 py-2"
                                style="background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.22); color: #ffffff; font-size: 0.74rem;">
                                <i class="fa fa-graduation-cap text-warning mr-1"></i>
                                {{ $activeTahunAjaran->full_label }}
                            </span>
                        @endif
                        @if ($isEligible && $stats['total_sd'] > 0)
                            <span class="badge px-3 py-2"
                                style="background: rgba(16, 185, 129, 0.22); border: 1px solid rgba(52, 211, 153, 0.35); color: #ecfdf5; font-size: 0.74rem;">
                                <i class="fa fa-users mr-1"></i>
                                {{ $stats['total_sd'] }} Siswa SD
                                @if ($nonSdCount > 0)
                                    ({{ $nonSdCount }} non-SD disembunyikan)
                                @endif
                            </span>
                        @endif
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="wk-team-card">
                        <div class="text-uppercase font-weight-bold mb-2"
                            style="font-size: 0.68rem; letter-spacing: 0.05em; color: #fb923c;">
                            Periode Rapor Aktif
                        </div>
                        <div class="d-flex gap-2 mb-2">
                            <a href="{{ route('guru-t2q.rapor.index', array_filter(['periode' => 'mid', 'tingkat' => $selectedTingkat])) }}"
                                class="btn btn-sm flex-fill font-weight-bold {{ $isMid ? 'text-white' : 'btn-light text-dark' }}"
                                style="{{ $isMid ? 'background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border-radius: 8px;' : 'border-radius: 8px;' }}">
                                Mid Semester
                            </a>
                            <a href="{{ route('guru-t2q.rapor.index', array_filter(['periode' => 'semester', 'tingkat' => $selectedTingkat])) }}"
                                class="btn btn-sm flex-fill font-weight-bold {{ ! $isMid ? 'text-white' : 'btn-light text-dark' }}"
                                style="{{ ! $isMid ? 'background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border-radius: 8px;' : 'border-radius: 8px;' }}">
                                Semester
                            </a>
                        </div>
                        <div class="small" style="color: rgba(255, 255, 255, 0.74); font-size: 0.71rem; line-height: 1.4;">
                            Pembimbing: <strong class="text-white">{{ $displayName }}</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if (! $isEligible)
            <div class="wk-empty-guard">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                    style="width: 58px; height: 58px; border-radius: 16px; background: #fff7ed; color: #ea580c; font-size: 1.5rem;">
                    <i class="fa fa-quran"></i>
                </div>
                <h5 class="font-weight-bold text-dark mb-2">Belum Ada Daftar Bimbingan T2Q Aktif</h5>
                <p class="small text-muted mb-3">
                    Pada Tahun Ajaran aktif, NIPY Anda (<code>{{ $nipy ?: '-' }}</code>) belum tercatat pada daftar <strong>Anggota T2Q</strong>.
                </p>
                <a href="{{ route('guru-t2q.dashboard') }}" class="btn btn-sm text-white font-weight-semibold px-3"
                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border-radius: 9px;">
                    <i class="fa fa-arrow-left mr-1"></i> Kembali ke Dashboard
                </a>
            </div>
        @elseif ($ineligibleReason === 'no_sd_students')
            <div class="wk-empty-guard">
                <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                    style="width: 58px; height: 58px; border-radius: 16px; background: #eff6ff; color: #2563eb; font-size: 1.5rem;">
                    <i class="fa fa-school"></i>
                </div>
                <h5 class="font-weight-bold text-dark mb-2">Rekap Rapor T2Q Tersedia Khusus Jenjang SD</h5>
                <p class="small text-muted mb-3">
                    @if ($nonSdCount > 0)
                        Seluruh {{ $nonSdCount }} siswa bimbingan T2Q Anda pada Tahun Ajaran aktif berasal dari jenjang non-SD. Fitur Rekap &amp; Deskripsi Rapor T2Q saat ini aktif untuk rapor jenjang <strong>SD</strong>.
                    @else
                        Belum ada siswa jenjang SD yang terdaftar dalam bimbingan T2Q Anda pada Tahun Ajaran aktif.
                    @endif
                </p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="{{ route('guru-t2q.yaumiyah-tahsin.index') }}" class="btn btn-sm btn-outline-primary font-weight-semibold" style="border-radius: 9px;">
                        <i class="fa fa-book-open mr-1"></i> Input Yaumiyah Tahsin
                    </a>
                    <a href="{{ route('guru-t2q.dashboard') }}" class="btn btn-sm text-white font-weight-semibold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border-radius: 9px;">
                        <i class="fa fa-arrow-left mr-1"></i> Kembali ke Dashboard
                    </a>
                </div>
            </div>
        @else
            <!-- KPI STRIP -->
            <div class="wk-kpi-grid">
                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #2563eb;"></span> Siswa SD Bimbingan
                        </div>
                        <div class="wk-kpi-value">{{ $stats['total_sd'] }}</div>
                        <div class="wk-kpi-sub">{{ count($tingkatTabs) }} tingkat SD aktif</div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-users"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #ea580c;"></span> Nilai Akhir Tahsin
                        </div>
                        <div class="wk-kpi-value" style="color: #c2410c;">
                            {{ $stats['tahsin_ready'] }} <span class="text-muted" style="font-size: 0.88rem; font-weight: 600;">/ {{ $stats['total_sd'] }}</span>
                        </div>
                        <div class="wk-kpi-sub">Setoran terakhir tersedia</div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-book-open"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #10b981;"></span> Nilai Akhir Tahfidz
                        </div>
                        <div class="wk-kpi-value" style="color: #047857;">
                            {{ $stats['tahfizh_ready'] }} <span class="text-muted" style="font-size: 0.88rem; font-weight: 600;">/ {{ $stats['total_sd'] }}</span>
                        </div>
                        <div class="wk-kpi-sub">Setoran terakhir tersedia</div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa fa-quran"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Rekap Tersimpan
                        </div>
                        <div class="wk-kpi-value" style="color: #6d28d9;">
                            {{ $stats['deskripsi_saved'] }} <span class="text-muted" style="font-size: 0.88rem; font-weight: 600;">/ {{ $stats['total_sd'] }}</span>
                        </div>
                        <div class="wk-kpi-sub">
                            {{ $stats['locked_count'] > 0 ? $stats['locked_count'].' siswa terkunci final' : 'Siap disimpan & difinalkan' }}
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                        <i class="fa fa-check-double"></i>
                    </span>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success shadow-sm border-0 mb-3" style="border-radius: 12px;">
                    <i class="fa fa-check-circle mr-1"></i> {{ session('success') }}
                </div>
            @endif

            @if (isset($errors) && $errors->any())
                <div class="alert alert-danger shadow-sm border-0 mb-3" style="border-radius: 12px;">
                    <div class="font-weight-bold mb-1">
                        <i class="fa fa-exclamation-triangle mr-1"></i> Periksa kembali pengisian Rekap Rapor T2Q:
                    </div>
                    <ul class="mb-0 pl-3 small">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- SKALA PREDIKAT REFERENSI (KKM 75) -->
            <div class="t2q-scale-strip">
                <span class="small font-weight-bold text-muted mr-1">
                    <i class="fa fa-sliders-h text-warning mr-1"></i> Skala Predikat Rapor T2Q (Min. 75):
                </span>
                @foreach (array_reverse($predikatRanges) as $range)
                    @php
                        $pillCss = match ($range['predikat']) {
                            'SAB' => 'background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0;',
                            'BSH' => 'background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;',
                            'SB' => 'background: #ecfeff; color: #0e7490; border: 1px solid #a5f3fc;',
                            default => 'background: #fffbeb; color: #b45309; border: 1px solid #fde68a;',
                        };
                    @endphp
                    <span class="t2q-scale-pill" style="{{ $pillCss }}">
                        <strong>{{ $range['predikat'] }}</strong>
                        <span>{{ $range['nilai_min'] }}–{{ $range['nilai_max'] }} ({{ $range['keterangan'] }})</span>
                    </span>
                @endforeach
            </div>

            <!-- PANEL FINALISASI T2Q -->
            @include('guruT2q.partials._finalisasi_card', [
                't2qFinalisasi' => $t2qFinalisasi,
                'compact' => true,
            ])

            <!-- TOOLBAR FILTER TINGKAT, SEARCH & GENERATE DRAF OTOMATIS -->
            <div class="wk-surface mb-3">
                <div class="p-3 d-flex flex-wrap align-items-center justify-content-between" style="gap: 0.75rem;">
                    <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                        <a href="{{ route('guru-t2q.rapor.index', ['periode' => $periode]) }}"
                            class="wk-class-pill {{ $selectedTingkat === '' ? 'is-active' : '' }}">
                            <span>Semua Tingkat SD</span>
                            <span class="badge badge-pill {{ $selectedTingkat === '' ? 'badge-light text-dark' : 'badge-secondary' }}">
                                {{ $stats['total_sd'] }}
                            </span>
                        </a>
                        @foreach ($tingkatTabs as $tKey => $tCount)
                            @php $isTingkatActive = $selectedTingkat === $tKey; @endphp
                            <a href="{{ route('guru-t2q.rapor.index', ['periode' => $periode, 'tingkat' => $tKey]) }}"
                                class="wk-class-pill {{ $isTingkatActive ? 'is-active' : '' }}">
                                <span>Tingkat {{ $tKey }}</span>
                                <span class="badge badge-pill {{ $isTingkatActive ? 'badge-light text-dark' : 'badge-secondary' }}">
                                    {{ $tCount }}
                                </span>
                            </a>
                        @endforeach
                    </div>

                    <div class="d-flex flex-wrap align-items-center" style="gap: 0.55rem;">
                        <div class="wk-search-wrap">
                            <i class="fa fa-search"></i>
                            <input type="text" id="t2qRaporSearch" class="wk-search-input"
                                placeholder="Cari nama atau NIS siswa...">
                        </div>

                        @if ($stats['unlocked_count'] > 0)
                            <form method="POST" action="{{ route('guru-t2q.rapor.generate') }}" class="d-inline m-0">
                                @csrf
                                <input type="hidden" name="periode" value="{{ $periode }}">
                                <input type="hidden" name="tingkat" value="{{ $selectedTingkat }}">
                                <button type="submit" class="btn btn-sm font-weight-bold shadow-sm"
                                    style="background: #fff7ed; color: #c2410c; border: 1px solid #fdba74; border-radius: 9px; padding: 0.42rem 0.85rem;">
                                    <i class="fa fa-sync mr-1"></i> Tarik Nilai Terakhir &amp; Generate Draf
                                </button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

            <!-- DAFTAR SISWA & EDITOR REKAP RAPOR -->
            <form method="POST" action="{{ route('guru-t2q.rapor.store') }}" id="t2qRaporBatchForm">
                @csrf
                <input type="hidden" name="periode" value="{{ $periode }}">
                <input type="hidden" name="tingkat" value="{{ $selectedTingkat }}">

                @foreach ($rows as $idx => $row)
                    @php
                        /** @var \App\Models\Admin\DataMaster\AnggotaT2q $anggota */
                        $anggota = $row['anggota'];
                        /** @var \App\Models\Admin\DataMaster\Siswa|null $siswa */
                        $siswa = $row['siswa'];
                        $t2qId = (int) $anggota->id;
                        $isLocked = (bool) ($row['is_locked'] ?? false);
                        $midCutoff = $row['mid_cutoff_date'] ?? null;
                        $tahsin = $row['tahsin'];
                        $tahfizh = $row['tahfizh'];
                        $namaSiswa = $siswa?->nama_lengkap ?? ($anggota->anggotaKelas?->siswa_nis ?? 'Siswa');
                        $nisSiswa = $siswa?->nis ?? ($anggota->anggotaKelas?->siswa_nis ?? '-');
                        $searchBlob = mb_strtolower(implode(' ', array_filter([
                            $namaSiswa,
                            $nisSiswa,
                            'tingkat '.$anggota->tingkat,
                            $tahsin['materi'] ?? null,
                            $tahfizh['materi'] ?? null,
                        ])));
                    @endphp

                    <div class="t2q-rapor-card {{ $isLocked ? 'is-locked' : '' }}" data-rapor-card="{{ $t2qId }}" data-search-text="{{ $searchBlob }}">
                        <div class="t2q-rapor-head">
                            <div class="d-flex align-items-center" style="gap: 0.7rem; min-width: 0;">
                                <span class="badge badge-light border font-weight-bold text-muted px-2 py-1" style="border-radius: 8px;">
                                    #{{ $idx + 1 }}
                                </span>
                                @if ($siswa?->avatar_url)
                                    <img src="{{ $siswa->avatar_url }}" alt="{{ $namaSiswa }}" class="wk-avatar-thumb">
                                @else
                                    <div class="wk-avatar-fallback">
                                        {{ $siswa?->inisial_nama ?? mb_strtoupper(mb_substr($namaSiswa, 0, 1)) }}
                                    </div>
                                @endif
                                <div style="min-width: 0;">
                                    <div class="font-weight-bold text-dark" style="font-size: 0.9rem; line-height: 1.25;">
                                        {{ $namaSiswa }}
                                    </div>
                                    <div class="small text-muted" style="font-size: 0.73rem;">
                                        NIS: <span class="font-weight-semibold">{{ $nisSiswa }}</span>
                                        @if ($siswa?->jenis_kelamin_label)
                                            &bull; {{ $siswa->jenis_kelamin_label }}
                                        @endif
                                        &bull; <span class="font-weight-semibold text-dark">Tingkat {{ $anggota->tingkat }}</span>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex flex-wrap align-items-center" style="gap: 0.4rem;">
                                @if ($midCutoff)
                                    <span class="badge px-2 py-1" style="background: #eff6ff; color: #1d4ed8; border-radius: 8px; font-size: 0.7rem;">
                                        <i class="fa fa-calendar-check mr-1"></i> Cut-off Mid: {{ \Carbon\Carbon::parse($midCutoff)->locale('id')->translatedFormat('d M Y') }}
                                    </span>
                                @endif
                                @if (($tahsin['has_newer_yaumiyah'] ?? false) || ($tahfizh['has_newer_yaumiyah'] ?? false))
                                    <span class="badge px-2 py-1" style="background: #fff7ed; color: #c2410c; border-radius: 8px; font-size: 0.7rem;">
                                        <i class="fa fa-bolt mr-1"></i> Disinkronkan ke Setoran Terbaru
                                    </span>
                                @endif
                                @if ($isLocked)
                                    <span class="badge px-2 py-1 font-weight-bold" style="background: #dcfce7; color: #166534; border-radius: 8px; font-size: 0.72rem;">
                                        <i class="fa fa-lock mr-1"></i> Terkunci (Final)
                                    </span>
                                @else
                                    <span class="badge px-2 py-1" style="background: #f1f5f9; color: #475569; border-radius: 8px; font-size: 0.7rem;">
                                        <i class="fa fa-pen mr-1"></i> Dapat Diedit
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="t2q-rapor-body">
                            @foreach ([
                                ['key' => 'tahsin', 'title' => "TAHSIN AL-QUR'AN", 'icon' => 'fa-book-open', 'accent' => '#ea580c', 'data' => $tahsin, 'kPresets' => $tahsinKeunggulanPresets, 'pPresets' => $tahsinPeningkatanPresets, 'placeholderK' => 'Pilih kelebihan Tahsin (bisa >1)...', 'placeholderP' => 'Pilih peningkatan Tahsin (bisa >1)...'],
                                ['key' => 'tahfizh', 'title' => "TAHFIDZ AL-QUR'AN", 'icon' => 'fa-quran', 'accent' => '#059669', 'data' => $tahfizh, 'kPresets' => $tahfizhKeunggulanPresets, 'pPresets' => $tahfizhPeningkatanPresets, 'placeholderK' => 'Pilih kelebihan Tahfidz (bisa >1)...', 'placeholderP' => 'Pilih peningkatan Tahfidz (bisa >1)...'],
                            ] as $panel)
                                @php
                                    $ch = $panel['key'];
                                    $d = $panel['data'];
                                    $hasData = (bool) ($d['has_data'] ?? false);
                                    $predCode = $d['predikat'] ?? null;
                                    $predLabel = $d['predikat_label'] ?? null;
                                    $predBadgeStyle = match ($predCode) {
                                        'SAB' => 'background: #ecfdf5; color: #047857;',
                                        'BSH' => 'background: #eff6ff; color: #1d4ed8;',
                                        'SB' => 'background: #ecfeff; color: #0e7490;',
                                        'MB' => 'background: #fffbeb; color: #b45309;',
                                        default => 'background: #f1f5f9; color: #64748b;',
                                    };
                                    $kVal = (string) old("{$ch}.{$t2qId}.keunggulan", $d['keunggulan'] ?? '');
                                    $pVal = (string) old("{$ch}.{$t2qId}.peningkatan", $d['peningkatan'] ?? '');
                                    $kItems = \App\Models\Admin\T2q\RaporT2q::parseAspectList($kVal);
                                    $pItems = \App\Models\Admin\T2q\RaporT2q::parseAspectList($pVal);
                                    $kOptions = array_values(array_unique(array_merge($panel['kPresets'], $kItems)));
                                    $pOptions = array_values(array_unique(array_merge($panel['pPresets'], $pItems)));
                                    $descVal = old("{$ch}.{$t2qId}.deskripsi", $d['deskripsi'] ?? '');
                                    $nilaiNum = $d['nilai'] ?? null;
                                    $nilaiDisplay = $nilaiNum !== null ? number_format((float) $nilaiNum, 0) : '-';
                                @endphp

                                <div class="t2q-rapor-panel js-t2q-channel-panel"
                                    data-mode="{{ $ch }}"
                                    data-nilai="{{ $nilaiNum !== null ? $nilaiNum : '' }}"
                                    data-predikat="{{ $predCode ?? '' }}"
                                    data-materi="{{ $d['materi'] ?? '' }}">
                                    <div class="t2q-panel-header">
                                        <div class="font-weight-bold text-dark d-flex align-items-center" style="font-size: 0.78rem; gap: 0.4rem;">
                                            <i class="fa {{ $panel['icon'] }}" style="color: {{ $panel['accent'] }};"></i>
                                            <span>{{ $panel['title'] }}</span>
                                            @if ($ch === 'tahfizh' && $hasData)
                                                <span class="badge px-2 py-0" style="background: #f1f5f9; color: #475569; font-size: 0.65rem; border-radius: 6px;">
                                                    {{ ($d['has_ayat'] ?? false) ? 'Konteks Ayat' : 'Surah Utuh' }}
                                                </span>
                                            @endif
                                        </div>

                                        <div class="d-flex align-items-center" style="gap: 0.35rem;">
                                            <span class="wk-badge-status" style="{{ $predBadgeStyle }} font-size: 0.7rem; padding: 0.18rem 0.55rem;">
                                                Nilai: <strong>{{ $nilaiDisplay }}</strong>
                                                @if ($predCode)
                                                    &bull; {{ $predCode }} ({{ $predLabel }})
                                                @endif
                                            </span>
                                        </div>
                                    </div>

                                    <div class="t2q-materi-box">
                                        <div style="min-width: 0;">
                                            <div class="small text-muted" style="font-size: 0.66rem; text-transform: uppercase; letter-spacing: 0.04em;">
                                                Materi Terakhir
                                            </div>
                                            <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.82rem;">
                                                {{ $hasData && filled($d['materi'] ?? null) ? $d['materi'] : 'Belum ada setoran Yaumiyah' }}
                                            </div>
                                        </div>
                                        @if (! empty($d['tanggal_formatted']))
                                            <span class="badge badge-light border text-muted text-nowrap" style="font-size: 0.67rem;">
                                                {{ $d['tanggal_formatted'] }}
                                            </span>
                                        @endif
                                    </div>

                                    <div class="row mb-2">
                                        <div class="col-md-6 mb-2 mb-md-0">
                                            <label class="small font-weight-bold text-slate-700 mb-1 d-block" style="font-size: 0.72rem;">
                                                Terutama dalam <span class="text-muted font-weight-normal">(kelebihan)</span>
                                            </label>
                                            <div class="t2q-ms js-t2q-ms"
                                                data-field-role="keunggulan"
                                                data-placeholder="{{ $panel['placeholderK'] }}"
                                                data-selected="{{ json_encode($kItems) }}"
                                                data-options="{{ json_encode($kOptions) }}">
                                                <input type="hidden"
                                                    name="{{ $ch }}[{{ $t2qId }}][keunggulan]"
                                                    value="{{ $kVal }}"
                                                    class="js-t2q-ms-input js-t2q-keunggulan"
                                                    @disabled($isLocked)>

                                                <button type="button"
                                                    class="t2q-ms-trigger js-t2q-ms-trigger {{ $isLocked ? 'is-disabled' : '' }}"
                                                    aria-expanded="false"
                                                    @disabled($isLocked)>
                                                    <span class="t2q-ms-tags js-t2q-ms-tags d-flex flex-wrap align-items-center" style="gap: 0.25rem;"></span>
                                                    <i class="fa fa-chevron-down t2q-ms-caret"></i>
                                                </button>

                                                <div class="t2q-ms-menu js-t2q-ms-menu">
                                                    <div class="t2q-ms-search-wrap">
                                                        <i class="fa fa-search"></i>
                                                        <input type="text"
                                                            class="t2q-ms-search js-t2q-ms-search"
                                                            placeholder="Cari atau ketik poin baru...">
                                                    </div>
                                                    <div class="t2q-ms-list js-t2q-ms-list"></div>
                                                    <div class="t2q-ms-footer">
                                                        <span class="text-muted js-t2q-ms-count">0 dipilih</span>
                                                        <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                                            <button type="button" class="btn btn-link btn-sm p-0 text-danger font-weight-bold js-t2q-ms-clear" style="font-size: 0.68rem;">Kosongkan</button>
                                                            <button type="button" class="btn btn-link btn-sm p-0 text-primary font-weight-bold js-t2q-ms-done" style="font-size: 0.68rem;">Selesai</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <label class="small font-weight-bold text-slate-700 mb-1 d-block" style="font-size: 0.72rem;">
                                                Perlu peningkatan dalam <span class="text-muted font-weight-normal">(kekurangan)</span>
                                            </label>
                                            <div class="t2q-ms js-t2q-ms"
                                                data-field-role="peningkatan"
                                                data-placeholder="{{ $panel['placeholderP'] }}"
                                                data-selected="{{ json_encode($pItems) }}"
                                                data-options="{{ json_encode($pOptions) }}">
                                                <input type="hidden"
                                                    name="{{ $ch }}[{{ $t2qId }}][peningkatan]"
                                                    value="{{ $pVal }}"
                                                    class="js-t2q-ms-input js-t2q-peningkatan"
                                                    @disabled($isLocked)>

                                                <button type="button"
                                                    class="t2q-ms-trigger js-t2q-ms-trigger {{ $isLocked ? 'is-disabled' : '' }}"
                                                    aria-expanded="false"
                                                    @disabled($isLocked)>
                                                    <span class="t2q-ms-tags js-t2q-ms-tags d-flex flex-wrap align-items-center" style="gap: 0.25rem;"></span>
                                                    <i class="fa fa-chevron-down t2q-ms-caret"></i>
                                                </button>

                                                <div class="t2q-ms-menu js-t2q-ms-menu">
                                                    <div class="t2q-ms-search-wrap">
                                                        <i class="fa fa-search"></i>
                                                        <input type="text"
                                                            class="t2q-ms-search js-t2q-ms-search"
                                                            placeholder="Cari atau ketik poin baru...">
                                                    </div>
                                                    <div class="t2q-ms-list js-t2q-ms-list"></div>
                                                    <div class="t2q-ms-footer">
                                                        <span class="text-muted js-t2q-ms-count">0 dipilih</span>
                                                        <div class="d-flex align-items-center" style="gap: 0.5rem;">
                                                            <button type="button" class="btn btn-link btn-sm p-0 text-danger font-weight-bold js-t2q-ms-clear" style="font-size: 0.68rem;">Kosongkan</button>
                                                            <button type="button" class="btn btn-link btn-sm p-0 text-primary font-weight-bold js-t2q-ms-done" style="font-size: 0.68rem;">Selesai</button>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div class="d-flex align-items-center justify-content-between mb-1">
                                            <label class="small font-weight-bold text-slate-700 mb-0" style="font-size: 0.72rem;">
                                                Deskripsi Rapor Final
                                            </label>
                                            @if (! $isLocked && $hasData)
                                                <button type="button" class="btn btn-link btn-sm p-0 text-warning font-weight-bold js-t2q-reset-desc"
                                                    style="font-size: 0.7rem;">
                                                    <i class="fa fa-magic mr-1"></i> Susun Otomatis
                                                </button>
                                            @endif
                                        </div>
                                        <textarea name="{{ $ch }}[{{ $t2qId }}][deskripsi]"
                                            rows="3"
                                            class="form-control form-control-sm t2q-desc-textarea js-t2q-deskripsi"
                                            placeholder="{{ $hasData ? 'Kalimat deskripsi otomatis...' : 'Belum ada nilai Yaumiyah pada periode ini.' }}"
                                            @disabled($isLocked)>{{ $descVal }}</textarea>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach

                <div class="t2q-save-bar">
                    <div class="small text-muted">
                        <i class="fa fa-info-circle text-warning mr-1"></i>
                        Menampilkan <strong>{{ $rows->count() }}</strong> siswa SD &bull; Pilih lebih dari 1 kelebihan/kekurangan via dropdown ber-pencarian.
                    </div>

                    @if ($stats['unlocked_count'] > 0)
                        <button type="submit" class="btn text-white font-weight-bold px-4 shadow-sm"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px;">
                            <i class="fa fa-save mr-1"></i> Simpan Rekap &amp; Deskripsi Rapor
                        </button>
                    @else
                        <span class="badge badge-success px-3 py-2 font-weight-bold" style="border-radius: 10px; font-size: 0.78rem;">
                            <i class="fa fa-lock mr-1"></i> Seluruh Siswa Terkunci (Final)
                        </span>
                    @endif
                </div>
            </form>
        @endif
    </div>
@endsection

@push('js')
    <script>
        (function () {
            const searchInput = document.getElementById('t2qRaporSearch');
            const cards = document.querySelectorAll('[data-rapor-card]');

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const q = String(this.value || '').trim().toLowerCase();
                    cards.forEach(function (card) {
                        const haystack = String(card.getAttribute('data-search-text') || '');
                        card.style.display = (q === '' || haystack.includes(q)) ? '' : 'none';
                    });
                });
            }

            const OPENINGS = {
                SAB: 'Ananda menunjukkan capaian sangat baik',
                BSH: 'Ananda berkembang sesuai harapan',
                SB: 'Ananda sedang berkembang',
                MB: 'Ananda mulai berkembang'
            };

            function sanitizeFragment(raw, pattern) {
                let text = String(raw || '').replace(/\s+/g, ' ').trim();
                if (!text) return '';
                text = text.replace(pattern, '').trim();
                return text.replace(/[\.\s]+$/g, '');
            }

            function parseTahfizhContext(materi) {
                const clean = String(materi || '').replace(/\s+/g, ' ').trim();
                if (!clean) {
                    return { hasAyat: false, surahLabel: '', ayatLabel: null };
                }
                const m = clean.match(/^(.+?)\s+ayat\s+(.+)$/i);
                if (m) {
                    return { hasAyat: true, surahLabel: m[1].trim(), ayatLabel: m[2].trim() };
                }
                return { hasAyat: false, surahLabel: clean, ayatLabel: null };
            }

            function formatIndonesianList(items) {
                const clean = [];
                const seen = new Set();
                (items || []).forEach(function (raw) {
                    const text = String(raw || '').replace(/\s+/g, ' ').replace(/[\.\s]+$/g, '').trim();
                    if (!text) return;
                    const key = text.toLowerCase();
                    if (seen.has(key)) return;
                    seen.add(key);
                    clean.push(text);
                });

                if (clean.length === 0) return '';
                if (clean.length === 1) return clean[0];
                if (clean.length === 2) return clean[0] + ' dan ' + clean[1];
                return clean.slice(0, -1).join(', ') + ', dan ' + clean[clean.length - 1];
            }

            function composeAutoDeskripsi(panel) {
                const mode = panel.getAttribute('data-mode') || 'tahsin';
                const predCode = String(panel.getAttribute('data-predikat') || '').toUpperCase();
                const materi = String(panel.getAttribute('data-materi') || '').trim();
                const opening = OPENINGS[predCode];
                if (!opening || !materi) return '';

                let core = '';
                if (mode === 'tahfizh') {
                    const ctx = parseTahfizhContext(materi);
                    core = (ctx.hasAyat && ctx.ayatLabel)
                        ? (opening + ' dalam menghafal dan melafalkan ' + ctx.surahLabel + ' hingga ayat ' + ctx.ayatLabel)
                        : (opening + ' dalam melafalkan ' + ctx.surahLabel);
                } else {
                    core = opening + ' dalam menguasai kompetensi tahsin ' + materi;
                }

                const keunggulanEl = panel.querySelector('.js-t2q-keunggulan');
                const peningkatanEl = panel.querySelector('.js-t2q-peningkatan');

                const keunggulan = sanitizeFragment(keunggulanEl ? keunggulanEl.value : '', /^(terutama\s+(dalam\s+)?)/i);
                const peningkatan = sanitizeFragment(peningkatanEl ? peningkatanEl.value : '', /^(perlu\s+peningkatan\s+(dalam\s+)?)/i);

                const firstSentence = keunggulan !== ''
                    ? (core + ', terutama dalam ' + keunggulan + '.')
                    : (core + '.');

                if (peningkatan !== '') {
                    return firstSentence + ' Perlu peningkatan dalam ' + peningkatan + '.';
                }

                if (predCode === 'MB') {
                    const fallbackFocus = mode === 'tahfizh' ? 'kelancaran hafalan' : 'kelancaran dan ketepatan bacaan';
                    return firstSentence + ' Perlu peningkatan dalam ' + fallbackFocus + '.';
                }

                return firstSentence;
            }

            function updatePanelDescription(panel) {
                const descEl = panel.querySelector('.js-t2q-deskripsi');
                if (!descEl || descEl.disabled) return;
                const generated = composeAutoDeskripsi(panel);
                if (generated !== '') {
                    descEl.value = generated;
                }
            }

            function closeAllDropdowns(exceptEl) {
                document.querySelectorAll('.js-t2q-ms.is-open').forEach(function (ms) {
                    if (ms === exceptEl) return;
                    ms.classList.remove('is-open', 'is-open-up');
                    const trig = ms.querySelector('.js-t2q-ms-trigger');
                    if (trig) trig.setAttribute('aria-expanded', 'false');
                    const card = ms.closest('.t2q-rapor-card');
                    if (card) card.classList.remove('has-open-ms');
                });
            }

            function escapeHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            document.querySelectorAll('.js-t2q-ms').forEach(function (ms) {
                const panel = ms.closest('.js-t2q-channel-panel');
                const hiddenInput = ms.querySelector('.js-t2q-ms-input');
                const trigger = ms.querySelector('.js-t2q-ms-trigger');
                const tagsContainer = ms.querySelector('.js-t2q-ms-tags');
                const searchField = ms.querySelector('.js-t2q-ms-search');
                const listEl = ms.querySelector('.js-t2q-ms-list');
                const countEl = ms.querySelector('.js-t2q-ms-count');
                const clearBtn = ms.querySelector('.js-t2q-ms-clear');
                const doneBtn = ms.querySelector('.js-t2q-ms-done');
                const placeholderText = ms.getAttribute('data-placeholder') || 'Pilih satu atau lebih aspek...';
                const isDisabled = !hiddenInput || hiddenInput.disabled;

                let options = [];
                let selected = [];
                try {
                    options = JSON.parse(ms.getAttribute('data-options') || '[]') || [];
                    selected = JSON.parse(ms.getAttribute('data-selected') || '[]') || [];
                } catch (e) {
                    options = [];
                    selected = [];
                }

                function hasSelected(item) {
                    const needle = String(item || '').toLowerCase();
                    return selected.some(function (s) {
                        return String(s).toLowerCase() === needle;
                    });
                }

                function syncHiddenAndCount() {
                    const joined = formatIndonesianList(selected);
                    if (hiddenInput) {
                        hiddenInput.value = joined;
                    }
                    if (countEl) {
                        countEl.textContent = selected.length + ' dipilih';
                    }
                }

                function renderTags() {
                    if (!tagsContainer) return;
                    if (selected.length === 0) {
                        tagsContainer.innerHTML = '<span class="t2q-ms-placeholder">' + escapeHtml(placeholderText) + '</span>';
                        return;
                    }

                    tagsContainer.innerHTML = selected.map(function (item) {
                        const removeBtn = isDisabled
                            ? ''
                            : '<span class="t2q-ms-tag-remove" role="button" tabindex="-1" data-remove-val="' + escapeHtml(item) + '" title="Hapus pilihan">&times;</span>';
                        return '<span class="t2q-ms-tag"><span>' + escapeHtml(item) + '</span>' + removeBtn + '</span>';
                    }).join('');
                }

                function renderOptions(filterQuery) {
                    if (!listEl) return;
                    const qRaw = String(filterQuery || '').replace(/\s+/g, ' ').trim();
                    const q = qRaw.toLowerCase();

                    const filtered = options.filter(function (opt) {
                        return q === '' || String(opt).toLowerCase().includes(q);
                    });

                    let html = '';

                    if (qRaw !== '' && !options.some(function (opt) { return String(opt).toLowerCase() === q; })) {
                        html += '<button type="button" class="t2q-ms-custom-add js-t2q-add-custom" data-custom-val="' + escapeHtml(qRaw) + '">'
                            + '<i class="fa fa-plus-circle mr-1"></i> Tambah "' + escapeHtml(qRaw) + '"'
                            + '</button>';
                    }

                    if (filtered.length === 0 && html === '') {
                        html = '<div class="text-center text-muted small py-2">Tidak ada opsi yang cocok.</div>';
                    } else {
                        html += filtered.map(function (opt) {
                            const active = hasSelected(opt);
                            return '<button type="button" class="t2q-ms-option ' + (active ? 'is-selected' : '') + '" data-opt-val="' + escapeHtml(opt) + '">'
                                + '<span class="t2q-ms-check">' + (active ? '<i class="fa fa-check"></i>' : '') + '</span>'
                                + '<span>' + escapeHtml(opt) + '</span>'
                                + '</button>';
                        }).join('');
                    }

                    listEl.innerHTML = html;
                }

                function toggleItem(value) {
                    if (isDisabled) return;
                    const cleanVal = String(value || '').replace(/\s+/g, ' ').replace(/[\.\s]+$/g, '').trim();
                    if (!cleanVal) return;

                    if (hasSelected(cleanVal)) {
                        const needle = cleanVal.toLowerCase();
                        selected = selected.filter(function (s) {
                            return String(s).toLowerCase() !== needle;
                        });
                    } else {
                        const candidate = formatIndonesianList(selected.concat([cleanVal]));
                        if (candidate.length > 240) {
                            if (window.toastr) {
                                window.toastr.warning('Panjang maksimal aspek pengamatan tercapai (240 karakter).');
                            }
                            return;
                        }
                        if (!options.some(function (o) { return String(o).toLowerCase() === cleanVal.toLowerCase(); })) {
                            options.unshift(cleanVal);
                        }
                        selected.push(cleanVal);
                    }

                    syncHiddenAndCount();
                    renderTags();
                    renderOptions(searchField ? searchField.value : '');
                    if (panel) {
                        updatePanelDescription(panel);
                    }
                }

                syncHiddenAndCount();
                renderTags();
                renderOptions('');

                if (trigger && !isDisabled) {
                    trigger.addEventListener('click', function (e) {
                        const removeTarget = e.target.closest('.t2q-ms-tag-remove');
                        if (removeTarget) {
                            e.stopPropagation();
                            toggleItem(removeTarget.getAttribute('data-remove-val') || '');
                            return;
                        }

                        e.stopPropagation();
                        const willOpen = !ms.classList.contains('is-open');
                        closeAllDropdowns(ms);

                        if (willOpen) {
                            const rect = trigger.getBoundingClientRect();
                            const spaceBelow = window.innerHeight - rect.bottom;
                            ms.classList.toggle('is-open-up', spaceBelow < 270 && rect.top > spaceBelow);
                            ms.classList.add('is-open');
                            const card = ms.closest('.t2q-rapor-card');
                            if (card) card.classList.add('has-open-ms');
                            trigger.setAttribute('aria-expanded', 'true');
                            if (searchField) {
                                searchField.value = '';
                                renderOptions('');
                                setTimeout(function () {
                                    searchField.focus();
                                }, 10);
                            }
                        } else {
                            ms.classList.remove('is-open', 'is-open-up');
                            const card = ms.closest('.t2q-rapor-card');
                            if (card) card.classList.remove('has-open-ms');
                            trigger.setAttribute('aria-expanded', 'false');
                        }
                    });
                }

                if (searchField && !isDisabled) {
                    searchField.addEventListener('input', function () {
                        renderOptions(this.value);
                    });

                    searchField.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            const customBtn = listEl ? listEl.querySelector('.js-t2q-add-custom') : null;
                            if (customBtn) {
                                toggleItem(customBtn.getAttribute('data-custom-val') || '');
                                searchField.value = '';
                                renderOptions('');
                            } else {
                                const firstOpt = listEl ? listEl.querySelector('.t2q-ms-option') : null;
                                if (firstOpt) {
                                    toggleItem(firstOpt.getAttribute('data-opt-val') || '');
                                }
                            }
                        } else if (e.key === 'Escape') {
                            closeAllDropdowns(null);
                        }
                    });
                }

                if (listEl && !isDisabled) {
                    listEl.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const customBtn = e.target.closest('.js-t2q-add-custom');
                        if (customBtn) {
                            toggleItem(customBtn.getAttribute('data-custom-val') || '');
                            if (searchField) {
                                searchField.value = '';
                                renderOptions('');
                                searchField.focus();
                            }
                            return;
                        }

                        const optBtn = e.target.closest('.t2q-ms-option');
                        if (optBtn) {
                            toggleItem(optBtn.getAttribute('data-opt-val') || '');
                        }
                    });
                }

                if (clearBtn && !isDisabled) {
                    clearBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        selected = [];
                        syncHiddenAndCount();
                        renderTags();
                        renderOptions(searchField ? searchField.value : '');
                        if (panel) {
                            updatePanelDescription(panel);
                        }
                    });
                }

                if (doneBtn) {
                    doneBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        closeAllDropdowns(null);
                    });
                }
            });

            document.querySelectorAll('.js-t2q-channel-panel').forEach(function (panel) {
                const resetBtn = panel.querySelector('.js-t2q-reset-desc');
                if (resetBtn) {
                    resetBtn.addEventListener('click', function () {
                        updatePanelDescription(panel);
                    });
                }
            });

            document.addEventListener('click', function (e) {
                if (!e.target.closest('.js-t2q-ms')) {
                    closeAllDropdowns(null);
                }
            });

            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape') {
                    closeAllDropdowns(null);
                }
            });
        })();
    </script>
@endpush
