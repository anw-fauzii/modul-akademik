@extends('layouts.appui')

@section('title')
    <title>Jadwal Mengajar Saya - Guru Mapel SD</title>
@endsection

@pushOnce('css')
    <style>
        .gmjp-wrap {
            max-width: 1280px;
            margin: 0 auto;
        }

        /* Header minimalis */
        .gmjp-header {
            display: flex;
            align-items: flex-end;
            justify-content: space-between;
            gap: 1rem;
            flex-wrap: wrap;
            margin-bottom: 1.05rem;
        }

        .gmjp-title-wrap {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            min-width: 0;
        }

        .gmjp-icon {
            width: 42px;
            height: 42px;
            border-radius: 12px;
            background: linear-gradient(135deg, #fff7ed 0%, #ffedd5 100%);
            border: 1px solid #fed7aa;
            color: #ea580c;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.05rem;
            flex-shrink: 0;
        }

        .gmjp-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: -0.02em;
            line-height: 1.2;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .gmjp-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #c2410c;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .gmjp-sub {
            margin: 0.28rem 0 0;
            font-size: 0.81rem;
            color: #64748b;
            line-height: 1.4;
        }

        .gmjp-actions {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            flex-wrap: wrap;
        }

        .gmjp-btn-ghost {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.44rem 0.85rem;
            border-radius: 9px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #334155;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.15s ease, border-color 0.15s ease, color 0.15s ease;
        }

        .gmjp-btn-ghost:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
            text-decoration: none;
        }

        .gmjp-btn-accent {
            background: #fff7ed;
            border-color: #fed7aa;
            color: #c2410c;
        }

        .gmjp-btn-accent:hover {
            background: #ffedd5;
            border-color: #fdba74;
            color: #9a3412;
        }

        /* Switcher kelas horizontal */
        .gmjp-switcher {
            display: flex;
            align-items: center;
            gap: 0.4rem;
            flex-wrap: wrap;
            margin-bottom: 0.9rem;
        }

        .gmjp-switcher-label {
            font-size: 0.74rem;
            font-weight: 600;
            color: #64748b;
            margin-right: 0.15rem;
        }

        .gmjp-class-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.36rem 0.78rem;
            border-radius: 999px;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            color: #475569;
            font-size: 0.76rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.15s ease;
        }

        .gmjp-class-chip:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
            color: #0f172a;
            text-decoration: none;
        }

        .gmjp-class-chip.is-active {
            background: #ea580c;
            border-color: #ea580c;
            color: #ffffff;
        }

        .gmjp-class-select {
            height: 32px;
            padding: 0 0.7rem;
            border-radius: 999px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #334155;
            font-size: 0.76rem;
            font-weight: 600;
            outline: none;
            cursor: pointer;
        }

        .gmjp-class-select:focus,
        .gmjp-class-select.is-filtered {
            border-color: #ea580c;
            color: #c2410c;
            background: #fff7ed;
        }

        /* Kartu utama */
        .gmjp-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04);
            overflow: hidden;
        }

        .gmjp-toolbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            flex-wrap: wrap;
            padding: 0.75rem 1.125rem;
            border-bottom: 1px solid #f1f5f9;
            background: #fcfcfd;
        }

        .gmjp-meta-strip {
            display: flex;
            align-items: center;
            gap: 0.85rem;
            flex-wrap: wrap;
            font-size: 0.78rem;
            color: #475569;
        }

        .gmjp-meta-item {
            display: inline-flex;
            align-items: center;
            gap: 0.38rem;
            font-weight: 600;
            color: #334155;
        }

        .gmjp-meta-dot {
            width: 7px;
            height: 7px;
            border-radius: 999px;
            background: #ea580c;
            flex-shrink: 0;
        }

        .gmjp-meta-dot.is-green {
            background: #10b981;
        }

        .gmjp-filter-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.2rem 0.55rem;
            border-radius: 999px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #c2410c;
            font-size: 0.72rem;
            font-weight: 700;
        }

        .gmjp-reset-link {
            color: #9a3412;
            text-decoration: none;
            font-weight: 700;
            margin-left: 0.15rem;
        }

        .gmjp-reset-link:hover {
            color: #7c2d12;
            text-decoration: underline;
        }

        .gmjp-controls {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: nowrap;
        }

        .gmjp-search-box {
            position: relative;
            width: 225px;
        }

        .gmjp-search-box i {
            position: absolute;
            left: 0.68rem;
            top: 50%;
            transform: translateY(-50%);
            font-size: 0.74rem;
            color: #94a3b8;
            pointer-events: none;
        }

        .gmjp-search-input {
            width: 100%;
            height: 32px;
            padding: 0 1.65rem 0 1.9rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: #ffffff;
            font-size: 0.78rem;
            color: #0f172a;
            outline: none;
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .gmjp-search-input:focus {
            border-color: #fb923c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
        }

        .gmjp-search-input::placeholder {
            color: #94a3b8;
        }

        .gmjp-search-clear {
            position: absolute;
            right: 0.42rem;
            top: 50%;
            transform: translateY(-50%);
            width: 18px;
            height: 18px;
            border: 0;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            font-size: 0.72rem;
            line-height: 1;
            display: none;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            padding: 0;
        }

        .gmjp-search-clear.is-visible {
            display: inline-flex;
        }

        .gmjp-search-clear:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        .gmjp-seg {
            display: inline-flex;
            padding: 2px;
            border-radius: 9px;
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            flex-shrink: 0;
        }

        .gmjp-seg-btn {
            border: 0;
            background: transparent;
            color: #64748b;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.3rem 0.72rem;
            border-radius: 7px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .gmjp-seg-btn.is-active {
            background: #ffffff;
            color: #0f172a;
            box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
        }

        /* Matriks Mingguan */
        .gmjp-table-scroll {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        .gmjp-matrix {
            width: 100%;
            min-width: 800px;
            border-collapse: separate;
            border-spacing: 0;
            table-layout: fixed;
        }

        .gmjp-matrix col.col-time {
            width: 112px;
        }

        .gmjp-matrix col.col-day {
            width: calc((100% - 112px) / 5);
        }

        .gmjp-matrix thead th {
            padding: 0.7rem 0.65rem;
            background: #ffffff;
            border-bottom: 1px solid #e2e8f0;
            border-right: 1px solid #f1f5f9;
            text-align: left;
            font-size: 0.76rem;
            font-weight: 700;
            color: #334155;
            vertical-align: middle;
        }

        .gmjp-matrix thead th:last-child {
            border-right: 0;
        }

        .gmjp-matrix thead th.is-today {
            background: #fff7ed;
            color: #c2410c;
        }

        .gmjp-th-inner {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
        }

        .gmjp-day-title {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .gmjp-today-tag {
            font-size: 0.61rem;
            font-weight: 700;
            padding: 0.08rem 0.38rem;
            border-radius: 999px;
            background: #ea580c;
            color: #ffffff;
            letter-spacing: 0.01em;
        }

        .gmjp-day-count {
            font-size: 0.67rem;
            font-weight: 700;
            min-width: 20px;
            height: 20px;
            padding: 0 0.32rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .gmjp-matrix thead th.is-today .gmjp-day-count {
            background: #ffedd5;
            color: #c2410c;
        }

        .gmjp-matrix tbody td {
            padding: 0.52rem 0.52rem;
            border-bottom: 1px solid #f1f5f9;
            border-right: 1px solid #f1f5f9;
            vertical-align: top;
            background: #ffffff;
        }

        .gmjp-matrix tbody td:last-child {
            border-right: 0;
        }

        .gmjp-matrix tbody tr:last-child td {
            border-bottom: 0;
        }

        .gmjp-matrix tbody td.is-today {
            background: #fffcf9;
        }

        .gmjp-time-col {
            background: #fafbfc !important;
            border-right: 1px solid #e2e8f0 !important;
            padding: 0.62rem 0.7rem !important;
        }

        .gmjp-time-range {
            display: block;
            font-size: 0.75rem;
            font-weight: 700;
            color: #0f172a;
            font-variant-numeric: tabular-nums;
            line-height: 1.3;
        }

        .gmjp-jam-ke {
            display: block;
            margin-top: 0.14rem;
            font-size: 0.66rem;
            font-weight: 500;
            color: #94a3b8;
        }

        .gmjp-cell-empty {
            height: 52px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #e2e8f0;
            font-size: 0.78rem;
            user-select: none;
        }

        .gmjp-slot-stack {
            display: flex;
            flex-direction: column;
            gap: 0.4rem;
        }

        /* Kartu slot ajar */
        .gmjp-slot {
            padding: 0.48rem 0.56rem;
            border-radius: 9px;
            background: #fafafa;
            border: 1px solid #e2e8f0;
            border-left: 3px solid #ea580c;
            transition: background 0.15s ease, border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .gmjp-slot:hover {
            background: #ffffff;
            border-color: #cbd5e1;
            border-left-color: #ea580c;
            box-shadow: 0 2px 6px rgba(15, 23, 42, 0.05);
        }

        .gmjp-slot.is-dimmed {
            display: none;
        }

        .gmjp-slot-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
            margin-bottom: 0.22rem;
        }

        .gmjp-slot-kelas {
            display: inline-flex;
            align-items: center;
            padding: 0.1rem 0.42rem;
            border-radius: 5px;
            background: #fff7ed;
            border: 1px solid #fed7aa;
            color: #c2410c;
            font-size: 0.65rem;
            font-weight: 700;
            white-space: nowrap;
        }

        .gmjp-slot-ruang {
            font-size: 0.65rem;
            font-weight: 500;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 96px;
        }

        .gmjp-slot-subject {
            font-size: 0.78rem;
            font-weight: 700;
            color: #0f172a;
            line-height: 1.3;
            word-break: break-word;
        }

        .gmjp-slot-note {
            margin-top: 0.16rem;
            font-size: 0.68rem;
            color: #64748b;
            line-height: 1.3;
        }

        .gmjp-slot-foot {
            margin-top: 0.32rem;
            padding-top: 0.26rem;
            border-top: 1px dashed #e2e8f0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.35rem;
        }

        .gmjp-slot-link {
            font-size: 0.67rem;
            font-weight: 700;
            color: #c2410c;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.22rem;
        }

        .gmjp-slot-link:hover {
            color: #9a3412;
            text-decoration: underline;
        }

        /* Tampilan Agenda Per Hari */
        .gmjp-agenda-wrap {
            padding: 1rem 1.125rem 1.15rem;
        }

        .gmjp-agenda-grid {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.75rem;
        }

        @media (max-width: 1199.98px) {
            .gmjp-agenda-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 639.98px) {
            .gmjp-agenda-grid {
                grid-template-columns: 1fr;
            }

            .gmjp-controls {
                width: 100%;
                justify-content: space-between;
            }

            .gmjp-search-box {
                flex: 1 1 auto;
                width: auto;
            }
        }

        .gmjp-day-col {
            border: 1px solid #e2e8f0;
            border-radius: 11px;
            background: #fafbfc;
            display: flex;
            flex-direction: column;
            min-height: 180px;
            overflow: hidden;
        }

        .gmjp-day-col.is-today {
            border-color: #fdba74;
            background: #fffdfb;
        }

        .gmjp-day-head {
            padding: 0.62rem 0.75rem;
            border-bottom: 1px solid #f1f5f9;
            background: #ffffff;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.4rem;
        }

        .gmjp-day-col.is-today .gmjp-day-head {
            background: #fff7ed;
            border-bottom-color: #fed7aa;
        }

        .gmjp-day-name {
            font-size: 0.8rem;
            font-weight: 800;
            color: #0f172a;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .gmjp-day-col.is-today .gmjp-day-name {
            color: #9a3412;
        }

        .gmjp-day-badge {
            font-size: 0.68rem;
            font-weight: 700;
            padding: 0.1rem 0.45rem;
            border-radius: 999px;
            background: #f1f5f9;
            color: #64748b;
        }

        .gmjp-day-col.is-today .gmjp-day-badge {
            background: #ffedd5;
            color: #c2410c;
        }

        .gmjp-day-list {
            padding: 0.55rem;
            display: flex;
            flex-direction: column;
            gap: 0.48rem;
            flex: 1 1 auto;
        }

        .gmjp-time-badge {
            display: inline-flex;
            align-items: center;
            padding: 0.08rem 0.42rem;
            border-radius: 5px;
            background: #f1f5f9;
            color: #334155;
            font-size: 0.66rem;
            font-weight: 700;
            font-variant-numeric: tabular-nums;
        }

        .gmjp-day-empty {
            flex: 1 1 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1.5rem 0.6rem;
            text-align: center;
            font-size: 0.73rem;
            color: #94a3b8;
        }

        /* Empty & Guard states */
        .gmjp-empty {
            padding: 3rem 1.25rem;
            text-align: center;
            color: #64748b;
        }

        .gmjp-empty-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: #fff7ed;
            color: #ea580c;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 1.15rem;
            margin-bottom: 0.75rem;
        }

        .gmjp-empty-title {
            font-size: 0.94rem;
            font-weight: 700;
            color: #0f172a;
            margin-bottom: 0.3rem;
        }

        .gmjp-empty-desc {
            font-size: 0.81rem;
            color: #64748b;
            max-width: 420px;
            margin: 0 auto;
        }

        .gmjp-search-empty {
            padding: 2rem 1.25rem;
            text-align: center;
            color: #64748b;
            font-size: 0.82rem;
            border-top: 1px solid #f1f5f9;
        }
    </style>
@endPushOnce

@section('content')
    @php
        $selectedKelasId = (string) ($filters['kelas_id'] ?? '') !== '' ? (int) $filters['kelas_id'] : null;
        $selectedHari = (string) ($filters['hari'] ?? '');
        $initialSearch = (string) ($filters['search'] ?? '');
        $todayDayNum = (int) now()->dayOfWeekIso;
        $agendaByDay = $jadwalPelajaran
            ->groupBy(fn ($item) => (int) $item->hari)
            ->map(fn ($items) => $items->sortBy(fn ($item) => (int) ($item->jamPelajaran?->jam_ke ?? 0))->values());
        $totalJamCount = (int) ($summary['total'] ?? $jadwalPelajaran->count());
        $totalClassCount = (int) ($summary['classes'] ?? $taughtClasses->count());
    @endphp

    <div class="gmjp-wrap">
        <div class="gmjp-header">
            <div class="gmjp-title-wrap">
                <div class="gmjp-icon" aria-hidden="true">
                    <i class="fa fa-calendar-check"></i>
                </div>
                <div>
                    <h1 class="gmjp-title">
                        <span>Jadwal Mengajar Saya</span>
                        <span class="gmjp-pill">Guru Mapel SD</span>
                    </h1>
                    <p class="gmjp-sub">
                        @if ($activeTahunAjaran)
                            {{ $activeTahunAjaran->full_label }}
                        @else
                            Tahun ajaran aktif belum diatur
                        @endif
                        @if ($karyawanProfile?->nama_gelar)
                            &middot; {{ $karyawanProfile->nama_gelar }}
                        @endif
                        @if ($nipy !== '')
                            &middot; NIPY {{ $nipy }}
                        @endif
                    </p>
                </div>
            </div>

            <div class="gmjp-actions">
                <a href="{{ route('guru-mapel.dashboard') }}" class="gmjp-btn-ghost">
                    <i class="fa fa-arrow-left"></i> Dashboard
                </a>
                <a href="{{ route('guru-mapel.penilaian-tp.index') }}" class="gmjp-btn-ghost gmjp-btn-accent">
                    <i class="fa fa-clipboard-check"></i> Input Nilai TP
                </a>
            </div>
        </div>

        @if (! $isEligible)
            <div class="gmjp-card">
                <div class="gmjp-empty">
                    <div class="gmjp-empty-icon">
                        <i class="fa fa-user-lock"></i>
                    </div>
                    <div class="gmjp-empty-title">Jadwal Mengajar Belum Tersedia</div>
                    <p class="gmjp-empty-desc">
                        {{ $ineligibleReason ?: 'Akun Anda belum terhubung dengan penugasan mengajar SD pada tahun ajaran aktif.' }}
                    </p>
                </div>
            </div>
        @else
            @if ($taughtClasses->isNotEmpty())
                <div class="gmjp-switcher" role="navigation" aria-label="Filter kelas yang diajar">
                    <span class="gmjp-switcher-label">Kelas:</span>
                    <a href="{{ route('guru-mapel.jadwal-pelajaran.index', array_filter(['hari' => $selectedHari !== '' ? $selectedHari : null])) }}"
                        class="gmjp-class-chip {{ $selectedKelasId === null ? 'is-active' : '' }}">
                        Semua Kelas ({{ $taughtClasses->count() }})
                    </a>
                    @if ($taughtClasses->count() > 5)
                        @php
                            $allJadwalUrl = route('guru-mapel.jadwal-pelajaran.index', array_filter(['hari' => $selectedHari !== '' ? $selectedHari : null]));
                            $groupedTaughtClasses = $taughtClasses
                                ->groupBy(static function ($k): string {
                                    $tingkat = trim((string) ($k->tingkatan_kelas ?? ''));

                                    return $tingkat !== '' ? 'Tingkat '.$tingkat : 'Daftar Kelas';
                                })
                                ->sortKeys(SORT_NATURAL);
                        @endphp
                        <select class="gmjp-class-select {{ $selectedKelasId !== null ? 'is-filtered' : '' }}"
                            aria-label="Pilih kelas yang diajar"
                            onchange="if (this.value) window.location.href = this.value;">
                            <option value="{{ $allJadwalUrl }}" {{ $selectedKelasId === null ? 'selected' : '' }}>
                                Pilih kelas tertentu...
                            </option>
                            @foreach ($groupedTaughtClasses as $groupLabel => $groupClasses)
                                <optgroup label="{{ $groupLabel }}">
                                    @foreach ($groupClasses as $kOpt)
                                        @php
                                            $encodedKelasOpt = \App\Support\Sqid::encodeQueryValue('kelas_id', $kOpt->id);
                                            $optUrl = route('guru-mapel.jadwal-pelajaran.index', array_filter([
                                                'kelas_id' => $encodedKelasOpt,
                                                'hari' => $selectedHari !== '' ? $selectedHari : null,
                                            ]));
                                        @endphp
                                        <option value="{{ $optUrl }}" {{ $selectedKelasId === (int) $kOpt->id ? 'selected' : '' }}>
                                            Kelas {{ $kOpt->nama_kelas }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    @else
                        @foreach ($taughtClasses as $kOpt)
                            @php
                                $encodedKelasOpt = \App\Support\Sqid::encodeQueryValue('kelas_id', $kOpt->id);
                            @endphp
                            <a href="{{ route('guru-mapel.jadwal-pelajaran.index', array_filter(['kelas_id' => $encodedKelasOpt, 'hari' => $selectedHari !== '' ? $selectedHari : null])) }}"
                                class="gmjp-class-chip {{ $selectedKelasId === (int) $kOpt->id ? 'is-active' : '' }}">
                                Kelas {{ $kOpt->nama_kelas }}
                            </a>
                        @endforeach
                    @endif
                </div>
            @endif

            <div class="gmjp-card">
                <div class="gmjp-toolbar">
                    <div class="gmjp-meta-strip">
                        <span class="gmjp-meta-item">
                            <span class="gmjp-meta-dot is-green"></span>
                            {{ $totalJamCount }} Sesi Mengajar
                        </span>
                        <span class="gmjp-meta-item">
                            <span class="gmjp-meta-dot"></span>
                            {{ $totalClassCount }} Kelas Diajar
                        </span>
                        @if ($selectedHari !== '' && isset($hariOptions[(int) $selectedHari]))
                            <span class="gmjp-filter-tag">
                                Hari {{ $hariOptions[(int) $selectedHari] }}
                                <a href="{{ route('guru-mapel.jadwal-pelajaran.index', array_filter(['kelas_id' => $selectedKelasId ? \App\Support\Sqid::encodeQueryValue('kelas_id', $selectedKelasId) : null])) }}"
                                    class="gmjp-reset-link" title="Tampilkan semua hari">&times;</a>
                            </span>
                        @endif
                    </div>

                    @if ($jadwalPelajaran->isNotEmpty())
                        <div class="gmjp-controls">
                            <div class="gmjp-search-box">
                                <i class="fa fa-search" aria-hidden="true"></i>
                                <input type="search" id="gmjpSearchInput" class="gmjp-search-input"
                                    value="{{ $initialSearch }}"
                                    placeholder="Cari mapel, kelas, ruang..."
                                    aria-label="Cari jadwal mengajar">
                                <button type="button" id="gmjpSearchClear"
                                    class="gmjp-search-clear {{ $initialSearch !== '' ? 'is-visible' : '' }}"
                                    aria-label="Hapus pencarian">&times;</button>
                            </div>

                            <div class="gmjp-seg" role="group" aria-label="Mode tampilan jadwal">
                                <button type="button" class="gmjp-seg-btn is-active" data-gmjp-view="matrix" aria-pressed="true">
                                    <i class="fa fa-th"></i> Matriks
                                </button>
                                <button type="button" class="gmjp-seg-btn" data-gmjp-view="agenda" aria-pressed="false">
                                    <i class="fa fa-columns"></i> Per Hari
                                </button>
                            </div>
                        </div>
                    @endif
                </div>

                @if ($jadwalPelajaran->isEmpty() || $jamSlots->isEmpty())
                    <div class="gmjp-empty">
                        <div class="gmjp-empty-icon">
                            <i class="fa fa-calendar-times"></i>
                        </div>
                        <div class="gmjp-empty-title">Belum Ada Jadwal Mengajar</div>
                        <p class="gmjp-empty-desc">
                            Tidak ada sesi mengajar yang cocok dengan filter saat ini atau slot jadwal SD Anda belum diinput oleh Admin.
                        </p>
                    </div>
                @else
                    {{-- Mode 1: Matriks Mingguan --}}
                    <div id="gmjpMatrixPanel" class="gmjp-table-scroll">
                        <table class="gmjp-matrix">
                            <colgroup>
                                <col class="col-time">
                                @foreach ($hariOptions as $dayNum => $dayLabel)
                                    <col class="col-day">
                                @endforeach
                            </colgroup>
                            <thead>
                                <tr>
                                    <th>Jam</th>
                                    @foreach ($hariOptions as $dayNum => $dayLabel)
                                        @php
                                            $isToday = $todayDayNum === $dayNum;
                                            $dayCount = (int) ($summary['per_hari'][$dayNum] ?? 0);
                                        @endphp
                                        <th class="{{ $isToday ? 'is-today' : '' }}">
                                            <div class="gmjp-th-inner">
                                                <span class="gmjp-day-title">
                                                    <span>{{ $dayLabel }}</span>
                                                    @if ($isToday)
                                                        <span class="gmjp-today-tag">Hari Ini</span>
                                                    @endif
                                                </span>
                                                <span class="gmjp-day-count">{{ $dayCount }}</span>
                                            </div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($jamSlots as $jam)
                                    <tr>
                                        <td class="gmjp-time-col">
                                            <span class="gmjp-time-range">{{ $jam->rentang_waktu }}</span>
                                            <span class="gmjp-jam-ke">Jam ke-{{ $jam->jam_ke }}</span>
                                        </td>
                                        @foreach ($hariOptions as $dayNum => $dayLabel)
                                            @php
                                                $isToday = $todayDayNum === $dayNum;
                                                $cellSlots = $weeklySlots[$jam->id][$dayNum] ?? [];
                                            @endphp
                                            <td class="{{ $isToday ? 'is-today' : '' }}">
                                                @if (empty($cellSlots))
                                                    <div class="gmjp-cell-empty">&mdash;</div>
                                                @else
                                                    <div class="gmjp-slot-stack">
                                                        @foreach ($cellSlots as $slot)
                                                            @php
                                                                $mapelNama = $slot->mataPelajaran?->nama_mata_pelajaran ?: ($slot->keterangan ?: 'Sesi Mengajar');
                                                                $kelasNama = $slot->kelas?->nama_kelas ?? '-';
                                                                $ruangText = trim((string) ($slot->ruang ?? ''));
                                                                $searchHaystack = mb_strtolower(trim($mapelNama.' kelas '.$kelasNama.' '.$ruangText.' '.$dayLabel));
                                                                $hasTpShortcut = $slot->kelas && $slot->kelas->getKey() && $slot->mataPelajaran && $slot->mataPelajaran->getKey();
                                                            @endphp
                                                            <div class="gmjp-slot" data-gmjp-item="{{ $searchHaystack }}">
                                                                <div class="gmjp-slot-top">
                                                                    <span class="gmjp-slot-kelas">Kelas {{ $kelasNama }}</span>
                                                                    @if ($ruangText !== '')
                                                                        <span class="gmjp-slot-ruang" title="{{ $ruangText }}">
                                                                            {{ $ruangText }}
                                                                        </span>
                                                                    @endif
                                                                </div>
                                                                <div class="gmjp-slot-subject">{{ $mapelNama }}</div>
                                                                @if ($slot->keterangan && $slot->mataPelajaran)
                                                                    <div class="gmjp-slot-note">{{ $slot->keterangan }}</div>
                                                                @endif
                                                                @if ($hasTpShortcut)
                                                                    <div class="gmjp-slot-foot">
                                                                        <a href="{{ route('guru-mapel.penilaian-tp.index', ['kelas_id' => \App\Support\Sqid::encodeQueryValue('kelas_id', $slot->kelas->id), 'mata_pelajaran_id' => \App\Support\Sqid::encodeQueryValue('mata_pelajaran_id', $slot->mataPelajaran->id)]) }}"
                                                                            class="gmjp-slot-link">
                                                                            Nilai TP <i class="fa fa-arrow-right"></i>
                                                                        </a>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    {{-- Mode 2: Agenda Per Hari --}}
                    <div id="gmjpAgendaPanel" class="gmjp-agenda-wrap" hidden>
                        <div class="gmjp-agenda-grid">
                            @foreach ($hariOptions as $dayNum => $dayLabel)
                                @php
                                    $isToday = $todayDayNum === $dayNum;
                                    $daySlots = $agendaByDay->get($dayNum, collect());
                                @endphp
                                <div class="gmjp-day-col {{ $isToday ? 'is-today' : '' }}" data-gmjp-day-card="{{ $dayNum }}">
                                    <div class="gmjp-day-head">
                                        <span class="gmjp-day-name">
                                            {{ $dayLabel }}
                                            @if ($isToday)
                                                <span class="gmjp-today-tag">Hari Ini</span>
                                            @endif
                                        </span>
                                        <span class="gmjp-day-badge">{{ $daySlots->count() }}</span>
                                    </div>

                                    <div class="gmjp-day-list">
                                        @forelse ($daySlots as $slot)
                                            @php
                                                $mapelNama = $slot->mataPelajaran?->nama_mata_pelajaran ?: ($slot->keterangan ?: 'Sesi Mengajar');
                                                $kelasNama = $slot->kelas?->nama_kelas ?? '-';
                                                $ruangText = trim((string) ($slot->ruang ?? ''));
                                                $searchHaystack = mb_strtolower(trim($mapelNama.' kelas '.$kelasNama.' '.$ruangText.' '.$dayLabel));
                                                $hasTpShortcut = $slot->kelas && $slot->kelas->getKey() && $slot->mataPelajaran && $slot->mataPelajaran->getKey();
                                            @endphp
                                            <div class="gmjp-slot" data-gmjp-agenda-item="{{ $searchHaystack }}">
                                                <div class="gmjp-slot-top">
                                                    <span class="gmjp-time-badge">
                                                        {{ $slot->jamPelajaran?->rentang_waktu ?? '-' }}
                                                    </span>
                                                    @if ($ruangText !== '')
                                                        <span class="gmjp-slot-ruang" title="{{ $ruangText }}">
                                                            {{ $ruangText }}
                                                        </span>
                                                    @endif
                                                </div>
                                                <div class="gmjp-slot-subject">{{ $mapelNama }}</div>
                                                <div class="gmjp-slot-note">
                                                    <span class="gmjp-slot-kelas">Kelas {{ $kelasNama }}</span>
                                                    <span style="margin-left: 0.25rem;">Jam ke-{{ $slot->jamPelajaran?->jam_ke ?? '-' }}</span>
                                                </div>
                                                @if ($hasTpShortcut)
                                                    <div class="gmjp-slot-foot">
                                                        <a href="{{ route('guru-mapel.penilaian-tp.index', ['kelas_id' => \App\Support\Sqid::encodeQueryValue('kelas_id', $slot->kelas->id), 'mata_pelajaran_id' => \App\Support\Sqid::encodeQueryValue('mata_pelajaran_id', $slot->mataPelajaran->id)]) }}"
                                                            class="gmjp-slot-link">
                                                            Nilai TP
                                                        </a>
                                                        <a href="{{ route('guru-mapel.perkembangan-siswa.index', ['kelas_id' => \App\Support\Sqid::encodeQueryValue('kelas_id', $slot->kelas->id)]) }}"
                                                            class="gmjp-slot-link" style="color: #475569;">
                                                            Leger
                                                        </a>
                                                    </div>
                                                @endif
                                            </div>
                                        @empty
                                            <div class="gmjp-day-empty">
                                                Tidak ada jam mengajar
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div id="gmjpSearchEmpty" class="gmjp-search-empty" hidden>
                        Tidak ada jadwal mengajar yang cocok dengan pencarian Anda.
                    </div>
                @endif
            </div>
        @endif
    </div>
@endsection

@push('js')
    <script>
        (function () {
            const viewButtons = document.querySelectorAll('[data-gmjp-view]');
            const matrixPanel = document.getElementById('gmjpMatrixPanel');
            const agendaPanel = document.getElementById('gmjpAgendaPanel');
            const searchInput = document.getElementById('gmjpSearchInput');
            const searchClear = document.getElementById('gmjpSearchClear');
            const searchEmpty = document.getElementById('gmjpSearchEmpty');

            if (!matrixPanel || !agendaPanel) {
                return;
            }

            let activeView = 'matrix';

            function applySearch() {
                const rawQuery = searchInput ? searchInput.value : '';
                const query = rawQuery.trim().toLowerCase();

                if (searchClear) {
                    searchClear.classList.toggle('is-visible', query !== '');
                }

                const selector = activeView === 'matrix'
                    ? '[data-gmjp-item]'
                    : '[data-gmjp-agenda-item]';
                const items = document.querySelectorAll(selector);
                let visibleCount = 0;

                items.forEach(function (el) {
                    const text = (el.getAttribute(activeView === 'matrix' ? 'data-gmjp-item' : 'data-gmjp-agenda-item') || '').toLowerCase();
                    const matched = query === '' || text.includes(query);
                    el.classList.toggle('is-dimmed', !matched);
                    if (matched) {
                        visibleCount++;
                    }
                });

                if (searchEmpty) {
                    searchEmpty.hidden = !(query !== '' && visibleCount === 0);
                }
            }

            viewButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const target = btn.getAttribute('data-gmjp-view');
                    if (!target || target === activeView) {
                        return;
                    }

                    activeView = target;
                    viewButtons.forEach(function (other) {
                        const isActive = other === btn;
                        other.classList.toggle('is-active', isActive);
                        other.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                    });

                    matrixPanel.hidden = activeView !== 'matrix';
                    agendaPanel.hidden = activeView !== 'agenda';
                    applySearch();
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', applySearch);
                searchInput.addEventListener('keydown', function (event) {
                    if (event.key === 'Escape' && searchInput.value !== '') {
                        searchInput.value = '';
                        applySearch();
                    }
                });
                if (searchInput.value.trim() !== '') {
                    applySearch();
                }
            }

            if (searchClear && searchInput) {
                searchClear.addEventListener('click', function () {
                    searchInput.value = '';
                    searchInput.focus();
                    applySearch();
                });
            }
        })();
    </script>
@endPush
