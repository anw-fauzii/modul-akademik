@extends('layouts.appui')

@section('title')
    <title>Master Data Siswa</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-study" style="color: white;"></i>
                    </div>
                    <div>
                        Data Siswa
                        <div class="page-title-subheading">
                            Kelola data induk peserta didik, penempatan kelas, pembimbing, layanan ekstrakurikuler, dan jemputan.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('siswa.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH SISWA
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
                                <th>Siswa</th>
                                <th class="text-center" style="width: 10%;">JK</th>
                                <th>Kelas</th>
                                <th>Pembimbing & Layanan</th>
                                <th class="text-center" style="width: 11%;">Status</th>
                                <th style="width: 16%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($siswa as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800" style="font-size: 0.95rem;">
                                            {{ $item->nama_lengkap }}
                                        </div>
                                        <div class="small text-muted" style="font-size: 11px;">
                                            NIS: <span class="font-weight-bold">{{ $item->nis }}</span>
                                            @if ($item->nisn)
                                                • NISN: {{ $item->nisn }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        @if (strtoupper((string) $item->jenis_kelamin) === 'L')
                                            <span class="badge badge-info px-2 py-1">Laki-laki</span>
                                        @elseif (strtoupper((string) $item->jenis_kelamin) === 'P')
                                            <span class="badge text-white px-2 py-1" style="background-color: #ec4899;">Perempuan</span>
                                        @else
                                            <span class="badge badge-secondary px-2 py-1">{{ $item->jenis_kelamin_label }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($item->kelas)
                                            <div class="font-weight-bold text-slate-700">{{ $item->kelas->nama_kelas }}</div>
                                            <div class="small text-muted">{{ strtoupper($item->kelas->jenjang_kelas) }}</div>
                                        @else
                                            <span class="text-muted font-italic">Belum ditempatkan</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">
                                            <div>
                                                <i class="fa fa-user-tie text-primary mr-1"></i>
                                                {{ $item->karyawan?->nama_gelar ?? 'Pembimbing belum ditentukan' }}
                                            </div>
                                            <div class="text-muted">
                                                <i class="fa fa-medal text-warning mr-1"></i>
                                                {{ $item->ekstrakurikuler?->nama_ekstrakurikuler ?? 'Tanpa Ekskul' }}
                                                <span class="mx-1">•</span>
                                                <i class="fa fa-car text-info mr-1"></i>
                                                {{ $item->jemputan?->petugas?->nama_gelar ?? 'Tanpa Jemputan' }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->status_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ $item->status }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <!-- Detail Button -->
                                            <a href="{{ route('siswa.show', $item->nis) }}"
                                                class="btn btn-sm btn-info shadow-sm mx-1 text-white"
                                                style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                data-toggle="tooltip" data-placement="top" title="Detail Data">
                                                <i class="fa fa-eye" style="font-size: 0.75rem;"></i>
                                            </a>

                                            <!-- Edit Button -->
                                            <a href="{{ route('siswa.edit', $item->nis) }}"
                                                class="btn btn-sm btn-primary shadow-sm mx-1"
                                                style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                data-toggle="tooltip" data-placement="top" title="Edit Data">
                                                <i class="fa fa-edit" style="font-size: 0.75rem;"></i>
                                            </a>

                                            <!-- Delete Button -->
                                            <form action="{{ route('siswa.destroy', $item->nis) }}" method="POST"
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

