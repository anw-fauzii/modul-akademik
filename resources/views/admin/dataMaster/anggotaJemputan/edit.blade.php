@extends('layouts.appui')

@section('title')
    <title>Edit Anggota Jemputan</title>
@endsection

@section('content')
    @php
        $siswaNisLabel = $anggotaJemputan->relationLoaded('anggotaKelas')
            ? ($anggotaJemputan->anggotaKelas?->siswa_nis ?? '-')
            : '-';
        $redirectJemputanId = old('redirect_to_jemputan', $fromJemputanId ?? null);
        $backUrl = is_numeric($redirectJemputanId) && (int) $redirectJemputanId > 0
            ? route('jemputan.show', (int) $redirectJemputanId)
            : ($anggotaJemputan->akademik_master_jemputan_id
                ? route('jemputan.show', $anggotaJemputan->akademik_master_jemputan_id)
                : route('jemputan.index'));
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-car" style="color: white;"></i>
                    </div>
                    <div>
                        Edit Anggota Jemputan
                        <div class="page-title-subheading">
                            Perbarui layanan jemputan, status pendaftaran, dan keterangan anggota kelas (NIS: {{ $siswaNisLabel }}).
                        </div>
                    </div>
                </div>

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
                <form method="post" action="{{ route('anggota-jemputan.update', $anggotaJemputan->id) }}" id="editForm">
                    @csrf
                    @method('PUT')
                    @if (is_numeric($redirectJemputanId) && (int) $redirectJemputanId > 0)
                        <input type="hidden" name="redirect_to_jemputan" value="{{ (int) $redirectJemputanId }}">
                    @endif
                    @include('admin.dataMaster.anggotaJemputan._form')
                </form>
            </div>
        </div>
    </div>
@endsection

