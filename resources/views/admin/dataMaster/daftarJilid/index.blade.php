@extends('layouts.appui')

@section('title')
    <title>Master Data Daftar Jilid T2Q</title>
@endsection

@section('content')
    @include('admin.dataMaster.partials._t2q_arab_styles')

    @php
        $daftarJilid = $daftarJilid ?? collect();
        $jenisOptions = $jenisOptions ?? collect();
        $selectedJenis = trim((string) ($selectedJenis ?? ''));
        $hasActiveFilter = $selectedJenis !== '';
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
                        Daftar Jilid T2Q
                        <div class="page-title-subheading">
                            Data master referensi tingkatan jilid T2Q beserta penulisan Arab dan jenis kelompoknya.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- MAIN CARD -->
        <div class="main-card card mb-3 shadow-sm border-0">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="mb-0 table table-hover table-striped align-middle" id="myTable3"
                        data-search-placeholder="Cari nama jilid atau jenis..."
                        data-empty-table="Belum ada data daftar jilid T2Q.">
                        <thead>
                            <tr>
                                <th style="width: 6%;">No</th>
                                <th class="text-center" style="width: 10%;">Urutan</th>
                                <th>Jilid (Latin)</th>
                                <th class="text-center" style="width: 30%;">Jilid (Arab)</th>
                                <th class="text-center" style="width: 16%;">Jenis</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($daftarJilid as $item)
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td class="text-center">
                                        <span class="badge badge-light border px-2 py-1 font-weight-bold text-slate-700"
                                            style="font-size: 0.78rem; font-variant-numeric: tabular-nums;">
                                            {{ $item->urutan }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="font-weight-bold text-slate-800">{{ $item->jilid_latin }}</span>
                                    </td>
                                    <td class="text-center">
                                        @if (filled($item->jilid_arab))
                                            <span class="t2q-arab-text" lang="ar">{{ $item->jilid_arab }}</span>
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $item->jenis_badge_class }} px-3 py-1" style="font-size: 0.75rem;">
                                            {{ ucfirst((string) $item->jenis) }}
                                        </span>
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

