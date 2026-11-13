@extends('layouts.appui')

@section('title')
    <title>Master Data Ekstrakurikuler</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-medal" style="color: white;"></i>
                    </div>
                    <div>
                        Data Ekstrakurikuler
                        <div class="page-title-subheading">
                            Kelola data kegiatan ekstrakurikuler, unit pendidikan, pembina/pelatih, dan biaya kegiatan.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('ekstrakurikuler.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH EKSTRAKURIKULER
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
                                <th>Nama Ekstrakurikuler</th>
                                <th class="text-center" style="width: 12%;">Unit</th>
                                <th>Tahun Ajaran</th>
                                <th>Pembina / Pelatih</th>
                                <th class="text-right" style="width: 14%;">Biaya</th>
                                <th class="text-center" style="width: 13%;">Jumlah Siswa</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($ekstrakurikuler as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <a href="{{ route('ekstrakurikuler.show', $item->id) }}"
                                            class="font-weight-bold text-slate-800 text-decoration-none"
                                            style="font-size: 0.95rem;"
                                            title="Detail &amp; Anggota Ekstrakurikuler {{ $item->nama_ekstrakurikuler }}">
                                            {{ $item->nama_ekstrakurikuler }}
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->unit_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ strtoupper($item->unit) }}
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
                                        <div class="small">
                                            <i class="fa fa-user-tie text-primary mr-1" title="Pembina / Pelatih"></i>
                                            @if ($item->pembina)
                                                <strong>{{ $item->pembina->nama_gelar }}</strong>
                                            @else
                                                <span class="text-muted font-italic">Pembina belum ditentukan</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-right">
                                        @if ((int) $item->biaya <= 0)
                                            <span class="badge badge-success px-2 py-1" style="font-size: 0.75rem;">
                                                {{ $item->biaya_formatted }}
                                            </span>
                                        @else
                                            <span class="font-weight-bold text-slate-800">
                                                {{ $item->biaya_formatted }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $jumlahSiswa = (int) ($item->anggota_ekstrakurikuler_count ?? 0);
                                        @endphp
                                        <a href="{{ route('ekstrakurikuler.show', $item->id) }}"
                                            class="badge {{ $jumlahSiswa > 0 ? 'badge-primary' : 'badge-secondary' }} px-2 py-1 text-decoration-none"
                                            style="font-size: 0.78rem;"
                                            data-toggle="tooltip" data-placement="top"
                                            title="{{ $jumlahSiswa > 0 ? "Lihat {$jumlahSiswa} siswa & tambah anggota ekskul {$item->nama_ekstrakurikuler}" : "Belum ada anggota, klik untuk tambah siswa ke ekskul {$item->nama_ekstrakurikuler}" }}">
                                            <i class="fa {{ $jumlahSiswa > 0 ? 'fa-users' : 'fa-user-plus' }} mr-1"></i>
                                            {{ $jumlahSiswa }} Siswa
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('ekstrakurikuler.show', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-eye text-info action-item-icon"></i>
                                                <span>Detail &amp; Anggota Ekskul</span>
                                            </a>
                                            <a href="{{ route('ekstrakurikuler.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('ekstrakurikuler.destroy', $item->id) }}" method="POST" class="delete-form">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button" class="action-dropdown-item action-dropdown-item--danger delete-button" role="menuitem">
                                                    <i class="fa fa-trash action-item-icon"></i>
                                                    <span>Hapus Data</span>
                                                </button>
                                            </form>
                                        </x-action-dropdown>
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

