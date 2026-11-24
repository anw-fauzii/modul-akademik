@extends('layouts.appui')

@section('title')
    <title>Rekap &amp; Deskripsi Rapor T2Q (SD) - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @pushOnce('css')
        <style>
            .t2q-page-wrap {
                margin: 0 auto;
            }

            /* Header Minimalis */
            .t2q-head-bar {
                display: flex;
                flex-wrap: wrap;
                align-items: flex-end;
                justify-content: space-between;
                gap: 0.9rem;
                margin-bottom: 0.95rem;
            }

            .t2q-head-left {
                display: flex;
                align-items: flex-start;
                gap: 0.8rem;
                min-width: 0;
            }

            .t2q-head-icon {
                width: 42px;
                height: 42px;
                border-radius: 12px;
                background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
                border: 1px solid #fed7aa;
                color: #ea580c;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 1.02rem;
                flex-shrink: 0;
            }

            .t2q-head-title {
                margin: 0;
                font-size: 1.2rem;
                font-weight: 800;
                color: #0f172a;
                letter-spacing: -0.02em;
                line-height: 1.25;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.45rem;
            }

            .t2q-unit-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.16rem 0.6rem;
                border-radius: 999px;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #c2410c;
                font-size: 0.69rem;
                font-weight: 700;
            }

            .t2q-head-sub {
                margin: 0.24rem 0 0;
                font-size: 0.78rem;
                color: #64748b;
                line-height: 1.45;
            }

            .t2q-seg-control {
                display: inline-flex;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 10px;
                padding: 3px;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            }

            .t2q-seg-btn {
                padding: 0.36rem 0.82rem;
                border-radius: 7px;
                font-size: 0.75rem;
                font-weight: 700;
                color: #475569;
                text-decoration: none !important;
                transition: all 0.15s ease;
                white-space: nowrap;
            }

            .t2q-seg-btn:hover {
                color: #0f172a;
                background: #f8fafc;
            }

            .t2q-seg-btn.is-active {
                background: #ea580c;
                color: #ffffff;
                box-shadow: 0 2px 6px rgba(234, 88, 12, 0.25);
            }

            /* Ringkasan Statistik Ringkas */
            .t2q-mini-stats {
                display: grid;
                grid-template-columns: repeat(2, minmax(0, 1fr));
                gap: 0.6rem;
                margin-bottom: 0.8rem;
            }

            @media (min-width: 992px) {
                .t2q-mini-stats {
                    grid-template-columns: repeat(4, minmax(0, 1fr));
                }
            }

            .t2q-stat-tile {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.65rem 0.88rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.6rem;
            }

            .t2q-stat-label {
                font-size: 0.67rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #64748b;
            }

            .t2q-stat-val {
                font-size: 1.1rem;
                font-weight: 800;
                color: #0f172a;
                line-height: 1.2;
                margin-top: 0.1rem;
                font-variant-numeric: tabular-nums;
            }

            .t2q-stat-sub {
                font-size: 0.67rem;
                color: #64748b;
                margin-top: 0.06rem;
            }

            .t2q-stat-dot {
                width: 30px;
                height: 30px;
                border-radius: 8px;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 0.8rem;
                flex-shrink: 0;
            }

            /* Panduan Ringkas & Skala 75 */
            .t2q-guide-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.62rem 0.9rem;
                margin-bottom: 0.8rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.55rem 1rem;
            }

            .t2q-steps-flow {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
                font-size: 0.72rem;
                color: #475569;
            }

            .t2q-step-badge {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                background: #f8fafc;
                border: 1px solid #e2e8f0;
                border-radius: 7px;
                padding: 0.16rem 0.48rem;
                color: #334155;
                font-weight: 600;
            }

            .t2q-step-num {
                width: 15px;
                height: 15px;
                border-radius: 999px;
                background: #ea580c;
                color: #ffffff;
                font-size: 0.61rem;
                font-weight: 800;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .t2q-scale-row {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
            }

            .t2q-scale-chip {
                font-size: 0.67rem;
                font-weight: 700;
                border-radius: 6px;
                padding: 0.14rem 0.45rem;
                display: inline-flex;
                align-items: center;
                gap: 0.25rem;
            }

            /* Toolbar 1 Baris Rapi */
            .t2q-toolbar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.62rem 0.9rem;
                margin-bottom: 0.85rem;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem 1rem;
            }

            .t2q-tingkat-pills {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.35rem;
                min-width: 0;
            }

            .t2q-pill-tab {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.3rem 0.68rem;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #475569;
                font-size: 0.73rem;
                font-weight: 700;
                text-decoration: none !important;
                transition: all 0.14s ease;
            }

            .t2q-pill-tab:hover {
                border-color: #fdba74;
                color: #c2410c;
                background: #fff7ed;
            }

            .t2q-pill-tab.is-active {
                background: #0f172a;
                border-color: #0f172a;
                color: #ffffff;
            }

            .t2q-pill-count {
                padding: 0.04rem 0.38rem;
                border-radius: 999px;
                font-size: 0.64rem;
                background: rgba(15, 23, 42, 0.08);
                color: inherit;
            }

            .t2q-pill-tab.is-active .t2q-pill-count {
                background: rgba(255, 255, 255, 0.18);
                color: #ffffff;
            }

            .t2q-toolbar-right {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.45rem;
                margin-left: auto;
            }

            .t2q-toolbar-right .wk-search-wrap {
                width: 100%;
                max-width: none;
                flex: 1 1 210px;
            }

            .t2q-toolbar-right .wk-search-input {
                width: 100%;
                height: 34px;
                font-size: 0.75rem;
            }

            .t2q-sync-form {
                margin: 0;
                flex-shrink: 0;
            }

            @media (min-width: 640px) {
                .t2q-toolbar-right {
                    flex-wrap: nowrap;
                }

                .t2q-toolbar-right .wk-search-wrap {
                    width: 220px;
                    flex: 0 0 220px;
                }
            }

            @media (max-width: 639.98px) {
                .t2q-toolbar-right {
                    width: 100%;
                }

                .t2q-toolbar-right .wk-search-wrap {
                    flex: 1 1 100%;
                }

                .t2q-toolbar-right .t2q-ghost-btn,
                .t2q-toolbar-right .t2q-sync-form {
                    flex: 1 1 auto;
                }

                .t2q-toolbar-right .t2q-ghost-btn,
                .t2q-toolbar-right .t2q-sync-btn {
                    width: 100%;
                    justify-content: center;
                }
            }

            .t2q-ghost-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                height: 34px;
                padding: 0 0.75rem;
                border-radius: 9px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                color: #334155;
                font-size: 0.73rem;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.14s ease;
                white-space: nowrap;
                flex-shrink: 0;
            }

            .t2q-ghost-btn:hover {
                background: #f8fafc;
                border-color: #cbd5e1;
                color: #0f172a;
            }

            .t2q-sync-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                height: 34px;
                padding: 0 0.8rem;
                border-radius: 9px;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #c2410c;
                font-size: 0.73rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.14s ease;
                white-space: nowrap;
                flex-shrink: 0;
            }

            .t2q-sync-btn:hover {
                background: #ffedd5;
                border-color: #fdba74;
            }

            /* Daftar Siswa Compact Expandable (Tanpa overflow-hidden agar dropdown bebas) */
            .t2q-list-surface {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 14px;
                overflow: visible;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
            }

            .t2q-list-head {
                display: none;
            }

            @media (min-width: 992px) {
                .t2q-list-head {
                    display: grid;
                    grid-template-columns: 36px minmax(200px, 1.2fr) minmax(175px, 1fr) minmax(175px, 1fr) minmax(185px, 1.05fr) 104px;
                    gap: 0.75rem;
                    align-items: center;
                    padding: 0.62rem 1rem;
                    background: #f8fafc;
                    border-bottom: 1px solid #e2e8f0;
                    border-radius: 13px 13px 0 0;
                    font-size: 0.67rem;
                    font-weight: 800;
                    text-transform: uppercase;
                    letter-spacing: 0.05em;
                    color: #64748b;
                }
            }

            .t2q-student-item {
                position: relative;
                z-index: 1;
                border-bottom: 1px solid #f1f5f9;
                transition: background-color 0.14s ease;
            }

            .t2q-student-item:last-child {
                border-bottom: 0;
            }

            .t2q-student-item:last-child > .t2q-row-summary {
                border-radius: 0 0 13px 13px;
            }

            .t2q-student-item.is-expanded > .t2q-row-summary {
                border-radius: 0;
                background: #fffcf8;
                border-bottom: 1px dashed #fed7aa;
            }

            .t2q-student-item.has-open-ms {
                z-index: 70;
            }

            .t2q-row-summary {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.65rem;
                padding: 0.68rem 1rem;
                align-items: center;
                cursor: pointer;
                transition: background-color 0.12s ease;
            }

            .t2q-row-summary:hover {
                background: #fafbfc;
            }

            @media (min-width: 992px) {
                .t2q-row-summary {
                    grid-template-columns: 36px minmax(200px, 1.2fr) minmax(175px, 1fr) minmax(175px, 1fr) minmax(185px, 1.05fr) 104px;
                    gap: 0.75rem;
                }
            }

            .t2q-row-num {
                display: none;
            }

            @media (min-width: 992px) {
                .t2q-row-num {
                    display: block;
                    font-size: 0.74rem;
                    font-weight: 700;
                    color: #94a3b8;
                }
            }

            .t2q-student-cell {
                display: flex;
                align-items: center;
                gap: 0.62rem;
                min-width: 0;
            }

            .t2q-student-avatar {
                width: 32px;
                height: 32px;
                border-radius: 999px;
                object-fit: cover;
                flex-shrink: 0;
            }

            .t2q-student-initials {
                width: 32px;
                height: 32px;
                border-radius: 999px;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                color: #334155;
                font-size: 0.73rem;
                font-weight: 800;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }

            .t2q-cell-block {
                min-width: 0;
                display: flex;
                flex-direction: column;
                gap: 0.14rem;
            }

            .t2q-mobile-label {
                display: inline-block;
                font-size: 0.62rem;
                font-weight: 800;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #94a3b8;
            }

            @media (min-width: 992px) {
                .t2q-mobile-label {
                    display: none;
                }
            }

            .t2q-mini-score {
                display: inline-flex;
                align-items: center;
                gap: 0.28rem;
                font-size: 0.69rem;
                font-weight: 700;
                border-radius: 6px;
                padding: 0.1rem 0.42rem;
                font-variant-numeric: tabular-nums;
                flex-shrink: 0;
            }

            .t2q-summary-tags {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.25rem;
            }

            .t2q-summary-pill {
                font-size: 0.67rem;
                font-weight: 600;
                border-radius: 6px;
                padding: 0.1rem 0.42rem;
                background: #f1f5f9;
                color: #475569;
                white-space: nowrap;
            }

            .t2q-summary-pill.has-val {
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #9a3412;
            }

            .t2q-toggle-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 0.35rem;
                height: 30px;
                padding: 0 0.72rem;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                background: #ffffff;
                color: #334155;
                font-size: 0.72rem;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.14s ease;
                white-space: nowrap;
            }

            .t2q-toggle-btn:hover {
                border-color: #fdba74;
                color: #c2410c;
                background: #fff7ed;
            }

            .t2q-student-item.is-expanded .t2q-toggle-btn {
                background: #ea580c;
                border-color: #ea580c;
                color: #ffffff;
            }

            /* Panel Editor Expand-In-Place */
            .t2q-row-drawer {
                display: none;
                background: #f8fafc;
                padding: 0.9rem 1rem 0.95rem;
                border-radius: 0 0 13px 13px;
            }

            .t2q-student-item.is-expanded .t2q-row-drawer {
                display: block;
            }

            .t2q-drawer-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.85rem;
            }

            @media (min-width: 992px) {
                .t2q-drawer-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            .t2q-channel-box {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                padding: 0.85rem 0.95rem;
                display: flex;
                flex-direction: column;
                gap: 0.65rem;
                min-width: 0;
            }

            .t2q-snapshot-bar {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem;
                padding-bottom: 0.58rem;
                border-bottom: 1px dashed #e2e8f0;
            }

            .t2q-channel-kicker {
                font-size: 0.64rem;
                font-weight: 800;
                letter-spacing: 0.06em;
                text-transform: uppercase;
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                margin-bottom: 0.14rem;
            }

            .t2q-materi-title {
                font-size: 0.83rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.3;
            }

            .t2q-materi-meta {
                font-size: 0.67rem;
                color: #64748b;
                margin-top: 0.06rem;
            }

            .t2q-score-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.38rem;
                padding: 0.24rem 0.58rem;
                border-radius: 8px;
                font-variant-numeric: tabular-nums;
                flex-shrink: 0;
            }

            .t2q-score-num {
                font-size: 0.92rem;
                font-weight: 800;
                line-height: 1;
            }

            .t2q-score-code {
                font-size: 0.65rem;
                font-weight: 700;
                padding-left: 0.36rem;
                border-left: 1px solid currentColor;
            }

            .t2q-field-label {
                font-size: 0.7rem;
                font-weight: 700;
                color: #334155;
                margin-bottom: 0.24rem;
                display: flex;
                align-items: center;
                justify-content: space-between;
            }

            .t2q-field-hint {
                font-size: 0.65rem;
                font-weight: 500;
                color: #94a3b8;
            }

            /* Searchable Multi-Select Trigger FIXED 1-BARIS (38px, tidak melar!) */
            .t2q-ms {
                position: relative;
            }

            .t2q-ms-trigger {
                width: 100%;
                height: 38px;
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 9px;
                padding: 0 2rem 0 0.58rem;
                display: flex;
                align-items: center;
                justify-content: flex-start;
                gap: 0.38rem;
                cursor: pointer;
                text-align: left;
                position: relative;
                overflow: hidden;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .t2q-ms-trigger:hover:not(.is-disabled) {
                border-color: #94a3b8;
            }

            .t2q-ms.is-open .t2q-ms-trigger {
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.13);
            }

            .t2q-ms-trigger.is-disabled {
                background: #f8fafc;
                cursor: not-allowed;
                opacity: 0.78;
            }

            .t2q-ms-summary {
                display: flex;
                align-items: center;
                gap: 0.38rem;
                min-width: 0;
                flex: 1 1 auto;
                overflow: hidden;
            }

            .t2q-ms-placeholder {
                font-size: 0.74rem;
                color: #94a3b8;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                user-select: none;
            }

            .t2q-ms-badge-count {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                background: #ea580c;
                color: #ffffff;
                font-size: 0.66rem;
                font-weight: 800;
                border-radius: 6px;
                padding: 0.12rem 0.44rem;
                white-space: nowrap;
                flex-shrink: 0;
            }

            .t2q-ms-preview-text {
                font-size: 0.73rem;
                font-weight: 600;
                color: #1e293b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                min-width: 0;
            }

            .t2q-ms-caret {
                position: absolute;
                right: 0.62rem;
                top: 50%;
                transform: translateY(-50%);
                color: #64748b;
                font-size: 0.68rem;
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
                min-width: 260px;
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 12px;
                box-shadow: 0 20px 38px -10px rgba(15, 23, 42, 0.24);
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

            .t2q-ms-selected-drawer {
                padding: 0.45rem 0.58rem;
                border-bottom: 1px solid #f1f5f9;
                background: #fffaf5;
                display: flex;
                flex-wrap: wrap;
                gap: 0.25rem;
                max-height: 92px;
                overflow-y: auto;
            }

            .t2q-ms-tag {
                display: inline-flex;
                align-items: center;
                gap: 0.26rem;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #9a3412;
                font-size: 0.67rem;
                font-weight: 600;
                border-radius: 6px;
                padding: 0.1rem 0.38rem;
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

            .t2q-ms-search-wrap {
                padding: 0.48rem 0.56rem;
                border-bottom: 1px solid #f1f5f9;
                background: #f8fafc;
                position: relative;
            }

            .t2q-ms-search-wrap i {
                position: absolute;
                left: 1.12rem;
                top: 50%;
                transform: translateY(-50%);
                color: #94a3b8;
                font-size: 0.71rem;
                pointer-events: none;
            }

            .t2q-ms-search {
                width: 100%;
                height: 31px;
                border: 1px solid #cbd5e1;
                border-radius: 8px;
                background: #ffffff;
                padding: 0.22rem 0.58rem 0.22rem 1.8rem;
                font-size: 0.73rem;
                color: #0f172a;
                outline: 0;
            }

            .t2q-ms-search:focus {
                border-color: #ea580c;
                box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.12);
            }

            .t2q-ms-list {
                max-height: 195px;
                overflow-y: auto;
                padding: 0.28rem;
            }

            .t2q-ms-option {
                width: 100%;
                border: 0;
                background: transparent;
                border-radius: 7px;
                padding: 0.36rem 0.52rem;
                display: flex;
                align-items: center;
                gap: 0.48rem;
                font-size: 0.73rem;
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
                font-size: 0.56rem;
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
                border-radius: 7px;
                padding: 0.38rem 0.52rem;
                font-size: 0.72rem;
                font-weight: 600;
                text-align: left;
                cursor: pointer;
                margin-bottom: 0.24rem;
            }

            .t2q-ms-custom-add:hover {
                background: #ffedd5;
            }

            .t2q-ms-footer {
                padding: 0.36rem 0.58rem;
                border-top: 1px solid #f1f5f9;
                background: #f8fafc;
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 0.67rem;
            }

            .t2q-desc-textarea {
                font-size: 0.78rem;
                line-height: 1.52;
                border-radius: 9px;
                border: 1px solid #e2e8f0;
                background: #f8fafc;
                color: #0f172a;
                padding: 0.5rem 0.68rem;
                min-height: 76px;
                resize: vertical;
                transition: background-color 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .t2q-desc-textarea:focus {
                background: #ffffff;
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.11);
            }

            .t2q-desc-textarea:disabled {
                background: #f8fafc;
                color: #475569;
            }

            .t2q-drawer-foot {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.5rem;
                margin-top: 0.72rem;
                padding-top: 0.62rem;
                border-top: 1px solid #e2e8f0;
            }

            .t2q-save-bar {
                position: sticky;
                bottom: 0.75rem;
                z-index: 20;
                background: rgba(255, 255, 255, 0.96);
                backdrop-filter: blur(8px);
                border: 1px solid #e2e8f0;
                border-radius: 13px;
                padding: 0.72rem 1.05rem;
                box-shadow: 0 12px 28px -10px rgba(15, 23, 42, 0.18);
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.7rem;
                margin-top: 0.85rem;
            }

            .t2q-dirty-badge {
                display: none;
                align-items: center;
                gap: 0.35rem;
                background: #fff7ed;
                border: 1px solid #fed7aa;
                color: #c2410c;
                font-size: 0.7rem;
                font-weight: 700;
                border-radius: 999px;
                padding: 0.16rem 0.58rem;
            }

            .t2q-dirty-badge.is-visible {
                display: inline-flex;
            }
        </style>
    @endPushOnce

    @php
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
        $komentarOptions = array_values(array_unique(array_filter(
            $komentarOptions ?? \App\Models\Admin\DataMaster\KomentarT2q::DEFAULT_COMMENTS,
            static fn ($v): bool => filled($v)
        )));
    @endphp

    <div class="app-main__inner">
        <div class="t2q-page-wrap">
            <!-- HEADER MINIMALIS -->
            <div class="t2q-head-bar">
                <div class="t2q-head-left">
                    <div class="t2q-head-icon">
                        <i class="fa fa-file-signature"></i>
                    </div>
                    <div>
                        <h3 class="t2q-head-title">
                            <span>Rekap &amp; Deskripsi Rapor T2Q</span>
                            <span class="t2q-unit-pill">
                                <i class="fa fa-school"></i> Khusus SD &bull; {{ $periodeLabel }}
                            </span>
                        </h3>
                        <p class="t2q-head-sub">
                            @if ($activeTahunAjaran)
                                <strong>{{ $activeTahunAjaran->full_label }}</strong> &bull;
                            @endif
                            Pembimbing: <strong>{{ $displayName }}</strong>. Nilai &amp; materi otomatis diambil dari setoran Yaumiyah terakhir.
                            @if ($nonSdCount > 0)
                                ({{ $nonSdCount }} siswa non-SD disembunyikan)
                            @endif
                        </p>
                    </div>
                </div>

                <div class="t2q-seg-control" role="tablist" aria-label="Pilih Periode Rapor T2Q">
                    <a href="{{ route('guru-t2q.rapor.index', array_filter(['periode' => 'mid', 'tingkat' => $selectedTingkat])) }}"
                        class="t2q-seg-btn {{ $isMid ? 'is-active' : '' }}">
                        Mid Semester
                    </a>
                    <a href="{{ route('guru-t2q.rapor.index', array_filter(['periode' => 'semester', 'tingkat' => $selectedTingkat])) }}"
                        class="t2q-seg-btn {{ ! $isMid ? 'is-active' : '' }}">
                        Semester
                    </a>
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
                <!-- RINGKASAN PROGRES -->
                <div class="t2q-mini-stats">
                    <div class="t2q-stat-tile">
                        <div>
                            <div class="t2q-stat-label">Siswa SD</div>
                            <div class="t2q-stat-val">{{ $stats['total_sd'] }}</div>
                            <div class="t2q-stat-sub">{{ count($tingkatTabs) }} tingkat binaan</div>
                        </div>
                        <span class="t2q-stat-dot" style="background: #eff6ff; color: #2563eb;">
                            <i class="fa fa-users"></i>
                        </span>
                    </div>

                    <div class="t2q-stat-tile">
                        <div>
                            <div class="t2q-stat-label">Tahsin Terisi</div>
                            <div class="t2q-stat-val" style="color: #c2410c;">
                                {{ $stats['tahsin_ready'] }} <span class="text-muted" style="font-size: 0.78rem; font-weight: 600;">/ {{ $stats['total_sd'] }}</span>
                            </div>
                            <div class="t2q-stat-sub">Dari setoran terakhir</div>
                        </div>
                        <span class="t2q-stat-dot" style="background: #fff7ed; color: #ea580c;">
                            <i class="fa fa-book-open"></i>
                        </span>
                    </div>

                    <div class="t2q-stat-tile">
                        <div>
                            <div class="t2q-stat-label">Tahfidz Terisi</div>
                            <div class="t2q-stat-val" style="color: #047857;">
                                {{ $stats['tahfizh_ready'] }} <span class="text-muted" style="font-size: 0.78rem; font-weight: 600;">/ {{ $stats['total_sd'] }}</span>
                            </div>
                            <div class="t2q-stat-sub">Dari setoran terakhir</div>
                        </div>
                        <span class="t2q-stat-dot" style="background: #ecfdf5; color: #059669;">
                            <i class="fa fa-quran"></i>
                        </span>
                    </div>

                    <div class="t2q-stat-tile">
                        <div>
                            <div class="t2q-stat-label">Deskripsi Siap</div>
                            <div class="t2q-stat-val" style="color: #6d28d9;">
                                {{ $stats['deskripsi_saved'] }} <span class="text-muted" style="font-size: 0.78rem; font-weight: 600;">/ {{ $stats['total_sd'] }}</span>
                            </div>
                            <div class="t2q-stat-sub">
                                {{ $stats['locked_count'] > 0 ? $stats['locked_count'].' terkunci final' : 'Belum difinalkan' }}
                            </div>
                        </div>
                        <span class="t2q-stat-dot" style="background: #f5f3ff; color: #7c3aed;">
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

                <!-- ALUR PENGISIAN SINGKAT & SKALA PREDIKAT -->
                <div class="t2q-guide-card">
                    <div class="t2q-steps-flow">
                        <span class="t2q-step-badge"><span class="t2q-step-num">1</span> Klik baris siswa</span>
                        <i class="fa fa-chevron-right text-muted" style="font-size: 0.58rem;"></i>
                        <span class="t2q-step-badge"><span class="t2q-step-num">2</span> Pilih kelebihan / kekurangan</span>
                        <i class="fa fa-chevron-right text-muted" style="font-size: 0.58rem;"></i>
                        <span class="t2q-step-badge"><span class="t2q-step-num">3</span> Simpan &amp; Finalkan</span>
                    </div>

                    <div class="t2q-scale-row">
                        <span class="small font-weight-bold text-muted mr-1" style="font-size: 0.68rem;">
                            Skala Predikat Rapor T2Q (Min. 75):
                        </span>
                        @foreach (array_reverse($predikatRanges) as $range)
                            @php
                                $pillCss = match ($range['predikat']) {
                                    'SAB' => 'background: #ecfdf5; color: #047857;',
                                    'BSH' => 'background: #eff6ff; color: #1d4ed8;',
                                    'SB' => 'background: #ecfeff; color: #0e7490;',
                                    default => 'background: #fffbeb; color: #b45309;',
                                };
                            @endphp
                            <span class="t2q-scale-chip" style="{{ $pillCss }}" title="{{ $range['keterangan'] }}">
                                <strong>{{ $range['predikat'] }}</strong> {{ $range['nilai_min'] }}–{{ $range['nilai_max'] }}
                            </span>
                        @endforeach
                    </div>
                </div>

                <!-- PANEL FINALISASI T2Q -->
                @include('guruT2q.partials._finalisasi_card', [
                    't2qFinalisasi' => $t2qFinalisasi,
                    'compact' => true,
                ])

                <!-- TOOLBAR 1 BARIS HORIZONTAL -->
                <div class="t2q-toolbar">
                    <div class="t2q-tingkat-pills">
                        <a href="{{ route('guru-t2q.rapor.index', ['periode' => $periode]) }}"
                            class="t2q-pill-tab {{ $selectedTingkat === '' ? 'is-active' : '' }}">
                            <span>Semua SD</span>
                            <span class="t2q-pill-count">{{ $stats['total_sd'] }}</span>
                        </a>
                        @foreach ($tingkatTabs as $tKey => $tCount)
                            @php $isTingkatActive = $selectedTingkat === $tKey; @endphp
                            <a href="{{ route('guru-t2q.rapor.index', ['periode' => $periode, 'tingkat' => $tKey]) }}"
                                class="t2q-pill-tab {{ $isTingkatActive ? 'is-active' : '' }}">
                                <span>Tingkat {{ $tKey }}</span>
                                <span class="t2q-pill-count">{{ $tCount }}</span>
                            </a>
                        @endforeach
                    </div>

                    <div class="t2q-toolbar-right">
                        <div class="wk-search-wrap">
                            <i class="fa fa-search"></i>
                            <input type="text" id="t2qRaporSearch" class="wk-search-input"
                                placeholder="Cari nama atau NIS...">
                        </div>

                        @if ($rows->isNotEmpty())
                            <button type="button" id="t2qToggleAllBtn" class="t2q-ghost-btn" title="Buka atau tutup seluruh editor siswa">
                                <i class="fa fa-expand-alt"></i> <span>Buka Semua</span>
                            </button>
                        @endif

                        @if ($stats['unlocked_count'] > 0)
                            <form method="POST" action="{{ route('guru-t2q.rapor.generate') }}" class="t2q-sync-form">
                                @csrf
                                <input type="hidden" name="periode" value="{{ $periode }}">
                                <input type="hidden" name="tingkat" value="{{ $selectedTingkat }}">
                                <button type="submit" class="t2q-sync-btn"
                                    title="Muat ulang nilai &amp; materi terakhir dari Yaumiyah lalu susun ulang draf deskripsi">
                                    <i class="fa fa-sync"></i> <span>Sinkronkan Nilai Terbaru</span>
                                </button>
                            </form>
                        @endif
                    </div>
                </div>

                <!-- DAFTAR SISWA RINGKAS & COMPACT EXPANDABLE -->
                <form method="POST" action="{{ route('guru-t2q.rapor.store') }}" id="t2qRaporBatchForm">
                    @csrf
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <input type="hidden" name="tingkat" value="{{ $selectedTingkat }}">

                    <div id="t2qSearchEmpty" class="wk-empty-guard d-none mb-3" style="padding: 2rem 1rem;">
                        <i class="fa fa-search text-muted mb-2" style="font-size: 1.4rem;"></i>
                        <div class="font-weight-bold text-dark small">Tidak ada siswa yang sesuai pencarian</div>
                        <div class="text-muted" style="font-size: 0.74rem;">Coba ubah kata kunci nama atau NIS siswa.</div>
                    </div>

                    <div class="t2q-list-surface">
                        <div class="t2q-list-head">
                            <div>No</div>
                            <div>Siswa</div>
                            <div>Tahsin Terakhir</div>
                            <div>Tahfidz Terakhir</div>
                            <div>Ringkasan Poin &amp; Status</div>
                            <div class="text-right">Editor</div>
                        </div>

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

                                $tBadgeStyle = match ($tahsin['predikat'] ?? null) {
                                    'SAB' => 'background: #ecfdf5; color: #047857;',
                                    'BSH' => 'background: #eff6ff; color: #1d4ed8;',
                                    'SB' => 'background: #ecfeff; color: #0e7490;',
                                    'MB' => 'background: #fffbeb; color: #b45309;',
                                    default => 'background: #f1f5f9; color: #64748b;',
                                };
                                $fBadgeStyle = match ($tahfizh['predikat'] ?? null) {
                                    'SAB' => 'background: #ecfdf5; color: #047857;',
                                    'BSH' => 'background: #eff6ff; color: #1d4ed8;',
                                    'SB' => 'background: #ecfeff; color: #0e7490;',
                                    'MB' => 'background: #fffbeb; color: #b45309;',
                                    default => 'background: #f1f5f9; color: #64748b;',
                                };

                                $tInitialKeunggulan = \App\Models\Admin\T2q\RaporT2q::parseAspectList((string) old("tahsin.{$t2qId}.keunggulan", $tahsin['keunggulan'] ?? ''), $komentarOptions);
                                $tInitialPeningkatan = \App\Models\Admin\T2q\RaporT2q::parseAspectList((string) old("tahsin.{$t2qId}.peningkatan", $tahsin['peningkatan'] ?? ''), $komentarOptions);
                                $fInitialKeunggulan = \App\Models\Admin\T2q\RaporT2q::parseAspectList((string) old("tahfizh.{$t2qId}.keunggulan", $tahfizh['keunggulan'] ?? ''), $komentarOptions);
                                $fInitialPeningkatan = \App\Models\Admin\T2q\RaporT2q::parseAspectList((string) old("tahfizh.{$t2qId}.peningkatan", $tahfizh['peningkatan'] ?? ''), $komentarOptions);
                                $totalKeunggulanSelected = count($tInitialKeunggulan) + count($fInitialKeunggulan);
                                $totalPeningkatanSelected = count($tInitialPeningkatan) + count($fInitialPeningkatan);
                                $isSavedRecord = $row['saved'] instanceof \App\Models\Admin\T2q\RaporT2q;
                            @endphp

                            <div class="t2q-student-item js-t2q-student-item"
                                data-rapor-card="{{ $t2qId }}"
                                data-row-index="{{ $idx }}"
                                data-search-text="{{ $searchBlob }}">
                                <!-- BARIS RINGKAS (1 BARIS PER SISWA) -->
                                <div class="t2q-row-summary js-t2q-row-summary">
                                    <div class="t2q-row-num">{{ $idx + 1 }}.</div>

                                    <div class="t2q-student-cell">
                                        @if ($siswa?->avatar_url)
                                            <img src="{{ $siswa->avatar_url }}" alt="{{ $namaSiswa }}" class="t2q-student-avatar">
                                        @else
                                            <div class="t2q-student-initials">
                                                {{ $siswa?->inisial_nama ?? mb_strtoupper(mb_substr($namaSiswa, 0, 1)) }}
                                            </div>
                                        @endif
                                        <div style="min-width: 0;">
                                            <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.84rem; line-height: 1.25;">
                                                {{ $namaSiswa }}
                                            </div>
                                            <div class="text-muted text-truncate" style="font-size: 0.69rem;">
                                                NIS {{ $nisSiswa }} &bull; Tingkat {{ $anggota->tingkat }}
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Ringkasan Tahsin -->
                                    <div class="t2q-cell-block">
                                        <span class="t2q-mobile-label">Tahsin Terakhir</span>
                                        <div class="d-flex align-items-center" style="gap: 0.4rem; min-width: 0;">
                                            @if ($tahsin['has_data'] ?? false)
                                                <span class="t2q-mini-score" style="{{ $tBadgeStyle }}">
                                                    {{ $tahsin['nilai'] !== null ? number_format((float) $tahsin['nilai'], 0) : '-' }}
                                                    @if ($tahsin['predikat'])
                                                        <span>&bull; {{ $tahsin['predikat'] }}</span>
                                                    @endif
                                                </span>
                                                <span class="font-weight-semibold text-dark text-truncate" style="font-size: 0.76rem;" title="{{ $tahsin['materi'] }}">
                                                    {{ $tahsin['materi'] ?: '-' }}
                                                </span>
                                            @else
                                                <span class="text-muted small" style="font-size: 0.72rem;">Belum ada setoran</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Ringkasan Tahfidz -->
                                    <div class="t2q-cell-block">
                                        <span class="t2q-mobile-label">Tahfidz Terakhir</span>
                                        <div class="d-flex align-items-center" style="gap: 0.4rem; min-width: 0;">
                                            @if ($tahfizh['has_data'] ?? false)
                                                <span class="t2q-mini-score" style="{{ $fBadgeStyle }}">
                                                    {{ $tahfizh['nilai'] !== null ? number_format((float) $tahfizh['nilai'], 0) : '-' }}
                                                    @if ($tahfizh['predikat'])
                                                        <span>&bull; {{ $tahfizh['predikat'] }}</span>
                                                    @endif
                                                </span>
                                                <span class="font-weight-semibold text-dark text-truncate" style="font-size: 0.76rem;" title="{{ $tahfizh['materi'] }}">
                                                    {{ $tahfizh['materi'] ?: '-' }}
                                                </span>
                                            @else
                                                <span class="text-muted small" style="font-size: 0.72rem;">Belum ada setoran</span>
                                            @endif
                                        </div>
                                    </div>

                                    <!-- Ringkasan Poin & Status -->
                                    <div class="t2q-cell-block">
                                        <span class="t2q-mobile-label">Poin &amp; Status</span>
                                        <div class="t2q-summary-tags">
                                            <span class="t2q-summary-pill js-t2q-row-k-pill {{ $totalKeunggulanSelected > 0 ? 'has-val' : '' }}">
                                                <span class="js-t2q-row-k-count">{{ $totalKeunggulanSelected }}</span> kelebihan
                                            </span>
                                            <span class="t2q-summary-pill js-t2q-row-p-pill {{ $totalPeningkatanSelected > 0 ? 'has-val' : '' }}">
                                                <span class="js-t2q-row-p-count">{{ $totalPeningkatanSelected }}</span> peningkatan
                                            </span>
                                            @if ($isLocked)
                                                <span class="t2q-summary-pill" style="background: #dcfce7; color: #166534;">
                                                    <i class="fa fa-lock mr-1"></i>Final
                                                </span>
                                            @elseif ($isSavedRecord)
                                                <span class="t2q-summary-pill" style="background: #eff6ff; color: #1d4ed8;">Tersimpan</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="text-right">
                                        <button type="button" class="t2q-toggle-btn js-t2q-toggle-row" aria-expanded="false">
                                            <span class="js-t2q-toggle-label">Atur</span>
                                            <i class="fa fa-chevron-down js-t2q-toggle-icon" style="font-size: 0.62rem;"></i>
                                        </button>
                                    </div>
                                </div>

                                <!-- PANEL EDITOR DETAIL (EXPANDABLE IN-PLACE) -->
                                <div class="t2q-row-drawer js-t2q-row-drawer">
                                    <div class="t2q-drawer-grid">
                                        @foreach ([
                                            ['key' => 'tahsin', 'title' => "TAHSIN AL-QUR'AN", 'icon' => 'fa-book-open', 'accent' => '#ea580c', 'data' => $tahsin],
                                            ['key' => 'tahfizh', 'title' => "TAHFIDZ AL-QUR'AN", 'icon' => 'fa-quran', 'accent' => '#059669', 'data' => $tahfizh],
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
                                                $kItems = \App\Models\Admin\T2q\RaporT2q::parseAspectList($kVal, $komentarOptions);
                                                $pItems = \App\Models\Admin\T2q\RaporT2q::parseAspectList($pVal, $komentarOptions);
                                                $descVal = old("{$ch}.{$t2qId}.deskripsi", $d['deskripsi'] ?? '');
                                                $nilaiNum = $d['nilai'] ?? null;
                                                $nilaiDisplay = $nilaiNum !== null ? number_format((float) $nilaiNum, 0) : '-';
                                                $aspectFields = [
                                                    ['role' => 'keunggulan', 'label' => 'Kelebihan', 'val' => $kVal, 'items' => $kItems, 'placeholder' => 'Pilih kelebihan (bisa >1)...'],
                                                    ['role' => 'peningkatan', 'label' => 'Perlu Ditingkatkan', 'val' => $pVal, 'items' => $pItems, 'placeholder' => 'Pilih kekurangan (bisa >1)...'],
                                                ];
                                            @endphp

                                            <div class="t2q-channel-box js-t2q-channel-panel"
                                                data-mode="{{ $ch }}"
                                                data-nilai="{{ $nilaiNum !== null ? $nilaiNum : '' }}"
                                                data-predikat="{{ $predCode ?? '' }}"
                                                data-materi="{{ $d['materi'] ?? '' }}">
                                                <div class="t2q-snapshot-bar">
                                                    <div style="min-width: 0;">
                                                        <div class="t2q-channel-kicker" style="color: {{ $panel['accent'] }};">
                                                            <i class="fa {{ $panel['icon'] }}"></i>
                                                            <span>{{ $panel['title'] }}</span>
                                                        </div>
                                                        <div class="t2q-materi-title text-truncate">
                                                            {{ $hasData && filled($d['materi'] ?? null) ? $d['materi'] : 'Belum ada setoran Yaumiyah' }}
                                                        </div>
                                                        <div class="t2q-materi-meta">
                                                            @if ($hasData)
                                                                @if (! empty($d['tanggal_formatted']))
                                                                    Setoran {{ $d['tanggal_formatted'] }}
                                                                @else
                                                                    Data tersimpan
                                                                @endif
                                                                @if ($ch === 'tahfizh')
                                                                    &bull; {{ ($d['has_ayat'] ?? false) ? 'Progres ayat' : 'Surah utuh' }}
                                                                @endif
                                                            @else
                                                                Input setoran di Yaumiyah agar tampil otomatis
                                                            @endif
                                                        </div>
                                                    </div>

                                                    <div class="t2q-score-pill" style="{{ $predBadgeStyle }}" title="{{ $predLabel ?? 'Belum ada nilai' }}">
                                                        <span class="t2q-score-num">{{ $nilaiDisplay }}</span>
                                                        @if ($predCode)
                                                            <span class="t2q-score-code">{{ $predCode }}</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- 2 DROPDOWN FIXED-HEIGHT SEJAJAR -->
                                                <div class="row">
                                                    @foreach ($aspectFields as $af)
                                                        <div class="col-md-6 {{ $loop->first ? 'mb-2 mb-md-0' : '' }}">
                                                            <div class="t2q-field-label">
                                                                <span>{{ $af['label'] }}</span>
                                                                <span class="t2q-field-hint">Bisa &gt;1</span>
                                                            </div>
                                                            <div class="t2q-ms js-t2q-ms"
                                                                data-field-role="{{ $af['role'] }}"
                                                                data-placeholder="{{ $af['placeholder'] }}"
                                                                data-selected="{{ json_encode($af['items']) }}">
                                                                <input type="hidden"
                                                                    name="{{ $ch }}[{{ $t2qId }}][{{ $af['role'] }}]"
                                                                    value="{{ $af['val'] }}"
                                                                    class="js-t2q-ms-input js-t2q-{{ $af['role'] }}"
                                                                    @disabled($isLocked)>

                                                                <button type="button"
                                                                    class="t2q-ms-trigger js-t2q-ms-trigger {{ $isLocked ? 'is-disabled' : '' }}"
                                                                    aria-expanded="false"
                                                                    @disabled($isLocked)>
                                                                    <span class="t2q-ms-summary js-t2q-ms-summary"></span>
                                                                    <i class="fa fa-chevron-down t2q-ms-caret"></i>
                                                                </button>

                                                                <div class="t2q-ms-menu js-t2q-ms-menu">
                                                                    <div class="t2q-ms-selected-drawer js-t2q-ms-selected-drawer d-none"></div>
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
                                                                            <button type="button" class="btn btn-link btn-sm p-0 text-danger font-weight-bold js-t2q-ms-clear" style="font-size: 0.67rem;">Kosongkan</button>
                                                                            <button type="button" class="btn btn-link btn-sm p-0 text-primary font-weight-bold js-t2q-ms-done" style="font-size: 0.67rem;">Selesai</button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>

                                                <div>
                                                    <div class="t2q-field-label" style="margin-bottom: 0.22rem;">
                                                        <span>Teks Deskripsi Rapor</span>
                                                        @if (! $isLocked && $hasData)
                                                            <button type="button" class="btn btn-link btn-sm p-0 text-warning font-weight-bold js-t2q-reset-desc"
                                                                style="font-size: 0.68rem; text-decoration: none;">
                                                                <i class="fa fa-magic mr-1"></i> Reset otomatis
                                                            </button>
                                                        @endif
                                                    </div>
                                                    <textarea name="{{ $ch }}[{{ $t2qId }}][deskripsi]"
                                                        rows="3"
                                                        class="form-control form-control-sm t2q-desc-textarea js-t2q-deskripsi"
                                                        placeholder="{{ $hasData ? 'Kalimat deskripsi otomatis tampil di rapor...' : 'Belum ada nilai Yaumiyah pada periode ini.' }}"
                                                        @disabled($isLocked)>{{ $descVal }}</textarea>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <div class="t2q-drawer-foot">
                                        <div class="small text-muted" style="font-size: 0.71rem;">
                                            @if ($midCutoff)
                                                Cut-off Mid: {{ \Carbon\Carbon::parse($midCutoff)->locale('id')->translatedFormat('d M Y') }}
                                            @else
                                                Perubahan tersimpan saat Anda menekan <strong>Simpan Rekap &amp; Deskripsi Rapor</strong>.
                                            @endif
                                        </div>
                                        <div class="d-flex align-items-center" style="gap: 0.4rem;">
                                            @if ($idx > 0)
                                                <button type="button" class="t2q-ghost-btn js-t2q-nav-btn" data-nav-step="-1" style="height: 29px; font-size: 0.71rem;">
                                                    <i class="fa fa-arrow-left"></i> Sebelumnya
                                                </button>
                                            @endif
                                            @if ($idx < $rows->count() - 1)
                                                <button type="button" class="t2q-ghost-btn js-t2q-nav-btn" data-nav-step="1" style="height: 29px; font-size: 0.71rem;">
                                                    Berikutnya <i class="fa fa-arrow-right"></i>
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="t2q-save-bar">
                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.55rem;">
                            <span class="small text-muted">
                                Menampilkan <strong>{{ $rows->count() }}</strong> siswa SD
                            </span>
                            <span id="t2qDirtyBadge" class="t2q-dirty-badge">
                                <i class="fa fa-circle" style="font-size: 0.45rem;"></i> Perubahan belum disimpan
                            </span>
                        </div>

                        @if ($stats['unlocked_count'] > 0)
                            <button type="submit" class="btn text-white font-weight-bold px-4 shadow-sm"
                                style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px; font-size: 0.82rem;">
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
    </div>
@endsection

@push('js')
    <script>
        (function () {
            const BASE_KOMENTAR_OPTIONS = @json($komentarOptions);
            const searchInput = document.getElementById('t2qRaporSearch');
            const searchEmpty = document.getElementById('t2qSearchEmpty');
            const studentItems = Array.from(document.querySelectorAll('.js-t2q-student-item'));
            const toggleAllBtn = document.getElementById('t2qToggleAllBtn');
            const dirtyBadge = document.getElementById('t2qDirtyBadge');

            function markDirty() {
                if (dirtyBadge) {
                    dirtyBadge.classList.add('is-visible');
                }
            }

            function autoSizeTextarea(textarea) {
                if (!textarea) return;
                textarea.style.height = 'auto';
                const targetHeight = Math.min(200, Math.max(76, textarea.scrollHeight + 2));
                textarea.style.height = targetHeight + 'px';
            }

            function setItemExpanded(item, expand, scrollIntoView) {
                if (!item) return;
                item.classList.toggle('is-expanded', expand);
                const toggleBtn = item.querySelector('.js-t2q-toggle-row');
                const toggleLabel = item.querySelector('.js-t2q-toggle-label');
                const toggleIcon = item.querySelector('.js-t2q-toggle-icon');

                if (toggleBtn) toggleBtn.setAttribute('aria-expanded', expand ? 'true' : 'false');
                if (toggleLabel) toggleLabel.textContent = expand ? 'Tutup' : 'Atur';
                if (toggleIcon) {
                    toggleIcon.classList.toggle('fa-chevron-down', !expand);
                    toggleIcon.classList.toggle('fa-chevron-up', expand);
                }

                if (expand) {
                    item.querySelectorAll('.js-t2q-deskripsi').forEach(autoSizeTextarea);
                    if (scrollIntoView) {
                        setTimeout(function () {
                            item.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        }, 30);
                    }
                }
            }

            function syncToggleAllButtonState() {
                if (!toggleAllBtn || studentItems.length === 0) return;
                const allOpen = studentItems.every(function (item) {
                    return item.classList.contains('is-expanded');
                });
                const span = toggleAllBtn.querySelector('span');
                const icon = toggleAllBtn.querySelector('i');
                if (span) span.textContent = allOpen ? 'Tutup Semua' : 'Buka Semua';
                if (icon) {
                    icon.classList.toggle('fa-expand-alt', !allOpen);
                    icon.classList.toggle('fa-compress-alt', allOpen);
                }
            }

            studentItems.forEach(function (item) {
                const summary = item.querySelector('.js-t2q-row-summary');
                if (summary) {
                    summary.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const nextState = !item.classList.contains('is-expanded');
                        closeAllDropdowns(null);
                        setItemExpanded(item, nextState, false);
                        syncToggleAllButtonState();
                    });
                }

                item.querySelectorAll('.js-t2q-nav-btn').forEach(function (navBtn) {
                    navBtn.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const step = parseInt(this.getAttribute('data-nav-step') || '0', 10);
                        const visibleItems = studentItems.filter(function (el) {
                            return el.style.display !== 'none';
                        });
                        const curIdx = visibleItems.indexOf(item);
                        const target = visibleItems[curIdx + step];
                        if (!target) return;
                        closeAllDropdowns(null);
                        setItemExpanded(item, false, false);
                        setItemExpanded(target, true, true);
                        syncToggleAllButtonState();
                    });
                });
            });

            if (toggleAllBtn) {
                toggleAllBtn.addEventListener('click', function () {
                    const anyClosed = studentItems.some(function (item) {
                        return !item.classList.contains('is-expanded');
                    });
                    closeAllDropdowns(null);
                    studentItems.forEach(function (item) {
                        setItemExpanded(item, anyClosed, false);
                    });
                    syncToggleAllButtonState();
                });
            }

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const q = String(this.value || '').trim().toLowerCase();
                    let visibleCount = 0;
                    studentItems.forEach(function (card) {
                        const haystack = String(card.getAttribute('data-search-text') || '');
                        const match = q === '' || haystack.includes(q);
                        card.style.display = match ? '' : 'none';
                        if (match) visibleCount++;
                    });
                    if (searchEmpty) {
                        searchEmpty.classList.toggle('d-none', visibleCount > 0 || studentItems.length === 0);
                    }
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

                const hasComma = clean.some(function (item) { return item.includes(','); });
                const hasDan = clean.some(function (item) { return /\bdan\b/i.test(item); });

                if (clean.length === 2) {
                    const glue = (hasComma || hasDan) ? ', serta ' : ' dan ';
                    return clean[0] + glue + clean[1];
                }

                const head = clean.slice(0, -1);
                const tail = clean[clean.length - 1];
                if (hasComma) {
                    return head.join('; ') + '; serta ' + tail;
                }
                return head.join(', ') + (hasDan ? ', serta ' : ', dan ') + tail;
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
                    autoSizeTextarea(descEl);
                }
            }

            function syncRowSummaryBadges(studentItem) {
                if (!studentItem) return;
                let kCount = 0;
                let pCount = 0;

                studentItem.querySelectorAll('.js-t2q-ms[data-field-role="keunggulan"]').forEach(function (ms) {
                    kCount += Number(ms.getAttribute('data-selected-count') || '0');
                });
                studentItem.querySelectorAll('.js-t2q-ms[data-field-role="peningkatan"]').forEach(function (ms) {
                    pCount += Number(ms.getAttribute('data-selected-count') || '0');
                });

                const kCountEl = studentItem.querySelector('.js-t2q-row-k-count');
                const kPillEl = studentItem.querySelector('.js-t2q-row-k-pill');
                const pCountEl = studentItem.querySelector('.js-t2q-row-p-count');
                const pPillEl = studentItem.querySelector('.js-t2q-row-p-pill');

                if (kCountEl) kCountEl.textContent = String(kCount);
                if (kPillEl) kPillEl.classList.toggle('has-val', kCount > 0);
                if (pCountEl) pCountEl.textContent = String(pCount);
                if (pPillEl) pPillEl.classList.toggle('has-val', pCount > 0);
            }

            function closeAllDropdowns(exceptEl) {
                document.querySelectorAll('.js-t2q-ms.is-open').forEach(function (ms) {
                    if (ms === exceptEl) return;
                    ms.classList.remove('is-open', 'is-open-up');
                    const trig = ms.querySelector('.js-t2q-ms-trigger');
                    if (trig) trig.setAttribute('aria-expanded', 'false');
                    const card = ms.closest('.js-t2q-student-item');
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
                const studentItem = ms.closest('.js-t2q-student-item');
                const panel = ms.closest('.js-t2q-channel-panel');
                const hiddenInput = ms.querySelector('.js-t2q-ms-input');
                const trigger = ms.querySelector('.js-t2q-ms-trigger');
                const summaryEl = ms.querySelector('.js-t2q-ms-summary');
                const selectedDrawerEl = ms.querySelector('.js-t2q-ms-selected-drawer');
                const searchField = ms.querySelector('.js-t2q-ms-search');
                const listEl = ms.querySelector('.js-t2q-ms-list');
                const countEl = ms.querySelector('.js-t2q-ms-count');
                const clearBtn = ms.querySelector('.js-t2q-ms-clear');
                const doneBtn = ms.querySelector('.js-t2q-ms-done');
                const placeholderText = ms.getAttribute('data-placeholder') || 'Pilih atau cari poin...';
                const isDisabled = !hiddenInput || hiddenInput.disabled;

                let options = [];
                let selected = [];
                try {
                    selected = JSON.parse(ms.getAttribute('data-selected') || '[]') || [];
                } catch (e) {
                    selected = [];
                }
                options = Array.isArray(BASE_KOMENTAR_OPTIONS) ? BASE_KOMENTAR_OPTIONS.slice() : [];
                selected.forEach(function (item) {
                    if (!options.some(function (o) { return String(o).toLowerCase() === String(item).toLowerCase(); })) {
                        options.unshift(item);
                    }
                });

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
                    ms.setAttribute('data-selected-count', String(selected.length));
                    if (countEl) {
                        countEl.textContent = selected.length + ' dipilih (' + joined.length + '/240)';
                    }
                    if (trigger) {
                        trigger.title = joined || placeholderText;
                    }
                }

                function renderSummaryAndSelectedDrawer() {
                    if (summaryEl) {
                        if (selected.length === 0) {
                            summaryEl.innerHTML = '<span class="t2q-ms-placeholder">' + escapeHtml(placeholderText) + '</span>';
                        } else if (selected.length === 1) {
                            summaryEl.innerHTML = '<span class="t2q-ms-badge-count">1</span>'
                                + '<span class="t2q-ms-preview-text">' + escapeHtml(selected[0]) + '</span>';
                        } else {
                            summaryEl.innerHTML = '<span class="t2q-ms-badge-count">' + selected.length + ' dipilih</span>'
                                + '<span class="t2q-ms-preview-text">' + escapeHtml(selected.join(' · ')) + '</span>';
                        }
                    }

                    if (selectedDrawerEl) {
                        if (selected.length === 0) {
                            selectedDrawerEl.classList.add('d-none');
                            selectedDrawerEl.innerHTML = '';
                        } else {
                            selectedDrawerEl.classList.remove('d-none');
                            selectedDrawerEl.innerHTML = selected.map(function (item) {
                                const removeBtn = isDisabled
                                    ? ''
                                    : '<span class="t2q-ms-tag-remove" role="button" tabindex="-1" data-remove-val="' + escapeHtml(item) + '" title="Hapus pilihan">&times;</span>';
                                return '<span class="t2q-ms-tag"><span>' + escapeHtml(item) + '</span>' + removeBtn + '</span>';
                            }).join('');
                        }
                    }
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
                                window.toastr.warning('Panjang maksimal gabungan aspek tercapai (240 karakter).');
                            }
                            return;
                        }
                        if (!options.some(function (o) { return String(o).toLowerCase() === cleanVal.toLowerCase(); })) {
                            options.unshift(cleanVal);
                        }
                        selected.push(cleanVal);
                    }

                    syncHiddenAndCount();
                    renderSummaryAndSelectedDrawer();
                    renderOptions(searchField ? searchField.value : '');
                    syncRowSummaryBadges(studentItem);
                    if (panel) {
                        updatePanelDescription(panel);
                    }
                    markDirty();
                }

                syncHiddenAndCount();
                renderSummaryAndSelectedDrawer();
                renderOptions('');

                if (trigger && !isDisabled) {
                    trigger.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const willOpen = !ms.classList.contains('is-open');
                        closeAllDropdowns(ms);

                        if (willOpen) {
                            const rect = trigger.getBoundingClientRect();
                            const spaceBelow = window.innerHeight - rect.bottom;
                            ms.classList.toggle('is-open-up', spaceBelow < 290 && rect.top > spaceBelow);
                            ms.classList.add('is-open');
                            if (studentItem) studentItem.classList.add('has-open-ms');
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
                            if (studentItem) studentItem.classList.remove('has-open-ms');
                            trigger.setAttribute('aria-expanded', 'false');
                        }
                    });
                }

                if (selectedDrawerEl && !isDisabled) {
                    selectedDrawerEl.addEventListener('click', function (e) {
                        e.stopPropagation();
                        const removeTarget = e.target.closest('.t2q-ms-tag-remove');
                        if (removeTarget) {
                            toggleItem(removeTarget.getAttribute('data-remove-val') || '');
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
                        renderSummaryAndSelectedDrawer();
                        renderOptions(searchField ? searchField.value : '');
                        syncRowSummaryBadges(studentItem);
                        if (panel) {
                            updatePanelDescription(panel);
                        }
                        markDirty();
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
                const descEl = panel.querySelector('.js-t2q-deskripsi');
                if (resetBtn) {
                    resetBtn.addEventListener('click', function () {
                        updatePanelDescription(panel);
                        markDirty();
                    });
                }
                if (descEl) {
                    descEl.addEventListener('input', function () {
                        autoSizeTextarea(this);
                        markDirty();
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
