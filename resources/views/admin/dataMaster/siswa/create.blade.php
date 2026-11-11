@extends('layouts.appui')

@section('title')
    <title>Tambah Siswa Baru</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-study" style="color: white;"></i>
                    </div>
                    <div>
                        Tambah Siswa Baru
                        <div class="page-title-subheading">
                            Lengkapi biodata siswa, penempatan kelas, layanan akademik, alamat, serta data orang tua/wali.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('siswa.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('siswa.store') }}" id="createForm" enctype="multipart/form-data">
                    @csrf
                    @include('admin.dataMaster.siswa._form')
                </form>
            </div>
        </div>
    </div>
@endsection

