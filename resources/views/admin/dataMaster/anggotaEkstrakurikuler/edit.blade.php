@extends('layouts.appui')

@section('title')
    <title>Edit Anggota Ekstrakurikuler</title>
@endsection

@section('content')
    @php
        $siswaNisLabel = $anggotaEkstrakurikuler->relationLoaded('anggotaKelas')
            ? ($anggotaEkstrakurikuler->anggotaKelas?->siswa_nis ?? '-')
            : '-';
        $redirectEkstrakurikulerId = old('redirect_to_ekstrakurikuler', $fromEkstrakurikulerId ?? null);
        $backUrl = is_numeric($redirectEkstrakurikulerId) && (int) $redirectEkstrakurikulerId > 0
            ? route('ekstrakurikuler.show', (int) $redirectEkstrakurikulerId)
            : ($anggotaEkstrakurikuler->akademik_master_ekstrakuler_id
                ? route('ekstrakurikuler.show', $anggotaEkstrakurikuler->akademik_master_ekstrakuler_id)
                : route('ekstrakurikuler.index'));
    @endphp

    <div class="app-main__inner">
        <div class="app-page-title">
            <div class="page-title-wrapper">
                <div class="page-title-heading">
                    <div class="page-title-icon text-white shadow-sm"
                        style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                        <i class="pe-7s-medal" style="color: white;"></i>
                    </div>
                    <div>
                        Edit Anggota Ekstrakurikuler
                        <div class="page-title-subheading">
                            Perbarui ekstrakurikuler, status pendaftaran, dan keterangan anggota kelas (NIS: {{ $siswaNisLabel }}).
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
                <form method="post" action="{{ route('anggota-ekstrakurikuler.update', $anggotaEkstrakurikuler->id) }}" id="editForm">
                    @csrf
                    @method('PUT')
                    @if (is_numeric($redirectEkstrakurikulerId) && (int) $redirectEkstrakurikulerId > 0)
                        <input type="hidden" name="redirect_to_ekstrakurikuler" value="{{ (int) $redirectEkstrakurikulerId }}">
                    @endif
                    @include('admin.dataMaster.anggotaEkstrakurikuler._form')
                </form>
            </div>
        </div>
    </div>
@endsection

