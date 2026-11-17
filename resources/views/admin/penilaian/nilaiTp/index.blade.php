@extends('layouts.appui')

@section('title')
    <title>Penilaian Tujuan Pembelajaran (SD)</title>
@endsection

@section('content')
    @push('css')
        <style>
            .np-filter-bar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 0.9rem 1.15rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            }

            .np-filter-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.75rem;
                align-items: end;
            }

            @media (min-width: 768px) {
                .np-filter-grid {
                    grid-template-columns: 1.2fr 1fr 1.2fr auto;
                }
            }

            .np-periode-tabs {
                display: inline-flex;
                align-items: center;
                padding: 0.25rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                gap: 0.25rem;
            }

            .np-periode-tab {
                padding: 0.42rem 0.9rem;
                border-radius: 9px;
                font-size: 0.8rem;
                font-weight: 700;
                color: #475569;
                text-decoration: none !important;
                transition: all 0.15s ease;
            }

            .np-periode-tab:hover {
                color: #0f172a;
                background: rgba(255, 255, 255, 0.7);
            }

            .np-periode-tab.is-active {
                background: #ffffff;
                color: #c2410c;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
            }

            /* Ringkasan KKM, Bobot & Komponen (Minimalis 3-Kartu) */
            .np-summary-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.75rem;
                margin-bottom: 0.9rem;
            }

            @media (min-width: 768px) {
                .np-summary-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1100px) {
                .np-summary-grid {
                    grid-template-columns: 1.25fr 1.1fr 1fr;
                }
            }

            .np-summary-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                padding: 0.85rem 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                gap: 0.45rem;
            }

            .np-card-head {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
            }

            .np-card-label {
                font-size: 0.7rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
                display: inline-flex;
                align-items: center;
                gap: 0.4rem;
            }

            .np-card-dot {
                width: 7px;
                height: 7px;
                border-radius: 999px;
                display: inline-block;
                flex-shrink: 0;
            }

            .np-card-action {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.2rem 0.55rem;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #475569;
                font-size: 0.7rem;
                font-weight: 600;
                text-decoration: none !important;
                cursor: pointer;
                transition: all 0.15s ease;
                white-space: nowrap;
            }

            .np-card-action:hover {
                border-color: #cbd5e1;
                background: #f1f5f9;
                color: #0f172a;
            }

            .np-card-action.is-indigo {
                background: #eef2ff;
                border-color: #c7d2fe;
                color: #4338ca;
            }

            .np-card-action.is-indigo:hover {
                background: #e0e7ff;
                color: #3730a3;
            }

            .np-card-metric {
                font-size: 1.05rem;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.3;
                display: flex;
                flex-wrap: wrap;
                align-items: baseline;
                gap: 0.45rem;
                font-variant-numeric: tabular-nums;
            }

            .np-pred-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
            }

            .np-pred-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.14rem 0.45rem;
                border-radius: 6px;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                color: #475569;
                font-size: 0.69rem;
                font-weight: 500;
                font-variant-numeric: tabular-nums;
            }

            .np-pred-chip strong {
                color: #0f172a;
                font-weight: 700;
            }

            .np-pred-chip.is-tuntas-no {
                background: #fff1f2;
                border-color: #fecdd3;
                color: #be123c;
            }

            .np-pred-chip.is-tuntas-no strong {
                color: #9f1239;
            }

            .np-card-sub {
                font-size: 0.72rem;
                color: #64748b;
                line-height: 1.35;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
            }

            .np-status-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.14rem 0.5rem;
                border-radius: 999px;
                background: #f1f5f9;
                border: 1px solid #cbd5e1;
                color: #475569;
                font-size: 0.68rem;
                font-weight: 600;
            }

            /* Toolbar Terintegrasi di Atas Grid Nilai */
            .np-matrix-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.75rem 1rem;
                background: #ffffff;
                border-bottom: 1px solid #e2e8f0;
            }

            .np-search-wrap {
                position: relative;
                width: 100%;
                max-width: 245px;
            }

            .np-search-wrap i {
                position: absolute;
                left: 0.72rem;
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8;
                font-size: 0.76rem;
                pointer-events: none;
            }

            .np-search-input {
                width: 100%;
                height: 32px;
                padding: 0.3rem 0.7rem 0.3rem 2rem;
                border-radius: 9px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                font-size: 0.76rem;
                color: #0f172a;
                transition: all 0.15s ease;
            }

            .np-search-input:focus {
                background: #ffffff;
                border-color: #ea580c;
                outline: 0;
                box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.14);
            }

            .np-toolbar-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
            }

            .np-action-cluster {
                display: inline-flex;
                align-items: center;
                padding: 0.18rem;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                gap: 0.18rem;
            }

            .np-cluster-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.32rem;
                padding: 0.28rem 0.65rem;
                border-radius: 7px;
                border: 0;
                background: transparent;
                color: #475569;
                font-size: 0.74rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.12s ease;
                white-space: nowrap;
            }

            .np-cluster-btn:hover {
                background: #ffffff;
                color: #0f172a;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.06);
            }

            .np-cluster-btn.is-accent-orange {
                background: #fff7ed;
                color: #c2410c;
                font-weight: 700;
            }

            .np-cluster-btn.is-accent-orange:hover {
                background: #ea580c;
                color: #ffffff;
            }

            .np-cluster-btn.is-accent-teal {
                background: #f0fdfa;
                color: #0f766e;
                font-weight: 700;
            }

            .np-cluster-btn.is-accent-teal:hover {
                background: #0f766e;
                color: #ffffff;
            }

            .np-mid-toggle-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                height: 32px;
                padding: 0 0.72rem;
                border-radius: 9px;
                border: 1px solid #cbd5e1;
                background: #ffffff;
                color: #475569;
                font-size: 0.74rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.14s ease;
                white-space: nowrap;
            }

            .np-mid-toggle-btn:hover {
                border-color: #94a3b8;
                color: #0f172a;
            }

            .np-mid-toggle-btn.is-active {
                background: #0f172a;
                border-color: #0f172a;
                color: #ffffff;
            }

            .np-table.is-hide-mid .np-mid-col {
                display: none;
            }

            .np-th-tp-inner {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.32rem;
            }

            .np-tp-tooltip-trigger {
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
                padding: 0.1rem 0.35rem;
                border-radius: 6px;
                background: transparent;
                border: 0;
                color: inherit;
                font-weight: 800;
                font-size: 0.78rem;
                cursor: pointer;
                transition: background-color 0.12s ease;
            }

            .np-tp-tooltip-trigger:hover,
            .np-tp-tooltip-trigger:focus {
                background: rgba(255, 255, 255, 0.55);
                outline: none;
            }

            .np-th-mini-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                height: 20px;
                padding: 0 0.38rem;
                border-radius: 999px;
                border: 1px solid rgba(15, 23, 42, 0.14);
                font-size: 0.66rem;
                font-weight: 700;
                line-height: 1;
                cursor: pointer;
                background: rgba(255, 255, 255, 0.85);
                color: #0f172a;
                transition: all 0.12s ease;
            }

            .np-th-mini-btn:hover {
                background: #ffffff;
                border-color: #ea580c;
                color: #c2410c;
            }

            .np-th-mini-btn.is-rapor-on {
                background: #16a34a;
                border-color: #15803d;
                color: #ffffff;
            }

            .np-th-mini-btn.is-rapor-off {
                background: #cbd5e1;
                border-color: #94a3b8;
                color: #334155;
            }

            .np-tp-tooltip-popover {
                position: fixed;
                z-index: 2100;
                max-width: 340px;
                min-width: 220px;
                padding: 0.65rem 0.8rem;
                border-radius: 10px;
                background: #0f172a;
                color: #f8fafc;
                box-shadow: 0 18px 35px -10px rgba(15, 23, 42, 0.45);
                border: 1px solid rgba(251, 146, 60, 0.45);
                font-size: 0.76rem;
                line-height: 1.45;
                pointer-events: none;
                opacity: 0;
                transform: translateY(4px);
                transition: opacity 0.1s ease, transform 0.1s ease;
                display: none;
            }

            .np-tp-tooltip-popover.is-visible {
                display: block;
                opacity: 1;
                transform: translateY(0);
            }

            .np-manager-scroll {
                max-height: 60vh;
                overflow-y: auto;
            }

            .np-matrix-wrap {
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 14px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            }

            .np-matrix-scroll {
                overflow-x: auto;
                max-width: 100%;
            }

            .np-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                font-size: 0.78rem;
                font-variant-numeric: tabular-nums;
            }

            .np-table th,
            .np-table td {
                border-right: 1px solid #cbd5e1;
                border-bottom: 1px solid #cbd5e1;
                padding: 0.42rem 0.5rem;
                vertical-align: middle;
            }

            .np-th-identity {
                background: #e2e8f0;
                color: #0f172a;
                font-weight: 700;
                text-align: center;
            }

            .np-th-tp {
                background: #bfdbfe;
                color: #0f172a;
                font-weight: 700;
                text-align: center;
                white-space: nowrap;
            }

            .np-th-tp.is-excluded {
                background: #e2e8f0;
                color: #64748b;
            }

            .np-th-tp.is-mid-readonly {
                background: #e2e8f0;
                color: #334155;
            }

            .np-th-tp.is-mid-readonly.is-excluded {
                background: #f1f5f9;
                color: #64748b;
            }

            .np-mid-readonly-pill {
                display: inline-flex;
                align-items: center;
                padding: 0.08rem 0.4rem;
                border-radius: 999px;
                background: #f8fafc;
                border: 1px solid #cbd5e1;
                color: #475569;
                font-size: 0.62rem;
                font-weight: 700;
                line-height: 1.1;
            }

            .np-th-aspek {
                background: #fef08a;
                color: #0f172a;
                font-weight: 700;
                text-align: center;
                min-width: 84px;
                position: relative;
            }

            .np-th-natp {
                background: #b91c1c;
                color: #ffffff;
                font-weight: 700;
                text-align: center;
                min-width: 68px;
                white-space: nowrap;
            }

            .np-th-natp.is-mid-readonly {
                background: #64748b;
                color: #f8fafc;
                min-width: 82px;
            }

            .np-th-ujian {
                background: #4338ca;
                color: #ffffff;
                font-weight: 700;
                text-align: center;
                min-width: 92px;
                white-space: nowrap;
            }

            .np-th-sumatif-group {
                background: #99f6e4;
                color: #134e4a;
                font-weight: 800;
                text-align: center;
                white-space: nowrap;
            }

            .np-th-sumatif {
                background: #ccfbf1;
                color: #0f172a;
                font-weight: 700;
                text-align: center;
                min-width: 88px;
                position: relative;
            }

            .np-th-sumatif-mid {
                background: #e2e8f0;
                color: #475569;
                font-weight: 700;
                text-align: center;
                min-width: 84px;
            }

            .np-th-sumatif-rata {
                background: #0f766e;
                color: #ffffff;
                font-weight: 700;
                text-align: center;
                min-width: 78px;
                white-space: nowrap;
            }

            .np-sumatif-mid-cell {
                background: #f8fafc;
                color: #475569;
                text-align: center;
                font-weight: 700;
            }

            .np-sumatif-rata-cell {
                background: #f0fdfa;
                color: #115e59;
                text-align: center;
                font-weight: 800;
            }

            .np-sumatif-rata-cell.is-below-kkm {
                background: #fef2f2;
                color: #b91c1c;
            }

            .np-sumatif-action-btn {
                border: none;
                background: transparent;
                color: #115e59;
                opacity: 0.7;
                cursor: pointer;
                padding: 0 0.18rem;
                font-size: 0.68rem;
                line-height: 1;
            }

            .np-sumatif-action-btn:hover {
                opacity: 1;
                color: #0f766e;
            }

            .np-sumatif-action-btn.is-danger:hover {
                color: #dc2626;
            }

            .np-th-rapor {
                background: #ea580c;
                color: #ffffff;
                font-weight: 700;
                text-align: center;
                min-width: 78px;
            }

            .np-ujian-cell {
                background: #eef2ff;
                text-align: center;
            }

            .np-sticky-col-1 {
                position: sticky;
                left: 0;
                z-index: 3;
                background: #ffffff;
                min-width: 42px;
                width: 42px;
                text-align: center;
            }

            .np-sticky-col-2 {
                position: sticky;
                left: 42px;
                z-index: 3;
                background: #ffffff;
                min-width: 210px;
                max-width: 240px;
                box-shadow: 2px 0 4px rgba(15, 23, 42, 0.05);
            }

            thead .np-sticky-col-1,
            thead .np-sticky-col-2 {
                z-index: 5;
                background: #e2e8f0;
            }

            .np-score-input {
                width: 66px;
                height: 32px;
                padding: 0 0.35rem;
                text-align: center;
                font-size: 0.8rem;
                font-weight: 600;
                color: #0f172a;
                border: 1px solid #cbd5e1;
                border-radius: 7px;
                background: #ffffff;
            }

            .np-score-input:focus {
                border-color: #ea580c;
                outline: 0;
                box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.2);
            }

            .np-score-input.is-below-kkm {
                background: #fef2f2;
                border-color: #fca5a5;
                color: #b91c1c;
            }

            .np-natp-cell {
                background: #eff6ff;
                text-align: center;
                font-weight: 800;
                color: #1e293b;
            }

            .np-natp-cell.is-below-kkm {
                color: #b91c1c;
                background: #fef2f2;
            }

            .np-natp-readonly-cell {
                background: #f8fafc;
                color: #475569;
                text-align: center;
                font-weight: 700;
            }

            .np-readonly-chip {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                min-width: 52px;
                height: 30px;
                padding: 0 0.45rem;
                border-radius: 7px;
                background: #f1f5f9;
                border: 1px dashed #cbd5e1;
                color: #334155;
                font-size: 0.8rem;
                font-weight: 700;
                cursor: default;
            }

            .np-natp-readonly-cell.is-below-kkm .np-readonly-chip,
            .np-sumatif-mid-cell.is-below-kkm .np-readonly-chip {
                background: #fee2e2;
                border-color: #fca5a5;
                color: #b91c1c;
            }

            .np-sumatif-mid-cell.is-below-kkm {
                background: #fef2f2;
            }

            .np-mid-carry {
                display: block;
                font-size: 0.64rem;
                font-weight: 600;
                color: #64748b;
            }

            .np-aspek-edit {
                border: none;
                background: transparent;
                color: #92400e;
                opacity: 0.65;
                cursor: pointer;
                padding: 0 0.16rem;
                font-size: 0.66rem;
                line-height: 1;
            }

            .np-aspek-edit:hover {
                opacity: 1;
                color: #c2410c;
            }

            .np-aspek-pill-btn {
                border: 1px solid #f59e0b;
                background: #fef3c7;
                color: #78350f;
                border-radius: 6px;
                padding: 0.1rem 0.4rem;
                font-size: 0.69rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.12s ease;
            }

            .np-aspek-pill-btn:hover {
                background: #fde68a;
                border-color: #d97706;
            }

            .np-aspek-del {
                border: none;
                background: transparent;
                color: #92400e;
                opacity: 0.65;
                cursor: pointer;
                padding: 0 0.2rem;
                font-size: 0.7rem;
            }

            .np-aspek-del:hover {
                opacity: 1;
                color: #dc2626;
            }

            .np-modal-overlay {
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

            .np-modal-overlay.is-open {
                display: flex;
            }

            .np-modal-card {
                width: 100%;
                max-width: 480px;
                background: #ffffff;
                border-radius: 16px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.35);
                overflow: hidden;
            }
        </style>
    @endpush

    @php
        $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $isMid = $periode === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $kkmNilai = $kkmNilai ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_KKM;
        $bobotTp = (int) ($bobotTp ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_TP);
        $bobotSumatif = (int) ($bobotSumatif ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_SUMATIF);
        $bobotUjian = (int) ($bobotUjian ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_UJIAN);
        $selectedTaSemester = $selectedTahunAjaran?->semester
            ?? ($tahunAjaranOptions ?? collect())->firstWhere('id', $selectedTahunAjaranId ?? null)?->semester;
        $ujianLabel = $ujianLabel ?? \App\Models\Admin\Penilaian\PeriodeTp::ujianLabel($periode, $selectedTaSemester);
        $ujianFullLabel = $ujianFullLabel ?? \App\Models\Admin\Penilaian\PeriodeTp::ujianFullLabel($periode, $selectedTaSemester);
        $kkmPredikats = $kkmPredikats ?? collect();
        $hasKkmConfig = $hasKkmConfig ?? false;
        $tpList = $tpList ?? collect();
        $students = $students ?? collect();
        $aspeksByTp = $aspeksByTp ?? collect();
        $periodeSettingsByTp = $periodeSettingsByTp ?? collect();
        $carriedMidSumatifBabs = $carriedMidSumatifBabs ?? collect();
        $activeSumatifBabs = $activeSumatifBabs ?? collect();
        $sumatifNilaiLookup = $sumatifNilaiLookup ?? [];
        $nilaiLookup = $nilaiLookup ?? [];
        $rekapTpLookup = $rekapTpLookup ?? [];
        $rekapMapelLookup = $rekapMapelLookup ?? [];
        $teknikOptions = $teknikOptions ?? \App\Models\Admin\Penilaian\AspekTp::TEKNIK_OPTIONS;

        // Pada tab Mid Semester: sembunyikan TP yang sudah memiliki nilai di Semester (selama belum dinilai di Mid).
        // Pada tab Semester: tampilkan kembali TP yang sudah dinilai di Mid dan Sumatif Bab Mid dalam mode read-only.
        $hiddenSemTpIds = [];
        $readOnlyMidTpIds = [];
        foreach ($tpList as $tpItem) {
            $hasMidScore = false;
            $hasSemScore = false;
            foreach ($students as $st) {
                if (($rekapTpLookup["{$st->id}:{$tpItem->id}:".\App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID]?->nilai_akhir_tp ?? null) !== null) {
                    $hasMidScore = true;
                }
                if (($rekapTpLookup["{$st->id}:{$tpItem->id}:".\App\Models\Admin\Penilaian\PeriodeTp::PERIODE_SEMESTER]?->nilai_akhir_tp ?? null) !== null) {
                    $hasSemScore = true;
                }
            }

            if ($isMid && $hasSemScore && ! $hasMidScore) {
                $hiddenSemTpIds[$tpItem->id] = true;
            } elseif (! $isMid && $hasMidScore && ! $hasSemScore) {
                $readOnlyMidTpIds[$tpItem->id] = true;
            }
        }

        $displayTpList = $tpList->reject(static fn ($tpItem): bool => isset($hiddenSemTpIds[$tpItem->id]))->values();
        $editableTpList = $displayTpList->reject(static fn ($tpItem): bool => isset($readOnlyMidTpIds[$tpItem->id]))->values();
        $hiddenSemTpCount = count($hiddenSemTpIds);
        $readOnlyMidTpCount = count($readOnlyMidTpIds);
        $visibleMidSumatifBabs = ! $isMid ? $carriedMidSumatifBabs->values() : collect();
        $displaySumatifBabs = $activeSumatifBabs->values();
        $visibleSumatifCount = $visibleMidSumatifBabs->count() + $displaySumatifBabs->count();
        $readOnlyMidItemsCount = $readOnlyMidTpCount + $visibleMidSumatifBabs->count();
        $hasSumatifCols = $visibleSumatifCount > 0;
        $includedTpTotal = $displayTpList->filter(static function ($tpItem) use ($periodeSettingsByTp): bool {
            $setting = $periodeSettingsByTp->get($tpItem->id);

            return $setting === null ? true : (bool) $setting->masuk_rapor;
        })->count();
        $kkmDisplay = rtrim(rtrim(number_format((float) $kkmNilai, 2, '.', ''), '0'), '.');

        $bobotFormulaParts = array_values(array_filter([
            $bobotTp > 0 ? "{$bobotTp}% TP" : null,
            $bobotSumatif > 0 ? "{$bobotSumatif}% Sumatif Bab" : null,
            $bobotUjian > 0 ? "{$bobotUjian}% {$ujianLabel}" : null,
        ]));
        $bobotFormulaLabel = $bobotFormulaParts !== [] ? implode(' + ', $bobotFormulaParts) : '100% TP';

        $contextParams = array_filter([
            'tahun_ajaran_id' => $selectedTahunAjaranId ?? null,
            'kelas_id' => $selectedKelas?->id ?? null,
            'mata_pelajaran_id' => $selectedMapel?->id ?? null,
            'periode' => $periode,
        ], static fn ($v) => $v !== null && $v !== '');

        $predikatsForJs = $kkmPredikats->map(static fn ($p): array => [
            'predikat' => $p->predikat,
            'nilai_min' => (float) $p->nilai_min,
            'nilai_max' => (float) $p->nilai_max,
            'is_tuntas' => (bool) $p->is_tuntas,
        ])->values()->all();
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-note2" style="color: white;"></i>
                    </div>
                    <div>
                        Penilaian Tujuan Pembelajaran (SD)
                        <div class="page-title-subheading">
                            Input nilai aspek per TP jenjang SD. Periode Semester menggabungkan seluruh NA TP Mid &amp; Semester secara otomatis.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions flex-wrap">
                    @if ($selectedKelas && $selectedMapel && ($tpList->isNotEmpty() || $hasSumatifCols))
                        <a href="{{ route('penilaian-rekap.index', $contextParams) }}" class="btn btn-light shadow-sm"
                            style="border-radius: 10px; font-weight: 600;">
                            <i class="fa fa-list-alt mr-1"></i> Rekap &amp; Deskripsi
                        </a>
                        <a href="{{ route('penilaian-tp.export', $contextParams) }}" class="btn btn-light shadow-sm"
                            style="border-radius: 10px; font-weight: 600; color: #0f766e; border-color: #99f6e4; background: #f0fdfa;">
                            <i class="fa fa-file-excel mr-1"></i> Export Excel
                        </a>
                        <button type="button" id="btnOpenImportNilai" class="btn btn-light shadow-sm"
                            style="border-radius: 10px; font-weight: 600; color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff;">
                            <i class="fa fa-upload mr-1"></i> Import Excel
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if (session('import_errors'))
            <div class="alert alert-danger mb-3" style="border-radius: 12px;">
                <strong>Detail Error Import:</strong>
                <ul class="mb-0 mt-1 pl-3 small">
                    @foreach (session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter Konteks + Tab Periode Mid / Semester -->
        <div class="np-filter-bar">
            <form method="GET" action="{{ route('penilaian-tp.index') }}" id="matrixFilterForm">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <div class="np-filter-grid">
                    <div>
                        <label for="np_ta" class="small font-weight-semibold text-muted mb-1">Tahun Ajaran / Semester</label>
                        <select name="tahun_ajaran_id" id="np_ta" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach ($tahunAjaranOptions as $ta)
                                <option value="{{ $ta->id }}" @selected((int) $selectedTahunAjaranId === (int) $ta->id)>
                                    {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="np_kelas" class="small font-weight-semibold text-muted mb-1">Kelas (SD)</label>
                        <select name="kelas_id" id="np_kelas" class="form-control form-control-sm" onchange="this.form.submit()">
                            @forelse ($kelasOptions as $kls)
                                <option value="{{ $kls->id }}" @selected($selectedKelas && (int) $selectedKelas->id === (int) $kls->id)>
                                    {{ $kls->nama_kelas }} (Tingkat {{ $kls->tingkatan_kelas }})
                                </option>
                            @empty
                                <option value="">Belum ada kelas SD di TA ini</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label for="np_mapel" class="small font-weight-semibold text-muted mb-1">Mata Pelajaran (SD)</label>
                        <select name="mata_pelajaran_id" id="np_mapel" class="form-control form-control-sm" onchange="this.form.submit()">
                            @forelse ($mataPelajaranOptions as $mp)
                                <option value="{{ $mp->id }}" @selected($selectedMapel && (int) $selectedMapel->id === (int) $mp->id)>
                                    [{{ $mp->singkatan }}] {{ $mp->nama_mata_pelajaran }}
                                </option>
                            @empty
                                <option value="">Belum ada mapel SD</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <div class="small font-weight-semibold text-muted mb-1">Periode Penilaian</div>
                        <div class="np-periode-tabs">
                            <a href="{{ route('penilaian-tp.index', array_merge($contextParams, ['periode' => 'mid'])) }}"
                                class="np-periode-tab {{ $isMid ? 'is-active' : '' }}">
                                Mid Semester
                            </a>
                            <a href="{{ route('penilaian-tp.index', array_merge($contextParams, ['periode' => 'semester'])) }}"
                                class="np-periode-tab {{ ! $isMid ? 'is-active' : '' }}">
                                Semester
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if ($selectedKelas && $selectedMapel)
            <!-- Ringkasan Minimalis: KKM, Formula Bobot Rapor & Komponen Penilaian -->
            <div class="np-summary-grid">
                <div class="np-summary-card">
                    <div class="np-card-head">
                        <span class="np-card-label">
                            <span class="np-card-dot" style="background: #ea580c;"></span>
                            KKM Tingkat {{ $selectedKelas->tingkatan_kelas }}
                        </span>
                        @if (! $hasKkmConfig)
                            <a href="{{ route('penilaian-kkm.create', ['tahun_ajaran_id' => $selectedTahunAjaranId, 'mata_pelajaran_id' => $selectedMapel->id, 'tingkatan_kelas' => $selectedKelas->tingkatan_kelas]) }}"
                                class="np-card-action">
                                <i class="fa fa-sliders-h"></i> Atur KKM
                            </a>
                        @else
                            <a href="{{ route('penilaian-kkm.edit', $kkmModel->id) }}" class="np-card-action">
                                <i class="fa fa-cog"></i> Ubah KKM
                            </a>
                        @endif
                    </div>
                    <div class="np-card-metric">
                        <span style="color: #c2410c;">{{ $kkmDisplay }}</span>
                        @if (! $hasKkmConfig)
                            <span class="badge badge-light" style="font-size: 0.66rem; font-weight: 600;">Default</span>
                        @endif
                    </div>
                    <div class="np-pred-row">
                        @foreach ($kkmPredikats as $pred)
                            <span class="np-pred-chip {{ $pred->is_tuntas ? '' : 'is-tuntas-no' }}">
                                <strong>{{ $pred->predikat }}</strong>
                                {{ rtrim(rtrim(number_format((float) $pred->nilai_min, 2, '.', ''), '0'), '.') }}–{{ rtrim(rtrim(number_format((float) $pred->nilai_max, 2, '.', ''), '0'), '.') }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="np-summary-card">
                    <div class="np-card-head">
                        <span class="np-card-label">
                            <span class="np-card-dot" style="background: #4f46e5;"></span>
                            Formula Rapor ({{ $ujianLabel }})
                        </span>
                        <button type="button" id="btnOpenBobotModal" class="np-card-action is-indigo">
                            <i class="fa fa-sliders-h"></i> Atur Bobot Nilai
                        </button>
                    </div>
                    <div class="np-card-metric" style="font-size: 0.96rem; color: #312e81;">
                        Bobot Rapor: {{ $bobotFormulaLabel }}
                    </div>
                    <div class="np-card-sub">
                        @if ($hasSumatifCols && $bobotSumatif <= 0)
                            <span style="color: #0f766e; font-weight: 600;">
                                <i class="fa fa-exclamation-circle mr-1"></i>Bobot Sumatif Bab masih 0% — aktifkan agar masuk rapor.
                            </span>
                        @else
                            <span>
                                <i class="fa fa-calculator text-muted mr-1"></i>Komponen yang kosong otomatis ternormalisasi dari komponen aktif.
                            </span>
                        @endif
                    </div>
                </div>

                <div class="np-summary-card">
                    <div class="np-card-head">
                        <span class="np-card-label">
                            <span class="np-card-dot" style="background: #0f766e;"></span>
                            Komponen {{ $isMid ? 'Mid Semester' : 'Semester' }}
                        </span>
                    </div>
                    <div class="np-card-metric" style="font-size: 0.98rem;">
                        <span>{{ $displayTpList->count() }} TP</span>
                        <span class="text-muted font-weight-normal" style="font-size: 0.85rem;">&bull;</span>
                        <span style="color: #0f766e;">{{ $visibleSumatifCount }} Sumatif Bab</span>
                    </div>
                    <div class="np-card-sub">
                        <span><strong>{{ $includedTpTotal }}</strong> masuk rapor {{ $isMid ? 'Mid' : 'Semester' }}</span>
                        @if ($isMid && $hiddenSemTpCount > 0)
                            <span class="np-status-pill"
                                title="TP yang sudah memiliki nilai Semester disembunyikan pada tab Mid Semester.">
                                Disembunyikan: <strong>{{ $hiddenSemTpCount }} TP Semester</strong>
                            </span>
                        @elseif (! $isMid && ($readOnlyMidTpCount > 0 || $visibleMidSumatifBabs->isNotEmpty()))
                            <span class="np-status-pill"
                                title="Item dari Mid Semester ditampilkan dalam mode read-only pada tab Semester (edit melalui tab Mid Semester) dan nilainya tetap terhitung otomatis ke NA Rapor.">
                                <i class="fa fa-lock"></i> Read-Only Mid:
                                @if ($readOnlyMidTpCount > 0)
                                    <strong>{{ $readOnlyMidTpCount }} TP Mid</strong>
                                @endif
                                @if ($readOnlyMidTpCount > 0 && $visibleMidSumatifBabs->isNotEmpty())
                                    &bull;
                                @endif
                                @if ($visibleMidSumatifBabs->isNotEmpty())
                                    <strong>{{ $visibleMidSumatifBabs->count() }} Sumatif Mid</strong>
                                @endif
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        @if ($tpList->isEmpty() && ! $hasSumatifCols)
            <div class="main-card card">
                <div class="card-body text-center py-5 text-muted">
                    <div class="mb-2"><i class="fa fa-clipboard-list fa-2x" style="color: #cbd5e1;"></i></div>
                    <div class="font-weight-bold text-dark mb-1">Belum ada TP atau Sumatif Bab aktif untuk kombinasi filter ini</div>
                    <div class="small mb-3">
                        Tambahkan Tujuan Pembelajaran terlebih dahulu di menu Daftar TP, atau tambahkan kolom Sumatif Bab per mapel &amp; tingkatan kelas.
                    </div>
                    <div class="d-flex flex-wrap justify-content-center gap-2">
                        <a href="{{ route('tujuan-pembelajaran.create') }}" class="btn btn-sm text-white"
                            style="background: #ea580c; border-radius: 8px;">
                            <i class="fa fa-plus mr-1"></i> Buat Tujuan Pembelajaran
                        </a>
                        @if ($selectedKelas && $selectedMapel)
                            <button type="button" class="btn btn-sm text-white btn-open-sumatif-modal"
                                style="background: #0f766e; border-radius: 8px; font-weight: 600;">
                                <i class="fa fa-plus mr-1"></i> Tambah Sumatif Bab
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        @elseif ($students->isEmpty())
            <div class="main-card card">
                <div class="card-body text-center py-5 text-muted">
                    <div class="font-weight-bold text-dark mb-1">Belum ada anggota siswa di {{ $selectedKelas->nama_kelas }}</div>
                    <div class="small">Silakan masukkan siswa ke kelas ini pada menu Anggota Kelas terlebih dahulu.</div>
                </div>
            </div>
        @else
            <!-- Grid Nilai 2-Baris Header Ala Spreadsheet dengan Toolbar Terintegrasi -->
            <form method="POST" action="{{ route('penilaian-tp.store') }}" id="nilaiGridForm">
                @csrf
                <input type="hidden" name="tahun_ajaran_id" value="{{ $selectedTahunAjaranId }}">
                <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedMapel->id }}">
                <input type="hidden" name="periode" value="{{ $periode }}">

                <div class="np-matrix-wrap">
                    <div class="np-matrix-toolbar">
                        <div class="d-flex flex-wrap align-items-center gap-2" style="flex: 1 1 260px;">
                            <div class="np-search-wrap">
                                <i class="fa fa-search"></i>
                                <input type="search" id="npStudentSearch" class="np-search-input"
                                    placeholder="Cari nama atau NIS siswa...">
                            </div>
                            @if ($displayTpList->isNotEmpty())
                                <span class="small text-muted d-none d-lg-inline">
                                    <i class="fa fa-info-circle text-warning mr-1"></i>Klik kode TP untuk lihat isi tujuan pembelajaran.
                                </span>
                            @endif
                        </div>

                        <div class="np-toolbar-actions">
                            @if (! $isMid && $readOnlyMidItemsCount > 0)
                                <button type="button" id="btnToggleMidCols" class="np-mid-toggle-btn" aria-pressed="false"
                                    data-mid-count="{{ $readOnlyMidItemsCount }}"
                                    title="Sembunyikan atau tampilkan kolom nilai dari Mid Semester (NA Rapor tetap menggabungkan Mid + Semester).">
                                    <i class="fa fa-eye mr-1"></i> Nilai Mid: Tampil ({{ $readOnlyMidItemsCount }})
                                </button>
                            @endif

                            @if ($displayTpList->isNotEmpty() || $editableTpList->isNotEmpty())
                                <div class="np-action-cluster">
                                    @if ($displayTpList->isNotEmpty())
                                        <button type="button" id="btnOpenTpManager" class="np-cluster-btn">
                                            <i class="fa fa-tasks text-primary"></i> Kelola TP &amp; Aspek ({{ $displayTpList->count() }})
                                        </button>
                                    @endif
                                    @if ($editableTpList->isNotEmpty())
                                        <button type="button" class="np-cluster-btn is-accent-orange btn-open-aspek-modal"
                                            data-tp-id="{{ $editableTpList->first()?->id }}"
                                            data-tp-kode="{{ $editableTpList->first()?->kode_tp }}"
                                            title="Tambah aspek penilaian baru">
                                            <i class="fa fa-plus"></i> Aspek
                                        </button>
                                    @endif
                                </div>
                            @endif

                            <div class="np-action-cluster">
                                @if ($hasSumatifCols)
                                    <button type="button" id="btnOpenSumatifManager" class="np-cluster-btn">
                                        <i class="fa fa-layer-group" style="color: #0f766e;"></i> Kelola Sumatif Bab ({{ $visibleSumatifCount }})
                                    </button>
                                @endif
                                <button type="button" class="np-cluster-btn is-accent-teal btn-open-sumatif-modal"
                                    title="Tambah kolom Sumatif Bab baru">
                                    <i class="fa fa-plus"></i> Sumatif Bab
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="np-matrix-scroll">
                        <table class="np-table" id="tpNilaiMatrixTable">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="np-th-identity np-sticky-col-1">No</th>
                                    <th rowspan="2" class="np-th-identity np-sticky-col-2 text-left">Nama Siswa</th>
                                    @foreach ($displayTpList as $tp)
                                        @php
                                            $isReadOnlyMidTp = ! $isMid && isset($readOnlyMidTpIds[$tp->id]);
                                            $tpAspeks = $aspeksByTp->get($tp->id, collect());
                                            $colspan = $isReadOnlyMidTp ? 1 : ($tpAspeks->count() + 1);
                                            $setting = $periodeSettingsByTp->get($tp->id);
                                            $isIncluded = $setting === null ? true : (bool) $setting->masuk_rapor;
                                        @endphp
                                        <th colspan="{{ $colspan }}"
                                            class="np-th-tp {{ $isReadOnlyMidTp ? 'is-mid-readonly np-mid-col' : '' }} {{ ! $isIncluded ? 'is-excluded' : '' }}">
                                            <div class="np-th-tp-inner">
                                                <button type="button"
                                                    class="np-tp-tooltip-trigger"
                                                    data-tp-kode="{{ $tp->kode_tp }}"
                                                    data-tp-elemen="{{ $tp->elemen ?? '' }}"
                                                    data-tp-lingkup="{{ $tp->lingkup_materi ?? '' }}"
                                                    data-tp-teks="{{ $tp->tujuan_pembelajaran }}"
                                                    aria-label="Detail {{ $tp->kode_tp }}">
                                                    <span>{{ $tp->kode_tp }}</span>
                                                    <i class="fa fa-info-circle" style="font-size: 0.7rem; opacity: 0.75;"></i>
                                                </button>

                                                @if ($isReadOnlyMidTp)
                                                    <span class="np-mid-readonly-pill"
                                                        title="Sudah dinilai pada Mid Semester (read-only). Ubah melalui tab Mid Semester.">
                                                        <i class="fa fa-lock mr-1"></i>Mid
                                                    </span>
                                                    <span class="np-th-mini-btn {{ $isIncluded ? 'is-rapor-on' : 'is-rapor-off' }}"
                                                        title="{{ $isIncluded ? 'Masuk Rapor Semester' : 'Tidak Masuk Rapor Semester' }}"
                                                        style="cursor: default; opacity: 0.8;">
                                                        <i class="fa {{ $isIncluded ? 'fa-check' : 'fa-ban' }}"></i>
                                                    </span>
                                                @else
                                                    <button type="button"
                                                        class="np-th-mini-btn {{ $isIncluded ? 'is-rapor-on' : 'is-rapor-off' }} btn-toggle-rapor-tp"
                                                        data-action="{{ route('penilaian-tp.toggle-masuk-rapor', $tp->id) }}"
                                                        data-next="{{ $isIncluded ? '0' : '1' }}"
                                                        title="{{ $isIncluded ? 'Masuk Rapor (klik untuk kecualikan)' : 'Tidak Masuk Rapor (klik untuk masukkan)' }}">
                                                        <i class="fa {{ $isIncluded ? 'fa-check' : 'fa-ban' }}"></i>
                                                    </button>

                                                    <button type="button"
                                                        class="np-th-mini-btn btn-open-aspek-modal"
                                                        data-tp-id="{{ $tp->id }}"
                                                        data-tp-kode="{{ $tp->kode_tp }}"
                                                        title="Tambah aspek pada {{ $tp->kode_tp }}">
                                                        <i class="fa fa-plus"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </th>
                                    @endforeach
                                    @if ($hasSumatifCols)
                                        <th id="npSumatifGroupTh"
                                            colspan="{{ $visibleSumatifCount + 1 }}"
                                            data-full-colspan="{{ $visibleSumatifCount + 1 }}"
                                            data-sem-colspan="{{ $displaySumatifBabs->count() + 1 }}"
                                            class="np-th-sumatif-group">
                                            <div class="np-th-tp-inner">
                                                <span>Sumatif Bab</span>
                                                <button type="button"
                                                    class="np-th-mini-btn btn-open-sumatif-modal"
                                                    title="Tambah kolom Sumatif Bab baru">
                                                    <i class="fa fa-plus"></i>
                                                </button>
                                            </div>
                                        </th>
                                    @endif
                                    <th rowspan="2" class="np-th-ujian" title="{{ $ujianFullLabel }}">
                                        {{ $ujianLabel }}
                                        <div style="font-size: 0.65rem; font-weight: 500; opacity: 0.9;">
                                            {{ $bobotUjian > 0 ? "Bobot {$bobotUjian}%" : 'Opsional (0%)' }}
                                        </div>
                                    </th>
                                    <th rowspan="2" class="np-th-rapor">NA Rapor</th>
                                    <th rowspan="2" class="np-th-rapor" style="background: #9a3412;">Predikat</th>
                                </tr>
                                <tr>
                                    @foreach ($displayTpList as $tp)
                                        @php
                                            $isReadOnlyMidTp = ! $isMid && isset($readOnlyMidTpIds[$tp->id]);
                                            $tpAspeks = $aspeksByTp->get($tp->id, collect());
                                        @endphp
                                        @if ($isReadOnlyMidTp)
                                            <th class="np-th-natp is-mid-readonly np-mid-col"
                                                title="Nilai Akhir TP dari Mid Semester (Read-Only)">
                                                <i class="fa fa-lock mr-1" style="font-size: 0.62rem;"></i>NA Mid
                                            </th>
                                        @else
                                            @foreach ($tpAspeks as $aspek)
                                                <th class="np-th-aspek" data-aspek-col="{{ $aspek->id }}">
                                                    <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                                        <span>{{ $aspek->nama_aspek }}</span>
                                                        <button type="button"
                                                            class="np-aspek-edit btn-edit-aspek-modal"
                                                            data-action="{{ route('penilaian-tp.aspek.update', $aspek->id) }}"
                                                            data-tp-id="{{ $tp->id }}"
                                                            data-tp-kode="{{ $tp->kode_tp }}"
                                                            data-nama="{{ $aspek->nama_aspek }}"
                                                            data-teknik="{{ $aspek->teknik_penilaian ?? '' }}"
                                                            data-bobot="{{ $aspek->bobot ?? 100 }}"
                                                            data-counts-na="{{ $aspek->masuk_hitung_na_tp ? '1' : '0' }}"
                                                            data-urutan="{{ $aspek->urutan ?? 1 }}"
                                                            title="Ubah nama aspek {{ $aspek->nama_aspek }}">
                                                            <i class="fa fa-pen"></i>
                                                        </button>
                                                        @if ($tpAspeks->count() > 1)
                                                            <button type="button"
                                                                class="np-aspek-del btn-delete-aspek"
                                                                data-action="{{ route('penilaian-tp.aspek.destroy', $aspek->id) }}"
                                                                data-label="{{ $tp->kode_tp }} - {{ $aspek->nama_aspek }}"
                                                                title="Hapus aspek {{ $aspek->nama_aspek }}">
                                                                &times;
                                                            </button>
                                                        @endif
                                                    </div>
                                                </th>
                                            @endforeach
                                            <th class="np-th-natp">NA TP</th>
                                        @endif
                                    @endforeach
                                    @if ($hasSumatifCols)
                                        @foreach ($visibleMidSumatifBabs as $midBab)
                                            <th class="np-th-sumatif-mid np-mid-col" data-sumatif-mid-col="{{ $midBab->id }}"
                                                title="{{ ($midBab->keterangan ? $midBab->keterangan.' — ' : '').'Sumatif Bab dari Mid Semester (Read-Only)' }}">
                                                <div>{{ $midBab->nama_bab }}</div>
                                                <div style="font-size: 0.6rem; font-weight: 700; color: #64748b;">
                                                    <i class="fa fa-lock mr-1"></i>Mid
                                                </div>
                                            </th>
                                        @endforeach
                                        @foreach ($displaySumatifBabs as $bab)
                                            <th class="np-th-sumatif" data-sumatif-col="{{ $bab->id }}"
                                                title="{{ $bab->keterangan ?? $bab->nama_bab }}">
                                                <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                                    <span>{{ $bab->nama_bab }}</span>
                                                    <button type="button"
                                                        class="np-sumatif-action-btn btn-edit-sumatif-modal"
                                                        data-action="{{ route('penilaian-tp.sumatif-bab.update', $bab->id) }}"
                                                        data-nama="{{ $bab->nama_bab }}"
                                                        data-keterangan="{{ $bab->keterangan ?? '' }}"
                                                        data-urutan="{{ $bab->urutan }}"
                                                        title="Ubah {{ $bab->nama_bab }}">
                                                        <i class="fa fa-pen"></i>
                                                    </button>
                                                    <button type="button"
                                                        class="np-sumatif-action-btn is-danger btn-delete-sumatif"
                                                        data-action="{{ route('penilaian-tp.sumatif-bab.destroy', $bab->id) }}"
                                                        data-label="{{ $bab->nama_bab }}"
                                                        title="Hapus {{ $bab->nama_bab }}">
                                                        &times;
                                                    </button>
                                                </div>
                                            </th>
                                        @endforeach
                                        <th class="np-th-sumatif-rata">
                                            Rata
                                            <div style="font-size: 0.62rem; font-weight: 500; opacity: 0.9;">
                                                {{ $bobotSumatif > 0 ? "Bobot {$bobotSumatif}%" : 'Sumatif' }}
                                            </div>
                                        </th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students->values() as $idx => $student)
                                    @php
                                        $studentName = $student->siswa?->nama_lengkap ?? $student->siswa_nis;
                                        $rekapMapel = $rekapMapelLookup["{$student->id}:{$periode}"] ?? null;
                                    @endphp
                                    <tr data-student-row="{{ $student->id }}"
                                        data-student-search="{{ mb_strtolower($studentName.' '.$student->siswa_nis) }}">
                                        <td class="np-sticky-col-1 text-muted">{{ $idx + 1 }}</td>
                                        <td class="np-sticky-col-2">
                                            <div class="font-weight-semibold text-dark" style="line-height: 1.25;">
                                                {{ $studentName }}
                                            </div>
                                            <div class="text-muted" style="font-size: 0.68rem;">NIS: {{ $student->siswa_nis }}</div>
                                        </td>

                                        @foreach ($displayTpList as $tp)
                                            @php
                                                $isReadOnlyMidTp = ! $isMid && isset($readOnlyMidTpIds[$tp->id]);
                                                $tpAspeks = $aspeksByTp->get($tp->id, collect());
                                                $setting = $periodeSettingsByTp->get($tp->id);
                                                $isIncluded = $setting === null ? true : (bool) $setting->masuk_rapor;
                                                $rekapTpMid = $rekapTpLookup["{$student->id}:{$tp->id}:".\App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID] ?? null;
                                                $rekapTpCurrent = $rekapTpLookup["{$student->id}:{$tp->id}:{$periode}"] ?? null;
                                                $naTpValue = $rekapTpCurrent?->nilai_akhir_tp;
                                                $midNaTpValue = $rekapTpMid?->nilai_akhir_tp;
                                            @endphp

                                            @if ($isReadOnlyMidTp)
                                                <td class="np-natp-cell np-natp-readonly-cell np-mid-col {{ ($midNaTpValue !== null && (float) $midNaTpValue < (float) $kkmNilai) ? 'is-below-kkm' : '' }}"
                                                    data-readonly-mid-tp-cell="{{ $student->id }}:{{ $tp->id }}"
                                                    data-mid-score="{{ $midNaTpValue !== null ? (int) round((float) $midNaTpValue) : '' }}"
                                                    data-included-rapor="{{ $isIncluded ? '1' : '0' }}"
                                                    title="Nilai TP dari Mid Semester (Read-Only)">
                                                    <span class="np-readonly-chip">
                                                        {{ $midNaTpValue !== null ? (int) round((float) $midNaTpValue) : '-' }}
                                                    </span>
                                                </td>
                                            @else
                                                @foreach ($tpAspeks as $aspek)
                                                    @php
                                                        $nilaiObj = $nilaiLookup["{$student->id}:{$aspek->id}"] ?? null;
                                                        $cellVal = $nilaiObj?->nilai;
                                                        $cellDisplay = $cellVal !== null
                                                            ? rtrim(rtrim(number_format((float) $cellVal, 2, '.', ''), '0'), '.')
                                                            : '';
                                                        $isBelow = $cellVal !== null && (float) $cellVal < (float) $kkmNilai;
                                                    @endphp
                                                    <td class="text-center">
                                                        <input type="number"
                                                            step="0.01"
                                                            min="0"
                                                            max="100"
                                                            name="nilai[{{ $student->id }}][{{ $aspek->id }}]"
                                                            value="{{ $cellDisplay }}"
                                                            data-student-id="{{ $student->id }}"
                                                            data-tp-id="{{ $tp->id }}"
                                                            data-aspek-id="{{ $aspek->id }}"
                                                            data-weight="{{ $aspek->bobot }}"
                                                            data-counts-na="{{ $aspek->masuk_hitung_na_tp ? '1' : '0' }}"
                                                            class="np-score-input {{ $isBelow ? 'is-below-kkm' : '' }}">
                                                    </td>
                                                @endforeach

                                                <td class="np-natp-cell {{ ($naTpValue !== null && (float) $naTpValue < (float) $kkmNilai) ? 'is-below-kkm' : '' }}"
                                                    data-natp-cell="{{ $student->id }}:{{ $tp->id }}"
                                                    data-included-rapor="{{ $isIncluded ? '1' : '0' }}">
                                                    <span class="js-natp-value">
                                                        {{ $naTpValue !== null ? (int) round((float) $naTpValue) : '-' }}
                                                    </span>
                                                </td>
                                            @endif
                                        @endforeach

                                        @if ($hasSumatifCols)
                                            @foreach ($visibleMidSumatifBabs as $midBab)
                                                @php
                                                    $midSumObj = $sumatifNilaiLookup["{$student->id}:{$midBab->id}"] ?? null;
                                                    $midSumVal = $midSumObj?->nilai;
                                                    $midSumDisplay = $midSumVal !== null
                                                        ? rtrim(rtrim(number_format((float) $midSumVal, 2, '.', ''), '0'), '.')
                                                        : '-';
                                                    $isMidSumBelow = $midSumVal !== null && (float) $midSumVal < (float) $kkmNilai;
                                                @endphp
                                                <td class="np-sumatif-mid-cell np-mid-col {{ $isMidSumBelow ? 'is-below-kkm' : '' }}"
                                                    data-readonly-mid-sumatif-cell="{{ $student->id }}:{{ $midBab->id }}"
                                                    data-mid-score="{{ $midSumVal !== null ? rtrim(rtrim(number_format((float) $midSumVal, 2, '.', ''), '0'), '.') : '' }}"
                                                    title="Nilai Sumatif Bab Mid Semester (Read-Only)">
                                                    <span class="np-readonly-chip">{{ $midSumDisplay }}</span>
                                                </td>
                                            @endforeach

                                            @foreach ($displaySumatifBabs as $bab)
                                                @php
                                                    $sumObj = $sumatifNilaiLookup["{$student->id}:{$bab->id}"] ?? null;
                                                    $sumVal = $sumObj?->nilai;
                                                    $sumDisplay = $sumVal !== null
                                                        ? rtrim(rtrim(number_format((float) $sumVal, 2, '.', ''), '0'), '.')
                                                        : '';
                                                    $isSumBelow = $sumVal !== null && (float) $sumVal < (float) $kkmNilai;
                                                @endphp
                                                <td class="text-center" style="background: #f0fdfa;">
                                                    <input type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="100"
                                                        name="sumatif[{{ $student->id }}][{{ $bab->id }}]"
                                                        value="{{ $sumDisplay }}"
                                                        data-student-id="{{ $student->id }}"
                                                        data-sumatif-bab-id="{{ $bab->id }}"
                                                        data-aspek-id="sumatif-{{ $bab->id }}"
                                                        placeholder="-"
                                                        class="np-score-input np-sumatif-input {{ $isSumBelow ? 'is-below-kkm' : '' }}">
                                                </td>
                                            @endforeach

                                            @php
                                                $rataSumatifVal = $rekapMapel?->rata_rata_sumatif;
                                            @endphp
                                            <td class="np-sumatif-rata-cell {{ ($rataSumatifVal !== null && (float) $rataSumatifVal < (float) $kkmNilai) ? 'is-below-kkm' : '' }}"
                                                data-ratasumatif-cell="{{ $student->id }}">
                                                {{ $rataSumatifVal !== null ? (int) round((float) $rataSumatifVal) : '-' }}
                                            </td>
                                        @endif

                                        @php
                                            $nilaiUjian = $rekapMapel?->nilai_ujian;
                                            $ujianDisplay = $nilaiUjian !== null
                                                ? rtrim(rtrim(number_format((float) $nilaiUjian, 2, '.', ''), '0'), '.')
                                                : '';
                                            $isUjianBelow = $nilaiUjian !== null && (float) $nilaiUjian < (float) $kkmNilai;
                                            $naRaporVal = $rekapMapel?->nilai_akhir_rapor;
                                            $predRapor = $rekapMapel?->predikat_rapor;
                                            $isTuntasRapor = $rekapMapel?->tuntas_kkm;
                                        @endphp
                                        <td class="np-ujian-cell">
                                            <input type="number"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                name="ujian[{{ $student->id }}]"
                                                value="{{ $ujianDisplay }}"
                                                data-student-id="{{ $student->id }}"
                                                data-aspek-id="ujian"
                                                placeholder="-"
                                                class="np-score-input np-ujian-input {{ $isUjianBelow ? 'is-below-kkm' : '' }}">
                                        </td>
                                        <td class="text-center font-weight-bold"
                                            style="background: #fff7ed; color: #9a3412;"
                                            data-narapor-cell="{{ $student->id }}">
                                            {{ $naRaporVal !== null ? (int) round((float) $naRaporVal) : '-' }}
                                        </td>
                                        <td class="text-center" data-predikat-cell="{{ $student->id }}">
                                            @if ($predRapor !== null)
                                                <span class="badge {{ $isTuntasRapor === false ? 'badge-danger' : 'badge-success' }}">
                                                    {{ $predRapor }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-3"
                        style="background: #f8fafc; border-top: 1px solid #cbd5e1;">
                        <div class="small text-muted">
                            Menampilkan <strong>{{ $students->count() }}</strong> siswa dan <strong>{{ $displayTpList->count() }}</strong> TP.
                            Gunakan tombol <strong>Enter</strong> / <strong>Panah</strong> untuk berpindah sel cepat.
                        </div>
                        <button type="submit" class="btn text-white shadow-sm"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px; font-weight: 600; padding: 0.55rem 1.4rem;">
                            <i class="fa fa-save mr-1"></i> Simpan Semua Nilai
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <!-- Form Terpisah Hapus Aspek, Hapus Sumatif Bab & Toggle Masuk Rapor -->
    <form method="POST" id="deleteAspekForm" class="d-none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="kelas_id" value="{{ $selectedKelas?->id ?? '' }}">
    </form>

    <form method="POST" id="deleteSumatifForm" class="d-none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="kelas_id" value="{{ $selectedKelas?->id ?? '' }}">
    </form>

    <form method="POST" id="toggleMasukRaporForm" class="d-none">
        @csrf
        <input type="hidden" name="periode" value="{{ $periode }}">
        <input type="hidden" name="kelas_id" value="{{ $selectedKelas?->id ?? '' }}">
        <input type="hidden" name="masuk_rapor" id="toggleMasukRaporValue" value="1">
    </form>

    <!-- Tooltip Instan Kode Tujuan Pembelajaran -->
    <div id="npTpTooltip" class="np-tp-tooltip-popover" role="tooltip" aria-hidden="true"></div>

    <!-- Modal Kelola Semua TP & Aspek (Ringkas untuk Banyak TP) -->
    @if ($selectedKelas && $selectedMapel && $displayTpList->isNotEmpty())
        <div id="tpManagerModal" class="np-modal-overlay" role="dialog" aria-modal="true">
            <div class="np-modal-card" style="max-width: 780px;">
                <div class="d-flex align-items-center justify-content-between px-4 py-3"
                    style="border-bottom: 1px solid #e2e8f0;">
                    <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">
                        Kelola Masuk Rapor &amp; Aspek ({{ $displayTpList->count() }} TP — Kelas {{ $selectedKelas->tingkatan_kelas }})
                    </h5>
                    <button type="button" class="btn btn-sm btn-light" data-close-modal="tpManagerModal">&times;</button>
                </div>
                <div class="p-3" style="border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
                    <input type="search" id="tpManagerSearchInput" class="form-control form-control-sm"
                        placeholder="Cari kode TP, lingkup materi, atau isi tujuan pembelajaran...">
                </div>
                <div class="np-manager-scroll">
                    <table class="table table-hover table-sm mb-0" style="font-size: 0.78rem;">
                        <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th class="pl-3" style="width: 90px;">Kode TP</th>
                                <th>Tujuan Pembelajaran &amp; Aspek</th>
                                <th class="text-center" style="width: 145px;">Rapor {{ $isMid ? 'Mid' : 'Semester' }}</th>
                                <th class="text-center pr-3" style="width: 105px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody id="tpManagerTableBody">
                            @foreach ($displayTpList as $tp)
                                @php
                                    $isReadOnlyMidTp = ! $isMid && isset($readOnlyMidTpIds[$tp->id]);
                                    $setting = $periodeSettingsByTp->get($tp->id);
                                    $isIncluded = $setting === null ? true : (bool) $setting->masuk_rapor;
                                    $tpAspeks = $aspeksByTp->get($tp->id, collect());
                                    $searchHaystack = mb_strtolower($tp->kode_tp.' '.($tp->lingkup_materi ?? '').' '.$tp->tujuan_pembelajaran);
                                @endphp
                                <tr class="js-tp-manager-row" data-search="{{ $searchHaystack }}">
                                    <td class="pl-3 align-middle font-weight-bold text-dark">
                                        {{ $tp->kode_tp }}
                                        @if ($isReadOnlyMidTp)
                                            <div>
                                                <span class="badge badge-secondary mt-1" style="font-size: 0.62rem;">
                                                    <i class="fa fa-lock mr-1"></i>Mid
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-middle py-2">
                                        @if (filled($tp->lingkup_materi))
                                            <span class="badge badge-info mb-1">{{ $tp->lingkup_materi }}</span>
                                        @endif
                                        <div class="text-slate-700" style="line-height: 1.35;">{{ $tp->tujuan_pembelajaran }}</div>
                                        @if ($isReadOnlyMidTp)
                                            <div class="mt-1 text-muted" style="font-size: 0.71rem;">
                                                Sudah dinilai pada Mid Semester (tampil read-only pada tab Semester).
                                            </div>
                                        @else
                                            <div class="mt-1 text-muted d-flex flex-wrap align-items-center gap-1" style="font-size: 0.71rem;">
                                                <span>Aspek:</span>
                                                @foreach ($tpAspeks as $aspek)
                                                    <button type="button"
                                                        class="np-aspek-pill-btn btn-edit-aspek-modal"
                                                        data-action="{{ route('penilaian-tp.aspek.update', $aspek->id) }}"
                                                        data-tp-id="{{ $tp->id }}"
                                                        data-tp-kode="{{ $tp->kode_tp }}"
                                                        data-nama="{{ $aspek->nama_aspek }}"
                                                        data-teknik="{{ $aspek->teknik_penilaian ?? '' }}"
                                                        data-bobot="{{ $aspek->bobot ?? 100 }}"
                                                        data-counts-na="{{ $aspek->masuk_hitung_na_tp ? '1' : '0' }}"
                                                        data-urutan="{{ $aspek->urutan ?? 1 }}"
                                                        title="Ubah nama aspek {{ $aspek->nama_aspek }}">
                                                        {{ $aspek->nama_aspek }} <i class="fa fa-pen ml-1" style="font-size: 0.6rem; opacity: 0.75;"></i>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle">
                                        @if ($isReadOnlyMidTp)
                                            <span class="badge {{ $isIncluded ? 'badge-success' : 'badge-secondary' }}" style="font-size: 0.7rem; border-radius: 999px;">
                                                <i class="fa {{ $isIncluded ? 'fa-check' : 'fa-ban' }} mr-1"></i>
                                                {{ $isIncluded ? 'Masuk Rapor' : 'Non-Rapor' }}
                                            </span>
                                        @else
                                            <button type="button"
                                                class="btn btn-sm py-1 px-2 {{ $isIncluded ? 'btn-success' : 'btn-outline-secondary' }} btn-toggle-rapor-tp"
                                                data-action="{{ route('penilaian-tp.toggle-masuk-rapor', $tp->id) }}"
                                                data-next="{{ $isIncluded ? '0' : '1' }}"
                                                style="font-size: 0.7rem; border-radius: 999px;">
                                                <i class="fa {{ $isIncluded ? 'fa-check-square' : 'fa-square' }} mr-1"></i>
                                                {{ $isIncluded ? 'Masuk Rapor' : 'Non-Rapor' }}
                                            </button>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle pr-3">
                                        @if ($isReadOnlyMidTp)
                                            <span class="text-muted small"><i class="fa fa-lock mr-1"></i>Read-Only</span>
                                        @else
                                            <button type="button"
                                                class="btn btn-sm text-white py-1 px-2 btn-open-aspek-modal"
                                                data-tp-id="{{ $tp->id }}"
                                                data-tp-kode="{{ $tp->kode_tp }}"
                                                style="background: #ea580c; font-size: 0.72rem; border-radius: 7px;">
                                                <i class="fa fa-plus mr-1"></i> Aspek
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-end px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-light btn-sm" data-close-modal="tpManagerModal">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Tambah / Ubah Aspek TP (Seragam Per Jenjang) -->
    <div id="tpAspekModal" class="np-modal-overlay" role="dialog" aria-modal="true">
        <div class="np-modal-card">
            <div class="d-flex align-items-center justify-content-between px-4 py-3"
                style="border-bottom: 1px solid #e2e8f0;">
                <h5 class="mb-0 font-weight-bold" id="aspekModalTitle" style="font-size: 0.95rem;">
                    <span id="aspekModalTitlePrefix">Tambah Aspek Penilaian</span> (<span id="aspekModalTpLabel">TP</span>)
                </h5>
                <button type="button" class="btn btn-sm btn-light" data-close-modal="tpAspekModal">&times;</button>
            </div>
            <form method="POST" id="tpAspekForm" action="{{ route('penilaian-tp.aspek.store') }}"
                data-store-action="{{ route('penilaian-tp.aspek.store') }}">
                @csrf
                <input type="hidden" name="_method" id="tpAspekMethod" value="POST">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <input type="hidden" name="kelas_id" value="{{ $selectedKelas?->id ?? '' }}">
                <input type="hidden" name="tujuan_pembelajaran_id" id="aspekModalTpHidden" value="{{ $editableTpList->first()?->id ?? '' }}">
                <input type="hidden" name="bobot" id="aspekModalBobot" value="100">
                <input type="hidden" name="masuk_hitung_na_tp" id="aspekModalCountsNa" value="1">
                <input type="hidden" name="urutan" id="aspekModalUrutan" value="">
                <div class="p-4">
                    <p class="small text-muted mb-3">
                        Aspek yang ditambahkan atau diubah otomatis seragam untuk seluruh kelas paralel jenjang <strong>Kelas {{ $selectedKelas?->tingkatan_kelas }} SD</strong>.
                    </p>
                    <div class="form-group">
                        <label for="aspekModalTpId" class="small font-weight-bold">Tujuan Pembelajaran (TP) <span class="text-danger">*</span></label>
                        <select id="aspekModalTpId" class="form-control">
                            @foreach ($editableTpList as $tpOption)
                                <option value="{{ $tpOption->id }}"
                                    data-kode="{{ $tpOption->kode_tp }}"
                                    data-teks="{{ $tpOption->tujuan_pembelajaran }}">
                                    [{{ $tpOption->kode_tp }}] {{ \Illuminate\Support\Str::limit($tpOption->lingkup_materi ?: $tpOption->tujuan_pembelajaran, 75) }}
                                </option>
                            @endforeach
                        </select>
                        <div id="aspekModalTpPreview" class="small text-muted mt-2 p-2 rounded"
                            style="background: #f8fafc; border: 1px solid #e2e8f0; line-height: 1.35;"></div>
                    </div>
                    <div class="form-group">
                        <label for="nama_aspek" class="small font-weight-bold">Nama Aspek <span class="text-danger">*</span></label>
                        <input type="text" name="nama_aspek" id="nama_aspek" maxlength="100" required
                            class="form-control" placeholder="Contoh: Pemahaman / Bacaan / Makhroj / Hafalan">
                    </div>
                    <div class="form-group mb-0">
                        <label for="teknik_penilaian" class="small font-weight-bold">Teknik Penilaian (opsional)</label>
                        <select name="teknik_penilaian" id="teknik_penilaian" class="form-control">
                            <option value="">-- Umum / Opsional --</option>
                            @foreach ($teknikOptions as $techCode => $techLabel)
                                <option value="{{ $techCode }}">{{ $techLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-light btn-sm" data-close-modal="tpAspekModal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white" id="btnSubmitAspekModal" style="background: #ea580c;">Simpan Aspek</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Kelola Semua Sumatif Bab (Per Mapel & Tingkatan Kelas) -->
    @if ($selectedKelas && $selectedMapel && $hasSumatifCols)
        <div id="sumatifManagerModal" class="np-modal-overlay" role="dialog" aria-modal="true">
            <div class="np-modal-card" style="max-width: 700px;">
                <div class="d-flex align-items-center justify-content-between px-4 py-3"
                    style="border-bottom: 1px solid #e2e8f0;">
                    <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">
                        Kelola Kolom Sumatif Bab ({{ $visibleSumatifCount }}) — {{ $selectedMapel->singkatan }} Kelas {{ $selectedKelas->tingkatan_kelas }}
                    </h5>
                    <button type="button" class="btn btn-sm btn-light" data-close-modal="sumatifManagerModal">&times;</button>
                </div>
                <div class="np-manager-scroll">
                    <table class="table table-hover table-sm mb-0" style="font-size: 0.78rem;">
                        <thead style="background: #f8fafc; position: sticky; top: 0; z-index: 2;">
                            <tr>
                                <th class="pl-3" style="width: 65px;">Urutan</th>
                                <th style="width: 95px;">Periode</th>
                                <th>Nama Sumatif &amp; Keterangan</th>
                                <th class="text-center pr-3" style="width: 130px;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($visibleMidSumatifBabs as $midBab)
                                <tr style="background: #f8fafc;">
                                    <td class="pl-3 align-middle font-weight-bold text-muted">{{ $midBab->urutan }}</td>
                                    <td class="align-middle">
                                        <span class="badge badge-secondary">Mid</span>
                                    </td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold text-dark">{{ $midBab->nama_bab }}</div>
                                        @if (filled($midBab->keterangan))
                                            <div class="text-muted small">{{ $midBab->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle pr-3">
                                        <span class="text-muted small"><i class="fa fa-lock mr-1"></i>Read-Only</span>
                                    </td>
                                </tr>
                            @endforeach
                            @foreach ($displaySumatifBabs as $bab)
                                <tr>
                                    <td class="pl-3 align-middle font-weight-bold">{{ $bab->urutan }}</td>
                                    <td class="align-middle">
                                        <span class="badge" style="background: #ccfbf1; color: #115e59;">
                                            {{ $isMid ? 'Mid' : 'Semester' }}
                                        </span>
                                    </td>
                                    <td class="align-middle">
                                        <div class="font-weight-bold text-dark">{{ $bab->nama_bab }}</div>
                                        @if (filled($bab->keterangan))
                                            <div class="text-muted small">{{ $bab->keterangan }}</div>
                                        @endif
                                    </td>
                                    <td class="text-center align-middle pr-3">
                                        <button type="button"
                                            class="btn btn-sm btn-light py-1 px-2 btn-edit-sumatif-modal"
                                            data-action="{{ route('penilaian-tp.sumatif-bab.update', $bab->id) }}"
                                            data-nama="{{ $bab->nama_bab }}"
                                            data-keterangan="{{ $bab->keterangan ?? '' }}"
                                            data-urutan="{{ $bab->urutan }}"
                                            title="Ubah {{ $bab->nama_bab }}">
                                            <i class="fa fa-edit text-primary"></i>
                                        </button>
                                        <button type="button"
                                            class="btn btn-sm btn-light py-1 px-2 btn-delete-sumatif"
                                            data-action="{{ route('penilaian-tp.sumatif-bab.destroy', $bab->id) }}"
                                            data-label="{{ $bab->nama_bab }}"
                                            title="Hapus {{ $bab->nama_bab }}">
                                            <i class="fa fa-trash text-danger"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="d-flex justify-content-between px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-sm text-white btn-open-sumatif-modal" style="background: #0f766e;">
                        <i class="fa fa-plus mr-1"></i> Tambah Sumatif Bab
                    </button>
                    <button type="button" class="btn btn-light btn-sm" data-close-modal="sumatifManagerModal">Tutup</button>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Tambah / Ubah Kolom Sumatif Bab (Seragam Per Jenjang) -->
    @if ($selectedKelas && $selectedMapel)
        <div id="sumatifBabModal" class="np-modal-overlay" role="dialog" aria-modal="true">
            <div class="np-modal-card">
                <div class="d-flex align-items-center justify-content-between px-4 py-3"
                    style="border-bottom: 1px solid #e2e8f0;">
                    <h5 class="mb-0 font-weight-bold" id="sumatifBabModalTitle" style="font-size: 0.95rem;">
                        Tambah Sumatif Bab ({{ $isMid ? 'Mid Semester' : 'Semester' }})
                    </h5>
                    <button type="button" class="btn btn-sm btn-light" data-close-modal="sumatifBabModal">&times;</button>
                </div>
                <form method="POST" id="sumatifBabForm" action="{{ route('penilaian-tp.sumatif-bab.store') }}"
                    data-store-action="{{ route('penilaian-tp.sumatif-bab.store') }}">
                    @csrf
                    <input type="hidden" name="_method" id="sumatifBabMethod" value="POST">
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $selectedTahunAjaranId }}">
                    <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedMapel->id }}">
                    <input type="hidden" name="tingkatan_kelas" value="{{ $selectedKelas->tingkatan_kelas }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                    <div class="p-4">
                        <p class="small text-muted mb-3">
                            Kolom Sumatif Bab berlaku seragam untuk seluruh kelas paralel <strong>Kelas {{ $selectedKelas->tingkatan_kelas }} SD</strong> mapel <strong>{{ $selectedMapel->nama_mata_pelajaran }}</strong>.
                            @if (! $isMid)
                                Sumatif Bab yang dibuat di periode Mid tetap otomatis digabungkan pada Rekap Semester.
                            @endif
                        </p>
                        <div class="form-group">
                            <label for="sumatifNamaBabInput" class="small font-weight-bold">Nama Sumatif Bab <span class="text-danger">*</span></label>
                            <input type="text" name="nama_bab" id="sumatifNamaBabInput" maxlength="100" required
                                class="form-control" placeholder="Contoh: Bab 1 / Sumatif Bab 1 / Bab 1: Bilangan">
                        </div>
                        <div class="form-group">
                            <label for="sumatifKeteranganInput" class="small font-weight-bold">Keterangan / Lingkup Materi (opsional)</label>
                            <input type="text" name="keterangan" id="sumatifKeteranganInput" maxlength="255"
                                class="form-control" placeholder="Contoh: Penjumlahan & Pengurangan Bilangan Cacah">
                        </div>
                        <div class="form-group mb-0">
                            <label for="sumatifUrutanInput" class="small font-weight-bold">Urutan Tampil</label>
                            <input type="number" name="urutan" id="sumatifUrutanInput" min="1" max="999"
                                value="{{ ($activeSumatifBabs->max('urutan') ?? 0) + 1 }}"
                                data-default-urutan="{{ ($activeSumatifBabs->max('urutan') ?? 0) + 1 }}"
                                class="form-control">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                        <button type="button" class="btn btn-light btn-sm" data-close-modal="sumatifBabModal">Batal</button>
                        <button type="submit" class="btn btn-sm text-white" style="background: #0f766e;">Simpan Sumatif Bab</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Atur Bobot Penilaian (NA TP, Sumatif Bab, PSTS / PSAS / PSAT) -->
    @if ($selectedKelas && $selectedMapel)
        <div id="tpBobotModal" class="np-modal-overlay" role="dialog" aria-modal="true">
            <div class="np-modal-card" style="max-width: 540px;">
                <div class="d-flex align-items-center justify-content-between px-4 py-3"
                    style="border-bottom: 1px solid #e2e8f0;">
                    <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">
                        Atur Bobot Penilaian Rapor — Kelas {{ $selectedKelas->tingkatan_kelas }} SD
                    </h5>
                    <button type="button" class="btn btn-sm btn-light" data-close-modal="tpBobotModal">&times;</button>
                </div>
                <form method="POST" action="{{ route('penilaian-tp.bobot.update') }}">
                    @csrf
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $selectedTahunAjaranId }}">
                    <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                    <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedMapel->id }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <div class="p-4">
                        <p class="small text-muted mb-3">
                            Bobot berlaku seragam untuk seluruh kelas paralel <strong>Tingkat {{ $selectedKelas->tingkatan_kelas }}</strong> mapel <strong>{{ $selectedMapel->nama_mata_pelajaran }}</strong>. Total ketiga bobot harus tepat <strong>100%</strong>.
                        </p>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-light js-modal-bobot-preset" data-tp="100" data-sumatif="0" data-ujian="0"
                                style="border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600;">
                                100% NA TP
                            </button>
                            <button type="button" class="btn btn-sm btn-light js-modal-bobot-preset" data-tp="40" data-sumatif="40" data-ujian="20"
                                style="border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600;">
                                40% TP + 40% Sumatif + 20% {{ $ujianLabel }}
                            </button>
                            <button type="button" class="btn btn-sm btn-light js-modal-bobot-preset" data-tp="50" data-sumatif="30" data-ujian="20"
                                style="border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600;">
                                50% TP + 30% Sumatif + 20% {{ $ujianLabel }}
                            </button>
                            <button type="button" class="btn btn-sm btn-light js-modal-bobot-preset" data-tp="50" data-sumatif="25" data-ujian="25"
                                style="border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600;">
                                50% TP + 25% Sumatif + 25% {{ $ujianLabel }}
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-4">
                                <label for="modalBobotTp" class="small font-weight-bold">Bobot NA TP (%)</label>
                                <input type="number" min="0" max="100" step="1" name="bobot_tp" id="modalBobotTp"
                                    value="{{ $bobotTp }}" required class="form-control">
                            </div>
                            <div class="col-4">
                                <label for="modalBobotSumatif" class="small font-weight-bold">Sumatif Bab (%)</label>
                                <input type="number" min="0" max="100" step="1" name="bobot_sumatif" id="modalBobotSumatif"
                                    value="{{ $bobotSumatif }}" required class="form-control">
                            </div>
                            <div class="col-4">
                                <label for="modalBobotUjian" class="small font-weight-bold">Bobot {{ $ujianLabel }} (%)</label>
                                <input type="number" min="0" max="100" step="1" name="bobot_ujian" id="modalBobotUjian"
                                    value="{{ $bobotUjian }}" required class="form-control">
                            </div>
                        </div>
                        <div class="mt-3 d-flex justify-content-end">
                            <span id="modalBobotTotalBadge" class="badge badge-success" style="font-size: 0.74rem; padding: 0.35rem 0.65rem;">
                                Total: {{ $bobotTp + $bobotSumatif + $bobotUjian }}%
                            </span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                        <button type="button" class="btn btn-light btn-sm" data-close-modal="tpBobotModal">Batal</button>
                        <button type="submit" class="btn btn-sm text-white" id="btnSubmitModalBobot" style="background: #4338ca;">Simpan &amp; Hitung Ulang</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Import Excel Nilai TP -->
    @if ($selectedKelas && $selectedMapel)
        <div id="tpImportNilaiModal" class="np-modal-overlay" role="dialog" aria-modal="true">
            <div class="np-modal-card">
                <div class="d-flex align-items-center justify-content-between px-4 py-3"
                    style="border-bottom: 1px solid #e2e8f0;">
                    <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">
                        Import Nilai TP &amp; Sumatif dari Excel
                    </h5>
                    <button type="button" class="btn btn-sm btn-light" data-close-modal="tpImportNilaiModal">&times;</button>
                </div>
                <form method="POST" action="{{ route('penilaian-tp.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $selectedTahunAjaranId }}">
                    <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                    <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedMapel->id }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <div class="p-4">
                        <p class="small text-muted mb-3">
                            Gunakan file hasil tombol <strong>Export Excel</strong> yang sudah memiliki format 2 baris header (Kode TP / SUMATIF BAB / {{ $ujianLabel }} di baris 1, Nama Aspek / Nama Bab di baris 2).
                        </p>
                        <div class="form-group mb-0">
                            <label for="nilai_excel_file" class="small font-weight-bold">Pilih File (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="nilai_excel_file" accept=".xlsx,.xls,.csv" required class="form-control">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                        <button type="button" class="btn btn-light btn-sm" data-close-modal="tpImportNilaiModal">Batal</button>
                        <button type="submit" class="btn btn-sm text-white" style="background: #ea580c;">Import &amp; Hitung Ulang</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const kkmNilai = {{ json_encode((float) $kkmNilai) }};
            const bobotTp = {{ (int) $bobotTp }};
            const bobotSumatif = {{ (int) $bobotSumatif }};
            const bobotUjian = {{ (int) $bobotUjian }};
            const predikats = @json($predikatsForJs);
            const isMid = {{ $isMid ? 'true' : 'false' }};
            const table = document.getElementById('tpNilaiMatrixTable');

            const openModal = function (id) {
                const modal = document.getElementById(id);
                if (modal) modal.classList.add('is-open');
            };
            const closeModal = function (id) {
                const modal = document.getElementById(id);
                if (modal) modal.classList.remove('is-open');
            };

            document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    closeModal(btn.dataset.closeModal);
                });
            });

            document.querySelectorAll('.np-modal-overlay').forEach(function (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) {
                        overlay.classList.remove('is-open');
                    }
                });
            });

            const tpAspekForm = document.getElementById('tpAspekForm');
            const tpAspekMethod = document.getElementById('tpAspekMethod');
            const aspekTitlePrefix = document.getElementById('aspekModalTitlePrefix');
            const aspekTpSelect = document.getElementById('aspekModalTpId');
            const aspekTpHidden = document.getElementById('aspekModalTpHidden');
            const aspekTpLabel = document.getElementById('aspekModalTpLabel');
            const aspekTpPreview = document.getElementById('aspekModalTpPreview');
            const aspekNamaInput = document.getElementById('nama_aspek');
            const aspekTeknikSelect = document.getElementById('teknik_penilaian');
            const aspekBobotInput = document.getElementById('aspekModalBobot');
            const aspekCountsNaInput = document.getElementById('aspekModalCountsNa');
            const aspekUrutanInput = document.getElementById('aspekModalUrutan');
            const btnSubmitAspekModal = document.getElementById('btnSubmitAspekModal');

            const syncAspekModalPreview = function () {
                if (!aspekTpSelect) return;
                const opt = aspekTpSelect.options[aspekTpSelect.selectedIndex];
                if (aspekTpHidden && opt) {
                    aspekTpHidden.value = opt.value || '';
                }
                if (aspekTpLabel) {
                    aspekTpLabel.textContent = opt ? (opt.dataset.kode || 'TP') : 'TP';
                }
                if (aspekTpPreview) {
                    aspekTpPreview.textContent = opt ? (opt.dataset.teks || '') : '';
                }
            };

            if (aspekTpSelect) {
                aspekTpSelect.addEventListener('change', syncAspekModalPreview);
                syncAspekModalPreview();
            }

            document.querySelectorAll('.btn-open-aspek-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    closeModal('tpManagerModal');
                    if (tpAspekForm && tpAspekForm.dataset.storeAction) {
                        tpAspekForm.action = tpAspekForm.dataset.storeAction;
                    }
                    if (tpAspekMethod) tpAspekMethod.value = 'POST';
                    if (aspekTitlePrefix) aspekTitlePrefix.textContent = 'Tambah Aspek Penilaian';
                    if (btnSubmitAspekModal) btnSubmitAspekModal.textContent = 'Simpan Aspek';
                    if (aspekTpSelect) {
                        aspekTpSelect.disabled = false;
                        if (btn.dataset.tpId) {
                            aspekTpSelect.value = btn.dataset.tpId;
                        }
                        syncAspekModalPreview();
                    } else if (aspekTpLabel && btn.dataset.tpKode) {
                        aspekTpLabel.textContent = btn.dataset.tpKode;
                    }
                    if (aspekNamaInput) aspekNamaInput.value = '';
                    if (aspekTeknikSelect) aspekTeknikSelect.value = '';
                    if (aspekBobotInput) aspekBobotInput.value = '100';
                    if (aspekCountsNaInput) aspekCountsNaInput.value = '1';
                    if (aspekUrutanInput) aspekUrutanInput.value = '';
                    openModal('tpAspekModal');
                    if (aspekNamaInput) {
                        setTimeout(function () { aspekNamaInput.focus(); }, 30);
                    }
                });
            });

            document.querySelectorAll('.btn-edit-aspek-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    closeModal('tpManagerModal');
                    if (tpAspekForm && btn.dataset.action) {
                        tpAspekForm.action = btn.dataset.action;
                    }
                    if (tpAspekMethod) tpAspekMethod.value = 'PUT';
                    if (aspekTitlePrefix) aspekTitlePrefix.textContent = 'Ubah Nama Aspek';
                    if (btnSubmitAspekModal) btnSubmitAspekModal.textContent = 'Simpan Perubahan';
                    if (aspekTpSelect && btn.dataset.tpId) {
                        aspekTpSelect.value = btn.dataset.tpId;
                        syncAspekModalPreview();
                        aspekTpSelect.disabled = true;
                    }
                    if (aspekTpHidden && btn.dataset.tpId) {
                        aspekTpHidden.value = btn.dataset.tpId;
                    }
                    if (aspekTpLabel && btn.dataset.tpKode) {
                        aspekTpLabel.textContent = btn.dataset.tpKode;
                    }
                    if (aspekNamaInput) aspekNamaInput.value = btn.dataset.nama || '';
                    if (aspekTeknikSelect) aspekTeknikSelect.value = btn.dataset.teknik || '';
                    if (aspekBobotInput) aspekBobotInput.value = btn.dataset.bobot || '100';
                    if (aspekCountsNaInput) aspekCountsNaInput.value = btn.dataset.countsNa || '1';
                    if (aspekUrutanInput) aspekUrutanInput.value = btn.dataset.urutan || '1';
                    openModal('tpAspekModal');
                    if (aspekNamaInput) {
                        setTimeout(function () {
                            aspekNamaInput.focus();
                            aspekNamaInput.select();
                        }, 30);
                    }
                });
            });

            const managerBtn = document.getElementById('btnOpenTpManager');
            if (managerBtn) {
                managerBtn.addEventListener('click', function () {
                    openModal('tpManagerModal');
                });
            }

            const managerSearch = document.getElementById('tpManagerSearchInput');
            if (managerSearch) {
                managerSearch.addEventListener('input', function () {
                    const q = managerSearch.value.trim().toLowerCase();
                    document.querySelectorAll('.js-tp-manager-row').forEach(function (row) {
                        const hay = row.dataset.search || '';
                        row.style.display = q === '' || hay.indexOf(q) !== -1 ? '' : 'none';
                    });
                });
            }

            const toggleRaporForm = document.getElementById('toggleMasukRaporForm');
            const toggleRaporVal = document.getElementById('toggleMasukRaporValue');
            document.querySelectorAll('.btn-toggle-rapor-tp').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!toggleRaporForm || !toggleRaporVal) return;
                    toggleRaporForm.action = btn.dataset.action;
                    toggleRaporVal.value = btn.dataset.next;
                    toggleRaporForm.submit();
                });
            });

            // Tooltip Instan Tujuan Pembelajaran saat hover / fokus / klik pada kode TP
            const tpTooltip = document.getElementById('npTpTooltip');
            let activeTooltipTrigger = null;

            const hideTpTooltip = function () {
                if (!tpTooltip) return;
                activeTooltipTrigger = null;
                tpTooltip.classList.remove('is-visible');
                tpTooltip.setAttribute('aria-hidden', 'true');
            };

            const showTpTooltip = function (trigger) {
                if (!tpTooltip || !trigger) return;
                const kode = trigger.dataset.tpKode || '';
                const elemen = trigger.dataset.tpElemen || '';
                const lingkup = trigger.dataset.tpLingkup || '';
                const teks = trigger.dataset.tpTeks || '';
                if (!kode && !teks) return;

                activeTooltipTrigger = trigger;
                tpTooltip.innerHTML = '';

                const headDiv = document.createElement('div');
                headDiv.className = 'd-flex flex-wrap align-items-center gap-1 mb-1';

                const kodeBadge = document.createElement('span');
                kodeBadge.style.cssText = 'background:#ea580c;color:#fff;padding:0.1rem 0.45rem;border-radius:6px;font-weight:800;font-size:0.7rem;';
                kodeBadge.textContent = kode;
                headDiv.appendChild(kodeBadge);

                const subMeta = lingkup || elemen;
                if (subMeta) {
                    const metaBadge = document.createElement('span');
                    metaBadge.style.cssText = 'background:rgba(255,255,255,0.14);color:#fde68a;padding:0.1rem 0.45rem;border-radius:6px;font-weight:600;font-size:0.68rem;';
                    metaBadge.textContent = subMeta;
                    headDiv.appendChild(metaBadge);
                }

                const bodyDiv = document.createElement('div');
                bodyDiv.style.cssText = 'color:#f1f5f9;font-weight:500;';
                bodyDiv.textContent = teks;

                tpTooltip.appendChild(headDiv);
                tpTooltip.appendChild(bodyDiv);

                tpTooltip.classList.add('is-visible');
                tpTooltip.setAttribute('aria-hidden', 'false');

                const rect = trigger.getBoundingClientRect();
                const tipRect = tpTooltip.getBoundingClientRect();
                const margin = 8;

                let left = rect.left + (rect.width / 2) - (tipRect.width / 2);
                left = Math.max(margin, Math.min(left, window.innerWidth - tipRect.width - margin));

                let top = rect.bottom + 8;
                if (top + tipRect.height > window.innerHeight - margin && rect.top - tipRect.height - 8 > margin) {
                    top = rect.top - tipRect.height - 8;
                }

                tpTooltip.style.left = left + 'px';
                tpTooltip.style.top = top + 'px';
            };

            document.querySelectorAll('.np-tp-tooltip-trigger').forEach(function (trigger) {
                trigger.addEventListener('mouseenter', function () { showTpTooltip(trigger); });
                trigger.addEventListener('mouseleave', hideTpTooltip);
                trigger.addEventListener('focus', function () { showTpTooltip(trigger); });
                trigger.addEventListener('blur', hideTpTooltip);
                trigger.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    if (activeTooltipTrigger === trigger && tpTooltip && tpTooltip.classList.contains('is-visible')) {
                        hideTpTooltip();
                    } else {
                        showTpTooltip(trigger);
                    }
                });
            });

            document.addEventListener('click', function (e) {
                if (activeTooltipTrigger && !e.target.closest('.np-tp-tooltip-trigger')) {
                    hideTpTooltip();
                }
            });

            window.addEventListener('scroll', hideTpTooltip, { capture: true, passive: true });

            const importBtn = document.getElementById('btnOpenImportNilai');
            if (importBtn) {
                importBtn.addEventListener('click', function () {
                    openModal('tpImportNilaiModal');
                });
            }

            const bobotBtn = document.getElementById('btnOpenBobotModal');
            if (bobotBtn) {
                bobotBtn.addEventListener('click', function () {
                    openModal('tpBobotModal');
                });
            }

            const modalBobotTp = document.getElementById('modalBobotTp');
            const modalBobotSumatif = document.getElementById('modalBobotSumatif');
            const modalBobotUjian = document.getElementById('modalBobotUjian');
            const modalBobotTotalBadge = document.getElementById('modalBobotTotalBadge');
            const btnSubmitModalBobot = document.getElementById('btnSubmitModalBobot');

            const syncModalBobotTotal = function () {
                if (!modalBobotTp || !modalBobotSumatif || !modalBobotUjian || !modalBobotTotalBadge) return;
                const tp = parseInt(modalBobotTp.value || '0', 10) || 0;
                const sumatif = parseInt(modalBobotSumatif.value || '0', 10) || 0;
                const ujian = parseInt(modalBobotUjian.value || '0', 10) || 0;
                const total = tp + sumatif + ujian;
                modalBobotTotalBadge.textContent = 'Total: ' + total + '%' + (total === 100 ? '' : ' (Harus 100%)');
                modalBobotTotalBadge.className = 'badge ' + (total === 100 ? 'badge-success' : 'badge-danger');
                if (btnSubmitModalBobot) {
                    btnSubmitModalBobot.disabled = total !== 100;
                }
            };

            if (modalBobotTp && modalBobotSumatif && modalBobotUjian) {
                [modalBobotTp, modalBobotSumatif, modalBobotUjian].forEach(function (inp) {
                    inp.addEventListener('input', syncModalBobotTotal);
                });
                document.querySelectorAll('.js-modal-bobot-preset').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        modalBobotTp.value = btn.dataset.tp;
                        modalBobotSumatif.value = btn.dataset.sumatif || '0';
                        modalBobotUjian.value = btn.dataset.ujian;
                        syncModalBobotTotal();
                    });
                });
                syncModalBobotTotal();
            }

            const sumatifManagerBtn = document.getElementById('btnOpenSumatifManager');
            if (sumatifManagerBtn) {
                sumatifManagerBtn.addEventListener('click', function () {
                    openModal('sumatifManagerModal');
                });
            }

            const sumatifBabForm = document.getElementById('sumatifBabForm');
            const sumatifBabMethod = document.getElementById('sumatifBabMethod');
            const sumatifBabTitle = document.getElementById('sumatifBabModalTitle');
            const sumatifNamaInput = document.getElementById('sumatifNamaBabInput');
            const sumatifKetInput = document.getElementById('sumatifKeteranganInput');
            const sumatifUrutanInput = document.getElementById('sumatifUrutanInput');

            document.querySelectorAll('.btn-open-sumatif-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    closeModal('sumatifManagerModal');
                    if (sumatifBabForm) {
                        sumatifBabForm.action = sumatifBabForm.dataset.storeAction;
                    }
                    if (sumatifBabMethod) sumatifBabMethod.value = 'POST';
                    if (sumatifBabTitle) {
                        sumatifBabTitle.textContent = 'Tambah Sumatif Bab (' + (isMid ? 'Mid Semester' : 'Semester') + ')';
                    }
                    if (sumatifNamaInput) sumatifNamaInput.value = '';
                    if (sumatifKetInput) sumatifKetInput.value = '';
                    if (sumatifUrutanInput) {
                        sumatifUrutanInput.value = sumatifUrutanInput.dataset.defaultUrutan || '1';
                    }
                    openModal('sumatifBabModal');
                });
            });

            document.querySelectorAll('.btn-edit-sumatif-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    closeModal('sumatifManagerModal');
                    if (sumatifBabForm && btn.dataset.action) {
                        sumatifBabForm.action = btn.dataset.action;
                    }
                    if (sumatifBabMethod) sumatifBabMethod.value = 'PUT';
                    if (sumatifBabTitle) {
                        sumatifBabTitle.textContent = 'Ubah Sumatif Bab (' + (btn.dataset.nama || '') + ')';
                    }
                    if (sumatifNamaInput) sumatifNamaInput.value = btn.dataset.nama || '';
                    if (sumatifKetInput) sumatifKetInput.value = btn.dataset.keterangan || '';
                    if (sumatifUrutanInput) sumatifUrutanInput.value = btn.dataset.urutan || '1';
                    openModal('sumatifBabModal');
                });
            });

            const deleteSumatifForm = document.getElementById('deleteSumatifForm');
            document.querySelectorAll('.btn-delete-sumatif').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!deleteSumatifForm) return;
                    if (confirm('Hapus kolom Sumatif Bab "' + btn.dataset.label + '" beserta nilainya? NA Rapor akan dihitung ulang otomatis.')) {
                        deleteSumatifForm.action = btn.dataset.action;
                        deleteSumatifForm.submit();
                    }
                });
            });

            const deleteForm = document.getElementById('deleteAspekForm');
            document.querySelectorAll('.btn-delete-aspek').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!deleteForm) return;
                    if (confirm('Hapus aspek "' + btn.dataset.label + '" beserta nilai aspeknya? NA TP akan dihitung ulang otomatis.')) {
                        deleteForm.action = btn.dataset.action;
                        deleteForm.submit();
                    }
                });
            });

            if (!table) {
                return;
            }

            const resolvePredikat = function (score) {
                const rounded = Math.round(score);
                for (let i = 0; i < predikats.length; i++) {
                    if (rounded >= predikats[i].nilai_min && rounded <= predikats[i].nilai_max) {
                        return predikats[i];
                    }
                }
                return null;
            };

            const recalculateRow = function (tr) {
                const studentId = tr.dataset.studentRow;
                const includedScores = [];

                tr.querySelectorAll('[data-readonly-mid-tp-cell]').forEach(function (roCell) {
                    if (roCell.dataset.includedRapor !== '1') {
                        return;
                    }
                    const rawMid = (roCell.dataset.midScore || '').trim();
                    if (rawMid !== '' && !isNaN(Number(rawMid))) {
                        includedScores.push(Number(rawMid));
                    }
                });

                const naCells = tr.querySelectorAll('[data-natp-cell]');

                naCells.forEach(function (naCell) {
                    const parts = naCell.dataset.natpCell.split(':');
                    const tpId = parts[1];
                    const isIncluded = naCell.dataset.includedRapor === '1';

                    const inputs = tr.querySelectorAll('input.np-score-input[data-tp-id="' + tpId + '"]');
                    let sum = 0;
                    let weightSum = 0;
                    let count = 0;

                    inputs.forEach(function (inp) {
                        const raw = inp.value.trim();
                        if (raw === '' || isNaN(Number(raw))) {
                            inp.classList.remove('is-below-kkm');
                            return;
                        }
                        const num = Math.max(0, Math.min(100, Number(raw)));
                        inp.classList.toggle('is-below-kkm', num < kkmNilai);

                        if (inp.dataset.countsNa === '1') {
                            const w = Number(inp.dataset.weight || 100);
                            sum += num * w;
                            weightSum += w;
                            count++;
                        }
                    });

                    const span = naCell.querySelector('.js-natp-value');
                    let currentTpScore = null;

                    if (count > 0 && weightSum > 0) {
                        currentTpScore = Math.round(sum / weightSum);
                        if (span) span.textContent = currentTpScore;
                        naCell.classList.toggle('is-below-kkm', currentTpScore < kkmNilai);
                    } else {
                        if (span) span.textContent = '-';
                        naCell.classList.remove('is-below-kkm');
                    }

                    if (isIncluded && currentTpScore !== null) {
                        includedScores.push(currentTpScore);
                    }
                });

                const avgTp = includedScores.length > 0
                    ? Math.round(includedScores.reduce((a, b) => a + b, 0) / includedScores.length)
                    : null;

                const sumatifValues = [];
                tr.querySelectorAll('[data-readonly-mid-sumatif-cell]').forEach(function (roSumCell) {
                    const rawMidSum = (roSumCell.dataset.midScore || '').trim();
                    if (rawMidSum !== '' && !isNaN(Number(rawMidSum))) {
                        sumatifValues.push(Number(rawMidSum));
                    }
                });
                tr.querySelectorAll('input.np-sumatif-input').forEach(function (inp) {
                    const rawSumatif = inp.value.trim();
                    if (rawSumatif !== '' && !isNaN(Number(rawSumatif))) {
                        const num = Math.max(0, Math.min(100, Number(rawSumatif)));
                        inp.classList.toggle('is-below-kkm', num < kkmNilai);
                        sumatifValues.push(num);
                    } else {
                        inp.classList.remove('is-below-kkm');
                    }
                });

                const avgSumatif = sumatifValues.length > 0
                    ? Math.round(sumatifValues.reduce((a, b) => a + b, 0) / sumatifValues.length)
                    : null;

                const rataSumatifCell = tr.querySelector('[data-ratasumatif-cell="' + studentId + '"]');
                if (rataSumatifCell) {
                    rataSumatifCell.textContent = avgSumatif !== null ? avgSumatif : '-';
                    rataSumatifCell.classList.toggle('is-below-kkm', avgSumatif !== null && avgSumatif < kkmNilai);
                }

                let ujianScore = null;
                const ujianInput = tr.querySelector('input.np-ujian-input');
                if (ujianInput) {
                    const rawUjian = ujianInput.value.trim();
                    if (rawUjian !== '' && !isNaN(Number(rawUjian))) {
                        ujianScore = Math.max(0, Math.min(100, Number(rawUjian)));
                        ujianInput.classList.toggle('is-below-kkm', ujianScore < kkmNilai);
                    } else {
                        ujianInput.classList.remove('is-below-kkm');
                    }
                }

                const naRaporCell = tr.querySelector('[data-narapor-cell="' + studentId + '"]');
                const predCell = tr.querySelector('[data-predikat-cell="' + studentId + '"]');

                const activeComponents = [
                    { value: avgTp, weight: bobotTp },
                    { value: avgSumatif, weight: bobotSumatif },
                    { value: ujianScore, weight: bobotUjian },
                ].filter(function (comp) {
                    return comp.value !== null && comp.weight > 0;
                });

                let avgRapor = null;
                if (activeComponents.length > 0) {
                    const totalWeight = activeComponents.reduce((acc, c) => acc + c.weight, 0);
                    const weightedSum = activeComponents.reduce((acc, c) => acc + (c.value * c.weight), 0);
                    if (totalWeight > 0) {
                        avgRapor = Math.round(weightedSum / totalWeight);
                    }
                }

                if (avgRapor === null) {
                    if (naRaporCell) naRaporCell.textContent = '-';
                    if (predCell) predCell.innerHTML = '<span class="text-muted">-</span>';
                    return;
                }

                if (naRaporCell) naRaporCell.textContent = avgRapor;

                const matched = resolvePredikat(avgRapor);
                if (predCell) {
                    if (matched) {
                        const cls = matched.is_tuntas ? 'badge-success' : 'badge-danger';
                        predCell.innerHTML = '<span class="badge ' + cls + '">' + matched.predikat + '</span>';
                    } else {
                        predCell.innerHTML = '<span class="text-muted">-</span>';
                    }
                }
            };

            table.addEventListener('input', function (e) {
                if (!e.target.classList.contains('np-score-input')) {
                    return;
                }
                const tr = e.target.closest('tr');
                if (tr) recalculateRow(tr);
            });

            const studentSearchInput = document.getElementById('npStudentSearch');
            if (studentSearchInput) {
                studentSearchInput.addEventListener('input', function () {
                    const q = studentSearchInput.value.trim().toLowerCase();
                    table.querySelectorAll('tbody tr[data-student-row]').forEach(function (tr) {
                        const hay = tr.dataset.studentSearch || '';
                        tr.style.display = q === '' || hay.indexOf(q) !== -1 ? '' : 'none';
                    });
                });
            }

            // Toggle Tampilkan / Sembunyikan Kolom Nilai Mid (Read-Only) di Tab Semester
            const toggleMidColsBtn = document.getElementById('btnToggleMidCols');
            const sumatifGroupTh = document.getElementById('npSumatifGroupTh');
            if (toggleMidColsBtn) {
                const midCount = toggleMidColsBtn.dataset.midCount || '0';
                const applyMidCols = function (hideMid) {
                    table.classList.toggle('is-hide-mid', hideMid);
                    toggleMidColsBtn.classList.toggle('is-active', hideMid);
                    toggleMidColsBtn.setAttribute('aria-pressed', hideMid ? 'true' : 'false');
                    toggleMidColsBtn.innerHTML = hideMid
                        ? '<i class="fa fa-eye-slash mr-1"></i> Nilai Mid: Disembunyikan (' + midCount + ')'
                        : '<i class="fa fa-eye mr-1"></i> Nilai Mid: Tampil (' + midCount + ')';
                    if (sumatifGroupTh) {
                        const targetColspan = hideMid
                            ? parseInt(sumatifGroupTh.dataset.semColspan || '1', 10)
                            : parseInt(sumatifGroupTh.dataset.fullColspan || '1', 10);
                        sumatifGroupTh.colSpan = Math.max(1, targetColspan);
                    }
                    try {
                        localStorage.setItem('np_nilai_tp_hide_mid', hideMid ? '1' : '0');
                    } catch (err) {}
                };

                let initialHideMid = false;
                try {
                    initialHideMid = localStorage.getItem('np_nilai_tp_hide_mid') === '1';
                } catch (err) {}
                applyMidCols(initialHideMid);

                toggleMidColsBtn.addEventListener('click', function () {
                    applyMidCols(!table.classList.contains('is-hide-mid'));
                });
            }

            // Navigasi Excel vertikal menggunakan Enter / ArrowUp / ArrowDown pada kolom aspek yang sama
            table.addEventListener('keydown', function (e) {
                if (!e.target.classList.contains('np-score-input')) {
                    return;
                }
                if (e.key !== 'Enter' && e.key !== 'ArrowDown' && e.key !== 'ArrowUp') {
                    return;
                }
                e.preventDefault();
                const aspekId = e.target.dataset.aspekId;
                const allCols = Array.from(table.querySelectorAll('input.np-score-input[data-aspek-id="' + aspekId + '"]'))
                    .filter(function (inp) {
                        const row = inp.closest('tr');
                        return !row || row.style.display !== 'none';
                    });
                const idx = allCols.indexOf(e.target);
                const nextIdx = e.key === 'ArrowUp' ? idx - 1 : idx + 1;
                if (nextIdx >= 0 && nextIdx < allCols.length) {
                    allCols[nextIdx].focus();
                    allCols[nextIdx].select();
                }
            });
        });
    </script>
@endpush

