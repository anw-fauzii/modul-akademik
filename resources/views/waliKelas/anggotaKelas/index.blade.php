@extends('layouts.appui')

@section('title')
    <title>Anggota Kelas Wali Kelas - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @php
        $isEligible = $isEligible ?? false;
        $assignedClasses = $assignedClasses ?? collect();
        $students = $students ?? collect();
        $studentStats = $studentStats ?? ['total' => 0, 'laki' => 0, 'perempuan' => 0, 'aktif' => 0, 'by_pendaftaran' => []];
        $pendaftaranSummaryText = collect($studentStats['by_pendaftaran'] ?? [])
            ->map(static fn (int $count, string $label): string => "{$label}: {$count}")
            ->implode(' • ');
    @endphp

    <div class="app-main__inner">
        @include('waliKelas.partials._hero', [
            'heroBadgeText' => $isEligible ? 'ANGGOTA KELAS • '.strtoupper((string) $peranDiKelas) : 'ANGGOTA KELAS WALI KELAS',
            'heroTitle' => $isEligible && $selectedKelas ? 'Anggota Kelas '.$selectedKelas->nama_kelas : 'Data Anggota Kelas Binaan',
            'heroSubtitle' => $isEligible && $selectedKelas
                ? "Daftar lengkap siswa binaan kelas {$selectedKelas->nama_kelas} ({$selectedKelas->jenjang_kelas} • Tingkat {$selectedKelas->tingkatan_kelas}) yang Anda kelola sebagai {$peranDiKelas}."
                : null,
            'heroActionUrl' => route('wali-kelas.dashboard', $selectedKelas ? ['kelas_id' => $selectedKelas->id] : []),
            'heroActionLabel' => 'Kembali ke Dashboard',
            'heroActionIcon' => 'fa-arrow-left',
        ])

        @if (! $isEligible)
            @include('waliKelas.partials._ineligible_guard')
        @else
            @if ($assignedClasses->count() > 1)
                @include('waliKelas.partials._class_switcher', [
                    'switcherRoute' => 'wali-kelas.anggota-kelas.index',
                ])
            @endif

            <!-- KPI SUMMARY STRIP -->
            <div class="wk-kpi-grid">
                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #2563eb;"></span> Total Anggota Kelas
                        </div>
                        <div class="wk-kpi-value">{{ $studentStats['total'] }}</div>
                        <div class="wk-kpi-sub">
                            {{ $studentStats['aktif'] }} siswa berstatus aktif
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #eff6ff; color: #2563eb;">
                        <i class="fa fa-users"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #0284c7;"></span> Laki-laki
                        </div>
                        <div class="wk-kpi-value" style="color: #0369a1;">{{ $studentStats['laki'] }}</div>
                        <div class="wk-kpi-sub">
                            {{ $studentStats['total'] > 0 ? round(($studentStats['laki'] / $studentStats['total']) * 100, 1) : 0 }}% dari anggota kelas
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #e0f2fe; color: #0284c7;">
                        <i class="fa fa-male"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #db2777;"></span> Perempuan
                        </div>
                        <div class="wk-kpi-value" style="color: #be185d;">{{ $studentStats['perempuan'] }}</div>
                        <div class="wk-kpi-sub">
                            {{ $studentStats['total'] > 0 ? round(($studentStats['perempuan'] / $studentStats['total']) * 100, 1) : 0 }}% dari anggota kelas
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fce7f3; color: #db2777;">
                        <i class="fa fa-female"></i>
                    </span>
                </div>

                <div class="wk-kpi-card">
                    <div>
                        <div class="wk-kpi-label">
                            <span class="wk-kpi-dot" style="background: #ea580c;"></span> Komposisi Pendaftaran
                        </div>
                        <div class="wk-kpi-value" style="color: #c2410c; font-size: 1.15rem;">
                            {{ count($studentStats['by_pendaftaran'] ?? []) }} Kategori
                        </div>
                        <div class="wk-kpi-sub">
                            {{ $pendaftaranSummaryText !== '' ? $pendaftaranSummaryText : 'Belum ada data pendaftaran' }}
                        </div>
                    </div>
                    <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                        <i class="fa fa-tags"></i>
                    </span>
                </div>
            </div>

            <!-- ROSTER DATATABLE SURFACE -->
            <div class="wk-surface">
                <div class="wk-surface-head">
                    <div>
                        <h6 class="wk-surface-title">
                            <i class="fa fa-address-book text-primary mr-1"></i>
                            Daftar Anggota Kelas {{ $selectedKelas->nama_kelas }}
                        </h6>
                        <div class="small text-muted">
                            Total <strong>{{ $students->count() }}</strong> siswa binaan pada {{ $activeTahunAjaran->full_label }}
                        </div>
                    </div>
                    <span class="badge badge-light border px-3 py-2" style="font-size: 0.74rem; color: #475569;">
                        <i class="fa fa-shield-alt text-warning mr-1"></i>
                        Peran Anda: <strong>{{ $peranDiKelas }}</strong>
                    </span>
                </div>

                <div class="p-3 p-md-4">
                    <div class="table-responsive">
                        <table class="mb-0 table table-hover align-middle datatable-ordered" id="wkAnggotaTable"
                            data-search-placeholder="Cari nama siswa..."
                            data-empty-table="Kelas {{ $selectedKelas->nama_kelas }} belum memiliki anggota kelas pada tahun ajaran aktif.">
                            <thead>
                                <tr>
                                    <th class="text-center" style="width: 52px;" data-orderable="false" data-searchable="false">No</th>
                                    <th>Siswa</th>
                                    <th class="text-center" style="width: 110px;" data-searchable="false">Gender</th>
                                    <th data-searchable="false">Tempat, Tgl Lahir</th>
                                    <th data-searchable="false">Kontak &amp; Orang Tua</th>
                                    <th class="text-center" style="width: 120px;" data-searchable="false">Pendaftaran</th>
                                    <th class="text-center" style="width: 100px;" data-searchable="false">Status</th>
                                    <th class="text-center" style="width: 96px;" data-orderable="false" data-searchable="false">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $idx => $anggota)
                                    @php
                                        $siswa = $anggota->siswa;
                                        $siswaName = $siswa?->nama_lengkap ?? $anggota->siswa_nis;
                                        $genderLabel = $siswa?->jenis_kelamin_label ?? '-';
                                        $isMale = $genderLabel === 'Laki-laki';
                                        $isFemale = $genderLabel === 'Perempuan';
                                        $birthDateFormatted = $siswa?->tanggal_lahir?->copy()->locale('id')->translatedFormat('d M Y');
                                        $birthInfo = collect([$siswa?->tempat_lahir, $birthDateFormatted])->filter()->implode(', ');
                                        $contactNumber = $siswa?->whatsapp ?: $siswa?->nomor_hp;
                                        $waDigits = $contactNumber ? preg_replace('/[^0-9]/', '', $contactNumber) : null;
                                        $detailUrl = route('wali-kelas.anggota-kelas.show', $anggota->id);
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted font-weight-semibold">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="d-flex align-items-center" style="gap: 0.7rem;">
                                                @if ($siswa?->avatar_url)
                                                    <img src="{{ $siswa->avatar_url }}" alt="{{ $siswaName }}" class="wk-avatar-thumb">
                                                @else
                                                    <div class="wk-avatar-fallback">
                                                        {{ $siswa?->inisial_nama ?? 'S' }}
                                                    </div>
                                                @endif
                                                <div style="min-width: 0;">
                                                    <a href="{{ $detailUrl }}" class="font-weight-bold text-dark text-decoration-none d-block"
                                                        style="font-size: 0.84rem; line-height: 1.25;">
                                                        {{ $siswaName }}
                                                    </a>
                                                    <div class="small text-muted" style="font-size: 0.71rem;">
                                                        NIS: <span class="font-weight-semibold">{{ $anggota->siswa_nis }}</span>
                                                        @if ($siswa?->nisn)
                                                            &bull; NISN: {{ $siswa->nisn }}
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <span class="wk-badge-status"
                                                style="background: {{ $isMale ? '#e0f2fe' : ($isFemale ? '#fce7f3' : '#f1f5f9') }}; color: {{ $isMale ? '#0369a1' : ($isFemale ? '#be185d' : '#475569') }};">
                                                {{ $genderLabel }}
                                            </span>
                                        </td>
                                        <td class="small text-slate-700">
                                            {{ $birthInfo !== '' ? $birthInfo : '-' }}
                                        </td>
                                        <td>
                                            @if ($contactNumber)
                                                <div class="small mb-1">
                                                    @if ($siswa?->whatsapp && $waDigits)
                                                        <a href="https://wa.me/{{ $waDigits }}" target="_blank" rel="noopener"
                                                            class="text-success font-weight-semibold text-decoration-none">
                                                            <i class="fab fa-whatsapp mr-1"></i>{{ $contactNumber }}
                                                        </a>
                                                    @else
                                                        <span class="text-dark">
                                                            <i class="fa fa-phone text-muted mr-1"></i>{{ $contactNumber }}
                                                        </span>
                                                    @endif
                                                </div>
                                            @endif
                                            @if ($siswa?->nama_ayah || $siswa?->nama_ibu)
                                                <div class="small text-muted" style="font-size: 0.71rem;">
                                                    {{ collect([$siswa?->nama_ayah ? 'Ayah: '.$siswa->nama_ayah : null, $siswa?->nama_ibu ? 'Ibu: '.$siswa->nama_ibu : null])->filter()->implode(' • ') }}
                                                </div>
                                            @elseif (! $contactNumber)
                                                <span class="small text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            <span class="badge badge-light border px-2 py-1" style="font-size: 0.71rem;">
                                                {{ $anggota->pendaftaran ?: '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge {{ $siswa?->status_badge_class ?? 'badge-secondary' }} px-2 py-1" style="font-size: 0.71rem;">
                                                {{ $siswa?->status ?? '-' }}
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <a href="{{ $detailUrl }}" class="btn btn-sm btn-outline-primary font-weight-semibold"
                                                style="border-radius: 8px; font-size: 0.72rem;" title="Lihat biodata lengkap siswa">
                                                <i class="fa fa-eye mr-1"></i> Detail
                                            </a>
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
