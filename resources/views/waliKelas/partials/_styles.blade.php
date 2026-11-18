@pushOnce('css')
    <style>
        .wk-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
            border-radius: 16px;
            padding: 1.5rem 1.75rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.22);
            margin-bottom: 1.15rem;
        }

        .wk-hero-glow {
            position: absolute;
            right: -35px;
            top: -45px;
            width: 220px;
            height: 220px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(234, 88, 12, 0.28) 0%, rgba(234, 88, 12, 0) 70%);
            pointer-events: none;
        }

        .wk-class-switcher {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem;
            margin-bottom: 1rem;
        }

        .wk-class-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.38rem 0.8rem;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: 0.76rem;
            font-weight: 700;
            text-decoration: none !important;
            transition: all 0.15s ease;
        }

        .wk-class-pill:hover {
            border-color: #ea580c;
            color: #c2410c;
        }

        .wk-class-pill.is-active {
            background: #ea580c;
            border-color: #ea580c;
            color: #ffffff;
            box-shadow: 0 4px 10px rgba(234, 88, 12, 0.25);
        }

        .wk-team-card {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 12px;
            padding: 0.85rem 1rem;
            backdrop-filter: blur(4px);
        }

        .wk-kpi-grid {
            display: grid;
            grid-template-columns: repeat(1, minmax(0, 1fr));
            gap: 0.75rem;
            margin-bottom: 1.15rem;
        }

        @media (min-width: 640px) {
            .wk-kpi-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (min-width: 1100px) {
            .wk-kpi-grid {
                grid-template-columns: repeat(4, minmax(0, 1fr));
            }
        }

        .wk-kpi-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 0.95rem 1.1rem;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
        }

        .wk-kpi-label {
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .wk-kpi-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            display: inline-block;
        }

        .wk-kpi-value {
            font-size: 1.45rem;
            font-weight: 800;
            color: #0f172a;
            line-height: 1.2;
            margin-top: 0.25rem;
            font-variant-numeric: tabular-nums;
        }

        .wk-kpi-sub {
            font-size: 0.72rem;
            color: #64748b;
            margin-top: 0.2rem;
        }

        .wk-kpi-icon {
            width: 42px;
            height: 42px;
            border-radius: 11px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            flex-shrink: 0;
        }

        .wk-surface {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(15, 23, 42, 0.03);
            overflow: hidden;
            margin-bottom: 1.15rem;
        }

        .wk-surface-head {
            padding: 0.85rem 1.1rem;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.65rem;
        }

        .wk-surface-title {
            font-size: 0.9rem;
            font-weight: 800;
            color: #0f172a;
            margin: 0;
        }

        .wk-search-wrap {
            position: relative;
            width: 100%;
            max-width: 230px;
        }

        .wk-search-wrap i {
            position: absolute;
            left: 0.7rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 0.74rem;
            pointer-events: none;
        }

        .wk-search-input {
            width: 100%;
            height: 32px;
            padding: 0.25rem 0.65rem 0.25rem 1.9rem;
            border-radius: 9px;
            border: 1px solid #e2e8f0;
            background: #f8fafc;
            font-size: 0.76rem;
            color: #0f172a;
        }

        .wk-search-input:focus {
            outline: 0;
            background: #ffffff;
            border-color: #ea580c;
            box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.14);
        }

        .wk-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
            font-variant-numeric: tabular-nums;
            margin: 0;
        }

        .wk-table thead th {
            background: #f8fafc;
            color: #475569;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 0.65rem 0.85rem;
            border-bottom: 1px solid #e2e8f0;
            white-space: nowrap;
        }

        .wk-table tbody td {
            padding: 0.65rem 0.85rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .wk-table tbody tr:last-child td {
            border-bottom: 0;
        }

        .wk-table tbody tr:hover td {
            background: #f8fafc;
        }

        .wk-scroll-body {
            max-height: 410px;
            overflow-y: auto;
        }

        .wk-badge-status {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .wk-progress-track {
            height: 7px;
            width: 100%;
            background: #e2e8f0;
            border-radius: 999px;
            overflow: hidden;
        }

        .wk-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #ea580c 0%, #f59e0b 100%);
            border-radius: 999px;
        }

        .wk-empty-guard {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 2.5rem 1.5rem;
            text-align: center;
            max-width: 660px;
            margin: 1.5rem auto;
            box-shadow: 0 4px 12px rgba(15, 23, 42, 0.04);
        }

        .wk-avatar-thumb {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            object-fit: cover;
            flex-shrink: 0;
            border: 1px solid #e2e8f0;
        }

        .wk-avatar-fallback {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.74rem;
            font-weight: 800;
            color: #ffffff;
            background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);
            flex-shrink: 0;
        }
    </style>
@endPushOnce

