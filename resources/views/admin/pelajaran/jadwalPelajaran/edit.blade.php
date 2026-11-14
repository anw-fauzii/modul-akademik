@extends('layouts.appui')

@section('title')
    <title>Edit Jadwal Pelajaran</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-date" style="color: white;"></i>
                    </div>
                    <div>
                        Edit Jadwal Pelajaran
                        <div class="page-title-subheading">
                            Perbarui slot jadwal: {{ $jadwalPelajaran->hari_label }} &bull; {{ $jadwalPelajaran->judul_tampil }}.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('jadwal-pelajaran.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('jadwal-pelajaran.update', $jadwalPelajaran->id) }}" id="editForm">
                    @csrf
                    @method('PUT')
                    @include('admin.pelajaran.jadwalPelajaran._form')
                </form>
            </div>
        </div>
    </div>
@endsection

