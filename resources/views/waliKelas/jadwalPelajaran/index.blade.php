@extends('layouts.appui')

@section('title')
    <title>Jadwal Pelajaran {{ $selectedKelas?->nama_kelas ?? 'Kelas Binaan' }} - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @push('css')
        <style>
            .wkjp-header {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem 1rem;
                margin-bottom: 0.9rem;
            }

            .wkjp-title-wrap {
                display: flex;
                align-items: center;
                gap: 0.75rem;
            }

            .wkjp-icon {
                width: 40px;
                height: 40px;
                border-radius: 11px;
                background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);
                color: #ffffff;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                font-size: 1.05rem;
                box-shadow: 0 6px 14px -6px rgba(234, 88, 12, 0.55);
                flex-shrink: 0;
            }

            .wkjp-title {
                margin: 0;
                font-size: 1.08rem;
                font-weight: 800;
                color: #0f172a;
                letter-spacing: -0.015em;
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.45rem;
            }

            .wkjp-pill {
                display: inline-flex;
                align-items: center;
                gap: 0.3rem;
                padding: 0.18rem 0.55rem;
                border-radius: 999px;
                font-size: 0.68rem;
                font-weight: 700;
                background: #fff7ed;
                color: #c2410c;
                border: 1px solid #fed7aa;
            }

            .wkjp-sub {
                font-size: 0.76rem;
                color: #64748b;
                margin-top: 0.1rem;
            }

            .wkjp-actions {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
            }

            .wkjp-switcher {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.4rem;
                margin-bottom: 0.85rem;
            }

            .wkjp-class-chip {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                padding: 0.32rem 0.72rem;
                border-radius: 999px;
                border: 1px solid #e2e8f0;
                background: #ffffff;
                color: #475569;
                font-size: 0.74rem;
                font-weight: 700;
                text-decoration: none !important;
                transition: all 0.15s ease;
            }

            .wkjp-class-chip:hover {
                border-color: #fdba74;
                color: #c2410c;
            }

            .wkjp-class-chip.is-active {
                background: #0f172a;
                border-color: #0f172a;
                color: #ffffff;
            }

            .wkjp-card {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                overflow: hidden;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03), 0 8px 24px -18px rgba(15, 23, 42, 0.14);
            }

            .wkjp-toolbar {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.65rem 1rem;
                padding: 0.75rem 1rem;
                border-bottom: 1px solid #f1f5f9;
                background: #fcfcfd;
            }

            .wkjp-meta-strip {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.65rem;
                font-size: 0.73rem;
                color: #64748b;
                font-weight: 600;
            }

            .wkjp-dot {
                width: 6px;
                height: 6px;
                border-radius: 999px;
                display: inline-block;
            }

            .wkjp-controls {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                gap: 0.5rem;
            }

            .wkjp-search-box {
                position: relative;
            }

            .wkjp-search-box i {
                position: absolute;
                left: 0.65rem;
                top: 50%;
                transform: translateY(-50%);
                font-size: 0.72rem;
                color: #94a3b8;
                pointer-events: none;
            }

            .wkjp-search-input {
                height: 31px;
                width: 195px;
                padding: 0 0.65rem 0 1.85rem;
                border-radius: 8px;
                border: 1px solid #e2e8f0;
                background: #ffffff;
                font-size: 0.75rem;
                color: #0f172a;
                outline: none;
                transition: border-color 0.15s ease, box-shadow 0.15s ease;
            }

            .wkjp-search-input:focus {
                border-color: #ea580c;
                box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.1);
            }

            .wkjp-seg {
                display: inline-flex;
                padding: 0.18rem;
                border-radius: 9px;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                gap: 0.15rem;
            }

            .wkjp-seg-btn {
                border: 0;
                background: transparent;
                padding: 0.25rem 0.65rem;
                border-radius: 7px;
                font-size: 0.73rem;
                font-weight: 700;
                color: #64748b;
                cursor: pointer;
                transition: all 0.15s ease;
            }

            .wkjp-seg-btn.is-active {
                background: #ffffff;
                color: #0f172a;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.08);
            }

            /* Matrix Table - Compact & Modern */
            .wkjp-matrix-scroll {
                overflow-x: auto;
            }

            .wkjp-matrix {
                width: 100%;
                min-width: 820px;
                border-collapse: separate;
                border-spacing: 0;
                table-layout: fixed;
            }

            .wkjp-matrix th {
                padding: 0.58rem 0.65rem;
                font-size: 0.72rem;
                font-weight: 700;
                text-transform: uppercase;
                letter-spacing: 0.04em;
                color: #475569;
                background: #f8fafc;
                border-bottom: 1px solid #e2e8f0;
                border-right: 1px solid #f1f5f9;
            }

            .wkjp-matrix th:last-child {
                border-right: 0;
            }

            .wkjp-matrix th.is-today {
                background: #fff7ed;
                color: #c2410c;
            }

            .wkjp-matrix td {
                padding: 0.35rem 0.4rem;
                vertical-align: middle;
                border-bottom: 1px solid #f1f5f9;
                border-right: 1px solid #f1f5f9;
                background: #ffffff;
            }

            .wkjp-matrix td:last-child {
                border-right: 0;
            }

            .wkjp-matrix tr:last-child td {
                border-bottom: 0;
            }

            .wkjp-time-col {
                width: 106px;
                background: #fcfcfd !important;
                text-align: center;
                padding: 0.45rem 0.5rem !important;
            }

            .wkjp-time-range {
                font-size: 0.74rem;
                font-weight: 700;
                color: #0f172a;
                font-variant-numeric: tabular-nums;
                line-height: 1.25;
            }

            .wkjp-time-jam {
                font-size: 0.66rem;
                font-weight: 600;
                color: #94a3b8;
                margin-top: 0.1rem;
            }

            .wkjp-slot {
                display: flex;
                flex-direction: column;
                gap: 0.22rem;
                padding: 0.42rem 0.55rem;
                border-radius: 9px;
                border: 1px solid #f1f5f9;
                border-left: 3px solid #ea580c;
                background: #f8fafc;
                transition: opacity 0.15s ease, transform 0.15s ease, box-shadow 0.15s ease;
            }

            .wkjp-slot:hover {
                background: #ffffff;
                border-color: #e2e8f0;
                border-left-color: #ea580c;
                box-shadow: 0 3px 10px -6px rgba(15, 23, 42, 0.2);
            }

            .wkjp-slot.is-non-mapel {
                border-left-color: #f59e0b;
                background: #fffbeb;
            }

            .wkjp-slot.is-inactive {
                border-left-color: #94a3b8;
                background: #f8fafc;
                opacity: 0.65;
            }

            .wkjp-slot.is-dimmed {
                opacity: 0.18;
            }

            .wkjp-slot-top {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 0.3rem;
            }

            .wkjp-code {
                font-size: 0.63rem;
                font-weight: 800;
                letter-spacing: 0.02em;
                padding: 0.08rem 0.38rem;
                border-radius: 5px;
                background: #fff7ed;
                color: #c2410c;
            }

            .wkjp-slot.is-non-mapel .wkjp-code {
                background: #fef3c7;
                color: #92400e;
            }

            .wkjp-room {
                font-size: 0.63rem;
                font-weight: 600;
                color: #64748b;
            }

            .wkjp-subject {
                font-size: 0.75rem;
                font-weight: 700;
                color: #0f172a;
                line-height: 1.25;
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }

            .wkjp-teacher {
                font-size: 0.67rem;
                color: #64748b;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .wkjp-empty-cell {
                text-align: center;
                font-size: 0.72rem;
                color: #cbd5e1;
                user-select: none;
            }

            /* Day Agenda Panel */
            .wkjp-agenda-grid {
                display: grid;
                grid-template-columns: repeat(1, minmax(0, 1fr));
                gap: 0.75rem;
                padding: 0.9rem;
            }

            @media (min-width: 768px) {
                .wkjp-agenda-grid {
                    grid-template-columns: repeat(2, minmax(0, 1fr));
                }
            }

            @media (min-width: 1200px) {
                .wkjp-agenda-grid {
                    grid-template-columns: repeat(5, minmax(0, 1fr));
                }
            }

            .wkjp-day-col {
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                background: #fcfcfd;
                overflow: hidden;
                display: flex;
                flex-direction: column;
            }

            .wkjp-day-col.is-today {
                border-color: #fdba74;
                background: #fffaf5;
            }

            .wkjp-day-head {
                padding: 0.55rem 0.75rem;
                border-bottom: 1px solid #f1f5f9;
                display: flex;
                align-items: center;
                justify-content: space-between;
                font-size: 0.76rem;
                font-weight: 800;
                color: #0f172a;
            }

            .wkjp-day-list {
                padding: 0.55rem;
                display: flex;
                flex-direction: column;
                gap: 0.45rem;
            }
        </style>
    @endpush

    @php
        $assignedClasses = $assignedClasses ?? collect();
        $jadwalPelajaran = $jadwalPelajaran ?? collect();
        $hariOptions = $hariOptions ?? \App\Models\Admin\Pelajaran\JadwalPelajaran::HARI_OPTIONS;
        $weeklyMatrix = $weeklyMatrix ?? null;
        $summary = $summary ?? ['total' => 0, 'aktif' => 0, 'nonaktif' => 0, 'per_hari' => []];
        $todayIsoDay = \Carbon\Carbon::now()->dayOfWeekIso;
        $byHari = $jadwalPelajaran->groupBy('hari');
    @endphp

    <div class="app-main__inner">
        <!-- HEADER RINGKAS -->
        <div class="wkjp-header">
            <div class="wkjp-title-wrap">
                <div class="wkjp-icon">
                    <i class="pe-7s-date"></i>
                </div>
                <div>
                    <h1 class="wkjp-title">
                        <span>Jadwal Pelajaran &bull; {{ $selectedKelas?->nama_kelas ?? '-' }}</span>
                        <span class="wkjp-pill">
                            <i class="fa fa-eye"></i> {{ $peranDiKelas ?: 'Wali Kelas' }} &bull; Hanya Lihat
                        </span>
                    </h1>
                    <div class="wkjp-sub">
                        {{ $activeTahunAjaran?->full_label }} &bull;
                        Jenjang {{ strtoupper((string) ($selectedKelas?->jenjang_kelas ?? '-')) }} (Tingkat {{ $selectedKelas?->tingkatan_kelas ?? '-' }})
                        @if ($selectedKelas?->waliKelas?->nama_gelar)
                            &bull; Wali Kelas: {{ $selectedKelas->waliKelas->nama_gelar }}
                        @endif
                    </div>
                </div>
            </div>

            <div class="wkjp-actions">
                <a href="{{ route('wali-kelas.dashboard', $selectedKelas ? ['kelas_id' => $selectedKelas->sqid] : []) }}"
                    class="btn btn-sm btn-light border font-weight-semibold px-3"
                    style="border-radius: 9px; color: #334155;">
                    <i class="fa fa-arrow-left mr-1"></i> Dashboard
                </a>
            </div>
        </div>

        @if ($assignedClasses->count() > 1)
            <div class="wkjp-switcher">
                <span class="small font-weight-bold text-muted mr-1">Kelas Binaan:</span>
                @foreach ($assignedClasses as $kelasOption)
                    @php
                        $isCurrent = (int) $kelasOption->id === (int) ($selectedKelas?->id ?? 0);
                    @endphp
                    <a href="{{ route('wali-kelas.jadwal-pelajaran.index', ['kelas_id' => $kelasOption->sqid]) }}"
                        class="wkjp-class-chip {{ $isCurrent ? 'is-active' : '' }}">
                        <span>{{ $kelasOption->nama_kelas }}</span>
                    </a>
                @endforeach
            </div>
        @endif

        <!-- SATU PANEL TERPADU (MATRIKS / PER HARI) -->
        <div class="wkjp-card">
            <div class="wkjp-toolbar">
                <div class="wkjp-meta-strip">
                    <span class="d-inline-flex align-items-center" style="gap: 0.35rem; color: #0f172a;">
                        <span class="wkjp-dot" style="background: #10b981;"></span>
                        {{ $summary['aktif'] ?? 0 }} Sesi Aktif
                    </span>
                    @if (($summary['nonaktif'] ?? 0) > 0)
                        <span class="d-inline-flex align-items-center" style="gap: 0.35rem;">
                            <span class="wkjp-dot" style="background: #94a3b8;"></span>
                            {{ $summary['nonaktif'] }} Nonaktif
                        </span>
                    @endif
                    <span class="d-none d-sm-inline-flex align-items-center" style="gap: 0.35rem;">
                        <span class="wkjp-dot" style="background: #ea580c;"></span> Mapel
                    </span>
                    <span class="d-none d-sm-inline-flex align-items-center" style="gap: 0.35rem;">
                        <span class="wkjp-dot" style="background: #f59e0b;"></span> Non-Mapel
                    </span>
                </div>

                <div class="wkjp-controls">
                    <div class="wkjp-search-box">
                        <i class="fa fa-search"></i>
                        <input type="text" id="wkjpSearch" class="wkjp-search-input"
                            placeholder="Cari mapel / guru...">
                    </div>

                    <div class="wkjp-seg" role="tablist" aria-label="Mode Tampilan Jadwal">
                        <button type="button" class="wkjp-seg-btn is-active" data-wkjp-view="matrix" aria-selected="true">
                            <i class="fa fa-th mr-1"></i> Matriks
                        </button>
                        <button type="button" class="wkjp-seg-btn" data-wkjp-view="agenda" aria-selected="false">
                            <i class="fa fa-list-ul mr-1"></i> Per Hari
                        </button>
                    </div>
                </div>
            </div>

            @if ($jadwalPelajaran->isEmpty() || ! $weeklyMatrix || $weeklyMatrix['jamSlots']->isEmpty())
                <div class="p-5 text-center text-muted small">
                    <i class="fa fa-calendar-times mb-2 d-block" style="font-size: 1.5rem; color: #cbd5e1;"></i>
                    Belum ada jadwal pelajaran aktif tercatat untuk kelas <strong>{{ $selectedKelas?->nama_kelas }}</strong>.
                </div>
            @else
                <!-- MODE 1: MATRIKS MINGGUAN -->
                <div id="wkjpMatrixPanel" class="wkjp-matrix-scroll">
                    <table class="wkjp-matrix">
                        <thead>
                            <tr>
                                <th class="wkjp-time-col">Waktu</th>
                                @foreach ($hariOptions as $hariCode => $hariNama)
                                    <th class="{{ $todayIsoDay === (int) $hariCode ? 'is-today' : '' }}">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <span>{{ $hariNama }}</span>
                                            <span style="font-size: 0.64rem; font-weight: 700; opacity: 0.7;">
                                                {{ $todayIsoDay === (int) $hariCode ? 'Hari Ini' : ($summary['per_hari'][$hariCode] ?? 0) }}
                                            </span>
                                        </div>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($weeklyMatrix['jamSlots'] as $jamSlot)
                                <tr>
                                    <td class="wkjp-time-col">
                                        <div class="wkjp-time-range">{{ $jamSlot->rentang_waktu }}</div>
                                        <div class="wkjp-time-jam">Jam ke-{{ $jamSlot->jam_ke }}</div>
                                    </td>
                                    @foreach ($hariOptions as $hariCode => $hariNama)
                                        @php
                                            $slotEntry = $weeklyMatrix['grid'][$jamSlot->id][$hariCode] ?? null;
                                            $slotMapel = $slotEntry && $slotEntry->relationLoaded('mataPelajaran')
                                                ? $slotEntry->mataPelajaran
                                                : null;
                                            $slotGuru = $slotEntry && $slotEntry->relationLoaded('guru')
                                                ? $slotEntry->guru
                                                : null;
                                            $searchText = $slotEntry
                                                ? mb_strtolower(trim("{$slotEntry->judul_tampil} {$slotMapel?->singkatan} {$slotGuru?->nama_gelar} {$slotEntry->ruang}"))
                                                : '';
                                        @endphp
                                        <td>
                                            @if ($slotEntry)
                                                <div class="wkjp-slot {{ $slotEntry->is_non_mapel ? 'is-non-mapel' : '' }} {{ ! $slotEntry->status ? 'is-inactive' : '' }}"
                                                    data-wkjp-item
                                                    data-wkjp-text="{{ $searchText }}">
                                                    <div class="wkjp-slot-top">
                                                        <span class="wkjp-code">
                                                            {{ $slotEntry->is_non_mapel ? 'Non-Mapel' : ($slotMapel?->singkatan ?: 'Mapel') }}
                                                        </span>
                                                        @if (filled($slotEntry->ruang))
                                                            <span class="wkjp-room">{{ $slotEntry->ruang }}</span>
                                                        @endif
                                                    </div>
                                                    <div class="wkjp-subject">{{ $slotEntry->judul_tampil }}</div>
                                                    @if ($slotGuru)
                                                        <div class="wkjp-teacher">{{ $slotGuru->nama_gelar }}</div>
                                                    @endif
                                                </div>
                                            @else
                                                <div class="wkjp-empty-cell">&mdash;</div>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- MODE 2: RINGKASAN PER HARI -->
                <div id="wkjpAgendaPanel" class="wkjp-agenda-grid d-none">
                    @foreach ($hariOptions as $hariCode => $hariNama)
                        @php
                            $daySlots = $byHari->get($hariCode, collect());
                        @endphp
                        <div class="wkjp-day-col {{ $todayIsoDay === (int) $hariCode ? 'is-today' : '' }}">
                            <div class="wkjp-day-head">
                                <span>{{ $hariNama }}</span>
                                <span class="badge badge-light border" style="font-size: 0.66rem;">{{ $daySlots->count() }}</span>
                            </div>
                            <div class="wkjp-day-list">
                                @forelse ($daySlots as $slot)
                                    @php
                                        $jam = $slot->relationLoaded('jamPelajaran') ? $slot->jamPelajaran : null;
                                        $mapel = $slot->relationLoaded('mataPelajaran') ? $slot->mataPelajaran : null;
                                        $guru = $slot->relationLoaded('guru') ? $slot->guru : null;
                                        $searchText = mb_strtolower(trim("{$slot->judul_tampil} {$mapel?->singkatan} {$guru?->nama_gelar} {$slot->ruang} {$hariNama}"));
                                    @endphp
                                    <div class="wkjp-slot {{ $slot->is_non_mapel ? 'is-non-mapel' : '' }} {{ ! $slot->status ? 'is-inactive' : '' }}"
                                        data-wkjp-agenda-item
                                        data-wkjp-text="{{ $searchText }}">
                                        <div class="wkjp-slot-top">
                                            <span class="wkjp-code">{{ $jam?->rentang_waktu ?? '-' }}</span>
                                            @if (filled($slot->ruang))
                                                <span class="wkjp-room">{{ $slot->ruang }}</span>
                                            @endif
                                        </div>
                                        <div class="wkjp-subject">{{ $slot->judul_tampil }}</div>
                                        @if ($guru)
                                            <div class="wkjp-teacher">{{ $guru->nama_gelar }}</div>
                                        @endif
                                    </div>
                                @empty
                                    <div class="text-center text-muted py-3" style="font-size: 0.72rem;">
                                        Tidak ada jadwal
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const viewButtons = document.querySelectorAll('[data-wkjp-view]');
            const matrixPanel = document.getElementById('wkjpMatrixPanel');
            const agendaPanel = document.getElementById('wkjpAgendaPanel');
            const searchInput = document.getElementById('wkjpSearch');

            viewButtons.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const target = btn.getAttribute('data-wkjp-view');
                    viewButtons.forEach(function (other) {
                        const isActive = other === btn;
                        other.classList.toggle('is-active', isActive);
                        other.setAttribute('aria-selected', isActive ? 'true' : 'false');
                    });

                    if (matrixPanel && agendaPanel) {
                        matrixPanel.classList.toggle('d-none', target !== 'matrix');
                        agendaPanel.classList.toggle('d-none', target !== 'agenda');
                    }
                });
            });

            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const q = searchInput.value.trim().toLowerCase();

                    document.querySelectorAll('[data-wkjp-item]').forEach(function (el) {
                        const hay = el.getAttribute('data-wkjp-text') || '';
                        el.classList.toggle('is-dimmed', q !== '' && !hay.includes(q));
                    });

                    document.querySelectorAll('[data-wkjp-agenda-item]').forEach(function (el) {
                        const hay = el.getAttribute('data-wkjp-text') || '';
                        el.classList.toggle('d-none', q !== '' && !hay.includes(q));
                    });
                });
            }
        });
    </script>
@endpush

