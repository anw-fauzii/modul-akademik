@extends('layouts.appui')

@section('title')
    <title>Edit Tujuan Pembelajaran (TP)</title>
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
                        Edit Tujuan Pembelajaran (TP)
                        <div class="page-title-subheading">
                            Perbarui butir Tujuan Pembelajaran: <span class="font-weight-semibold text-slate-700">{{ $tujuanPembelajaran->kode_tp }}</span>.
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
            <form method="post" action="{{ route('tujuan-pembelajaran.update', $tujuanPembelajaran->id) }}" id="editForm">
                @csrf
                @method('PUT')
                @include('admin.tujuanPembelajaran.tujuanPembelajaran._form')
            </form>
        </div>
    </div>
@endsection
