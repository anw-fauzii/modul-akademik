@extends('layouts.appui')

@section('title')
    <title>Master Data Guru & Karyawan</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-users" style="color: white;"></i>
                    </div>
                    <div>
                        Data Guru & Karyawan
                        <div class="page-title-subheading">
                            Daftar tenaga pendidik dan kependidikan Yayasan Prima Insani beserta status sertifikasi.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="main-card card mb-3">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover table-striped" id="myTable3">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th>Nama Guru / Karyawan</th>
                                <th>Jabatan</th>
                                <th class="text-center" style="width: 10%;">JK</th>
                                <th class="text-center" style="width: 12%;">Unit</th>
                                <th class="text-center" style="width: 15%;">Sertifikasi</th>
                                <th class="text-center" style="width: 10%;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($karyawan as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <strong>{{ $item->nama_gelar }}</strong>
                                        @if ($item->nipy)
                                            <div class="text-muted small" style="font-size: 11px;">
                                                NIPY: {{ $item->nipy }}
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="font-weight-medium text-slate-700">
                                            {{ $item->jabatan ?? '-' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($item->jk === 'L' || strtolower($item->jk) === 'laki-laki')
                                            <span class="badge badge-info px-2 py-1">Laki-laki</span>
                                        @elseif ($item->jk === 'P' || strtolower($item->jk) === 'perempuan')
                                            <span class="badge text-white px-2 py-1" style="background-color: #ec4899;">Perempuan</span>
                                        @else
                                            <span class="badge badge-secondary px-2 py-1">{{ $item->jk ?? '-' }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($item->unit)
                                            <span class="badge badge-secondary font-weight-bold px-2 py-1">
                                                {{ $item->unit }}
                                            </span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if ($item->sertifikasi)
                                            <span class="badge badge-success px-2 py-1 font-weight-bold shadow-xs">
                                                <i class="fa fa-check-circle mr-1"></i> Tersertifikasi
                                            </span>
                                        @else
                                            <span class="badge badge-secondary px-2 py-1 text-muted" style="background-color: #f1f5f9; border: 1px solid #e2e8f0; color: #64748b !important;">
                                                <i class="fa fa-minus-circle mr-1"></i> Belum Sertifikasi
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('karyawan.show', $item->nipy) }}"
                                            class="btn btn-sm btn-primary shadow-sm"
                                            style="border-radius: 6px; padding: 4px 10px; display: inline-flex; align-items: center; justify-content: center;"
                                            data-toggle="tooltip" data-placement="top" title="Lihat Detail Profil Guru">
                                            <i class="fa fa-eye mr-1" style="font-size: 0.75rem;"></i>
                                            <span style="font-size: 0.75rem;">Detail</span>
                                        </a>
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
