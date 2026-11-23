<?php

namespace Tests\Feature;

use App\Http\Controllers\GuruT2q\RaporT2qController;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\AngkaArab;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Sekolah;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\Surah;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\T2q\RaporT2q;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\User;
use App\Services\Admin\Penilaian\RaporPdfService;
use App\Services\GuruT2q\GuruT2qRaporService;
use App\Services\GuruT2q\GuruT2qScopeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RaporT2qTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.host' => 'https://sso.test']);
        Http::preventStrayRequests();

        if (! Schema::hasTable('kepegawaian_master_karyawan')) {
            Schema::create('kepegawaian_master_karyawan', function (Blueprint $table): void {
                $table->string('nipy')->primary();
                $table->string('nama_lengkap');
                $table->string('gelar')->nullable();
                $table->string('jabatan')->nullable();
                $table->string('unit')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function seedT2qRaporFixtures(): array
    {
        Role::findOrCreate('super admin', 'web');
        Role::findOrCreate('guru t2q', 'web');
        Role::findOrCreate('guru mapel sd', 'web');

        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        Karyawan::create([
            'nipy' => 'NIPY-TQ-1',
            'nama_lengkap' => 'Ustadz Abdullah',
            'gelar' => 'S.Pd.I',
            'jabatan' => 'Guru T2Q',
            'unit' => 'SD',
        ]);
        Karyawan::create([
            'nipy' => 'NIPY-TQ-OTHER',
            'nama_lengkap' => 'Ustadzah Fatimah',
            'gelar' => 'S.Ag',
            'jabatan' => 'Guru T2Q',
            'unit' => 'SD',
        ]);
        Karyawan::create([
            'nipy' => 'NIPY-WALI',
            'nama_lengkap' => 'Siti Rohmah',
            'gelar' => 'S.Pd',
            'jabatan' => 'Wali Kelas',
            'unit' => 'SD',
        ]);

        $guruT2qUser = User::factory()->create(['name' => 'Abdullah', 'email' => 'tq1@test.local', 'no_induk' => 'NIPY-TQ-1']);
        $guruT2qUser->assignRole('guru t2q');

        $otherGuruUser = User::factory()->create(['name' => 'Fatimah', 'email' => 'tq2@test.local', 'no_induk' => 'NIPY-TQ-OTHER']);
        $otherGuruUser->assignRole('guru t2q');

        $nonT2qUser = User::factory()->create(['name' => 'Guru Mapel', 'email' => 'mapel@test.local', 'no_induk' => 'NIPY-WALI']);
        $nonT2qUser->assignRole('guru mapel sd');

        Sekolah::create([
            'jenjang_kelas' => 'SD',
            'nama_yayasan' => 'Yayasan Prima Insani',
            'nama_sekolah' => 'SD Prima Insani',
            'npsn' => '20209000',
            'status' => true,
        ]);

        $kelasSd = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'karyawan_nipy' => 'NIPY-WALI',
            'tingkatan_kelas' => '4',
            'nama_kelas' => '4 Al-Farabi',
            'jenjang_kelas' => 'SD',
            'romawi' => 'IV',
        ]);

        $kelasSmp = Kelas::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'karyawan_nipy' => 'NIPY-WALI',
            'tingkatan_kelas' => '7',
            'nama_kelas' => '7 Al-Khawarizmi',
            'jenjang_kelas' => 'SMP',
            'romawi' => 'VII',
        ]);

        $siswaSd = Siswa::create([
            'nis' => '2026001',
            'nisn' => '0120001',
            'nama_lengkap' => 'Aisyah Humaira',
            'jenis_kelamin' => 'P',
            'status' => 'Aktif',
        ]);
        $siswaSmp = Siswa::create([
            'nis' => '2026002',
            'nisn' => '0120002',
            'nama_lengkap' => 'Zayd Ibrahim',
            'jenis_kelamin' => 'L',
            'status' => 'Aktif',
        ]);

        $akSd = AnggotaKelas::create([
            'siswa_nis' => $siswaSd->nis,
            'akademik_master_kelas_id' => $kelasSd->id,
            'pendaftaran' => 'Naik Kelas',
        ]);
        $akSmp = AnggotaKelas::create([
            'siswa_nis' => $siswaSmp->nis,
            'akademik_master_kelas_id' => $kelasSmp->id,
            'pendaftaran' => 'Baru',
        ]);

        $anggotaSd = AnggotaT2q::create([
            'anggota_kelas_id' => $akSd->id,
            'karyawan_nipy' => 'NIPY-TQ-1',
            'tingkat' => '4',
            'pendaftaran' => 'Lanjutan',
        ]);
        $anggotaSmp = AnggotaT2q::create([
            'anggota_kelas_id' => $akSmp->id,
            'karyawan_nipy' => 'NIPY-TQ-1',
            'tingkat' => '7',
            'pendaftaran' => 'Baru',
        ]);

        $jilid4 = DaftarJilid::create(['jilid_latin' => 'Jilid 4', 'jilid_arab' => 'المجلد ٤', 'jenis' => 'halaman', 'urutan' => 4]);
        $tilawah = DaftarJilid::create(['jilid_latin' => 'Tilawah', 'jilid_arab' => 'تلاوة', 'jenis' => 'quran', 'urutan' => 11]);
        $surahBuruj = Surah::create(['nama_surah' => 'al-Buruj', 'nama_surah_arab' => 'البروج', 'jumlah_ayat' => 22]);
        $surahNaba = Surah::create(['nama_surah' => 'an-Naba', 'nama_surah_arab' => 'النبإ', 'jumlah_ayat' => 40]);
        $angka11 = AngkaArab::create(['angka_latin' => '11', 'angka_arab' => '١١']);
        $angka15 = AngkaArab::create(['angka_latin' => '15', 'angka_arab' => '١٥']);

        return compact(
            'ta',
            'guruT2qUser',
            'otherGuruUser',
            'nonT2qUser',
            'kelasSd',
            'kelasSmp',
            'akSd',
            'akSmp',
            'anggotaSd',
            'anggotaSmp',
            'jilid4',
            'tilawah',
            'surahBuruj',
            'surahNaba',
            'angka11',
            'angka15'
        );
    }

    public function test_predikat_scale_75_and_ayat_aware_description_generation(): void
    {
        $this->assertSame('MB', RaporT2q::resolvePredikatMeta(74)['predikat']);
        $this->assertSame('Mulai Berkembang', RaporT2q::resolvePredikatMeta(74)['keterangan']);
        $this->assertSame('SB', RaporT2q::resolvePredikatMeta(75)['predikat']);
        $this->assertSame('BSH', RaporT2q::resolvePredikatMeta(83)['predikat']);
        $this->assertSame('BSH', RaporT2q::resolvePredikatMeta(91)['predikat']);
        $this->assertSame('SAB', RaporT2q::resolvePredikatMeta(92)['predikat']);

        $tahsinDesc = RaporT2q::buildAutoDeskripsi(
            RaporT2q::MODE_TAHSIN,
            85,
            'Jilid 4 halaman 11',
            'bacaan panjang 2 harakat (mad)',
            'huruf yang disukunkan'
        );
        $this->assertSame(
            'Ananda berkembang sesuai harapan dalam menguasai kompetensi tahsin Jilid 4 halaman 11, terutama dalam bacaan panjang 2 harakat (mad). Perlu peningkatan dalam huruf yang disukunkan.',
            $tahsinDesc
        );

        // Tahfidz tanpa ayat -> konteks surah utuh
        $tahfizhWhole = RaporT2q::buildAutoDeskripsi(
            RaporT2q::MODE_TAHFIZH,
            85,
            'Surah al-Buruj',
            'terutama dalam mad',
            'perlu peningkatan dalam kelancaran.'
        );
        $this->assertSame(
            'Ananda berkembang sesuai harapan dalam melafalkan Surah al-Buruj, terutama dalam mad. Perlu peningkatan dalam kelancaran.',
            $tahfizhWhole
        );

        // Tahfidz dengan ayat -> beda konteks ("hingga ayat ...")
        $tahfizhAyat = RaporT2q::buildAutoDeskripsi(
            RaporT2q::MODE_TAHFIZH,
            88,
            'Surah al-Buruj ayat 15',
            'bacaan mad',
            'kelancaran hafalan'
        );
        $this->assertSame(
            'Ananda berkembang sesuai harapan dalam menghafal dan melafalkan Surah al-Buruj hingga ayat 15, terutama dalam bacaan mad. Perlu peningkatan dalam kelancaran hafalan.',
            $tahfizhAyat
        );

        // Nilai di bawah KKM 75 otomatis memicu MB + catatan penguatan default bila belum diisi
        $mbDesc = RaporT2q::buildAutoDeskripsi(RaporT2q::MODE_TAHFIZH, 70, 'Surah an-Naba ayat 11');
        $this->assertSame(
            'Ananda mulai berkembang dalam menghafal dan melafalkan Surah an-Naba hingga ayat 11. Perlu peningkatan dalam kelancaran hafalan.',
            $mbDesc
        );
    }

    public function test_guru_t2q_rapor_workspace_save_finalisasi_cutoff_and_pdf_integration(): void
    {
        Carbon::setTestNow('2026-10-15 10:00:00');
        try {
            $f = $this->seedT2qRaporFixtures();

            // Setoran Mid Tahsin (lama 76 lalu terbaru 86) & Tahfizh (tanpa ayat: Surah al-Buruj)
            YaumiyahTahsin::create([
                'anggota_t2q_id' => $f['anggotaSd']->id,
                'akademik_master_t2q_daftar_jilid_id' => $f['jilid4']->id,
                'akademik_master_t2q_angka_arab_id' => $f['angka11']->id,
                'tanggal' => '2026-10-10',
                'nilai' => 76,
            ]);
            YaumiyahTahsin::create([
                'anggota_t2q_id' => $f['anggotaSd']->id,
                'akademik_master_t2q_daftar_jilid_id' => $f['jilid4']->id,
                'akademik_master_t2q_angka_arab_id' => $f['angka11']->id,
                'tanggal' => '2026-10-14',
                'nilai' => 86,
            ]);
            YaumiyahTahfizh::create([
                'anggota_t2q_id' => $f['anggotaSd']->id,
                'akademik_master_t2q_surah_id' => $f['surahBuruj']->id,
                'akademik_master_t2q_angka_arab_id' => null,
                'tanggal' => '2026-10-14',
                'nilai' => 85,
            ]);

            // Setoran siswa SMP (seharusnya dikecualikan dari Rekap Rapor SD)
            YaumiyahTahsin::create([
                'anggota_t2q_id' => $f['anggotaSmp']->id,
                'akademik_master_t2q_daftar_jilid_id' => $f['jilid4']->id,
                'tanggal' => '2026-10-14',
                'nilai' => 90,
            ]);

            $raporService = app(GuruT2qRaporService::class);

            Model::preventLazyLoading(true);
            try {
                $workspace = $raporService->getWorkspaceData($f['guruT2qUser'], ['periode' => PeriodeTp::PERIODE_MID]);
                $html = view('guruT2q.rapor.index', $workspace)->render();
            } finally {
                Model::preventLazyLoading(false);
            }

            $this->assertTrue($workspace['isEligible']);
            $this->assertSame(1, $workspace['stats']['total_sd']);
            $this->assertSame(1, $workspace['nonSdCount']);
            $this->assertCount(1, $workspace['rows']);
            $row = $workspace['rows']->first();
            $this->assertSame('Jilid 4 halaman 11', $row['tahsin']['materi']);
            $this->assertSame(86.0, $row['tahsin']['nilai']);
            $this->assertSame('BSH', $row['tahsin']['predikat']);
            $this->assertSame('Surah al-Buruj', $row['tahfizh']['materi']);

            // Fokus SD & non-rombel kelas terjaga di UI Guru T2Q
            $this->assertStringContainsString('Aisyah Humaira', $html);
            $this->assertStringNotContainsString('Zayd Ibrahim', $html);
            $this->assertStringNotContainsString('4 Al-Farabi', $html);

            // Simpan aspek keunggulan & peningkatan pada Mid
            $savedResult = $raporService->saveBatch($f['guruT2qUser'], [
                'periode' => PeriodeTp::PERIODE_MID,
                'tahsin' => [
                    $f['anggotaSd']->id => [
                        'keunggulan' => 'bacaan panjang 2 harakat (mad)',
                        'peningkatan' => 'huruf yang disukunkan',
                    ],
                ],
                'tahfizh' => [
                    $f['anggotaSd']->id => [
                        'keunggulan' => 'mad',
                        'peningkatan' => 'kelancaran',
                    ],
                ],
            ]);
            $this->assertSame(1, $savedResult['saved_count']);

            // ID siswa non-SD atau milik guru lain ditolak (403)
            try {
                $raporService->saveBatch($f['guruT2qUser'], [
                    'periode' => PeriodeTp::PERIODE_MID,
                    'tahsin' => [$f['anggotaSmp']->id => ['keunggulan' => 'mad']],
                ]);
                $this->fail('Penyimpanan anggota non-SD harus ditolak.');
            } catch (HttpException $e) {
                $this->assertSame(403, $e->getStatusCode());
            }

            // Finalisasi Mid oleh Guru T2Q pada 2026-10-15 mengunci edit Mid
            app(GuruT2qScopeService::class)->finalizeBatchForUser($f['guruT2qUser'], PeriodeTp::PERIODE_MID);

            try {
                $raporService->saveBatch($f['guruT2qUser'], [
                    'periode' => PeriodeTp::PERIODE_MID,
                    'tahsin' => [$f['anggotaSd']->id => ['keunggulan' => 'ubah setelah final']],
                ]);
                $this->fail('Edit rekap rapor setelah Final harus diblokir.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('finalisasi', $e->errors());
            }

            // Pasca-Mid (2026-11-20), siswa setor Tahsin nilai 95 & Tahfidz Surah al-Buruj ayat 15 nilai 93
            Carbon::setTestNow('2026-11-20 09:00:00');
            YaumiyahTahsin::create([
                'anggota_t2q_id' => $f['anggotaSd']->id,
                'akademik_master_t2q_daftar_jilid_id' => $f['tilawah']->id,
                'akademik_master_t2q_surah_id' => $f['surahNaba']->id,
                'akademik_master_t2q_angka_arab_id' => $f['angka15']->id,
                'tanggal' => '2026-11-20',
                'nilai' => 95,
            ]);
            YaumiyahTahfizh::create([
                'anggota_t2q_id' => $f['anggotaSd']->id,
                'akademik_master_t2q_surah_id' => $f['surahBuruj']->id,
                'akademik_master_t2q_angka_arab_id' => $f['angka15']->id,
                'tanggal' => '2026-11-20',
                'nilai' => 93,
            ]);

            $raporPdfService = app(RaporPdfService::class);
            Model::preventLazyLoading(true);
            try {
                $midPayload = $raporPdfService->buildNilaiPdfPayload($f['kelasSd'], PeriodeTp::PERIODE_MID, $f['akSd']);
                $semPayload = $raporPdfService->buildNilaiPdfPayload($f['kelasSd'], PeriodeTp::PERIODE_SEMESTER, $f['akSd']);
                $midPdfHtml = view('pdf.rapor.nilai', $midPayload)->render();
                $semPdfHtml = view('pdf.rapor.nilai', $semPayload)->render();
            } finally {
                Model::preventLazyLoading(false);
            }

            // Rapor Mid tetap membekukan nilai/materi Mid (86 / Jilid 4 halaman 11 / Surah al-Buruj)
            $this->assertSame('Jilid 4 halaman 11', $midPayload['entries'][0]['t2qRapor']['tahsin']['materi']);
            $this->assertSame(86.0, $midPayload['entries'][0]['t2qRapor']['tahsin']['nilai']);
            $this->assertSame('Surah al-Buruj', $midPayload['entries'][0]['t2qRapor']['tahfizh']['materi']);
            $this->assertStringContainsString('Jilid 4 halaman 11', $midPdfHtml);
            $this->assertStringContainsString('Perlu peningkatan dalam huruf yang disukunkan.', $midPdfHtml);

            // Rapor Semester mengambil setoran terakhir (95 / Tilawah Surah an-Naba ayat 15 & 93 / Surah al-Buruj ayat 15)
            $this->assertSame('Tilawah Surah an-Naba ayat 15', $semPayload['entries'][0]['t2qRapor']['tahsin']['materi']);
            $this->assertSame(95.0, $semPayload['entries'][0]['t2qRapor']['tahsin']['nilai']);
            $this->assertSame('SAB', $semPayload['entries'][0]['t2qRapor']['tahsin']['predikat']);
            $this->assertSame('Surah al-Buruj ayat 15', $semPayload['entries'][0]['t2qRapor']['tahfizh']['materi']);
            $this->assertStringContainsString('Surah al-Buruj ayat 15', $semPdfHtml);
            $this->assertStringContainsString('hingga ayat 15', $semPdfHtml);

            // Pastikan PDF binary DomPDF valid tanpa error
            $pdfBinary = Pdf::loadView('pdf.rapor.nilai', $semPayload)->setPaper('a4', 'portrait')->output();
            $this->assertStringStartsWith('%PDF-', $pdfBinary);

            // Verifikasi generate ulang & controller permissions
            $genCount = $raporService->regenerateAllDrafts($f['guruT2qUser'], PeriodeTp::PERIODE_SEMESTER);
            $this->assertSame(1, $genCount['generated_count']);
            $this->assertDatabaseHas('akademik_t2q_rekap_rapor', [
                'anggota_t2q_id' => $f['anggotaSd']->id,
                'periode' => PeriodeTp::PERIODE_SEMESTER,
                'tahsin_predikat' => 'SAB',
                'tahfizh_materi' => 'Surah al-Buruj ayat 15',
            ]);

            $controller = app(RaporT2qController::class);
            $this->actingAs($f['guruT2qUser']);
            $reqIdx = Request::create('/guru-t2q/rapor', 'GET', ['periode' => PeriodeTp::PERIODE_SEMESTER]);
            $reqIdx->setUserResolver(static fn () => $f['guruT2qUser']);
            $view = $controller->index($reqIdx);
            $this->assertSame('guruT2q.rapor.index', $view->name());

            $this->actingAs($f['nonT2qUser'])
                ->get('/guru-t2q/rapor')
                ->assertForbidden();
        } finally {
            Carbon::setTestNow();
        }
    }

    public function test_non_sd_guru_t2q_sees_clear_sd_only_notice(): void
    {
        $f = $this->seedT2qRaporFixtures();
        // Pindahkan seluruh siswa Guru T2Q menjadi non-SD
        $f['anggotaSd']->delete();

        $raporService = app(GuruT2qRaporService::class);
        $data = $raporService->getWorkspaceData($f['guruT2qUser']);
        $html = view('guruT2q.rapor.index', $data)->render();

        $this->assertSame('no_sd_students', $data['ineligibleReason']);
        $this->assertStringContainsString('Rekap Rapor T2Q Tersedia Khusus Jenjang SD', $html);
    }
}
