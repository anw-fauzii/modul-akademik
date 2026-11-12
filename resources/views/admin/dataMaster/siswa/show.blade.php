@extends('layouts.appui')

@section('title')
    <title>Detail Siswa - {{ $siswa->nama_lengkap }}</title>
@endsection

@pushOnce('css')
    <style>
        .siswa-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 58%, #334155 100%);
            border-radius: 16px;
            padding: 1.35rem 1.6rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 12px 26px -6px rgba(15, 23, 42, 0.28);
        }

        .siswa-hero::after {
            content: '';
            position: absolute;
            top: -55px;
            right: -55px;
            width: 210px;
            height: 210px;
            border-radius: 9999px;
            background: radial-gradient(circle, rgba(249, 115, 22, 0.24) 0%, rgba(249, 115, 22, 0) 70%);
            pointer-events: none;
        }

        .siswa-hero-inner {
            position: relative;
            z-index: 1;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 1.25rem;
        }

        .siswa-hero-left {
            display: flex;
            align-items: center;
            gap: 1.1rem;
            min-width: 0;
            flex: 1 1 380px;
        }

        .siswa-avatar {
            width: 78px;
            height: 78px;
            border-radius: 18px;
            object-fit: cover;
            border: 3px solid rgba(255, 255, 255, 0.24);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.24);
            flex-shrink: 0;
        }

        .siswa-avatar-fallback {
            width: 78px;
            height: 78px;
            border-radius: 18px;
            background: linear-gradient(135deg, #ea580c 0%, #f59e0b 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            font-weight: 800;
            color: #ffffff;
            border: 3px solid rgba(255, 255, 255, 0.24);
            box-shadow: 0 8px 18px rgba(234, 88, 12, 0.35);
            flex-shrink: 0;
        }

        .siswa-hero-chips {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.45rem;
            margin-top: 0.45rem;
        }

        .siswa-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.24rem 0.6rem;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.15);
            font-size: 0.77rem;
            font-weight: 600;
            color: #e2e8f0;
            text-decoration: none;
        }

        a.siswa-chip:hover {
            background: rgba(249, 115, 22, 0.28);
            border-color: rgba(251, 146, 60, 0.55);
            color: #ffffff;
        }

        .siswa-hero-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 0.65rem;
            flex-shrink: 0;
        }

        .siswa-quick-links {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
            justify-content: flex-end;
        }

        .siswa-quick-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.36rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.76rem;
            font-weight: 700;
            text-decoration: none;
            transition: transform 0.15s ease, opacity 0.15s ease;
        }

        .siswa-quick-btn:hover {
            transform: translateY(-1px);
            opacity: 0.92;
        }

        .siswa-quick-wa {
            background-color: #16a34a;
            color: #ffffff;
        }

        .siswa-quick-mail {
            background-color: rgba(255, 255, 255, 0.14);
            border: 1px solid rgba(255, 255, 255, 0.24);
            color: #ffffff;
        }

        .siswa-kpi-strip {
            display: grid;
            grid-template-columns: repeat(4, minmax(68px, 1fr));
            gap: 0.5rem;
        }

        .siswa-kpi-box {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.13);
            border-radius: 10px;
            padding: 0.45rem 0.65rem;
            text-align: center;
        }

        .siswa-kpi-val {
            font-size: 0.9rem;
            font-weight: 800;
            color: #ffffff;
            line-height: 1.2;
        }

        .siswa-kpi-lbl {
            font-size: 0.66rem;
            color: #cbd5e1;
            font-weight: 600;
            margin-top: 0.12rem;
            white-space: nowrap;
        }

        /* Modern Segmented Tabs Card */
        .siswa-tabs-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 4px 16px -4px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }

        .siswa-tabs-header {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.875rem 1.25rem;
            background-color: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
        }

        .siswa-tablist {
            display: flex;
            flex-wrap: wrap;
            gap: 0.45rem;
        }

        .siswa-tab-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0.5rem 0.95rem;
            border-radius: 10px;
            border: 1px solid transparent;
            background-color: transparent;
            color: #475569;
            font-size: 0.84rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.15s ease;
        }

        .siswa-tab-btn:hover {
            background-color: #ffffff;
            color: #0f172a;
            border-color: #e2e8f0;
        }

        .siswa-tab-btn.is-active {
            background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);
            color: #ffffff;
            box-shadow: 0 4px 10px -2px rgba(234, 88, 12, 0.35);
        }

        .siswa-tabs-body {
            padding: 1.25rem;
        }

        .siswa-tab-panel {
            display: none;
        }

        .siswa-tab-panel.is-active {
            display: block;
        }

        .siswa-grid-2 {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 1.15rem;
        }

        .siswa-grid-3 {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 1rem;
        }

        .siswa-subcard {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem 1.15rem;
        }

        .siswa-subcard-title {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #ea580c;
            padding-bottom: 0.55rem;
            margin-bottom: 0.7rem;
            border-bottom: 1.5px solid #fed7aa;
        }

        .siswa-kv-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 0.65rem 1rem;
        }

        .siswa-kv-item.is-full {
            grid-column: 1 / -1;
        }

        .siswa-kv-label {
            font-size: 0.72rem;
            font-weight: 600;
            color: #64748b;
            margin-bottom: 0.12rem;
        }

        .siswa-kv-value {
            font-size: 0.86rem;
            font-weight: 600;
            color: #0f172a;
            word-break: break-word;
        }

        .siswa-metric-chips {
            display: grid;
            grid-template-columns: repeat(5, minmax(0, 1fr));
            gap: 0.5rem;
            margin-top: 0.85rem;
            padding-top: 0.75rem;
            border-top: 1px dashed #cbd5e1;
        }

        .siswa-metric-chip {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 9px;
            padding: 0.45rem 0.55rem;
            text-align: center;
        }

        .siswa-metric-chip-val {
            font-size: 0.84rem;
            font-weight: 700;
            color: #0f172a;
        }

        .siswa-metric-chip-lbl {
            font-size: 0.68rem;
            color: #64748b;
            font-weight: 600;
        }

        .siswa-family-card {
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            overflow: hidden;
        }

        .siswa-family-header {
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            border-bottom: 1px solid #e2e8f0;
        }

        .siswa-family-ayah .siswa-family-header {
            background-color: #eff6ff;
            color: #1d4ed8;
        }

        .siswa-family-ibu .siswa-family-header {
            background-color: #fdf2f8;
            color: #be185d;
        }

        .siswa-family-wali .siswa-family-header {
            background-color: #fff7ed;
            color: #c2410c;
        }

        .siswa-family-rows {
            padding: 0.35rem 1rem 0.6rem;
        }

        .siswa-family-row {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            gap: 0.75rem;
            padding: 0.46rem 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.82rem;
        }

        .siswa-family-row:last-child {
            border-bottom: none;
        }

        .siswa-family-row-label {
            color: #64748b;
            font-weight: 500;
            flex-shrink: 0;
        }

        .siswa-family-row-val {
            color: #0f172a;
            font-weight: 600;
            text-align: right;
            word-break: break-word;
        }

        @media (max-width: 991.98px) {
            .siswa-grid-2,
            .siswa-grid-3 {
                grid-template-columns: 1fr;
            }

            .siswa-hero-right {
                align-items: flex-start;
                width: 100%;
            }

            .siswa-quick-links {
                justify-content: flex-start;
            }

            .siswa-kpi-strip {
                width: 100%;
            }

            .siswa-metric-chips {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 575.98px) {
            .siswa-hero-left {
                flex-direction: column;
                align-items: flex-start;
            }

            .siswa-kv-grid {
                grid-template-columns: 1fr;
            }

            .siswa-kpi-strip {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
    </style>
@endPushOnce

@section('content')
    @php
        $keluargaGroups = [
            'ayah' => [
                'title' => 'Data Ayah',
                'icon' => 'fa-male',
                'modifier' => 'siswa-family-ayah',
                'jenjang' => $siswa->jenjangPendidikanAyah?->nama_jenjang_pendidikan,
                'pekerjaan' => $siswa->pekerjaanAyah?->nama_pekerjaan,
                'penghasilan' => $siswa->penghasilanAyah?->nama_penghasilan,
                'khusus' => $siswa->berkebutuhanKhususAyah?->nama_berkebutuhan_khusus,
            ],
            'ibu' => [
                'title' => 'Data Ibu',
                'icon' => 'fa-female',
                'modifier' => 'siswa-family-ibu',
                'jenjang' => $siswa->jenjangPendidikanIbu?->nama_jenjang_pendidikan,
                'pekerjaan' => $siswa->pekerjaanIbu?->nama_pekerjaan,
                'penghasilan' => $siswa->penghasilanIbu?->nama_penghasilan,
                'khusus' => $siswa->berkebutuhanKhususIbu?->nama_berkebutuhan_khusus,
            ],
            'wali' => [
                'title' => 'Data Wali',
                'icon' => 'fa-user-shield',
                'modifier' => 'siswa-family-wali',
                'jenjang' => $siswa->jenjangPendidikanWali?->nama_jenjang_pendidikan,
                'pekerjaan' => $siswa->pekerjaanWali?->nama_pekerjaan,
                'penghasilan' => $siswa->penghasilanWali?->nama_penghasilan,
                'khusus' => $siswa->berkebutuhanKhususWali?->nama_berkebutuhan_khusus,
            ],
        ];
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-study" style="color: white;"></i>
                    </div>
                    <div>
                        Detail Data Siswa
                        <div class="page-title-subheading">
                            Profil ringkas biodata, akademik, kontak, kesehatan, serta orang tua/wali siswa.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions d-flex align-items-center" style="gap: 0.5rem;">
                    <a href="{{ route('siswa.edit', $siswa->nis) }}" class="btn btn-primary shadow-sm" style="border-radius: 8px;">
                        <i class="fa fa-edit mr-1"></i> Edit Data
                    </a>
                    <a href="{{ route('siswa.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>

        <!-- COMPACT PROFILE HERO -->
        <div class="siswa-hero mb-4">
            <div class="siswa-hero-inner">
                <div class="siswa-hero-left">
                    @if ($siswa->avatar_url)
                        <img src="{{ $siswa->avatar_url }}" alt="{{ $siswa->nama_lengkap }}" class="siswa-avatar">
                    @else
                        <div class="siswa-avatar-fallback">
                            {{ $siswa->inisial_nama }}
                        </div>
                    @endif

                    <div style="min-width: 0;">
                        <div class="d-flex flex-wrap align-items-center mb-1" style="gap: 0.35rem;">
                            <span class="badge {{ $siswa->status_badge_class }} px-2 py-1 font-weight-bold">
                                {{ $siswa->status }}
                            </span>
                            <span class="badge badge-info px-2 py-1 font-weight-bold">
                                {{ $siswa->jenis_kelamin_label }}
                            </span>
                            @if ($siswa->jenis_pendaftaran)
                                <span class="badge badge-secondary px-2 py-1 font-weight-bold">
                                    {{ $siswa->jenis_pendaftaran }}
                                </span>
                            @endif
                        </div>

                        <h3 class="font-weight-bold text-white mb-0" style="font-size: 1.38rem; line-height: 1.25;">
                            {{ $siswa->nama_lengkap }}
                        </h3>

                        <div class="siswa-hero-chips">
                            <span class="siswa-chip">
                                <i class="fa fa-id-badge text-warning"></i> NIS: {{ $siswa->nis }}
                            </span>
                            @if ($siswa->nisn)
                                <span class="siswa-chip">
                                    <i class="fa fa-fingerprint text-info"></i> NISN: {{ $siswa->nisn }}
                                </span>
                            @endif
                            @if ($siswa->kelas)
                                @if ($siswa->kelas->id)
                                    <a href="{{ route('kelas.show', $siswa->kelas->id) }}" class="siswa-chip"
                                        title="Lihat detail kelas">
                                        <i class="fa fa-school text-success"></i>
                                        {{ $siswa->kelas->nama_kelas }} ({{ $siswa->kelas->jenjang_kelas }})
                                    </a>
                                @else
                                    <span class="siswa-chip">
                                        <i class="fa fa-school text-success"></i>
                                        {{ $siswa->kelas->nama_kelas }} ({{ $siswa->kelas->jenjang_kelas }})
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                </div>

                <div class="siswa-hero-right">
                    @if ($siswa->whatsapp || $siswa->email)
                        <div class="siswa-quick-links">
                            @if ($siswa->whatsapp)
                                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siswa->whatsapp) }}"
                                    target="_blank" rel="noopener" class="siswa-quick-btn siswa-quick-wa">
                                    <i class="fab fa-whatsapp"></i> {{ $siswa->whatsapp }}
                                </a>
                            @endif
                            @if ($siswa->email)
                                <a href="mailto:{{ $siswa->email }}" class="siswa-quick-btn siswa-quick-mail">
                                    <i class="fa fa-envelope"></i> Email
                                </a>
                            @endif
                        </div>
                    @endif

                    <div class="siswa-kpi-strip">
                        <div class="siswa-kpi-box">
                            <div class="siswa-kpi-val">{{ $siswa->tinggi_badan !== null ? $siswa->tinggi_badan . ' cm' : '-' }}</div>
                            <div class="siswa-kpi-lbl">Tinggi</div>
                        </div>
                        <div class="siswa-kpi-box">
                            <div class="siswa-kpi-val">{{ $siswa->berat_badan !== null ? $siswa->berat_badan . ' kg' : '-' }}</div>
                            <div class="siswa-kpi-lbl">Berat</div>
                        </div>
                        <div class="siswa-kpi-box">
                            <div class="siswa-kpi-val">{{ $siswa->jarak !== null ? $siswa->jarak . ' km' : '-' }}</div>
                            <div class="siswa-kpi-lbl">Jarak</div>
                        </div>
                        <div class="siswa-kpi-box">
                            <div class="siswa-kpi-val">{{ $siswa->waktu_tempuh !== null ? $siswa->waktu_tempuh . ' mnt' : '-' }}</div>
                            <div class="siswa-kpi-lbl">Tempuh</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABBED DETAIL CARD -->
        <div class="siswa-tabs-card mb-3">
            <div class="siswa-tabs-header">
                <div class="siswa-tablist" role="tablist" aria-label="Kategori detail siswa">
                    <button type="button" class="siswa-tab-btn is-active" role="tab"
                        aria-selected="true" aria-controls="panel-akademik" data-siswa-tab="akademik">
                        <i class="fa fa-graduation-cap"></i> Akademik &amp; Biodata
                    </button>
                    <button type="button" class="siswa-tab-btn" role="tab"
                        aria-selected="false" aria-controls="panel-kontak" data-siswa-tab="kontak">
                        <i class="fa fa-map-marker-alt"></i> Kontak &amp; Alamat
                    </button>
                    <button type="button" class="siswa-tab-btn" role="tab"
                        aria-selected="false" aria-controls="panel-keluarga" data-siswa-tab="keluarga">
                        <i class="fa fa-users"></i> Orang Tua &amp; Wali
                    </button>
                </div>

                @if ($siswa->karyawan)
                    <span class="small text-muted font-weight-600">
                        <i class="fa fa-user-tie text-warning mr-1"></i>
                        Wali/Pembimbing: <strong class="text-slate-800">{{ $siswa->karyawan->nama_gelar }}</strong>
                    </span>
                @endif
            </div>

            <div class="siswa-tabs-body">
                <!-- TAB 1: AKADEMIK & BIODATA -->
                <section id="panel-akademik" class="siswa-tab-panel is-active" role="tabpanel" data-siswa-panel="akademik">
                    <div class="siswa-grid-2">
                        <!-- Akademik & Layanan -->
                        <div class="siswa-subcard">
                            <div class="siswa-subcard-title">
                                <i class="pe-7s-notebook" style="font-size: 16px;"></i> Akademik &amp; Layanan
                            </div>
                            <div class="siswa-kv-grid">
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Kelas Saat Ini</div>
                                    <div class="siswa-kv-value">
                                        @if ($siswa->kelas)
                                            @if ($siswa->kelas->id)
                                                <a href="{{ route('kelas.show', $siswa->kelas->id) }}" class="text-primary text-decoration-none">
                                                    {{ $siswa->kelas->nama_kelas }} ({{ $siswa->kelas->jenjang_kelas }})
                                                </a>
                                            @else
                                                {{ $siswa->kelas->nama_kelas }} ({{ $siswa->kelas->jenjang_kelas }})
                                            @endif
                                            @if ($siswa->kelas->tahunAjaran)
                                                <div class="small text-muted font-weight-normal">
                                                    {{ $siswa->kelas->tahunAjaran->nama_tahun_ajaran }} - Sem {{ $siswa->kelas->tahunAjaran->semester }}
                                                </div>
                                            @endif
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>

                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Guru Pembimbing / Wali</div>
                                    <div class="siswa-kv-value">{{ $siswa->karyawan?->nama_gelar ?? '-' }}</div>
                                </div>

                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Ekstrakurikuler</div>
                                    <div class="siswa-kv-value">
                                        @if ($siswa->ekstrakurikuler)
                                            @if (Route::has('ekstrakurikuler.show') && $siswa->ekstrakurikuler->id)
                                                <a href="{{ route('ekstrakurikuler.show', $siswa->ekstrakurikuler->id) }}"
                                                    class="text-decoration-none font-weight-bold text-primary">
                                                    {{ $siswa->ekstrakurikuler->nama_ekstrakurikuler }}
                                                </a>
                                            @else
                                                {{ $siswa->ekstrakurikuler->nama_ekstrakurikuler }}
                                            @endif
                                            <div class="small text-muted font-weight-normal">Unit {{ $siswa->ekstrakurikuler->unit }}</div>
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>

                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Layanan Jemputan</div>
                                    <div class="siswa-kv-value">
                                        @if ($siswa->jemputan)
                                            @php
                                                $jemputanLabel = $siswa->jemputan->petugas?->nama_gelar ?? 'Petugas #' . $siswa->jemputan->karyawan_nipy;
                                            @endphp
                                            @if (Route::has('jemputan.show') && $siswa->jemputan->id)
                                                <a href="{{ route('jemputan.show', $siswa->jemputan->id) }}"
                                                    class="text-decoration-none font-weight-bold text-slate-800">
                                                    {{ $jemputanLabel }}
                                                </a>
                                            @else
                                                {{ $jemputanLabel }}
                                            @endif
                                            <div class="small text-muted font-weight-normal">
                                                PP: {{ $siswa->jemputan->harga_pp_formatted }} • 1/2: {{ $siswa->jemputan->harga_setengah_formatted }}
                                            </div>
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>

                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Jenis Pendaftaran</div>
                                    <div class="siswa-kv-value">{{ $siswa->jenis_pendaftaran ?? '-' }}</div>
                                </div>

                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Status</div>
                                    <div class="siswa-kv-value">
                                        <span class="badge {{ $siswa->status_badge_class }} px-2 py-1">{{ $siswa->status }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Biodata & Periodik -->
                        <div class="siswa-subcard">
                            <div class="siswa-subcard-title">
                                <i class="pe-7s-user" style="font-size: 16px;"></i> Biodata &amp; Data Periodik
                            </div>
                            <div class="siswa-kv-grid">
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">NIK</div>
                                    <div class="siswa-kv-value font-mono">{{ $siswa->nik ?? '-' }}</div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">No. Kartu Keluarga (KK)</div>
                                    <div class="siswa-kv-value font-mono">{{ $siswa->no_kk ?? '-' }}</div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Tempat, Tanggal Lahir</div>
                                    <div class="siswa-kv-value">
                                        {{ $siswa->tempat_lahir ?? '-' }},
                                        {{ $siswa->tanggal_lahir ? $siswa->tanggal_lahir->translatedFormat('d F Y') : '-' }}
                                    </div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">No. Akta Lahir</div>
                                    <div class="siswa-kv-value">{{ $siswa->akta_lahir ?? '-' }}</div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Agama &amp; Kewarganegaraan</div>
                                    <div class="siswa-kv-value">
                                        {{ $siswa->agama ?? '-' }}
                                        @if ($siswa->kewarganegaraan || $siswa->nama_negara)
                                            • {{ $siswa->kewarganegaraan ?? '-' }}
                                            @if ($siswa->nama_negara)
                                                ({{ $siswa->nama_negara }})
                                            @endif
                                        @endif
                                    </div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Berkebutuhan Khusus</div>
                                    <div class="siswa-kv-value">{{ $siswa->berkebutuhanKhusus?->nama_berkebutuhan_khusus ?? '-' }}</div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Anak Ke / Jml Saudara</div>
                                    <div class="siswa-kv-value">
                                        {{ $siswa->anak_ke ?? '-' }} dari {{ $siswa->jumlah_saudara ?? '-' }} bersaudara
                                    </div>
                                </div>
                            </div>

                            <div class="siswa-metric-chips">
                                <div class="siswa-metric-chip">
                                    <div class="siswa-metric-chip-val">{{ $siswa->tinggi_badan !== null ? $siswa->tinggi_badan . ' cm' : '-' }}</div>
                                    <div class="siswa-metric-chip-lbl">Tinggi Badan</div>
                                </div>
                                <div class="siswa-metric-chip">
                                    <div class="siswa-metric-chip-val">{{ $siswa->berat_badan !== null ? $siswa->berat_badan . ' kg' : '-' }}</div>
                                    <div class="siswa-metric-chip-lbl">Berat Badan</div>
                                </div>
                                <div class="siswa-metric-chip">
                                    <div class="siswa-metric-chip-val">{{ $siswa->lingkar_kepala !== null ? $siswa->lingkar_kepala . ' cm' : '-' }}</div>
                                    <div class="siswa-metric-chip-lbl">Lingkar Kepala</div>
                                </div>
                                <div class="siswa-metric-chip">
                                    <div class="siswa-metric-chip-val">{{ $siswa->jarak !== null ? $siswa->jarak . ' km' : '-' }}</div>
                                    <div class="siswa-metric-chip-lbl">Jarak Sekolah</div>
                                </div>
                                <div class="siswa-metric-chip">
                                    <div class="siswa-metric-chip-val">{{ $siswa->waktu_tempuh !== null ? $siswa->waktu_tempuh . ' mnt' : '-' }}</div>
                                    <div class="siswa-metric-chip-lbl">Waktu Tempuh</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- TAB 2: KONTAK & ALAMAT -->
                <section id="panel-kontak" class="siswa-tab-panel" role="tabpanel" data-siswa-panel="kontak">
                    <div class="siswa-grid-2">
                        <div class="siswa-subcard">
                            <div class="siswa-subcard-title">
                                <i class="fa fa-phone-alt"></i> Kontak &amp; Transportasi
                            </div>
                            <div class="siswa-kv-grid">
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Nomor HP</div>
                                    <div class="siswa-kv-value">{{ $siswa->nomor_hp ?? '-' }}</div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">WhatsApp</div>
                                    <div class="siswa-kv-value">
                                        @if ($siswa->whatsapp)
                                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siswa->whatsapp) }}"
                                                target="_blank" rel="noopener" class="text-success font-weight-bold text-decoration-none">
                                                <i class="fab fa-whatsapp mr-1"></i> {{ $siswa->whatsapp }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>
                                <div class="siswa-kv-item is-full">
                                    <div class="siswa-kv-label">Email</div>
                                    <div class="siswa-kv-value">
                                        @if ($siswa->email)
                                            <a href="mailto:{{ $siswa->email }}" class="text-primary font-weight-bold text-decoration-none">
                                                {{ $siswa->email }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Tempat Tinggal</div>
                                    <div class="siswa-kv-value">{{ $siswa->tempat_tinggal ?? '-' }}</div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Moda Transportasi</div>
                                    <div class="siswa-kv-value">{{ $siswa->transportasi?->nama_transportasi ?? '-' }}</div>
                                </div>
                            </div>
                        </div>

                        <div class="siswa-subcard">
                            <div class="siswa-subcard-title">
                                <i class="pe-7s-map-marker" style="font-size: 16px;"></i> Domisili &amp; Alamat Lengkap
                            </div>
                            <div class="siswa-kv-grid">
                                <div class="siswa-kv-item is-full">
                                    <div class="siswa-kv-label">Alamat Jalan / Rumah</div>
                                    <div class="siswa-kv-value">
                                        {{ $siswa->alamat ?? '-' }}
                                        @if ($siswa->rt || $siswa->rw)
                                            (RT {{ $siswa->rt ?? '-' }} / RW {{ $siswa->rw ?? '-' }})
                                        @endif
                                    </div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Desa / Kelurahan</div>
                                    <div class="siswa-kv-value">{{ $siswa->desa ?? '-' }}</div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Kecamatan</div>
                                    <div class="siswa-kv-value">{{ $siswa->kecamatan ?? '-' }}</div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Kabupaten / Kota</div>
                                    <div class="siswa-kv-value">{{ $siswa->kabupaten ?? '-' }}</div>
                                </div>
                                <div class="siswa-kv-item">
                                    <div class="siswa-kv-label">Provinsi &amp; Kode Pos</div>
                                    <div class="siswa-kv-value">
                                        {{ $siswa->provinsi ?? '-' }}{{ $siswa->kode_pos ? ' • ' . $siswa->kode_pos : '' }}
                                    </div>
                                </div>
                                <div class="siswa-kv-item is-full">
                                    <div class="siswa-kv-label">Koordinat (Lintang / Bujur)</div>
                                    <div class="siswa-kv-value font-mono d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                                        <span>{{ $siswa->lintang ?? '-' }} / {{ $siswa->bujur ?? '-' }}</span>
                                        @if ($siswa->lintang && $siswa->bujur)
                                            <a href="https://maps.google.com/?q={{ $siswa->lintang }},{{ $siswa->bujur }}"
                                                target="_blank" rel="noopener" class="badge badge-info text-decoration-none">
                                                <i class="fa fa-map-pin mr-1"></i> Lihat Peta
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- TAB 3: ORANG TUA & WALI -->
                <section id="panel-keluarga" class="siswa-tab-panel" role="tabpanel" data-siswa-panel="keluarga">
                    <div class="siswa-grid-3">
                        @foreach ($keluargaGroups as $peran => $group)
                            <div class="siswa-family-card {{ $group['modifier'] }}">
                                <div class="siswa-family-header">
                                    <i class="fa {{ $group['icon'] }}"></i>
                                    <span>{{ $group['title'] }}</span>
                                </div>
                                <div class="siswa-family-rows">
                                    <div class="siswa-family-row">
                                        <span class="siswa-family-row-label">Nama Lengkap</span>
                                        <span class="siswa-family-row-val">{{ $siswa->{"nama_{$peran}"} ?? '-' }}</span>
                                    </div>
                                    <div class="siswa-family-row">
                                        <span class="siswa-family-row-label">NIK</span>
                                        <span class="siswa-family-row-val font-mono">{{ $siswa->{"nik_{$peran}"} ?? '-' }}</span>
                                    </div>
                                    <div class="siswa-family-row">
                                        <span class="siswa-family-row-label">Thn/Tgl Lahir</span>
                                        <span class="siswa-family-row-val">{{ $siswa->{"lahir_{$peran}"} ?? '-' }}</span>
                                    </div>
                                    <div class="siswa-family-row">
                                        <span class="siswa-family-row-label">Pendidikan</span>
                                        <span class="siswa-family-row-val">{{ $group['jenjang'] ?? '-' }}</span>
                                    </div>
                                    <div class="siswa-family-row">
                                        <span class="siswa-family-row-label">Pekerjaan</span>
                                        <span class="siswa-family-row-val">{{ $group['pekerjaan'] ?? '-' }}</span>
                                    </div>
                                    <div class="siswa-family-row">
                                        <span class="siswa-family-row-label">Penghasilan</span>
                                        <span class="siswa-family-row-val">{{ $group['penghasilan'] ?? '-' }}</span>
                                    </div>
                                    <div class="siswa-family-row">
                                        <span class="siswa-family-row-label">Berkebutuhan Khusus</span>
                                        <span class="siswa-family-row-val">{{ $group['khusus'] ?? '-' }}</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabButtons = Array.from(document.querySelectorAll('[data-siswa-tab]'));
            const tabPanels = Array.from(document.querySelectorAll('[data-siswa-panel]'));

            if (tabButtons.length === 0 || tabPanels.length === 0) {
                return;
            }

            const activateTab = (targetName) => {
                tabButtons.forEach((btn) => {
                    const active = btn.dataset.siswaTab === targetName;
                    btn.classList.toggle('is-active', active);
                    btn.setAttribute('aria-selected', active ? 'true' : 'false');
                });

                tabPanels.forEach((panel) => {
                    panel.classList.toggle('is-active', panel.dataset.siswaPanel === targetName);
                });
            };

            tabButtons.forEach((btn) => {
                btn.addEventListener('click', () => {
                    activateTab(btn.dataset.siswaTab);
                });
            });
        });
    </script>
@endsection
