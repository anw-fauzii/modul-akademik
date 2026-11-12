@extends('layouts.appui')

@section('title')
    <title>Edit Anggota T2Q</title>
@endsection

@section('content')
    @php
        $siswaNisLabel = $anggotaT2q->relationLoaded('anggotaKelas')
            ? ($anggotaT2q->anggotaKelas?->siswa_nis ?? '-')
            : '-';
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-notebook" style="color: white;"></i>
                    </div>
                    <div>
                        Edit Anggota T2Q
                        <div class="page-title-subheading">
                            Perbarui guru pembimbing T2Q, tingkat, status pendaftaran, dan keterangan anggota kelas (NIS: {{ $siswaNisLabel }}).
                        </div>
                    </div>
                </div>

                <div class="page-title-actions">
                    <a href="{{ route('anggota-t2q.index') }}" class="btn text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                        <i class="fa fa-reply mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card">
            <div class="card-body">
                <form method="post" action="{{ route('anggota-t2q.update', $anggotaT2q->id) }}" id="editForm">
                    @csrf
                    @method('PUT')
                    @include('admin.dataMaster.anggotaT2q._form')
                </form>
            </div>
        </div>
    </div>
@endsection

