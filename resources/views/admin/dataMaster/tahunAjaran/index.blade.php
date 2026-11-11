@extends('layouts.appui')

@section('title')
    <title>Master Data Tahun Ajaran</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-date" style="color: white;"></i>
                    </div>
                    <div>
                        Tahun Ajaran
                        <div class="page-title-subheading">
                            Kelola data tahun ajaran dan status aktif semester.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('tahun-ajaran.create') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH BARU
                    </a>
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
                                <th>Tahun Ajaran</th>
                                <th>Semester</th>
                                <th class="text-center">Status</th>
                                <th style="width: 20%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $no = 1;
                            @endphp
                            @forelse ($tahunAjaran as $item)
                                <tr>
                                    <td>{{ $no++ }}</td>
                                    <td><strong>{{ $item->nama_tahun_ajaran }}</strong></td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ strtoupper($item->semester) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($item->is_active)
                                            <span class="badge badge-success">Aktif</span>
                                        @else
                                            <span class="badge badge-secondary">Nonaktif</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <!-- Toggle Active Status Form -->
                                            <form action="{{ route('tahun-ajaran.toggle-active', $item->id) }}" method="POST" class="d-inline">
                                                @csrf
                                                <button type="submit"
                                                    class="btn btn-sm {{ $item->is_active ? 'btn-success' : 'btn-outline-secondary' }} shadow-sm mx-1"
                                                    style="border-radius: 6px; height: 32px; padding: 0 10px; display: inline-flex; align-items: center; justify-content: center;"
                                                    data-toggle="tooltip" data-placement="top"
                                                    title="{{ $item->is_active ? 'Nonaktifkan Tahun Ajaran' : 'Jadikan Tahun Ajaran Aktif' }}">
                                                    <i class="fa {{ $item->is_active ? 'fa-check-circle' : 'fa-power-off' }} mr-1"></i>
                                                    <span style="font-size: 0.72rem;">{{ $item->is_active ? 'Aktif' : 'Aktifkan' }}</span>
                                                </button>
                                            </form>

                                            <!-- Edit Button -->
                                            <a href="{{ route('tahun-ajaran.edit', $item->id) }}"
                                                class="btn btn-sm btn-primary shadow-sm mx-1"
                                                style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                data-toggle="tooltip" data-placement="top" title="Edit Data">
                                                <i class="fa fa-edit" style="font-size: 0.5rem;"></i>
                                            </a>

                                            <!-- Delete Button -->
                                            <form action="{{ route('tahun-ajaran.destroy', $item->id) }}" method="POST"
                                                class="delete-form d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="button"
                                                    class="btn btn-sm btn-warning shadow-sm delete-button mx-1 p-0 text-white"
                                                    style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                    data-toggle="tooltip" data-placement="top" title="Hapus Data">
                                                    <i class="fa fa-trash" style="font-size: 0.5rem;"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="fa fa-info-circle mr-1"></i> Belum ada data tahun ajaran tersimpan.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
