<?php

namespace Tests\Feature;

use App\Exports\Admin\Penilaian\PenilaianTpExport;
use App\Imports\Admin\Penilaian\PenilaianTpSheetImport;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\NilaiSumatifBab;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\RekapTp;
use App\Models\Admin\Penilaian\SumatifBab;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PenilaianTpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.host' => 'https://sso.test']);
        Http::preventStrayRequests();

        if (! Schema::hasTable('kepegawaian_master_karyawan')) {
            Schema::create('kepegawaian_master_karyawan', function (Blueprint $table) {
                $table->string('nipy')->primary();
                $table->string('nama_lengkap');
                $table->string('gelar')->nullable();
                $table->string('jabatan')->nullable();
                $table->string('unit')->nullable();
                $table->timestamps();
            });
        }
    }

    private function authenticateUser(): User
    {
        Http::fake([
            'https://sso.test/api/user' => Http::response([
                'name' => 'Guru SD',
                'email' => 'gurusd@primainsani.sch.id',
                'no_induk' => 'NIPY-SD-01',
            ], 200),
        ]);

        return User::create([
            'name' => 'Guru SD',
            'email' => 'gurusd@primainsani.sch.id',
            'no_induk' => 'NIPY-SD-01',
        ]);
    }

    private function actingAsSso(User $user): static
    {
        return $this->actingAs($user)->withSession(['access_token' => 'valid-token']);
    }

    public function test_kkm_crud_and_sd_filter_work_with_custom_predikat_ranges(): void
    {
        $user = $this->authenticateUser();

        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $kelompok = KelompokPelajaran::factory()->create();
        $mapel = MataPelajaran::factory()->create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'PAI',
            'nama_mata_pelajaran' => 'Pendidikan Agama Islam',
            'unit' => 'SD',
        ]);

        $response = $this->actingAsSso($user)->post(route('penilaian-kkm.store'), [
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
            'tingkatan_kelas' => '5',
            'nilai_kkm' => 75,
            'status' => true,
            'predikats' => [
                ['predikat' => 'A', 'keterangan' => 'Sangat Baik', 'nilai_min' => 92, 'nilai_max' => 100, 'is_tuntas' => true],
                ['predikat' => 'B', 'keterangan' => 'Baik', 'nilai_min' => 83, 'nilai_max' => 91, 'is_tuntas' => true],
                ['predikat' => 'C', 'keterangan' => 'Cukup', 'nilai_min' => 75, 'nilai_max' => 82, 'is_tuntas' => true],
                ['predikat' => 'D', 'keterangan' => 'Perlu Bimbingan', 'nilai_min' => 0, 'nilai_max' => 74, 'is_tuntas' => false],
            ],
        ]);

        $response->assertRedirect(route('penilaian-kkm.index'));

        $kkm = Kkm::query()->with('predikats')->firstOrFail();
        $this->assertSame('5', $kkm->tingkatan_kelas);
        $this->assertCount(4, $kkm->predikats);

        $indexResponse = $this->actingAsSso($user)->get(route('penilaian-kkm.index', ['tahun_ajaran_id' => $ta->sqid]));
        $indexResponse->assertOk()->assertSeeText('Pendidikan Agama Islam');

        // Simpan banyak mata pelajaran & banyak tingkatan kelas sekaligus
        $mapel2 = MataPelajaran::factory()->create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'unit' => 'SD',
        ]);

        $bulkResponse = $this->actingAsSso($user)->post(route('penilaian-kkm.store'), [
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'mata_pelajaran_ids' => [$mapel->id, $mapel2->id],
            'tingkatan_kelas' => '1',
            'copy_to_tingkatan' => ['2'],
            'nilai_kkm' => 75,
            'bobot_tp' => 50,
            'bobot_sumatif' => 25,
            'bobot_ujian' => 25,
            'status' => true,
            'predikats' => [
                ['predikat' => 'A', 'keterangan' => 'Sangat Baik', 'nilai_min' => 92, 'nilai_max' => 100, 'is_tuntas' => true],
                ['predikat' => 'B', 'keterangan' => 'Baik', 'nilai_min' => 83, 'nilai_max' => 91, 'is_tuntas' => true],
                ['predikat' => 'C', 'keterangan' => 'Cukup', 'nilai_min' => 75, 'nilai_max' => 82, 'is_tuntas' => true],
                ['predikat' => 'D', 'keterangan' => 'Perlu Bimbingan', 'nilai_min' => 0, 'nilai_max' => 74, 'is_tuntas' => false],
            ],
        ]);
        $bulkResponse->assertRedirect(route('penilaian-kkm.index'));

        $this->assertSame(
            4,
            Kkm::query()
                ->where('akademik_master_tahun_ajaran_id', $ta->id)
                ->whereIn('akademik_pelajaran_mata_pelajaran_id', [$mapel->id, $mapel2->id])
                ->whereIn('tingkatan_kelas', ['1', '2'])
                ->count()
        );
    }

    public function test_mid_selective_inclusion_and_semester_combined_na_tp_pure_average_and_excel_roundtrip(): void
    {
        $user = $this->authenticateUser();

        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $kelas = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'tingkatan_kelas' => '5',
            'nama_kelas' => '5 Al-Fatihah',
            'jenjang_kelas' => 'SD',
            'romawi' => 'V',
        ]);

        $kelompok = KelompokPelajaran::factory()->create();
        $mapel = MataPelajaran::factory()->create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'PAI',
            'nama_mata_pelajaran' => 'Pendidikan Agama Islam',
            'unit' => 'SD',
        ]);

        $siswa1 = Siswa::factory()->create(['nis' => '20265001', 'nama_lengkap' => 'Ahmad Fauzan']);
        $siswa2 = Siswa::factory()->create(['nis' => '20265002', 'nama_lengkap' => 'Budi Santoso']);

        $anggota1 = AnggotaKelas::factory()->create([
            'siswa_nis' => $siswa1->nis,
            'akademik_master_kelas_id' => $kelas->id,
        ]);
        $anggota2 = AnggotaKelas::factory()->create([
            'siswa_nis' => $siswa2->nis,
            'akademik_master_kelas_id' => $kelas->id,
        ]);

        $tp51 = TujuanPembelajaran::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
            'tingkatan_kelas' => '5',
            'fase' => 'C',
            'kode_tp' => 'TP 5.1',
            'tujuan_pembelajaran' => 'Memahami dan membaca surat pendek',
            'lingkup_materi' => 'Pemahaman Bacaan',
            'urutan' => 1,
            'status' => true,
        ]);

        $tp52 = TujuanPembelajaran::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
            'tingkatan_kelas' => '5',
            'fase' => 'C',
            'kode_tp' => 'TP 5.2',
            'tujuan_pembelajaran' => 'Melafalkan makhroj, hafalan, dan arti',
            'lingkup_materi' => 'Makhroj & Hafalan',
            'urutan' => 2,
            'status' => true,
        ]);

        // KKM 75 default ranges
        $kkm = Kkm::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
            'tingkatan_kelas' => '5',
            'nilai_kkm' => 75,
            'status' => true,
        ]);
        $kkm->predikats()->createMany(Kkm::generateDefaultPredikats(75));

        // Konfigurasi aspek seragam jenjang pada periode Mid
        $aspekPemahaman = AspekTp::create(['tujuan_pembelajaran_id' => $tp51->sqid, 'periode' => 'mid', 'nama_aspek' => 'Pemahaman', 'urutan' => 1]);
        $aspekBacaan = AspekTp::create(['tujuan_pembelajaran_id' => $tp51->sqid, 'periode' => 'mid', 'nama_aspek' => 'Bacaan', 'urutan' => 2]);
        $aspekMakhroj = AspekTp::create(['tujuan_pembelajaran_id' => $tp52->sqid, 'periode' => 'mid', 'nama_aspek' => 'Makhroj', 'urutan' => 1]);
        $aspekHafalan = AspekTp::create(['tujuan_pembelajaran_id' => $tp52->sqid, 'periode' => 'mid', 'nama_aspek' => 'Hafalan', 'urutan' => 2]);
        $aspekArtinya = AspekTp::create(['tujuan_pembelajaran_id' => $tp52->sqid, 'periode' => 'mid', 'nama_aspek' => 'Artinya', 'urutan' => 3]);

        // Pada Mid Semester: TP 5.1 masuk rapor Mid, TP 5.2 dikecualikan dari rapor Mid
        $this->actingAsSso($user)->post(route('penilaian-tp.toggle-masuk-rapor', $tp52), [
            'periode' => 'mid',
            'masuk_rapor' => 0,
            'kelas_id' => $kelas->sqid,
        ])->assertRedirect();

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAsSso($user)->post(route('penilaian-tp.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapel->sqid,
            'periode' => 'mid',
            'nilai' => [
                $anggota1->id => [
                    $aspekPemahaman->id => 100,
                    $aspekBacaan->id => 80, // NA TP 5.1 = 90
                    $aspekMakhroj->id => 80,
                    $aspekHafalan->id => 80,
                    $aspekArtinya->id => 75, // NA TP 5.2 = 78
                ],
                $anggota2->id => [
                    $aspekPemahaman->id => 90,
                    $aspekBacaan->id => 70, // NA TP 5.1 = 80
                    $aspekMakhroj->id => 88,
                    $aspekHafalan->id => 88,
                    $aspekArtinya->id => 75, // NA TP 5.2 = 84
                ],
            ],
        ])->assertRedirect();

        $saveQueries = DB::getQueryLog();
        DB::disableQueryLog();
        $this->assertLessThanOrEqual(30, count($saveQueries));

        // Pada Mid Rapor, hanya TP 5.1 yang dihitung -> NA Rapor Mid = 90 (siswa 1) dan 80 (siswa 2)
        $rekapMid1 = RekapMapel::query()
            ->where('anggota_kelas_id', $anggota1->id)
            ->where('periode', 'mid')
            ->firstOrFail();
        $this->assertEquals(90.0, $rekapMid1->nilai_akhir_rapor);
        $this->assertSame('B', $rekapMid1->predikat_rapor);

        // Input nilai di Semester hanya untuk TP 5.2 (siswa 1 mendapat perbaikan nilai 90), TP 5.1 diambil dari Mid (90 & 80)
        $aspekSem = AspekTp::create([
            'tujuan_pembelajaran_id' => $tp52->sqid,
            'periode' => 'semester',
            'nama_aspek' => 'Nilai TP',
            'urutan' => 1,
        ]);

        $this->actingAsSso($user)->post(route('penilaian-tp.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapel->sqid,
            'periode' => 'semester',
            'nilai' => [
                $anggota1->id => [$aspekSem->id => 94], // TP 5.1 (Mid=90) + TP 5.2 (Sem=94) => 92
                $anggota2->id => [$aspekSem->id => 86], // TP 5.1 (Mid=80) + TP 5.2 (Sem=86) => 83
            ],
        ])->assertRedirect();

        $rekapSem1 = RekapMapel::query()
            ->where('anggota_kelas_id', $anggota1->id)
            ->where('periode', 'semester')
            ->firstOrFail();
        $rekapSem2 = RekapMapel::query()
            ->where('anggota_kelas_id', $anggota2->id)
            ->where('periode', 'semester')
            ->firstOrFail();

        $this->assertEquals(92.0, $rekapSem1->nilai_akhir_rapor);
        $this->assertSame('A', $rekapSem1->predikat_rapor);
        $this->assertEquals(83.0, $rekapSem2->nilai_akhir_rapor);
        $this->assertSame('B', $rekapSem2->predikat_rapor);

        // Uji Export -> Import Sheet 2-baris header kembali
        $exportRows = (new PenilaianTpExport([
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapel->sqid,
            'periode' => 'mid',
        ]))->array();

        // Ubah nilai Bacaan siswa 1 menjadi 100 di spreadsheet baris 3 -> NA TP 5.1 naik menjadi 100
        $exportRows[2][4] = 100;
        $sheetImport = new PenilaianTpSheetImport($kelas, $mapel, 'mid', 'NIPY-SD-01');
        $sheetImport->collection(new Collection($exportRows));

        $this->assertSame([], $sheetImport->errors);
        $updatedNaTp1 = RekapTp::query()
            ->where('tujuan_pembelajaran_id', $tp51->id)
            ->where('anggota_kelas_id', $anggota1->id)
            ->where('periode', 'mid')
            ->value('nilai_akhir_tp');
        $this->assertEquals(100.0, (float) $updatedNaTp1);

        // Aktifkan bobot 70% NA TP + 30% Nilai Ujian langsung dari halaman Input Nilai TP
        $this->actingAsSso($user)->post(route('penilaian-tp.bobot.update'), [
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapel->sqid,
            'periode' => 'semester',
            'bobot_tp' => 70,
            'bobot_ujian' => 30,
        ])->assertRedirect();

        // Simpan nilai STS di periode Mid dan nilai SAS di periode Semester (Mid tidak membocori Semester SAS)
        $this->actingAsSso($user)->post(route('penilaian-tp.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapel->sqid,
            'periode' => 'mid',
            'ujian' => [
                $anggota1->id => 80, // TP 5.1 (Mid=100) * 70% + STS (80) * 30% = 94
            ],
        ])->assertRedirect();

        $this->actingAsSso($user)->post(route('penilaian-tp.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapel->sqid,
            'periode' => 'semester',
            'ujian' => [
                $anggota1->id => 70, // Rata TP Sem (100+94 => 97) * 70% + SAS (70) * 30% = 67.9 + 21 = 89
            ],
        ])->assertRedirect();

        $rekapMidAfterSts = RekapMapel::query()
            ->where('anggota_kelas_id', $anggota1->id)
            ->where('periode', 'mid')
            ->firstOrFail();
        $rekapSemAfterSas = RekapMapel::query()
            ->where('anggota_kelas_id', $anggota1->id)
            ->where('periode', 'semester')
            ->firstOrFail();

        $this->assertEquals(80.0, $rekapMidAfterSts->nilai_ujian);
        $this->assertEquals(94.0, $rekapMidAfterSts->nilai_akhir_rapor);
        $this->assertEquals(70.0, $rekapSemAfterSas->nilai_ujian);
        $this->assertEquals(89.0, $rekapSemAfterSas->nilai_akhir_rapor);

        // Uji export & import ulang nilai PSTS pada kolom Ujian (PSTS)
        $exportWithSts = (new PenilaianTpExport([
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapel->sqid,
            'periode' => 'mid',
        ]))->array();
        $stsColIdx = array_search('PSTS', $exportWithSts[0], true);
        $this->assertIsInt($stsColIdx);
        $exportWithSts[2][$stsColIdx] = 90;

        $reimport = new PenilaianTpSheetImport($kelas, $mapel, 'mid', 'NIPY-SD-01');
        $reimport->collection(new Collection($exportWithSts));
        $this->assertSame([], $reimport->errors);
        $this->assertEquals(90.0, (float) $rekapMidAfterSts->refresh()->nilai_ujian);

        // Uji Generate Ulang Deskripsi di Semester -> semua NA TP dikelompokkan per predikat KKM
        $tp53 = TujuanPembelajaran::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
            'tingkatan_kelas' => '5',
            'fase' => 'C',
            'kode_tp' => 'TP 5.3',
            'tujuan_pembelajaran' => 'Menjelaskan isi kandungan surat pendek',
            'lingkup_materi' => 'Isi Kandungan',
            'urutan' => 3,
            'status' => true,
        ]);
        $aspek53 = AspekTp::create([
            'tujuan_pembelajaran_id' => $tp53->sqid,
            'periode' => 'semester',
            'nama_aspek' => 'Nilai TP',
            'urutan' => 1,
        ]);
        $this->actingAsSso($user)->post(route('penilaian-tp.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapel->sqid,
            'periode' => 'semester',
            'nilai' => [
                $anggota1->id => [$aspek53->id => 96], // TP 5.1=100 (A), TP 5.2=94 (A), TP 5.3=96 (A) => A koma + dan
            ],
        ])->assertRedirect();

        $this->actingAsSso($user)->post(route('penilaian-rekap.deskripsi.regenerate'), [
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapel->sqid,
            'periode' => 'semester',
        ])->assertRedirect();

        $deskripsi1 = RekapMapel::query()
            ->where('anggota_kelas_id', $anggota1->id)
            ->where('periode', 'semester')
            ->value('deskripsi_capaian');
        $this->assertSame(
            'Menunjukkan penguasaan yang sangat baik dalam Memahami dan membaca surat pendek, Melafalkan makhroj, hafalan, dan arti, dan Menjelaskan isi kandungan surat pendek.',
            $deskripsi1
        );
    }

    public function test_dynamic_sumatif_bab_per_mapel_merges_mid_and_semester_with_three_component_weights_and_excel_roundtrip(): void
    {
        $user = $this->createAdminUser();
        $ta = TahunAjaran::factory()->active()->create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
        ]);
        $mapelMat = MataPelajaran::create([
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'unit' => 'SD',
            'sifat' => 'Wajib',
            'urutan' => 1,
            'status' => true,
        ]);
        $mapelIpas = MataPelajaran::create([
            'singkatan' => 'IPAS',
            'nama_mata_pelajaran' => 'Ilmu Pengetahuan Alam dan Sosial',
            'unit' => 'SD',
            'sifat' => 'Wajib',
            'urutan' => 2,
            'status' => true,
        ]);
        $kelas = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'tingkatan_kelas' => '4',
            'nama_kelas' => '4 Umar',
            'jenjang_kelas' => 'SD',
            'status' => true,
        ]);

        $siswa = Siswa::factory()->create(['nis' => '20260401', 'nama_lengkap' => 'Budi Santoso']);
        $anggota = AnggotaKelas::create([
            'siswa_nis' => $siswa->nis,
            'akademik_master_kelas_id' => $kelas->id,
            'pendaftaran' => 'baru',
        ]);

        $tp1 = TujuanPembelajaran::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapelMat->id,
            'tingkatan_kelas' => '4',
            'fase' => 'B',
            'kode_tp' => 'TP 4.1',
            'tujuan_pembelajaran' => 'Menghitung operasi bilangan cacah',
            'urutan' => 1,
            'status' => true,
        ]);
        $aspekMid = AspekTp::create([
            'tujuan_pembelajaran_id' => $tp1->sqid,
            'periode' => 'mid',
            'nama_aspek' => 'Nilai TP',
            'urutan' => 1,
        ]);

        // Tambah 2 Sumatif Bab di Mid untuk Matematika & 1 Sumatif Bab di IPAS (beda jumlah antar mapel)
        $this->actingAsSso($user)->post(route('penilaian-tp.sumatif-bab.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'mata_pelajaran_id' => $mapelMat->sqid,
            'tingkatan_kelas' => '4',
            'periode' => 'mid',
            'nama_bab' => 'Bab 1',
            'keterangan' => 'Bilangan Cacah',
            'kelas_id' => $kelas->sqid,
        ])->assertRedirect();

        $this->actingAsSso($user)->post(route('penilaian-tp.sumatif-bab.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'mata_pelajaran_id' => $mapelMat->sqid,
            'tingkatan_kelas' => '4',
            'periode' => 'mid',
            'nama_bab' => 'Bab 2',
            'keterangan' => 'Pecahan',
            'kelas_id' => $kelas->sqid,
        ])->assertRedirect();

        $this->actingAsSso($user)->post(route('penilaian-tp.sumatif-bab.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'mata_pelajaran_id' => $mapelIpas->sqid,
            'tingkatan_kelas' => '4',
            'periode' => 'mid',
            'nama_bab' => 'Bab 1 IPA',
            'kelas_id' => $kelas->sqid,
        ])->assertRedirect();

        $mtkMidBabs = SumatifBab::query()
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelMat->id)
            ->where('periode', 'mid')
            ->orderBy('urutan')
            ->get();
        $this->assertCount(2, $mtkMidBabs);
        $this->assertSame(1, SumatifBab::query()->where('akademik_pelajaran_mata_pelajaran_id', $mapelIpas->id)->count());

        // Set bobot 40% TP + 40% Sumatif Bab + 20% Ujian
        $this->actingAsSso($user)->post(route('penilaian-tp.bobot.update'), [
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapelMat->sqid,
            'periode' => 'mid',
            'bobot_tp' => 40,
            'bobot_sumatif' => 40,
            'bobot_ujian' => 20,
        ])->assertRedirect();

        // Simpan nilai Mid: TP=80, Bab1=85, Bab2=95 (Rata=90), STS=100 => 0.4*80 + 0.4*90 + 0.2*100 = 32+36+20 = 88
        $this->actingAsSso($user)->post(route('penilaian-tp.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapelMat->sqid,
            'periode' => 'mid',
            'nilai' => [
                $anggota->id => [$aspekMid->id => 80],
            ],
            'sumatif' => [
                $anggota->id => [
                    $mtkMidBabs[0]->id => 85,
                    $mtkMidBabs[1]->id => 95,
                ],
            ],
            'ujian' => [
                $anggota->id => 100,
            ],
        ])->assertRedirect();

        $rekapMid = RekapMapel::query()
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelMat->id)
            ->where('anggota_kelas_id', $anggota->id)
            ->where('periode', 'mid')
            ->firstOrFail();
        $this->assertEquals(90.0, $rekapMid->rata_rata_sumatif);
        $this->assertEquals(88.0, $rekapMid->nilai_akhir_rapor);

        // Tambah Bab 3 di Semester dan simpan nilai 75 => gabungan Mid(85,95)+Sem(75) = 255/3 = 85
        $this->actingAsSso($user)->post(route('penilaian-tp.sumatif-bab.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'mata_pelajaran_id' => $mapelMat->sqid,
            'tingkatan_kelas' => '4',
            'periode' => 'semester',
            'nama_bab' => 'Bab 3',
            'kelas_id' => $kelas->sqid,
        ])->assertRedirect();

        $mtkSemBab = SumatifBab::query()
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelMat->id)
            ->where('periode', 'semester')
            ->firstOrFail();

        $this->actingAsSso($user)->post(route('penilaian-tp.store'), [
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapelMat->sqid,
            'periode' => 'semester',
            'sumatif' => [
                $anggota->id => [$mtkSemBab->id => 75],
            ],
            'ujian' => [
                $anggota->id => 90,
            ],
        ])->assertRedirect();

        // NA Rapor Sem = 0.4*80 + 0.4*85 + 0.2*90 = 32 + 34 + 18 = 84
        $rekapSem = RekapMapel::query()
            ->where('akademik_pelajaran_mata_pelajaran_id', $mapelMat->id)
            ->where('anggota_kelas_id', $anggota->id)
            ->where('periode', 'semester')
            ->firstOrFail();
        $this->assertEquals(85.0, $rekapSem->rata_rata_sumatif);
        $this->assertEquals(84.0, $rekapSem->nilai_akhir_rapor);

        // Export & Re-import Semester Excel: kolom Bab 1 (Mid) & Bab 2 (Mid) diabaikan, Bab 3 berubah ke 90 -> rata sumatif (85+95+90)/3 = 90
        $exportRows = (new PenilaianTpExport([
            'tahun_ajaran_id' => $ta->sqid,
            'kelas_id' => $kelas->sqid,
            'mata_pelajaran_id' => $mapelMat->sqid,
            'periode' => 'semester',
        ]))->array();
        $bab3ColIdx = array_search('Bab 3', $exportRows[1], true);
        $this->assertIsInt($bab3ColIdx);
        $exportRows[2][$bab3ColIdx] = 90;

        $import = new PenilaianTpSheetImport($kelas, $mapelMat, 'semester', 'NIPY-SD-02');
        $import->collection(new Collection($exportRows));
        $this->assertSame([], $import->errors);
        $this->assertEquals(90.0, (float) NilaiSumatifBab::query()->where('sumatif_bab_id', $mtkSemBab->id)->value('nilai'));
        $this->assertEquals(90.0, (float) $rekapSem->refresh()->rata_rata_sumatif);

        // Hapus Bab 3 -> otomatis menghitung ulang Rata Sumatif kembali ke gabungan Mid (90)
        $this->actingAsSso($user)->delete(route('penilaian-tp.sumatif-bab.destroy', $mtkSemBab), [
            'kelas_id' => $kelas->sqid,
        ])->assertRedirect();
        $this->assertEquals(90.0, (float) $rekapSem->refresh()->rata_rata_sumatif);
    }
}
