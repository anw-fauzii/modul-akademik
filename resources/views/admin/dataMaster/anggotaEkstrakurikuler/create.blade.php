@extends('layouts.appui')

@section('title')
    <title>Tambah Anggota Ekstrakurikuler</title>
@endsection

@section('content')
    @php
        $backUrl = isset($preselectedEkstrakurikulerId) && $preselectedEkstrakurikulerId
            ? route('ekstrakurikuler.show', $preselectedEkstrakurikulerId)
            : route('anggota-ekstrakurikuler.index');
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-medal" style="color: white;"></i>
                    </div>
                    <div>
                        Tambah Anggota Ekstrakurikuler
                        <div class="page-title-subheading">
                            Daftarkan satu atau beberapa siswa aktif melalui keanggotaan kelas ke ekstrakurikuler pada tahun ajaran terkait.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ $backUrl }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('anggota-ekstrakurikuler.store') }}" id="createForm">
                    @csrf
                    @if (isset($preselectedEkstrakurikulerId) && $preselectedEkstrakurikulerId)
                        <input type="hidden" name="redirect_to_ekstrakurikuler" value="{{ $preselectedEkstrakurikulerId }}">
                    @endif
                    @include('admin.dataMaster.anggotaEkstrakurikuler._form')
                </form>
            </div>
        </div>
    </div>
@endsection

