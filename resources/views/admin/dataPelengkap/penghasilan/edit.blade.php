@extends('layouts.appui')

@section('title')
    <title>Edit Penghasilan</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-cash" style="color: white;"></i>
                    </div>
                    <div>
                        Perbarui Penghasilan
                        <div class="page-title-subheading">
                            Perbarui data rentang penghasilan.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('penghasilan.index') }}" class="btn text-white shadow-sm"
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
                <form method="post" action="{{ route('penghasilan.update', $penghasilan->id) }}" id="editForm">
                    @csrf
                    @method('PUT')
                    @include('admin.dataPelengkap.penghasilan._form')
                </form>
            </div>
        </div>
    </div>
@endsection
