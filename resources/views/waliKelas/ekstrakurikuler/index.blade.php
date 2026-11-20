@extends('layouts.appui')

@section('title')
    <title>Penilaian Ekstrakurikuler - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @pushOnce('css')
        <style>
            .ekskul-nilai-input {
                width: 82px;
                height: 36px;
                border-radius: 10px;
                border: 1.5px solid #cbd5e1;
                background: #f8fafc;
                font-size: 0.92rem;
                font-weight: 800;
                color: #0f172a;
                text-align: center;
                font-variant-numeric: tabular-nums;
                transition: all 0.15s ease;
            }

            .ekskul-nilai-input:focus {
                outline: 0;
                background: #ffffff;
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.15);
            }

            .ekskul-save-bar {
                background: #f8fafc;
                border-top: 1px solid #e2e8f0;
                padding: 0.9rem 1.25rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }
        </style>
    @endPushOnce

    @php
        $heroDate = \Carbon\Carbon::now();
        $displayName = $karyawanProfile?->nama_gelar ?? Auth::user()?->name ?? 'Pembina Ekstrakurikuler';
        $assignedEkskuls = $assignedEkskuls ?? collect();
        $members = $members ?? collect();
        $totalMembers = (int) ($stats['total'] ?? $members->count());
        $dinilaiCount = (int) ($stats['dinilai'] ?? 0);
        $belumDinilaiCount = (int) ($stats['belum_dinilai'] ?? max(0, $totalMembers - $dinilaiCount));
        $progressPercent = $totalMembers > 0 ? round(($dinilaiCount / $totalMembers) * 100) : 0;
        $tuntasCount = $members->filter(static fn ($m): bool => in_array($m->predikat, ['SAB', 'BSH', 'SB'], true))->count();
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
                            <i class="fa fa-medal mr-1"></i>
                            PEMBIMBING EKSTRAKURIKULER
                        </span>
                        <span class="small" style="color: rgba(255, 255, 255, 0.72);">
                            <i class="fa fa-calendar-alt mr-1"></i>
                            {{ $heroDate->copy()->locale('id')->translatedFormat('l, d F Y') }}
                        </span>
                    </div>

                    <h3 class="font-weight-bold text-white mb-2">
                        {{ $isEligible && $selectedEkskul ? 'Penilaian Ekstrakurikuler '.$selectedEkskul->nama_ekstrakurikuler : 'Penilaian Ekstrakurikuler' }}
                    </h3>

                    @if ($isEligible && $selectedEkskul)
                        <p class="mb-3" style="color: rgba(255, 255, 255, 0.78); max-width: 680px; font-size: 0.86rem; line-height: 1.5;">
                            Cukup input <strong>nilai angka (0–100)</strong> untuk peserta <strong>{{ $selectedEkskul->nama_ekstrakurikuler }}</strong>.
                            Predikat rapor (<strong>SAB, BSH, SB, MB</strong>) dan deskripsi capaian rapor akan terbentuk otomatis.
                        </p>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @if ($activeTahunAjaran)
                                <span class="badge px-3 py-2"
                                    style="background: rgba(255, 255, 255, 0.14); border: 1px solid rgba(255, 255, 255, 0.22); color: #ffffff; font-size: 0.74rem;">
                                    <i class="fa fa-graduation-cap text-warning mr-1"></i>
                                    {{ $activeTahunAjaran->full_label }}
                                </span>
                            @endif
                            <span class="badge px-3 py-2"
                                style="background: rgba(16, 185, 129, 0.22); border: 1px solid rgba(52, 211, 153, 0.35); color: #ecfdf5; font-size: 0.74rem;">
                                <i class="fa fa-school mr-1"></i>
                                Unit {{ strtoupper((string) $selectedEkskul->unit) }}
                            </span>
                        </div>
                    @else
                        <p class="mb-2" style="color: rgba(255, 255, 255, 0.76); max-width: 640px; font-size: 0.86rem;">
                            Halo {{ $displayName }}, halaman ini digunakan oleh Pembina / Pelatih untuk menginput nilai dan materi rapor ekstrakurikuler yang dibimbing pada Tahun Ajaran aktif.
                        </p>
                        @if ($activeTahunAjaran)
                            <span class="badge px-3 py-2"
                                style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2); color: #ffffff; font-size: 0.74rem;">
                                <i class="fa fa-graduation-cap text-warning mr-1"></i>
                                Tahun Ajaran Aktif: {{ $activeTahunAjaran->full_label }}
                            </span>
                        @endif
                    @endif
                </div>

                @if ($isEligible && $selectedEkskul)
                    <div class="col-lg-4">
                        <div class="wk-team-card">
                            <div class="text-uppercase font-weight-bold mb-2"
                                style="font-size: 0.68rem; letter-spacing: 0.05em; color: #fb923c;">
                                Tim Pembina Ekstrakurikuler
                            </div>
                            <div class="mb-2">
                                <div style="font-size: 0.68rem; color: rgba(255, 255, 255, 0.62);">Pembina / Pelatih</div>
                                <div class="font-weight-bold text-white" style="font-size: 0.86rem;">
                                    {{ $selectedEkskul->pembina?->nama_gelar ?? $displayName }}
                                </div>
                            </div>
                            <div class="pt-2" style="border-top: 1px solid rgba(255, 255, 255, 0.12);">
                                <div class="d-flex justify-content-between align-items-center mb-1" style="font-size: 0.72rem;">
                                    <span style="color: rgba(255, 255, 255, 0.68);">Progres Penilaian</span>
                                    <span class="font-weight-bold" style="color: #34d399;">{{ $dinilaiCount }} / {{ $totalMembers }} ({{ $progressPercent }}%)</span>
                                </div>
                                <div class="wk-progress-track" style="background: rgba(255, 255, 255, 0.14);">
                                    <div class="wk-progress-fill" style="width: {{ $progressPercent }}%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if (! $isEligible)
            @if (($guardReason ?? null) === 'no_assigned_ekskul')
                <div class="wk-empty-guard">
                    <div class="mx-auto mb-3 d-flex align-items-center justify-content-center"
                        style="width: 58px; height: 58px; border-radius: 16px; background: #fff7ed; color: #ea580c; font-size: 1.5rem;">
                        <i class="fa fa-medal"></i>
                    </div>
                    <h5 class="font-weight-bold text-dark mb-2">
                        Tidak Ada Ekstrakurikuler yang Anda Dampingi
                    </h5>
                    <p class="small text-muted mb-3">
                        Pada Tahun Ajaran aktif (<strong>{{ $activeTahunAjaran?->full_label }}</strong>), NIPY Anda (<code>{{ $nipy ?: '-' }}</code>) belum tercatat sebagai <strong>Pembina / Pelatih Ekstrakurikuler</strong> mana pun.
                    </p>
                    <div class="small text-muted px-3 py-2 rounded d-inline-block mb-3"
                        style="background: #f8fafc; border: 1px solid #e2e8f0;">
                        <i class="fa fa-info-circle text-warning mr-1"></i>
                        Hubungi Admin Akademik apabila Anda bertugas membimbing ekstrakurikuler pada semester ini.
                    </div>
                    <div>
                        <a href="{{ route('wali-kelas.dashboard') }}" class="btn btn-sm text-white font-weight-semibold px-3"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 9px;">
                            <i class="fa fa-arrow-left mr-1"></i> Kembali ke Dashboard
                        </a>
                    </div>
                </div>
            @else
                @include('waliKelas.partials._ineligible_guard')
            @endif
        @else
            <!-- SWITCHER EKSTRAKURIKULER JIKA MEMBIMBING > 1 -->
            @if ($assignedEkskuls->count() > 1)
                <div class="wk-class-switcher">
                    <span class="small font-weight-bold text-muted mr-1">
                        <i class="fa fa-layer-group mr-1"></i> Ekstrakurikuler Diampu:
                    </span>
                    @foreach ($assignedEkskuls as $ekskulOption)
                        @php
                            $isActiveOption = (int) $ekskulOption->id === (int) $selectedEkskul->id;
                        @endphp
                        <a href="{{ route('wali-kelas.ekstrakurikuler.index', ['ekstrakurikuler_id' => $ekskulOption->sqid]) }}"
                            class="wk-class-pill {{ $isActiveOption ? 'is-active' : '' }}">
                            <i class="fa fa-medal"></i>
                            <span>{{ $ekskulOption->nama_ekstrakurikuler }} ({{ strtoupper((string) $ekskulOption->unit) }})</span>
                            <span class="badge badge-pill {{ $isActiveOption ? 'badge-light text-dark' : 'badge-secondary' }}">
                                {{ $ekskulOption->anggota_ekstrakurikuler_count ?? 0 }}
                            </span>
                        </a>
                    @endforeach
                </div>
            @endif

            <!-- KPI SUMMARY STRIP -->
            <div class="wk-kpi-grid">
                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #2563eb;"></span> Total Peserta
                        </div>
                        <div class="wk-kpi-value">{{ $totalMembers }}</div>
                        <div class="wk-kpi-sub">
                            Siswa terdaftar aktif
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-users"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #10b981;"></span> Sudah Dinilai
                        </div>
                        <div class="wk-kpi-value" style="color: #059669;">{{ $dinilaiCount }}</div>
                        <div class="wk-kpi-sub">
                            {{ $progressPercent }}% nilai masuk rapor
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #ecfdf5; color: #10b981;">
                        <i class="fa fa-check-circle"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #f59e0b;"></span> Belum Dinilai
                        </div>
                        <div class="wk-kpi-value" style="color: #d97706;">{{ $belumDinilaiCount }}</div>
                        <div class="wk-kpi-sub">
                            {{ $belumDinilaiCount === 0 ? 'Semua peserta sudah dinilai' : 'Menunggu input nilai' }}
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fffbeb; color: #f59e0b;">
                        <i class="fa fa-hourglass-half"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #ea580c;"></span> Mencapai Harapan
                        </div>
                        <div class="wk-kpi-value" style="color: #c2410c;">{{ $tuntasCount }} <span class="text-muted" style="font-size: 0.85rem; font-weight: 600;">/ {{ $totalMembers }}</span></div>
                        <div class="wk-kpi-sub">
                            Predikat SAB, BSH, atau SB
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-award"></i>
                    </span>
                </div>
            </div>

            @if (session('success'))
                <div class="alert alert-success shadow-sm border-0 mb-3" style="border-radius: 12px;">
                    <i class="fa fa-check-circle mr-1"></i> {{ session('success') }}
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger shadow-sm border-0 mb-3" style="border-radius: 12px;">
                    <div class="font-weight-bold mb-1">
                        <i class="fa fa-exclamation-triangle mr-1"></i> Periksa kembali input penilaian:
                    </div>
                    <ul class="mb-0 pl-3 small">
                        @foreach ($errors->all() as $err)
                            <li>{{ $err }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @include('waliKelas.ekstrakurikuler._grading_card', [
                'ekstrakurikuler' => $selectedEkskul,
                'members' => $members,
                'predikatRanges' => $predikatRanges,
                'formAction' => route('wali-kelas.ekstrakurikuler.penilaian.store', $selectedEkskul),
                'fromAdminShow' => false,
            ])
        @endif
    </div>
@endsection
