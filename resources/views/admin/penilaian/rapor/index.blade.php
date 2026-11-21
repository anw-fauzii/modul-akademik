@extends('layouts.appui')

@section('title')
    <title>Cetak PDF Rapor (Identitas &amp; Nilai)</title>
@endsection

@section('content')
    @php
        $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
        $kelasOptions = $kelasOptions ?? collect();
        $periodeOptions = $periodeOptions ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_OPTIONS;
        $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $students = $students ?? collect();
        $rekapCountByAnggota = $rekapCountByAnggota ?? [];
        $ekskulCountByAnggota = $ekskulCountByAnggota ?? [];
        $t2qCountByAnggota = $t2qCountByAnggota ?? [];
        $presensiByAnggota = $presensiByAnggota ?? [];
        $totalSdMapels = $totalSdMapels ?? 0;
        $isSd = $isSd ?? false;
        $readiness = $readiness ?? null;
        $canPrintNilai = $readiness === null || (bool) ($readiness['is_ready'] ?? false);
        $pendingCountRapor = (int) ($readiness['pending_count'] ?? 0);
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-print" style="color: white;"></i>
                    </div>
                    <div>
                        Cetak PDF Rapor Hasil Pembelajaran
                        <div class="page-title-subheading">
                            Cetak dokumen <strong>Identitas Sekolah &amp; Siswa</strong> serta <strong>Nilai Hasil Belajar</strong> secara terpisah (per siswa atau massal 1 kelas).
                        </div>
                    </div>
                </div>

                @if ($selectedKelas && $students->isNotEmpty())
                    <div class="page-title-actions d-flex flex-wrap" style="gap: 0.5rem;">
                        <a href="{{ route('penilaian-rapor.identitas.kelas', $selectedKelas) }}" target="_blank"
                            class="btn btn-light border shadow-sm font-weight-semibold"
                            style="border-radius: 9px; color: #1e293b;">
                            <i class="fa fa-id-card text-primary mr-1"></i> Cetak Identitas (1 Kelas)
                        </a>
                        @if ($isSd)
                            @if ($canPrintNilai)
                                <a href="{{ route('penilaian-rapor.nilai.kelas', ['kelas' => $selectedKelas, 'periode' => $periode]) }}" target="_blank"
                                    class="btn text-white shadow-sm font-weight-bold"
                                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 9px;">
                                    <i class="fa fa-file-pdf mr-1"></i> Cetak Nilai Rapor (1 Kelas)
                                </a>
                            @else
                                <button type="button"
                                    class="btn btn-secondary shadow-sm font-weight-bold"
                                    style="border-radius: 9px; opacity: 0.85;"
                                    onclick="if (window.toastr) { window.toastr.error('Cetak Nilai Rapor diblokir: masih ada {{ $pendingCountRapor }} komponen belum Final pada periode ini.', 'Cetak Diblokir'); }"
                                    title="Diblokir: masih ada {{ $pendingCountRapor }} komponen belum Final pada periode ini.">
                                    <i class="fa fa-lock mr-1"></i> Cetak Nilai Rapor ({{ $pendingCountRapor }} Belum Final)
                                </button>
                            @endif
                        @endif
                    </div>
                @endif
            </div>
        </div>

        <!-- FILTER KONTEKS -->
        <div class="main-card card mb-3 shadow-sm border-0" style="border-radius: 14px;">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('penilaian-rapor.index') }}" class="row align-items-end">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <label for="rapor_ta" class="small font-weight-bold text-muted mb-1">Tahun Ajaran</label>
                        <select name="tahun_ajaran_id" id="rapor_ta" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach ($tahunAjaranOptions as $ta)
                                <option value="{{ $ta->sqid }}" @selected((int) $selectedTahunAjaranId === (int) $ta->id)>
                                    {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-4 mb-2 mb-md-0">
                        <label for="rapor_kelas" class="small font-weight-bold text-muted mb-1">Kelas</label>
                        <select name="kelas_id" id="rapor_kelas" class="form-control form-control-sm" onchange="this.form.submit()">
                            @forelse ($kelasOptions as $kls)
                                <option value="{{ $kls->sqid }}" @selected($selectedKelas && (int) $selectedKelas->id === (int) $kls->id)>
                                    [{{ strtoupper((string) $kls->jenjang_kelas) }}] {{ $kls->nama_kelas }}
                                </option>
                            @empty
                                <option value="">Belum ada kelas di TA ini</option>
                            @endforelse
                        </select>
                    </div>

                    <div class="col-md-4">
                        <label for="rapor_periode" class="small font-weight-bold text-muted mb-1">Periode Nilai Rapor</label>
                        <select name="periode" id="rapor_periode" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach ($periodeOptions as $kode => $label)
                                <option value="{{ $kode }}" @selected($periode === $kode)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>
        </div>

        @if ($selectedKelas)
            @if ($isSd && $readiness !== null)
                @include('admin.penilaian.partials._rapor_readiness_panel', [
                    'readiness' => $readiness,
                    'routePrefix' => 'penilaian-finalisasi',
                    'periode' => $periode,
                ])
            @endif

            <!-- STATUS KELENGKAPAN MASTER DATA (SEKOLAH & TITIMANGSA) -->
            <div class="row mb-3">
                <div class="col-md-6 mb-2 mb-md-0">
                    <div class="card border shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small text-muted font-weight-bold text-uppercase">Identitas Sekolah ({{ strtoupper((string) $selectedKelas->jenjang_kelas) }})</div>
                                @if ($sekolah)
                                    <div class="font-weight-bold text-dark mt-1">{{ $sekolah->nama_sekolah }}</div>
                                    <div class="small text-muted">NPSN: {{ $sekolah->npsn ?: '-' }}</div>
                                @else
                                    <div class="text-danger small font-weight-bold mt-1">
                                        Belum diatur untuk jenjang {{ strtoupper((string) $selectedKelas->jenjang_kelas) }}
                                    </div>
                                @endif
                            </div>
                            <a href="{{ $sekolah ? route('sekolah.edit', $sekolah) : route('sekolah.create') }}"
                                class="btn btn-sm btn-outline-primary font-weight-semibold" style="border-radius: 8px;">
                                {{ $sekolah ? 'Ubah' : 'Atur Sekolah' }}
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card border shadow-sm h-100" style="border-radius: 12px;">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <div class="small text-muted font-weight-bold text-uppercase">
                                    Titimangsa &amp; Kepsek ({{ $periodeOptions[$periode] ?? $periode }})
                                </div>
                                @if ($titimangsa)
                                    <div class="font-weight-bold text-dark mt-1">{{ $titimangsa->titimangsa_label }}</div>
                                    <div class="small text-muted">
                                        {{ $titimangsa->kepala_sekolah_nama_tampil }} ({{ $titimangsa->tingkatan_label }})
                                    </div>
                                @else
                                    <div class="text-danger small font-weight-bold mt-1">
                                        Belum diatur untuk {{ $selectedKelas->jenjang_kelas }} tingkat {{ $selectedKelas->tingkatan_kelas }}
                                    </div>
                                @endif
                            </div>
                            <a href="{{ $titimangsa ? route('titimangsa.edit', $titimangsa) : route('titimangsa.create') }}"
                                class="btn btn-sm btn-outline-primary font-weight-semibold" style="border-radius: 8px;">
                                {{ $titimangsa ? 'Ubah' : 'Atur Titimangsa' }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- DAFTAR SISWA & AKSI CETAK TERPISAH -->
            <div class="main-card card mb-3 shadow-sm border-0" style="border-radius: 14px;">
                <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center" style="border-bottom: 1px solid #f1f5f9;">
                    <div>
                        <h6 class="font-weight-bold text-dark mb-0">
                            Daftar Siswa Kelas {{ $selectedKelas->nama_kelas }}
                        </h6>
                        <div class="small text-muted">
                            Wali Kelas: <strong>{{ $selectedKelas->waliKelas?->nama_gelar ?? ($selectedKelas->karyawan_nipy ?: '-') }}</strong>
                            &bull; {{ $students->count() }} siswa
                        </div>
                    </div>
                    @if (! $isSd)
                        <span class="badge badge-info px-2 py-1">
                            Jenjang {{ strtoupper((string) $selectedKelas->jenjang_kelas) }}: Cetak Identitas tersedia (Cetak Nilai khusus SD)
                        </span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="mb-0 table table-hover align-middle" id="myTable3"
                            data-search-placeholder="Cari nama siswa...">
                            <thead>
                                <tr>
                                    <th style="width: 5%;">No</th>
                                    <th style="width: 14%;">NIS / NISN</th>
                                    <th>Nama Siswa</th>
                                    @if ($isSd)
                                        <th class="text-center" style="width: 14%;">Mapel Dinilai</th>
                                    @endif
                                    <th class="text-center" style="width: 14%;">Ekskul / T2Q</th>
                                    <th class="text-center" style="width: 16%;">Absensi (S/I/A)</th>
                                    <th class="text-right" style="width: 24%;">Cetak PDF</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students as $anggota)
                                    @php
                                        $siswa = $anggota->siswa;
                                        $dinilai = $rekapCountByAnggota[$anggota->id] ?? 0;
                                        $ekskulCount = $ekskulCountByAnggota[$anggota->id] ?? 0;
                                        $t2qCount = $t2qCountByAnggota[$anggota->id] ?? 0;
                                        $absen = $presensiByAnggota[$anggota->id] ?? ['sakit' => 0, 'izin' => 0, 'alpa' => 0];
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration }}</td>
                                        <td>
                                            <div class="font-weight-bold text-dark">{{ $anggota->siswa_nis }}</div>
                                            <div class="small text-muted">{{ $siswa?->nisn ?: '-' }}</div>
                                        </td>
                                        <td class="font-weight-bold text-slate-800">
                                            {{ $siswa?->nama_lengkap ?? $anggota->siswa_nis }}
                                        </td>
                                        @if ($isSd)
                                            <td class="text-center">
                                                <span class="badge {{ $dinilai > 0 ? 'badge-success' : 'badge-light border text-muted' }} px-2 py-1">
                                                    {{ $dinilai }} / {{ $totalSdMapels }}
                                                </span>
                                            </td>
                                        @endif
                                        <td class="text-center small text-muted">
                                            {{ $ekskulCount }} Ekskul &bull; {{ $t2qCount > 0 ? 'Ada T2Q' : 'Non-T2Q' }}
                                        </td>
                                        <td class="text-center small">
                                            S: <strong>{{ $absen['sakit'] }}</strong> &bull;
                                            I: <strong>{{ $absen['izin'] }}</strong> &bull;
                                            A: <strong>{{ $absen['alpa'] }}</strong>
                                        </td>
                                        <td class="text-right text-nowrap">
                                            <a href="{{ route('penilaian-rapor.identitas.siswa', $anggota) }}" target="_blank"
                                                class="btn btn-sm btn-outline-primary font-weight-semibold mr-1"
                                                style="border-radius: 7px;" title="Cetak Cover, Data Sekolah &amp; Biodata Siswa">
                                                <i class="fa fa-id-card mr-1"></i> Identitas
                                            </a>
                                            @if ($isSd)
                                                @if ($canPrintNilai)
                                                    <a href="{{ route('penilaian-rapor.nilai.siswa', ['anggota_kelas' => $anggota, 'periode' => $periode]) }}" target="_blank"
                                                        class="btn btn-sm text-white font-weight-semibold"
                                                        style="background: #ea580c; border-radius: 7px;" title="Cetak Nilai Hasil Belajar">
                                                        <i class="fa fa-file-pdf mr-1"></i> Nilai
                                                    </a>
                                                @else
                                                    <button type="button"
                                                        class="btn btn-sm btn-secondary font-weight-semibold"
                                                        style="border-radius: 7px; opacity: 0.85;"
                                                        onclick="if (window.toastr) { window.toastr.error('Cetak Nilai Rapor diblokir: masih ada {{ $pendingCountRapor }} komponen belum Final.', 'Cetak Diblokir'); }"
                                                        title="Diblokir: masih ada {{ $pendingCountRapor }} komponen belum Final">
                                                        <i class="fa fa-lock mr-1"></i> Nilai
                                                    </button>
                                                @endif
                                            @endif
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

