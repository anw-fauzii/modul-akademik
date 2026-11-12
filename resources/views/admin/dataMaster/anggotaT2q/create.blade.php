@extends('layouts.appui')

@section('title')
    <title>Tambah Anggota T2Q</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-notebook" style="color: white;"></i>
                    </div>
                    <div>
                        Tambah Anggota T2Q
                        <div class="page-title-subheading">
                            Daftarkan satu atau beberapa siswa aktif melalui keanggotaan kelas beserta guru pembimbing T2Q dan tingkatnya.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('anggota-t2q.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('anggota-t2q.store') }}" id="createForm">
                    @csrf
                    @include('admin.dataMaster.anggotaT2q._form')
                </form>
            </div>
        </div>
    </div>
@endsection

