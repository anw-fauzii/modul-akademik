@extends('layouts.appui')

@section('title')
    <title>Detail Siswa - {{ $siswa->nama_lengkap }}</title>
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
    <div class="app-page-title">
        <div class="page-title-wrapper">
            <div class="page-title-heading">
                <div class="page-title-icon text-white shadow-sm"
                    style="background: linear-gradient(135deg, #ea580c 0%, #d97706 100%) !important; border-radius: 12px;">
                    <i class="pe-7s-study" style="color: white;"></i>
                </div>
                <div>
                    Detail Data Siswa
                    <div class="page-title-subheading">
                        Informasi lengkap biodata, akademik, kontak, kesehatan, dan data orang tua/wali siswa.
                    </div>
                </div>
            </div>

            <div class="page-title-actions d-flex gap-2">
                <a href="{{ route('siswa.edit', $siswa->nis) }}" class="btn btn-primary shadow-sm mr-2" style="border-radius: 8px;">
                    <i class="fa fa-edit mr-1"></i> Edit Data
                </a>
                <a href="{{ route('siswa.index') }}" class="btn text-white shadow-sm"
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
                @if ($siswa->avatar_url)
                    <img src="{{ $siswa->avatar_url }}" alt="{{ $siswa->nama_lengkap }}" class="profile-avatar d-block mx-auto">
                @else
                    <div class="profile-avatar-fallback mx-auto">
                        {{ $siswa->inisial_nama }}
                    </div>
                @endif
            </div>
            <div class="col-md text-center text-md-left">
                <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-md-start gap-2 mb-2">
                    <span class="badge {{ $siswa->status_badge_class }} px-2 py-1 font-weight-bold mr-1">
                        {{ $siswa->status }}
                    </span>
                    <span class="badge badge-info px-2 py-1 font-weight-bold mr-1">
                        {{ $siswa->jenis_kelamin_label }}
                    </span>
                    @if ($siswa->jenis_pendaftaran)
                        <span class="badge badge-secondary px-2 py-1 font-weight-bold mr-1">
                            {{ $siswa->jenis_pendaftaran }}
                        </span>
                    @endif
                </div>

                <h3 class="font-weight-bold text-white mb-1">
                    {{ $siswa->nama_lengkap }}
                </h3>
                <p class="text-white-50 mb-0" style="font-size: 14px;">
                    <i class="fa fa-id-badge mr-1 text-warning"></i> NIS: <strong>{{ $siswa->nis }}</strong>
                    @if ($siswa->nisn)
                        <span class="mx-2">•</span>
                        NISN: <strong>{{ $siswa->nisn }}</strong>
                    @endif
                    @if ($siswa->kelas)
                        <span class="mx-2">•</span>
                        <i class="fa fa-school mr-1 text-info"></i> {{ $siswa->kelas->nama_kelas }} ({{ $siswa->kelas->jenjang_kelas }})
                    @endif
                </p>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- CARD 1: AKADEMIK & LAYANAN -->
        <div class="col-lg-6 mb-4">
            <div class="main-card card h-100">
                <div class="card-body">
                    <div class="info-group-title">
                        <i class="pe-7s-notebook mr-2" style="font-size: 18px;"></i> Akademik & Layanan Siswa
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="detail-label">Kelas</div>
                            <div class="detail-value">
                                @if ($siswa->kelas)
                                    {{ $siswa->kelas->nama_kelas }} ({{ $siswa->kelas->jenjang_kelas }})
                                    @if ($siswa->kelas->tahunAjaran)
                                        <div class="small text-muted">
                                            {{ $siswa->kelas->tahunAjaran->nama_tahun_ajaran }} - Sem {{ $siswa->kelas->tahunAjaran->semester }}
                                        </div>
                                    @endif
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Guru Pembimbing / Wali</div>
                            <div class="detail-value">{{ $siswa->karyawan?->nama_gelar ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Ekstrakurikuler</div>
                            <div class="detail-value">
                                @if ($siswa->ekstrakurikuler)
                                    {{ $siswa->ekstrakurikuler->nama_ekstrakurikuler }}
                                    <div class="small text-muted">Unit {{ $siswa->ekstrakurikuler->unit }}</div>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Layanan Jemputan</div>
                            <div class="detail-value">
                                @if ($siswa->jemputan)
                                    {{ $siswa->jemputan->petugas?->nama_gelar ?? 'Petugas #' . $siswa->jemputan->karyawan_nipy }}
                                    <div class="small text-muted">
                                        PP: {{ $siswa->jemputan->harga_pp_formatted }} • 1/2: {{ $siswa->jemputan->harga_setengah_formatted }}
                                    </div>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Jenis Pendaftaran</div>
                            <div class="detail-value">{{ $siswa->jenis_pendaftaran ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Status</div>
                            <div class="detail-value">
                                <span class="badge {{ $siswa->status_badge_class }} px-2 py-1">{{ $siswa->status }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 2: BIODATA & IDENTITAS -->
        <div class="col-lg-6 mb-4">
            <div class="main-card card h-100">
                <div class="card-body">
                    <div class="info-group-title">
                        <i class="pe-7s-user mr-2" style="font-size: 18px;"></i> Biodata & Identitas Pribadi
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="detail-label">NIK</div>
                            <div class="detail-value font-mono">{{ $siswa->nik ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Nomor Kartu Keluarga (KK)</div>
                            <div class="detail-value font-mono">{{ $siswa->no_kk ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Tempat, Tanggal Lahir</div>
                            <div class="detail-value">
                                {{ $siswa->tempat_lahir ?? '-' }},
                                {{ $siswa->tanggal_lahir ? $siswa->tanggal_lahir->translatedFormat('d F Y') : '-' }}
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">No. Akta Lahir</div>
                            <div class="detail-value">{{ $siswa->akta_lahir ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Agama</div>
                            <div class="detail-value">{{ $siswa->agama ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Kewarganegaraan</div>
                            <div class="detail-value">{{ $siswa->kewarganegaraan ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Negara</div>
                            <div class="detail-value">{{ $siswa->nama_negara ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Berkebutuhan Khusus</div>
                            <div class="detail-value">{{ $siswa->berkebutuhanKhusus?->nama_berkebutuhan_khusus ?? '-' }}</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="detail-label">Anak Ke</div>
                            <div class="detail-value">{{ $siswa->anak_ke ?? '-' }}</div>
                        </div>
                        <div class="col-sm-3">
                            <div class="detail-label">Jml Saudara</div>
                            <div class="detail-value">{{ $siswa->jumlah_saudara ?? '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 3: KONTAK, ALAMAT & TRANSPORTASI -->
        <div class="col-lg-6 mb-4">
            <div class="main-card card h-100">
                <div class="card-body">
                    <div class="info-group-title">
                        <i class="pe-7s-map-marker mr-2" style="font-size: 18px;"></i> Kontak, Alamat & Transportasi
                    </div>
                    <div class="row">
                        <div class="col-sm-4">
                            <div class="detail-label">Nomor HP</div>
                            <div class="detail-value">{{ $siswa->nomor_hp ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">WhatsApp</div>
                            <div class="detail-value">
                                @if ($siswa->whatsapp)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $siswa->whatsapp) }}" target="_blank" class="text-success font-weight-bold">
                                        <i class="fab fa-whatsapp mr-1"></i> {{ $siswa->whatsapp }}
                                    </a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Email</div>
                            <div class="detail-value">
                                @if ($siswa->email)
                                    <a href="mailto:{{ $siswa->email }}" class="text-primary font-weight-bold">{{ $siswa->email }}</a>
                                @else
                                    -
                                @endif
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="detail-label">Alamat Lengkap</div>
                            <div class="detail-value">
                                {{ $siswa->alamat ?? '-' }}
                                @if ($siswa->rt || $siswa->rw)
                                    (RT {{ $siswa->rt ?? '-' }} / RW {{ $siswa->rw ?? '-' }})
                                @endif
                            </div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Desa / Kelurahan</div>
                            <div class="detail-value">{{ $siswa->desa ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Kecamatan</div>
                            <div class="detail-value">{{ $siswa->kecamatan ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Kabupaten / Kota</div>
                            <div class="detail-value">{{ $siswa->kabupaten ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Provinsi</div>
                            <div class="detail-value">{{ $siswa->provinsi ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Kode Pos</div>
                            <div class="detail-value">{{ $siswa->kode_pos ?? '-' }}</div>
                        </div>
                        <div class="col-sm-4">
                            <div class="detail-label">Tempat Tinggal</div>
                            <div class="detail-value">{{ $siswa->tempat_tinggal ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Moda Transportasi</div>
                            <div class="detail-value">{{ $siswa->transportasi?->nama_transportasi ?? '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Koordinat (Lintang / Bujur)</div>
                            <div class="detail-value font-mono">
                                {{ $siswa->lintang ?? '-' }} / {{ $siswa->bujur ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- CARD 4: PERIODIK & KESEHATAN -->
        <div class="col-lg-6 mb-4">
            <div class="main-card card h-100">
                <div class="card-body">
                    <div class="info-group-title">
                        <i class="pe-7s-graph2 mr-2" style="font-size: 18px;"></i> Data Periodik & Kesehatan
                    </div>
                    <div class="row">
                        <div class="col-sm-6">
                            <div class="detail-label">Tinggi Badan</div>
                            <div class="detail-value">{{ $siswa->tinggi_badan !== null ? $siswa->tinggi_badan . ' cm' : '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Berat Badan</div>
                            <div class="detail-value">{{ $siswa->berat_badan !== null ? $siswa->berat_badan . ' kg' : '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Lingkar Kepala</div>
                            <div class="detail-value">{{ $siswa->lingkar_kepala !== null ? $siswa->lingkar_kepala . ' cm' : '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Jarak ke Sekolah</div>
                            <div class="detail-value">{{ $siswa->jarak !== null ? $siswa->jarak . ' km' : '-' }}</div>
                        </div>
                        <div class="col-sm-6">
                            <div class="detail-label">Waktu Tempuh</div>
                            <div class="detail-value">{{ $siswa->waktu_tempuh !== null ? $siswa->waktu_tempuh . ' menit' : '-' }}</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- DATA ORANG TUA & WALI -->
    @php
        $keluargaGroups = [
            'ayah' => [
                'title' => 'Data Ayah',
                'jenjang' => $siswa->jenjangPendidikanAyah?->nama_jenjang_pendidikan,
                'pekerjaan' => $siswa->pekerjaanAyah?->nama_pekerjaan,
                'penghasilan' => $siswa->penghasilanAyah?->nama_penghasilan,
                'khusus' => $siswa->berkebutuhanKhususAyah?->nama_berkebutuhan_khusus,
            ],
            'ibu' => [
                'title' => 'Data Ibu',
                'jenjang' => $siswa->jenjangPendidikanIbu?->nama_jenjang_pendidikan,
                'pekerjaan' => $siswa->pekerjaanIbu?->nama_pekerjaan,
                'penghasilan' => $siswa->penghasilanIbu?->nama_penghasilan,
                'khusus' => $siswa->berkebutuhanKhususIbu?->nama_berkebutuhan_khusus,
            ],
            'wali' => [
                'title' => 'Data Wali',
                'jenjang' => $siswa->jenjangPendidikanWali?->nama_jenjang_pendidikan,
                'pekerjaan' => $siswa->pekerjaanWali?->nama_pekerjaan,
                'penghasilan' => $siswa->penghasilanWali?->nama_penghasilan,
                'khusus' => $siswa->berkebutuhanKhususWali?->nama_berkebutuhan_khusus,
            ],
        ];
    @endphp
    <div class="row">
        @foreach ($keluargaGroups as $peran => $group)
            <div class="col-lg-4 mb-4">
                <div class="main-card card h-100">
                    <div class="card-body">
                        <div class="info-group-title">
                            <i class="pe-7s-users mr-2" style="font-size: 18px;"></i> {{ $group['title'] }}
                        </div>
                        <div class="detail-label">Nama Lengkap</div>
                        <div class="detail-value">{{ $siswa->{"nama_{$peran}"} ?? '-' }}</div>

                        <div class="detail-label">NIK</div>
                        <div class="detail-value font-mono">{{ $siswa->{"nik_{$peran}"} ?? '-' }}</div>

                        <div class="detail-label">Tahun / Tanggal Lahir</div>
                        <div class="detail-value">{{ $siswa->{"lahir_{$peran}"} ?? '-' }}</div>

                        <div class="detail-label">Jenjang Pendidikan</div>
                        <div class="detail-value">{{ $group['jenjang'] ?? '-' }}</div>

                        <div class="detail-label">Pekerjaan</div>
                        <div class="detail-value">{{ $group['pekerjaan'] ?? '-' }}</div>

                        <div class="detail-label">Penghasilan</div>
                        <div class="detail-value">{{ $group['penghasilan'] ?? '-' }}</div>

                        <div class="detail-label">Berkebutuhan Khusus</div>
                        <div class="detail-value">{{ $group['khusus'] ?? '-' }}</div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection

