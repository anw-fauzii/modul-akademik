@extends('layouts.appui')

@section('title')
    <title>Master Identitas Sekolah</title>
@endsection

@section('content')
    @php
        $sekolahList = $sekolahList ?? collect();
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-culture" style="color: white;"></i>
                    </div>
                    <div>
                        Master Identitas Sekolah
                        <div class="page-title-subheading">
                            Kelola profil satuan pendidikan (Nama Sekolah, NPSN, NSS, Alamat, dan Kontak) per jenjang untuk kebutuhan cetak Rapor PDF.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('sekolah.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH IDENTITAS SEKOLAH
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover table-striped align-middle" id="myTable3">
                        <thead>
                            <tr>
                                <th style="width: 5%;">No</th>
                                <th class="text-center" style="width: 10%;">Jenjang</th>
                                <th>Nama Sekolah &amp; Yayasan</th>
                                <th style="width: 16%;">NPSN / NSS</th>
                                <th>Alamat &amp; Kontak</th>
                                <th class="text-center" style="width: 10%;">Status</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($sekolahList as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->jenjang_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ strtoupper((string) $item->jenjang_kelas) }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800">{{ $item->nama_sekolah }}</div>
                                        @if (filled($item->nama_yayasan))
                                            <div class="small text-muted">{{ $item->nama_yayasan }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="small">
                                            <strong>NPSN:</strong> {{ $item->npsn ?: '-' }}
                                        </div>
                                        <div class="small text-muted">
                                            <strong>NSS:</strong> {{ $item->nss ?: '-' }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="small text-slate-700">{{ $item->alamat_lengkap }}</div>
                                        @if (filled($item->telepon) || filled($item->email) || filled($item->website))
                                            <div class="small text-muted mt-1">
                                                {{ collect([$item->telepon, $item->email, $item->website])->filter()->implode(' • ') }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('sekolah.toggle-status', $item->id) }}" method="POST" class="d-inline">
                                            @csrf
                                            <button type="submit"
                                                class="btn btn-sm {{ $item->status ? 'btn-success' : 'btn-secondary' }} px-2 py-1"
                                                style="font-size: 0.72rem; border-radius: 999px;"
                                                title="Klik untuk ubah status aktif">
                                                {{ $item->status ? 'Aktif' : 'Nonaktif' }}
                                            </button>
                                        </form>
                                    </td>
                                    <td class="text-center">
                                        <x-action-dropdown>
                                            <a href="{{ route('sekolah.edit', $item->id) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('sekolah.destroy', $item->id) }}" method="POST" class="delete-form">
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

