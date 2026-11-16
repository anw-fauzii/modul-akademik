<?php

namespace Tests\Unit;

use App\Exports\Admin\Penilaian\PenilaianTpExport;
use App\Http\Controllers\Admin\Penilaian\KkmController;
use App\Http\Controllers\Admin\Penilaian\PenilaianTpController;
use App\Http\Requests\Admin\Penilaian\AspekTpRequest;
use App\Http\Requests\Admin\Penilaian\ImportNilaiTpRequest;
use App\Http\Requests\Admin\Penilaian\KkmRequest;
use App\Http\Requests\Admin\Penilaian\SimpanNilaiTpRequest;
use App\Http\Requests\Admin\Penilaian\UpdateBobotRaporRequest;
use App\Imports\Admin\Penilaian\PenilaianTpImport;
use App\Imports\Admin\Penilaian\PenilaianTpSheetImport;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\Penilaian\AspekTp;
use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\KkmPredikat;
use App\Models\Admin\Penilaian\NilaiAspek;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\Penilaian\RekapMapel;
use App\Models\Admin\Penilaian\RekapTp;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Services\Admin\Penilaian\KkmService;
use App\Services\Admin\Penilaian\PenilaianTpService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCase;

class PenilaianTpModelAndViewTest extends TestCase
{
    public function test_penilaian_models_metadata_casts_and_relations_are_configured(): void
    {
        $kkm = new Kkm;
        $this->assertSame('akademik_penilaian_kkm', $kkm->getTable());
        $this->assertSame([
            'akademik_master_tahun_ajaran_id',
            'akademik_pelajaran_mata_pelajaran_id',
            'tingkatan_kelas',
            'nilai_kkm',
            'bobot_tp',
            'bobot_ujian',
            'status',
        ], $kkm->getFillable());
        $this->assertSame('float', $kkm->getCasts()['nilai_kkm']);
        $this->assertSame('integer', $kkm->getCasts()['bobot_tp']);
        $this->assertSame('integer', $kkm->getCasts()['bobot_ujian']);
        $this->assertSame('boolean', $kkm->getCasts()['status']);
        $this->assertNull(Kkm::validateBobotPair(70, 30));
        $this->assertNotNull(Kkm::validateBobotPair(70, 20));
        $this->assertSame('kkm_id', $kkm->predikats()->getForeignKeyName());

        $predikat = new KkmPredikat;
        $this->assertSame('akademik_penilaian_kkm_predikat', $predikat->getTable());
        $this->assertSame('boolean', $predikat->getCasts()['is_tuntas']);
        $this->assertSame('kkm_id', $predikat->kkm()->getForeignKeyName());

        $periodeTp = new PeriodeTp;
        $this->assertSame('akademik_penilaian_periode_tp', $periodeTp->getTable());
        $this->assertSame('boolean', $periodeTp->getCasts()['masuk_rapor']);
        $this->assertSame('tujuan_pembelajaran_id', $periodeTp->tujuanPembelajaran()->getForeignKeyName());

        $aspekTp = new AspekTp;
        $this->assertSame('akademik_penilaian_aspek_tp', $aspekTp->getTable());
        $this->assertSame('integer', $aspekTp->getCasts()['bobot']);
        $this->assertSame('aspek_tp_id', $aspekTp->nilaiAspek()->getForeignKeyName());

        $nilaiAspek = new NilaiAspek;
        $this->assertSame('akademik_penilaian_nilai_aspek', $nilaiAspek->getTable());
        $this->assertSame('float', $nilaiAspek->getCasts()['nilai']);
        $this->assertSame('anggota_kelas_id', $nilaiAspek->anggotaKelas()->getForeignKeyName());

        $rekapTp = new RekapTp;
        $this->assertSame('akademik_penilaian_rekap_tp', $rekapTp->getTable());
        $this->assertSame('boolean', $rekapTp->getCasts()['tuntas_kkm']);

        $rekapMapel = new RekapMapel;
        $this->assertSame('akademik_penilaian_rekap_mapel', $rekapMapel->getTable());
        $this->assertSame('float', $rekapMapel->getCasts()['nilai_akhir_rapor']);
        $this->assertSame('float', $rekapMapel->getCasts()['nilai_ujian']);
        $this->assertSame('integer', $rekapMapel->getCasts()['bobot_tp_snapshot']);
        $this->assertSame('integer', $rekapMapel->getCasts()['bobot_ujian_snapshot']);
        $this->assertSame('STS', PeriodeTp::ujianLabel(PeriodeTp::PERIODE_MID));
        $this->assertSame('SAS', PeriodeTp::ujianLabel(PeriodeTp::PERIODE_SEMESTER));
    }

    #[TestWith([75.0, 92, 83, 75, 74])]
    #[TestWith([70.0, 90, 80, 70, 69])]
    #[TestWith([80.0, 94, 87, 80, 79])]
    public function test_generate_default_predikats_builds_contiguous_ranges_for_kkm(
        float $kkm,
        int $minA,
        int $minB,
        int $minC,
        int $maxD
    ): void {
        $rows = Kkm::generateDefaultPredikats($kkm);

        $this->assertCount(4, $rows);
        $this->assertSame('A', $rows[0]['predikat']);
        $this->assertSame($minA, $rows[0]['nilai_min']);
        $this->assertSame(100, $rows[0]['nilai_max']);
        $this->assertTrue($rows[0]['is_tuntas']);

        $this->assertSame('B', $rows[1]['predikat']);
        $this->assertSame($minB, $rows[1]['nilai_min']);
        $this->assertSame($minA - 1, $rows[1]['nilai_max']);
        $this->assertTrue($rows[1]['is_tuntas']);

        $this->assertSame('C', $rows[2]['predikat']);
        $this->assertSame($minC, $rows[2]['nilai_min']);
        $this->assertSame($minB - 1, $rows[2]['nilai_max']);
        $this->assertTrue($rows[2]['is_tuntas']);

        $this->assertSame('D', $rows[3]['predikat']);
        $this->assertSame(0, $rows[3]['nilai_min']);
        $this->assertSame($maxD, $rows[3]['nilai_max']);
        $this->assertFalse($rows[3]['is_tuntas']);
    }

    public function test_generate_default_predikats_handles_kkm_100_edge_case(): void
    {
        $rows = Kkm::generateDefaultPredikats(100.0);

        $this->assertCount(2, $rows);
        $this->assertSame(['A', 100, 100, true], [$rows[0]['predikat'], $rows[0]['nilai_min'], $rows[0]['nilai_max'], $rows[0]['is_tuntas']]);
        $this->assertSame(['D', 0, 99, false], [$rows[1]['predikat'], $rows[1]['nilai_min'], $rows[1]['nilai_max'], $rows[1]['is_tuntas']]);
    }

    #[TestWith([91.6, 'A', true])]
    #[TestWith([83.67, 'B', true])]
    #[TestWith([78.33, 'C', true])]
    #[TestWith([74.4, 'D', false])]
    public function test_resolve_predikat_for_nilai_uses_rounded_score(
        float $nilai,
        string $expectedPredikat,
        bool $expectedTuntas
    ): void {
        $predikats = new Collection(array_map(
            static fn (array $row): KkmPredikat => KkmPredikat::make($row),
            Kkm::generateDefaultPredikats(75.0)
        ));

        $matched = Kkm::resolvePredikatForNilai($nilai, $predikats);

        $this->assertNotNull($matched);
        $this->assertSame($expectedPredikat, $matched->predikat);
        $this->assertSame($expectedTuntas, $matched->is_tuntas);
    }

    public function test_kkm_service_validates_and_normalizes_contiguous_predikat_ranges(): void
    {
        $service = new KkmService;

        $normalized = $service->validateAndNormalizePredikats([
            ['predikat' => 'd', 'keterangan' => 'Perlu Bimbingan', 'nilai_min' => 0, 'nilai_max' => 74, 'is_tuntas' => false],
            ['predikat' => 'b', 'keterangan' => 'Baik', 'nilai_min' => 83, 'nilai_max' => 91, 'is_tuntas' => true],
            ['predikat' => 'a', 'keterangan' => 'Sangat Baik', 'nilai_min' => 92, 'nilai_max' => 100, 'is_tuntas' => true],
            ['predikat' => 'c', 'keterangan' => 'Cukup', 'nilai_min' => 75, 'nilai_max' => 82, 'is_tuntas' => true],
        ]);

        $this->assertCount(4, $normalized);
        $this->assertSame('A', $normalized[0]['predikat']);
        $this->assertSame(1, $normalized[0]['urutan']);
        $this->assertSame('D', $normalized[3]['predikat']);
        $this->assertSame(4, $normalized[3]['urutan']);
    }

    public function test_kkm_service_rejects_gaps_overlaps_duplicate_codes_and_open_bounds(): void
    {
        $service = new KkmService;

        $invalidCases = [
            'gap' => [
                ['predikat' => 'A', 'keterangan' => 'Sangat Baik', 'nilai_min' => 80, 'nilai_max' => 100, 'is_tuntas' => true],
                ['predikat' => 'D', 'keterangan' => 'Perlu Bimbingan', 'nilai_min' => 0, 'nilai_max' => 74, 'is_tuntas' => false],
            ],
            'overlap' => [
                ['predikat' => 'A', 'keterangan' => 'Sangat Baik', 'nilai_min' => 75, 'nilai_max' => 100, 'is_tuntas' => true],
                ['predikat' => 'D', 'keterangan' => 'Perlu Bimbingan', 'nilai_min' => 0, 'nilai_max' => 75, 'is_tuntas' => false],
            ],
            'missing_zero' => [
                ['predikat' => 'A', 'keterangan' => 'Sangat Baik', 'nilai_min' => 5, 'nilai_max' => 100, 'is_tuntas' => true],
            ],
            'duplicate' => [
                ['predikat' => 'A', 'keterangan' => 'Satu', 'nilai_min' => 80, 'nilai_max' => 100, 'is_tuntas' => true],
                ['predikat' => 'a', 'keterangan' => 'Dua', 'nilai_min' => 0, 'nilai_max' => 79, 'is_tuntas' => false],
            ],
        ];

        foreach ($invalidCases as $caseName => $payload) {
            try {
                $service->validateAndNormalizePredikats($payload);
                $this->fail("Expected ValidationException for case: {$caseName}");
            } catch (ValidationException $exception) {
                $this->assertArrayHasKey('predikats', $exception->errors(), "Missing predikats error key for {$caseName}");
            }
        }
    }

    public function test_compute_na_tp_matches_spreadsheet_rounding_and_null_rules(): void
    {
        // TP 5.1: Pemahaman=100, Bacaan=80 => 90
        $this->assertSame(90.0, PenilaianTpService::computeNaTp([1 => 100, 2 => 80]));

        // TP 5.2: Makhroj, Hafalan, Artinya sesuai contoh tabel guru senior
        $this->assertSame(78.0, PenilaianTpService::computeNaTp([1 => 80, 2 => 80, 3 => 75]));
        $this->assertSame(84.0, PenilaianTpService::computeNaTp([1 => 88, 2 => 88, 3 => 75]));
        $this->assertSame(82.0, PenilaianTpService::computeNaTp([1 => 85, 2 => 85, 3 => 75]));

        // Sel kosong / null tidak dianggap 0
        $this->assertSame(90.0, PenilaianTpService::computeNaTp([1 => 90, 2 => null, 3 => '']));
        $this->assertNull(PenilaianTpService::computeNaTp([1 => null, 2 => '']));

        // Angka 0 eksplisit tetap dihitung
        $this->assertSame(40.0, PenilaianTpService::computeNaTp([1 => 0, 2 => 80]));

        // Mendukung pembobotan per aspek
        $this->assertSame(93.0, PenilaianTpService::computeNaTp([1 => 100, 2 => 80], [1 => 2, 2 => 1]));
    }

    public function test_compute_na_rapor_handles_tp_only_weighted_ujian_and_empty_ujian_fallback(): void
    {
        $this->assertSame(85.0, PenilaianTpService::computeNaRapor(85.0, 90.0, 100, 0));
        $this->assertSame(85.0, PenilaianTpService::computeNaRapor(85.0, null, 70, 30));
        $this->assertSame(87.0, PenilaianTpService::computeNaRapor(85.0, 90.0, 70, 30));
        $this->assertSame(88.0, PenilaianTpService::computeNaRapor(null, 88.0, 70, 30));
        $this->assertNull(PenilaianTpService::computeNaRapor(null, 88.0, 100, 0));
        $this->assertNull(PenilaianTpService::computeNaRapor(null, null, 70, 30));
    }

    public function test_format_daftar_topik_and_build_deskripsi_capaian_group_all_scored_tps_by_kkm_predikat(): void
    {
        $this->assertSame('', PenilaianTpService::formatDaftarTopik([]));
        $this->assertSame('Bilangan', PenilaianTpService::formatDaftarTopik(['Bilangan']));
        $this->assertSame('Bilangan dan Pecahan', PenilaianTpService::formatDaftarTopik(['Bilangan', 'Pecahan']));
        $this->assertSame(
            'Bilangan, Pecahan, dan Geometri',
            PenilaianTpService::formatDaftarTopik(['Bilangan', 'Pecahan', 'Geometri'])
        );
        $this->assertSame(
            'Bilangan dan Pecahan',
            PenilaianTpService::formatDaftarTopik(['Bilangan', 'Pecahan', 'Bilangan', ''])
        );

        $this->assertNull(PenilaianTpService::buildDeskripsiCapaian([], 75.0));
        $this->assertNull(PenilaianTpService::buildDeskripsiCapaian([
            ['kode_tp' => 'TP 5.1', 'tujuan_pembelajaran' => 'Membaca', 'lingkup_materi' => 'Bacaan', 'nilai' => null],
        ], 75.0));

        // KKM 75 => A: 92-100 (Sangat Baik), B: 83-91 (Baik), C: 75-82 (Cukup), D: 0-74 (Perlu Bimbingan)
        $narrative = PenilaianTpService::buildDeskripsiCapaian([
            ['kode_tp' => 'TP 5.1', 'tujuan_pembelajaran' => 'TP 1', 'lingkup_materi' => 'Teks Narasi', 'nilai' => 95], // A (1/2)
            ['kode_tp' => 'TP 5.2', 'tujuan_pembelajaran' => 'TP 2', 'lingkup_materi' => 'Surat Pendek', 'nilai' => 92], // A (2/2) -> pakai "dan"
            ['kode_tp' => 'TP 5.3', 'tujuan_pembelajaran' => 'TP 3', 'lingkup_materi' => 'Pengurangan', 'nilai' => 88], // B (1/3)
            ['kode_tp' => 'TP 5.4', 'tujuan_pembelajaran' => 'TP 4', 'lingkup_materi' => 'Perkalian', 'nilai' => 85], // B (2/3)
            ['kode_tp' => 'TP 5.5', 'tujuan_pembelajaran' => 'TP 5', 'lingkup_materi' => 'Pembagian', 'nilai' => 83], // B (3/3) -> pakai koma & "dan"
            ['kode_tp' => 'TP 5.6', 'tujuan_pembelajaran' => 'TP 6', 'lingkup_materi' => 'Pidato', 'nilai' => 78], // C (1/1)
            ['kode_tp' => 'TP 5.7', 'tujuan_pembelajaran' => 'TP 7', 'lingkup_materi' => null, 'nilai' => null], // null diabaikan
            ['kode_tp' => 'TP 5.8', 'tujuan_pembelajaran' => 'Memahami pecahan sederhana.', 'lingkup_materi' => '', 'nilai' => 65], // D fallback teks TP
        ], 75.0);

        $this->assertSame(
            'Menunjukkan penguasaan yang sangat baik dalam Teks Narasi dan Surat Pendek. '
            .'Menunjukkan penguasaan yang baik dalam Pengurangan, Perkalian, dan Pembagian. '
            .'Menunjukkan penguasaan yang cukup dalam Pidato. '
            .'Perlu bimbingan dan pendampingan lebih lanjut dalam Memahami pecahan sederhana.',
            $narrative
        );

        // Uji dengan rentang KKM predikat kustom (KKM 80 dengan keterangan kustom)
        $customPredikats = new Collection([
            KkmPredikat::make(['predikat' => 'SB', 'keterangan' => 'Istimewa', 'nilai_min' => 90, 'nilai_max' => 100, 'is_tuntas' => true, 'urutan' => 1]),
            KkmPredikat::make(['predikat' => 'BSH', 'keterangan' => 'Kompeten', 'nilai_min' => 80, 'nilai_max' => 89, 'is_tuntas' => true, 'urutan' => 2]),
            KkmPredikat::make(['predikat' => 'PB', 'keterangan' => 'Belum Terpenuhi', 'nilai_min' => 0, 'nilai_max' => 79, 'is_tuntas' => false, 'urutan' => 3]),
        ]);

        $customNarrative = PenilaianTpService::buildDeskripsiCapaian([
            ['kode_tp' => 'TP 1', 'tujuan_pembelajaran' => 'A', 'lingkup_materi' => 'Tajwid', 'nilai' => 91],
            ['kode_tp' => 'TP 2', 'tujuan_pembelajaran' => 'B', 'lingkup_materi' => 'Hafalan', 'nilai' => 79],
            ['kode_tp' => 'TP 3', 'tujuan_pembelajaran' => 'C', 'lingkup_materi' => 'Adab', 'nilai' => 0],
        ], 80.0, $customPredikats);

        $this->assertSame(
            'Menunjukkan penguasaan yang istimewa dalam Tajwid. '
            .'Perlu pendampingan dan latihan lebih lanjut (belum terpenuhi) dalam Hafalan dan Adab.',
            $customNarrative
        );
    }

    public function test_form_requests_normalize_inputs_and_enforce_sd_rules(): void
    {
        $kkmReq = KkmRequest::create('/penilaian/kkm', 'POST', [
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'tingkatan_kelas' => ' 5 ',
            'nilai_kkm' => 75,
            'status' => '1',
            'predikats' => [
                ['predikat' => ' a ', 'keterangan' => ' Sangat Baik ', 'nilai_min' => 92, 'nilai_max' => 100, 'is_tuntas' => '1'],
                ['predikat' => ' d ', 'keterangan' => ' Perlu Bimbingan ', 'nilai_min' => 0, 'nilai_max' => 91, 'is_tuntas' => '0'],
            ],
        ]);
        (new ReflectionMethod(KkmRequest::class, 'prepareForValidation'))->invoke($kkmReq);

        $this->assertSame('5', $kkmReq->input('tingkatan_kelas'));
        $this->assertTrue($kkmReq->input('status'));
        $this->assertSame('A', $kkmReq->input('predikats.0.predikat'));
        $this->assertTrue($kkmReq->input('predikats.0.is_tuntas'));
        $this->assertFalse($kkmReq->input('predikats.1.is_tuntas'));

        $kkmRules = $kkmReq->rules();
        $this->assertTrue(collect($kkmRules['tingkatan_kelas'])->contains(static fn ($r) => $r instanceof Unique));

        $aspekReq = AspekTpRequest::create('/penilaian/nilai-tp/aspek', 'POST', [
            'tujuan_pembelajaran_id' => 10,
            'periode' => ' SEMESTER ',
            'nama_aspek' => '  Makhroj  ',
            'teknik_penilaian' => ' LISAN ',
            'bobot' => '',
        ]);
        (new ReflectionMethod(AspekTpRequest::class, 'prepareForValidation'))->invoke($aspekReq);

        $this->assertSame('semester', $aspekReq->input('periode'));
        $this->assertSame('Makhroj', $aspekReq->input('nama_aspek'));
        $this->assertSame('lisan', $aspekReq->input('teknik_penilaian'));
        $this->assertSame(100, $aspekReq->input('bobot'));
        $this->assertTrue($aspekReq->input('masuk_hitung_na_tp'));

        $nilaiReq = SimpanNilaiTpRequest::create('/penilaian/nilai-tp/simpan', 'POST', [
            'kelas_id' => 4,
            'mata_pelajaran_id' => 2,
            'periode' => ' MID ',
            'nilai' => [
                101 => [5 => ' 87,5 ', 6 => '   '],
            ],
            'ujian' => [
                101 => ' 92,5 ',
                102 => '   ',
            ],
        ]);
        (new ReflectionMethod(SimpanNilaiTpRequest::class, 'prepareForValidation'))->invoke($nilaiReq);

        $this->assertSame('mid', $nilaiReq->input('periode'));
        $this->assertSame('87.5', $nilaiReq->input('nilai.101.5'));
        $this->assertNull($nilaiReq->input('nilai.101.6'));
        $this->assertSame('92.5', $nilaiReq->input('ujian.101'));
        $this->assertNull($nilaiReq->input('ujian.102'));

        $nilaiRules = $nilaiReq->rules();
        $this->assertTrue(collect($nilaiRules['kelas_id'])->contains(static fn ($r) => $r instanceof Exists));
        $this->assertContains('nullable', $nilaiRules['nilai']);
        $this->assertContains('max:100', $nilaiRules['nilai.*.*']);
        $this->assertContains('max:100', $nilaiRules['ujian.*']);

        $bobotReq = UpdateBobotRaporRequest::create('/penilaian/nilai-tp/bobot', 'POST', [
            'kelas_id' => 4,
            'mata_pelajaran_id' => 2,
            'periode' => ' SEMESTER ',
            'bobot_tp' => '70',
            'bobot_ujian' => '20',
        ]);
        (new ReflectionMethod(UpdateBobotRaporRequest::class, 'prepareForValidation'))->invoke($bobotReq);
        $this->assertSame('semester', $bobotReq->input('periode'));
        $bobotValidator = app('validator')->make($bobotReq->all(), ['bobot_tp' => 'integer', 'bobot_ujian' => 'integer']);
        foreach ($bobotReq->after() as $hook) {
            $bobotValidator->after($hook);
        }
        $this->assertTrue($bobotValidator->fails());
        $this->assertArrayHasKey('bobot_ujian', $bobotValidator->errors()->toArray());

        $importReq = ImportNilaiTpRequest::create('/penilaian/nilai-tp/import', 'POST', ['periode' => ' SEMESTER ']);
        (new ReflectionMethod(ImportNilaiTpRequest::class, 'prepareForValidation'))->invoke($importReq);
        $this->assertSame('semester', $importReq->input('periode'));
        $this->assertContains('mimes:xlsx,xls,csv', $importReq->rules()['file']);
    }

    public function test_kkm_and_penilaian_controllers_delegate_cleanly_and_stream_excel(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;
        $mapel = MataPelajaran::make(['singkatan' => 'PAI', 'nama_mata_pelajaran' => 'Pendidikan Agama Islam', 'unit' => 'SD']);
        $mapel->id = 2;

        $kkm = Kkm::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'tingkatan_kelas' => '5',
            'nilai_kkm' => 75,
            'status' => true,
        ]);
        $kkm->id = 10;
        $kkm->setRelation('tahunAjaran', $ta);
        $kkm->setRelation('mataPelajaran', $mapel);
        $kkm->setRelation('predikats', new Collection);

        $kkmService = $this->createMock(KkmService::class);
        $kkmService->expects($this->once())->method('getIndexData')->willReturn([
            'kkmList' => new Collection([$kkm]),
            'filters' => ['tahun_ajaran_id' => '1', 'mata_pelajaran_id' => '', 'tingkatan_kelas' => ''],
            'tahunAjaranOptions' => new Collection([$ta]),
            'mataPelajaranOptions' => new Collection([$mapel]),
            'tingkatanOptions' => Kkm::SD_TINGKATAN_OPTIONS,
        ]);
        $kkmService->expects($this->exactly(2))->method('getFormOptions')->willReturn([
            'tahunAjaranOptions' => new Collection([$ta]),
            'mataPelajaranOptions' => new Collection([$mapel]),
            'tingkatanOptions' => Kkm::SD_TINGKATAN_OPTIONS,
            'defaultPredikats' => Kkm::generateDefaultPredikats(75),
            'defaults' => ['tahun_ajaran_id' => '1', 'mata_pelajaran_id' => '', 'tingkatan_kelas' => '1'],
        ]);
        $kkmService->expects($this->exactly(2))->method('saveWithPredikats')->willReturn($kkm);
        $kkmService->expects($this->once())->method('delete')->with($kkm)->willReturn(true);

        $kkmController = new KkmController($kkmService);
        $this->assertSame('admin.penilaian.kkm.index', $kkmController->index(Request::create('/penilaian/kkm', 'GET'))->name());
        $this->assertSame('admin.penilaian.kkm.create', $kkmController->create(Request::create('/penilaian/kkm/create', 'GET'))->name());
        $this->assertSame('admin.penilaian.kkm.edit', $kkmController->edit($kkm)->name());

        $kkmReq = KkmRequest::create('/penilaian/kkm', 'POST', [
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'tingkatan_kelas' => '5',
            'nilai_kkm' => 75,
            'status' => true,
            'predikats' => Kkm::generateDefaultPredikats(75),
        ]);
        $val = app('validator')->make($kkmReq->all(), ['akademik_master_tahun_ajaran_id' => 'required']);
        $val->validate();
        $kkmReq->setValidator($val);

        $this->assertTrue($kkmController->store($kkmReq)->isRedirect(route('penilaian-kkm.index')));
        $this->assertTrue($kkmController->update($kkmReq, $kkm)->isRedirect(route('penilaian-kkm.index')));
        $this->assertTrue($kkmController->destroy($kkm)->isRedirect(route('penilaian-kkm.index')));

        $tp = TujuanPembelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'tingkatan_kelas' => '5',
            'kode_tp' => 'TP 5.1',
            'tujuan_pembelajaran' => 'Memahami bacaan',
        ]);
        $tp->id = 30;

        $aspek = AspekTp::make([
            'tujuan_pembelajaran_id' => 30,
            'periode' => PeriodeTp::PERIODE_MID,
            'nama_aspek' => 'Pemahaman',
        ]);
        $aspek->id = 40;
        $aspek->setRelation('tujuanPembelajaran', $tp);

        $rekapMapel = RekapMapel::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'anggota_kelas_id' => 50,
            'periode' => PeriodeTp::PERIODE_SEMESTER,
        ]);
        $rekapMapel->id = 60;

        $penilaianService = $this->createMock(PenilaianTpService::class);
        $penilaianService->expects($this->once())->method('getMatrixData')->willReturn([]);
        $penilaianService->expects($this->once())->method('getRekapData')->willReturn([]);
        $penilaianService->expects($this->once())->method('toggleMasukRapor')->with($tp, 'mid', false)->willReturn(new PeriodeTp);
        $penilaianService->expects($this->once())->method('deleteAspek')->with($aspek);
        $penilaianService->expects($this->once())->method('updateDeskripsi')->with($rekapMapel, 'Bagus sekali')->willReturn($rekapMapel);

        $penilaianController = new PenilaianTpController($penilaianService);
        $this->assertSame('admin.penilaian.nilaiTp.index', $penilaianController->index(Request::create('/penilaian/nilai-tp', 'GET'))->name());
        $this->assertSame('admin.penilaian.rekap.index', $penilaianController->rekap(Request::create('/penilaian/rekap', 'GET'))->name());
        $this->assertStringContainsString(
            route('penilaian-tp.index'),
            $penilaianController->toggleMasukRapor(Request::create('/penilaian/nilai-tp/tp/30/toggle-masuk-rapor', 'POST', ['periode' => 'mid', 'masuk_rapor' => '0']), $tp)->getTargetUrl()
        );
        $this->assertStringContainsString(
            route('penilaian-tp.index'),
            $penilaianController->destroyAspek(Request::create('/penilaian/nilai-tp/aspek/40', 'DELETE'), $aspek)->getTargetUrl()
        );
        $this->assertStringContainsString(
            route('penilaian-rekap.index'),
            $penilaianController->updateDeskripsi(Request::create('/penilaian/rekap/60/deskripsi', 'PUT', ['deskripsi_capaian' => 'Bagus sekali']), $rekapMapel)->getTargetUrl()
        );

        Excel::fake();
        $penilaianController->export(Request::create('/penilaian/nilai-tp/export', 'GET', ['periode' => 'mid', 'kelas_id' => '5']));
        Excel::assertDownloaded('nilai-tp-mid-kelas-5-'.now()->format('Ymd-His').'.xlsx', static fn ($export): bool => $export instanceof PenilaianTpExport);
    }

    public function test_export_builds_two_row_merged_tp_aspek_structure_and_import_contracts(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;
        $kelas = Kelas::make(['tingkatan_kelas' => '5', 'nama_kelas' => '5 Al-Fatihah', 'jenjang_kelas' => 'SD']);
        $kelas->id = 11;
        $mapel = MataPelajaran::make(['singkatan' => 'PAI', 'nama_mata_pelajaran' => 'Pendidikan Agama Islam', 'unit' => 'SD']);
        $mapel->id = 12;

        $tp1 = TujuanPembelajaran::make(['kode_tp' => 'TP 5.1', 'tujuan_pembelajaran' => 'Bacaan']);
        $tp1->id = 101;
        $tp2 = TujuanPembelajaran::make(['kode_tp' => 'TP 5.2', 'tujuan_pembelajaran' => 'Hafalan']);
        $tp2->id = 102;

        $a1 = AspekTp::make(['tujuan_pembelajaran_id' => 101, 'nama_aspek' => 'Pemahaman']);
        $a1->id = 201;
        $a2 = AspekTp::make(['tujuan_pembelajaran_id' => 101, 'nama_aspek' => 'Bacaan']);
        $a2->id = 202;
        $a3 = AspekTp::make(['tujuan_pembelajaran_id' => 102, 'nama_aspek' => 'Makhroj']);
        $a3->id = 203;
        $a4 = AspekTp::make(['tujuan_pembelajaran_id' => 102, 'nama_aspek' => 'Hafalan']);
        $a4->id = 204;
        $a5 = AspekTp::make(['tujuan_pembelajaran_id' => 102, 'nama_aspek' => 'Artinya']);
        $a5->id = 205;

        $siswa = Siswa::make(['nis' => '202600001', 'nama_lengkap' => 'Aisyah Putri']);
        $anggota = AnggotaKelas::make(['siswa_nis' => '202600001', 'akademik_master_kelas_id' => 11]);
        $anggota->id = 301;
        $anggota->setRelation('siswa', $siswa);

        $mockMatrix = [
            'selectedKelas' => $kelas,
            'selectedMapel' => $mapel,
            'periode' => PeriodeTp::PERIODE_MID,
            'tpList' => new Collection([$tp1, $tp2]),
            'aspeksByTp' => collect([
                101 => new Collection([$a1, $a2]),
                102 => new Collection([$a3, $a4, $a5]),
            ]),
            'students' => new Collection([$anggota]),
            'nilaiLookup' => [
                '301:201' => NilaiAspek::make(['nilai' => 100]),
                '301:202' => NilaiAspek::make(['nilai' => 80]),
                '301:203' => NilaiAspek::make(['nilai' => 80]),
                '301:204' => NilaiAspek::make(['nilai' => 80]),
                '301:205' => NilaiAspek::make(['nilai' => 75]),
            ],
            'rekapTpLookup' => [
                '301:101:mid' => RekapTp::make(['nilai_akhir_tp' => 90]),
                '301:102:mid' => RekapTp::make(['nilai_akhir_tp' => 78]),
            ],
            'rekapMapelLookup' => [
                '301:mid' => RekapMapel::make(['nilai_ujian' => 86.0, 'nilai_akhir_rapor' => 85, 'predikat_rapor' => 'B', 'tuntas_kkm' => true]),
            ],
        ];

        $mockService = $this->createMock(PenilaianTpService::class);
        $mockService->expects($this->once())->method('getMatrixData')->willReturn($mockMatrix);
        $this->app->instance(PenilaianTpService::class, $mockService);

        $export = new PenilaianTpExport(['periode' => 'mid']);
        $this->assertInstanceOf(Export::class, $export);
        $this->assertInstanceOf(FromArray::class, $export);
        $this->assertInstanceOf(ShouldAutoSize::class, $export);
        $this->assertInstanceOf(WithStyles::class, $export);
        $this->assertInstanceOf(WithTitle::class, $export);
        $this->assertSame('TP Mid PAI 5 Al-Fatihah', $export->title());

        $rows = $export->array();
        $this->assertSame(['No', 'NIS', 'Nama Siswa', 'TP 5.1', '', '', 'TP 5.2', '', '', '', 'STS', 'NA Rapor', 'Predikat', 'Ketuntasan'], $rows[0]);
        $this->assertSame(['', '', '', 'Pemahaman', 'Bacaan', 'NA TP', 'Makhroj', 'Hafalan', 'Artinya', 'NA TP', 'Ujian', 'Nilai', 'Huruf', 'KKM'], $rows[1]);
        $this->assertSame([1, '202600001', 'Aisyah Putri', 100.0, 80.0, 90, 80.0, 80.0, 75.0, 78, 86.0, 85, 'B', 'Tuntas'], $rows[2]);

        $import = new PenilaianTpImport($kelas, $mapel, PeriodeTp::PERIODE_MID, 'NIPY001');
        $this->assertInstanceOf(Import::class, $import);
        $this->assertInstanceOf(WithMultipleSheets::class, $import);
        $this->assertInstanceOf(PenilaianTpSheetImport::class, $import->sheets()[0]);
        $this->assertInstanceOf(SkipsEmptyRows::class, $import->sheetImport);
        $this->assertInstanceOf(ToCollection::class, $import->sheetImport);
    }

    public function test_kkm_and_penilaian_views_render_cleanly_without_lazy_loading(): void
    {
        view()->share('errors', new ViewErrorBag);

        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;
        $mapel = MataPelajaran::make(['singkatan' => 'PAI', 'nama_mata_pelajaran' => 'Pendidikan Agama Islam', 'unit' => 'SD']);
        $mapel->id = 2;
        $kelas = Kelas::make(['akademik_master_tahun_ajaran_id' => 1, 'tingkatan_kelas' => '5', 'nama_kelas' => '5 Al-Fatihah', 'jenjang_kelas' => 'SD']);
        $kelas->id = 3;

        $predikatModels = new Collection(array_map(
            static fn (array $row): KkmPredikat => KkmPredikat::make($row),
            Kkm::generateDefaultPredikats(75)
        ));

        $kkm = Kkm::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'tingkatan_kelas' => '5',
            'nilai_kkm' => 75,
            'status' => true,
        ]);
        $kkm->id = 10;
        $kkm->setRelation('tahunAjaran', $ta);
        $kkm->setRelation('mataPelajaran', $mapel);
        $kkm->setRelation('predikats', $predikatModels);

        $tp1 = TujuanPembelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'tingkatan_kelas' => '5',
            'kode_tp' => 'TP 5.1',
            'tujuan_pembelajaran' => 'Memahami bacaan surat pendek.',
            'lingkup_materi' => 'Pemahaman Bacaan',
            'urutan' => 1,
            'status' => true,
        ]);
        $tp1->id = 21;

        $aspek1 = AspekTp::make(['tujuan_pembelajaran_id' => 21, 'periode' => 'mid', 'nama_aspek' => 'Pemahaman', 'urutan' => 1]);
        $aspek1->id = 31;
        $aspek2 = AspekTp::make(['tujuan_pembelajaran_id' => 21, 'periode' => 'mid', 'nama_aspek' => 'Bacaan', 'urutan' => 2]);
        $aspek2->id = 32;

        $siswa = Siswa::make(['nis' => '202600001', 'nama_lengkap' => 'Aisyah Putri']);
        $anggota = AnggotaKelas::make(['siswa_nis' => '202600001', 'akademik_master_kelas_id' => 3]);
        $anggota->id = 41;
        $anggota->setRelation('siswa', $siswa);

        $rekapMid = RekapTp::make([
            'tujuan_pembelajaran_id' => 21,
            'anggota_kelas_id' => 41,
            'periode' => 'mid',
            'nilai_akhir_tp' => 90,
            'predikat' => 'B',
            'tuntas_kkm' => true,
        ]);
        $rekapMapel = RekapMapel::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_pelajaran_mata_pelajaran_id' => 2,
            'anggota_kelas_id' => 41,
            'periode' => 'semester',
            'rata_rata_tp' => 90,
            'nilai_ujian' => 94,
            'nilai_akhir_rapor' => 91,
            'predikat_rapor' => 'B',
            'keterangan_predikat' => 'Baik',
            'tuntas_kkm' => true,
            'deskripsi_capaian' => 'Menunjukkan penguasaan yang baik dalam Pemahaman Bacaan.',
        ]);
        $rekapMapel->id = 51;

        Model::preventLazyLoading(true);

        try {
            $kkmIndexHtml = view('admin.penilaian.kkm.index', [
                'kkmList' => new Collection([$kkm]),
                'filters' => ['tahun_ajaran_id' => '1', 'mata_pelajaran_id' => '', 'tingkatan_kelas' => ''],
                'tahunAjaranOptions' => new Collection([$ta]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'tingkatanOptions' => Kkm::SD_TINGKATAN_OPTIONS,
            ])->render();
            $this->assertStringContainsString('Pendidikan Agama Islam', $kkmIndexHtml);
            $this->assertStringContainsString('Kelas 5', $kkmIndexHtml);

            $kkmCreateHtml = view('admin.penilaian.kkm.create', [
                'tahunAjaranOptions' => new Collection([$ta]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'tingkatanOptions' => Kkm::SD_TINGKATAN_OPTIONS,
                'defaultPredikats' => Kkm::generateDefaultPredikats(75),
                'defaults' => ['tahun_ajaran_id' => '1', 'mata_pelajaran_id' => '2', 'tingkatan_kelas' => '5'],
            ])->render();
            $this->assertStringContainsString('Generate Rentang dari KKM', $kkmCreateHtml);

            $kkmEditHtml = view('admin.penilaian.kkm.edit', [
                'kkm' => $kkm,
                'tahunAjaranOptions' => new Collection([$ta]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'tingkatanOptions' => Kkm::SD_TINGKATAN_OPTIONS,
                'defaultPredikats' => Kkm::generateDefaultPredikats(75),
                'defaults' => ['tahun_ajaran_id' => '1', 'mata_pelajaran_id' => '2', 'tingkatan_kelas' => '5'],
            ])->render();
            $this->assertStringContainsString('Edit KKM', $kkmEditHtml);

            $nilaiHtml = view('admin.penilaian.nilaiTp.index', [
                'tahunAjaranOptions' => new Collection([$ta]),
                'kelasOptions' => new Collection([$kelas]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'selectedTahunAjaranId' => 1,
                'selectedKelas' => $kelas,
                'selectedMapel' => $mapel,
                'periode' => PeriodeTp::PERIODE_SEMESTER,
                'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
                'teknikOptions' => AspekTp::TEKNIK_OPTIONS,
                'tpList' => new Collection([$tp1]),
                'periodeSettingsByTp' => collect([21 => PeriodeTp::make(['tujuan_pembelajaran_id' => 21, 'periode' => 'semester', 'masuk_rapor' => true])]),
                'aspeksByTp' => collect([21 => new Collection([$aspek1, $aspek2])]),
                'students' => new Collection([$anggota]),
                'nilaiLookup' => ['41:31' => NilaiAspek::make(['nilai' => 100]), '41:32' => NilaiAspek::make(['nilai' => 80])],
                'rekapTpLookup' => ['41:21:mid' => $rekapMid],
                'rekapMapelLookup' => ['41:semester' => $rekapMapel],
                'kkmNilai' => 75.0,
                'kkmPredikats' => $predikatModels,
                'bobotTp' => 70,
                'bobotUjian' => 30,
                'ujianLabel' => 'SAS',
                'hasKkmConfig' => true,
                'kkmModel' => $kkm,
                'includedTpCount' => 1,
            ])->render();
            $this->assertStringContainsString('TP 5.1', $nilaiHtml);
            $this->assertStringContainsString('Aisyah Putri', $nilaiHtml);
            $this->assertStringContainsString('Mid: 90', $nilaiHtml);
            $this->assertStringContainsString('70% TP + 30% SAS', $nilaiHtml);

            $rekapHtml = view('admin.penilaian.rekap.index', [
                'tahunAjaranOptions' => new Collection([$ta]),
                'kelasOptions' => new Collection([$kelas]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'selectedTahunAjaranId' => 1,
                'selectedKelas' => $kelas,
                'selectedMapel' => $mapel,
                'periode' => PeriodeTp::PERIODE_SEMESTER,
                'bobotTp' => 70,
                'bobotUjian' => 30,
                'ujianLabel' => 'SAS',
                'tpList' => new Collection([$tp1]),
                'students' => new Collection([$anggota]),
                'combinedTpSummaries' => [
                    41 => [21 => ['included' => true, 'nilai' => 90, 'predikat' => 'B', 'tuntas' => true, 'source' => 'Mid']],
                ],
                'rekapMapelLookup' => ['41:semester' => $rekapMapel],
                'kkmNilai' => 75.0,
            ])->render();
            $this->assertStringContainsString('Menunjukkan penguasaan yang baik dalam Pemahaman Bacaan.', $rekapHtml);
            $this->assertStringContainsString('SAS', $rekapHtml);
        } finally {
            Model::preventLazyLoading(false);
        }
    }
}
