@extends('layouts.appui')

@section('title')
    <title>Edit Anggota Kelas</title>
@endsection

@section('content')
    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-users" style="color: white;"></i>
                    </div>
                    <div>
                        Edit Anggota Kelas
                        <div class="page-title-subheading">
                            Perbarui penempatan kelas dan status pendaftaran siswa (NIS: {{ $anggotaKelas->siswa_nis }}).
                        </div>
                    </div>
                </div>

@php
    $redirectKelasId = old('redirect_to_kelas', $fromKelasId ?? null);
    $backUrl = is_numeric($redirectKelasId) && (int) $redirectKelasId > 0
        ? route('kelas.show', (int) $redirectKelasId)
        : ($anggotaKelas->akademik_master_kelas_id
            ? route('kelas.show', $anggotaKelas->akademik_master_kelas_id)
            : route('kelas.index'));
@endphp

                <div class="page-title-actions">
                    <a href="{{ $backUrl }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('anggota-kelas.update', $anggotaKelas->id) }}" id="editForm">
                    @csrf
                    @method('PUT')
                    @if (is_numeric($redirectKelasId) && (int) $redirectKelasId > 0)
                        <input type="hidden" name="redirect_to_kelas" value="{{ (int) $redirectKelasId }}">
                    @endif
                    @include('admin.dataMaster.anggotaKelas._form')
                </form>
            </div>
        </div>
    </div>
@endsection

