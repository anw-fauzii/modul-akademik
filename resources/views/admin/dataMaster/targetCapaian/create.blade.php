@extends('layouts.appui')

@section('title')
    <title>Tambah Target Capaian T2Q</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-target" style="color: white;"></i>
                    </div>
                    <div>
                        Tambah Target Capaian T2Q
                        <div class="page-title-subheading">
                            Tetapkan target capaian bacaan jilid dan/atau hafalan surah per tahun ajaran dan tingkat.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('target-capaian.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('target-capaian.store') }}" id="createForm">
                    @csrf
                    @include('admin.dataMaster.targetCapaian._form')
                </form>
            </div>
        </div>
    </div>
@endsection

