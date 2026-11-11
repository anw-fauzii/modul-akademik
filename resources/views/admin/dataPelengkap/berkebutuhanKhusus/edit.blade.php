@extends('layouts.appui')

@section('title')
    <title>Edit Berkebutuhan Khusus</title>
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
                        Perbarui Berkebutuhan Khusus
                        <div class="page-title-subheading">
                            Perbarui data kebutuhan khusus.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('berkebutuhan-khusus.index') }}" class="btn text-white shadow-sm"
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
                <form method="post" action="{{ route('berkebutuhan-khusus.update', $berkebutuhanKhusus->id) }}" id="editForm">
                    @csrf
                    @method('PUT')
                    @include('admin.dataPelengkap.berkebutuhanKhusus._form')
                </form>
            </div>
        </div>
    </div>
@endsection
