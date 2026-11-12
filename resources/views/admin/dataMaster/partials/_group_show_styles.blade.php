@pushOnce('css')
    <style>
        .kelas-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #334155 100%);
            border-radius: 16px;
            padding: 24px 28px;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
        }

        .kelas-hero-icon {
            width: 72px;
            height: 72px;
            border-radius: 16px;
            background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 800;
            color: #ffffff;
            box-shadow: 0 8px 18px rgba(234, 88, 12, 0.35);
            flex-shrink: 0;
        }

        .kelas-stat-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(92px, 1fr));
            gap: 0.75rem;
        }

        .kelas-stat-pill {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            border-radius: 12px;
            padding: 0.65rem 0.9rem;
            text-align: center;
        }

        .kelas-stat-value {
            font-size: 1.35rem;
            font-weight: 800;
            line-height: 1.15;
            color: #ffffff;
        }

        .kelas-stat-label {
            font-size: 0.72rem;
            color: #cbd5e1;
            font-weight: 600;
            margin-top: 0.2rem;
        }

        .kelas-section-title {
            font-size: 0.85rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #ea580c;
            margin-bottom: 1rem;
            padding-bottom: 0.75rem;
            border-bottom: 2px solid #fed7aa;
            display: flex;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
            gap: 0.75rem;
        }

        .kelas-target-box {
            padding: 0.625rem 0.875rem;
            border-radius: 0.625rem;
            background-color: #fff7ed;
            border: 1px solid #fed7aa;
            color: #9a3412;
            font-size: 0.84rem;
            font-weight: 600;
        }

        .ak-alert-danger {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            border-radius: 0.75rem;
            padding: 0.875rem 1rem;
            margin-bottom: 1rem;
        }

        /* Custom Accessible Modal Overlay (Tailwind v4 kompatibel) */
        .ak-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 1050;
            display: none;
            align-items: center;
            justify-content: center;
            padding: 1rem;
            background-color: rgba(15, 23, 42, 0.62);
            backdrop-filter: blur(3px);
        }

        .ak-modal-overlay.is-open {
            display: flex;
        }

        .ak-modal-dialog {
            width: 100%;
            max-width: 920px;
            max-height: 92vh;
            background-color: #ffffff;
            border-radius: 1rem;
            border: 1px solid #e2e8f0;
            box-shadow: 0 25px 50px -12px rgba(15, 23, 42, 0.35);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .ak-modal-header {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 1rem;
            padding: 1.15rem 1.5rem;
            border-bottom: 1px solid #e2e8f0;
            background-color: #ffffff;
        }

        .ak-modal-close {
            width: 2rem;
            height: 2rem;
            border-radius: 0.5rem;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            color: #475569;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 1.1rem;
            line-height: 1;
            flex-shrink: 0;
            transition: background-color 0.15s ease, color 0.15s ease;
        }

        .ak-modal-close:hover {
            background-color: #fee2e2;
            color: #b91c1c;
            border-color: #fecaca;
        }

        .ak-modal-body {
            padding: 1.25rem 1.5rem;
            overflow-y: auto;
            flex: 1 1 auto;
            min-height: 0;
        }

        .ak-modal-body .ak-student-list {
            max-height: 320px;
        }

        .ak-modal-footer {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 1rem 1.5rem;
            border-top: 1px solid #e2e8f0;
            background-color: #f8fafc;
        }

        @media (max-width: 767.98px) {
            .kelas-stat-grid {
                grid-template-columns: repeat(3, minmax(0, 1fr));
                width: 100%;
            }

            .ak-modal-overlay {
                padding: 0.5rem;
            }
        }
    </style>
@endPushOnce

