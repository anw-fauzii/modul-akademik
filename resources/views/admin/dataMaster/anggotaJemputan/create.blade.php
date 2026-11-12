@extends('layouts.appui')

@section('title')
    <title>Tambah Anggota Jemputan</title>
@endsection

@section('content')
    @php
        $backUrl = isset($preselectedJemputanId) && $preselectedJemputanId
            ? route('jemputan.show', $preselectedJemputanId)
            : route('anggota-jemputan.index');
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-car" style="color: white;"></i>
                    </div>
                    <div>
                        Tambah Anggota Jemputan
                        <div class="page-title-subheading">
                            Daftarkan satu atau beberapa siswa aktif melalui keanggotaan kelas ke layanan jemputan pada tahun ajaran terkait.
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
                <form method="post" action="{{ route('anggota-jemputan.store') }}" id="createForm">
                    @csrf
                    @if (isset($preselectedJemputanId) && $preselectedJemputanId)
                        <input type="hidden" name="redirect_to_jemputan" value="{{ $preselectedJemputanId }}">
                    @endif
                    @include('admin.dataMaster.anggotaJemputan._form')
                </form>
            </div>
        </div>
    </div>
@endsection

