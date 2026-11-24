@extends('layouts.appui')

@section('title')
    <title>Tambah Prestasi Siswa</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="fa fa-trophy" style="color: white;"></i>
                    </div>
                    <div>
                        Tambah Prestasi Siswa Baru
                        <div class="page-title-subheading">
                            Catat pencapaian akademik atau non-akademik siswa beserta keterangan beregu dan foto dokumentasi.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('prestasi-siswa.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card shadow-sm border-0">
            <div class="card-body">
                <form method="post" action="{{ route('prestasi-siswa.store') }}" enctype="multipart/form-data" id="createPrestasiForm">
                    @csrf
                    @include('admin.prestasi.prestasiSiswa._form')
                </form>
            </div>
        </div>
    </div>
@endsection

