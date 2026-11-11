@extends('layouts.appui')

@section('title')
    <title>Tambah Kelas Baru</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-id" style="color: white;"></i>
                    </div>
                    <div>
                        Tambah Kelas Baru
                        <div class="page-title-subheading">
                            Tambahkan data rombel/kelas baru dan tentukan wali kelas serta guru pendamping.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('kelas.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('kelas.store') }}" id="createForm">
                    @csrf
                    @include('admin.dataMaster.kelas._form')
                </form>
            </div>
        </div>
    </div>
@endsection
