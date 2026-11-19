@include('admin.dataMaster.partials._t2q_arab_styles')

@push('css')
    <style>
        .t2q-surface {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
        }

        .t2q-chip-btn {
            border-color: #cbd5e1;
        }

        .t2q-chip-btn.is-current {
            background: #fff7ed;
            border-color: #fdba74;
            color: #c2410c !important;
        }

        .t2q-view-switch {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem;
            border-radius: 9999px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }

        .t2q-view-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.38rem;
            padding: 0.36rem 0.85rem;
            border-radius: 9999px;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 0.76rem;
            font-weight: 700;
            color: #64748b;
            text-decoration: none !important;
            transition: all 0.15s ease;
        }

        .t2q-view-pill:hover {
            color: #0f172a;
        }

        .t2q-view-pill.is-active {
            background: #ffffff;
            color: #c2410c;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
        }

        .t2q-tingkat-strip {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem 1rem;
        }

        .t2q-tingkat-tabs {
            display: inline-flex;
            flex-wrap: wrap;
            gap: 0.35rem;
            padding: 0.28rem;
            border-radius: 12px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
        }

        .t2q-tingkat-tab {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.42rem 0.85rem;
            border-radius: 9px;
            border: none;
            background: transparent;
            cursor: pointer;
            color: #475569;
            font-size: 0.78rem;
            font-weight: 700;
            text-decoration: none !important;
            transition: all 0.15s ease;
        }

        .t2q-tingkat-tab:hover {
            color: #0f172a;
            background: rgba(255, 255, 255, 0.65);
        }

        .t2q-tingkat-tab.is-active {
            background: #ffffff;
            color: #ea580c;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
        }

        .t2q-tingkat-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
            height: 18px;
            padding: 0 5px;
            border-radius: 9999px;
            font-size: 0.68rem;
            font-weight: 700;
            background: #e2e8f0;
            color: #475569;
        }

        .t2q-tingkat-tab.is-active .t2q-tingkat-count {
            background: #ffedd5;
            color: #c2410c;
        }

        .t2q-target-strip {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.4rem;
        }

        .t2q-target-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.32rem 0.7rem;
            border-radius: 9999px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            font-size: 0.74rem;
            font-weight: 600;
        }

        .t2q-search-box {
            position: relative;
            min-width: 230px;
        }

        .t2q-search-box i {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.78rem;
            pointer-events: none;
        }

        .t2q-search-box input {
            padding-left: 2rem;
            height: 2.2rem;
            border-radius: 9999px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: 0.8rem;
            width: 100%;
            outline: none;
            transition: all 0.15s ease;
        }

        .t2q-search-box input:focus {
            background: #ffffff;
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
        }

        .t2q-score-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 34px;
            padding: 0.14rem 0.46rem;
            border-radius: 9999px;
            font-size: 0.71rem;
            font-weight: 800;
            font-variant-numeric: tabular-nums;
            line-height: 1.3;
            border: 1px solid transparent;
            flex-shrink: 0;
        }

        .t2q-score-pill--excellent {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #047857;
        }

        .t2q-score-pill--good {
            background: #eff6ff;
            border-color: #bfdbfe;
            color: #1d4ed8;
        }

        .t2q-score-pill--fair {
            background: #fffbeb;
            border-color: #fde68a;
            color: #b45309;
        }

        .t2q-score-pill--poor {
            background: #fff1f2;
            border-color: #fecdd3;
            color: #be123c;
        }

        .t2q-score-pill--neutral {
            background: #f1f5f9;
            border-color: #e2e8f0;
            color: #64748b;
        }

        .t2q-arab-inline {
            font-family: 'Traditional Arabic', 'Amiri', 'Scheherazade New', 'Noto Naskh Arabic', serif;
            font-size: 0.92rem;
            line-height: 1.25;
            direction: rtl;
            unicode-bidi: isolate;
        }
    </style>
@endpush

