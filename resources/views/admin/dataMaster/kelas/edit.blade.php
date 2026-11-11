@extends('layouts.appui')

@section('title')
    <title>Edit Kelas</title>
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
                        Edit Data Kelas
                        <div class="page-title-subheading">
                            Perbarui informasi data kelas: {{ $kelas->nama_kelas }}.
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
                <form method="post" action="{{ route('kelas.update', $kelas->id) }}" id="editForm">
                    @csrf
                    @method('PUT')
                    @include('admin.dataMaster.kelas._form')
                </form>
            </div>
        </div>
    </div>
@endsection
