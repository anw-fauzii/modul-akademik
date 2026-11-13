@extends('layouts.appui')

@section('title')
    <title>Input Presensi Massal - SIPIT Akademik</title>
@endsection

@push('css')
    <style>
        .bulk-hero {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 55%, #334155 100%);
            border-radius: 18px;
            padding: 20px 24px;
            color: #ffffff;
            box-shadow: 0 12px 28px -8px rgba(15, 23, 42, 0.3);
        }

        .bulk-status-group {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            flex-wrap: nowrap;
        }

        .bulk-status-item {
            position: relative;
            margin: 0;
        }

        .bulk-status-radio {
            position: absolute;
            opacity: 0;
            width: 1px;
            height: 1px;
            pointer-events: none;
        }

        .bulk-status-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 4px;
            min-width: 38px;
            height: 32px;
            padding: 0 9px;
            border-radius: 9px;
            border: 1px solid #cbd5e1;
            background: #ffffff;
            color: #475569;
            font-size: 11px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.15s ease;
            user-select: none;
        }

        .bulk-status-pill:hover {
            border-color: #94a3b8;
            background: #f8fafc;
        }

        .bulk-status-radio:checked + .bulk-pill-hadir {
            background: #10b981;
            border-color: #059669;
            color: #ffffff;
            box-shadow: 0 4px 10px -2px rgba(16, 185, 129, 0.4);
        }

        .bulk-status-radio:checked + .bulk-pill-terlambat {
            background: #f59e0b;
            border-color: #d97706;
            color: #ffffff;
            box-shadow: 0 4px 10px -2px rgba(245, 158, 11, 0.4);
        }

        .bulk-status-radio:checked + .bulk-pill-izin {
            background: #0ea5e9;
            border-color: #0284c7;
            color: #ffffff;
            box-shadow: 0 4px 10px -2px rgba(14, 165, 233, 0.4);
        }

        .bulk-status-radio:checked + .bulk-pill-sakit {
            background: #6366f1;
            border-color: #4f46e5;
            color: #ffffff;
            box-shadow: 0 4px 10px -2px rgba(99, 102, 241, 0.4);
        }

        .bulk-status-radio:checked + .bulk-pill-alpa {
            background: #f43f5e;
            border-color: #e11d48;
            color: #ffffff;
            box-shadow: 0 4px 10px -2px rgba(244, 63, 94, 0.4);
        }

        .bulk-row-muted-input {
            background-color: #f1f5f9 !important;
            color: #94a3b8 !important;
            cursor: not-allowed;
        }

        .bulk-toolbar {
            background: linear-gradient(180deg, #f8fafc 0%, #ffffff 100%);
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 20px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .bulk-toolbar-top {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 10px;
        }

        .bulk-quick-group {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
        }

        .bulk-quick-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 34px;
            padding: 0 11px;
            border-radius: 9px;
            background: #0f172a;
            color: #ffffff;
            font-size: 11px;
            font-weight: 800;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .bulk-quick-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 34px;
            padding: 0 12px;
            border-radius: 9px;
            border: 1px solid #cbd5e1;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.15s ease;
            white-space: nowrap;
        }

        .bulk-quick-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 10px -3px rgba(15, 23, 42, 0.18);
        }

        .bulk-quick-hadir {
            background: #ecfdf5;
            border-color: #a7f3d0;
            color: #047857;
        }

        .bulk-quick-hadir:hover {
            background: #059669;
            border-color: #059669;
            color: #ffffff;
        }

        .bulk-quick-terlambat {
            background: #fffbeb;
            border-color: #fde68a;
            color: #b45309;
        }

        .bulk-quick-terlambat:hover {
            background: #d97706;
            border-color: #d97706;
            color: #ffffff;
        }

        .bulk-quick-izin {
            background: #f0f9ff;
            border-color: #bae6fd;
            color: #0369a1;
        }

        .bulk-quick-izin:hover {
            background: #0284c7;
            border-color: #0284c7;
            color: #ffffff;
        }

        .bulk-quick-sakit {
            background: #eef2ff;
            border-color: #c7d2fe;
            color: #4338ca;
        }

        .bulk-quick-sakit:hover {
            background: #4f46e5;
            border-color: #4f46e5;
            color: #ffffff;
        }

        .bulk-quick-alpa {
            background: #fff1f2;
            border-color: #fecdd3;
            color: #be123c;
        }

        .bulk-quick-alpa:hover {
            background: #e11d48;
            border-color: #e11d48;
            color: #ffffff;
        }

        .bulk-cutoff-pill {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 999px;
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            font-size: 11px;
            font-weight: 700;
            white-space: nowrap;
        }

        .bulk-toolbar-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            padding-top: 12px;
            border-top: 1px dashed #cbd5e1;
        }

        .bulk-jam-box {
            display: inline-flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 8px;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            padding: 4px 5px 4px 12px;
            box-shadow: 0 2px 6px -2px rgba(15, 23, 42, 0.06);
        }

        .bulk-jam-label {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            font-size: 12px;
            font-weight: 700;
            color: #334155;
            white-space: nowrap;
        }

        .bulk-jam-input {
            height: 32px;
            padding: 0 10px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            background: #f8fafc;
            font-size: 12px;
            font-weight: 700;
            color: #0f172a;
        }

        .bulk-jam-input:focus {
            outline: none;
            border-color: #ea580c;
            background: #ffffff;
        }

        .bulk-jam-apply {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            height: 32px;
            padding: 0 12px;
            border: none;
            border-radius: 8px;
            background: #0f172a;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s ease;
            white-space: nowrap;
        }

        .bulk-jam-apply:hover {
            background: #1e293b;
        }

        .bulk-search-wrap {
            position: relative;
            flex: 1 1 260px;
            max-width: 340px;
        }

        .bulk-search-icon {
            position: absolute;
            top: 50%;
            left: 12px;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 12px;
            pointer-events: none;
        }

        .bulk-search-input {
            width: 100%;
            height: 40px;
            padding: 0 14px 0 34px;
            border: 1px solid #cbd5e1;
            border-radius: 11px;
            background: #ffffff;
            font-size: 12px;
            font-weight: 500;
            color: #1e293b;
            box-shadow: 0 2px 6px -2px rgba(15, 23, 42, 0.05);
            transition: border-color 0.15s ease, box-shadow 0.15s ease;
        }

        .bulk-search-input:focus {
            outline: none;
            border-color: #ea580c;
            box-shadow: 0 0 0 3px rgba(234, 88, 12, 0.12);
        }
    </style>
@endpush

@section('content')
    @php
        $batasJamMasuk = \App\Models\Admin\Presensi\PresensiSiswa::BATAS_JAM_MASUK;
        $statusShortLabels = [
            'hadir' => 'H',
            'terlambat' => 'T',
            'izin' => 'I',
            'sakit' => 'S',
            'alpa' => 'A',
        ];
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
                        Input Presensi Massal Per Tanggal
                        <div class="page-title-subheading">
                            Catat atau perbarui kehadiran satu kelas sekaligus pada tanggal pilihan Anda.
                        </div>
                    </div>
                </div>
                <div class="page-title-actions d-flex flex-wrap align-items-center" style="gap: 0.5rem;">
                    <a href="{{ route('presensi-siswa.index', array_filter([
                        'kelas_id' => $selectedKelasId > 0 ? $selectedKelasId : null,
                        'bulan' => \Carbon\Carbon::parse($tanggal)->format('Y-m'),
                    ])) }}"
                        class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali ke Matriks
                    </a>
                </div>
            </div>
        </div>

        @if (isset($errors) && $errors->any())
            <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius: 12px;">
                <div class="font-weight-bold mb-1">
                    <i class="fa fa-exclamation-triangle mr-1"></i> Terdapat kesalahan input presensi massal:
                </div>
                <ul class="mb-0 pl-3">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger border-0 shadow-sm mb-3" style="border-radius: 12px;">
                <i class="fa fa-times-circle mr-1"></i> {{ session('error') }}
            </div>
        @endif

        <!-- PILIH KELAS & TANGGAL -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-calendar-check"></i></span>
                    <span>Pilih Kelas &amp; Tanggal Presensi</span>
                </div>
                @if ($selectedKelas)
                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <span class="filter-count-pill">
                            <i class="fa fa-users text-primary"></i>
                            Total Siswa: <strong>{{ count($rows) }}</strong>
                        </span>
                        <span class="filter-count-pill">
                            <i class="fa fa-check-circle text-success"></i>
                            Sudah Tercatat: <strong>{{ $existingCount }}</strong>
                        </span>
                        @if ($existingCount > 0)
                            <form id="bulkDestroyCurrentForm" action="{{ route('presensi-siswa.bulk.destroy') }}" method="POST" class="d-inline">
                                @csrf
                                @method('DELETE')
                                <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                                <input type="hidden" name="tanggal" value="{{ $tanggal }}">
                                <input type="hidden" name="redirect_to" value="bulk">
                                <button type="button"
                                    class="filter-btn filter-btn-secondary text-rose-600 border-rose-200 hover:bg-rose-50 delete-button"
                                    style="height: 1.9rem; min-height: 1.9rem; padding: 0 0.7rem; font-size: 0.75rem;">
                                    <i class="fa fa-trash-alt"></i> Hapus Semua ({{ $existingCount }})
                                </button>
                            </form>
                        @endif
                    </div>
                @endif
            </div>

            <div class="filter-card-body">
                <form method="GET" action="{{ route('presensi-siswa.bulk') }}" id="bulkContextForm"
                    class="grid grid-cols-1 md:grid-cols-12 gap-3 items-end">
                    <div class="md:col-span-6">
                        <label for="bulk_kelas_id" class="filter-label">Kelas</label>
                        <select id="bulk_kelas_id" name="kelas_id" class="filter-control" required>
                            <option value="">-- Pilih Kelas --</option>
                            @foreach ($kelasOptions as $kelas)
                                <option value="{{ $kelas->id }}" @selected($selectedKelasId === (int) $kelas->id)>
                                    {{ $kelas->jenjang_kelas }} - {{ $kelas->nama_kelas }}
                                    @if ($kelas->tahunAjaran)
                                        ({{ $kelas->tahunAjaran->nama_tahun_ajaran }} - {{ $kelas->tahunAjaran->semester }})
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-4">
                        <label for="bulk_tanggal" class="filter-label">Tanggal Presensi</label>
                        <input id="bulk_tanggal" type="date" name="tanggal" value="{{ $tanggal }}"
                            class="filter-control" required>
                    </div>

                    <div class="md:col-span-2">
                        <button type="submit" class="filter-btn filter-btn-primary w-100 justify-content-center">
                            <i class="fa fa-search"></i> Muat Siswa
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if (! $selectedKelas)
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body py-12 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-orange-50 text-orange-600 border border-orange-100">
                        <i class="fa fa-layer-group fa-2x"></i>
                    </div>
                    <h4 class="text-lg font-extrabold text-slate-800">Pilih Kelas &amp; Tanggal Terlebih Dahulu</h4>
                    <p class="mx-auto mt-1.5 max-w-md text-sm text-slate-500">
                        Tentukan <strong>Kelas</strong> dan <strong>Tanggal Presensi</strong> di panel atas lalu klik
                        <strong>Muat Siswa</strong> untuk menampilkan daftar seluruh siswa dan mengisi presensi secara massal.
                    </p>
                </div>
            </div>
        @elseif (count($rows) === 0)
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body py-12 text-center">
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-50 text-amber-600">
                        <i class="fa fa-user-slash fa-2x"></i>
                    </div>
                    <h4 class="text-lg font-extrabold text-slate-800">Belum Ada Anggota Kelas</h4>
                    <p class="mx-auto mt-1.5 max-w-md text-sm text-slate-500">
                        Kelas <strong>{{ $selectedKelas->jenjang_kelas }} - {{ $selectedKelas->nama_kelas }}</strong> belum memiliki anggota siswa terdaftar.
                    </p>
                </div>
            </div>
        @else
            <!-- JUDUL & INFORMASI SESI INPUT MASSAL -->
            <div class="bulk-hero mb-4">
                <div class="text-xs uppercase tracking-wider text-orange-300 font-bold mb-1">
                    Sesi Input Massal
                </div>
                <h4 class="text-xl font-extrabold text-white mb-1">
                    Kelas {{ $selectedKelas->jenjang_kelas }} - {{ $selectedKelas->nama_kelas }}
                </h4>
                <div class="text-xs text-slate-300 flex flex-wrap items-center gap-3">
                    <span><i class="fa fa-calendar mr-1 text-orange-300"></i> {{ $tanggalLabel }}</span>
                    @if ($selectedKelas->tahunAjaran)
                        <span>
                            <i class="fa fa-graduation-cap mr-1 text-amber-300"></i>
                            {{ $selectedKelas->tahunAjaran->nama_tahun_ajaran }} ({{ $selectedKelas->tahunAjaran->semester }})
                        </span>
                    @endif
                    @if ($selectedKelas->waliKelas)
                        <span>
                            <i class="fa fa-user-tie mr-1 text-sky-300"></i>
                            Wali Kelas: {{ $selectedKelas->waliKelas->nama_gelar }}
                        </span>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('presensi-siswa.bulk.store') }}" id="bulkAttendanceForm">
                @csrf
                <input type="hidden" name="kelas_id" value="{{ $selectedKelas->id }}">
                <input type="hidden" name="tanggal" value="{{ $tanggal }}">

                <div class="card border-0 shadow-sm mb-4 overflow-hidden" style="border-radius: 16px;">
                    <!-- TOOLBAR AKSI CEPAT -->
                    <div class="bulk-toolbar">
                        <div class="bulk-toolbar-top">
                            <div class="bulk-quick-group">
                                <span class="bulk-quick-label">
                                    <i class="fa fa-bolt text-amber-400"></i> Aksi Cepat
                                </span>

                                <button type="button" class="bulk-quick-btn bulk-quick-hadir js-set-all-status"
                                    data-status="hadir">
                                    <i class="fa fa-check-circle"></i> Semua Hadir
                                </button>
                                <button type="button" class="bulk-quick-btn bulk-quick-terlambat js-set-all-status"
                                    data-status="terlambat">
                                    <i class="fa fa-clock"></i> Semua Terlambat
                                </button>
                                <button type="button" class="bulk-quick-btn bulk-quick-izin js-set-all-status"
                                    data-status="izin">
                                    <i class="fa fa-envelope"></i> Semua Izin
                                </button>
                                <button type="button" class="bulk-quick-btn bulk-quick-sakit js-set-all-status"
                                    data-status="sakit">
                                    <i class="fa fa-notes-medical"></i> Semua Sakit
                                </button>
                                <button type="button" class="bulk-quick-btn bulk-quick-alpa js-set-all-status"
                                    data-status="alpa">
                                    <i class="fa fa-times-circle"></i> Semua Alpa
                                </button>
                            </div>

                            <span class="bulk-cutoff-pill">
                                <i class="fa fa-stopwatch"></i>
                                Batas tepat waktu: <strong>{{ $batasJamMasuk }}</strong>
                            </span>
                        </div>

                        <div class="bulk-toolbar-controls">
                            <div class="bulk-jam-box">
                                <span class="bulk-jam-label">
                                    <i class="fa fa-clock text-orange-500"></i> Jam Masuk
                                </span>
                                <input type="time" id="massJamMasukInput" value="07:00" class="bulk-jam-input">
                                <button type="button" id="applyMassJamBtn" class="bulk-jam-apply">
                                    <i class="fa fa-magic text-amber-400"></i> Terapkan ke Semua
                                </button>
                            </div>

                            <div class="bulk-search-wrap">
                                <i class="fa fa-search bulk-search-icon"></i>
                                <input type="search" id="bulkStudentSearch" class="bulk-search-input"
                                    placeholder="Cari nama atau NIS siswa...">
                            </div>
                        </div>

                        @if ($existingCount > 0)
                            <div class="text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-200 rounded-xl px-3 py-2 flex items-center gap-2">
                                <i class="fa fa-shield-alt text-amber-600 shrink-0"></i>
                                <span>
                                    <strong>{{ $existingCount }}</strong> siswa berstatus <strong>Tercatat</strong> tidak akan berubah saat memakai <em>Aksi Cepat</em> atau <em>Terapkan Jam Massal</em> (hanya berlaku untuk siswa berstatus <strong>Baru</strong>).
                                </span>
                            </div>
                        @endif
                    </div>

                    <!-- TABEL INPUT MASSAL -->
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="bulkStudentsTable">
                                <thead class="bg-light text-slate-600">
                                    <tr>
                                        <th style="width: 52px;" class="text-center">No</th>
                                        <th style="min-width: 230px;">Siswa</th>
                                        <th style="width: 130px;" class="text-center">Rekam</th>
                                        <th style="min-width: 260px;">Status (H / T / I / S / A)</th>
                                        <th style="width: 135px;">Jam Masuk</th>
                                        <th style="width: 135px;">Telat (Menit)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($rows as $index => $row)
                                        @php
                                            $anggota = $row['anggota'];
                                            $siswa = $anggota->siswa;
                                            $existing = $row['existing'];

                                            $defaultStatus = $existing ? (string) $existing->status : 'hadir';
                                            $currentStatus = (string) old("items.$index.status", $defaultStatus);

                                            $defaultJam = $existing?->jam_masuk
                                                ? substr((string) $existing->jam_masuk, 0, 5)
                                                : ($currentStatus === 'hadir' ? '07:00' : '');
                                            $currentJam = (string) old("items.$index.jam_masuk", $defaultJam);

                                            $defaultTelat = $existing ? (int) $existing->keterlambatan_menit : 0;
                                            $currentTelat = (int) old("items.$index.keterlambatan_menit", $defaultTelat);

                                            $allowsTime = in_array($currentStatus, ['hadir', 'terlambat'], true);
                                            $allowsLate = $currentStatus === 'terlambat';
                                        @endphp
                                        <tr class="js-bulk-student-row"
                                            data-has-existing="{{ $existing ? '1' : '0' }}"
                                            data-search="{{ mb_strtolower(trim(($siswa?->nama_lengkap ?? '') . ' ' . $anggota->siswa_nis)) }}">
                                            <td class="text-center font-weight-bold text-slate-500">{{ $index + 1 }}</td>
                                            <td>
                                                <input type="hidden" name="items[{{ $index }}][anggota_kelas_id]"
                                                    value="{{ $anggota->id }}">
                                                <div class="font-weight-bold text-slate-800">
                                                    {{ $siswa?->nama_lengkap ?? '-' }}
                                                </div>
                                                <div class="text-xs text-slate-500">
                                                    NIS: {{ $anggota->siswa_nis }}
                                                    @if ($siswa?->jenis_kelamin)
                                                        &bull; {{ $siswa->jenis_kelamin }}
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="text-center">
                                                @if ($existing)
                                                    <span class="badge badge-success px-2 py-1" style="border-radius: 6px;">
                                                        <i class="fa fa-check mr-1"></i>Tercatat
                                                    </span>
                                                @else
                                                    <span class="badge badge-light border text-slate-500 px-2 py-1" style="border-radius: 6px;">
                                                        Baru
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <div class="bulk-status-group" role="radiogroup"
                                                    aria-label="Status {{ $siswa?->nama_lengkap }}">
                                                    @foreach ($statusOptions as $statusKey => $statusLabel)
                                                        @php
                                                            $radioId = "bulk_status_{$index}_{$statusKey}";
                                                            $shortCode = $statusShortLabels[$statusKey] ?? strtoupper(substr($statusKey, 0, 1));
                                                        @endphp
                                                        <label for="{{ $radioId }}" class="bulk-status-item"
                                                            title="{{ $statusLabel }}">
                                                            <input type="radio"
                                                                id="{{ $radioId }}"
                                                                name="items[{{ $index }}][status]"
                                                                value="{{ $statusKey }}"
                                                                class="bulk-status-radio js-bulk-status-radio"
                                                                @checked($currentStatus === $statusKey)>
                                                            <span class="bulk-status-pill bulk-pill-{{ $statusKey }}">
                                                                {{ $shortCode }}
                                                            </span>
                                                        </label>
                                                    @endforeach
                                                </div>
                                            </td>
                                            <td>
                                                <input type="time"
                                                    name="items[{{ $index }}][jam_masuk]"
                                                    value="{{ $allowsTime ? $currentJam : '' }}"
                                                    class="form-control form-control-sm js-bulk-jam-input {{ $allowsTime ? '' : 'bulk-row-muted-input' }}"
                                                    @readonly(! $allowsTime)>
                                            </td>
                                            <td>
                                                <input type="number"
                                                    min="0"
                                                    max="600"
                                                    name="items[{{ $index }}][keterlambatan_menit]"
                                                    value="{{ $allowsLate ? $currentTelat : 0 }}"
                                                    class="form-control form-control-sm js-bulk-telat-input {{ $allowsLate ? '' : 'bulk-row-muted-input' }}"
                                                    @readonly(! $allowsLate)>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="card-footer bg-white border-top py-3 px-4 d-flex flex-wrap justify-content-between align-items-center gap-2">
                        <div class="text-xs text-slate-500">
                            Keterangan kode: <strong>H</strong> = Hadir, <strong>T</strong> = Terlambat,
                            <strong>I</strong> = Izin, <strong>S</strong> = Sakit, <strong>A</strong> = Alpa.
                            &bull; Batas tepat waktu: <strong>{{ $batasJamMasuk }}</strong> (lewat {{ $batasJamMasuk }} otomatis Terlambat).
                        </div>
                        <div class="d-flex align-items-center" style="gap: 0.5rem;">
                            <a href="{{ route('presensi-siswa.index', [
                                'kelas_id' => $selectedKelas->id,
                                'bulan' => \Carbon\Carbon::parse($tanggal)->format('Y-m'),
                            ]) }}"
                                class="btn btn-light border" style="border-radius: 8px;">
                                Batal
                            </a>
                            <button type="submit" class="btn text-white shadow-sm px-4 font-weight-bold"
                                style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px; border: none;">
                                <i class="fa fa-save mr-1"></i> Simpan Semua Presensi ({{ count($rows) }} Siswa)
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        @endif
    </div>
@endsection

@push('js')
    <script>
        (function () {
            const contextForm = document.getElementById('bulkContextForm');
            const kelasSelect = document.getElementById('bulk_kelas_id');
            const tanggalInput = document.getElementById('bulk_tanggal');

            if (contextForm && kelasSelect && tanggalInput) {
                const submitIfReady = function () {
                    if (kelasSelect.value && tanggalInput.value) {
                        contextForm.submit();
                    }
                };
                kelasSelect.addEventListener('change', submitIfReady);
                tanggalInput.addEventListener('change', submitIfReady);
            }

            const BATAS_JAM_MASUK = @json($batasJamMasuk);
            const rows = Array.from(document.querySelectorAll('.js-bulk-student-row'));
            if (rows.length === 0) {
                return;
            }

            function calculateLateMinutes(jamValue) {
                if (!jamValue || !/^\d{2}:\d{2}/.test(jamValue)) {
                    return 0;
                }

                const [maxH, maxM] = BATAS_JAM_MASUK.split(':').map(Number);
                const [h, m] = jamValue.slice(0, 5).split(':').map(Number);

                return Math.max(0, (h * 60 + m) - (maxH * 60 + maxM));
            }

            function syncRowInputs(row, status) {
                const jamInput = row.querySelector('.js-bulk-jam-input');
                const telatInput = row.querySelector('.js-bulk-telat-input');
                const massJamInput = document.getElementById('massJamMasukInput');

                if (jamInput) {
                    const allowsTime = status === 'hadir' || status === 'terlambat';
                    jamInput.readOnly = !allowsTime;
                    jamInput.classList.toggle('bulk-row-muted-input', !allowsTime);
                    if (!allowsTime) {
                        jamInput.value = '';
                    } else if (!jamInput.value && massJamInput && massJamInput.value) {
                        jamInput.value = massJamInput.value;
                    }
                }

                if (telatInput) {
                    const allowsLate = status === 'terlambat';
                    telatInput.readOnly = !allowsLate;
                    telatInput.classList.toggle('bulk-row-muted-input', !allowsLate);
                    if (!allowsLate) {
                        telatInput.value = '0';
                    } else if (telatInput.value === '' || Number(telatInput.value) <= 0) {
                        const computed = jamInput ? calculateLateMinutes(jamInput.value) : 0;
                        telatInput.value = String(computed);
                    }
                }
            }

            function syncRowFromJam(row) {
                const checked = row.querySelector('.js-bulk-status-radio:checked');
                const jamInput = row.querySelector('.js-bulk-jam-input');
                const telatInput = row.querySelector('.js-bulk-telat-input');

                if (!checked || !jamInput || !telatInput) {
                    return;
                }

                if (checked.value !== 'hadir' && checked.value !== 'terlambat') {
                    return;
                }

                const lateMinutes = calculateLateMinutes(jamInput.value);
                if (lateMinutes > 0) {
                    const lateRadio = row.querySelector('.js-bulk-status-radio[value="terlambat"]');
                    if (lateRadio) {
                        lateRadio.checked = true;
                    }
                    syncRowInputs(row, 'terlambat');
                    telatInput.value = String(lateMinutes);
                } else if (jamInput.value) {
                    const hadirRadio = row.querySelector('.js-bulk-status-radio[value="hadir"]');
                    if (hadirRadio) {
                        hadirRadio.checked = true;
                    }
                    syncRowInputs(row, 'hadir');
                    telatInput.value = '0';
                }
            }

            rows.forEach(function (row) {
                row.querySelectorAll('.js-bulk-status-radio').forEach(function (radio) {
                    radio.addEventListener('change', function () {
                        if (radio.checked) {
                            syncRowInputs(row, radio.value);
                        }
                    });
                });

                const jamInput = row.querySelector('.js-bulk-jam-input');
                if (jamInput) {
                    const onJamChanged = function () {
                        syncRowFromJam(row);
                    };
                    jamInput.addEventListener('input', onJamChanged);
                    jamInput.addEventListener('change', onJamChanged);
                }
            });

            function applyToNewRowsOnly(callback, actionLabel) {
                let updatedCount = 0;
                let skippedCount = 0;

                rows.forEach(function (row) {
                    if (row.dataset.hasExisting === '1') {
                        skippedCount++;
                        return;
                    }

                    if (callback(row) !== false) {
                        updatedCount++;
                    }
                });

                if (skippedCount > 0 && typeof window.toastr !== 'undefined') {
                    const msg = updatedCount > 0
                        ? actionLabel + ' diterapkan ke ' + updatedCount + ' siswa baru (' + skippedCount + ' siswa tercatat dipertahankan).'
                        : 'Seluruh ' + skippedCount + ' siswa sudah tercatat dan datanya dipertahankan.';
                    window.toastr.info(msg);
                }
            }

            document.querySelectorAll('.js-set-all-status').forEach(function (button) {
                button.addEventListener('click', function () {
                    const targetStatus = button.getAttribute('data-status');
                    if (!targetStatus) {
                        return;
                    }

                    applyToNewRowsOnly(function (row) {
                        const radio = row.querySelector('.js-bulk-status-radio[value="' + targetStatus + '"]');
                        if (!radio) {
                            return false;
                        }
                        radio.checked = true;
                        syncRowInputs(row, targetStatus);
                        return true;
                    }, 'Status "' + button.textContent.trim() + '"');
                });
            });

            const applyMassJamBtn = document.getElementById('applyMassJamBtn');
            const massJamInput = document.getElementById('massJamMasukInput');
            if (applyMassJamBtn && massJamInput) {
                applyMassJamBtn.addEventListener('click', function () {
                    const jamValue = massJamInput.value;
                    applyToNewRowsOnly(function (row) {
                        const checked = row.querySelector('.js-bulk-status-radio:checked');
                        const jamInput = row.querySelector('.js-bulk-jam-input');
                        if (!checked || !jamInput || (checked.value !== 'hadir' && checked.value !== 'terlambat')) {
                            return false;
                        }
                        jamInput.value = jamValue;
                        syncRowFromJam(row);
                        return true;
                    }, 'Jam masuk');
                });
            }

            const searchInput = document.getElementById('bulkStudentSearch');
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const keyword = searchInput.value.trim().toLowerCase();
                    rows.forEach(function (row) {
                        const haystack = row.getAttribute('data-search') || '';
                        row.style.display = keyword === '' || haystack.indexOf(keyword) !== -1 ? '' : 'none';
                    });
                });
            }
        })();
    </script>
@endpush
