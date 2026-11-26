@extends('layouts.appui')

@section('title')
    <title>Detail Perkembangan {{ $siswa->nama_lengkap }} - PION Yayasan Prima Insani</title>
@endsection

@section('content')
    @include('waliKelas.partials._styles')

    @php
        $isSd = $isSd ?? false;
        $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $isMid = $periode === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $presensiStats = $presensiStats ?? ['total' => 0, 'hadir' => 0, 'terlambat' => 0, 'izin' => 0, 'sakit' => 0, 'alpa' => 0, 'hadir_rate' => null];
        $summary = $summary ?? ['total_mapel' => 0, 'dinilai_count' => 0, 'tuntas_count' => 0, 'belum_tuntas_count' => 0, 'rata_rapor' => null];
        $subjectDetails = $subjectDetails ?? collect();
        $readiness = $readiness ?? null;
        $isGuruMapelScope = $isGuruMapelScope ?? false;
        $canPrintRapor = $canPrintRapor ?? ! $isGuruMapelScope;
        $perkembanganRoutePrefix = $perkembanganRoutePrefix ?? 'wali-kelas.perkembangan-siswa';
        $tpRoutePrefix = $tpRoutePrefix ?? 'wali-kelas.penilaian-tp';
        $canPrintNilai = $canPrintRapor && ($readiness === null || (bool) ($readiness['is_ready'] ?? false));
        $pendingCountRapor = (int) ($readiness['pending_count'] ?? 0);
    @endphp

    <div class="app-main__inner">
        <div class="wk-hero">
            <div class="wk-hero-glow"></div>
            <div class="row align-items-center position-relative" style="z-index: 1;">
                <div class="col-lg-8 mb-3 mb-lg-0">
                    <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                        <span class="badge text-white px-2 py-1 font-weight-bold"
                            style="background: rgba(234, 88, 12, 0.92); font-size: 11px; border-radius: 6px;">
                            <i class="fa fa-user-graduate mr-1"></i> PERKEMBANGAN SISWA
                        </span>
                        <span class="badge px-2.5 py-1"
                            style="background: rgba(255, 255, 255, 0.14); color: #ffffff; font-size: 11px;">
                            {{ $selectedKelas->nama_kelas }} ({{ $selectedKelas->jenjang_kelas }})
                        </span>
                        @if ($activeTahunAjaran)
                            <span class="badge px-2.5 py-1"
                                style="background: rgba(16, 185, 129, 0.22); color: #ecfdf5; font-size: 11px;">
                                {{ $activeTahunAjaran->full_label }}
                            </span>
                        @endif
                    </div>

                    <div class="d-flex align-items-center gap-3 mb-2">
                        @if ($siswa->avatar_url)
                            <img src="{{ $siswa->avatar_url }}" alt="{{ $siswa->nama_lengkap }}"
                                style="width: 56px; height: 56px; border-radius: 14px; object-fit: cover; border: 2px solid rgba(255,255,255,0.3);">
                        @else
                            <div class="wk-avatar-fallback" style="width: 56px; height: 56px; font-size: 1.15rem; border-radius: 14px;">
                                {{ $siswa->inisial_nama }}
                            </div>
                        @endif
                        <div>
                            <h3 class="font-weight-bold text-white mb-0">{{ $siswa->nama_lengkap }}</h3>
                            <div class="small" style="color: rgba(255,255,255,0.78);">
                                NIS: <strong>{{ $siswa->nis }}</strong>
                                @if ($siswa->nisn)
                                    &bull; NISN: {{ $siswa->nisn }}
                                @endif
                                &bull; {{ $siswa->jenis_kelamin_label }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-flex flex-wrap justify-content-lg-end gap-2">
                    @if ($canPrintRapor)
                        <a href="{{ route('wali-kelas.rapor.identitas.siswa', $anggotaKelas) }}" target="_blank"
                            class="btn btn-sm btn-light font-weight-semibold shadow-sm" style="border-radius: 9px;">
                            <i class="fa fa-id-card text-primary mr-1"></i> PDF Identitas
                        </a>
                        @if ($isSd)
                            @if ($canPrintNilai)
                                <a href="{{ route('wali-kelas.rapor.nilai.siswa', ['anggota_kelas' => $anggotaKelas, 'periode' => $periode]) }}" target="_blank"
                                    class="btn btn-sm btn-light font-weight-semibold shadow-sm" style="border-radius: 9px; color: #c2410c;">
                                    <i class="fa fa-file-pdf mr-1"></i> PDF Nilai
                                </a>
                            @else
                                <button type="button"
                                    class="btn btn-sm btn-secondary font-weight-semibold shadow-sm"
                                    style="border-radius: 9px; opacity: 0.85;"
                                    onclick="if (window.toastr) { window.toastr.error('Cetak Nilai Rapor diblokir: masih ada {{ $pendingCountRapor }} komponen belum Final.', 'Cetak Diblokir'); }"
                                    title="PDF Nilai diblokir: masih ada {{ $pendingCountRapor }} komponen belum Final.">
                                    <i class="fa fa-lock mr-1"></i> PDF Nilai ({{ $pendingCountRapor }} Draft)
                                </button>
                            @endif
                        @endif
                        <a href="{{ route('wali-kelas.anggota-kelas.show', $anggotaKelas) }}"
                            class="btn btn-sm btn-light font-weight-semibold shadow-sm" style="border-radius: 9px;">
                            <i class="fa fa-user mr-1"></i> Biodata
                        </a>
                    @endif
                    <a href="{{ route($perkembanganRoutePrefix.'.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $periode]) }}"
                        class="btn btn-sm text-white font-weight-semibold shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 9px;">
                        <i class="fa fa-arrow-left mr-1"></i> Kembali ke Leger
                    </a>
                </div>
            </div>
        </div>

        @if ($isSd)
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
                <div class="small text-muted font-weight-semibold">
                    Menampilkan rekap periode <strong>{{ $isMid ? 'Mid Semester' : 'Semester' }}</strong> (beserta perbandingan Mid vs Semester per mapel &amp; TP).
                </div>
                <div class="btn-group btn-group-sm shadow-sm">
                    <a href="{{ route($perkembanganRoutePrefix.'.show', ['anggota_kelas' => $anggotaKelas, 'periode' => 'mid']) }}"
                        class="btn {{ $isMid ? 'text-white font-weight-bold' : 'btn-light border text-slate-700' }}"
                        style="{{ $isMid ? 'background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);' : '' }}">
                        Mid Semester
                    </a>
                    <a href="{{ route($perkembanganRoutePrefix.'.show', ['anggota_kelas' => $anggotaKelas, 'periode' => 'semester']) }}"
                        class="btn {{ ! $isMid ? 'text-white font-weight-bold' : 'btn-light border text-slate-700' }}"
                        style="{{ ! $isMid ? 'background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);' : '' }}">
                        Semester
                    </a>
                </div>
            </div>
        @endif

        <!-- KPI SUMMARY -->
        <div class="wk-kpi-grid">
            <div class="wk-kpi-card">
                <div>
                    <div class="wk-kpi-label">
                        <span class="wk-kpi-dot" style="background: #ea580c;"></span> Rata-Rata NA Rapor
                    </div>
                    <div class="wk-kpi-value" style="color: #c2410c;">
                        {{ $summary['rata_rapor'] !== null ? number_format((float) $summary['rata_rapor'], 1) : '-' }}
                    </div>
                    <div class="wk-kpi-sub">
                        {{ $summary['dinilai_count'] }} dari {{ $summary['total_mapel'] }} mapel dinilai
                    </div>
                </div>
                <span class="wk-kpi-icon" style="background: #fff7ed; color: #ea580c;">
                    <i class="fa fa-star"></i>
                </span>
            </div>

            <div class="wk-kpi-card">
                <div>
                    <div class="wk-kpi-label">
                        <span class="wk-kpi-dot" style="background: #10b981;"></span> Mapel Tuntas KKM
                    </div>
                    <div class="wk-kpi-value" style="color: #047857;">{{ $summary['tuntas_count'] }}</div>
                    <div class="wk-kpi-sub">
                        Dari {{ $summary['dinilai_count'] }} mapel bernilai
                    </div>
                </div>
                <span class="wk-kpi-icon" style="background: #ecfdf5; color: #059669;">
                    <i class="fa fa-check-circle"></i>
                </span>
            </div>

            <div class="wk-kpi-card">
                <div>
                    <div class="wk-kpi-label">
                        <span class="wk-kpi-dot" style="background: #ef4444;"></span> Perlu Pendampingan
                    </div>
                    <div class="wk-kpi-value" style="color: #b91c1c;">{{ $summary['belum_tuntas_count'] }}</div>
                    <div class="wk-kpi-sub">
                        Mapel dengan nilai di bawah KKM
                    </div>
                </div>
                <span class="wk-kpi-icon" style="background: #fef2f2; color: #dc2626;">
                    <i class="fa fa-exclamation-triangle"></i>
                </span>
            </div>

            <div class="wk-kpi-card">
                <div>
                    <div class="wk-kpi-label">
                        <span class="wk-kpi-dot" style="background: #8b5cf6;"></span> Kehadiran Presensi
                    </div>
                    <div class="wk-kpi-value" style="color: #6d28d9;">
                        {{ $presensiStats['hadir_rate'] !== null ? $presensiStats['hadir_rate'].'%' : '-' }}
                    </div>
                    <div class="wk-kpi-sub">
                        H:{{ $presensiStats['hadir'] }} T:{{ $presensiStats['terlambat'] }} I:{{ $presensiStats['izin'] }} S:{{ $presensiStats['sakit'] }} A:{{ $presensiStats['alpa'] }}
                    </div>
                </div>
                <span class="wk-kpi-icon" style="background: #f5f3ff; color: #7c3aed;">
                    <i class="fa fa-calendar-check"></i>
                </span>
            </div>
        </div>

        @if ($canEditCatatanWali ?? false)
            @php
                $catatanWaliObj = $catatanWali ?? null;
                $komentarTemplatesList = $komentarTemplates ?? collect();
                $catatanText = old('catatan', $catatanWaliObj?->catatan ?? '');
            @endphp
            <div class="wk-surface mb-3">
                <div class="wk-surface-head">
                    <div>
                        <h6 class="wk-surface-title mb-0">
                            <i class="fa fa-comment-dots text-warning mr-1"></i>
                            Catatan Wali Kelas / Pendamping ({{ $isMid ? 'Mid Semester' : 'Semester' }})
                        </h6>
                        <div class="small text-muted">
                            Tampil di kotak CATATAN WALI KELAS pada PDF Nilai Rapor (opsional / tidak memblokir cetak PDF).
                        </div>
                    </div>
                    @if ($catatanWaliObj?->updated_by_name)
                        <span class="badge badge-light border text-muted" style="font-size: 0.71rem;">
                            Diisi {{ $catatanWaliObj->updated_by_name }} ({{ $catatanWaliObj->updated_by_role_label ?? 'Wali Kelas' }})
                        </span>
                    @endif
                </div>
                <div class="p-3">
                    @if (session('success'))
                        <div class="alert alert-success alert-dismissible fade show py-2 small" role="alert">
                            <i class="fa fa-check-circle mr-1"></i> {{ session('success') }}
                            <button type="button" class="close py-1" data-dismiss="alert" aria-label="Tutup">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif
                    <form method="POST" action="{{ route('wali-kelas.catatan-rapor.update-single', $anggotaKelas) }}">
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="periode" value="{{ $periode }}">

                        @if ($komentarTemplatesList->isNotEmpty())
                            <div class="form-group mb-2">
                                <select name="komentar_rapor_id" id="singleCatatanTemplateSelect"
                                    class="form-control form-control-sm" style="max-width: 360px;">
                                    <option value="">-- Pilih Template Komentar Cepat (Opsional) --</option>
                                    @foreach ($komentarTemplatesList as $tpl)
                                        <option value="{{ $tpl->sqid ?? '' }}"
                                            data-body="{{ $tpl->renderForSiswa($siswa->nama_lengkap) }}"
                                            @selected($tpl->id !== null && ((string) old('komentar_rapor_id', $catatanWaliObj?->komentar_rapor_id) === (string) $tpl->id || (string) old('komentar_rapor_id', $catatanWaliObj?->komentar_rapor_id) === (string) $tpl->sqid))>
                                            {{ $tpl->judul }} ({{ $tpl->kategori_label }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="form-group mb-2">
                            <textarea name="catatan" id="singleCatatanTextarea" rows="3"
                                class="form-control form-control-sm"
                                placeholder="Tuliskan komentar/catatan wali kelas atau pendamping untuk {{ $siswa->nama_lengkap }}...">{{ $catatanText }}</textarea>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <a href="{{ route('wali-kelas.catatan-rapor.index', ['kelas_id' => $selectedKelas->sqid, 'periode' => $periode]) }}"
                                class="small font-weight-semibold text-primary">
                                <i class="fa fa-list mr-1"></i> Kelola seluruh kelas
                            </a>
                            <button type="submit" class="btn btn-sm text-white font-weight-bold px-3 shadow-sm"
                                style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%); border: none; border-radius: 8px;">
                                <i class="fa fa-save mr-1"></i> Simpan Catatan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            @if ($komentarTemplatesList->isNotEmpty())
                @pushOnce('js')
                    <script>
                        document.addEventListener('DOMContentLoaded', function() {
                            const sel = document.getElementById('singleCatatanTemplateSelect');
                            const box = document.getElementById('singleCatatanTextarea');
                            if (sel && box) {
                                sel.addEventListener('change', function() {
                                    const opt = this.options[this.selectedIndex];
                                    const body = opt ? opt.getAttribute('data-body') : '';
                                    if (body) box.value = body;
                                });
                            }
                        });
                    </script>
                @endPushOnce
            @endif
        @endif

        @if ($isSd)
            @foreach ($subjectDetails as $item)
                @php
                    $mp = $item['mapel'];
                    $selectedRekap = $item['selected_rekap'];
                    $rekapMid = $item['rekap_mid'];
                    $rekapSem = $item['rekap_semester'];
                    $tpItems = $item['tp_items'];
                @endphp
                <div class="wk-surface">
                    <div class="wk-surface-head">
                        <div>
                            <div class="d-flex flex-wrap align-items-center gap-2">
                                <h6 class="wk-surface-title">[{{ $mp->singkatan }}] {{ $mp->nama_mata_pelajaran }}</h6>
                                @if ($item['is_taught_by_user'])
                                    <span class="badge badge-warning text-dark font-weight-bold" style="font-size: 0.67rem;">Anda Ampu</span>
                                @endif
                            </div>
                            <div class="small text-muted">
                                Guru Pengampu: <strong>{{ $item['teachers'] !== [] ? implode(', ', $item['teachers']) : 'Belum dijadwalkan' }}</strong>
                                &bull; KKM: <strong>{{ rtrim(rtrim(number_format((float) $item['kkm'], 2, '.', ''), '0'), '.') }}</strong>
                            </div>
                        </div>

                        <div class="d-flex flex-wrap align-items-center gap-2">
                            @if ($rekapMid?->nilai_akhir_rapor !== null)
                                <span class="badge badge-light border px-2 py-1" style="font-size: 0.72rem;">
                                    Mid: <strong>{{ rtrim(rtrim(number_format((float) $rekapMid->nilai_akhir_rapor, 1, '.', ''), '0'), '.') }}</strong>
                                </span>
                            @endif
                            @if ($rekapSem?->nilai_akhir_rapor !== null)
                                <span class="badge badge-light border px-2 py-1" style="font-size: 0.72rem;">
                                    Sem: <strong>{{ rtrim(rtrim(number_format((float) $rekapSem->nilai_akhir_rapor, 1, '.', ''), '0'), '.') }}</strong>
                                </span>
                            @endif

                            @if ($selectedRekap?->nilai_akhir_rapor !== null)
                                <span class="badge {{ $selectedRekap->tuntas_kkm ? 'badge-success' : 'badge-danger' }} px-2.5 py-1" style="font-size: 0.74rem;">
                                    NA {{ rtrim(rtrim(number_format((float) $selectedRekap->nilai_akhir_rapor, 1, '.', ''), '0'), '.') }}
                                    @if ($selectedRekap->predikat_rapor)
                                        ({{ $selectedRekap->predikat_rapor }})
                                    @endif
                                    &bull; {{ $selectedRekap->tuntas_kkm ? 'Tuntas' : 'Belum Tuntas' }}
                                </span>
                            @else
                                <span class="badge badge-secondary px-2 py-1" style="font-size: 0.72rem;">Belum Dinilai</span>
                            @endif

                            @if ($item['is_taught_by_user'])
                                <a href="{{ route($tpRoutePrefix.'.index', ['kelas_id' => $selectedKelas->sqid, 'mata_pelajaran_id' => $mp->sqid, 'periode' => $periode]) }}"
                                    class="btn btn-sm btn-outline-primary font-weight-semibold" style="border-radius: 8px; font-size: 0.72rem;">
                                    <i class="fa fa-edit mr-1"></i> Kelola Nilai
                                </a>
                            @endif
                        </div>
                    </div>

                    <div class="p-3">
                        @if ($selectedRekap)
                            <div class="d-flex flex-wrap gap-3 small mb-2 px-2 py-2 rounded" style="background: #f8fafc; border: 1px solid #e2e8f0;">
                                <span>NA TP: <strong>{{ $selectedRekap->rata_rata_tp !== null ? number_format((float) $selectedRekap->rata_rata_tp, 1) : '-' }}</strong></span>
                                <span>Rata Sumatif: <strong>{{ $selectedRekap->rata_rata_sumatif !== null ? number_format((float) $selectedRekap->rata_rata_sumatif, 1) : '-' }}</strong></span>
                                <span>Nilai Ujian: <strong>{{ $selectedRekap->nilai_ujian !== null ? number_format((float) $selectedRekap->nilai_ujian, 1) : '-' }}</strong></span>
                            </div>

                            @if ($selectedRekap->deskripsi_capaian)
                                <div class="small p-2.5 rounded mb-2" style="background: #fff7ed; border: 1px solid #fed7aa; color: #7c2d12;">
                                    <strong class="d-block mb-0.5"><i class="fa fa-align-left mr-1"></i> Deskripsi Capaian Rapor:</strong>
                                    {{ $selectedRekap->deskripsi_capaian }}
                                </div>
                            @endif
                        @endif

                        @if ($tpItems->isNotEmpty())
                            <div class="table-responsive">
                                <table class="wk-table">
                                    <thead>
                                        <tr>
                                            <th style="width: 90px;">Kode TP</th>
                                            <th>Tujuan Pembelajaran / Lingkup Materi</th>
                                            <th class="text-center" style="width: 110px;">NA Mid</th>
                                            <th class="text-center" style="width: 110px;">NA Semester</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($tpItems as $tpRow)
                                            @php
                                                $tpModel = $tpRow['tp'];
                                                $midTp = $tpRow['mid'];
                                                $semTp = $tpRow['semester'];
                                            @endphp
                                            <tr>
                                                <td class="font-weight-bold text-dark">{{ $tpModel->kode_tp }}</td>
                                                <td>
                                                    <div class="text-dark">{{ $tpModel->tujuan_pembelajaran }}</div>
                                                    @if ($tpModel->lingkup_materi)
                                                        <div class="small text-muted" style="font-size: 0.7rem;">{{ $tpModel->lingkup_materi }}</div>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($midTp?->nilai_akhir_tp !== null)
                                                        <span class="badge {{ $midTp->tuntas_kkm === false ? 'badge-danger' : 'badge-success' }}">
                                                            {{ rtrim(rtrim(number_format((float) $midTp->nilai_akhir_tp, 1, '.', ''), '0'), '.') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                                <td class="text-center">
                                                    @if ($semTp?->nilai_akhir_tp !== null)
                                                        <span class="badge {{ $semTp->tuntas_kkm === false ? 'badge-danger' : 'badge-success' }}">
                                                            {{ rtrim(rtrim(number_format((float) $semTp->nilai_akhir_tp, 1, '.', ''), '0'), '.') }}
                                                        </span>
                                                    @else
                                                        <span class="text-muted small">-</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="small text-muted font-italic">Belum ada Tujuan Pembelajaran aktif untuk mata pelajaran ini.</div>
                        @endif
                    </div>
                </div>
            @endforeach
        @else
            <div class="wk-surface p-4 text-center text-muted small">
                <i class="fa fa-info-circle text-warning mr-1"></i>
                Penilaian TP &amp; deskripsi rapor terperinci saat ini hanya tersedia untuk jenjang SD. Ringkasan presensi siswa ditampilkan di kartu di atas.
            </div>
        @endif
    </div>
@endsection

