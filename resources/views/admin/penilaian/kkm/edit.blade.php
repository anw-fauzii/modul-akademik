@extends('layouts.appui')

@section('title')
    <title>Edit KKM &amp; Rentang Predikat (SD)</title>
@endsection

@section('content')
    <div class="app-main__inner tp-form-shell">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-ribbon" style="color: white;"></i>
                    </div>
                    <div>
                        Edit KKM &amp; Rentang Predikat
                        <div class="page-title-subheading">
                            Perbarui KKM dan rentang predikat untuk {{ $kkm->mataPelajaran?->nama_mata_pelajaran }} (Kelas {{ $kkm->tingkatan_kelas }}).
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('penilaian-kkm.index') }}" class="btn btn-light shadow-sm"
                        style="border-radius: 10px; font-weight: 600;">
                        <i class="fa fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="tp-form-card">
            <form method="POST" action="{{ route('penilaian-kkm.update', $kkm) }}" id="kkmForm">
                @csrf
                @method('PUT')
                @include('admin.penilaian.kkm._form')
            </form>
        </div>
    </div>
@endsection

