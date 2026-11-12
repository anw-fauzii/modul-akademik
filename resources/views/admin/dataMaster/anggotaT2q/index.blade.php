@extends('layouts.appui')

@section('title')
    <title>Master Data Anggota T2Q</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-notebook" style="color: white;"></i>
                    </div>
                    <div>
                        Data Anggota T2Q
                        <div class="page-title-subheading">
                            Kelola daftar peserta bimbingan T2Q, guru pembimbing, dan tingkat berdasarkan keanggotaan kelas per tahun ajaran dan semester.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('anggota-t2q.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH ANGGOTA T2Q
                    </a>
                </div>
            </div>
        </div>

        <!-- FILTER CARD -->
        <div class="main-card card mb-3 shadow-sm border-0">
            <div class="card-body">
                <form method="GET" action="{{ route('anggota-t2q.index') }}" class="row align-items-end">
                    <div class="col-md-3 mb-2 mb-md-0">
                        <label for="filter_tahun_ajaran_id" class="font-weight-bold small text-uppercase text-muted mb-1">
                            Tahun Ajaran / Semester
                        </label>
                        <select name="tahun_ajaran_id" id="filter_tahun_ajaran_id" class="form-control">
                            <option value="">-- Semua Tahun Ajaran --</option>
                            @foreach ($tahunAjaran ?? [] as $ta)
                                <option value="{{ $ta->id }}" @selected((string) ($selectedTahunAjaranId ?? '') === (string) $ta->id)>
                                    {{ $ta->nama_tahun_ajaran }} - Semester {{ strtoupper($ta->semester) }} {{ $ta->is_active ? '(Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-2 mb-md-0">
                        <label for="filter_karyawan_nipy" class="font-weight-bold small text-uppercase text-muted mb-1">
                            Guru T2Q
                        </label>
                        <select name="karyawan_nipy" id="filter_karyawan_nipy" class="form-control">
                            <option value="">-- Semua Guru T2Q --</option>
                            @foreach ($karyawan ?? [] as $guru)
                                <option value="{{ $guru->nipy }}" @selected((string) ($selectedKaryawanNipy ?? '') === (string) $guru->nipy)>
                                    {{ $guru->nama_gelar }} ({{ $guru->nipy }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2 mb-md-0">
                        <label for="filter_tingkat" class="font-weight-bold small text-uppercase text-muted mb-1">
                            Tingkat
                        </label>
                        <select name="tingkat" id="filter_tingkat" class="form-control">
                            <option value="">-- Semua Tingkat --</option>
                            @foreach ($tingkatOptions ?? [] as $opsiTingkat)
                                <option value="{{ $opsiTingkat }}" @selected((string) ($selectedTingkat ?? '') === (string) $opsiTingkat)>
                                    Tingkat {{ $opsiTingkat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 mb-2 mb-md-0">
                        <label for="filter_kelas_id" class="font-weight-bold small text-uppercase text-muted mb-1">
                            Kelas
                        </label>
                        <select name="kelas_id" id="filter_kelas_id" class="form-control">
                            <option value="">-- Semua Kelas --</option>
                            @foreach ($kelas ?? [] as $k)
                                <option value="{{ $k->id }}" @selected((string) ($selectedKelasId ?? '') === (string) $k->id)>
                                    {{ $k->nama_kelas }} ({{ strtoupper($k->jenjang_kelas) }})
                                    @if ($k->tahunAjaran)
                                        — {{ $k->tahunAjaran->nama_tahun_ajaran }} Smt {{ strtoupper($k->tahunAjaran->semester) }}
                                    @endif
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex align-items-center">
                        <button type="submit" class="btn btn-primary font-weight-bold shadow-sm mr-2 flex-fill"
                            style="border-radius: 8px;">
                            <i class="fa fa-filter mr-1"></i> Filter
                        </button>
                        @if (($selectedTahunAjaranId ?? null) || ($selectedKaryawanNipy ?? null) || ($selectedTingkat ?? null) || ($selectedKelasId ?? null))
                            <a href="{{ route('anggota-t2q.index') }}" class="btn btn-light border shadow-sm"
                                style="border-radius: 8px;" title="Reset Filter">
                                <i class="fa fa-undo"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="main-card card mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover table-striped align-middle" id="myTable3">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Siswa</th>
                                <th>Kelas</th>
                                <th class="text-center" style="width: 11%;">Tingkat</th>
                                <th>Guru Pembimbing T2Q</th>
                                <th>Tahun Ajaran / Semester</th>
                                <th class="text-center" style="width: 13%;">Pendaftaran</th>
                                <th style="width: 13%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($anggotaT2q as $item)
                                @php
                                    $ak = $item->anggotaKelas;
                                    $siswaRow = $ak?->siswa;
                                    $kelasRow = $ak?->kelas;
                                    $tahunAjaranRow = $kelasRow?->tahunAjaran;
                                    $guruRow = $item->guruT2q;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800" style="font-size: 0.95rem;">
                                            {{ $siswaRow?->nama_lengkap ?? '-' }}
                                        </div>
                                        <div class="small text-muted" style="font-size: 11px;">
                                            NIS: <span class="font-weight-bold">{{ $ak?->siswa_nis ?? '-' }}</span>
                                            @if ($siswaRow?->nisn)
                                                • NISN: {{ $siswaRow->nisn }}
                                            @endif
                                            @if ($siswaRow?->status)
                                                • Status: {{ $siswaRow->status }}
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @if ($kelasRow)
                                            <div class="font-weight-bold text-slate-700">{{ $kelasRow->nama_kelas }}</div>
                                            <div class="small text-muted">
                                                {{ strtoupper((string) $kelasRow->jenjang_kelas) }} • Tingkat {{ $kelasRow->tingkatan_kelas }}
                                            </div>
                                        @else
                                            <span class="text-muted font-italic">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->tingkat_badge_class }} px-2 py-1" style="font-size: 0.78rem;">
                                            Tingkat {{ $item->tingkat }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="small">
                                            <i class="fa fa-user-tie text-primary mr-1" title="Guru Pembimbing T2Q"></i>
                                            <span class="font-weight-bold text-slate-800">
                                                {{ $guruRow?->nama_gelar ?? ('Guru #' . $item->karyawan_nipy) }}
                                            </span>
                                            <div class="text-muted" style="font-size: 11px;">
                                                NIPY: {{ $item->karyawan_nipy }}
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if ($tahunAjaranRow)
                                            <div class="font-weight-600 text-slate-700">
                                                {{ $tahunAjaranRow->nama_tahun_ajaran }}
                                            </div>
                                            <div class="small text-muted">
                                                Semester {{ strtoupper((string) $tahunAjaranRow->semester) }}
                                                @if ($tahunAjaranRow->is_active)
                                                    <span class="text-success font-weight-bold ml-1">• Aktif</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted font-italic">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->pendaftaran_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ $item->pendaftaran }}
                                        </span>
                                        @if ($item->keterangan)
                                            <div class="small text-muted mt-1" style="font-size: 11px;">
                                                {{ $item->keterangan }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <!-- Edit Button -->
                                            <a href="{{ route('anggota-t2q.edit', $item->id) }}"
                                                class="btn btn-sm btn-primary shadow-sm mx-1"
                                                style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                data-toggle="tooltip" data-placement="top" title="Edit Data">
                                                <i class="fa fa-edit" style="font-size: 0.75rem;"></i>
                                            </a>

                                            <!-- Delete Button -->
                                            <form action="{{ route('anggota-t2q.destroy', $item->id) }}" method="POST"
                                                class="delete-form d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    class="btn btn-sm btn-warning shadow-sm delete-button mx-1 p-0 text-white"
                                                    style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                    data-toggle="tooltip" data-placement="top" title="Hapus Data">
                                                    <i class="fa fa-trash" style="font-size: 0.75rem;"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
