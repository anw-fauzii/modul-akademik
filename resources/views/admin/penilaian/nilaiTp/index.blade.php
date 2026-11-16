@extends('layouts.appui')

@section('title')
    <title>Penilaian Tujuan Pembelajaran (SD)</title>
@endsection

@section('content')
    @push('css')
        <style>
            .np-filter-bar {
                background: #ffffff;
                border: 1px solid #e2e8f0;
                border-radius: 16px;
                padding: 0.9rem 1.15rem;
                margin-bottom: 1rem;
                box-shadow: 0 1px 2px rgba(15, 23, 42, 0.03);
            }

            .np-filter-grid {
                display: grid;
                grid-template-columns: minmax(0, 1fr);
                gap: 0.75rem;
                align-items: end;
            }

            @media (min-width: 768px) {
                .np-filter-grid {
                    grid-template-columns: 1.2fr 1fr 1.2fr auto;
                }
            }

            .np-periode-tabs {
                display: inline-flex;
                align-items: center;
                padding: 0.25rem;
                background: #f1f5f9;
                border: 1px solid #e2e8f0;
                border-radius: 12px;
                gap: 0.25rem;
            }

            .np-periode-tab {
                padding: 0.42rem 0.9rem;
                border-radius: 9px;
                font-size: 0.8rem;
                font-weight: 700;
                color: #475569;
                text-decoration: none !important;
                transition: all 0.15s ease;
            }

            .np-periode-tab:hover {
                color: #0f172a;
                background: rgba(255, 255, 255, 0.7);
            }

            .np-periode-tab.is-active {
                background: #ffffff;
                color: #c2410c;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.1);
            }

            .np-info-strip {
                display: flex;
                flex-wrap: wrap;
                align-items: center;
                justify-content: space-between;
                gap: 0.75rem;
                padding: 0.7rem 1rem;
                border-radius: 12px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                margin-bottom: 0.9rem;
                font-size: 0.78rem;
            }

            .np-tp-controls {
                display: flex;
                flex-wrap: wrap;
                gap: 0.5rem;
                margin-bottom: 1rem;
            }

            .np-tp-chip-card {
                display: inline-flex;
                align-items: center;
                gap: 0.45rem;
                padding: 0.35rem 0.65rem;
                border-radius: 10px;
                background: #ffffff;
                border: 1px solid #e2e8f0;
                font-size: 0.75rem;
            }

            .np-tp-chip-card.is-excluded {
                background: #f8fafc;
                border-style: dashed;
                opacity: 0.78;
            }

            .np-matrix-wrap {
                background: #ffffff;
                border: 1px solid #cbd5e1;
                border-radius: 14px;
                overflow: hidden;
                box-shadow: 0 1px 3px rgba(15, 23, 42, 0.04);
            }

            .np-matrix-scroll {
                overflow-x: auto;
                max-width: 100%;
            }

            .np-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
                font-size: 0.78rem;
                font-variant-numeric: tabular-nums;
            }

            .np-table th,
            .np-table td {
                border-right: 1px solid #cbd5e1;
                border-bottom: 1px solid #cbd5e1;
                padding: 0.42rem 0.5rem;
                vertical-align: middle;
            }

            .np-th-identity {
                background: #e2e8f0;
                color: #0f172a;
                font-weight: 700;
                text-align: center;
            }

            .np-th-tp {
                background: #bfdbfe;
                color: #0f172a;
                font-weight: 700;
                text-align: center;
                white-space: nowrap;
            }

            .np-th-tp.is-excluded {
                background: #e2e8f0;
                color: #64748b;
            }

            .np-th-aspek {
                background: #fef08a;
                color: #0f172a;
                font-weight: 700;
                text-align: center;
                min-width: 84px;
                position: relative;
            }

            .np-th-natp {
                background: #b91c1c;
                color: #ffffff;
                font-weight: 700;
                text-align: center;
                min-width: 68px;
                white-space: nowrap;
            }

            .np-th-ujian {
                background: #4338ca;
                color: #ffffff;
                font-weight: 700;
                text-align: center;
                min-width: 92px;
                white-space: nowrap;
            }

            .np-th-rapor {
                background: #ea580c;
                color: #ffffff;
                font-weight: 700;
                text-align: center;
                min-width: 78px;
            }

            .np-ujian-cell {
                background: #eef2ff;
                text-align: center;
            }

            .np-sticky-col-1 {
                position: sticky;
                left: 0;
                z-index: 3;
                background: #ffffff;
                min-width: 42px;
                width: 42px;
                text-align: center;
            }

            .np-sticky-col-2 {
                position: sticky;
                left: 42px;
                z-index: 3;
                background: #ffffff;
                min-width: 210px;
                max-width: 240px;
                box-shadow: 2px 0 4px rgba(15, 23, 42, 0.05);
            }

            thead .np-sticky-col-1,
            thead .np-sticky-col-2 {
                z-index: 5;
                background: #e2e8f0;
            }

            .np-score-input {
                width: 66px;
                height: 32px;
                padding: 0 0.35rem;
                text-align: center;
                font-size: 0.8rem;
                font-weight: 600;
                color: #0f172a;
                border: 1px solid #cbd5e1;
                border-radius: 7px;
                background: #ffffff;
            }

            .np-score-input:focus {
                border-color: #ea580c;
                outline: 0;
                box-shadow: 0 0 0 2px rgba(234, 88, 12, 0.2);
            }

            .np-score-input.is-below-kkm {
                background: #fef2f2;
                border-color: #fca5a5;
                color: #b91c1c;
            }

            .np-natp-cell {
                background: #eff6ff;
                text-align: center;
                font-weight: 800;
                color: #1e293b;
            }

            .np-natp-cell.is-below-kkm {
                color: #b91c1c;
                background: #fef2f2;
            }

            .np-mid-carry {
                display: block;
                font-size: 0.64rem;
                font-weight: 600;
                color: #64748b;
            }

            .np-aspek-del {
                border: none;
                background: transparent;
                color: #92400e;
                opacity: 0.65;
                cursor: pointer;
                padding: 0 0.2rem;
                font-size: 0.7rem;
            }

            .np-aspek-del:hover {
                opacity: 1;
                color: #dc2626;
            }

            .np-modal-overlay {
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

            .np-modal-overlay.is-open {
                display: flex;
            }

            .np-modal-card {
                width: 100%;
                max-width: 480px;
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
        $kkmNilai = $kkmNilai ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_KKM;
        $bobotTp = (int) ($bobotTp ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_TP);
        $bobotUjian = (int) ($bobotUjian ?? \App\Models\Admin\Penilaian\Kkm::DEFAULT_BOBOT_UJIAN);
        $ujianLabel = $ujianLabel ?? \App\Models\Admin\Penilaian\PeriodeTp::ujianLabel($periode);
        $ujianFullLabel = \App\Models\Admin\Penilaian\PeriodeTp::ujianFullLabel($periode);
        $kkmPredikats = $kkmPredikats ?? collect();
        $hasKkmConfig = $hasKkmConfig ?? false;
        $tpList = $tpList ?? collect();
        $students = $students ?? collect();
        $aspeksByTp = $aspeksByTp ?? collect();
        $periodeSettingsByTp = $periodeSettingsByTp ?? collect();
        $nilaiLookup = $nilaiLookup ?? [];
        $rekapTpLookup = $rekapTpLookup ?? [];
        $rekapMapelLookup = $rekapMapelLookup ?? [];
        $teknikOptions = $teknikOptions ?? \App\Models\Admin\Penilaian\AspekTp::TEKNIK_OPTIONS;

        $contextParams = array_filter([
            'tahun_ajaran_id' => $selectedTahunAjaranId ?? null,
            'kelas_id' => $selectedKelas?->id ?? null,
            'mata_pelajaran_id' => $selectedMapel?->id ?? null,
            'periode' => $periode,
        ], static fn ($v) => $v !== null && $v !== '');

        $predikatsForJs = $kkmPredikats->map(static fn ($p): array => [
            'predikat' => $p->predikat,
            'nilai_min' => (float) $p->nilai_min,
            'nilai_max' => (float) $p->nilai_max,
            'is_tuntas' => (bool) $p->is_tuntas,
        ])->values()->all();
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-note2" style="color: white;"></i>
                    </div>
                    <div>
                        Penilaian Tujuan Pembelajaran (SD)
                        <div class="page-title-subheading">
                            Input nilai aspek per TP jenjang SD. Periode Semester menggabungkan seluruh NA TP Mid &amp; Semester secara otomatis.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions flex-wrap">
                    @if ($selectedKelas && $selectedMapel && $tpList->isNotEmpty())
                        <a href="{{ route('penilaian-rekap.index', $contextParams) }}" class="btn btn-light shadow-sm"
                            style="border-radius: 10px; font-weight: 600;">
                            <i class="fa fa-list-alt mr-1"></i> Rekap &amp; Deskripsi
                        </a>
                        <a href="{{ route('penilaian-tp.export', $contextParams) }}" class="btn btn-light shadow-sm"
                            style="border-radius: 10px; font-weight: 600; color: #0f766e; border-color: #99f6e4; background: #f0fdfa;">
                            <i class="fa fa-file-excel mr-1"></i> Export Excel
                        </a>
                        <button type="button" id="btnOpenImportNilai" class="btn btn-light shadow-sm"
                            style="border-radius: 10px; font-weight: 600; color: #1d4ed8; border-color: #bfdbfe; background: #eff6ff;">
                            <i class="fa fa-upload mr-1"></i> Import Excel
                        </button>
                    @endif
                </div>
            </div>
        </div>

        @if (session('import_errors'))
            <div class="alert alert-danger mb-3" style="border-radius: 12px;">
                <strong>Detail Error Import:</strong>
                <ul class="mb-0 mt-1 pl-3 small">
                    @foreach (session('import_errors') as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Filter Konteks + Tab Periode Mid / Semester -->
        <div class="np-filter-bar">
            <form method="GET" action="{{ route('penilaian-tp.index') }}" id="matrixFilterForm">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <div class="np-filter-grid">
                    <div>
                        <label for="np_ta" class="small font-weight-semibold text-muted mb-1">Tahun Ajaran / Semester</label>
                        <select name="tahun_ajaran_id" id="np_ta" class="form-control form-control-sm" onchange="this.form.submit()">
                            @foreach ($tahunAjaranOptions as $ta)
                                <option value="{{ $ta->id }}" @selected((int) $selectedTahunAjaranId === (int) $ta->id)>
                                    {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="np_kelas" class="small font-weight-semibold text-muted mb-1">Kelas (SD)</label>
                        <select name="kelas_id" id="np_kelas" class="form-control form-control-sm" onchange="this.form.submit()">
                            @forelse ($kelasOptions as $kls)
                                <option value="{{ $kls->id }}" @selected($selectedKelas && (int) $selectedKelas->id === (int) $kls->id)>
                                    {{ $kls->nama_kelas }} (Tingkat {{ $kls->tingkatan_kelas }})
                                </option>
                            @empty
                                <option value="">Belum ada kelas SD di TA ini</option>
                            @endforelse
                        </select>
                    </div>

                    <div>
                        <label for="np_mapel" class="small font-weight-semibold text-muted mb-1">Mata Pelajaran (SD)</label>
                        <select name="mata_pelajaran_id" id="np_mapel" class="form-control form-control-sm" onchange="this.form.submit()">
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
                        <div class="small font-weight-semibold text-muted mb-1">Periode Penilaian</div>
                        <div class="np-periode-tabs">
                            <a href="{{ route('penilaian-tp.index', array_merge($contextParams, ['periode' => 'mid'])) }}"
                                class="np-periode-tab {{ $isMid ? 'is-active' : '' }}">
                                Mid Semester
                            </a>
                            <a href="{{ route('penilaian-tp.index', array_merge($contextParams, ['periode' => 'semester'])) }}"
                                class="np-periode-tab {{ ! $isMid ? 'is-active' : '' }}">
                                Semester
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        @if ($selectedKelas && $selectedMapel)
            <!-- Banner KKM & Info Aturan Rapor -->
            <div class="np-info-strip">
                <div class="d-flex flex-wrap align-items-center gap-2">
                    <span class="badge" style="background: #fff7ed; color: #c2410c; border: 1px solid #fed7aa; padding: 0.35rem 0.65rem;">
                        KKM Tingkat {{ $selectedKelas->tingkatan_kelas }}:
                        <strong>{{ rtrim(rtrim(number_format((float) $kkmNilai, 2, '.', ''), '0'), '.') }}</strong>
                    </span>

                    @foreach ($kkmPredikats as $pred)
                        <span class="badge {{ $pred->is_tuntas ? 'badge-light' : 'badge-danger' }}"
                            style="font-size: 0.7rem;">
                            {{ $pred->predikat }}:
                            {{ rtrim(rtrim(number_format((float) $pred->nilai_min, 2, '.', ''), '0'), '.') }}–{{ rtrim(rtrim(number_format((float) $pred->nilai_max, 2, '.', ''), '0'), '.') }}
                        </span>
                    @endforeach

                    @if (! $hasKkmConfig)
                        <a href="{{ route('penilaian-kkm.create', ['tahun_ajaran_id' => $selectedTahunAjaranId, 'mata_pelajaran_id' => $selectedMapel->id, 'tingkatan_kelas' => $selectedKelas->tingkatan_kelas]) }}"
                            class="small font-weight-semibold" style="color: #ea580c;">
                            (Default 75 — Atur KKM &amp; Rentang Kustom &rarr;)
                        </a>
                    @else
                        <a href="{{ route('penilaian-kkm.edit', $kkmModel->id) }}" class="small text-muted">
                            <i class="fa fa-cog"></i> Ubah KKM
                        </a>
                    @endif

                    <span class="badge" style="background: #eef2ff; color: #3730a3; border: 1px solid #c7d2fe; padding: 0.35rem 0.65rem;">
                        Bobot Rapor: <strong>{{ $bobotTp }}% TP + {{ $bobotUjian }}% {{ $ujianLabel }}</strong>
                    </span>
                    <button type="button" id="btnOpenBobotModal" class="btn btn-sm py-0 px-2"
                        style="background: #4338ca; color: #ffffff; border-radius: 999px; font-size: 0.7rem; font-weight: 600;">
                        <i class="fa fa-sliders-h mr-1"></i> Atur Bobot Ujian
                    </button>
                </div>

                <div class="small text-muted">
                    @if ($bobotUjian > 0)
                        <i class="fa fa-calculator text-primary mr-1"></i>
                        <strong>NA Rapor</strong> = {{ $bobotTp }}% Rata-rata NA TP + {{ $bobotUjian }}% {{ $ujianLabel }}
                        <span class="text-muted">(bila {{ $ujianLabel }} kosong, otomatis 100% NA TP)</span>.
                    @elseif ($isMid)
                        <i class="fa fa-info-circle text-primary mr-1"></i>
                        Centang <strong>Masuk Rapor Mid</strong> untuk memilih TP yang masuk Rapor Mid. Klik <strong>Atur Bobot Ujian</strong> bila nilai {{ $ujianLabel }} dihitung ke rapor.
                    @else
                        <i class="fa fa-check-circle text-success mr-1"></i>
                        <strong>NA Rapor Semester</strong> = 100% rata-rata gabungan seluruh NA TP (Mid + Semester). Klik <strong>Atur Bobot Ujian</strong> bila nilai {{ $ujianLabel }} dihitung.
                    @endif
                </div>
            </div>
        @endif

        @if ($tpList->isEmpty())
            <div class="main-card card">
                <div class="card-body text-center py-5 text-muted">
                    <div class="mb-2"><i class="fa fa-clipboard-list fa-2x" style="color: #cbd5e1;"></i></div>
                    <div class="font-weight-bold text-dark mb-1">Belum ada TP aktif untuk kombinasi filter ini</div>
                    <div class="small mb-3">
                        Tambahkan Tujuan Pembelajaran terlebih dahulu di menu Daftar TP untuk Mapel &amp; Tingkatan Kelas yang dipilih.
                    </div>
                    <a href="{{ route('tujuan-pembelajaran.create') }}" class="btn btn-sm text-white"
                        style="background: #ea580c; border-radius: 8px;">
                        <i class="fa fa-plus mr-1"></i> Buat Tujuan Pembelajaran
                    </a>
                </div>
            </div>
        @elseif ($students->isEmpty())
            <div class="main-card card">
                <div class="card-body text-center py-5 text-muted">
                    <div class="font-weight-bold text-dark mb-1">Belum ada anggota siswa di {{ $selectedKelas->nama_kelas }}</div>
                    <div class="small">Silakan masukkan siswa ke kelas ini pada menu Anggota Kelas terlebih dahulu.</div>
                </div>
            </div>
        @else
            <!-- Strip Pemilihan Masuk Rapor Mid/Semester & Tambah Aspek Per Jenjang -->
            <div class="np-tp-controls">
                @foreach ($tpList as $tp)
                    @php
                        $setting = $periodeSettingsByTp->get($tp->id);
                        $isIncluded = $setting === null ? true : (bool) $setting->masuk_rapor;
                    @endphp
                    <div class="np-tp-chip-card {{ ! $isIncluded ? 'is-excluded' : '' }}" title="{{ $tp->tujuan_pembelajaran }}">
                        <span class="font-weight-bold" style="color: #0f172a;">{{ $tp->kode_tp }}</span>

                        <form method="POST" action="{{ route('penilaian-tp.toggle-masuk-rapor', $tp->id) }}" class="d-inline">
                            @csrf
                            <input type="hidden" name="periode" value="{{ $periode }}">
                            <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                            <input type="hidden" name="masuk_rapor" value="{{ $isIncluded ? '0' : '1' }}">
                            <button type="submit"
                                class="btn btn-sm py-0 px-2 {{ $isIncluded ? 'btn-success' : 'btn-outline-secondary' }}"
                                style="font-size: 0.7rem; border-radius: 999px;"
                                title="Klik untuk ubah status masuk Rapor {{ $isMid ? 'Mid' : 'Semester' }}">
                                <i class="fa {{ $isIncluded ? 'fa-check-square' : 'fa-square' }} mr-1"></i>
                                {{ $isIncluded ? 'Masuk Rapor' : 'Tidak Masuk Rapor' }}
                            </button>
                        </form>

                        <button type="button"
                            class="btn btn-sm btn-light py-0 px-2 btn-open-aspek-modal"
                            data-tp-id="{{ $tp->id }}"
                            data-tp-kode="{{ $tp->kode_tp }}"
                            style="font-size: 0.7rem; border-radius: 7px;"
                            title="Tambah kolom aspek (Pemahaman, Bacaan, dll.) seragam Kelas {{ $selectedKelas->tingkatan_kelas }}">
                            <i class="fa fa-plus text-warning"></i> Aspek
                        </button>
                    </div>
                @endforeach
            </div>

            <!-- Grid Nilai 2-Baris Header Ala Spreadsheet -->
            <form method="POST" action="{{ route('penilaian-tp.store') }}" id="nilaiGridForm">
                @csrf
                <input type="hidden" name="tahun_ajaran_id" value="{{ $selectedTahunAjaranId }}">
                <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedMapel->id }}">
                <input type="hidden" name="periode" value="{{ $periode }}">

                <div class="np-matrix-wrap">
                    <div class="np-matrix-scroll">
                        <table class="np-table" id="tpNilaiMatrixTable">
                            <thead>
                                <tr>
                                    <th rowspan="2" class="np-th-identity np-sticky-col-1">No</th>
                                    <th rowspan="2" class="np-th-identity np-sticky-col-2 text-left">Nama Siswa</th>
                                    @foreach ($tpList as $tp)
                                        @php
                                            $tpAspeks = $aspeksByTp->get($tp->id, collect());
                                            $colspan = $tpAspeks->count() + 1;
                                            $setting = $periodeSettingsByTp->get($tp->id);
                                            $isIncluded = $setting === null ? true : (bool) $setting->masuk_rapor;
                                        @endphp
                                        <th colspan="{{ $colspan }}"
                                            class="np-th-tp {{ ! $isIncluded ? 'is-excluded' : '' }}"
                                            title="{{ $tp->tujuan_pembelajaran }}">
                                            {{ $tp->kode_tp }}
                                            @if (! $isIncluded)
                                                <span class="badge badge-secondary ml-1" style="font-size: 0.62rem;">Non-Rapor</span>
                                            @endif
                                        </th>
                                    @endforeach
                                    <th rowspan="2" class="np-th-ujian" title="{{ $ujianFullLabel }}">
                                        {{ $ujianLabel }}
                                        <div style="font-size: 0.65rem; font-weight: 500; opacity: 0.9;">
                                            {{ $bobotUjian > 0 ? "Bobot {$bobotUjian}%" : 'Opsional (0%)' }}
                                        </div>
                                    </th>
                                    <th rowspan="2" class="np-th-rapor">NA Rapor</th>
                                    <th rowspan="2" class="np-th-rapor" style="background: #9a3412;">Predikat</th>
                                </tr>
                                <tr>
                                    @foreach ($tpList as $tp)
                                        @php
                                            $tpAspeks = $aspeksByTp->get($tp->id, collect());
                                        @endphp
                                        @foreach ($tpAspeks as $aspek)
                                            <th class="np-th-aspek" data-aspek-col="{{ $aspek->id }}">
                                                <div class="d-inline-flex align-items-center justify-content-center gap-1">
                                                    <span>{{ $aspek->nama_aspek }}</span>
                                                    @if ($tpAspeks->count() > 1)
                                                        <button type="button"
                                                            class="np-aspek-del btn-delete-aspek"
                                                            data-action="{{ route('penilaian-tp.aspek.destroy', $aspek->id) }}"
                                                            data-label="{{ $tp->kode_tp }} - {{ $aspek->nama_aspek }}"
                                                            title="Hapus aspek {{ $aspek->nama_aspek }}">
                                                            &times;
                                                        </button>
                                                    @endif
                                                </div>
                                            </th>
                                        @endforeach
                                        <th class="np-th-natp">NA TP</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($students->values() as $idx => $student)
                                    @php
                                        $midScoresByTp = [];
                                        foreach ($tpList as $tpItem) {
                                            $midRekap = $rekapTpLookup["{$student->id}:{$tpItem->id}:".\App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID] ?? null;
                                            if ($midRekap?->nilai_akhir_tp !== null) {
                                                $midScoresByTp[$tpItem->id] = (float) $midRekap->nilai_akhir_tp;
                                            }
                                        }
                                        $rekapMapel = $rekapMapelLookup["{$student->id}:{$periode}"] ?? null;
                                    @endphp
                                    <tr data-student-row="{{ $student->id }}"
                                        data-mid-scores='@json($midScoresByTp)'>
                                        <td class="np-sticky-col-1 text-muted">{{ $idx + 1 }}</td>
                                        <td class="np-sticky-col-2">
                                            <div class="font-weight-semibold text-dark" style="line-height: 1.25;">
                                                {{ $student->siswa?->nama_lengkap ?? $student->siswa_nis }}
                                            </div>
                                            <div class="text-muted" style="font-size: 0.68rem;">NIS: {{ $student->siswa_nis }}</div>
                                        </td>

                                        @foreach ($tpList as $tp)
                                            @php
                                                $tpAspeks = $aspeksByTp->get($tp->id, collect());
                                                $setting = $periodeSettingsByTp->get($tp->id);
                                                $isIncluded = $setting === null ? true : (bool) $setting->masuk_rapor;
                                                $rekapTp = $rekapTpLookup["{$student->id}:{$tp->id}:{$periode}"] ?? null;
                                                $naTpValue = $rekapTp?->nilai_akhir_tp;
                                            @endphp

                                            @foreach ($tpAspeks as $aspek)
                                                @php
                                                    $nilaiObj = $nilaiLookup["{$student->id}:{$aspek->id}"] ?? null;
                                                    $cellVal = $nilaiObj?->nilai;
                                                    $cellDisplay = $cellVal !== null
                                                        ? rtrim(rtrim(number_format((float) $cellVal, 2, '.', ''), '0'), '.')
                                                        : '';
                                                    $isBelow = $cellVal !== null && (float) $cellVal < (float) $kkmNilai;
                                                @endphp
                                                <td class="text-center">
                                                    <input type="number"
                                                        step="0.01"
                                                        min="0"
                                                        max="100"
                                                        name="nilai[{{ $student->id }}][{{ $aspek->id }}]"
                                                        value="{{ $cellDisplay }}"
                                                        data-student-id="{{ $student->id }}"
                                                        data-tp-id="{{ $tp->id }}"
                                                        data-aspek-id="{{ $aspek->id }}"
                                                        data-weight="{{ $aspek->bobot }}"
                                                        data-counts-na="{{ $aspek->masuk_hitung_na_tp ? '1' : '0' }}"
                                                        class="np-score-input {{ $isBelow ? 'is-below-kkm' : '' }}">
                                                </td>
                                            @endforeach

                                            <td class="np-natp-cell {{ ($naTpValue !== null && (float) $naTpValue < (float) $kkmNilai) ? 'is-below-kkm' : '' }}"
                                                data-natp-cell="{{ $student->id }}:{{ $tp->id }}"
                                                data-included-rapor="{{ $isIncluded ? '1' : '0' }}">
                                                <span class="js-natp-value">
                                                    {{ $naTpValue !== null ? (int) round((float) $naTpValue) : '-' }}
                                                </span>
                                                @if (! $isMid && isset($midScoresByTp[$tp->id]))
                                                    <span class="np-mid-carry">Mid: {{ (int) round($midScoresByTp[$tp->id]) }}</span>
                                                @endif
                                            </td>
                                        @endforeach

                                        @php
                                            $nilaiUjian = $rekapMapel?->nilai_ujian;
                                            $ujianDisplay = $nilaiUjian !== null
                                                ? rtrim(rtrim(number_format((float) $nilaiUjian, 2, '.', ''), '0'), '.')
                                                : '';
                                            $isUjianBelow = $nilaiUjian !== null && (float) $nilaiUjian < (float) $kkmNilai;
                                            $naRaporVal = $rekapMapel?->nilai_akhir_rapor;
                                            $predRapor = $rekapMapel?->predikat_rapor;
                                            $isTuntasRapor = $rekapMapel?->tuntas_kkm;
                                        @endphp
                                        <td class="np-ujian-cell">
                                            <input type="number"
                                                step="0.01"
                                                min="0"
                                                max="100"
                                                name="ujian[{{ $student->id }}]"
                                                value="{{ $ujianDisplay }}"
                                                data-student-id="{{ $student->id }}"
                                                data-aspek-id="ujian"
                                                placeholder="-"
                                                class="np-score-input np-ujian-input {{ $isUjianBelow ? 'is-below-kkm' : '' }}">
                                        </td>
                                        <td class="text-center font-weight-bold"
                                            style="background: #fff7ed; color: #9a3412;"
                                            data-narapor-cell="{{ $student->id }}">
                                            {{ $naRaporVal !== null ? (int) round((float) $naRaporVal) : '-' }}
                                        </td>
                                        <td class="text-center" data-predikat-cell="{{ $student->id }}">
                                            @if ($predRapor !== null)
                                                <span class="badge {{ $isTuntasRapor === false ? 'badge-danger' : 'badge-success' }}">
                                                    {{ $predRapor }}
                                                </span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-wrap align-items-center justify-content-between gap-2 px-3 py-3"
                        style="background: #f8fafc; border-top: 1px solid #cbd5e1;">
                        <div class="small text-muted">
                            Menampilkan <strong>{{ $students->count() }}</strong> siswa dan <strong>{{ $tpList->count() }}</strong> TP.
                            Gunakan tombol <strong>Enter</strong> / <strong>Panah</strong> untuk berpindah sel cepat.
                        </div>
                        <button type="submit" class="btn text-white shadow-sm"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px; font-weight: 600; padding: 0.55rem 1.4rem;">
                            <i class="fa fa-save mr-1"></i> Simpan Semua Nilai
                        </button>
                    </div>
                </div>
            </form>
        @endif
    </div>

    <!-- Form Terpisah Hapus Aspek -->
    <form method="POST" id="deleteAspekForm" class="d-none">
        @csrf
        @method('DELETE')
        <input type="hidden" name="kelas_id" value="{{ $selectedKelas?->id ?? '' }}">
    </form>

    <!-- Modal Tambah Aspek TP (Seragam Per Jenjang) -->
    <div id="tpAspekModal" class="np-modal-overlay" role="dialog" aria-modal="true">
        <div class="np-modal-card">
            <div class="d-flex align-items-center justify-content-between px-4 py-3"
                style="border-bottom: 1px solid #e2e8f0;">
                <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">
                    Tambah Aspek Penilaian (<span id="aspekModalTpLabel">TP</span>)
                </h5>
                <button type="button" class="btn btn-sm btn-light" data-close-modal="tpAspekModal">&times;</button>
            </div>
            <form method="POST" action="{{ route('penilaian-tp.aspek.store') }}">
                @csrf
                <input type="hidden" name="tujuan_pembelajaran_id" id="aspekModalTpId">
                <input type="hidden" name="periode" value="{{ $periode }}">
                <input type="hidden" name="kelas_id" value="{{ $selectedKelas?->id ?? '' }}">
                <div class="p-4">
                    <p class="small text-muted mb-3">
                        Aspek yang ditambahkan otomatis seragam untuk seluruh kelas paralel jenjang <strong>Kelas {{ $selectedKelas?->tingkatan_kelas }} SD</strong>.
                    </p>
                    <div class="form-group">
                        <label for="nama_aspek" class="small font-weight-bold">Nama Aspek <span class="text-danger">*</span></label>
                        <input type="text" name="nama_aspek" id="nama_aspek" maxlength="100" required
                            class="form-control" placeholder="Contoh: Pemahaman / Bacaan / Makhroj / Hafalan">
                    </div>
                    <div class="form-group">
                        <label for="teknik_penilaian" class="small font-weight-bold">Teknik Penilaian (opsional)</label>
                        <select name="teknik_penilaian" id="teknik_penilaian" class="form-control">
                            <option value="">-- Umum / Opsional --</option>
                            @foreach ($teknikOptions as $techCode => $techLabel)
                                <option value="{{ $techCode }}">{{ $techLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="d-flex justify-content-end gap-2 px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                    <button type="button" class="btn btn-light btn-sm" data-close-modal="tpAspekModal">Batal</button>
                    <button type="submit" class="btn btn-sm text-white" style="background: #ea580c;">Simpan Aspek</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Atur Bobot Nilai Ujian (STS / SAS) -->
    @if ($selectedKelas && $selectedMapel)
        <div id="tpBobotModal" class="np-modal-overlay" role="dialog" aria-modal="true">
            <div class="np-modal-card">
                <div class="d-flex align-items-center justify-content-between px-4 py-3"
                    style="border-bottom: 1px solid #e2e8f0;">
                    <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">
                        Atur Bobot Nilai Ujian ({{ $ujianLabel }}) — Kelas {{ $selectedKelas->tingkatan_kelas }} SD
                    </h5>
                    <button type="button" class="btn btn-sm btn-light" data-close-modal="tpBobotModal">&times;</button>
                </div>
                <form method="POST" action="{{ route('penilaian-tp.bobot.update') }}">
                    @csrf
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $selectedTahunAjaranId }}">
                    <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                    <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedMapel->id }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <div class="p-4">
                        <p class="small text-muted mb-3">
                            Bobot berlaku seragam untuk seluruh kelas paralel <strong>Tingkat {{ $selectedKelas->tingkatan_kelas }}</strong> mapel <strong>{{ $selectedMapel->nama_mata_pelajaran }}</strong>. Pada periode Semester, hanya nilai <strong>SAS Semester</strong> yang dihitung (NA TP tetap gabungan Mid + Semester).
                        </p>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <button type="button" class="btn btn-sm btn-light js-modal-bobot-preset" data-tp="100" data-ujian="0"
                                style="border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600;">
                                100% NA TP (Tanpa Ujian)
                            </button>
                            <button type="button" class="btn btn-sm btn-light js-modal-bobot-preset" data-tp="70" data-ujian="30"
                                style="border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600;">
                                70% NA TP + 30% {{ $ujianLabel }}
                            </button>
                            <button type="button" class="btn btn-sm btn-light js-modal-bobot-preset" data-tp="60" data-ujian="40"
                                style="border: 1px solid #cbd5e1; border-radius: 8px; font-weight: 600;">
                                60% NA TP + 40% {{ $ujianLabel }}
                            </button>
                        </div>
                        <div class="row">
                            <div class="col-6">
                                <label for="modalBobotTp" class="small font-weight-bold">Bobot NA TP (%)</label>
                                <input type="number" min="0" max="100" step="1" name="bobot_tp" id="modalBobotTp"
                                    value="{{ $bobotTp }}" required class="form-control">
                            </div>
                            <div class="col-6">
                                <label for="modalBobotUjian" class="small font-weight-bold">Bobot {{ $ujianLabel }} (%)</label>
                                <input type="number" min="0" max="100" step="1" name="bobot_ujian" id="modalBobotUjian"
                                    value="{{ $bobotUjian }}" required class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                        <button type="button" class="btn btn-light btn-sm" data-close-modal="tpBobotModal">Batal</button>
                        <button type="submit" class="btn btn-sm text-white" style="background: #4338ca;">Simpan &amp; Hitung Ulang</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    <!-- Modal Import Excel Nilai TP -->
    @if ($selectedKelas && $selectedMapel)
        <div id="tpImportNilaiModal" class="np-modal-overlay" role="dialog" aria-modal="true">
            <div class="np-modal-card">
                <div class="d-flex align-items-center justify-content-between px-4 py-3"
                    style="border-bottom: 1px solid #e2e8f0;">
                    <h5 class="mb-0 font-weight-bold" style="font-size: 0.95rem;">
                        Import Nilai TP dari Excel
                    </h5>
                    <button type="button" class="btn btn-sm btn-light" data-close-modal="tpImportNilaiModal">&times;</button>
                </div>
                <form method="POST" action="{{ route('penilaian-tp.import') }}" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="tahun_ajaran_id" value="{{ $selectedTahunAjaranId }}">
                    <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                    <input type="hidden" name="mata_pelajaran_id" value="{{ $selectedMapel->id }}">
                    <input type="hidden" name="periode" value="{{ $periode }}">
                    <div class="p-4">
                        <p class="small text-muted mb-3">
                            Gunakan file hasil tombol <strong>Export Excel</strong> yang sudah memiliki format 2 baris header (Kode TP di baris 1, Nama Aspek + kolom {{ $ujianLabel }} di baris 2).
                        </p>
                        <div class="form-group mb-0">
                            <label for="nilai_excel_file" class="small font-weight-bold">Pilih File (.xlsx, .xls, .csv) <span class="text-danger">*</span></label>
                            <input type="file" name="file" id="nilai_excel_file" accept=".xlsx,.xls,.csv" required class="form-control">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2 px-4 py-3" style="background: #f8fafc; border-top: 1px solid #e2e8f0;">
                        <button type="button" class="btn btn-light btn-sm" data-close-modal="tpImportNilaiModal">Batal</button>
                        <button type="submit" class="btn btn-sm text-white" style="background: #ea580c;">Import &amp; Hitung Ulang</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
@endsection

@push('js')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const kkmNilai = {{ json_encode((float) $kkmNilai) }};
            const bobotTp = {{ (int) $bobotTp }};
            const bobotUjian = {{ (int) $bobotUjian }};
            const predikats = @json($predikatsForJs);
            const isMid = {{ $isMid ? 'true' : 'false' }};
            const table = document.getElementById('tpNilaiMatrixTable');

            const openModal = function (id) {
                const modal = document.getElementById(id);
                if (modal) modal.classList.add('is-open');
            };
            const closeModal = function (id) {
                const modal = document.getElementById(id);
                if (modal) modal.classList.remove('is-open');
            };

            document.querySelectorAll('[data-close-modal]').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    closeModal(btn.dataset.closeModal);
                });
            });

            document.querySelectorAll('.np-modal-overlay').forEach(function (overlay) {
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) {
                        overlay.classList.remove('is-open');
                    }
                });
            });

            document.querySelectorAll('.btn-open-aspek-modal').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    document.getElementById('aspekModalTpId').value = btn.dataset.tpId;
                    document.getElementById('aspekModalTpLabel').textContent = btn.dataset.tpKode;
                    openModal('tpAspekModal');
                });
            });

            const importBtn = document.getElementById('btnOpenImportNilai');
            if (importBtn) {
                importBtn.addEventListener('click', function () {
                    openModal('tpImportNilaiModal');
                });
            }

            const bobotBtn = document.getElementById('btnOpenBobotModal');
            if (bobotBtn) {
                bobotBtn.addEventListener('click', function () {
                    openModal('tpBobotModal');
                });
            }

            const modalBobotTp = document.getElementById('modalBobotTp');
            const modalBobotUjian = document.getElementById('modalBobotUjian');
            if (modalBobotTp && modalBobotUjian) {
                modalBobotTp.addEventListener('input', function () {
                    const tp = Math.max(0, Math.min(100, parseInt(modalBobotTp.value || '0', 10)));
                    modalBobotUjian.value = 100 - tp;
                });
                modalBobotUjian.addEventListener('input', function () {
                    const u = Math.max(0, Math.min(100, parseInt(modalBobotUjian.value || '0', 10)));
                    modalBobotTp.value = 100 - u;
                });
                document.querySelectorAll('.js-modal-bobot-preset').forEach(function (btn) {
                    btn.addEventListener('click', function () {
                        modalBobotTp.value = btn.dataset.tp;
                        modalBobotUjian.value = btn.dataset.ujian;
                    });
                });
            }

            const deleteForm = document.getElementById('deleteAspekForm');
            document.querySelectorAll('.btn-delete-aspek').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    if (!deleteForm) return;
                    if (confirm('Hapus aspek "' + btn.dataset.label + '" beserta nilai aspeknya? NA TP akan dihitung ulang otomatis.')) {
                        deleteForm.action = btn.dataset.action;
                        deleteForm.submit();
                    }
                });
            });

            if (!table) {
                return;
            }

            const resolvePredikat = function (score) {
                const rounded = Math.round(score);
                for (let i = 0; i < predikats.length; i++) {
                    if (rounded >= predikats[i].nilai_min && rounded <= predikats[i].nilai_max) {
                        return predikats[i];
                    }
                }
                return null;
            };

            const recalculateRow = function (tr) {
                const studentId = tr.dataset.studentRow;
                let midScores = {};
                try {
                    midScores = JSON.parse(tr.dataset.midScores || '{}');
                } catch (err) {
                    midScores = {};
                }

                const naCells = tr.querySelectorAll('[data-natp-cell]');
                const includedScores = [];

                naCells.forEach(function (naCell) {
                    const parts = naCell.dataset.natpCell.split(':');
                    const tpId = parts[1];
                    const isIncluded = naCell.dataset.includedRapor === '1';

                    const inputs = tr.querySelectorAll('input.np-score-input[data-tp-id="' + tpId + '"]');
                    let sum = 0;
                    let weightSum = 0;
                    let count = 0;

                    inputs.forEach(function (inp) {
                        const raw = inp.value.trim();
                        if (raw === '' || isNaN(Number(raw))) {
                            inp.classList.remove('is-below-kkm');
                            return;
                        }
                        const num = Math.max(0, Math.min(100, Number(raw)));
                        inp.classList.toggle('is-below-kkm', num < kkmNilai);

                        if (inp.dataset.countsNa === '1') {
                            const w = Number(inp.dataset.weight || 100);
                            sum += num * w;
                            weightSum += w;
                            count++;
                        }
                    });

                    const span = naCell.querySelector('.js-natp-value');
                    let currentTpScore = null;

                    if (count > 0 && weightSum > 0) {
                        currentTpScore = Math.round(sum / weightSum);
                        if (span) span.textContent = currentTpScore;
                        naCell.classList.toggle('is-below-kkm', currentTpScore < kkmNilai);
                    } else {
                        if (span) span.textContent = '-';
                        naCell.classList.remove('is-below-kkm');
                    }

                    if (isMid) {
                        if (isIncluded && currentTpScore !== null) {
                            includedScores.push(currentTpScore);
                        }
                    } else {
                        const effectiveScore = currentTpScore !== null
                            ? currentTpScore
                            : (midScores[tpId] !== undefined ? Math.round(Number(midScores[tpId])) : null);
                        if (isIncluded && effectiveScore !== null) {
                            includedScores.push(effectiveScore);
                        }
                    }
                });

                const avgTp = includedScores.length > 0
                    ? Math.round(includedScores.reduce((a, b) => a + b, 0) / includedScores.length)
                    : null;

                let ujianScore = null;
                const ujianInput = tr.querySelector('input.np-ujian-input');
                if (ujianInput) {
                    const rawUjian = ujianInput.value.trim();
                    if (rawUjian !== '' && !isNaN(Number(rawUjian))) {
                        ujianScore = Math.max(0, Math.min(100, Number(rawUjian)));
                        ujianInput.classList.toggle('is-below-kkm', ujianScore < kkmNilai);
                    } else {
                        ujianInput.classList.remove('is-below-kkm');
                    }
                }

                const naRaporCell = tr.querySelector('[data-narapor-cell="' + studentId + '"]');
                const predCell = tr.querySelector('[data-predikat-cell="' + studentId + '"]');

                let avgRapor = null;
                if (avgTp !== null || ujianScore !== null) {
                    if (bobotUjian <= 0 || ujianScore === null) {
                        avgRapor = avgTp;
                    } else if (bobotTp <= 0 || avgTp === null) {
                        avgRapor = Math.round(ujianScore);
                    } else {
                        avgRapor = Math.round(((avgTp * bobotTp) + (ujianScore * bobotUjian)) / (bobotTp + bobotUjian));
                    }
                }

                if (avgRapor === null) {
                    if (naRaporCell) naRaporCell.textContent = '-';
                    if (predCell) predCell.innerHTML = '<span class="text-muted">-</span>';
                    return;
                }

                if (naRaporCell) naRaporCell.textContent = avgRapor;

                const matched = resolvePredikat(avgRapor);
                if (predCell) {
                    if (matched) {
                        const cls = matched.is_tuntas ? 'badge-success' : 'badge-danger';
                        predCell.innerHTML = '<span class="badge ' + cls + '">' + matched.predikat + '</span>';
                    } else {
                        predCell.innerHTML = '<span class="text-muted">-</span>';
                    }
                }
            };

            table.addEventListener('input', function (e) {
                if (!e.target.classList.contains('np-score-input')) {
                    return;
                }
                const tr = e.target.closest('tr');
                if (tr) recalculateRow(tr);
            });

            // Navigasi Excel vertikal menggunakan Enter / ArrowUp / ArrowDown pada kolom aspek yang sama
            table.addEventListener('keydown', function (e) {
                if (!e.target.classList.contains('np-score-input')) {
                    return;
                }
                if (e.key !== 'Enter' && e.key !== 'ArrowDown' && e.key !== 'ArrowUp') {
                    return;
                }
                e.preventDefault();
                const aspekId = e.target.dataset.aspekId;
                const allCols = Array.from(table.querySelectorAll('input.np-score-input[data-aspek-id="' + aspekId + '"]'));
                const idx = allCols.indexOf(e.target);
                const nextIdx = e.key === 'ArrowUp' ? idx - 1 : idx + 1;
                if (nextIdx >= 0 && nextIdx < allCols.length) {
                    allCols[nextIdx].focus();
                    allCols[nextIdx].select();
                }
            });
        });
    </script>
@endpush

