@extends('layouts.appui')

@section('title')
    <title>Edit Agenda Sekolah</title>
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
                        Edit Agenda Sekolah
                        <div class="page-title-subheading">
                            Perbarui data agenda sekolah: {{ $agendaSekolah->nama_agenda }}.
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('agenda-sekolah.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('agenda-sekolah.update', $agendaSekolah->id) }}" id="editForm">
                    @csrf
                    @method('PUT')
                    @include('admin.informasi.agendaSekolah._form')
                </form>
            </div>
        </div>
    </div>
@endsection

