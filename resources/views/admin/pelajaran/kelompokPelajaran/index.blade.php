@extends('layouts.appui')

@section('title')
    <title>Kelompok Pelajaran</title>
@endsection

@section('content')
    @php
        $kelompokPelajaran = $kelompokPelajaran ?? collect();
        $filters = $filters ?? ['search' => ''];
        $totalKelompok = $totalKelompok ?? $kelompokPelajaran->count();
    @endphp

    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-bookmarks" style="color: white;"></i>
                    </div>
                    <div>
                        Kelompok Pelajaran
                        <div class="page-title-subheading">
                            Kelola pengelompokan mata pelajaran beserta kode identitas dan urutan tampil kurikulum.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('kelompok-pelajaran.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH KELOMPOK
                    </a>
                </div>
            </div>
        </div>

        <!-- FILTER & RINGKASAN -->
        <div class="filter-card">
            <div class="filter-card-header">
                <div class="filter-card-title">
                    <span class="filter-card-icon"><i class="fa fa-sliders-h"></i></span>
                    <span>Filter &amp; Pencarian Kelompok Pelajaran</span>
                    @if (($filters['search'] ?? '') !== '')
                        <span class="badge badge-primary">Filter Aktif</span>
                    @endif
                </div>
                <span class="filter-count-pill">
                    <i class="fa fa-layer-group text-primary"></i>
                    Total Kelompok: <strong>{{ $totalKelompok }}</strong>
                </span>
            </div>
            <div class="filter-card-body">
                <form method="GET" action="{{ route('kelompok-pelajaran.index') }}" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
                    <div class="sm:col-span-7 lg:col-span-8">
                        <label for="filter_search" class="filter-label">
                            Cari Kode / Kelompok Pelajaran
                        </label>
                        <input type="text" name="search" id="filter_search" class="filter-control"
                            placeholder="Ketik kode atau nama kelompok pelajaran..." value="{{ $filters['search'] ?? '' }}">
                    </div>

                    <div class="sm:col-span-5 lg:col-span-4 flex flex-wrap sm:justify-end gap-2">
                        <button type="submit" class="filter-btn filter-btn-primary flex-1 sm:flex-initial">
                            <i class="fa fa-search"></i> Tampilkan
                        </button>
                        <a href="{{ route('kelompok-pelajaran.index') }}" class="filter-btn filter-btn-secondary">
                            <i class="fa fa-undo"></i> Reset
                        </a>
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
                                <th style="width: 6%;">No</th>
                                <th class="text-center" style="width: 12%;">Urutan</th>
                                <th class="text-center" style="width: 16%;">Kode</th>
                                <th>Kelompok Pelajaran</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($kelompokPelajaran as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-light border font-weight-bold px-2.5 py-1 text-slate-700"
                                            style="font-size: 0.8rem;">
                                            #{{ $item->urutan }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-warning text-dark font-weight-bold px-2.5 py-1"
                                            style="font-size: 0.8rem; letter-spacing: 0.03em;">
                                            {{ $item->kode }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800" style="font-size: 0.95rem;">
                                            {{ $item->kelompok }}
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('kelompok-pelajaran.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('kelompok-pelajaran.destroy', $item->id) }}" method="POST" class="delete-form">
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

