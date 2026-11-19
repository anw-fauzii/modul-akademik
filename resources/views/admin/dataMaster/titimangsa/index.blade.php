@extends('layouts.appui')

@section('title')
    <title>Master Data Titimangsa Rapor</title>
@endsection

@section('content')
    @php
        $titimangsa = $titimangsa ?? collect();
        $filters = $filters ?? ['tahun_ajaran_id' => '', 'jenjang_kelas' => '', 'periode' => ''];
        $tahunAjaranOptions = $tahunAjaranOptions ?? collect();
        $jenjangOptions = $jenjangOptions ?? \App\Models\Admin\DataMaster\Titimangsa::JENJANG_OPTIONS;
        $periodeOptions = $periodeOptions ?? \App\Models\Admin\DataMaster\Titimangsa::PERIODE_OPTIONS;
        $hasActiveFilter = ($filters['tahun_ajaran_id'] ?? '') !== ''
            || ($filters['jenjang_kelas'] ?? '') !== ''
            || ($filters['periode'] ?? '') !== '';
    @endphp

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
                        Master Titimangsa &amp; Kepsek Rapor
                        <div class="page-title-subheading">
                            Atur tempat, tanggal titimangsa, serta Kepala Sekolah penandatangan rapor per Tahun Ajaran, Jenjang, dan Tingkatan Kelas.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('titimangsa.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH TITIMANGSA
                    </a>
                </div>
            </div>
        </div>

        <!-- INFO RINGKAS ATURAN PRIORITAS -->
        <div class="alert alert-light border shadow-sm mb-3 d-flex align-items-start" style="border-radius: 12px; gap: 0.75rem;">
            <span class="badge badge-warning text-dark mt-1">
                <i class="fa fa-info-circle mr-1"></i> Prioritas Otomatis
            </span>
            <div class="small text-muted">
                Buat 1 pengaturan dengan cakupan <strong>Semua Tingkat (*)</strong> sebagai standar setiap jenjang &amp; periode.
                Jika ada tingkat tertentu yang tanggal atau pejabatnya berbeda (misal khusus <strong>Tingkat 6 SD</strong> saat kelulusan), tambahkan pengaturan khusus tingkatan tersebut — sistem akan memprioritaskannya secara otomatis saat cetak PDF Rapor.
            </div>
        </div>

        <!-- FILTER BAR -->
        <div class="main-card card mb-3 shadow-sm border-0" style="border-radius: 12px;">
            <div class="card-body py-3">
                <form method="GET" action="{{ route('titimangsa.index') }}" class="row align-items-end">
                    <div class="col-md-4 mb-2 mb-md-0">
                        <label for="filter_tahun_ajaran_id" class="small font-weight-bold text-muted mb-1">Tahun Ajaran</label>
                        <select name="tahun_ajaran_id" id="filter_tahun_ajaran_id" class="form-control form-control-sm">
                            <option value="">Semua Tahun Ajaran</option>
                            @foreach ($tahunAjaranOptions as $ta)
                                <option value="{{ $ta->sqid }}" @selected((string) ($filters['tahun_ajaran_id'] ?? '') === (string) $ta->id)>
                                    {{ $ta->full_label }}{{ $ta->is_active ? ' (Aktif)' : '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-2 mb-md-0">
                        <label for="filter_jenjang_kelas" class="small font-weight-bold text-muted mb-1">Jenjang</label>
                        <select name="jenjang_kelas" id="filter_jenjang_kelas" class="form-control form-control-sm">
                            <option value="">Semua Jenjang</option>
                            @foreach ($jenjangOptions as $jenjang)
                                <option value="{{ $jenjang }}" @selected(strtoupper((string) ($filters['jenjang_kelas'] ?? '')) === $jenjang)>
                                    {{ $jenjang }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3 mb-2 mb-md-0">
                        <label for="filter_periode" class="small font-weight-bold text-muted mb-1">Periode Rapor</label>
                        <select name="periode" id="filter_periode" class="form-control form-control-sm">
                            <option value="">Semua Periode</option>
                            @foreach ($periodeOptions as $kode => $label)
                                <option value="{{ $kode }}" @selected(($filters['periode'] ?? '') === $kode)>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 d-flex" style="gap: 0.4rem;">
                        <button type="submit" class="btn btn-sm text-white font-weight-bold flex-fill"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 7px;">
                            <i class="fa fa-filter mr-1"></i> Filter
                        </button>
                        @if ($hasActiveFilter)
                            <a href="{{ route('titimangsa.index') }}" class="btn btn-sm btn-light border"
                                title="Reset Filter" style="border-radius: 7px;">
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
                                <th>Tahun Ajaran</th>
                                <th class="text-center" style="width: 10%;">Jenjang</th>
                                <th style="width: 15%;">Cakupan Tingkat</th>
                                <th class="text-center" style="width: 12%;">Periode</th>
                                <th>Titimangsa Rapor</th>
                                <th>Kepala Sekolah Penandatangan</th>
                                <th class="text-center" style="width: 10%;">Status</th>
                                <th style="width: 72px; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($titimangsa as $item)
                                @php
                                    $ta = $item->relationLoaded('tahunAjaran') ? $item->tahunAjaran : null;
                                    $kepsek = $item->relationLoaded('kepalaSekolah') ? $item->kepalaSekolah : null;
                                @endphp
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        @if ($ta)
                                            <div class="font-weight-bold text-slate-800">
                                                {{ $ta->nama_tahun_ajaran }}
                                            </div>
                                            <div class="small text-muted">
                                                Semester {{ strtoupper((string) $ta->semester) }}
                                                @if ($ta->is_active)
                                                    <span class="text-success font-weight-bold ml-1">&bull; Aktif</span>
                                                @endif
                                            </div>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->jenjang_badge_class }} px-2 py-1" style="font-size: 0.75rem;">
                                            {{ strtoupper((string) $item->jenjang_kelas) }}
                                        </span>
                                    </td>
                                    <td>
                                        @if ($item->is_specific_tingkatan)
                                            <span class="badge badge-pill badge-primary px-2 py-1" style="font-size: 0.74rem;">
                                                Khusus {{ $item->tingkatan_label }}
                                            </span>
                                        @else
                                            <span class="badge badge-pill badge-light border text-dark px-2 py-1" style="font-size: 0.74rem;">
                                                {{ $item->tingkatan_label }} (*)
                                            </span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->periode === 'mid' ? 'badge-info' : 'badge-dark' }} px-2 py-1" style="font-size: 0.73rem;">
                                            {{ $item->periode_label }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800">
                                            {{ $item->titimangsa_label }}
                                        </div>
                                        <div class="small text-muted">
                                            Kota: {{ $item->tempat }}
                                        </div>
                                    </td>
                                    <td>
                                        <div class="font-weight-bold text-slate-800">
                                            {{ $item->kepala_sekolah_nama_tampil }}
                                        </div>
                                        <div class="small text-muted">
                                            {{ $item->jabatan_kepala_sekolah }}
                                            @if ($kepsek)
                                                &bull; NIPY {{ $kepsek->nipy }}
                                            @elseif ($item->kepala_sekolah_nipy)
                                                &bull; NIPY {{ $item->kepala_sekolah_nipy }}
                                            @endif
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <form action="{{ route('titimangsa.toggle-status', $item) }}" method="POST" class="d-inline">
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
                                            <a href="{{ route('titimangsa.edit', $item) }}" class="action-dropdown-item" role="menuitem">
                                                <i class="fa fa-edit text-primary action-item-icon"></i>
                                                <span>Edit Data</span>
                                            </a>
                                            <div class="action-dropdown-divider"></div>
                                            <form action="{{ route('titimangsa.destroy', $item) }}" method="POST" class="delete-form">
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

