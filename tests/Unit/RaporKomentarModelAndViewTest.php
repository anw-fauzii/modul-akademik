<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DataMaster\BankKomentarController;
use App\Http\Controllers\GuruT2q\CatatanRaporT2qController;
use App\Http\Controllers\WaliKelas\CatatanRaporController;
use App\Http\Requests\Admin\DataMaster\KomentarRaporRequest;
use App\Http\Requests\Admin\DataMaster\KomentarT2qRequest;
use App\Http\Requests\GuruT2q\SaveCatatanRaporT2qRequest;
use App\Http\Requests\GuruT2q\SaveRaporT2qRequest;
use App\Http\Requests\WaliKelas\SaveCatatanRaporWaliRequest;
use App\Http\Requests\WaliKelas\SaveSingleCatatanRaporWaliRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\KomentarRapor;
use App\Models\Admin\DataMaster\KomentarT2q;
use App\Models\Admin\DataMaster\Sekolah;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\Titimangsa;
use App\Models\Admin\Penilaian\CatatanRaporWali;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Models\Admin\T2q\RaporT2q;
use App\Models\User;
use App\Policies\CatatanRaporWaliPolicy;
use App\Services\Admin\DataMaster\BankKomentarService;
use App\Services\Admin\T2q\T2qRaporResolverService;
use App\Services\GuruT2q\GuruT2qRaporService;
use App\Services\GuruT2q\GuruT2qScopeService;
use App\Services\WaliKelas\RaporCatatanWaliService;
use App\Support\Sqid;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use Illuminate\Validation\ValidationException;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class RaporKomentarModelAndViewTest extends TestCase
{
    public function test_komentar_models_template_rendering_and_sqid_isolation(): void
    {
        $template = new KomentarRapor;
        $template->setRawAttributes([
            'id' => 11,
            'judul' => 'Apresiasi Capaian',
            'kategori' => KomentarRapor::KATEGORI_POSITIF,
            'isi_komentar' => 'Alhamdulillah, {nama} sangat aktif dan mandiri di kelas.',
            'is_active' => true,
            'urutan' => 1,
        ]);

        $this->assertSame('Apresiasi & Capaian Baik', $template->kategori_label);
        $this->assertSame('badge-success', $template->kategori_badge_class);
        $this->assertSame(
            'Alhamdulillah, Ahmad Fauzi sangat aktif dan mandiri di kelas.',
            $template->renderForSiswa('  Ahmad   Fauzi ')
        );

        foreach ([
            CatatanRaporWali::class => 21,
            KomentarRapor::class => 22,
            KomentarT2q::class => 23,
        ] as $class => $id) {
            $token = Sqid::encodeFor($class, $id);
            $this->assertNotNull($token);
            $this->assertSame($id, Sqid::decodeFor($class, $token));
            $this->assertNull(Sqid::decodeFor(Kelas::class, $token));
        }
    }

    public function test_catatan_rapor_wali_policy_and_actor_role_resolution(): void
    {
        $policy = new CatatanRaporWaliPolicy;

        $superAdmin = $this->makeUserWithRole('super admin', 'ADM01');
        $waliUser = $this->makeUserWithRole('wali kelas', 'GURU01');
        $pendampingUser = $this->makeUserWithRole('wali kelas', 'GURU02');
        $guruMapelUser = $this->makeUserWithRole('guru mapel sd', 'GURU03');
        $guruT2qUser = $this->makeUserWithRole('guru t2q', 'T2Q01');

        $this->assertTrue($policy->before($superAdmin, 'viewAny'));
        $this->assertTrue($policy->viewAny($waliUser)->allowed());
        $this->assertTrue($policy->saveBatch($pendampingUser)->allowed());
        $this->assertTrue($policy->viewAny($guruMapelUser)->denied());
        $this->assertTrue($policy->saveBatch($guruT2qUser)->denied());

        $kelas = new Kelas;
        $kelas->setRawAttributes([
            'id' => 7,
            'karyawan_nipy' => 'GURU01',
            'pendamping_nipy' => 'GURU02',
        ]);

        $service = new RaporCatatanWaliService;
        $this->assertSame('Wali Kelas', $service->resolveActorRoleLabel($waliUser, $kelas));
        $this->assertSame('Guru Pendamping', $service->resolveActorRoleLabel($pendampingUser, $kelas));
        $this->assertSame('Super Admin', $service->resolveActorRoleLabel($superAdmin, $kelas));
        $this->assertSame(PeriodeTp::PERIODE_MID, $service->normalizePeriode('mid'));
        $this->assertSame(PeriodeTp::PERIODE_SEMESTER, $service->normalizePeriode('SEMESTER'));

        $bankService = new BankKomentarService;
        $defaultBankFilters = $bankService->normalizeFilters([]);
        $this->assertFalse($defaultBankFilters['hasActiveFilter']);
        $this->assertSame('wali', $defaultBankFilters['tab']);

        $activeBankFilters = $bankService->normalizeFilters([
            'tab' => 'wali',
            'kategori' => KomentarRapor::KATEGORI_POSITIF,
            'jenjang' => 'sd',
            'status' => '1',
            'search' => '  fokus  ',
        ]);
        $this->assertTrue($activeBankFilters['hasActiveFilter']);
        $this->assertSame('SD', $activeBankFilters['jenjang']);
        $this->assertSame('fokus', $activeBankFilters['search']);

        $t2qBankFilters = $bankService->normalizeFilters([
            'tab' => 't2q',
            'sort' => 'az',
            'search' => 'ghunnah',
        ]);
        $this->assertTrue($t2qBankFilters['hasActiveFilter']);
        $this->assertSame('t2q', $t2qBankFilters['tab']);
        $this->assertSame('az', $t2qBankFilters['sort']);

        $catatanMiddlewares = collect(app()->make(CatatanRaporController::class)->getMiddleware())
            ->pluck('middleware')
            ->all();
        $bankMiddlewares = collect(app()->make(BankKomentarController::class)->getMiddleware())
            ->pluck('middleware')
            ->all();

        $this->assertContains('role:super admin|wali kelas', $catatanMiddlewares);
        $this->assertContains('role:super admin', $bankMiddlewares);
    }

    public function test_catatan_and_bank_komentar_requests_normalize_sqid_and_validate(): void
    {
        $kelasToken = Sqid::encodeFor(Kelas::class, 8);
        $anggotaToken = Sqid::encodeFor(AnggotaKelas::class, 55);
        $tplToken = Sqid::encodeFor(KomentarRapor::class, 3);

        $batchReq = SaveCatatanRaporWaliRequest::create('/wali-kelas/catatan-rapor', 'POST', [
            'kelas_id' => $kelasToken,
            'periode' => PeriodeTp::PERIODE_MID,
            'catatan' => [$anggotaToken => '  Terus pertahankan semangat belajar di mid semester.  '],
            'komentar_rapor_id' => [$anggotaToken => $tplToken],
        ]);
        $this->invokePrepareForValidation($batchReq);

        $this->assertSame(8, $batchReq->input('kelas_id'));
        $this->assertSame([55 => 'Terus pertahankan semangat belajar di mid semester.'], $batchReq->input('catatan'));
        $this->assertSame([55 => 3], $batchReq->input('komentar_rapor_id'));
        $this->assertFalse(Validator::make($batchReq->all(), $this->stripDbRules($batchReq->rules()))->fails());

        $singleReq = SaveSingleCatatanRaporWaliRequest::create('/wali-kelas/catatan-rapor/abc', 'PUT', [
            'periode' => PeriodeTp::PERIODE_SEMESTER,
            'catatan' => '  Rajin membaca dan aktif di diskusi. ',
            'komentar_rapor_id' => $tplToken,
        ]);
        $this->invokePrepareForValidation($singleReq);
        $this->assertSame(3, $singleReq->input('komentar_rapor_id'));
        $this->assertFalse(Validator::make($singleReq->all(), $this->stripDbRules($singleReq->rules()))->fails());

        $t2qReq = SaveRaporT2qRequest::create('/guru-t2q/rapor', 'POST', [
            'periode' => PeriodeTp::PERIODE_MID,
            'catatan_t2q' => [10 => 'Tetap istiqomah murojaah di rumah.'],
        ]);
        $this->assertFalse(Validator::make($t2qReq->all(), $t2qReq->rules())->fails());

        $tplReq = KomentarRaporRequest::create('/data-master/bank-komentar/wali', 'POST', [
            'judul' => '  Motivasi Mid Semester  ',
            'kategori' => KomentarRapor::KATEGORI_MOTIVASI,
            'jenjang' => 'sd',
            'isi_komentar' => 'Semangat terus {nama}!',
        ]);
        $this->invokePrepareForValidation($tplReq);
        $this->assertSame('Motivasi Mid Semester', $tplReq->input('judul'));
        $this->assertSame('SD', $tplReq->input('jenjang'));
        $this->assertFalse(Validator::make($tplReq->all(), $this->stripDbRules($tplReq->rules()))->fails());

        $t2qMasterReq = KomentarT2qRequest::create('/data-master/bank-komentar/t2q', 'POST', [
            'komentar' => '  kelancaran bacaan mad thobi\'i ',
        ]);
        $this->invokePrepareForValidation($t2qMasterReq);
        $this->assertFalse(Validator::make($t2qMasterReq->all(), $this->stripDbRules($t2qMasterReq->rules()))->fails());

        // Pastikan request tidak gagal validasi meskipun template belum ter-seed / komentar_rapor_id tidak ada di DB
        $this->assertArrayNotHasKey('exists', $batchReq->rules()['komentar_rapor_id.*']);
        $this->assertFalse(Validator::make([
            'kelas_id' => 8,
            'periode' => PeriodeTp::PERIODE_MID,
            'catatan' => [55 => 'Catatan tanpa template DB'],
            'komentar_rapor_id' => [55 => null],
        ], $this->stripDbRules($batchReq->rules()))->fails());
    }

    public function test_catatan_views_and_rapor_pdf_render_without_lazy_loading(): void
    {
        $ta = new TahunAjaran;
        $ta->setRawAttributes([
            'id' => 4,
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $wali = new Karyawan;
        $wali->setRawAttributes(['nipy' => 'W01', 'nama_lengkap' => 'Budi Santoso', 'gelar' => 'S.Pd']);

        $pendamping = new Karyawan;
        $pendamping->setRawAttributes(['nipy' => 'P01', 'nama_lengkap' => 'Siti Aminah', 'gelar' => 'S.Pd']);

        $kelas = new Kelas;
        $kelas->setRawAttributes([
            'id' => 9,
            'akademik_master_tahun_ajaran_id' => 4,
            'karyawan_nipy' => 'W01',
            'pendamping_nipy' => 'P01',
            'tingkatan_kelas' => 4,
            'nama_kelas' => '4 Umar',
            'jenjang_kelas' => 'SD',
            'romawi' => 'IV',
        ]);
        $kelas->setRelation('tahunAjaran', $ta);
        $kelas->setRelation('waliKelas', $wali);
        $kelas->setRelation('pendamping', $pendamping);

        $siswa = new Siswa;
        $siswa->setRawAttributes([
            'nis' => '202604001',
            'nama_lengkap' => 'Nadia Putri',
            'jenis_kelamin' => 'P',
            'nisn' => '0099887766',
            'status' => Siswa::STATUS_AKTIF,
        ]);

        $anggota = new AnggotaKelas;
        $anggota->setRawAttributes([
            'id' => 55,
            'siswa_nis' => '202604001',
            'akademik_master_kelas_id' => 9,
            'pendaftaran' => 'Naik Kelas',
        ]);
        $anggota->setRelation('siswa', $siswa);
        $anggota->setRelation('kelas', $kelas);

        $template = new KomentarRapor;
        $template->setRawAttributes([
            'id' => 3,
            'judul' => 'Apresiasi Mid Semester',
            'kategori' => KomentarRapor::KATEGORI_POSITIF,
            'jenjang' => 'SD',
            'isi_komentar' => 'Alhamdulillah, {nama} menunjukkan fokus belajar yang baik.',
            'is_active' => true,
            'urutan' => 1,
        ]);

        $catatan = new CatatanRaporWali;
        $catatan->setRawAttributes([
            'id' => 12,
            'akademik_master_tahun_ajaran_id' => 4,
            'anggota_kelas_id' => 55,
            'periode' => PeriodeTp::PERIODE_MID,
            'catatan' => 'Ananda Nadia Putri sangat antusias pada kegiatan kelas.',
            'komentar_rapor_id' => 3,
            'updated_by_name' => 'Siti Aminah, S.Pd',
            'updated_by_role_label' => 'Guru Pendamping',
        ]);

        $t2q = new AnggotaT2q;
        $t2q->setRawAttributes([
            'id' => 81,
            'anggota_kelas_id' => 55,
            'tingkat' => 'Jilid 4',
        ]);

        $sekolah = new Sekolah;
        $sekolah->setRawAttributes(['id' => 1, 'nama_sekolah' => 'SD Prima Insani', 'alamat' => 'Jl. Pendidikan No. 1', 'kabupaten' => 'Garut']);

        $titimangsa = new Titimangsa;
        $titimangsa->setRawAttributes(['id' => 1, 'kepala_sekolah_nama_tampil' => 'Kepala Sekolah', 'kepala_sekolah_nipy' => 'KS01']);

        View::share('errors', new ViewErrorBag);
        Model::preventLazyLoading(true);

        try {
            $waliCatatanHtml = view('waliKelas.catatanRapor.index', [
                'isEligible' => true,
                'ineligibleReason' => null,
                'nipy' => 'P01',
                'karyawanProfile' => $pendamping,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new Collection([$kelas]),
                'selectedKelas' => $kelas,
                'peranDiKelas' => 'Guru Pendamping',
                'periode' => PeriodeTp::PERIODE_MID,
                'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
                'isSd' => true,
                'templates' => new Collection([$template]),
                'studentRows' => collect([[
                    'anggota' => $anggota,
                    'siswa' => $siswa,
                    'catatan' => $catatan,
                    'has_catatan' => true,
                    'presensi' => ['sakit' => 1, 'izin' => 0, 'alpa' => 0],
                    'avg_rapor' => ['avg' => 88.5, 'count' => 8],
                    't2q_summary' => ['tingkat' => 'Jilid 4', 'tahsin_nilai' => 86, 'tahfizh_nilai' => 90, 'catatan_t2q' => 'Istiqomah murojaah di rumah.'],
                ]]),
                'stats' => ['total' => 1, 'filled' => 1, 'empty' => 0, 'pct' => 100],
                'actorRoleLabel' => 'Guru Pendamping',
            ])->render();

            $bankWaliHtml = view('admin.dataMaster.bankKomentar.index', [
                'tab' => 'wali',
                'templates' => new Collection([$template]),
                't2qComments' => new Collection,
                'kategoriOptions' => KomentarRapor::KATEGORI_OPTIONS,
                'jenjangOptions' => ['PG', 'TK', 'SD'],
            ])->render();

            $bankT2qEmptyHtml = view('admin.dataMaster.bankKomentar.index', [
                'tab' => 't2q',
                'templates' => new Collection,
                't2qComments' => new Collection,
                'kategoriOptions' => KomentarRapor::KATEGORI_OPTIONS,
                'jenjangOptions' => ['PG', 'TK', 'SD'],
            ])->render();

            $pdfHtml = view('pdf.rapor.nilai', [
                'kelas' => $kelas,
                'tahunAjaran' => $ta,
                'periode' => PeriodeTp::PERIODE_MID,
                'periodeLabel' => 'Mid Semester',
                'fase' => 'B',
                'sekolah' => $sekolah,
                'titimangsa' => $titimangsa,
                'logoDataUri' => null,
                'entries' => [[
                    'anggota' => $anggota,
                    'siswa' => $siswa,
                    'mapelRows' => [],
                    'mapelGroups' => [],
                    'ekskulRows' => [],
                    't2q' => $t2q,
                    't2qRapor' => [
                        'tahsin' => ['has_data' => true, 'materi' => 'Jilid 4 hal 10', 'nilai' => 86, 'deskripsi' => 'Baik sekali.'],
                        'tahfizh' => ['has_data' => true, 'materi' => 'Surah An-Naba', 'nilai' => 90, 'deskripsi' => 'Lancar.'],
                        'catatan_t2q' => 'Tetap semangat murojaah bersama orang tua.',
                    ],
                    'presensi' => ['sakit' => 1, 'izin' => 0, 'alpa' => 0],
                    'catatanWali' => $catatan,
                ]],
            ])->render();

            $adminRaporHtml = view('admin.penilaian.rapor.index', [
                'tahunAjaranOptions' => new Collection([$ta]),
                'kelasOptions' => new Collection([$kelas]),
                'selectedTahunAjaranId' => 4,
                'selectedTahunAjaran' => $ta,
                'selectedKelas' => $kelas,
                'periode' => PeriodeTp::PERIODE_MID,
                'periodeOptions' => PeriodeTp::PERIODE_OPTIONS,
                'isSd' => true,
                'sekolah' => $sekolah,
                'titimangsa' => $titimangsa,
                'students' => new Collection([$anggota]),
                'rekapCountByAnggota' => [55 => 8],
                'ekskulCountByAnggota' => [55 => 1],
                't2qCountByAnggota' => [55 => 1],
                'catatanCountByAnggota' => [55 => true],
                'presensiByAnggota' => [55 => ['sakit' => 1, 'izin' => 0, 'alpa' => 0]],
                'totalSdMapels' => 8,
                'readiness' => null,
            ])->render();

            $perkembanganShowHtml = view('waliKelas.perkembanganSiswa.show', [
                'isEligible' => true,
                'selectedKelas' => $kelas,
                'activeTahunAjaran' => $ta,
                'assignedClasses' => new Collection([$kelas]),
                'peranDiKelas' => 'Guru Pendamping',
                'anggotaKelas' => $anggota,
                'siswa' => $siswa,
                'periode' => PeriodeTp::PERIODE_MID,
                'isSd' => true,
                'canEditCatatanWali' => true,
                'catatanWali' => $catatan,
                'komentarTemplates' => new Collection([$template]),
                'catatanActorRoleLabel' => 'Guru Pendamping',
                'presensiStats' => ['total' => 20, 'hadir' => 19, 'terlambat' => 0, 'izin' => 0, 'sakit' => 1, 'alpa' => 0, 'hadir_rate' => 95],
                'summary' => ['total_mapel' => 8, 'dinilai_count' => 8, 'tuntas_count' => 8, 'belum_tuntas_count' => 0, 'rata_rapor' => 88.5],
                'subjectDetails' => collect(),
                'readiness' => null,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Nadia Putri', $waliCatatanHtml);
        $this->assertStringContainsString('Guru Pendamping', $waliCatatanHtml);
        $this->assertStringContainsString('Istiqomah murojaah di rumah.', $waliCatatanHtml);

        $this->assertStringContainsString('Apresiasi Mid Semester', $bankWaliHtml);
        $this->assertStringContainsString('bk-filter-grid', $bankWaliHtml);
        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($bankT2qEmptyHtml);
        libxml_clear_errors();
        $xpath = new DOMXPath($document);
        $this->assertSame(3, $xpath->query('//table[@id="myTable6"]/thead/tr/th')->length);
        $this->assertSame(0, $xpath->query('//table[@id="myTable6"]/tbody/tr')->length);
        $this->assertSame(0, $xpath->query('//table[@id="myTable6"]/tbody//*[@colspan]')->length);

        $this->assertStringContainsString('Ananda Nadia Putri sangat antusias pada kegiatan kelas.', $pdfHtml);
        $this->assertStringContainsString('CATATAN GURU T2Q', $pdfHtml);
        $this->assertStringContainsString('Tetap semangat murojaah bersama orang tua.', $pdfHtml);
        $this->assertMatchesRegularExpression('/\.notes-box\s*\{[^}]*text-align:\s*justify;/s', $pdfHtml);

        $this->assertStringContainsString('Catatan Wali:', $adminRaporHtml);
        $this->assertStringContainsString('Catatan Wali ✓', $adminRaporHtml);

        $this->assertStringContainsString('Catatan Wali Kelas / Pendamping (Mid Semester)', $perkembanganShowHtml);
    }

    public function test_guru_t2q_catatan_rapor_controller_request_service_and_view(): void
    {
        $controller = new CatatanRaporT2qController(app(GuruT2qRaporService::class));
        $middlewares = collect($controller->getMiddleware())->pluck('middleware')->all();
        $this->assertContains('role:super admin|guru t2q', $middlewares);

        $sqid81 = Sqid::encodeFor(AnggotaT2q::class, 81);
        $request = new SaveCatatanRaporT2qRequest;
        $request->replace([
            'periode' => 'MID',
            'tingkat' => 'jilid 4',
            'catatan_t2q' => [
                (string) $sqid81 => "  Alhamdulillah bacaan lancar.\r\nTerus semangat murojaah! ",
                '99' => '   ',
            ],
        ]);
        $this->invokePrepareForValidation($request);

        $validator = Validator::make($request->all(), $this->stripDbRules($request->rules()));
        $this->assertFalse($validator->fails());
        $this->assertSame(PeriodeTp::PERIODE_MID, $request->input('periode'));
        $this->assertSame("Alhamdulillah bacaan lancar.\nTerus semangat murojaah!", $request->input('catatan_t2q.81'));
        $this->assertNull($request->input('catatan_t2q.99'));

        $tooLongValidator = Validator::make([
            'periode' => PeriodeTp::PERIODE_MID,
            'catatan_t2q' => [81 => str_repeat('a', 1501)],
        ], $this->stripDbRules($request->rules()));
        $this->assertTrue($tooLongValidator->fails());

        $user = $this->makeUserWithRole('guru t2q', 'T2Q-11');
        $karyawan = Karyawan::make(['nipy' => 'T2Q-11', 'nama_lengkap' => 'Ustadz Farhan', 'gelar' => 'S.Pd.I']);
        $ta = new TahunAjaran;
        $ta->setRawAttributes(['id' => 4, 'nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);

        $kelasSd = new Kelas;
        $kelasSd->setRawAttributes(['id' => 9, 'nama_kelas' => '4A', 'jenjang_kelas' => 'SD', 'tingkatan_kelas' => '4']);
        $siswaSd = Siswa::make(['nis' => '202604001', 'nama_lengkap' => 'Nadia Putri', 'jenis_kelamin' => 'P']);
        $akSd = new AnggotaKelas;
        $akSd->setRawAttributes(['id' => 55, 'siswa_nis' => '202604001', 'akademik_master_kelas_id' => 9]);
        $akSd->setRelation('siswa', $siswaSd);
        $akSd->setRelation('kelas', $kelasSd);
        $t2qSd = new AnggotaT2q;
        $t2qSd->setRawAttributes(['id' => 81, 'anggota_kelas_id' => 55, 'karyawan_nipy' => 'T2Q-11', 'tingkat' => 'Jilid 4']);
        $t2qSd->setRelation('anggotaKelas', $akSd);

        $kelasTk = new Kelas;
        $kelasTk->setRawAttributes(['id' => 10, 'nama_kelas' => 'TK B1', 'jenjang_kelas' => 'TK', 'tingkatan_kelas' => 'TK B']);
        $siswaTk = Siswa::make(['nis' => '202600002', 'nama_lengkap' => 'Zayd Ibrahim', 'jenis_kelamin' => 'L']);
        $akTk = new AnggotaKelas;
        $akTk->setRawAttributes(['id' => 56, 'siswa_nis' => '202600002', 'akademik_master_kelas_id' => 10]);
        $akTk->setRelation('siswa', $siswaTk);
        $akTk->setRelation('kelas', $kelasTk);
        $t2qTk = new AnggotaT2q;
        $t2qTk->setRawAttributes(['id' => 82, 'anggota_kelas_id' => 56, 'karyawan_nipy' => 'T2Q-11', 'tingkat' => 'Jilid 2']);
        $t2qTk->setRelation('anggotaKelas', $akTk);

        $scopeMock = $this->createMock(GuruT2qScopeService::class);
        $scopeMock->method('getActiveScopeForUser')->willReturn([
            'isEligible' => true,
            'ineligibleReason' => null,
            'nipy' => 'T2Q-11',
            'karyawanProfile' => $karyawan,
            'activeTahunAjaran' => $ta,
            'anggotaT2qList' => new Collection([$t2qSd, $t2qTk]),
            'supervisedKelasIds' => [9, 10],
            'tingkatOptions' => ['Jilid 2', 'Jilid 4'],
        ]);
        $scopeMock->method('getFinalisasiSummaryForUser')->willReturn([
            PeriodeTp::PERIODE_MID => [
                'periode' => PeriodeTp::PERIODE_MID,
                'periode_label' => 'Mid Semester',
                'is_final' => false,
                'status_label' => 'DRAFT (BELUM FINAL)',
            ],
        ]);

        $savedRow = new RaporT2q;
        $savedRow->setRawAttributes([
            'id' => 501,
            'akademik_master_tahun_ajaran_id' => 4,
            'anggota_t2q_id' => 81,
            'periode' => PeriodeTp::PERIODE_MID,
            'catatan_t2q' => 'Istiqomah menyetor setiap hari.',
            'updated_by_name' => 'Ustadz Farhan',
        ]);

        $resolverMock = $this->createMock(T2qRaporResolverService::class);
        $resolverMock->method('normalizePeriode')->willReturn(PeriodeTp::PERIODE_MID);
        $resolverMock->method('hasCatatanStorage')->willReturn(true);
        $resolverMock->method('resolveForAnggotaT2qList')->willReturn([
            81 => [
                'saved' => $savedRow,
                'is_locked' => false,
                'mid_cutoff_date' => '2026-10-15',
                'catatan_t2q' => 'Istiqomah menyetor setiap hari.',
                'tahsin' => ['has_data' => true, 'materi' => 'Jilid 4', 'nilai' => 88, 'predikat' => 'BSH', 'keunggulan' => 'mad', 'peningkatan' => 'makhraj', 'deskripsi' => 'Baik.'],
                'tahfizh' => ['has_data' => false, 'materi' => null, 'nilai' => null, 'predikat' => null, 'keunggulan' => null, 'peningkatan' => null, 'deskripsi' => null],
            ],
            82 => [
                'saved' => null,
                'is_locked' => false,
                'mid_cutoff_date' => '2026-10-15',
                'catatan_t2q' => null,
                'tahsin' => ['has_data' => false, 'materi' => null, 'nilai' => null, 'predikat' => null, 'keunggulan' => null, 'peningkatan' => null, 'deskripsi' => null],
                'tahfizh' => ['has_data' => false, 'materi' => null, 'nilai' => null, 'predikat' => null, 'keunggulan' => null, 'peningkatan' => null, 'deskripsi' => null],
            ],
        ]);

        $capturedUpserts = [];
        $resolverMock->method('buildUpsertRow')->willReturnCallback(
            static function (
                int $taId,
                int $t2qId,
                string $p,
                array $tahsin,
                array $tahfizh,
                ?string $tK,
                ?string $tP,
                ?string $tD,
                ?string $fK,
                ?string $fP,
                ?string $fD,
                ?int $actorId,
                ?RaporT2q $saved,
                \DateTimeInterface $now,
                ?string $catatanT2q = null
            ): array {
                return [
                    'anggota_t2q_id' => $t2qId,
                    'tahsin_deskripsi' => $tD,
                    'catatan_t2q' => $catatanT2q,
                ];
            }
        );
        $resolverMock->expects($this->once())
            ->method('upsertRows')
            ->willReturnCallback(static function (array $rows) use (&$capturedUpserts): void {
                $capturedUpserts = $rows;
            });

        $service = new GuruT2qRaporService($scopeMock, $resolverMock);
        $workspace = $service->getCatatanWorkspaceData($user, PeriodeTp::PERIODE_MID);

        $this->assertTrue($workspace['isEligible']);
        $this->assertTrue($workspace['catatanStorageReady']);
        $this->assertSame(2, $workspace['stats']['total_students']);
        $this->assertSame(1, $workspace['stats']['filled_count']);
        $this->assertSame(1, $workspace['stats']['empty_count']);

        View::share('errors', new ViewErrorBag);
        Model::preventLazyLoading(true);
        try {
            $html = view('guruT2q.catatanRapor.index', $workspace)->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Nadia Putri', $html);
        $this->assertStringContainsString('Zayd Ibrahim', $html);
        $this->assertStringContainsString('Istiqomah menyetor setiap hari.', $html);
        $this->assertStringContainsString('Tampil di Rapor PDF', $html);

        DB::shouldReceive('transaction')->once()->andReturnUsing(static fn ($cb) => $cb());

        // Siswa 82 tetap kosong dan belum ada record -> tidak membuat baris kosong baru; siswa 81 terupdate.
        $saveResult = $service->saveCatatanBatch($user, [
            'periode' => PeriodeTp::PERIODE_MID,
            'tingkat' => '',
            'catatan_t2q' => [
                81 => 'Ananda Nadia Putri makin mantap bacaannya.',
                82 => null,
            ],
        ]);
        $this->assertSame(1, $saveResult['saved_count']);
        $this->assertCount(1, $capturedUpserts);
        $this->assertSame(81, $capturedUpserts[0]['anggota_t2q_id']);
        $this->assertSame('Baik.', $capturedUpserts[0]['tahsin_deskripsi']);
        $this->assertSame('Ananda Nadia Putri makin mantap bacaannya.', $capturedUpserts[0]['catatan_t2q']);

        // Siswa di luar bimbingan wajib ditolak 403
        try {
            $service->saveCatatanBatch($user, [
                'periode' => PeriodeTp::PERIODE_MID,
                'catatan_t2q' => [999 => 'Catatan ilegal'],
            ]);
            $this->fail('ID di luar lingkup bimbingan T2Q wajib ditolak 403.');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }

        // Ketika kolom database belum termigrasi, saveCatatanBatch melempar ValidationException yang jelas
        $unmigratedResolver = $this->createMock(T2qRaporResolverService::class);
        $unmigratedResolver->method('hasCatatanStorage')->willReturn(false);
        $unmigratedService = new GuruT2qRaporService($scopeMock, $unmigratedResolver);

        $this->expectException(ValidationException::class);
        $unmigratedService->saveCatatanBatch($user, [
            'periode' => PeriodeTp::PERIODE_MID,
            'catatan_t2q' => [81 => 'Tes'],
        ]);
    }

    private function makeUserWithRole(string $roleName, string $noInduk): User
    {
        $role = new Role;
        $role->setRawAttributes(['id' => 1, 'name' => $roleName, 'guard_name' => 'web']);

        $user = new User;
        $user->setRawAttributes([
            'id' => 1,
            'name' => 'Test User',
            'email' => 'guru@primainsani.sch.id',
            'no_induk' => $noInduk,
        ]);
        $user->setRelation('roles', new Collection([$role]));
        $user->setRelation('permissions', new Collection);

        return $user;
    }

    private function invokePrepareForValidation(object $request): void
    {
        $method = new ReflectionMethod($request, 'prepareForValidation');
        $method->invoke($request);
    }

    /**
     * @param  array<string, mixed>  $rules
     * @return array<string, mixed>
     */
    private function stripDbRules(array $rules): array
    {
        $cleaned = [];

        foreach ($rules as $field => $fieldRules) {
            $list = is_array($fieldRules) ? $fieldRules : explode('|', (string) $fieldRules);
            $cleaned[$field] = array_values(array_filter($list, static function (mixed $rule): bool {
                if (is_string($rule)) {
                    return ! str_starts_with($rule, 'exists:') && ! str_starts_with($rule, 'unique:');
                }

                if ($rule instanceof Unique || $rule instanceof Exists) {
                    return false;
                }

                return true;
            }));
        }

        return $cleaned;
    }
}
