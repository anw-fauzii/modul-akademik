@extends('layouts.appui')

@section('title')
    <title>Master Data Jemputan</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <!-- PAGE TITLE & BREADCRUMB -->
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-car" style="color: white;"></i>
                    </div>
                    <div>
                        Data Jemputan
                        <div class="page-title-subheading">
                            Kelola data layanan antar-jemput siswa, petugas/driver, tahun ajaran, dan tarif PP maupun setengah perjalanan.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('jemputan.create') }}" class="btn text-white shadow-sm font-weight-bold"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-plus-circle mr-1"></i> TAMBAH JEMPUTAN
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
                                <th>Petugas / Driver</th>
                                <th>Tahun Ajaran</th>
                                <th class="text-right" style="width: 16%;">Harga PP</th>
                                <th class="text-right" style="width: 16%;">Harga Setengah</th>
                                <th style="width: 15%; text-align: center;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($jemputan as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>
                                        <div class="small">
                                            <i class="fa fa-user-tie text-primary mr-1" title="Petugas / Driver Jemputan"></i>
                                            @if ($item->petugas)
                                                <strong>{{ $item->petugas->nama_gelar }}</strong>
                                                <div class="text-muted" style="font-size: 11px;">
                                                    NIPY: {{ $item->karyawan_nipy }}
                                                </div>
                                            @else
                                                <span class="text-muted font-italic">NIPY: {{ $item->karyawan_nipy }}</span>
                                            @endif
                                        </div>
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
                                    @foreach ([['amount' => $item->harga_pp, 'formatted' => $item->harga_pp_formatted], ['amount' => $item->harga_setengah, 'formatted' => $item->harga_setengah_formatted]] as $tarif)
                                        <td class="text-right">
                                            @if ((int) $tarif['amount'] <= 0)
                                                <span class="badge badge-success px-2 py-1" style="font-size: 0.75rem;">
                                                    {{ $tarif['formatted'] }}
                                                </span>
                                            @else
                                                <span class="font-weight-bold text-slate-800">
                                                    {{ $tarif['formatted'] }}
                                                </span>
                                            @endif
                                        </td>
                                    @endforeach
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <!-- Edit Button -->
                                            <a href="{{ route('jemputan.edit', $item->id) }}"
                                                class="btn btn-sm btn-primary shadow-sm mx-1"
                                                style="border-radius: 6px; width: 32px; height: 32px; display: inline-flex; align-items: center; justify-content: center;"
                                                data-toggle="tooltip" data-placement="top" title="Edit Data">
                                                <i class="fa fa-edit" style="font-size: 0.75rem;"></i>
                                            </a>

                                            <!-- Delete Button -->
                                            <form action="{{ route('jemputan.destroy', $item->id) }}" method="POST"
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

