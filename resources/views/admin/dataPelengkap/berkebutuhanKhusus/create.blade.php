@extends('layouts.appui')

@section('title')
    <title>Tambah Berkebutuhan Khusus</title>
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
                        Tambah Berkebutuhan Khusus
                        <div class="page-title-subheading">
                            Tambah data kebutuhan khusus baru.
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
            <div class="card-body">
                <form method="post" action="{{ route('berkebutuhan-khusus.store') }}" id="createForm">
                    @csrf
                    @include('admin.dataPelengkap.berkebutuhanKhusus._form')
                </form>
            </div>
        </div>
    </div>
@endsection
