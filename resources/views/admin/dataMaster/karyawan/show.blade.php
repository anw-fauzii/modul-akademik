@extends('layouts.appui')

@section('title')
    <title>Detail Guru & Karyawan - {{ $karyawan->nama_gelar }}</title>
@endsection

@section('content')
<style>
    .profile-hero {
        background: linear-gradient(135deg, #0f172a 0%, #1e293b 60%, #334155 100%);
        border-radius: 16px;
        padding: 28px;
        color: #ffffff;
        position: relative;
        overflow: hidden;
        box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.25);
    }
    .profile-avatar {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        object-fit: cover;
        border: 4px solid rgba(255, 255, 255, 0.25);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }
    .profile-avatar-fallback {
        width: 100px;
        height: 100px;
        border-radius: 50%;
        background: linear-gradient(135deg, #ea580c 0%, #d97706 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 36px;
        font-weight: 700;
        color: #ffffff;
        border: 4px solid rgba(255, 255, 255, 0.25);
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
    }
    .info-group-title {
        font-size: 13px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #ea580c;
        margin-bottom: 16px;
        padding-bottom: 8px;
        border-bottom: 2px solid #fed7aa;
        display: flex;
        align-items: center;
    }
    .detail-label {
        font-size: 12px;
        color: #64748b;
        font-weight: 600;
        margin-bottom: 2px;
    }
    .detail-value {
        font-size: 14px;
        color: #1e293b;
        font-weight: 600;
        margin-bottom: 14px;
    }
</style>

<div class="app-main__inner">
    <!-- PAGE TITLE & BREADCRUMB -->
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon text-white shadow-sm"
                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                    <i class="pe-7s-id" style="color: white;"></i>
                </div>
                <div>
                    Detail Guru & Karyawan
                    <div class="page-title-subheading">
                        Informasi lengkap biodata, jabatan, dan status sertifikasi tenaga pendidik.
                    </div>
                </div>
            </div>

            <div class="page-title-actions">
                <a href="{{ route('karyawan.index') }}" class="btn text-white shadow-sm"
                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 8px;">
                    <i class="fa fa-reply mr-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>

    <!-- PROFILE HERO BANNER -->
    <div class="profile-hero mb-4">
        <div class="row align-items-center">
            <div class="col-md-auto text-center mb-3 mb-md-0">
                @if ($karyawan->user && $karyawan->foto)
                    <img src="{{ $karyawan->foto_url }}" alt="{{ $karyawan->nama_lengkap }}" class="profile-avatar d-block mx-auto">
                @else
                    <div class="profile-avatar-fallback mx-auto">   
                        {{ strtoupper(substr($karyawan->nama_lengkap, 0, 1)) }}
                    </div>
                @endif
            </div>
            <div class="col-md text-center text-md-left">
                <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                    <span class="badge badge-info px-2 py-1 font-weight-bold">
                        {{ $karyawan->unit ?? 'Yayasan' }}
                    </span>
                    @php
                        $badge = $karyawan->status_pegawai_badge;
                    @endphp
                    <span class="badge {{ $badge['class'] ?? 'badge-secondary' }} px-2 py-1 font-weight-bold">
                        Status: {{ $badge['label'] ?? $karyawan->status_pegawai }}
                    </span>
                    @if ($karyawan->sertifikasi)
                        <span class="badge badge-success px-2 py-1 font-weight-bold">
                            <i class="fa fa-check-circle mr-1"></i> Tersertifikasi
                        </span>
                    @else
                        <span class="badge badge-secondary px-2 py-1">
                            <i class="fa fa-minus-circle mr-1"></i> Belum Sertifikasi
                        </span>
                    @endif
                </div>

                <h3 class="font-weight-bold text-white mb-1">
                    {{ $karyawan->nama_gelar }}
                </h3>
                <p class="text-white-50 mb-0" style="font-size: 14px;">
                    <i class="fa fa-briefcase mr-1 text-warning"></i> {{ $karyawan->jabatan ?? 'Tenaga Pendidik' }}
                    @if ($karyawan->nipy)
                        <span class="mx-2">•</span>
                        <i class="fa fa-id-badge mr-1 text-info"></i> NIPY: <strong>{{ $karyawan->nipy }}</strong>
                    @endif
                </p>
            </div>
        </div>
    </div>

    <!-- MAIN DETAIL CARDS -->
    <div class="row">
        <!-- CARD 1: INFORMASI KEPEGAWAIAN -->
        <div class="col-lg-6 mb-4">
            <div class="main-card card h-100">
                <div class="card-body">
                    <div class="info-group-title">
                        <i class="pe-7s-portfolio mr-2" style="font-size: 18px;"></i> Informasi Kepegawaian
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="detail-label">Nomor Induk Pegawai (NIPY)</div>
                            <div class="detail-value font-mono">{{ $karyawan->nipy ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Jabatan</div>
                            <div class="detail-value">{{ $karyawan->jabatan ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Unit Kerja</div>
                            <div class="detail-value">
                                <span class="badge badge-secondary px-2 py-1">{{ $karyawan->unit ?? '-' }}</span>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Golongan</div>
                            <div class="detail-value">{{ $karyawan->golongan ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Status Sertifikasi</div>
                            <div class="detail-value">
                                @if ($karyawan->sertifikasi)
                                    <span class="text-success font-weight-bold">
                                        <i class="fa fa-check-circle mr-1"></i> Sudah Tersertifikasi
                                    </span>
                                @else
                                    <span class="text-muted">
                                        <i class="fa fa-times-circle mr-1"></i> Belum Tersertifikasi
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">NUPTK</div>
                            <div class="detail-value font-mono">{{ $karyawan->nuptk ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Mulai Tugas (TMT)</div>
                            <div class="detail-value">
                                {{ $karyawan->mulai_tugas ? $karyawan->mulai_tugas->translatedFormat('d F Y') : '-' }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Masa Kerja</div>
                            <div class="detail-value text-primary">{{ $karyawan->lama_kerja }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: PENDIDIKAN TERAKHIR -->
        <div class="col-lg-6 mb-4">
            <div class="main-card card h-100">
                <div class="card-body">
                    <div class="info-group-title">
                        <i class="pe-7s-study mr-2" style="font-size: 18px;"></i> Riwayat Pendidikan
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="detail-label">Jenjang Pendidikan</div>
                            <div class="detail-value">
                                {{ $karyawan->jenjangPendidikan->nama_jenjang_pendidikan ?? '-' }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Tahun Lulus</div>
                            <div class="detail-value">{{ $karyawan->tahun_lulus ?? '-' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Program Studi / Jurusan</div>
                            <div class="detail-value">{{ $karyawan->prodi ?? '-' }}</div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Gelar Akademik</div>
                            <div class="detail-value font-weight-bold text-slate-800">
                                {{ $karyawan->gelar ?: 'Tidak dicantumkan' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: BIODATA PRIBADI -->
        <div class="col-lg-6 mb-4">
            <div class="main-card card h-100">
                <div class="card-body">
                    <div class="info-group-title">
                        <i class="pe-7s-user mr-2" style="font-size: 18px;"></i> Biodata Pribadi
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="detail-label">Nama Lengkap (tanpa gelar)</div>
                            <div class="detail-value">{{ $karyawan->nama_lengkap }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Jenis Kelamin</div>
                            <div class="detail-value">
                                @if ($karyawan->jk === 'L' || strtolower($karyawan->jk) === 'laki-laki')
                                    <span class="badge badge-info px-2 py-1">Laki-laki</span>
                                @elseif ($karyawan->jk === 'P' || strtolower($karyawan->jk) === 'perempuan')
                                    <span class="badge text-white px-2 py-1" style="background-color: #ec4899;">Perempuan</span>
                                @else
                                    <span>{{ $karyawan->jk ?? '-' }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Tempat, Tanggal Lahir</div>
                            <div class="detail-value">
                                {{ $karyawan->tempat_lahir ?? '-' }},
                                {{ $karyawan->tanggal_lahir ? $karyawan->tanggal_lahir->translatedFormat('d F Y') : '-' }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Nomor Induk Kependudukan (NIK)</div>
                            <div class="detail-value font-mono">{{ $karyawan->nik ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 4: KONTAK & ALAMAT -->
        <div class="col-lg-6 mb-4">
            <div class="main-card card h-100">
                <div class="card-body">
                    <div class="info-group-title">
                        <i class="pe-7s-map-marker mr-2" style="font-size: 18px;"></i> Kontak & Alamat
                    </div>

                    <div class="row">
                        <div class="col-sm-6">
                            <div class="detail-label">No. WhatsApp / Telepon</div>
                            <div class="detail-value">
                                @if ($karyawan->whatsapp)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $karyawan->whatsapp) }}" target="_blank" class="text-success font-weight-bold">
                                        <i class="fab fa-whatsapp mr-1"></i> {{ $karyawan->whatsapp }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">
                                @if ($karyawan->email)
                                    <a href="mailto:{{ $karyawan->email }}" class="text-primary font-weight-bold">
                                        <i class="fa fa-envelope mr-1"></i> {{ $karyawan->email }}
                                    </a>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Alamat Rumah</div>
                            <div class="detail-value">
                                {{ $karyawan->alamat ?? '-' }}
                                @if ($karyawan->rt || $karyawan->rw)
                                    (RT {{ $karyawan->rt ?? '-' }} / RW {{ $karyawan->rw ?? '-' }})
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Kelurahan / Desa</div>
                            <div class="detail-value">{{ $karyawan->kelurahan ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Kecamatan</div>
                            <div class="detail-value">{{ $karyawan->kecamatan ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Kabupaten / Kota</div>
                            <div class="detail-value">{{ $karyawan->kabupaten ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
