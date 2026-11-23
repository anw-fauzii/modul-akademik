<?php

namespace Tests\Unit;

use App\Http\Controllers\GuruT2q\RaporT2qController;
use App\Http\Requests\GuruT2q\SaveRaporT2qRequest;
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
use App\Models\Admin\DataMaster\Titimangsa;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\T2q\RaporT2q;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Models\User;
use App\Services\Admin\T2q\T2qRaporResolverService;
use App\Services\GuruT2q\GuruT2qRaporService;
use App\Services\GuruT2q\GuruT2qScopeService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use ReflectionProperty;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RaporT2qTest extends TestCase
{
    private function makeUserWithRole(string $name, string $email, ?string $noInduk, string $roleName, ?Karyawan $karyawan = null): User
    {
        $role = new Role;
        $role->setRawAttributes(['name' => $roleName, 'guard_name' => 'web']);
        $role->setRelation('permissions', new EloquentCollection);

        $user = new User;
        $user->setRawAttributes([
            'id' => 77,
            'name' => $name,
            'email' => $email,
            'no_induk' => $noInduk,
        ]);
        $user->setRelation('roles', new EloquentCollection([$role]));
        $user->setRelation('permissions', new EloquentCollection);
        $user->setRelation('karyawan', $karyawan);

        return $user;
    }

    #[TestWith([0, 'MB', 'Mulai Berkembang', false])]
    #[TestWith([74, 'MB', 'Mulai Berkembang', false])]
    #[TestWith([75, 'SB', 'Sedang Berkembang', true])]
    #[TestWith([82, 'SB', 'Sedang Berkembang', true])]
    #[TestWith([83, 'BSH', 'Berkembang Sesuai Harapan', true])]
    #[TestWith([85, 'BSH', 'Berkembang Sesuai Harapan', true])]
    #[TestWith([91, 'BSH', 'Berkembang Sesuai Harapan', true])]
    #[TestWith([92, 'SAB', 'Sangat Baik', true])]
    #[TestWith([100, 'SAB', 'Sangat Baik', true])]
    public function test_predikat_scale_minimal_75_matches_ekstrakurikuler_ranges(
        int $nilai,
        string $expectedCode,
        string $expectedLabel,
        bool $expectedTuntas
    ): void {
        $meta = RaporT2q::resolvePredikatMeta($nilai);

        $this->assertNotNull($meta);
        $this->assertSame($expectedCode, $meta['predikat']);
        $this->assertSame($expectedLabel, $meta['keterangan']);
        $this->assertSame($expectedTuntas, $meta['is_tuntas']);
    }

    public function test_format_materi_handles_jilid_quran_and_tahfidz_ayat_contexts(): void
    {
        $jilid4 = DaftarJilid::make(['jilid_latin' => 'Jilid 4', 'jenis' => 'halaman']);
        $tilawah = DaftarJilid::make(['jilid_latin' => 'Tilawah', 'jenis' => 'quran']);
        $surahBuruj = Surah::make(['nama_surah' => 'al-Buruj', 'jumlah_ayat' => 22]);
        $surahPrefixed = Surah::make(['nama_surah' => 'Surah An-Naba', 'jumlah_ayat' => 40]);
        $angka11 = AngkaArab::make(['angka_latin' => '11', 'angka_arab' => '١١']);
        $angka15 = AngkaArab::make(['angka_latin' => '15', 'angka_arab' => '١٥']);

        $this->assertSame('Jilid 4 halaman 11', RaporT2q::formatTahsinMateri($jilid4, null, $angka11));
        $this->assertSame('Jilid 4', RaporT2q::formatTahsinMateri($jilid4, null, null));
        $this->assertSame('Tilawah Surah al-Buruj ayat 15', RaporT2q::formatTahsinMateri($tilawah, $surahBuruj, $angka15));
        $this->assertSame('Surah An-Naba', RaporT2q::formatSurahLabel($surahPrefixed));

        $whole = RaporT2q::formatTahfizhMateriDetail($surahBuruj, null);
        $this->assertFalse($whole['has_ayat']);
        $this->assertSame('Surah al-Buruj', $whole['label']);

        $withAyat = RaporT2q::formatTahfizhMateriDetail($surahBuruj, $angka15);
        $this->assertTrue($withAyat['has_ayat']);
        $this->assertSame('Surah al-Buruj ayat 15', $withAyat['label']);
    }

    public function test_build_auto_deskripsi_distinguishes_predikat_tiers_and_ayat_contexts(): void
    {
        $tahsinBsh = RaporT2q::buildAutoDeskripsi(
            RaporT2q::MODE_TAHSIN,
            85,
            'Jilid 4 halaman 11',
            'bacaan panjang 2 harakat (mad)',
            'huruf yang disukunkan'
        );
        $this->assertSame(
            'Ananda berkembang sesuai harapan dalam menguasai kompetensi tahsin Jilid 4 halaman 11, terutama dalam bacaan panjang 2 harakat (mad). Perlu peningkatan dalam huruf yang disukunkan.',
            $tahsinBsh
        );

        // Tanpa ayat => konteks pelafalan surah utuh + membersihkan awalan duplikat
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

        // Dengan ayat => beda konteks ("menghafal dan melafalkan ... hingga ayat ...")
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

        $sab = RaporT2q::buildAutoDeskripsi(RaporT2q::MODE_TAHSIN, 95, 'Jilid 4 halaman 20');
        $this->assertSame('Ananda menunjukkan capaian sangat baik dalam menguasai kompetensi tahsin Jilid 4 halaman 20.', $sab);

        $sb = RaporT2q::buildAutoDeskripsi(RaporT2q::MODE_TAHSIN, 78, 'Jilid 4 halaman 12');
        $this->assertSame('Ananda sedang berkembang dalam menguasai kompetensi tahsin Jilid 4 halaman 12.', $sb);

        $mb = RaporT2q::buildAutoDeskripsi(RaporT2q::MODE_TAHFIZH, 70, 'Surah an-Naba ayat 11');
        $this->assertSame(
            'Ananda mulai berkembang dalam menghafal dan melafalkan Surah an-Naba hingga ayat 11. Perlu peningkatan dalam kelancaran hafalan.',
            $mb
        );

        // Multi-select parsing & Indonesian natural list formatting ("A dan B" / "A, B, dan C")
        $this->assertSame([], RaporT2q::parseAspectList(null));
        $this->assertSame(['bacaan panjang 2 harakat (mad)'], RaporT2q::parseAspectList('bacaan panjang 2 harakat (mad)'));
        $this->assertSame(
            ['bacaan panjang 2 harakat (mad)', 'ketepatan makhraj huruf'],
            RaporT2q::parseAspectList('bacaan panjang 2 harakat (mad) dan ketepatan makhraj huruf')
        );
        $this->assertSame(
            ['bacaan mad', 'kelancaran hafalan', 'ketepatan makhraj huruf'],
            RaporT2q::parseAspectList('bacaan mad, kelancaran hafalan, dan ketepatan makhraj huruf')
        );
        $this->assertSame(
            'bacaan mad, kelancaran hafalan, dan ketepatan makhraj huruf',
            RaporT2q::formatAspectList(['bacaan mad', 'kelancaran hafalan', 'ketepatan makhraj huruf', 'Bacaan Mad'])
        );

        $multiAspectDesc = RaporT2q::buildAutoDeskripsi(
            RaporT2q::MODE_TAHSIN,
            88,
            'Jilid 4 halaman 11',
            RaporT2q::formatAspectList(['bacaan panjang 2 harakat (mad)', 'ketepatan makhraj huruf']),
            RaporT2q::formatAspectList(['huruf yang disukunkan', 'kelancaran membaca'])
        );
        $this->assertSame(
            'Ananda berkembang sesuai harapan dalam menguasai kompetensi tahsin Jilid 4 halaman 11, terutama dalam bacaan panjang 2 harakat (mad) dan ketepatan makhraj huruf. Perlu peningkatan dalam huruf yang disukunkan dan kelancaran membaca.',
            $multiAspectDesc
        );
    }

    public function test_resolver_mid_cutoff_and_snapshot_merge_logic(): void
    {
        $resolver = new T2qRaporResolverService;
        $pickMethod = new ReflectionMethod($resolver, 'pickLatestPerAnggota');
        $resolveChannelMethod = new ReflectionMethod($resolver, 'resolveChannel');

        $jilid4 = DaftarJilid::make(['jilid_latin' => 'Jilid 4', 'jenis' => 'halaman']);
        $surah = Surah::make(['nama_surah' => 'al-Buruj', 'jumlah_ayat' => 22]);
        $angka11 = AngkaArab::make(['angka_latin' => '11']);
        $angka15 = AngkaArab::make(['angka_latin' => '15']);

        $semEntry = YaumiyahTahsin::make([
            'anggota_t2q_id' => 10,
            'tanggal' => '2026-11-20',
            'nilai' => 94,
        ]);
        $semEntry->id = 200;
        $semEntry->setRelation('daftarJilid', $jilid4);
        $semEntry->setRelation('surah', null);
        $semEntry->setRelation('angkaArab', $angka15);

        $midEntry = YaumiyahTahsin::make([
            'anggota_t2q_id' => 10,
            'tanggal' => '2026-10-14',
            'nilai' => 86,
        ]);
        $midEntry->id = 150;
        $midEntry->setRelation('daftarJilid', $jilid4);
        $midEntry->setRelation('surah', null);
        $midEntry->setRelation('angkaArab', $angka11);

        $orderedRows = new EloquentCollection([$semEntry, $midEntry]);

        // Mid Final cut-off 2026-10-15 harus mengabaikan entri 2026-11-20
        $pickedMid = $pickMethod->invoke($resolver, $orderedRows, [10 => '2026-10-15']);
        $this->assertSame(150, $pickedMid->get(10)->id);

        // Semester tanpa cut-off harus memilih entri terbaru (2026-11-20)
        $pickedSem = $pickMethod->invoke($resolver, $orderedRows, [10 => null]);
        $this->assertSame(200, $pickedSem->get(10)->id);

        // Bila terkunci (Final) dengan snapshot tersimpan, nilai snapshot Mid dipertahankan meski ada yaumiyah baru
        $savedSnapshot = RaporT2q::make([
            'tahsin_yaumiyah_id' => 150,
            'tahsin_materi' => 'Jilid 4 halaman 11',
            'tahsin_nilai' => 86,
            'tahsin_predikat' => 'BSH',
            'tahsin_keunggulan' => 'mad',
            'tahsin_peningkatan' => 'sukun',
            'tahsin_deskripsi' => 'Deskripsi snapshot Mid yang sudah dikunci.',
        ]);
        $lockedChannel = $resolveChannelMethod->invoke($resolver, RaporT2q::MODE_TAHSIN, $savedSnapshot, $midEntry, true);
        $this->assertSame('snapshot_final', $lockedChannel['source']);
        $this->assertSame(86.0, $lockedChannel['nilai']);
        $this->assertSame('Deskripsi snapshot Mid yang sudah dikunci.', $lockedChannel['deskripsi']);

        // Saat Draft dan ada setoran Yaumiyah baru (id 200 != 150), skor & materi disinkronkan sementara preset keunggulan tetap terbawa
        $draftUpdated = $resolveChannelMethod->invoke($resolver, RaporT2q::MODE_TAHSIN, $savedSnapshot, $semEntry, false);
        $this->assertTrue($draftUpdated['has_newer_yaumiyah']);
        $this->assertSame(94.0, $draftUpdated['nilai']);
        $this->assertSame('SAB', $draftUpdated['predikat']);
        $this->assertSame('Jilid 4 halaman 15', $draftUpdated['materi']);
        $this->assertSame('mad', $draftUpdated['keunggulan']);
        $this->assertStringContainsString('Ananda menunjukkan capaian sangat baik', (string) $draftUpdated['deskripsi']);
        $this->assertStringContainsString('terutama dalam mad', (string) $draftUpdated['deskripsi']);

        // Tahfizh dengan ayat via resolveChannel
        $tahfizhEntry = YaumiyahTahfizh::make([
            'anggota_t2q_id' => 10,
            'tanggal' => '2026-11-20',
            'nilai' => 88,
        ]);
        $tahfizhEntry->id = 300;
        $tahfizhEntry->setRelation('surah', $surah);
        $tahfizhEntry->setRelation('angkaArab', $angka15);

        $tahfizhResolved = $resolveChannelMethod->invoke($resolver, RaporT2q::MODE_TAHFIZH, null, $tahfizhEntry, false);
        $this->assertTrue($tahfizhResolved['has_ayat']);
        $this->assertSame('Surah al-Buruj ayat 15', $tahfizhResolved['materi']);
        $this->assertStringContainsString('hingga ayat 15', (string) $tahfizhResolved['deskripsi']);
    }

    public function test_policy_controller_and_form_request_validation(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $permProp = new ReflectionProperty($registrar, 'permissions');
        $permProp->setAccessible(true);
        $permProp->setValue($registrar, new EloquentCollection);

        $guruKaryawan = Karyawan::make(['nipy' => 'NIPY-TQ-1', 'nama_lengkap' => 'Abdullah', 'gelar' => 'S.Pd.I']);
        $guruT2q = $this->makeUserWithRole('Abdullah', 'tq@test.local', 'NIPY-TQ-1', 'guru t2q', $guruKaryawan);
        $guruMapel = $this->makeUserWithRole('Budi', 'mapel@test.local', 'NIPY-GM', 'guru mapel sd');
        $admin = $this->makeUserWithRole('Admin', 'admin@test.local', 'ADM-1', 'super admin');

        $this->assertTrue(Gate::forUser($guruT2q)->allows('viewAny', RaporT2q::class));
        $this->assertTrue(Gate::forUser($guruT2q)->allows('saveBatch', RaporT2q::class));
        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', RaporT2q::class));
        $this->assertFalse(Gate::forUser($guruMapel)->allows('viewAny', RaporT2q::class));
        $this->assertFalse(Gate::forUser($guruMapel)->allows('saveBatch', RaporT2q::class));

        $requestRules = (new SaveRaporT2qRequest)->rules();
        $invalidValidator = Validator::make(['periode' => 'triwulan'], $requestRules);
        $this->assertTrue($invalidValidator->fails());

        $validValidator = Validator::make([
            'periode' => PeriodeTp::PERIODE_MID,
            'tahsin' => [1 => ['keunggulan' => 'mad', 'peningkatan' => 'sukun', 'deskripsi' => 'Kalimat uji']],
        ], $requestRules);
        $this->assertFalse($validValidator->fails());

        $serviceMock = $this->createMock(GuruT2qRaporService::class);
        $serviceMock->expects($this->once())
            ->method('getWorkspaceData')
            ->with($guruT2q, ['periode' => PeriodeTp::PERIODE_MID, 'tingkat' => '4'])
            ->willReturn(['isEligible' => false, 'ineligibleReason' => 'not_assigned_active_year', 'nipy' => 'NIPY-TQ-1', 'karyawanProfile' => $guruKaryawan, 'activeTahunAjaran' => null]);
        $serviceMock->expects($this->once())
            ->method('saveBatch')
            ->willReturn(['saved_count' => 2, 'periode_label' => 'Mid Semester']);
        $serviceMock->expects($this->once())
            ->method('regenerateAllDrafts')
            ->with($guruT2q, PeriodeTp::PERIODE_SEMESTER, '4')
            ->willReturn(['generated_count' => 2, 'periode_label' => 'Semester']);

        $controller = new RaporT2qController($serviceMock);

        $reqIndex = Request::create('/guru-t2q/rapor', 'GET', ['periode' => PeriodeTp::PERIODE_MID, 'tingkat' => '4']);
        $reqIndex->setUserResolver(static fn (): User => $guruT2q);
        $view = $controller->index($reqIndex);
        $this->assertSame('guruT2q.rapor.index', $view->name());

        $saveReq = SaveRaporT2qRequest::create('/guru-t2q/rapor', 'POST', [
            'periode' => PeriodeTp::PERIODE_MID,
            'tingkat' => '4',
            'tahsin' => [10 => ['keunggulan' => 'mad']],
        ]);
        $saveReq->setUserResolver(static fn (): User => $guruT2q);
        $saveReq->setContainer(app());
        $saveReq->setRedirector(app('redirect'));
        $saveReq->validateResolved();
        $saveResp = $controller->store($saveReq);
        $this->assertSame(302, $saveResp->getStatusCode());
        $this->assertStringContainsString('/guru-t2q/rapor', (string) $saveResp->headers->get('Location'));

        $reqGen = Request::create('/guru-t2q/rapor/generate', 'POST', [
            'periode' => PeriodeTp::PERIODE_SEMESTER,
            'tingkat' => '4',
        ]);
        $reqGen->setUserResolver(static fn (): User => $guruT2q);
        $genResp = $controller->generate($reqGen);
        $this->assertSame(302, $genResp->getStatusCode());
        $this->assertStringContainsString('/guru-t2q/rapor', (string) $genResp->headers->get('Location'));
    }

    public function test_guru_t2q_rapor_service_scopes_sd_only_renders_cleanly_and_blocks_locked_or_unauthorized(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;
        $guruKaryawan = Karyawan::make(['nipy' => 'NIPY-TQ-1', 'nama_lengkap' => 'Abdullah', 'gelar' => 'S.Pd.I']);
        $user = $this->makeUserWithRole('Abdullah', 'tq@test.local', 'NIPY-TQ-1', 'guru t2q', $guruKaryawan);

        $kelasSd = Kelas::make(['akademik_master_tahun_ajaran_id' => 1, 'nama_kelas' => '4 Al-Farabi', 'jenjang_kelas' => 'SD', 'tingkatan_kelas' => '4']);
        $kelasSd->id = 10;
        $kelasSmp = Kelas::make(['akademik_master_tahun_ajaran_id' => 1, 'nama_kelas' => '7 Al-Khawarizmi', 'jenjang_kelas' => 'SMP', 'tingkatan_kelas' => '7']);
        $kelasSmp->id = 11;

        $siswaSd = Siswa::make(['nis' => '2026001', 'nama_lengkap' => 'Aisyah Humaira', 'jenis_kelamin' => 'P']);
        $siswaSmp = Siswa::make(['nis' => '2026002', 'nama_lengkap' => 'Zayd Ibrahim', 'jenis_kelamin' => 'L']);

        $akSd = AnggotaKelas::make(['siswa_nis' => '2026001', 'akademik_master_kelas_id' => 10]);
        $akSd->id = 100;
        $akSd->setRelation('siswa', $siswaSd);
        $akSd->setRelation('kelas', $kelasSd);

        $akSmp = AnggotaKelas::make(['siswa_nis' => '2026002', 'akademik_master_kelas_id' => 11]);
        $akSmp->id = 101;
        $akSmp->setRelation('siswa', $siswaSmp);
        $akSmp->setRelation('kelas', $kelasSmp);

        $anggotaSd = AnggotaT2q::make(['anggota_kelas_id' => 100, 'karyawan_nipy' => 'NIPY-TQ-1', 'tingkat' => '4', 'pendaftaran' => 'Lanjutan']);
        $anggotaSd->id = 200;
        $anggotaSd->setRelation('anggotaKelas', $akSd);

        $anggotaSmp = AnggotaT2q::make(['anggota_kelas_id' => 101, 'karyawan_nipy' => 'NIPY-TQ-1', 'tingkat' => '7', 'pendaftaran' => 'Baru']);
        $anggotaSmp->id = 201;
        $anggotaSmp->setRelation('anggotaKelas', $akSmp);

        $scopeData = [
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => 'NIPY-TQ-1',
            'karyawanProfile' => $guruKaryawan,
            'activeTahunAjaran' => $ta,
            'anggotaT2qList' => new EloquentCollection([$anggotaSd, $anggotaSmp]),
            'supervisedKelasIds' => [10, 11],
            'tingkatOptions' => ['4', '7'],
        ];

        $resolvedSd = [
            'saved' => null,
            'is_locked' => false,
            'mid_cutoff_date' => null,
            'tahsin' => [
                'has_data' => true,
                'source' => 'latest_yaumiyah',
                'has_newer_yaumiyah' => false,
                'yaumiyah_id' => 50,
                'tanggal' => '2026-10-14',
                'tanggal_formatted' => '14 Okt 2026',
                'materi' => 'Jilid 4 halaman 11',
                'has_ayat' => false,
                'nilai' => 86.0,
                'nilai_bulat' => 86,
                'predikat' => 'BSH',
                'predikat_label' => 'Berkembang Sesuai Harapan',
                'keunggulan' => 'bacaan panjang 2 harakat (mad)',
                'peningkatan' => 'huruf yang disukunkan',
                'deskripsi' => 'Ananda berkembang sesuai harapan dalam menguasai kompetensi tahsin Jilid 4 halaman 11, terutama dalam bacaan panjang 2 harakat (mad). Perlu peningkatan dalam huruf yang disukunkan.',
                'auto_deskripsi' => 'Ananda berkembang sesuai harapan dalam menguasai kompetensi tahsin Jilid 4 halaman 11, terutama dalam bacaan panjang 2 harakat (mad). Perlu peningkatan dalam huruf yang disukunkan.',
            ],
            'tahfizh' => [
                'has_data' => true,
                'source' => 'latest_yaumiyah',
                'has_newer_yaumiyah' => false,
                'yaumiyah_id' => 60,
                'tanggal' => '2026-10-14',
                'tanggal_formatted' => '14 Okt 2026',
                'materi' => 'Surah al-Buruj ayat 15',
                'has_ayat' => true,
                'nilai' => 85.0,
                'nilai_bulat' => 85,
                'predikat' => 'BSH',
                'predikat_label' => 'Berkembang Sesuai Harapan',
                'keunggulan' => 'mad',
                'peningkatan' => 'kelancaran',
                'deskripsi' => 'Ananda berkembang sesuai harapan dalam menghafal dan melafalkan Surah al-Buruj hingga ayat 15, terutama dalam mad. Perlu peningkatan dalam kelancaran.',
                'auto_deskripsi' => 'Ananda berkembang sesuai harapan dalam menghafal dan melafalkan Surah al-Buruj hingga ayat 15, terutama dalam mad. Perlu peningkatan dalam kelancaran.',
            ],
        ];

        $scopeMock = $this->createMock(GuruT2qScopeService::class);
        $scopeMock->method('getActiveScopeForUser')->willReturn($scopeData);
        $scopeMock->method('getFinalisasiSummaryForUser')->willReturn([
            'mid' => ['periode' => 'mid', 'periode_label' => 'Mid Semester', 'is_final' => false, 'is_partial' => false, 'status_label' => 'DRAFT (BELUM FINAL)', 'can_finalize' => true, 'can_unlock' => false],
            'semester' => ['periode' => 'semester', 'periode_label' => 'Semester', 'is_final' => false, 'is_partial' => false, 'status_label' => 'DRAFT (BELUM FINAL)', 'can_finalize' => true, 'can_unlock' => false],
        ]);

        $resolverMock = $this->createMock(T2qRaporResolverService::class);
        $resolverMock->method('normalizePeriode')->willReturnCallback(static fn (?string $p): string => $p === 'semester' ? 'semester' : 'mid');
        $resolverMock->method('resolveForAnggotaT2qList')->willReturn([200 => $resolvedSd]);

        $service = new GuruT2qRaporService($scopeMock, $resolverMock);

        Model::preventLazyLoading(true);
        try {
            $data = $service->getWorkspaceData($user, ['periode' => PeriodeTp::PERIODE_MID]);
            $html = view('guruT2q.rapor.index', $data)->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame(1, $data['stats']['total_sd']);
        $this->assertSame(1, $data['nonSdCount']);
        $this->assertStringContainsString('Aisyah Humaira', $html);
        $this->assertStringContainsString('Jilid 4 halaman 11', $html);
        $this->assertStringContainsString('Surah al-Buruj ayat 15', $html);
        $this->assertStringContainsString('Skala Predikat Rapor T2Q (Min. 75)', $html);
        $this->assertStringContainsString('js-t2q-ms-search', $html);
        $this->assertStringContainsString('js-t2q-ms-trigger', $html);
        $this->assertStringContainsString('closeAllDropdowns', $html);
        $this->assertStringNotContainsString('Zayd Ibrahim', $html);
        $this->assertStringNotContainsString('4 Al-Farabi', $html);

        // Mengosongkan keunggulan/peningkatan (null akibat ConvertEmptyStringsToNull) tidak boleh kembali ke nilai lama
        $captureResolver = $this->createMock(T2qRaporResolverService::class);
        $captureResolver->method('normalizePeriode')->willReturn(PeriodeTp::PERIODE_MID);
        $captureResolver->method('resolveForAnggotaT2qList')->willReturn([200 => $resolvedSd]);
        $captureResolver->method('buildUpsertRow')->willReturnCallback(
            static function (
                int $taId,
                int $t2qId,
                string $p,
                array $tahsin,
                array $tahfizh,
                ?string $tKeunggulan,
                ?string $tPeningkatan,
                ?string $tDeskripsi
            ): array {
                return [
                    'anggota_t2q_id' => $t2qId,
                    'tahsin_keunggulan' => $tKeunggulan,
                    'tahsin_peningkatan' => $tPeningkatan,
                    'tahsin_deskripsi' => $tDeskripsi,
                ];
            }
        );
        $capturedUpserts = [];
        $captureResolver->expects($this->once())
            ->method('upsertRows')
            ->willReturnCallback(static function (array $rows) use (&$capturedUpserts): void {
                $capturedUpserts = $rows;
            });
        DB::shouldReceive('transaction')->once()->andReturnUsing(static fn ($cb) => $cb());

        $clearService = new GuruT2qRaporService($scopeMock, $captureResolver);
        $clearResult = $clearService->saveBatch($user, [
            'periode' => PeriodeTp::PERIODE_MID,
            'tahsin' => [200 => ['keunggulan' => null, 'peningkatan' => 'huruf yang disukunkan dan kelancaran membaca', 'deskripsi' => null]],
        ]);
        $this->assertSame(1, $clearResult['saved_count']);
        $this->assertNull($capturedUpserts[0]['tahsin_keunggulan']);
        $this->assertSame('huruf yang disukunkan dan kelancaran membaca', $capturedUpserts[0]['tahsin_peningkatan']);
        $this->assertStringContainsString('huruf yang disukunkan dan kelancaran membaca', (string) $capturedUpserts[0]['tahsin_deskripsi']);

        // Kirim ID non-SD harus ditolak 403
        try {
            $service->saveBatch($user, [
                'periode' => PeriodeTp::PERIODE_MID,
                'tahsin' => [201 => ['keunggulan' => 'mad']],
            ]);
            $this->fail('ID non-SD harus ditolak 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Bila terkunci final, saveBatch harus melempar ValidationException
        $lockedResolver = $this->createMock(T2qRaporResolverService::class);
        $lockedResolver->method('normalizePeriode')->willReturn(PeriodeTp::PERIODE_MID);
        $lockedResolver->method('resolveForAnggotaT2qList')->willReturn([200 => [...$resolvedSd, 'is_locked' => true]]);
        $lockedService = new GuruT2qRaporService($scopeMock, $lockedResolver);

        $this->expectException(ValidationException::class);
        $lockedService->saveBatch($user, [
            'periode' => PeriodeTp::PERIODE_MID,
            'tahsin' => [200 => ['keunggulan' => 'mad']],
        ]);
    }

    public function test_pdf_nilai_view_renders_t2q_subtables_with_ayat_context_and_empty_fallback(): void
    {
        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $wali = Karyawan::make(['nipy' => 'NIPY-W1', 'nama_lengkap' => 'Sari', 'gelar' => 'S.Pd']);
        $kelas = Kelas::make(['akademik_master_tahun_ajaran_id' => 1, 'nama_kelas' => '4A', 'romawi' => 'IV', 'jenjang_kelas' => 'SD', 'tingkatan_kelas' => '4']);
        $kelas->setRelation('waliKelas', $wali);
        $sekolah = Sekolah::make(['jenjang_kelas' => 'SD', 'nama_sekolah' => 'SD Prima Insani', 'kabupaten' => 'Garut', 'alamat' => 'Jl. Raya Garut']);
        $titimangsa = Titimangsa::make(['titimangsa_label' => 'Garut, 20 Desember 2026', 'kepala_sekolah_nama_tampil' => 'H. Ahmad, M.Pd']);

        $siswa = Siswa::make(['nis' => '2026001', 'nisn' => '01234', 'nama_lengkap' => 'Aisyah Humaira', 'jenis_kelamin' => 'P']);
        $ak = AnggotaKelas::make(['siswa_nis' => '2026001', 'akademik_master_kelas_id' => 1]);
        $ak->id = 100;
        $ak->setRelation('siswa', $siswa);
        $anggotaT2q = AnggotaT2q::make(['anggota_kelas_id' => 100, 'karyawan_nipy' => 'NIPY-TQ-1', 'tingkat' => '4']);
        $anggotaT2q->id = 200;

        $payloadWithAyat = [
            'kelas' => $kelas,
            'tahunAjaran' => $ta,
            'periode' => PeriodeTp::PERIODE_SEMESTER,
            'periodeLabel' => 'Semester',
            'fase' => 'B',
            'sekolah' => $sekolah,
            'titimangsa' => $titimangsa,
            'logoDataUri' => null,
            'entries' => [[
                'anggota' => $ak,
                'siswa' => $siswa,
                'mapelRows' => [],
                'mapelGroups' => [],
                'ekskulRows' => [],
                't2q' => $anggotaT2q,
                't2qRapor' => [
                    'tahsin' => [
                        'has_data' => true,
                        'materi' => 'Jilid 4 halaman 11',
                        'nilai' => 84,
                        'deskripsi' => 'Ananda berkembang sesuai harapan dalam menguasai kompetensi tahsin Jilid 4 halaman 11, terutama dalam bacaan panjang 2 harakat (mad). Perlu peningkatan dalam huruf yang disukunkan.',
                    ],
                    'tahfizh' => [
                        'has_data' => true,
                        'materi' => 'Surah al-Buruj ayat 15',
                        'nilai' => 88,
                        'deskripsi' => 'Ananda berkembang sesuai harapan dalam menghafal dan melafalkan Surah al-Buruj hingga ayat 15, terutama dalam bacaan mad. Perlu peningkatan dalam kelancaran hafalan.',
                    ],
                ],
                'presensi' => ['sakit' => 0, 'izin' => 0, 'alpa' => 0],
            ]],
        ];

        $partialPayload = $payloadWithAyat;
        $partialPayload['entries'][0]['t2qRapor']['tahfizh'] = ['has_data' => false, 'materi' => null, 'nilai' => null, 'deskripsi' => null];

        Model::preventLazyLoading(true);
        try {
            $htmlFull = view('pdf.rapor.nilai', $payloadWithAyat)->render();
            $htmlPartial = view('pdf.rapor.nilai', $partialPayload)->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString("TAHSIN DAN TAHFIDZ AL-QUR'AN", $htmlFull);
        $this->assertStringContainsString("TAHSIN AL-QUR'AN", $htmlFull);
        $this->assertStringContainsString("TAHFIDZ AL-QUR'AN", $htmlFull);
        $this->assertStringContainsString('Jilid 4 halaman 11', $htmlFull);
        $this->assertStringContainsString('Surah al-Buruj ayat 15', $htmlFull);
        $this->assertStringContainsString('hingga ayat 15', $htmlFull);
        $this->assertStringContainsString('Jilid 4 halaman 11', $htmlPartial);

        $binary = Pdf::loadView('pdf.rapor.nilai', $payloadWithAyat)->setPaper('a4', 'portrait')->output();
        $this->assertStringStartsWith('%PDF-', $binary);
    }
}
