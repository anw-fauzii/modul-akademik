<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Pencapaian Kompetensi Peserta Didik ({{ $periodeLabel }}) - {{ $kelas->nama_kelas }}</title>
    @include('pdf.rapor._styles')
</head>

<body>
    @php
        $namaSekolah = $sekolah?->nama_sekolah ?: (strtoupper((string) $kelas->jenjang_kelas).' Prima Insani');
        $alamatSekolah = trim((string) ($sekolah?->alamat ?? '')) !== ''
            ? $sekolah->alamat
            : ($sekolah?->alamat_lengkap ?: '-');
        $waliNama = $kelas->relationLoaded('waliKelas') && $kelas->waliKelas
            ? $kelas->waliKelas->nama_gelar
            : ($kelas->karyawan_nipy ?: '-');
        $waliNipy = $kelas->karyawan_nipy ?: '-';
        $kepsekNama = $titimangsa?->kepala_sekolah_nama_tampil ?: '........................................';
        $kepsekNipy = $titimangsa?->kepala_sekolah_nipy ?: '-';
        $kepsekJabatan = $titimangsa?->jabatan_kepala_sekolah ?: 'Kepala Sekolah';
        $kotaFallback = $sekolah?->kabupaten ?: '....................';
        $titimangsaLine = $titimangsa?->titimangsa_label ?: "{$kotaFallback}, ....................";
        $taLabel = $tahunAjaran?->nama_tahun_ajaran ?? '-';
        $semesterText = $tahunAjaran?->semester === '2' ? 'Genap' : 'Ganjil';
        $isMid = ($periode ?? \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID) === \App\Models\Admin\Penilaian\PeriodeTp::PERIODE_MID;
    @endphp

    @forelse ($entries as $entry)
        @php
            $siswa = $entry['siswa'];
            $anggota = $entry['anggota'];
            $mapelRows = $entry['mapelRows'];
            $ekskulRows = $entry['ekskulRows'];
            $t2q = $entry['t2q'];
            $presensi = $entry['presensi'];
        @endphp

        <div class="{{ ! $loop->last ? 'page-break' : '' }}">
            <!-- IDENTITAS RINGKAS DI BAGIAN PALING ATAS (TANPA KOP) -->
            <table class="meta-table">
                <tr>
                    <td style="width: 21%;">Nama Peserta Didik</td>
                    <td style="width: 2%;">:</td>
                    <td style="width: 38%;">{{ $siswa?->nama_lengkap ?? $anggota->siswa_nis }}</td>
                    <td style="width: 17%;">Kelas / Fase</td>
                    <td style="width: 2%;">:</td>
                    <td style="width: 20%;">{{ $kelas->nama_kelas }} / Fase {{ $fase }}</td>
                </tr>
                <tr>
                    <td>Nomor Induk/NISN</td>
                    <td>:</td>
                    <td>{{ $siswa?->nis ?? $anggota->siswa_nis }}/{{ $siswa?->nisn ?: '-' }}</td>
                    <td>Semester</td>
                    <td>:</td>
                    <td>{{ $semesterText }} ({{ $periodeLabel }})</td>
                </tr>
                <tr>
                    <td>Nama Sekolah</td>
                    <td>:</td>
                    <td>{{ $namaSekolah }}</td>
                    <td>Tahun Pelajaran</td>
                    <td>:</td>
                    <td>{{ $taLabel }}</td>
                </tr>
                <tr>
                    <td>Alamat</td>
                    <td>:</td>
                    <td>{{ $alamatSekolah }}</td>
                    <td colspan="3"></td>
                </tr>
            </table>

            <div class="doc-title uppercase">
                PENCAPAIAN KOMPETENSI PESERTA DIDIK
            </div>

            <!-- A. CAPAIAN KOMPETENSI MATA PELAJARAN -->
            <div class="section-heading">A. CAPAIAN KOMPETENSI MATA PELAJARAN</div>
            <table class="rapor-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">NO</th>
                        <th style="width: {{ $isMid ? '26%' : '25%' }};">MATA PELAJARAN</th>
                        @if (! $isMid)
                            <th style="width: 10%;">NILAI</th>
                        @endif
                        <th style="width: {{ $isMid ? '13%' : '11%' }};">CAPAIAN</th>
                        <th style="width: {{ $isMid ? '56%' : '49%' }};">DESKRIPSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($mapelRows as $mRow)
                        @php
                            $mp = $mRow['mapel'];
                            $rekap = $mRow['rekap'];
                            $naRapor = $rekap?->nilai_akhir_rapor;
                            $predikat = $rekap?->predikat_rapor;
                            $deskripsi = trim((string) ($rekap?->deskripsi_capaian ?? ''));
                        @endphp
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $mp->nama_mata_pelajaran }}</td>
                            @if (! $isMid)
                                <td class="text-center font-bold">
                                    {{ $naRapor !== null ? number_format((float) $naRapor, 0) : '-' }}
                                </td>
                            @endif
                            <td class="text-center">
                                @if ($predikat)
                                    <span class="font-bold">{{ $predikat }}</span>
                                    @if (filled($rekap->keterangan_predikat))
                                        <div style="font-size: 7pt; color: #4b5563;">({{ $rekap->keterangan_predikat }})</div>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <div class="desc-text">
                                    {{ $deskripsi !== '' ? $deskripsi : '-' }}
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isMid ? 4 : 5 }}" class="text-center muted">Belum ada mata pelajaran aktif.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- B. EKSTRAKURIKULER -->
            <div class="section-heading">B. EKSTRAKURIKULER</div>
            <table class="rapor-table">
                <thead>
                    <tr>
                        <th style="width: 5%;">NO</th>
                        <th style="width: 34%;">KEGIATAN EKSTRAKURIKULER</th>
                        <th style="width: 61%;">KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($ekskulRows as $ekskulItem)
                        @php
                            $ekskul = $ekskulItem->relationLoaded('ekstrakurikuler') ? $ekskulItem->ekstrakurikuler : null;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $loop->iteration }}</td>
                            <td>{{ $ekskul?->nama_ekstrakurikuler ?? '-' }}</td>
                            <td>{{ $ekskulItem->keterangan ?: 'Mengikuti kegiatan dengan baik.' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="text-center">1</td>
                            <td class="muted">-</td>
                            <td class="muted">-</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($t2q)
                @php
                    $guruT2q = $t2q->relationLoaded('guruT2q') ? $t2q->guruT2q : null;
                @endphp
                <div class="section-heading">C. PROGRAM TAHSIN &amp; TAHFIDZ AL-QUR'AN (T2Q)</div>
                <table class="rapor-table">
                    <thead>
                        <tr>
                            <th style="width: 24%;">TINGKAT / JILID</th>
                            <th style="width: 32%;">PEMBIMBING</th>
                            <th style="width: 44%;">KETERANGAN</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-center font-bold">{{ $t2q->tingkat ?: '-' }}</td>
                            <td>{{ $guruT2q?->nama_gelar ?? ($t2q->karyawan_nipy ?: '-') }}</td>
                            <td>{{ $t2q->keterangan ?: '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            @endif

            <!-- KETIDAKHADIRAN & CATATAN WALI KELAS -->
            <table class="split-table">
                <tbody>
                    <tr>
                        <td style="width: 38%; padding-right: 12px;">
                            <div class="section-heading" style="margin-top: 4px;">
                                {{ $t2q ? 'D.' : 'C.' }} KETIDAKHADIRAN
                            </div>
                            <table class="rapor-table" style="margin-bottom: 0;">
                                <tr>
                                    <td style="width: 65%;">Sakit</td>
                                    <td class="text-center font-bold" style="width: 35%;">{{ (int) ($presensi['sakit'] ?? 0) }} hari</td>
                                </tr>
                                <tr>
                                    <td>Izin</td>
                                    <td class="text-center font-bold">{{ (int) ($presensi['izin'] ?? 0) }} hari</td>
                                </tr>
                                <tr>
                                    <td>Tanpa Keterangan</td>
                                    <td class="text-center font-bold">{{ (int) ($presensi['alpa'] ?? 0) }} hari</td>
                                </tr>
                            </table>
                        </td>
                        <td style="width: 62%;">
                            <div class="section-heading" style="margin-top: 4px;">
                                {{ $t2q ? 'E.' : 'D.' }} CATATAN WALI KELAS
                            </div>
                            <div class="notes-box">
                                &nbsp;
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            <!-- TANDA TANGAN & TITIMANGSA -->
            <div class="ttd-wrap">
                <table class="ttd-table">
                    <tr>
                        <td style="width: 40%;">
                            <div>Mengetahui,</div>
                            <div>Orang Tua / Wali Siswa</div>
                            <div class="ttd-space"></div>
                            <div class="ttd-blank"></div>
                        </td>
                        <td style="width: 20%;"></td>
                        <td style="width: 40%;">
                            <div>{{ $titimangsaLine }}</div>
                            <div>Wali Kelas</div>
                            <div class="ttd-space"></div>
                            <div class="ttd-name">{{ $waliNama }}</div>
                            <div class="small muted">NIPY. {{ $waliNipy }}</div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="3" style="padding-top: 14px;">
                            <div>Mengetahui,</div>
                            <div>{{ $kepsekJabatan }}</div>
                            <div class="ttd-space"></div>
                            <div class="ttd-name">{{ $kepsekNama }}</div>
                            <div class="small muted">NIPY. {{ $kepsekNipy }}</div>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    @empty
        <div class="text-center muted" style="padding: 40px;">
            Belum ada siswa terdaftar di kelas {{ $kelas->nama_kelas }}.
        </div>
    @endforelse
</body>

</html>
