@extends('layouts.appui')

@section('title')
    <title>Dashboard Wali Kelas - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @php
        $todayDate = $todayDate ?? \Carbon\Carbon::now();
        $jam = (int) $todayDate->format('H');
        $salam = $jam < 11 ? 'Selamat Pagi' : ($jam < 15 ? 'Selamat Siang' : ($jam < 18 ? 'Selamat Sore' : 'Selamat Malam'));
        $authUser = Auth::user();
        $displayName = $karyawanProfile?->nama_gelar ?? $authUser?->name ?? 'Wali Kelas';
        $isEligible = $isEligible ?? false;
        $assignedClasses = $assignedClasses ?? collect();
        $students = $students ?? collect();
        $studentStats = $studentStats ?? ['total' => 0, 'laki' => 0, 'perempuan' => 0];
        $todayPresensiByAnggota = $todayPresensiByAnggota ?? [];
        $presensiTodaySummary = $presensiTodaySummary ?? ['recorded' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'belum' => 0];
        $presensiMonthSummary = $presensiMonthSummary ?? ['total' => 0, 'hadir_rate' => null, 'hadir_dan_terlambat' => 0];
        $jadwalHariIni = $jadwalHariIni ?? collect();
        $upcomingAgendas = $upcomingAgendas ?? collect();
        $latestPengumuman = $latestPengumuman ?? collect();
    @endphp

    <div class="app-main__inner">
        @include('waliKelas.partials._hero', [
            'heroBadgeText' => $isEligible ? strtoupper((string) $peranDiKelas) : 'DASHBOARD WALI KELAS',
            'heroTitle' => "{$salam}, {$displayName}!",
            'heroActionUrl' => $isEligible && $selectedKelas ? route('wali-kelas.anggota-kelas.index', ['kelas_id' => $selectedKelas->sqid]) : null,
            'heroActionLabel' => $isEligible && $selectedKelas ? 'Lihat Anggota Kelas' : null,
            'heroActionIcon' => 'fa-users',
        ])

        @if (! $isEligible)
            @include('waliKelas.partials._ineligible_guard')
        @else
            @if ($assignedClasses->count() > 1)
                @include('waliKelas.partials._class_switcher')
            @endif

            <!-- KPI SUMMARY STRIP -->
            <div class="wk-kpi-grid">
                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #3b82f6;"></span> Siswa Kelas {{ $selectedKelas->nama_kelas }}
                        </div>
                        <div class="wk-kpi-value">{{ $studentStats['total'] }}</div>
                        <div class="wk-kpi-sub">
                            {{ $studentStats['laki'] }} Laki-laki &bull; {{ $studentStats['perempuan'] }} Perempuan
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-users"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    @php
                        $hadirTodayCount = $presensiTodaySummary['hadir'] + $presensiTodaySummary['terlambat'];
                    @endphp
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #10b981;"></span> Hadir Hari Ini
                        </div>
                        <div class="wk-kpi-value" style="color: #047857;">
                            {{ $hadirTodayCount }}<span style="font-size: 0.9rem; font-weight: 600; color: #64748b;"> / {{ $studentStats['total'] }}</span>
                        </div>
                        <div class="wk-kpi-sub">
                            Izin {{ $presensiTodaySummary['izin'] }} &bull; Sakit {{ $presensiTodaySummary['sakit'] }} &bull; Alpa {{ $presensiTodaySummary['alpa'] }}
                            @if ($presensiTodaySummary['belum'] > 0)
                                &bull; Belum {{ $presensiTodaySummary['belum'] }}
                            @endif
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa fa-user-check"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #ea580c;"></span> Kehadiran Bulan Ini
                        </div>
                        <div class="wk-kpi-value" style="color: #c2410c;">
                            {{ $presensiMonthSummary['hadir_rate'] !== null ? $presensiMonthSummary['hadir_rate'].'%' : '-' }}
                        </div>
                        <div class="wk-kpi-sub">
                            {{ $presensiMonthSummary['total'] }} catatan presensi bulan {{ $todayDate->copy()->locale('id')->translatedFormat('F') }}
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-chart-pie"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    @if ($penilaianSummary !== null)
                        <div>
                            <div class="wk-kpi-label">
                                <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Progres Penilaian SD
                            </div>
                            <div class="wk-kpi-value" style="color: #6d28d9;">
                                {{ $penilaianSummary['assessed_mapel'] }}<span style="font-size: 0.9rem; font-weight: 600; color: #64748b;"> / {{ $penilaianSummary['total_mapel'] }} Mapel</span>
                            </div>
                            <div class="wk-kpi-sub">
                                Periode {{ $penilaianSummary['periode_label'] }}
                            </div>
                        </div>
                        <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                            <i class="fa fa-clipboard-check"></i>
                        </span>
                    @else
                        <div>
                            <div class="wk-kpi-label">
                                <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Jadwal Pelajaran
                            </div>
                            <div class="wk-kpi-value" style="color: #6d28d9;">
                                {{ $jadwalHariIni->count() }} <span style="font-size: 0.9rem; font-weight: 600; color: #64748b;">Sesi</span>
                            </div>
                            <div class="wk-kpi-sub">
                                {{ $isWeekday ? "Hari {$todayDayLabel}" : 'Akhir Pekan' }} &bull; {{ $jadwalMingguanCount }} total slot/minggu
                            </div>
                        </div>
                        <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                            <i class="fa fa-clock"></i>
                        </span>
                    @endif
                </div>
            </div>

            <!-- MAIN TWO-COLUMN CONTENT -->
            <div class="row">
                <!-- LEFT COLUMN: DAFTAR SISWA & PROGRES PENILAIAN SD -->
                <div class="col-lg-7">
                    <div class="wk-surface">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-users text-primary mr-1"></i> Daftar Siswa Kelas {{ $selectedKelas->nama_kelas }}
                                </h6>
                                <div class="small text-muted">Status kehadiran hari ini per anggota kelas</div>
                            </div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <div class="wk-search-wrap">
                                    <i class="fa fa-search"></i>
                                    <input type="search" id="wkStudentSearch" class="wk-search-input"
                                        placeholder="Cari nama / NIS siswa...">
                                </div>
                                <a href="{{ route('wali-kelas.anggota-kelas.index', ['kelas_id' => $selectedKelas->sqid]) }}"
                                    class="btn btn-sm btn-light border font-weight-semibold text-primary"
                                    style="border-radius: 9px; font-size: 0.74rem; white-space: nowrap;">
                                    <i class="fa fa-id-card mr-1"></i> Anggota Kelas
                                </a>
                                <a href="{{ route('wali-kelas.perkembangan-siswa.index', ['kelas_id' => $selectedKelas->sqid]) }}"
                                    class="btn btn-sm btn-light border font-weight-semibold"
                                    style="border-radius: 9px; font-size: 0.74rem; white-space: nowrap; color: #c2410c; border-color: #fed7aa; background: #fff7ed;">
                                    <i class="fa fa-chart-line mr-1"></i> Perkembangan
                                </a>
                                <a href="{{ route('wali-kelas.presensi-siswa.bulk', ['kelas_id' => $selectedKelas->sqid]) }}"
                                    class="btn btn-sm text-white font-weight-semibold shadow-sm"
                                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 9px; font-size: 0.74rem; white-space: nowrap;">
                                    <i class="fa fa-clipboard-check mr-1"></i> Input Presensi
                                </a>
                            </div>
                        </div>

                        <div class="wk-scroll-body">
                            <table class="wk-table" id="wkStudentsTable">
                                <thead>
                                    <tr>
                                        <th class="text-center" style="width: 48px;">No</th>
                                        <th>Siswa</th>
                                        <th class="text-center" style="width: 75px;">L/P</th>
                                        <th>Pendaftaran</th>
                                        <th class="text-center" style="width: 130px;">Presensi Hari Ini</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($students as $idx => $anggota)
                                        @php
                                            $siswa = $anggota->siswa;
                                            $siswaName = $siswa?->nama_lengkap ?? $anggota->siswa_nis;
                                            $jkRaw = strtoupper(trim((string) ($siswa?->jenis_kelamin ?? '')));
                                            $jkShort = ($jkRaw === 'L' || $jkRaw === 'LAKI-LAKI') ? 'L' : (($jkRaw === 'P' || $jkRaw === 'PEREMPUAN') ? 'P' : '-');
                                            $presensiToday = $todayPresensiByAnggota[$anggota->id] ?? null;
                                            $presensiStatus = strtolower(trim((string) ($presensiToday?->status ?? '')));
                                            $statusStyle = match ($presensiStatus) {
                                                'hadir' => ['bg' => '#dcfce7', 'color' => '#166534', 'label' => 'Hadir'],
                                                'terlambat' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Terlambat'],
                                                'izin' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Izin'],
                                                'sakit' => ['bg' => '#fae8ff', 'color' => '#86198f', 'label' => 'Sakit'],
                                                'alpa' => ['bg' => '#fee2e2', 'color' => '#991b1b', 'label' => 'Alpa'],
                                                default => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => 'Belum Tercatat'],
                                            };
                                        @endphp
                                        <tr class="js-wk-student-row"
                                            data-search="{{ mb_strtolower($siswaName.' '.$anggota->siswa_nis.' '.($siswa?->nisn ?? '')) }}">
                                            <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                            <td>
                                                <a href="{{ route('wali-kelas.anggota-kelas.show', $anggota) }}"
                                                    class="font-weight-semibold text-dark text-decoration-none">
                                                    {{ $siswaName }}
                                                </a>
                                                <div class="small text-muted" style="font-size: 0.7rem;">
                                                    NIS: {{ $anggota->siswa_nis }}
                                                    @if ($siswa?->nisn)
                                                        &bull; NISN: {{ $siswa->nisn }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge {{ $jkShort === 'L' ? 'badge-light' : 'badge-light' }}"
                                                    style="color: {{ $jkShort === 'L' ? '#1d4ed8' : ($jkShort === 'P' ? '#be185d' : '#64748b') }};">
                                                    {{ $jkShort }}
                                                </span>
                                            </td>
                                            <td class="small text-muted">{{ $anggota->pendaftaran ?: '-' }}</td>
                                            <td class="text-center">
                                                <span class="wk-badge-status"
                                                    style="background: {{ $statusStyle['bg'] }}; color: {{ $statusStyle['color'] }};">
                                                    {{ $statusStyle['label'] }}
                                                </span>
                                                @if ($presensiToday?->jam_masuk)
                                                    <div class="text-muted mt-1" style="font-size: 0.67rem;">
                                                        {{ substr((string) $presensiToday->jam_masuk, 0, 5) }}
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted small">
                                                Belum ada siswa terdaftar di kelas {{ $selectedKelas->nama_kelas }}.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>

                    @if ($penilaianSummary !== null)
                        <div class="wk-surface">
                            <div class="wk-surface-head">
                                <div>
                                    <h6 class="wk-surface-title">
                                        <i class="fa fa-chart-bar text-warning mr-1"></i> Progres Penilaian TP (SD)
                                    </h6>
                                    <div class="small text-muted">
                                        Rekap kelengkapan NA Rapor periode <strong>{{ $penilaianSummary['periode_label'] }}</strong> &bull; Tingkat {{ $selectedKelas->tingkatan_kelas }}
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap align-items-center gap-2">
                                    <span class="badge badge-light" style="font-weight: 700;">
                                        {{ $penilaianSummary['assessed_mapel'] }}/{{ $penilaianSummary['total_mapel'] }} Mapel Terisi
                                    </span>
                                    <a href="{{ route('wali-kelas.penilaian-tp.index', ['kelas_id' => $selectedKelas->sqid]) }}"
                                        class="btn btn-sm btn-light border font-weight-semibold text-primary"
                                        style="border-radius: 9px; font-size: 0.72rem; white-space: nowrap;">
                                        <i class="fa fa-edit mr-1"></i> Kelola Nilai TP
                                    </a>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="wk-table">
                                    <thead>
                                        <tr>
                                            <th>Mata Pelajaran</th>
                                            <th class="text-center" style="width: 70px;">TP</th>
                                            <th style="width: 170px;">Progres Siswa</th>
                                            <th class="text-center" style="width: 85px;">Rata NA</th>
                                            <th class="text-center" style="width: 85px;">Tuntas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($penilaianSummary['mapels'] as $mp)
                                            <tr>
                                                <td>
                                                    <span class="badge badge-light font-weight-bold mr-1" style="color: #c2410c; background: #fff7ed;">
                                                        {{ $mp['singkatan'] }}
                                                    </span>
                                                    <span class="font-weight-semibold text-dark">{{ $mp['nama'] }}</span>
                                                </td>
                                                <td class="text-center">{{ $mp['tp_count'] }}</td>
                                                <td>
                                                    <div class="d-flex justify-content-between small mb-1" style="font-size: 0.7rem;">
                                                        <span class="font-weight-semibold">{{ $mp['dinilai_count'] }} / {{ $studentStats['total'] }}</span>
                                                        <span class="text-muted">{{ $mp['progress_pct'] }}%</span>
                                                    </div>
                                                    <div class="wk-progress-track">
                                                        <div class="wk-progress-fill" style="width: {{ $mp['progress_pct'] }}%;"></div>
                                                    </div>
                                                </td>
                                                <td class="text-center font-weight-bold" style="color: #0f172a;">
                                                    {{ $mp['avg_rapor'] !== null ? $mp['avg_rapor'] : '-' }}
                                                </td>
                                                <td class="text-center">
                                                    @if ($mp['dinilai_count'] > 0)
                                                        <span class="badge badge-success">{{ $mp['tuntas_count'] }}</span>
                                                    @else
                                                        <span class="text-muted">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center py-4 text-muted small">
                                                    Belum ada mata pelajaran SD aktif.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- RIGHT COLUMN: JADWAL HARI INI, AGENDA & PENGUMUMAN -->
                <div class="col-lg-5">
                    <!-- JADWAL PELAJARAN HARI INI -->
                    <div class="wk-surface">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-calendar-day text-info mr-1"></i> Jadwal Pelajaran Hari Ini
                                </h6>
                                <div class="small text-muted">{{ $todayDayLabel }}, {{ $todayDate->copy()->locale('id')->translatedFormat('d M Y') }}</div>
                            </div>
                            <div class="d-flex align-items-center" style="gap: 0.45rem;">
                                <span class="badge badge-light" style="font-weight: 700;">
                                    {{ $jadwalHariIni->count() }} Slot
                                </span>
                                <a href="{{ route('wali-kelas.jadwal-pelajaran.index', ['kelas_id' => $selectedKelas->sqid]) }}"
                                    class="btn btn-xs btn-light border font-weight-semibold px-2 py-1"
                                    style="font-size: 0.72rem; border-radius: 7px;">
                                    Lihat Mingguan
                                </a>
                            </div>
                        </div>
                        <div class="p-3">
                            @if (! $isWeekday)
                                <div class="text-center py-3 text-muted small">
                                    <i class="fa fa-coffee mb-1 d-block" style="font-size: 1.25rem; color: #94a3b8;"></i>
                                    Hari {{ $todayDayLabel }} merupakan akhir pekan ({{ $jadwalMingguanCount }} sesi aktif pada Senin–Jumat).
                                </div>
                            @elseif ($jadwalHariIni->isEmpty())
                                <div class="text-center py-3 text-muted small">
                                    Belum ada jadwal pelajaran aktif tercatat untuk hari {{ $todayDayLabel }}.
                                </div>
                            @else
                                <div class="d-flex flex-column gap-2">
                                    @foreach ($jadwalHariIni as $slot)
                                        <div class="d-flex align-items-center justify-content-between p-2 rounded"
                                            style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                            <div>
                                                <div class="font-weight-bold text-dark" style="font-size: 0.82rem;">
                                                    {{ $slot->judul_tampil }}
                                                </div>
                                                <div class="small text-muted" style="font-size: 0.72rem;">
                                                    Jam ke-{{ $slot->jamPelajaran?->jam_ke ?? '-' }}
                                                    ({{ $slot->jamPelajaran?->rentang_waktu ?? '-' }})
                                                    @if ($slot->guru?->nama_gelar)
                                                        &bull; {{ $slot->guru->nama_gelar }}
                                                    @endif
                                                </div>
                                            </div>
                                            @if ($slot->ruang)
                                                <span class="badge badge-light" style="border: 1px solid #cbd5e1;">
                                                    {{ $slot->ruang }}
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- AGENDA SEKOLAH -->
                    <div class="wk-surface">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-calendar-check text-success mr-1"></i> Agenda Sekolah
                                </h6>
                                <div class="small text-muted">Tahun Ajaran {{ $activeTahunAjaran->full_label }}</div>
                            </div>
                        </div>
                        <div class="p-3">
                            @forelse ($upcomingAgendas as $agenda)
                                <div class="d-flex align-items-start justify-content-between gap-2 pb-2 mb-2"
                                    style="border-bottom: 1px solid #f1f5f9;">
                                    <div>
                                        <div class="font-weight-semibold text-dark" style="font-size: 0.82rem;">
                                            {{ $agenda->nama_agenda }}
                                        </div>
                                        <div class="small text-muted" style="font-size: 0.72rem;">
                                            <i class="fa fa-clock mr-1"></i>{{ $agenda->tanggal_formatted }} &bull; {{ $agenda->jam_formatted }}
                                        </div>
                                    </div>
                                    <span class="badge badge-light" style="font-size: 0.68rem;">{{ $agenda->unit }}</span>
                                </div>
                            @empty
                                <div class="text-center py-3 text-muted small">
                                    Belum ada agenda sekolah pada tahun ajaran aktif ini.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- PENGUMUMAN TERBARU -->
                    <div class="wk-surface">
                        <div class="wk-surface-head">
                            <div>
                                <h6 class="wk-surface-title">
                                    <i class="fa fa-bullhorn text-danger mr-1"></i> Pengumuman Terbaru
                                </h6>
                                <div class="small text-muted">Informasi untuk siswa &amp; wali kelas</div>
                            </div>
                        </div>
                        <div class="p-3">
                            @forelse ($latestPengumuman as $info)
                                <div class="pb-2 mb-2" style="border-bottom: 1px solid #f1f5f9;">
                                    <div class="font-weight-bold text-dark" style="font-size: 0.82rem;">
                                        {{ $info->judul }}
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.74rem; line-height: 1.4;">
                                        {{ $info->ringkasan_isi }}
                                    </div>
                                    <div class="text-muted mt-1" style="font-size: 0.68rem;">
                                        {{ $info->tanggal_dibuat_formatted }}
                                    </div>
                                </div>
                            @empty
                                <div class="text-center py-3 text-muted small">
                                    Belum ada pengumuman terbaru pada tahun ajaran aktif ini.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('wkStudentSearch');
            const rows = document.querySelectorAll('.js-wk-student-row');
            if (!searchInput || !rows.length) {
                return;
            }

            searchInput.addEventListener('input', function () {
                const q = searchInput.value.trim().toLowerCase();
                rows.forEach(function (tr) {
                    const hay = tr.dataset.search || '';
                    tr.style.display = q === '' || hay.indexOf(q) !== -1 ? '' : 'none';
                });
            });
        });
    </script>
@endpush

