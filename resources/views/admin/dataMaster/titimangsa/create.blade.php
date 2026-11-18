@extends('layouts.appui')

@section('title')
    <title>Tambah Titimangsa Rapor</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-date" style="color: white;"></i>
                    </div>
                    <div>
                        Tambah Titimangsa Rapor
                        <div class="page-title-subheading">
                            Tambahkan pengaturan tanggal titimangsa dan Kepala Sekolah penandatangan rapor per jenjang/tingkatan.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('titimangsa.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('titimangsa.store') }}" id="createForm">
                    @csrf
                    @include('admin.dataMaster.titimangsa._form')
                </form>
            </div>
        </div>
    </div>
@endsection

