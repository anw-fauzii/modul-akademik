@extends('layouts.appui')

@section('title')
    <title>Edit Pekerjaan</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-portfolio" style="color: white;"></i>
                    </div>
                    <div>
                        Perbarui Pekerjaan
                        <div class="page-title-subheading">
                            Perbarui data pekerjaan.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('pekerjaan.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-header">
                Update Data
            </div>
            <div class="card-body">
                <form method="post" action="{{ route('pekerjaan.update', $pekerjaan->id) }}" id="editForm">
                    @csrf
                    @method('PUT')
                    @include('admin.dataPelengkap.pekerjaan._form')
                </form>
            </div>
        </div>
    </div>
@endsection
