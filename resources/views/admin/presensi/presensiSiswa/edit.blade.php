@extends('layouts.appui')

@section('title')
    <title>Edit Presensi Siswa - SIPIT Akademik</title>
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
                        Edit Presensi Siswa
                        <div class="page-title-subheading">
                            Perbarui status kehadiran, jam masuk, atau keterlambatan siswa.
                        </div>
                    </div>
                </div>
                <div class="page-title-actions">
                    <a href="{{ route('presensi-siswa.index') }}" class="btn btn-light shadow-sm" style="border-radius: 10px;">
                        <i class="fa fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="main-card card shadow-sm border-0" style="border-radius: 16px;">
            <div class="card-body p-6">
                <form action="{{ route('presensi-siswa.update', $presensiSiswa) }}" method="POST">
                    @csrf
                    @method('PUT')

                    @include('admin.presensi.presensiSiswa._form')

                    <div class="mt-6 pt-4 border-t border-slate-100 flex justify-end gap-3">
                        <a href="{{ route('presensi-siswa.index') }}" class="btn btn-light" style="border-radius: 10px;">
                            Batal
                        </a>
                        <button type="submit" class="btn text-white shadow-sm"
                            style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 10px;">
                            <i class="fa fa-save mr-1"></i> Perbarui Presensi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

