@extends('layouts.appui')

@section('title')
    <title>Perkembangan Siswa Wali Kelas - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @php
        $isEligible = $isEligible ?? false;
        $isSd = $isSd ?? false;
        $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $isMid = $periode === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $assignedClasses = $assignedClasses ?? collect();
        $students = $students ?? collect();
        $studentStats = $studentStats ?? ['total' => 0, 'laki' => 0, 'perempuan' => 0, 'aktif' => 0];
        $mapels = $mapels ?? collect();
        $mapelSummaries = $mapelSummaries ?? collect();
        $studentRows = $studentRows ?? collect();
        $overviewStats = $overviewStats ?? [
            'avg_rapor' => null,
            'tuntas_rate' => null,
            'scored_entries' => 0,
            'tuntas_entries' => 0,
            'need_attention_students' => 0,
            'avg_kehadiran' => null,
        ];
    @endphp

    <div class="app-main__inner">
        @include('waliKelas.partials._hero', [
            'heroBadgeText' => $isEligible ? 'PERKEMBANGAN SISWA • '.strtoupper((string) $peranDiKelas) : 'PERKEMBANGAN SISWA',
            'heroTitle' => $isEligible && $selectedKelas ? 'Perkembangan Siswa Kelas '.$selectedKelas->nama_kelas : 'Perkembangan Hasil Belajar Siswa',
            'heroSubtitle' => $isEligible && $selectedKelas
                ? "Pantauan menyeluruh capaian semua mata pelajaran dan kehadiran seluruh siswa kelas {$selectedKelas->nama_kelas} pada {$activeTahunAjaran->full_label}."
                : null,
            'heroActionUrl' => route('wali-kelas.dashboard', $selectedKelas ? ['kelas_id' => $selectedKelas->id] : []),
            'heroActionLabel' => 'Kembali ke Dashboard',
            'heroActionIcon' => 'fa-arrow-left',
        ])

        @if (! $isEligible)
            @include('waliKelas.partials._ineligible_guard')
        @else
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                @if ($assignedClasses->count() > 1)
                    @include('waliKelas.partials._class_switcher', [
                        'switcherRoute' => 'wali-kelas.perkembangan-siswa.index',
                    ])
                @else
                    <div class="small text-muted font-weight-semibold">
                        <i class="fa fa-info-circle text-warning mr-1"></i>
                        Menampilkan ringkasan capaian semua mata pelajaran untuk peran <strong>{{ $peranDiKelas }}</strong>.
                    </div>
                @endif

                @if ($isSd)
                    <div class="btn-group btn-group-sm shadow-sm" role="group" aria-label="Pilih Periode Perkembangan">
                        <a href="{{ route('wali-kelas.perkembangan-siswa.index', ['kelas_id' => $selectedKelas->id, 'periode' => 'mid']) }}"
                            class="btn {{ $isMid ? 'text-white font-weight-bold' : 'btn-light border text-slate-700' }}"
                            style="{{ $isMid ? 'background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);' : '' }}">
                            Mid Semester
                        </a>
                        <a href="{{ route('wali-kelas.perkembangan-siswa.index', ['kelas_id' => $selectedKelas->id, 'periode' => 'semester']) }}"
                            class="btn {{ ! $isMid ? 'text-white font-weight-bold' : 'btn-light border text-slate-700' }}"
                            style="{{ ! $isMid ? 'background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);' : '' }}">
                            Semester
                        </a>
                    </div>
                @endif
            </div>

            <!-- KPI RINGKASAN PERKEMBANGAN -->
            <div class="wk-kpi-grid">
                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #2563eb;"></span> Total Siswa Binaan
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
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #ea580c;"></span> Rata-Rata Nilai Kelas
                        </div>
                        <div class="wk-kpi-value" style="color: #c2410c;">
                            {{ $overviewStats['avg_rapor'] !== null ? number_format((float) $overviewStats['avg_rapor'], 1) : '-' }}
                        </div>
                        <div class="wk-kpi-sub">
                            {{ $isSd ? ($isMid ? 'Periode Mid Semester' : 'Periode Semester') : 'Khusus presensi & biodata' }}
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-chart-line"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #10b981;"></span> Ketuntasan KKM
                        </div>
                        <div class="wk-kpi-value" style="color: #047857;">
                            {{ $overviewStats['tuntas_rate'] !== null ? $overviewStats['tuntas_rate'].'%' : '-' }}
                        </div>
                        <div class="wk-kpi-sub">
                            @if ($overviewStats['scored_entries'] > 0)
                                {{ $overviewStats['tuntas_entries'] }}/{{ $overviewStats['scored_entries'] }} capaian tuntas
                                @if ($overviewStats['need_attention_students'] > 0)
                                    &bull; <span class="text-danger font-weight-bold">{{ $overviewStats['need_attention_students'] }} siswa perlu pendampingan</span>
                                @endif
                            @else
                                Belum ada nilai rapor tercatat
                            @endif
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #ecfdf5; color: #059669;">
                        <i class="fa fa-award"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Rata-Rata Kehadiran
                        </div>
                        <div class="wk-kpi-value" style="color: #6d28d9;">
                            {{ $overviewStats['avg_kehadiran'] !== null ? $overviewStats['avg_kehadiran'].'%' : '-' }}
                        </div>
                        <div class="wk-kpi-sub">
                            Rekap kehadiran efektif kelas {{ $selectedKelas->nama_kelas }}
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                        <i class="fa fa-user-check"></i>
                    </span>
                </div>
            </div>

            @if ($isSd && $mapelSummaries->isNotEmpty())
                <!-- RINGKASAN PER MATA PELAJARAN (READ-ONLY SEMUA MAPEL) -->
                <div class="wk-surface">
                    <div class="wk-surface-head">
                        <div>
                            <h6 class="wk-surface-title">
                                <i class="fa fa-book-open text-warning mr-1"></i>
                                Ringkasan Perkembangan Seluruh Mata Pelajaran ({{ $isMid ? 'Mid Semester' : 'Semester' }})
                            </h6>
                            <div class="small text-muted">
                                Menampilkan capaian semua mata pelajaran SD termasuk yang diampu oleh guru bidang studi lain.
                            </div>
                        </div>
                        <span class="badge badge-light border px-2.5 py-1.5" style="font-size: 0.72rem;">
                            {{ $mapelSummaries->count() }} Mata Pelajaran
                        </span>
                    </div>

                    <div class="p-3">
                        <div class="row">
                            @foreach ($mapelSummaries as $ms)
                                @php
                                    $mp = $ms['mapel'];
                                @endphp
                                <div class="col-md-6 col-xl-4 mb-3">
                                    <div class="p-3 rounded border bg-light h-100 d-flex flex-column justify-content-between"
                                        style="border-radius: 12px !important; background: #f8fafc !important;">
                                        <div>
                                            <div class="d-flex align-items-start justify-content-between gap-2 mb-1">
                                                <div>
                                                    <div class="font-weight-bold text-dark" style="font-size: 0.86rem;">
                                                        [{{ $mp->singkatan }}] {{ $mp->nama_mata_pelajaran }}
                                                    </div>
                                                    <div class="small text-muted" style="font-size: 0.72rem;">
                                                        Pengampu: {{ $ms['teachers'] !== [] ? implode(', ', $ms['teachers']) : 'Belum dijadwalkan' }}
                                                    </div>
                                                </div>
                                                @if ($ms['is_taught_by_user'])
                                                    <span class="badge badge-warning text-dark font-weight-bold" style="font-size: 0.65rem;">
                                                        Mapel Anda
                                                    </span>
                                                @endif
                                            </div>
                                        </div>

                                        <div class="d-flex align-items-center justify-content-between pt-2 mt-2 border-top">
                                            <div class="small">
                                                <span class="text-muted">Rata NA:</span>
                                                <strong class="text-dark">{{ $ms['avg_rapor'] !== null ? number_format((float) $ms['avg_rapor'], 1) : '-' }}</strong>
                                                <span class="text-muted ml-2">KKM {{ rtrim(rtrim(number_format((float) $ms['kkm'], 2, '.', ''), '0'), '.') }}</span>
                                            </div>
                                            <div class="d-flex align-items-center gap-1">
                                                <span class="badge {{ $ms['belum_tuntas_count'] > 0 ? 'badge-danger' : ($ms['dinilai_count'] > 0 ? 'badge-success' : 'badge-secondary') }}"
                                                    style="font-size: 0.68rem;">
                                                    {{ $ms['dinilai_count'] > 0 ? "{$ms['tuntas_count']}/{$ms['dinilai_count']} Tuntas" : 'Belum dinilai' }}
                                                </span>
                                                @if ($ms['is_taught_by_user'])
                                                    <a href="{{ route('wali-kelas.penilaian-tp.index', ['kelas_id' => $selectedKelas->id, 'mata_pelajaran_id' => $mp->id, 'periode' => $periode]) }}"
                                                        class="badge badge-primary text-decoration-none" style="font-size: 0.68rem;" title="Input nilai mapel yang Anda ampu">
                                                        Input
                                                    </a>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif

            <!-- LEGER PERKEMBANGAN SELURUH SISWA -->
            <div class="wk-surface">
                <div class="wk-surface-head">
                    <div>
                        <h6 class="wk-surface-title">
                            <i class="fa fa-graduation-cap text-primary mr-1"></i>
                            Leger Perkembangan Siswa Kelas {{ $selectedKelas->nama_kelas }}
                        </h6>
                        <div class="small text-muted">
                            Klik nama siswa atau tombol <strong>Detail</strong> untuk melihat perkembangan lengkap, atau cetak PDF Identitas &amp; Nilai Rapor terpisah.
                        </div>
                    </div>
                    @if ($students->isNotEmpty())
                        <div class="d-flex flex-wrap align-items-center" style="gap: 0.45rem;">
                            <a href="{{ route('wali-kelas.rapor.identitas.kelas', $selectedKelas->id) }}" target="_blank"
                                class="btn btn-sm btn-outline-primary font-weight-semibold"
                                style="border-radius: 8px; font-size: 0.74rem;">
                                <i class="fa fa-id-card mr-1"></i> Identitas (1 Kelas)
                            </a>
                            @if ($isSd)
                                <a href="{{ route('wali-kelas.rapor.nilai.kelas', ['kelas' => $selectedKelas->id, 'periode' => $periode]) }}" target="_blank"
                                    class="btn btn-sm text-white font-weight-semibold shadow-sm"
                                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border-radius: 8px; font-size: 0.74rem;">
                                    <i class="fa fa-file-pdf mr-1"></i> Nilai Rapor (1 Kelas)
                                </a>
                            @endif
                        </div>
                    @endif
                </div>

                <div class="p-3 p-md-4">
                    <div class="table-responsive">
                        <table class="mb-0 table table-hover align-middle datatable-ordered" id="wkPerkembanganTable"
                            data-search-placeholder="Cari nama siswa..."
                            data-empty-table="Belum ada siswa terdaftar di kelas {{ $selectedKelas->nama_kelas }}.">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 46px;" data-orderable="false" data-searchable="false">No</th>
                                    <th>Siswa</th>
                                    <th class="text-center" data-searchable="false">Kehadiran</th>
                                    @if ($isSd)
                                        @foreach ($mapels as $mp)
                                            <th class="text-center" data-searchable="false" title="{{ $mp->nama_mata_pelajaran }}">
                                                {{ $mp->singkatan ?: \Illuminate\Support\Str::limit($mp->nama_mata_pelajaran, 8) }}
                                            </th>
                                        @endforeach
                                        <th class="text-center" data-searchable="false">Ketuntasan</th>
                                        <th class="text-center" data-searchable="false">Rata NA</th>
                                    @endif
                                    <th class="text-center" style="width: 72px; text-align: center;" data-orderable="false" data-searchable="false">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($studentRows as $idx => $row)
                                    @php
                                        $anggota = $row['anggota'];
                                        $siswa = $anggota->siswa;
                                        $siswaName = $siswa?->nama_lengkap ?? $anggota->siswa_nis;
                                        $presensi = $row['presensi'];
                                        $detailUrl = route('wali-kelas.perkembangan-siswa.show', ['anggota_kelas' => $anggota->id, 'periode' => $periode]);
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted font-weight-semibold">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center" style="gap: 0.65rem;">
                                                @if ($siswa?->avatar_url)
                                                    <img src="{{ $siswa->avatar_url }}" alt="{{ $siswaName }}" class="wk-avatar-thumb">
                                                @else
                                                    <div class="wk-avatar-fallback">
                                                        {{ $siswa?->inisial_nama ?? 'S' }}
                                                    </div>
                                                @endif
                                                <div style="min-width: 0;">
                                                    <a href="{{ $detailUrl }}" class="font-weight-bold text-dark text-decoration-none d-block"
                                                        style="font-size: 0.83rem;">
                                                        {{ $siswaName }}
                                                    </a>
                                                    <div class="small text-muted" style="font-size: 0.7rem;">
                                                        NIS: {{ $anggota->siswa_nis }}
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <div class="font-weight-bold" style="font-size: 0.8rem; color: {{ ($presensi['hadir_rate'] ?? 100) < 85 ? '#b91c1c' : '#047857' }};">
                                                {{ $presensi['hadir_rate'] !== null ? $presensi['hadir_rate'].'%' : '-' }}
                                            </div>
                                            <div class="text-muted" style="font-size: 0.66rem;">
                                                H:{{ $presensi['hadir'] }} T:{{ $presensi['terlambat'] }} I:{{ $presensi['izin'] }} S:{{ $presensi['sakit'] }} A:{{ $presensi['alpa'] }}
                                            </div>
                                        </td>
                                        @if ($isSd)
                                            @foreach ($mapels as $mp)
                                                @php
                                                    $cell = $row['mapel_cells'][$mp->id] ?? ['nilai' => null, 'predikat' => null, 'tuntas' => null];
                                                @endphp
                                                <td class="text-center">
                                                    @if ($cell['nilai'] !== null)
                                                        <span class="badge px-2 py-1"
                                                            style="font-size: 0.73rem; font-weight: 700; background: {{ $cell['tuntas'] === false ? '#fee2e2' : '#dcfce7' }}; color: {{ $cell['tuntas'] === false ? '#991b1b' : '#166534' }};">
                                                            {{ rtrim(rtrim(number_format((float) $cell['nilai'], 1, '.', ''), '0'), '.') }}
                                                            @if ($cell['predikat'])
                                                                ({{ $cell['predikat'] }})
                                                            @endif
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                            @endforeach
                                            <td class="text-center">
                                                @if ($row['dinilai_count'] > 0)
                                                    <span class="badge {{ $row['belum_tuntas_count'] > 0 ? 'badge-warning text-dark' : 'badge-success' }} px-2 py-1" style="font-size: 0.7rem;">
                                                        {{ $row['tuntas_count'] }}/{{ $row['dinilai_count'] }} Tuntas
                                                    </span>
                                                @else
                                                    <span class="text-muted small">-</span>
                                                @endif
                                            </td>
                                            <td class="text-center font-weight-bold text-dark" style="font-size: 0.85rem;">
                                                {{ $row['rata_rapor'] !== null ? number_format((float) $row['rata_rapor'], 1) : '-' }}
                                            </td>
                                        @endif
                                        <td class="text-center">
                                            <x-action-dropdown>
                                                <a href="{{ $detailUrl }}" class="action-dropdown-item" role="menuitem">
                                                    <i class="fa fa-chart-area text-primary action-item-icon"></i>
                                                    <span>Detail Perkembangan</span>
                                                </a>
                                                <div class="action-dropdown-divider"></div>
                                                <a href="{{ route('wali-kelas.rapor.identitas.siswa', $anggota->id) }}" target="_blank" class="action-dropdown-item" role="menuitem">
                                                    <i class="fa fa-id-card text-info action-item-icon"></i>
                                                    <span>Cetak Identitas (PDF)</span>
                                                </a>
                                                @if ($isSd)
                                                    <a href="{{ route('wali-kelas.rapor.nilai.siswa', ['anggota_kelas' => $anggota->id, 'periode' => $periode]) }}" target="_blank" class="action-dropdown-item" role="menuitem">
                                                        <i class="fa fa-file-pdf text-danger action-item-icon"></i>
                                                        <span>Cetak Nilai Rapor (PDF)</span>
                                                    </a>
                                                @endif
                                            </x-action-dropdown>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
@endsection

