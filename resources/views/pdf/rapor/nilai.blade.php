<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <title>Pencapaian Kompetensi Peserta Didik ({{ $periodeLabel }}) - {{ $kelas->nama_kelas }}</title>
    @include('pdf.rapor._styles')
</head>

<body>
    <div class="pdf-page-footer">
        <span class="pdf-page-number"></span>
    </div>
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
        $nextSectionLetter = static function (int $index): string {
            $letters = '';
            $n = $index;
            do {
                $letters = chr(65 + ($n % 26)) . $letters;
                $n = intdiv($n, 26) - 1;
            } while ($n >= 0);

            return $letters;
        };
    @endphp

    @forelse ($entries as $entry)
        @php
            $siswa = $entry['siswa'];
            $anggota = $entry['anggota'];
            $mapelRows = $entry['mapelRows'] ?? [];
            $rawMapelGroups = $entry['mapelGroups']
                ?? \App\Services\Admin\Penilaian\RaporPdfService::groupMapelRowsByKelompok($mapelRows);
            $mapelGroups = \App\Services\Admin\Penilaian\RaporPdfService::filterScoredMapelGroups($rawMapelGroups);
            $displayMapelGroups = $mapelGroups !== []
                ? $mapelGroups
                : [['kelompok' => null, 'kode' => '', 'label' => 'Mata Pelajaran', 'rows' => []]];
            $ekskulRows = $entry['ekskulRows'];
            $t2q = $entry['t2q'];
            $t2qRapor = $entry['t2qRapor'] ?? null;
            $catatanT2qText = is_array($t2qRapor) ? trim((string) ($t2qRapor['catatan_t2q'] ?? '')) : '';
            $guruT2qNama = ($t2q && $t2q->relationLoaded('guruT2q') && $t2q->guruT2q)
                ? trim((string) $t2q->guruT2q->nama_gelar)
                : trim((string) (is_array($t2qRapor) ? ($t2qRapor['updated_by_name'] ?? '') : ''));
            $presensi = $entry['presensi'];
            $catatanWali = $entry['catatanWali'] ?? null;
            $catatanWaliText = trim((string) ($catatanWali?->catatan ?? ''));
            $sectionIdx = 0;
        @endphp

        <div class="student-rapor-entry">
            <!-- IDENTITAS RINGKAS DI BAGIAN PALING ATAS (TANPA KOP) -->
            <div class="meta-box">
                <table class="meta-table">
                    <tr>
                        <td class="meta-label" style="width: 21%;">Nama Peserta Didik</td>
                        <td style="width: 2%;">:</td>
                        <td class="font-bold" style="width: 38%;">{{ $siswa?->nama_lengkap ?? $anggota->siswa_nis }}</td>
                        <td class="meta-label" style="width: 17%;">Kelas / Fase</td>
                        <td style="width: 2%;">:</td>
                        <td class="font-bold" style="width: 20%;">{{ $kelas->romawi }} / Fase {{ $fase }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Nomor Induk/NISN</td>
                        <td>:</td>
                        <td>{{ $siswa?->nis ?? $anggota->siswa_nis }}/{{ $siswa?->nisn ?: '-' }}</td>
                        <td class="meta-label">Semester</td>
                        <td>:</td>
                        <td>{{ $semesterText }} ({{ $periodeLabel }})</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Nama Sekolah</td>
                        <td>:</td>
                        <td>{{ $namaSekolah }}</td>
                        <td class="meta-label">Tahun Pelajaran</td>
                        <td>:</td>
                        <td>{{ $taLabel }}</td>
                    </tr>
                    <tr>
                        <td class="meta-label">Alamat</td>
                        <td>:</td>
                        <td colspan="4">{{ $alamatSekolah }}</td>
                    </tr>
                </table>
            </div>

            <div class="doc-title doc-title--nilai uppercase">
                PENCAPAIAN KOMPETENSI PESERTA DIDIK
            </div>

            <!-- DAFTAR KELOMPOK PELAJARAN (TABEL TERPISAH PER KELOMPOK) -->
            @foreach ($displayMapelGroups as $group)
                @php
                    $groupHeading = ($group['kelompok'] === null && count($displayMapelGroups) === 1)
                        ? 'MATA PELAJARAN'
                        : $group['label'];
                @endphp
                <div class="section-heading uppercase">{{ $nextSectionLetter($sectionIdx++) }}. {{ $groupHeading }}</div>
                <table class="rapor-table rapor-table--mapel">
                    <thead>
                        <tr>
                            <th style="width: 6%;">NO</th>
                            <th style="width: 24%;">MATA PELAJARAN</th>
                            <th style="width: 12%;">CAPAIAN</th>
                            <th style="width: 58%;">DESKRIPSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($group['rows'] as $mRow)
                            @php
                                $mp = $mRow['mapel'];
                                $rekap = $mRow['rekap'];
                                $naRapor = $rekap?->nilai_akhir_rapor;
                                $deskripsi = trim((string) ($rekap?->deskripsi_capaian ?? ''));
                            @endphp
                            <tr>
                                <td class="text-center muted font-bold" style="vertical-align: middle;">{{ $loop->iteration }}</td>
                                <td class="font-bold" style="vertical-align: middle;">{{ $mp->nama_mata_pelajaran }}</td>
                                <td class="text-center font-bold" style="vertical-align: middle; font-size: 8.5pt;">
                                    {{ $naRapor !== null ? number_format((float) $naRapor, 0) : '-' }}
                                </td>
                                <td>
                                    <div class="desc-text">
                                        {{ $deskripsi !== '' ? $deskripsi : '-' }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center muted">Belum ada mata pelajaran yang memiliki nilai.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            @endforeach

            <!-- EKSTRAKURIKULER -->
            @php
                $sortedEkskulRows = collect($ekskulRows)->sortBy(static function ($ekskulItem): string {
                    $ekskul = $ekskulItem->relationLoaded('ekstrakurikuler') ? $ekskulItem->ekstrakurikuler : null;
                    $isWajib = $ekskul?->isWajib() ?? false;

                    return ($isWajib ? '0_' : '1_').mb_strtolower((string) ($ekskul?->nama_ekstrakurikuler ?? ''));
                })->values();
            @endphp
            <div class="section-keep-together">
                <div class="section-heading uppercase">{{ $nextSectionLetter($sectionIdx++) }}. EKSTRAKURIKULER</div>
                <table class="rapor-table rapor-table--mapel">
                    <thead>
                        <tr>
                            <th style="width: 6%;">NO</th>
                            <th style="width: 24%;">EKSTRAKURIKULER</th>
                            <th style="width: 12%;">CAPAIAN</th>
                            <th style="width: 58%;">DESKRIPSI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sortedEkskulRows as $ekskulItem)
                            @php
                                $ekskul = $ekskulItem->relationLoaded('ekstrakurikuler') ? $ekskulItem->ekstrakurikuler : null;
                                $predikatEkskul = trim((string) ($ekskulItem->predikat ?? ''));
                                if ($predikatEkskul === '' && $ekskulItem->nilai !== null) {
                                    $resolvedRange = \App\Models\Admin\DataMaster\Ekstrakurikuler::resolvePredikatForNilai(
                                        $ekskulItem->nilai,
                                        $ekskul?->kkm
                                    );
                                    $predikatEkskul = $resolvedRange['predikat'] ?? '';
                                }
                                $deskripsiEkskul = $ekskulItem->resolvedDeskripsiRapor();
                            @endphp
                            <tr>
                                <td class="text-center muted font-bold" style="vertical-align: middle;">{{ $loop->iteration }}</td>
                                <td class="font-bold" style="vertical-align: middle;">{{ $ekskul?->nama_ekstrakurikuler ?? '-' }}</td>
                                <td class="text-center font-bold" style="vertical-align: middle; font-size: 8.5pt;">{{ $predikatEkskul !== '' ? $predikatEkskul : '-' }}</td>
                                <td>
                                    <div class="desc-text">
                                        {{ $deskripsiEkskul }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td class="text-center muted" style="vertical-align: middle;">1</td>
                                <td class="muted" style="vertical-align: middle;">-</td>
                                <td class="text-center muted" style="vertical-align: middle;">-</td>
                                <td>
                                    <div class="desc-text muted">-</div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($t2q)
                @php
                    $tahsinRapor = is_array($t2qRapor) ? ($t2qRapor['tahsin'] ?? null) : null;
                    $tahfizhRapor = is_array($t2qRapor) ? ($t2qRapor['tahfizh'] ?? null) : null;

                    $tahsinHasData = is_array($tahsinRapor) && ($tahsinRapor['has_data'] ?? false);
                    $tahsinMateriText = $tahsinHasData && filled($tahsinRapor['materi'] ?? null) ? $tahsinRapor['materi'] : '-';
                    $tahsinNilaiText = $tahsinHasData && isset($tahsinRapor['nilai']) && $tahsinRapor['nilai'] !== null
                        ? number_format((float) $tahsinRapor['nilai'], 0)
                        : '-';
                    $tahsinDeskripsiText = $tahsinHasData && filled($tahsinRapor['deskripsi'] ?? null)
                        ? $tahsinRapor['deskripsi']
                        : '-';

                    $tahfizhHasData = is_array($tahfizhRapor) && ($tahfizhRapor['has_data'] ?? false);
                    $tahfizhMateriText = $tahfizhHasData && filled($tahfizhRapor['materi'] ?? null) ? $tahfizhRapor['materi'] : '-';
                    $tahfizhNilaiText = $tahfizhHasData && isset($tahfizhRapor['nilai']) && $tahfizhRapor['nilai'] !== null
                        ? number_format((float) $tahfizhRapor['nilai'], 0)
                        : '-';
                    $tahfizhDeskripsiText = $tahfizhHasData && filled($tahfizhRapor['deskripsi'] ?? null)
                        ? $tahfizhRapor['deskripsi']
                        : '-';
                @endphp
                <div class="section-keep-together">
                    <div class="section-heading uppercase">{{ $nextSectionLetter($sectionIdx++) }}. TAHSIN DAN TAHFIDZ AL-QUR'AN</div>
                    <table class="rapor-table rapor-table--mapel rapor-table--t2q">
                        <tbody>
                            <tr>
                                <th style="width: 6%;">NO</th>
                                <th style="width: 24%;">TAHSIN AL-QUR'AN</th>
                                <th style="width: 12%;">NILAI</th>
                                <th style="width: 58%;">DESKRIPSI</th>
                            </tr>
                            <tr>
                                <td class="text-center muted font-bold" style="vertical-align: middle;">1</td>
                                <td class="font-bold" style="vertical-align: middle;">{{ $tahsinMateriText }}</td>
                                <td class="text-center font-bold" style="vertical-align: middle; font-size: 8.5pt;">{{ $tahsinNilaiText }}</td>
                                <td>
                                    <div class="desc-text">{{ $tahsinDeskripsiText }}</div>
                                </td>
                            </tr>
                            <tr>
                                <th style="width: 6%;">NO</th>
                                <th style="width: 24%;">TAHFIDZ AL-QUR'AN</th>
                                <th style="width: 12%;">NILAI</th>
                                <th style="width: 58%;">DESKRIPSI</th>
                            </tr>
                            <tr>
                                <td class="text-center muted font-bold" style="vertical-align: middle;">1</td>
                                <td class="font-bold" style="vertical-align: middle;">{{ $tahfizhMateriText }}</td>
                                <td class="text-center font-bold" style="vertical-align: middle; font-size: 8.5pt;">{{ $tahfizhNilaiText }}</td>
                                <td>
                                    <div class="desc-text">{{ $tahfizhDeskripsiText }}</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif

            <!-- KETIDAKHADIRAN & CATATAN WALI KELAS -->
            <table class="split-table">
                <tbody>
                    <tr>
                        <td style="width: 35%; padding-right: 12px;">
                            <div class="section-heading uppercase" style="margin-top: 4px;">
                                {{ $nextSectionLetter($sectionIdx++) }}. KETIDAKHADIRAN
                            </div>
                            <table class="rapor-table rapor-table--headless" style="margin-bottom: 0;">
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
                        <td style="width: 65%;">
                            <div class="section-heading uppercase" style="margin-top: 4px;">
                                {{ $nextSectionLetter($sectionIdx++) }}. CATATAN WALI KELAS
                            </div>
                            <div class="notes-box">
                                @if ($catatanWaliText !== '')
                                    {!! nl2br(e($catatanWaliText)) !!}
                                @else
                                    &nbsp;
                                @endif
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>

            @if ($t2q || $catatanT2qText !== '')
                <!-- CATATAN GURU T2Q -->
                <div class="section-keep-together" style="margin-top: 6px;">
                    <div class="section-heading uppercase" style="margin-top: 4px;">
                        {{ $nextSectionLetter($sectionIdx++) }}. CATATAN GURU T2Q
                        @if ($guruT2qNama !== '')
                            <span class="muted" style="font-weight: normal; text-transform: none; font-size: 7.6pt;">
                                ({{ $guruT2qNama }})
                            </span>
                        @endif
                    </div>
                    <div class="notes-box">
                        @if ($catatanT2qText !== '')
                            {!! nl2br(e($catatanT2qText)) !!}
                        @else
                            &nbsp;
                        @endif
                    </div>
                </div>
            @endif

            <!-- TANDA TANGAN & TITIMANGSA -->
            <div class="ttd-wrap">
                <table class="ttd-table">
                    <tr>
                        <td style="width: 40%;">
                            <div>Mengetahui,</div>
                            <div>Orang Tua / Wali Siswa</div>
                            <div class="ttd-space"></div><br>
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
        @if (! $loop->last)
            <div class="page-break student-page-reset"></div>
        @endif
    @empty
        <div class="text-center muted" style="padding: 40px;">
            Belum ada siswa terdaftar di kelas {{ $kelas->nama_kelas }}.
        </div>
    @endforelse
</body>

</html>
