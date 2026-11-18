<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Identitas Sekolah &amp; Peserta Didik - {{ $kelas->nama_kelas }}</title>
    @include('pdf.rapor._styles')
</head>

<body>
    @php
        $namaYayasan = $sekolah?->nama_yayasan ?: 'Yayasan Prima Insani';
        $namaSekolah = $sekolah?->nama_sekolah ?: (strtoupper((string) $kelas->jenjang_kelas).' Prima Insani');
        $kabupatenSekolah = $sekolah?->kabupaten ?: ($titimangsa?->tempat ?: 'Garut');
        $kepsekNama = $titimangsa?->kepala_sekolah_nama_tampil ?: '........................................';
        $kepsekNipy = $titimangsa?->kepala_sekolah_nipy ?: '-';
        $kepsekJabatan = $titimangsa?->jabatan_kepala_sekolah ?: 'Kepala Sekolah';
        $titimangsaLabel = $titimangsa?->titimangsa_label ?: "{$kabupatenSekolah}, ..............................";
    @endphp

    @forelse ($entries as $entry)
        @php
            $siswa = $entry['siswa'];
            $anggota = $entry['anggota'];
            $avatarDataUri = $entry['avatarDataUri'] ?? null;
            $isLastStudent = $loop->last;

            $ttl = collect([
                trim((string) ($siswa?->tempat_lahir ?? '')),
                $siswa?->tanggal_lahir ? $siswa->tanggal_lahir->copy()->locale('id')->translatedFormat('d F Y') : '',
            ])->filter()->implode(', ');

            $genderLabel = match (strtoupper(trim((string) ($siswa?->jenis_kelamin ?? '')))) {
                'L', 'LAKI-LAKI' => 'Laki-laki',
                'P', 'PEREMPUAN' => 'Perempuan',
                default => '-',
            };

            $alamatSiswa = collect([
                trim((string) ($siswa?->alamat ?? '')),
                ($siswa?->rt || $siswa?->rw) ? 'RT '.($siswa?->rt ?: '-').'/RW '.($siswa?->rw ?: '-') : '',
                $siswa?->desa ? 'Desa/Kel. '.$siswa->desa : '',
                $siswa?->kecamatan ? 'Kec. '.$siswa->kecamatan : '',
                $siswa?->kabupaten ?: '',
                $siswa?->provinsi ?: '',
                $siswa?->kode_pos ?: '',
            ])->filter()->implode(', ');

            $pekAyah = ($siswa && $siswa->relationLoaded('pekerjaanAyah')) ? $siswa->pekerjaanAyah?->nama_pekerjaan : null;
            $pekIbu = ($siswa && $siswa->relationLoaded('pekerjaanIbu')) ? $siswa->pekerjaanIbu?->nama_pekerjaan : null;
            $pekWali = ($siswa && $siswa->relationLoaded('pekerjaanWali')) ? $siswa->pekerjaanWali?->nama_pekerjaan : null;
        @endphp

        <!-- HALAMAN 1: SAMPUL RAPOR -->
        <div class="cover-frame page-break">
            @if ($logoDataUri)
                <div style="margin-bottom: 20px;">
                    <img src="{{ $logoDataUri }}" alt="Logo Sekolah" style="height: 70px; width: auto;">
                </div>
            @endif

            <div class="muted uppercase" style="font-size: 9.5pt; font-weight: bold; letter-spacing: 0.6px; margin-bottom: 6px;">
                {{ $namaYayasan }}
            </div>
            <div class="cover-title">RAPOR PESERTA DIDIK</div>
            <div class="cover-school uppercase">{{ $namaSekolah }}</div>

            <div class="small muted" style="margin-top: 26px;">Nama Peserta Didik :</div>
            <div class="cover-box uppercase">
                {{ $siswa?->nama_lengkap ?? $anggota->siswa_nis }}
            </div>

            <div class="small muted">NIS / NISN :</div>
            <div class="cover-box" style="width: 58%; font-size: 10.5pt;">
                {{ $siswa?->nis ?? $anggota->siswa_nis }} / {{ $siswa?->nisn ?: '-' }}
            </div>

            <div style="margin-top: 72px; font-size: 10pt; font-weight: bold;">
                {{ strtoupper($namaSekolah) }}
            </div>
            <div class="small muted" style="margin-top: 3px;">
                {{ $kabupatenSekolah }}
                @if ($sekolah?->provinsi)
                    &bull; {{ $sekolah->provinsi }}
                @endif
            </div>
        </div>

        <!-- HALAMAN 2: IDENTITAS SATUAN PENDIDIKAN -->
        <div class="page-break">
            @include('pdf.rapor._kop')

            <div class="doc-title uppercase" style="margin-top: 10px; margin-bottom: 16px;">
                IDENTITAS SATUAN PENDIDIKAN
            </div>

            <table class="bio-table">
                <tr>
                    <td style="width: 34%;">Nama Yayasan</td>
                    <td style="width: 3%;">:</td>
                    <td class="font-bold">{{ $namaYayasan }}</td>
                </tr>
                <tr>
                    <td>Nama Sekolah</td>
                    <td>:</td>
                    <td class="font-bold">{{ $namaSekolah }}</td>
                </tr>
                <tr>
                    <td>Jenjang Pendidikan</td>
                    <td>:</td>
                    <td>{{ strtoupper((string) $kelas->jenjang_kelas) }}</td>
                </tr>
                <tr>
                    <td>NPSN</td>
                    <td>:</td>
                    <td>{{ $sekolah?->npsn ?: '-' }}</td>
                </tr>
                <tr>
                    <td>NSS / NSM</td>
                    <td>:</td>
                    <td>{{ $sekolah?->nss ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Alamat Sekolah</td>
                    <td>:</td>
                    <td>{{ $sekolah?->alamat ?: '-' }}</td>
                </tr>
                <tr>
                    <td>RT / RW</td>
                    <td>:</td>
                    <td>{{ ($sekolah?->rt || $sekolah?->rw) ? ($sekolah?->rt ?: '-').' / '.($sekolah?->rw ?: '-') : '-' }}</td>
                </tr>
                <tr>
                    <td>Kelurahan / Desa</td>
                    <td>:</td>
                    <td>{{ $sekolah?->kelurahan ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Kecamatan</td>
                    <td>:</td>
                    <td>{{ $sekolah?->kecamatan ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Kabupaten / Kota</td>
                    <td>:</td>
                    <td>{{ $sekolah?->kabupaten ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Provinsi</td>
                    <td>:</td>
                    <td>{{ $sekolah?->provinsi ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Kode Pos</td>
                    <td>:</td>
                    <td>{{ $sekolah?->kode_pos ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Nomor Telepon</td>
                    <td>:</td>
                    <td>{{ $sekolah?->telepon ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Email Sekolah</td>
                    <td>:</td>
                    <td>{{ $sekolah?->email ?: '-' }}</td>
                </tr>
                <tr>
                    <td>Website</td>
                    <td>:</td>
                    <td>{{ $sekolah?->website ?: '-' }}</td>
                </tr>
            </table>
        </div>

        <!-- HALAMAN 3: KETERANGAN TENTANG DIRI PESERTA DIDIK -->
        <div class="{{ ! $isLastStudent ? 'page-break' : '' }}">
            @include('pdf.rapor._kop')

            <div class="doc-title uppercase" style="margin-top: 8px; margin-bottom: 12px;">
                KETERANGAN TENTANG DIRI PESERTA DIDIK
            </div>

            <table class="bio-table">
                <tr>
                    <td style="width: 4%;">1.</td>
                    <td style="width: 33%;">Nama Peserta Didik (Lengkap)</td>
                    <td style="width: 3%;">:</td>
                    <td class="font-bold uppercase">{{ $siswa?->nama_lengkap ?? $anggota->siswa_nis }}</td>
                </tr>
                <tr>
                    <td>2.</td>
                    <td>Nomor Induk / NISN</td>
                    <td>:</td>
                    <td>{{ $siswa?->nis ?? $anggota->siswa_nis }} / {{ $siswa?->nisn ?: '-' }}</td>
                </tr>
                <tr>
                    <td>3.</td>
                    <td>Tempat, Tanggal Lahir</td>
                    <td>:</td>
                    <td>{{ $ttl !== '' ? $ttl : '-' }}</td>
                </tr>
                <tr>
                    <td>4.</td>
                    <td>Jenis Kelamin</td>
                    <td>:</td>
                    <td>{{ $genderLabel }}</td>
                </tr>
                <tr>
                    <td>5.</td>
                    <td>Agama</td>
                    <td>:</td>
                    <td>{{ $siswa?->agama ?: '-' }}</td>
                </tr>
                <tr>
                    <td>6.</td>
                    <td>Anak ke</td>
                    <td>:</td>
                    <td>{{ $siswa?->anak_ke !== null ? $siswa->anak_ke : '-' }}</td>
                </tr>
                <tr>
                    <td>7.</td>
                    <td>Alamat Peserta Didik</td>
                    <td>:</td>
                    <td>{{ $alamatSiswa !== '' ? $alamatSiswa : '-' }}</td>
                </tr>
                <tr>
                    <td>8.</td>
                    <td>Nomor Telepon / HP</td>
                    <td>:</td>
                    <td>{{ $siswa?->nomor_hp ?: ($siswa?->whatsapp ?: '-') }}</td>
                </tr>
                <tr>
                    <td>9.</td>
                    <td>Diterima di Kelas</td>
                    <td>:</td>
                    <td>{{ $kelas->nama_kelas }} ({{ $anggota->pendaftaran ?: '-' }})</td>
                </tr>
                <tr>
                    <td>10.</td>
                    <td>Nama Orang Tua</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td class="bio-sub">a. Ayah</td>
                    <td>:</td>
                    <td>{{ $siswa?->nama_ayah ?: '-' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="bio-sub">b. Ibu</td>
                    <td>:</td>
                    <td>{{ $siswa?->nama_ibu ?: '-' }}</td>
                </tr>
                <tr>
                    <td>11.</td>
                    <td>Pekerjaan Orang Tua</td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td></td>
                    <td class="bio-sub">a. Ayah</td>
                    <td>:</td>
                    <td>{{ $pekAyah ?: '-' }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td class="bio-sub">b. Ibu</td>
                    <td>:</td>
                    <td>{{ $pekIbu ?: '-' }}</td>
                </tr>
                <tr>
                    <td>12.</td>
                    <td>Nama Wali Peserta Didik</td>
                    <td>:</td>
                    <td>{{ $siswa?->nama_wali ?: '-' }}</td>
                </tr>
                <tr>
                    <td>13.</td>
                    <td>Pekerjaan Wali</td>
                    <td>:</td>
                    <td>{{ $pekWali ?: '-' }}</td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse; margin-top: 22px;">
                <tr>
                    <td style="width: 35%; text-align: center; vertical-align: middle;">
                        <table style="margin: 0 auto; border-collapse: collapse;">
                            <tr>
                                <td class="photo-box">
                                    @if ($avatarDataUri)
                                        <img src="{{ $avatarDataUri }}" alt="Foto Siswa">
                                    @else
                                        Pasfoto<br>3 x 4
                                    @endif
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 65%; padding-left: 36px; vertical-align: top; font-size: 8.8pt;">
                        <div>{{ $titimangsaLabel }}</div>
                        <div>{{ $kepsekJabatan }},</div>
                        <div class="ttd-space"></div>
                        <div class="ttd-name">{{ $kepsekNama }}</div>
                        <div class="small muted">NIPY. {{ $kepsekNipy }}</div>
                    </td>
                </tr>
            </table>
        </div>
    @empty
        <div class="text-center muted" style="padding: 40px;">
            Belum ada siswa terdaftar di kelas {{ $kelas->nama_kelas }}.
        </div>
    @endforelse
</body>

</html>
