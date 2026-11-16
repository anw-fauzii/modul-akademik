@extends('layouts.appui')

@section('title')
    <title>Tambah KKM &amp; Rentang Predikat (SD)</title>
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
                        Tambah KKM &amp; Rentang Predikat
                        <div class="page-title-subheading">
                            Tetapkan batas ketuntasan minimal (KKM) beserta rentang nilai untuk predikat rapor SD.
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
            <form method="POST" action="{{ route('penilaian-kkm.store') }}" id="kkmForm">
                @csrf
                @include('admin.penilaian.kkm._form')
            </form>
        </div>
    </div>
@endsection

