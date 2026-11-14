@extends('layouts.appui')

@section('title')
    <title>Tambah Jam Pelajaran</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-clock" style="color: white;"></i>
                    </div>
                    <div>
                        Tambah Jam Pelajaran
                        <div class="page-title-subheading">
                            Tambahkan slot jam ke baru beserta unit pendidikan, jam mulai, jam selesai, dan keterangan.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('jam-pelajaran.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('jam-pelajaran.store') }}" id="createForm">
                    @csrf
                    @include('admin.pelajaran.jamPelajaran._form')
                </form>
            </div>
        </div>
    </div>
@endsection

