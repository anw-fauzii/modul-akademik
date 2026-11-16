@extends('layouts.appui')

@section('title')
    <title>Tambah Tujuan Pembelajaran (TP)</title>
@endsection

@section('content')
    <div class="app-main__inner tp-form-shell">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-check" style="color: white;"></i>
                    </div>
                    <div>
                        Tambah Tujuan Pembelajaran (TP)
                        <div class="page-title-subheading">
                            Tambahkan rumusan Tujuan Pembelajaran baru per semester, mata pelajaran, dan tingkatan kelas.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('tujuan-pembelajaran.index') }}" class="btn btn-light shadow-sm"
                        style="border-radius: 10px; font-weight: 600;">
                        <i class="fa fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="tp-form-card">
            <form method="post" action="{{ route('tujuan-pembelajaran.store') }}" id="createForm">
                @csrf
                @include('admin.tujuanPembelajaran.tujuanPembelajaran._form')
            </form>
        </div>
    </div>
@endsection
