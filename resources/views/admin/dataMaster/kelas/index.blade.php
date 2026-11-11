@extends('layouts.appui')

@section('title')
    <title>Master Data Kelas</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-id" style="color: white;"></i>
                    </div>
                    <div>
                        Data Kelas (Rombel)
                        <div class="page-title-subheading">
                            Kelola data rombongan belajar, wali kelas, guru pendamping, dan jenjang pendidikan.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('kelas.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH KELAS
                    </a>
                </div>
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
                                <th>Nama Kelas</th>
                                <th class="text-center" style="width: 12%;">Jenjang</th>
                                <th>Tahun Ajaran</th>
                                <th>Wali Kelas & Pendamping</th>
                                <th class="text-center" style="width: 13%;">Jumlah Siswa</th>
                                <th style="width: 15%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kelas as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800" style="font-size: 0.95rem;">
                                            {{ $item->nama_kelas }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $badgeClass = match (strtoupper($item->jenjang_kelas)) {
                                                'SD' => 'badge-success',
                                                'SMP' => 'badge-primary',
                                                'TK', 'PAUD' => 'badge-warning text-dark',
                                                'SMA', 'SMK' => 'badge-info',
                                                default => 'badge-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $badgeClass }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ strtoupper($item->jenjang_kelas) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($item->tahunAjaran)
                                            <div class="font-weight-600 text-slate-700">
                                                {{ $item->tahunAjaran->nama_tahun_ajaran }}
                                            </div>
                                            <div class="small text-muted">
                                                Semester {{ strtoupper($item->tahunAjaran->semester) }}
                                                @if ($item->tahunAjaran->is_active)
                                                    <span class="text-success font-weight-bold ml-1">• Aktif</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted italic">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="d-flex flex-column gap-1">
                                            <!-- Wali Kelas -->
                                            <div class="small">
                                                <i class="fa fa-user-tie text-primary mr-1" title="Wali Kelas"></i>
                                                @if ($item->waliKelas)
                                                    <strong>{{ $item->waliKelas->nama_gelar }}</strong>
                                                    <span class="text-muted font-italic" style="font-size: 11px;">(Wali Kelas)</span>
                                                @else
                                                    <span class="text-muted font-italic">Wali Kelas belum ditentukan</span>
                                                @endif
                                            </div>
                                            <!-- Guru Pendamping -->
                                            <div class="small text-muted">
                                                <i class="fa fa-user-friends text-info mr-1" title="Guru Pendamping"></i>
                                                @if ($item->pendamping)
                                                    <span>{{ $item->pendamping->nama_gelar }}</span>
                                                    <span class="font-italic" style="font-size: 11px;">(Pendamping)</span>
                                                @else
                                                    <span class="font-italic">Pendamping belum ditentukan</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $jumlahSiswa = (int) ($item->anggota_kelas_count ?? 0);
                                        @endphp
                                        @if ($jumlahSiswa > 0)
                                            <a href="{{ route('anggota-kelas.index', ['kelas_id' => $item->id]) }}"
                                                class="badge badge-primary px-2 py-1 text-decoration-none"
                                                style="font-size: 0.78rem;"
                                                data-toggle="tooltip" data-placement="top"
                                                title="Lihat {{ $jumlahSiswa }} siswa di kelas {{ $item->nama_kelas }}">
                                                <i class="fa fa-users mr-1"></i> {{ $jumlahSiswa }} Siswa
                                            </a>
                                        @else
                                            <a href="{{ route('anggota-kelas.create', ['kelas_id' => $item->id]) }}"
                                                class="badge badge-secondary px-2 py-1 text-decoration-none"
                                                style="font-size: 0.78rem;"
                                                data-toggle="tooltip" data-placement="top"
                                                title="Belum ada anggota, klik untuk tambah siswa ke kelas {{ $item->nama_kelas }}">
                                                <i class="fa fa-user-plus mr-1"></i> 0 Siswa
                                            </a>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <!-- Edit Button -->
                                            <a href="{{ route('kelas.edit', $item->id) }}"
                                                class="btn btn-sm btn-primary shadow-sm mx-1"
                                                style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                data-toggle="tooltip" data-placement="top" title="Edit Data">
                                                <i class="fa fa-edit" style="font-size: 0.75rem;"></i>
                                            </a>

                                            <!-- Delete Button -->
                                            <form action="{{ route('kelas.destroy', $item->id) }}" method="POST"
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
