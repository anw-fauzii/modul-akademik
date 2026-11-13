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
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($tahunAjaran as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
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
                                        <x-action-dropdown>
                                            <form action="{{ route('tahun-ajaran.toggle-active', $item->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="action-dropdown-item" role="menuitem">
                                                    <i class="fa {{ $item->is_active ? 'fa-check-circle text-success' : 'fa-power-off text-secondary' }} action-item-icon"></i>
                                                    <span>{{ $item->is_active ? 'Nonaktifkan Tahun Ajaran' : 'Jadikan Tahun Ajaran Aktif' }}</span>
                                                </button>
                                            </form>
                                            <a href="{{ route('tahun-ajaran.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('tahun-ajaran.destroy', $item->id) }}" method="POST" class="delete-form">
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
