@extends('layouts.appui')

@section('title')
    <title>Rekap Nilai &amp; Deskripsi Rapor TP (SD)</title>
@endsection

@section('content')
    @push('css')
        <style>
            .rk-filter-bar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 0.9rem 1.15rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            }

            .rk-filter-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.75rem;
                align-items: end;
            }

            @media (min-width: 768px) {
                .rk-filter-grid {
                    grid-template-columns: 1.2fr 1fr 1.2fr auto;
                }
            }

            .rk-periode-tabs,
            .rk-segmented {
                display: inline-flex;
                align-items: center;
                padding: 0.22rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 11px;
                gap: 0.2rem;
            }

            .rk-periode-tab,
            .rk-seg-btn {
                padding: 0.38rem 0.85rem;
                border-radius: 8px;
                border: 0;
                background: transparent;
                font-size: 0.78rem;
                font-weight: 700;
                color: #475569;
                text-decoration: none !important;
                cursor: pointer;
                transition: all 0.15s ease;
                white-space: nowrap;
            }

            .rk-periode-tab:hover,
            .rk-seg-btn:hover {
                color: #0f172a;
            }

            .rk-periode-tab.is-active,
            .rk-seg-btn.is-active {
                background: #ffffff;
                color: #c2410c;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.09);
            }

            /* Strip KPI Minimalis */
            .rk-kpi-grid {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.75rem;
                margin-bottom: 1rem;
            }

            @media (min-width: 992px) {
                .rk-kpi-grid {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .rk-kpi-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 0.85rem 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
            }

            .rk-kpi-label {
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
            }

            .rk-kpi-dot {
                width: 7px;
                height: 7px;
                border-radius: 999px;
                display: inline-block;
            }

            .rk-kpi-value {
                font-size: 1.35rem;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.2;
                margin-top: 0.2rem;
                font-variant-numeric: tabular-nums;
            }

            .rk-kpi-sub {
                font-size: 0.72rem;
                color: #64748b;
                margin-top: 0.15rem;
            }

            .rk-kpi-icon {
                width: 38px;
                height: 38px;
                border-radius: 10px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.95rem;
                flex-shrink: 0;
            }

            /* Card & Toolbar Tabel Modern */
            .rk-surface {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
                overflow: hidden;
            }

            .rk-table-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.85rem 1.15rem;
                border-bottom: 1px solid #f1f5f9;
                background: #ffffff;
            }

            .rk-search-wrap {
                position: relative;
                min-width: 220px;
                flex: 1 1 220px;
                max-width: 300px;
            }

            .rk-search-wrap i {
                position: absolute;
                left: 0.75rem;
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8;
                font-size: 0.78rem;
                pointer-events: none;
            }

            .rk-search-input {
                width: 100%;
                padding: 0.38rem 0.75rem 0.38rem 2rem;
                font-size: 0.8rem;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                background: #f8fafc;
                color: #0f172a;
                transition: all 0.15s ease;
            }

            .rk-search-input:focus {
                outline: none;
                background: #ffffff;
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
            }

            .rk-pill-filter {
                border: 1px solid #e2e8f0;
                background: #ffffff;
                color: #475569;
                border-radius: 999px;
                padding: 0.28rem 0.7rem;
                font-size: 0.74rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.12s ease;
            }

            .rk-pill-filter:hover {
                border-color: #cbd5e1;
                color: #0f172a;
            }

            .rk-pill-filter.is-active {
                background: #0f172a;
                border-color: #0f172a;
                color: #ffffff;
            }

            /* Tabel Minimalis & Sticky Identity */
            .rk-table-scroll {
                overflow-x: auto;
                max-width: 100%;
            }

            .rk-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                font-size: 0.8rem;
                font-variant-numeric: tabular-nums;
                margin: 0;
            }

            .rk-table thead th {
                background: #f8fafc;
                color: #475569;
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.03em;
                padding: 0.72rem 0.75rem;
                border-bottom: 1px solid #e2e8f0;
                vertical-align: middle;
                white-space: nowrap;
            }

            .rk-table tbody td {
                padding: 0.72rem 0.75rem;
                border-bottom: 1px solid #f1f5f9;
                vertical-align: middle;
                background: #ffffff;
                transition: background-color 0.12s ease;
            }

            .rk-table tbody tr:last-child td {
                border-bottom: 0;
            }

            .rk-table tbody tr:hover td {
                background: #f8fafc;
            }

            .rk-sticky-1 {
                position: sticky;
                left: 0;
                z-index: 2;
                width: 44px;
                min-width: 44px;
                text-align: center;
            }

            .rk-sticky-2 {
                position: sticky;
                left: 44px;
                z-index: 2;
                min-width: 195px;
                max-width: 230px;
                box-shadow: 2px 0 6px -2px rgba(15, 23, 42, 0.06);
            }

            .rk-table thead .rk-sticky-1,
            .rk-table thead .rk-sticky-2 {
                z-index: 4;
                background: #f8fafc;
            }

            .rk-table.is-summary-only .rk-detail-col {
                display: none;
            }

            .rk-table.is-hide-mid .rk-mid-col {
                display: none;
            }

            .rk-score-num {
                font-weight: 700;
                color: #1e293b;
                font-size: 0.84rem;
            }

            .rk-score-rapor {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 38px;
                padding: 0.22rem 0.55rem;
                border-radius: 8px;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #c2410c;
                font-weight: 800;
                font-size: 0.86rem;
            }

            .rk-score-rapor.is-fail {
                background: #fef2f2;
                border-color: #fecaca;
                color: #b91c1c;
            }

            .rk-source-sub {
                font-size: 0.63rem;
                font-weight: 600;
                color: #94a3b8;
                margin-top: 0.1rem;
            }

            .rk-status-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.22rem 0.6rem;
                border-radius: 999px;
                font-size: 0.72rem;
                font-weight: 700;
                white-space: nowrap;
            }

            .rk-status-pill.is-pass {
                background: #ecfdf5;
                color: #047857;
                border: 1px solid #a7f3d0;
            }

            .rk-status-pill.is-fail {
                background: #fef2f2;
                color: #b91c1c;
                border: 1px solid #fecaca;
            }

            .rk-status-pill.is-empty {
                background: #f1f5f9;
                color: #64748b;
                border: 1px solid #e2e8f0;
            }

            /* Deskripsi Capaian Rapor Rapi (2-line clamp + expand + edit) */
            .rk-desc-cell {
                min-width: 280px;
                max-width: 380px;
            }

            .rk-desc-box {
                display: flex;
                flex-direction: column;
                gap: 0.35rem;
            }

            .rk-desc-text {
                font-size: 0.775rem;
                line-height: 1.48;
                color: #334155;
                word-break: break-word;
            }

            .rk-desc-text.is-clamped {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .rk-desc-actions {
                display: flex;
                align-items: center;
                gap: 0.5rem;
                flex-wrap: wrap;
            }

            .rk-desc-toggle-btn {
                border: 0;
                background: transparent;
                padding: 0;
                font-size: 0.71rem;
                font-weight: 700;
                color: #ea580c;
                cursor: pointer;
            }

            .rk-desc-toggle-btn:hover {
                text-decoration: underline;
            }

            .rk-desc-edit-btn {
                border: 1px solid #e2e8f0;
                background: #ffffff;
                color: #475569;
                border-radius: 7px;
                padding: 0.14rem 0.5rem;
                font-size: 0.7rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.12s ease;
            }

            .rk-desc-edit-btn:hover {
                border-color: #ea580c;
                color: #c2410c;
                background: #fff7ed;
            }

            /* Tooltip Instan Header TP & Sumatif */
            .rk-tooltip-popover {
                position: fixed;
                z-index: 2100;
                max-width: 320px;
                padding: 0.55rem 0.75rem;
                border-radius: 10px;
                background: #0f172a;
                color: #f8fafc;
                box-shadow: 0 16px 30px -10px rgba(15, 23, 42, 0.45);
                border: 1px solid rgba(251, 146, 60, 0.4);
                font-size: 0.75rem;
                line-height: 1.45;
                pointer-events: none;
                display: none;
            }

            .rk-tooltip-popover.is-visible {
                display: block;
            }

            .rk-modal-overlay {
                position: fixed;
                inset: 0;
                z-index: 1050;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                background: rgba(15, 23, 42, 0.55);
                backdrop-filter: blur(2px);
            }

            .rk-modal-overlay.is-open {
                display: flex;
            }

            .rk-modal-card {
                width: 100%;
                max-width: 580px;
                background: #ffffff;
                border-radius: 16px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.35);
                overflow: hidden;
            }
        </style>
    @endpush

    @php
        $tpRoutePrefix = $tpRoutePrefix ?? 'penilaian-tp';
        $rekapRoutePrefix = $rekapRoutePrefix ?? 'penilaian-rekap';
        $isWaliKelasScope = $isWaliKelasScope ?? false;
        $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $isMid = $periode === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $bobotTp = (int) ($bobotTp ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_TP);
        $bobotSumatif = (int) ($bobotSumatif ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_SUMATIF);
        $bobotUjian = (int) ($bobotUjian ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_UJIAN);
        $selectedTaSemester = $selectedTahunAjaran?->semester
            ?? ($tahunAjaranOptions ?? collect())->firstWhere('id', $selectedTahunAjaranId ?? null)?->semester;
        $ujianLabel = $ujianLabel ?? \App\Models\Admin\Penilaian\PeriodeTp::ujianLabel($periode, $selectedTaSemester);
        $ujianFullLabel = $ujianFullLabel ?? \App\Models\Admin\Penilaian\PeriodeTp::ujianFullLabel($periode, $selectedTaSemester);
        $tpList = $tpList ?? collect();
        $rekapSumatifCols = $rekapSumatifCols ?? collect();
        $students = $students ?? collect();
        $combinedTpSummaries = $combinedTpSummaries ?? [];
        $combinedSumatifSummaries = $combinedSumatifSummaries ?? [];
        $rekapTpLookup = $rekapTpLookup ?? [];
        $rekapMapelLookup = $rekapMapelLookup ?? [];
        $kkmNilai = $kkmNilai ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_KKM;
        $kkmDisplay = rtrim(rtrim(number_format((float) $kkmNilai, 2, '.', ''), '0'), '.');
        $showSumatifSection = $rekapSumatifCols->isNotEmpty() || $bobotSumatif > 0;

        $hiddenSemTpIds = [];
        $midTpIds = [];
        $midSumatifIds = [];

        foreach ($tpList as $tpItem) {
            $hasMidScore = false;
            $hasSemScore = false;
            foreach ($students as $st) {
                $summaryInfo = $combinedTpSummaries[$st->id][$tpItem->id] ?? null;
                $lookupMidVal = $rekapTpLookup["{$st->id}:{$tpItem->id}:".\App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID]?->nilai_akhir_tp ?? null;
                $lookupSemVal = $rekapTpLookup["{$st->id}:{$tpItem->id}:".\App\Models\Admin\Penilaian\PeriodeTp::PERIODE_SEMESTER]?->nilai_akhir_tp ?? null;

                if ($lookupMidVal !== null || (($summaryInfo['nilai'] ?? null) !== null && ($isMid || ($summaryInfo['source'] ?? null) === 'Mid'))) {
                    $hasMidScore = true;
                }
                if ($lookupSemVal !== null || (($summaryInfo['nilai'] ?? null) !== null && ($summaryInfo['source'] ?? null) === 'Semester')) {
                    $hasSemScore = true;
                }
            }

            if ($isMid && $hasSemScore && ! $hasMidScore) {
                $hiddenSemTpIds[$tpItem->id] = true;
            } elseif (! $isMid && $hasMidScore && ! $hasSemScore) {
                $midTpIds[$tpItem->id] = true;
            }
        }

        if (! $isMid) {
            foreach ($rekapSumatifCols as $bab) {
                $isMidBab = $bab->periode === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
                if (! $isMidBab) {
                    foreach ($students as $st) {
                        if (($combinedSumatifSummaries[$st->id][$bab->id]['source'] ?? null) === 'Mid') {
                            $isMidBab = true;
                            break;
                        }
                    }
                }
                if ($isMidBab) {
                    $midSumatifIds[$bab->id] = true;
                }
            }
        }

        $displayTpList = $tpList->reject(static fn ($tpItem): bool => isset($hiddenSemTpIds[$tpItem->id]))->values();
        $hiddenSemTpCount = count($hiddenSemTpIds);
        $midItemsCount = count($midTpIds) + count($midSumatifIds);

        $bobotFormulaParts = array_values(array_filter([
            $bobotTp > 0 ? "{$bobotTp}% TP" : null,
            $bobotSumatif > 0 ? "{$bobotSumatif}% Sumatif" : null,
            $bobotUjian > 0 ? "{$bobotUjian}% {$ujianLabel}" : null,
        ]));
        $formulaBadge = $bobotFormulaParts !== [] ? implode(' + ', $bobotFormulaParts) : '100% TP';

        $scoredCount = 0;
        $tuntasCount = 0;
        $belumTuntasCount = 0;
        $raporSum = 0.0;
        foreach ($students as $st) {
            $rm = $rekapMapelLookup["{$st->id}:{$periode}"] ?? null;
            if ($rm?->nilai_akhir_rapor !== null) {
                $scoredCount++;
                $raporSum += (float) $rm->nilai_akhir_rapor;
            }
            if ($rm?->tuntas_kkm === true) {
                $tuntasCount++;
            } elseif ($rm?->tuntas_kkm === false) {
                $belumTuntasCount++;
            }
        }
        $avgRaporKelas = $scoredCount > 0 ? round($raporSum / $scoredCount, 1) : null;
        $tuntasPct = $scoredCount > 0 ? round(($tuntasCount / $scoredCount) * 100) : 0;

        $contextParams = \App\Support\Sqid::encodeQueryParams(array_filter([
            'tahun_ajaran_id' => $selectedTahunAjaranId ?? null,
            'kelas_id' => $selectedKelas?->sqid ?? null,
            'mata_pelajaran_id' => $selectedMapel?->sqid ?? null,
            'periode' => $periode,
        ], static fn ($v) => $v !== null && $v !== ''));
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-graph2" style="color: white;"></i>
                    </div>
                    <div>
                        Rekap Nilai &amp; Deskripsi Rapor ({{ $isMid ? 'Mid Semester' : 'Semester' }})
                        @if ($isWaliKelasScope)
                            <span class="badge badge-warning text-dark ml-1 align-middle" style="font-size: 0.7rem;">Mapel Diampu</span>
                        @endif
                        <div class="page-title-subheading">
                            Bobot aktif: <strong>{{ $formulaBadge }}</strong> &bull; KKM <strong>{{ $kkmDisplay }}</strong>
                            @if (! $isMid)
                                &bull; Menggabungkan capaian Mid Semester + Semester
                            @endif
                        </div>
                    </div>
                </div>

                <div class="page-title-actions flex-wrap">
                    @if ($isWaliKelasScope)
                        <a href="{{ route('wali-kelas.perkembangan-siswa.index', ['periode' => $periode]) }}"
                            class="btn btn-light shadow-sm"
                            style="border-radius: 10px; font-weight: 600; color: #ea580c; border-color: #fed7aa; background: #fff7ed;">
                            <i class="fa fa-chart-line mr-1"></i> Perkembangan Semua Mapel
                        </a>
                    @elseif ($selectedKelas)
                        <a href="{{ route('penilaian-rapor.index', \App\Support\Sqid::encodeQueryParams(array_filter(['tahun_ajaran_id' => $selectedTahunAjaranId, 'kelas_id' => $selectedKelas->sqid, 'periode' => $periode]))) }}"
                            class="btn btn-light shadow-sm"
                            style="border-radius: 10px; font-weight: 600; color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff;">
                            <i class="fa fa-file-pdf mr-1"></i> Cetak Rapor PDF
                        </a>
                    @endif
                    <a href="{{ route($tpRoutePrefix.'.index', $contextParams) }}" class="btn btn-light shadow-sm"
                        style="border-radius: 10px; font-weight: 600;">
                        <i class="fa fa-th mr-1"></i> Input Nilai
                    </a>

                    @if ($selectedKelas && $selectedMapel && $students->isNotEmpty())
                        <form method="POST" action="{{ route($rekapRoutePrefix.'.deskripsi.regenerate') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="kelas_id" value="{{ $selectedKelas->sqid }}">
                            <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedMapel->sqid }}">
                            <input type="hidden" name="periode" value="{{ $periode }}">
                            <button type="submit" class="btn text-white shadow-sm"
                                title="Susun deskripsi otomatis untuk seluruh TP yang bernilai NA TP berdasarkan kelompok predikat KKM"
                                style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px; font-weight: 600;">
                                <i class="fa fa-sync mr-1"></i> Generate Ulang Deskripsi
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="rk-filter-bar">
            <form method="GET" action="{{ route($rekapRoutePrefix.'.index') }}">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <div class="rk-filter-grid">
                    <div>
                        <label for="rk_ta" class="small font-weight-semibold text-muted mb-1">Tahun Ajaran / Semester</label>
                        <select name="tahun_ajaran_id" id="rk_ta" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach ($tahunAjaranOptions as $ta)
                                <option value="{{ $ta->sqid }}" @selected((int) $selectedTahunAjaranId === (int) $ta->id)>
                                    {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="rk_kelas" class="small font-weight-semibold text-muted mb-1">Kelas (SD)</label>
                        <select name="kelas_id" id="rk_kelas" class="form-control form-control-sm"
                            onchange="@if ($isWaliKelasScope) const mapelEl = document.getElementById('rk_mapel'); if (mapelEl) { mapelEl.disabled = true; } @endif this.form.submit()">
                            @forelse ($kelasOptions as $kls)
                                <option value="{{ $kls->sqid }}" @selected($selectedKelas && (int) $selectedKelas->id === (int) $kls->id)>
                                    {{ $kls->nama_kelas }} (Tingkat {{ $kls->tingkatan_kelas }})
                                </option>
                            @empty
                                <option value="">Belum ada kelas SD</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label for="rk_mapel" class="small font-weight-semibold text-muted mb-1">Mata Pelajaran (SD)</label>
                        <select name="mata_pelajaran_id" id="rk_mapel" class="form-control form-control-sm" onchange="this.form.submit()">
                            @forelse ($mataPelajaranOptions as $mp)
                                <option value="{{ $mp->sqid }}" @selected($selectedMapel && (int) $selectedMapel->id === (int) $mp->id)>
                                    [{{ $mp->singkatan }}] {{ $mp->nama_mata_pelajaran }}
                                </option>
                            @empty
                                <option value="">Belum ada mapel SD</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <div class="small font-weight-semibold text-muted mb-1">Periode Rapor</div>
                        <div class="rk-periode-tabs">
                            <a href="{{ route($rekapRoutePrefix.'.index', array_merge($contextParams, ['periode' => 'mid'])) }}"
                                class="rk-periode-tab {{ $isMid ? 'is-active' : '' }}">
                                Mid Semester
                            </a>
                            <a href="{{ route($rekapRoutePrefix.'.index', array_merge($contextParams, ['periode' => 'semester'])) }}"
                                class="rk-periode-tab {{ ! $isMid ? 'is-active' : '' }}">
                                Semester
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if ($students->isNotEmpty() && ($tpList->isNotEmpty() || $rekapSumatifCols->isNotEmpty()))
            <!-- KPI Ringkas Kelas -->
            <div class="rk-kpi-grid">
                <div class="rk-kpi-card">
                    <div>
                        <div class="rk-kpi-label">
                            <span class="rk-kpi-dot" style="background: #3b82f6;"></span> Total Siswa
                        </div>
                        <div class="rk-kpi-value">{{ $students->count() }}</div>
                        <div class="rk-kpi-sub">{{ $scoredCount }} sudah ada NA Rapor</div>
                    </div>
                    <span class="rk-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-users"></i>
                    </span>
                </div>

                <div class="rk-kpi-card">
                    <div>
                        <div class="rk-kpi-label">
                            <span class="rk-kpi-dot" style="background: #10b981;"></span> Tuntas KKM
                        </div>
                        <div class="rk-kpi-value" style="color: #047857;">{{ $tuntasCount }}</div>
                        <div class="rk-kpi-sub">{{ $tuntasPct }}% mencapai KKM {{ $kkmDisplay }}</div>
                    </div>
                    <span class="rk-kpi-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa fa-check-circle"></i>
                    </span>
                </div>

                <div class="rk-kpi-card">
                    <div>
                        <div class="rk-kpi-label">
                            <span class="rk-kpi-dot" style="background: #f43f5e;"></span> Belum Tuntas
                        </div>
                        <div class="rk-kpi-value" style="color: {{ $belumTuntasCount > 0 ? '#be123c' : '#64748b' }};">
                            {{ $belumTuntasCount }}
                        </div>
                        <div class="rk-kpi-sub">Perlu bimbingan di bawah {{ $kkmDisplay }}</div>
                    </div>
                    <span class="rk-kpi-icon" style="background: #fff1f2; color: #e11d48;">
                        <i class="fa fa-exclamation-circle"></i>
                    </span>
                </div>

                <div class="rk-kpi-card">
                    <div>
                        <div class="rk-kpi-label">
                            <span class="rk-kpi-dot" style="background: #ea580c;"></span> Rata-rata Kelas
                        </div>
                        <div class="rk-kpi-value" style="color: #c2410c;">
                            {{ $avgRaporKelas !== null ? rtrim(rtrim(number_format($avgRaporKelas, 1, '.', ''), '0'), '.') : '-' }}
                        </div>
                        <div class="rk-kpi-sub">
                            {{ $displayTpList->count() }} TP &bull; {{ $rekapSumatifCols->count() }} Sumatif Bab
                            @if ($isMid && $hiddenSemTpCount > 0)
                                &bull; {{ $hiddenSemTpCount }} TP Semester disembunyikan
                            @endif
                        </div>
                    </div>
                    <span class="rk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-chart-line"></i>
                    </span>
                </div>
            </div>
        @endif

        <div class="rk-surface">
            @if ($students->isEmpty() || ($tpList->isEmpty() && $rekapSumatifCols->isEmpty()))
                <div class="text-center py-5 px-3 text-muted">
                    <div class="mb-2"><i class="fa fa-clipboard-list fa-2x" style="color: #cbd5e1;"></i></div>
                    <div class="font-weight-bold text-dark mb-1">Belum ada data rekap untuk filter ini</div>
                    <div class="small">Pastikan kelas memiliki anggota siswa serta minimal 1 Tujuan Pembelajaran atau Sumatif Bab aktif.</div>
                </div>
            @else
                <div class="rk-table-toolbar">
                    <div class="d-flex flex-wrap align-items-center gap-2" style="flex: 1 1 320px;">
                        <div class="rk-search-wrap">
                            <i class="fa fa-search"></i>
                            <input type="search" id="rkSearchInput" class="rk-search-input"
                                placeholder="Cari nama atau NIS siswa...">
                        </div>

                        <div class="d-inline-flex align-items-center gap-1">
                            <button type="button" class="rk-pill-filter is-active js-rk-status-filter" data-filter="all">Semua</button>
                            <button type="button" class="rk-pill-filter js-rk-status-filter" data-filter="1">Tuntas ({{ $tuntasCount }})</button>
                            <button type="button" class="rk-pill-filter js-rk-status-filter" data-filter="0">Belum ({{ $belumTuntasCount }})</button>
                        </div>

                        @if ($isMid && $hiddenSemTpCount > 0)
                            <span class="badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; padding: 0.3rem 0.6rem; font-weight: 600;"
                                title="TP yang sudah bernilai di Semester disembunyikan pada tab Mid Semester, dan ditampilkan kembali pada tab Semester.">
                                Disembunyikan: <strong>{{ $hiddenSemTpCount }} TP Semester</strong>
                            </span>
                        @endif
                    </div>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        @if (! $isMid && $midItemsCount > 0)
                            <button type="button" id="btnToggleMidCols" class="rk-pill-filter" aria-pressed="false"
                                data-mid-count="{{ $midItemsCount }}"
                                title="Sembunyikan atau tampilkan kolom TP & Sumatif Bab dari Mid Semester di tampilan Detail (NA Rapor tetap gabungan Mid + Semester).">
                                <i class="fa fa-eye mr-1"></i> Nilai Mid: Tampil ({{ $midItemsCount }})
                            </button>
                        @endif

                        <button type="button" id="btnToggleAllDesc" class="btn btn-sm btn-light"
                            style="border: 1px solid #e2e8f0; border-radius: 9px; font-size: 0.76rem; font-weight: 600;">
                            <i class="fa fa-expand-alt mr-1 text-muted"></i> Perluas Deskripsi
                        </button>

                        <div class="rk-segmented" role="group" aria-label="Mode tampilan tabel">
                            <button type="button" class="rk-seg-btn is-active js-rk-view-mode" data-mode="summary">
                                Ringkas
                            </button>
                            <button type="button" class="rk-seg-btn js-rk-view-mode" data-mode="detail">
                                Detail TP &amp; Bab
                            </button>
                        </div>
                    </div>
                </div>

                <div class="rk-table-scroll">
                    <table class="rk-table is-summary-only" id="rkRekapTable">
                        <thead>
                            <tr>
                                <th class="rk-sticky-1">No</th>
                                <th class="rk-sticky-2 text-left">Siswa</th>
                                @foreach ($displayTpList as $tp)
                                    @php
                                        $isMidTpCol = isset($midTpIds[$tp->id]);
                                    @endphp
                                    <th class="text-center rk-detail-col {{ $isMidTpCol ? 'rk-mid-col' : '' }} js-rk-col-tip"
                                        data-tip-title="{{ $tp->kode_tp }}"
                                        data-tip-body="{{ $tp->tujuan_pembelajaran }}"
                                        style="min-width: 72px; cursor: help;">
                                        {{ $tp->kode_tp }}
                                        @if ($isMidTpCol)
                                            <span class="d-block" style="font-size: 0.6rem; font-weight: 600; color: #64748b; text-transform: none;">Mid</span>
                                        @endif
                                    </th>
                                @endforeach
                                <th class="text-center" style="min-width: 78px; color: #1e40af;">
                                    Rata TP
                                    @if ($bobotSumatif > 0 || $bobotUjian > 0)
                                        <span class="d-block" style="font-size: 0.62rem; font-weight: 500; text-transform: none;">({{ $bobotTp }}%)</span>
                                    @endif
                                </th>
                                @if ($showSumatifSection)
                                    @foreach ($rekapSumatifCols as $bab)
                                        @php
                                            $isMidBabCol = isset($midSumatifIds[$bab->id]);
                                        @endphp
                                        <th class="text-center rk-detail-col {{ $isMidBabCol ? 'rk-mid-col' : '' }} js-rk-col-tip"
                                            data-tip-title="{{ $bab->nama_bab }}"
                                            data-tip-body="{{ $bab->keterangan ?: 'Sumatif Bab ('.$bab->nama_bab.')' }}"
                                            style="min-width: 78px; color: #0f766e; cursor: help;">
                                            {{ $bab->nama_bab }}
                                            @if ($isMidBabCol)
                                                <span class="d-block" style="font-size: 0.6rem; font-weight: 600; color: #64748b; text-transform: none;">Mid</span>
                                            @endif
                                        </th>
                                    @endforeach
                                    <th class="text-center" style="min-width: 92px; color: #0f766e;">
                                        Rata Sumatif
                                        <span class="d-block" style="font-size: 0.62rem; font-weight: 500; text-transform: none;">
                                            {{ $bobotSumatif > 0 ? "({$bobotSumatif}%)" : '(0%)' }}
                                        </span>
                                    </th>
                                @endif
                                <th class="text-center" title="{{ $ujianFullLabel }}" style="min-width: 76px; color: #3730a3; cursor: help;">
                                    {{ $ujianLabel }}
                                    <span class="d-block" style="font-size: 0.62rem; font-weight: 500; text-transform: none;">
                                        {{ $bobotUjian > 0 ? "({$bobotUjian}%)" : '(0%)' }}
                                    </span>
                                </th>
                                <th class="text-center" style="min-width: 84px; color: #c2410c;">NA Rapor</th>
                                <th class="text-center" style="min-width: 120px;">Predikat &amp; Status</th>
                                <th class="rk-desc-cell text-left">Deskripsi Capaian Rapor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($students->values() as $idx => $student)
                                @php
                                    $studentName = $student->siswa?->nama_lengkap ?? $student->siswa_nis;
                                    $studentTpScores = $combinedTpSummaries[$student->id] ?? [];
                                    $studentSumatifScores = $combinedSumatifSummaries[$student->id] ?? [];
                                    $rekapMapel = $rekapMapelLookup["{$student->id}:{$periode}"] ?? null;
                                    $descText = trim((string) ($rekapMapel?->deskripsi_capaian ?? ''));
                                    $isLongDesc = mb_strlen($descText) > 80;
                                    $tuntasFlag = $rekapMapel?->tuntas_kkm === null ? 'empty' : ($rekapMapel->tuntas_kkm ? '1' : '0');
                                @endphp
                                <tr class="js-rk-student-row"
                                    data-search="{{ mb_strtolower($studentName.' '.$student->siswa_nis) }}"
                                    data-tuntas="{{ $tuntasFlag }}">
                                    <td class="rk-sticky-1 text-muted">{{ $idx + 1 }}</td>
                                    <td class="rk-sticky-2">
                                        <div class="font-weight-semibold text-dark" style="line-height: 1.3;">
                                            {{ $studentName }}
                                        </div>
                                        <div class="text-muted" style="font-size: 0.69rem;">NIS: {{ $student->siswa_nis }}</div>
                                    </td>

                                    @foreach ($displayTpList as $tp)
                                        @php
                                            $info = $studentTpScores[$tp->id] ?? ['included' => true, 'nilai' => null, 'source' => null];
                                            $isMidTpCol = isset($midTpIds[$tp->id]);
                                        @endphp
                                        <td class="text-center rk-detail-col {{ $isMidTpCol ? 'rk-mid-col' : '' }}" style="{{ ! $info['included'] ? 'color: #94a3b8;' : '' }}">
                                            @if ($info['nilai'] !== null)
                                                <span class="rk-score-num" style="{{ ! $info['included'] ? 'color: #94a3b8; font-weight: 600;' : '' }}">
                                                    {{ (int) round((float) $info['nilai']) }}
                                                </span>
                                                @if ($info['source'])
                                                    <div class="rk-source-sub">
                                                        {{ $info['included'] ? $info['source'] : 'Non-Rapor' }}
                                                    </div>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endforeach

                                    <td class="text-center">
                                        @if ($rekapMapel?->rata_rata_tp !== null)
                                            <span class="rk-score-num" style="color: #1e40af;">
                                                {{ (int) round((float) $rekapMapel->rata_rata_tp) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    @if ($showSumatifSection)
                                        @foreach ($rekapSumatifCols as $bab)
                                            @php
                                                $sumInfo = $studentSumatifScores[$bab->id] ?? ['nilai' => null, 'source' => null];
                                                $isMidBabCol = isset($midSumatifIds[$bab->id]);
                                            @endphp
                                            <td class="text-center rk-detail-col {{ $isMidBabCol ? 'rk-mid-col' : '' }}">
                                                @if ($sumInfo['nilai'] !== null)
                                                    <span class="rk-score-num" style="color: #0f766e;">
                                                        {{ rtrim(rtrim(number_format((float) $sumInfo['nilai'], 2, '.', ''), '0'), '.') }}
                                                    </span>
                                                    @if (! $isMid && $sumInfo['source'])
                                                        <div class="rk-source-sub">{{ $sumInfo['source'] }}</div>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center">
                                            @if ($rekapMapel?->rata_rata_sumatif !== null)
                                                <span class="rk-score-num" style="color: #0f766e;">
                                                    {{ (int) round((float) $rekapMapel->rata_rata_sumatif) }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    @endif

                                    <td class="text-center">
                                        @if ($rekapMapel?->nilai_ujian !== null)
                                            <span class="rk-score-num" style="color: #4338ca;">
                                                {{ rtrim(rtrim(number_format((float) $rekapMapel->nilai_ujian, 2, '.', ''), '0'), '.') }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if ($rekapMapel?->nilai_akhir_rapor !== null)
                                            <span class="rk-score-rapor {{ $rekapMapel->tuntas_kkm === false ? 'is-fail' : '' }}">
                                                {{ (int) round((float) $rekapMapel->nilai_akhir_rapor) }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>

                                    <td class="text-center">
                                        @if ($rekapMapel?->predikat_rapor)
                                            <span class="rk-status-pill {{ $rekapMapel->tuntas_kkm === false ? 'is-fail' : 'is-pass' }}">
                                                {{ $rekapMapel->predikat_rapor }}
                                                &bull;
                                                {{ $rekapMapel->tuntas_kkm ? 'Tuntas' : 'Belum' }}
                                            </span>
                                            @if ($rekapMapel->keterangan_predikat)
                                                <div class="rk-source-sub mt-1" style="color: #64748b;">
                                                    {{ $rekapMapel->keterangan_predikat }}
                                                </div>
                                            @endif
                                        @else
                                            <span class="rk-status-pill is-empty">-</span>
                                        @endif
                                    </td>

                                    <td class="rk-desc-cell">
                                        <div class="rk-desc-box">
                                            @if ($descText !== '')
                                                <div class="rk-desc-text is-clamped js-rk-desc-text" title="{{ $descText }}">
                                                    {{ $descText }}
                                                </div>
                                            @else
                                                <div class="small text-muted font-italic">Belum ada deskripsi.</div>
                                            @endif

                                            <div class="rk-desc-actions">
                                                @if ($isLongDesc)
                                                    <button type="button" class="rk-desc-toggle-btn js-toggle-desc-row">
                                                        Selengkapnya
                                                    </button>
                                                @endif
                                                @if ($rekapMapel !== null)
                                                    <button type="button"
                                                        class="rk-desc-edit-btn btn-edit-deskripsi"
                                                        data-action="{{ route($rekapRoutePrefix.'.deskripsi.update', $rekapMapel) }}"
                                                        data-student="{{ $studentName }}"
                                                        data-rapor="{{ $rekapMapel->nilai_akhir_rapor !== null ? (int) round((float) $rekapMapel->nilai_akhir_rapor) : '-' }}"
                                                        data-predikat="{{ $rekapMapel->predikat_rapor ?? '-' }}"
                                                        data-deskripsi="{{ $descText }}"
                                                        title="Baca penuh atau sunting deskripsi">
                                                        <i class="fa fa-pen mr-1"></i>{{ $descText !== '' ? 'Edit' : 'Isi' }}
                                                    </button>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            <tr id="rkEmptyFilterRow" class="d-none">
                                <td colspan="50" class="text-center py-4 text-muted small">
                                    Tidak ada siswa yang sesuai dengan pencarian atau filter status.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    <div id="rkTooltip" class="rk-tooltip-popover" role="tooltip" aria-hidden="true"></div>

    <div id="editDeskripsiModal" class="rk-modal-overlay" role="dialog" aria-modal="true">
        <div class="rk-modal-card">
            <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom: 1px solid #e2e8f0;">
                <div>
                    <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">
                        Deskripsi Rapor — <span id="descModalStudent">Siswa</span>
                    </h5>
                    <div class="small text-muted mt-1" id="descModalMeta"></div>
                </div>
                <button type="button" class="btn btn-sm btn-light" id="closeDescModal">&times;</button>
            </div>
            <form method="POST" id="editDeskripsiForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="kelas_id" value="{{ $selectedKelas?->sqid ?? '' }}">
                <div class="p-4">
                    <div class="form-group mb-0">
                        <div class="d-flex align-items-center justify-content-between mb-1">
                            <label for="descModalTextarea" class="small font-weight-bold mb-0">Deskripsi Capaian Kompetensi</label>
                            <span id="descModalCharCount" class="small text-muted">0 / 2000 karakter</span>
                        </div>
                        <textarea name="deskripsi_capaian" id="descModalTextarea" rows="6" maxlength="2000"
                            class="form-control" style="font-size: 0.85rem; line-height: 1.55; border-radius: 10px;"></textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-light btn-sm" id="cancelDescModal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white" style="background: #ea580c; border-radius: 8px; font-weight: 600;">Simpan Deskripsi</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('editDeskripsiModal');
            const form = document.getElementById('editDeskripsiForm');
            const studentSpan = document.getElementById('descModalStudent');
            const metaSpan = document.getElementById('descModalMeta');
            const textarea = document.getElementById('descModalTextarea');
            const charCount = document.getElementById('descModalCharCount');

            const syncCharCount = function () {
                if (charCount && textarea) {
                    charCount.textContent = textarea.value.length + ' / 2000 karakter';
                }
            };

            if (textarea) {
                textarea.addEventListener('input', syncCharCount);
            }

            document.querySelectorAll('.btn-edit-deskripsi').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    form.action = btn.dataset.action;
                    studentSpan.textContent = btn.dataset.student;
                    if (metaSpan) {
                        metaSpan.textContent = 'NA Rapor: ' + (btn.dataset.rapor || '-') + '  •  Predikat: ' + (btn.dataset.predikat || '-');
                    }
                    textarea.value = btn.dataset.deskripsi || '';
                    syncCharCount();
                    modal.classList.add('is-open');
                    setTimeout(function () { textarea.focus(); }, 60);
                });
            });

            ['closeDescModal', 'cancelDescModal'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.addEventListener('click', function () { modal.classList.remove('is-open'); });
            });

            if (modal) {
                modal.addEventListener('click', function (e) {
                    if (e.target === modal) modal.classList.remove('is-open');
                });
            }

            // Toggle baca selengkapnya per baris & perluas semua deskripsi
            document.querySelectorAll('.js-toggle-desc-row').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const box = btn.closest('.rk-desc-box');
                    const textEl = box ? box.querySelector('.js-rk-desc-text') : null;
                    if (!textEl) return;
                    const expanded = !textEl.classList.toggle('is-clamped');
                    btn.textContent = expanded ? 'Ringkas' : 'Selengkapnya';
                });
            });

            const toggleAllDescBtn = document.getElementById('btnToggleAllDesc');
            let allDescExpanded = false;
            if (toggleAllDescBtn) {
                toggleAllDescBtn.addEventListener('click', function () {
                    allDescExpanded = !allDescExpanded;
                    document.querySelectorAll('.js-rk-desc-text').forEach(function (el) {
                        el.classList.toggle('is-clamped', !allDescExpanded);
                    });
                    document.querySelectorAll('.js-toggle-desc-row').forEach(function (btn) {
                        btn.textContent = allDescExpanded ? 'Ringkas' : 'Selengkapnya';
                    });
                    toggleAllDescBtn.innerHTML = allDescExpanded
                        ? '<i class="fa fa-compress-alt mr-1 text-muted"></i> Ringkaskan Deskripsi'
                        : '<i class="fa fa-expand-alt mr-1 text-muted"></i> Perluas Deskripsi';
                });
            }

            // Toggle Mode Ringkas vs Detail TP & Sumatif Bab
            const rekapTable = document.getElementById('rkRekapTable');
            const viewModeBtns = document.querySelectorAll('.js-rk-view-mode');
            const applyViewMode = function (mode) {
                if (!rekapTable) return;
                const isSummary = mode !== 'detail';
                rekapTable.classList.toggle('is-summary-only', isSummary);
                viewModeBtns.forEach(function (b) {
                    b.classList.toggle('is-active', b.dataset.mode === (isSummary ? 'summary' : 'detail'));
                });
                try {
                    localStorage.setItem('rk_rekap_view_mode', isSummary ? 'summary' : 'detail');
                } catch (err) {}
            };

            if (rekapTable && viewModeBtns.length) {
                let savedMode = 'summary';
                try {
                    const stored = localStorage.getItem('rk_rekap_view_mode');
                    if (stored === 'detail' || stored === 'summary') savedMode = stored;
                } catch (err) {}
                applyViewMode(savedMode);

                viewModeBtns.forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        applyViewMode(btn.dataset.mode);
                    });
                });
            }

            // Toggle Tampilkan / Sembunyikan Kolom Nilai Mid di Tab Semester
            const toggleMidColsBtn = document.getElementById('btnToggleMidCols');
            if (rekapTable && toggleMidColsBtn) {
                const midCount = toggleMidColsBtn.dataset.midCount || '0';
                const applyMidCols = function (hideMid) {
                    rekapTable.classList.toggle('is-hide-mid', hideMid);
                    toggleMidColsBtn.classList.toggle('is-active', hideMid);
                    toggleMidColsBtn.setAttribute('aria-pressed', hideMid ? 'true' : 'false');
                    toggleMidColsBtn.innerHTML = hideMid
                        ? '<i class="fa fa-eye-slash mr-1"></i> Nilai Mid: Disembunyikan (' + midCount + ')'
                        : '<i class="fa fa-eye mr-1"></i> Nilai Mid: Tampil (' + midCount + ')';
                    try {
                        localStorage.setItem('rk_rekap_hide_mid', hideMid ? '1' : '0');
                    } catch (err) {}
                };

                let initialHideMid = false;
                try {
                    initialHideMid = localStorage.getItem('rk_rekap_hide_mid') === '1';
                } catch (err) {}
                applyMidCols(initialHideMid);

                toggleMidColsBtn.addEventListener('click', function () {
                    applyMidCols(!rekapTable.classList.contains('is-hide-mid'));
                });
            }

            // Pencarian nama/NIS & filter ketuntasan
            const searchInput = document.getElementById('rkSearchInput');
            const filterBtns = document.querySelectorAll('.js-rk-status-filter');
            const studentRows = document.querySelectorAll('.js-rk-student-row');
            const emptyRow = document.getElementById('rkEmptyFilterRow');
            let activeStatusFilter = 'all';

            const applyRowFilters = function () {
                const q = searchInput ? searchInput.value.trim().toLowerCase() : '';
                let visibleCount = 0;

                studentRows.forEach(function (row) {
                    const hay = row.dataset.search || '';
                    const tuntas = row.dataset.tuntas || 'empty';
                    const matchesSearch = q === '' || hay.indexOf(q) !== -1;
                    const matchesStatus = activeStatusFilter === 'all' || tuntas === activeStatusFilter;
                    const show = matchesSearch && matchesStatus;
                    row.style.display = show ? '' : 'none';
                    if (show) visibleCount++;
                });

                if (emptyRow) {
                    emptyRow.classList.toggle('d-none', visibleCount > 0 || studentRows.length === 0);
                }
            };

            if (searchInput) {
                searchInput.addEventListener('input', applyRowFilters);
            }
            filterBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    activeStatusFilter = btn.dataset.filter || 'all';
                    filterBtns.forEach(function (b) { b.classList.toggle('is-active', b === btn); });
                    applyRowFilters();
                });
            });

            // Tooltip header TP & Sumatif Bab
            const rkTooltip = document.getElementById('rkTooltip');
            const hideTip = function () {
                if (!rkTooltip) return;
                rkTooltip.classList.remove('is-visible');
                rkTooltip.setAttribute('aria-hidden', 'true');
            };
            const showTip = function (el) {
                if (!rkTooltip) return;
                const title = el.dataset.tipTitle || '';
                const body = el.dataset.tipBody || '';
                if (!title && !body) return;
                rkTooltip.innerHTML = '';

                const t = document.createElement('div');
                t.style.cssText = 'font-weight:800;color:#fb923c;margin-bottom:0.15rem;';
                t.textContent = title;
                rkTooltip.appendChild(t);

                if (body) {
                    const b = document.createElement('div');
                    b.textContent = body;
                    rkTooltip.appendChild(b);
                }

                rkTooltip.classList.add('is-visible');
                rkTooltip.setAttribute('aria-hidden', 'false');

                const rect = el.getBoundingClientRect();
                const tipRect = rkTooltip.getBoundingClientRect();
                const left = Math.max(8, Math.min(rect.left + (rect.width / 2) - (tipRect.width / 2), window.innerWidth - tipRect.width - 8));
                const top = rect.bottom + 6;
                rkTooltip.style.left = left + 'px';
                rkTooltip.style.top = top + 'px';
            };

            document.querySelectorAll('.js-rk-col-tip').forEach(function (th) {
                th.addEventListener('mouseenter', function () { showTip(th); });
                th.addEventListener('mouseleave', hideTip);
            });
            window.addEventListener('scroll', hideTip, { capture: true, passive: true });
        });
    </script>
@endpush
