@extends('layouts.appui')

@section('title')
    <title>Rekap Nilai &amp; Deskripsi Rapor TP (SD)</title>
@endsection

@section('content')
    @push('css')
        <style>
            .rk-filter-bar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 0.9rem 1.15rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            }

            .rk-filter-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.75rem;
                align-items: end;
            }

            @media (min-width: 768px) {
                .rk-filter-grid {
                    grid-template-columns: 1.2fr 1fr 1.2fr auto;
                }
            }

            .rk-periode-tabs {
                display: inline-flex;
                align-items: center;
                padding: 0.25rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                gap: 0.25rem;
            }

            .rk-periode-tab {
                padding: 0.42rem 0.9rem;
                border-radius: 9px;
                font-size: 0.8rem;
                font-weight: 700;
                color: #475569;
                text-decoration: none !important;
            }

            .rk-periode-tab.is-active {
                background: #ffffff;
                color: #c2410c;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
            }

            .rk-modal-overlay {
                position: fixed;
                inset: 0;
                z-index: 1050;
                display: none;
                align-items: center;
                justify-content: center;
                padding: 1rem;
                background: rgba(15, 23, 42, 0.55);
                backdrop-filter: blur(2px);
            }

            .rk-modal-overlay.is-open {
                display: flex;
            }

            .rk-modal-card {
                width: 100%;
                max-width: 540px;
                background: #ffffff;
                border-radius: 16px;
                border: 1px solid #e2e8f0;
                box-shadow: 0 20px 45px -12px rgba(15, 23, 42, 0.35);
                overflow: hidden;
            }
        </style>
    @endpush

    @php
        $periode = $periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $isMid = $periode === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
        $bobotTp = (int) ($bobotTp ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_TP);
        $bobotUjian = (int) ($bobotUjian ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_UJIAN);
        $ujianLabel = $ujianLabel ?? \App\Models\Admin\Penilaian\PeriodeTp::ujianLabel($periode);
        $tpList = $tpList ?? collect();
        $students = $students ?? collect();
        $combinedTpSummaries = $combinedTpSummaries ?? [];
        $rekapMapelLookup = $rekapMapelLookup ?? [];
        $kkmNilai = $kkmNilai ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_KKM;

        $contextParams = array_filter([
            'tahun_ajaran_id' => $selectedTahunAjaranId ?? null,
            'kelas_id' => $selectedKelas?->id ?? null,
            'mata_pelajaran_id' => $selectedMapel?->id ?? null,
            'periode' => $periode,
        ], static fn ($v) => $v !== null && $v !== '');
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-graph2" style="color: white;"></i>
                    </div>
                    <div>
                        Rekap Nilai &amp; Deskripsi Rapor ({{ $isMid ? 'Mid Semester' : 'Semester' }})
                        <div class="page-title-subheading">
                            @if ($bobotUjian > 0)
                                Rumus aktif: <strong>{{ $bobotTp }}% Rata-rata NA TP</strong> {{ $isMid ? '(TP Mid terpilih)' : '(gabungan Mid + Semester)' }} + <strong>{{ $bobotUjian }}% Nilai {{ $ujianLabel }}</strong>.
                            @elseif ($isMid)
                                Rapor Mid Semester hanya menghitung TP yang dipilih masuk Rapor Mid.
                            @else
                                Rapor Semester menggabungkan seluruh NA TP dari Mid Semester + Semester (100% murni rata-rata NA TP).
                            @endif
                        </div>
                    </div>
                </div>

                <div class="page-title-actions flex-wrap">
                    <a href="{{ route('penilaian-tp.index', $contextParams) }}" class="btn btn-light shadow-sm"
                        style="border-radius: 10px; font-weight: 600;">
                        <i class="fa fa-th mr-1"></i> Kembali ke Input Nilai
                    </a>

                    @if ($selectedKelas && $selectedMapel && $students->isNotEmpty())
                        <form method="POST" action="{{ route('penilaian-rekap.deskripsi.regenerate') }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                            <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedMapel->id }}">
                            <input type="hidden" name="periode" value="{{ $periode }}">
                            <button type="submit" class="btn text-white shadow-sm"
                                title="Susun deskripsi otomatis untuk seluruh TP yang bernilai NA TP berdasarkan kelompok predikat KKM (gabung dengan koma dan 'dan')"
                                style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px; font-weight: 600;">
                                <i class="fa fa-sync mr-1"></i> Generate Ulang Deskripsi
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        </div>

        <div class="rk-filter-bar">
            <form method="GET" action="{{ route('penilaian-rekap.index') }}">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <div class="rk-filter-grid">
                    <div>
                        <label for="rk_ta" class="small font-weight-semibold text-muted mb-1">Tahun Ajaran / Semester</label>
                        <select name="tahun_ajaran_id" id="rk_ta" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach ($tahunAjaranOptions as $ta)
                                <option value="{{ $ta->id }}" @selected((int) $selectedTahunAjaranId === (int) $ta->id)>
                                    {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="rk_kelas" class="small font-weight-semibold text-muted mb-1">Kelas (SD)</label>
                        <select name="kelas_id" id="rk_kelas" class="form-control form-control-sm" onchange="this.form.submit()">
                            @forelse ($kelasOptions as $kls)
                                <option value="{{ $kls->id }}" @selected($selectedKelas && (int) $selectedKelas->id === (int) $kls->id)>
                                    {{ $kls->nama_kelas }} (Tingkat {{ $kls->tingkatan_kelas }})
                                </option>
                            @empty
                                <option value="">Belum ada kelas SD</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label for="rk_mapel" class="small font-weight-semibold text-muted mb-1">Mata Pelajaran (SD)</label>
                        <select name="mata_pelajaran_id" id="rk_mapel" class="form-control form-control-sm" onchange="this.form.submit()">
                            @forelse ($mataPelajaranOptions as $mp)
                                <option value="{{ $mp->id }}" @selected($selectedMapel && (int) $selectedMapel->id === (int) $mp->id)>
                                    [{{ $mp->singkatan }}] {{ $mp->nama_mata_pelajaran }}
                                </option>
                            @empty
                                <option value="">Belum ada mapel SD</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <div class="small font-weight-semibold text-muted mb-1">Periode Rapor</div>
                        <div class="rk-periode-tabs">
                            <a href="{{ route('penilaian-rekap.index', array_merge($contextParams, ['periode' => 'mid'])) }}"
                                class="rk-periode-tab {{ $isMid ? 'is-active' : '' }}">
                                Mid Semester
                            </a>
                            <a href="{{ route('penilaian-rekap.index', array_merge($contextParams, ['periode' => 'semester'])) }}"
                                class="rk-periode-tab {{ ! $isMid ? 'is-active' : '' }}">
                                Semester (Mid + Sem)
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="main-card card">
            <div class="card-body">
                @if ($students->isEmpty() || $tpList->isEmpty())
                    <div class="text-center py-5 text-muted">
                        Belum ada data TP / anggota kelas untuk ditampilkan pada filter ini.
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered" style="font-size: 0.8rem;">
                            <thead style="background: #f8fafc;">
                                <tr>
                                    <th class="text-center" style="width: 48px;">No</th>
                                    <th style="min-width: 190px;">Siswa</th>
                                    @foreach ($tpList as $tp)
                                        <th class="text-center" title="{{ $tp->tujuan_pembelajaran }}" style="min-width: 78px;">
                                            {{ $tp->kode_tp }}
                                        </th>
                                    @endforeach
                                    <th class="text-center" style="min-width: 76px; background: #eff6ff; color: #1e3a8a;">
                                        Rata TP
                                        @if ($bobotUjian > 0)
                                            <div style="font-size: 0.65rem; font-weight: 500;">({{ $bobotTp }}%)</div>
                                        @endif
                                    </th>
                                    <th class="text-center" style="min-width: 76px; background: #eef2ff; color: #3730a3;">
                                        {{ $ujianLabel }}
                                        <div style="font-size: 0.65rem; font-weight: 500;">{{ $bobotUjian > 0 ? "({$bobotUjian}%)" : '(0%)' }}</div>
                                    </th>
                                    <th class="text-center" style="min-width: 82px; background: #fff7ed; color: #9a3412;">NA Rapor</th>
                                    <th class="text-center" style="width: 85px;">Predikat</th>
                                    <th class="text-center" style="width: 115px;">Ketuntasan</th>
                                    <th style="min-width: 260px;">Deskripsi Capaian Rapor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students->values() as $idx => $student)
                                    @php
                                        $studentTpScores = $combinedTpSummaries[$student->id] ?? [];
                                        $rekapMapel = $rekapMapelLookup["{$student->id}:{$periode}"] ?? null;
                                    @endphp
                                    <tr>
                                        <td class="text-center text-muted">{{ $idx + 1 }}</td>
                                        <td>
                                            <div class="font-weight-semibold text-dark">
                                                {{ $student->siswa?->nama_lengkap ?? $student->siswa_nis }}
                                            </div>
                                            <div class="small text-muted">NIS: {{ $student->siswa_nis }}</div>
                                        </td>
                                        @foreach ($tpList as $tp)
                                            @php
                                                $info = $studentTpScores[$tp->id] ?? ['included' => true, 'nilai' => null, 'source' => null];
                                            @endphp
                                            <td class="text-center" style="{{ ! $info['included'] ? 'background: #f8fafc; color: #94a3b8;' : '' }}">
                                                @if ($info['nilai'] !== null)
                                                    <strong>{{ (int) round((float) $info['nilai']) }}</strong>
                                                    @if ($info['source'])
                                                        <div style="font-size: 0.64rem; color: #64748b;">
                                                            {{ $info['included'] ? $info['source'] : 'Non-Rapor' }}
                                                        </div>
                                                    @endif
                                                @else
                                                    <span class="text-muted">-</span>
                                                @endif
                                            </td>
                                        @endforeach
                                        <td class="text-center font-weight-semibold" style="background: #eff6ff; color: #1e3a8a;">
                                            {{ $rekapMapel?->rata_rata_tp !== null ? (int) round((float) $rekapMapel->rata_rata_tp) : '-' }}
                                        </td>
                                        <td class="text-center font-weight-semibold" style="background: #eef2ff; color: #3730a3;">
                                            {{ $rekapMapel?->nilai_ujian !== null ? rtrim(rtrim(number_format((float) $rekapMapel->nilai_ujian, 2, '.', ''), '0'), '.') : '-' }}
                                        </td>
                                        <td class="text-center font-weight-bold" style="background: #fff7ed; color: #9a3412;">
                                            {{ $rekapMapel?->nilai_akhir_rapor !== null ? (int) round((float) $rekapMapel->nilai_akhir_rapor) : '-' }}
                                        </td>
                                        <td class="text-center">
                                            @if ($rekapMapel?->predikat_rapor)
                                                <span class="badge {{ $rekapMapel->tuntas_kkm === false ? 'badge-danger' : 'badge-success' }}">
                                                    {{ $rekapMapel->predikat_rapor }}
                                                </span>
                                                @if ($rekapMapel->keterangan_predikat)
                                                    <div style="font-size: 0.68rem;" class="text-muted mt-1">{{ $rekapMapel->keterangan_predikat }}</div>
                                                @endif
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($rekapMapel?->tuntas_kkm === null)
                                                <span class="badge badge-light">-</span>
                                            @elseif ($rekapMapel->tuntas_kkm)
                                                <span class="badge badge-success">Tuntas</span>
                                            @else
                                                <span class="badge badge-danger">Belum Tuntas</span>
                                            @endif
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-start justify-content-between gap-2">
                                                <div class="small" style="color: #334155;">
                                                    {{ $rekapMapel?->deskripsi_capaian ?: 'Belum ada deskripsi.' }}
                                                </div>
                                                @if ($rekapMapel !== null)
                                                    <button type="button"
                                                        class="btn btn-sm btn-light btn-edit-deskripsi"
                                                        data-action="{{ route('penilaian-rekap.deskripsi.update', $rekapMapel->id) }}"
                                                        data-student="{{ $student->siswa?->nama_lengkap ?? $student->siswa_nis }}"
                                                        data-deskripsi="{{ $rekapMapel->deskripsi_capaian }}"
                                                        title="Sunting Deskripsi">
                                                        <i class="fa fa-edit text-warning"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div id="editDeskripsiModal" class="rk-modal-overlay" role="dialog" aria-modal="true">
        <div class="rk-modal-card">
            <div class="d-flex align-items-center justify-content-between px-4 py-3" style="border-bottom: 1px solid #e2e8f0;">
                <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">
                    Edit Deskripsi Rapor — <span id="descModalStudent">Siswa</span>
                </h5>
                <button type="button" class="btn btn-sm btn-light" id="closeDescModal">&times;</button>
            </div>
            <form method="POST" id="editDeskripsiForm">
                @csrf
                @method('PUT')
                <input type="hidden" name="kelas_id" value="{{ $selectedKelas?->id ?? '' }}">
                <div class="p-4">
                    <div class="form-group mb-0">
                        <label for="descModalTextarea" class="small font-weight-bold">Deskripsi Capaian Kompetensi</label>
                        <textarea name="deskripsi_capaian" id="descModalTextarea" rows="4" maxlength="2000" class="form-control"></textarea>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-light btn-sm" id="cancelDescModal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white" style="background: #ea580c;">Simpan Deskripsi</button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const modal = document.getElementById('editDeskripsiModal');
            const form = document.getElementById('editDeskripsiForm');
            const studentSpan = document.getElementById('descModalStudent');
            const textarea = document.getElementById('descModalTextarea');

            document.querySelectorAll('.btn-edit-deskripsi').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    form.action = btn.dataset.action;
                    studentSpan.textContent = btn.dataset.student;
                    textarea.value = btn.dataset.deskripsi || '';
                    modal.classList.add('is-open');
                });
            });

            ['closeDescModal', 'cancelDescModal'].forEach(function (id) {
                const el = document.getElementById(id);
                if (el) el.addEventListener('click', function () { modal.classList.remove('is-open'); });
            });
        });
    </script>
@endpush

