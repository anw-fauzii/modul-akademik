<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DataMaster\BankKomentarController;
use App\Http\Controllers\WaliKelas\CatatanRaporController;
use App\Http\Requests\Admin\DataMaster\KomentarRaporRequest;
use App\Http\Requests\Admin\DataMaster\KomentarT2qRequest;
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
use App\Models\User;
use App\Policies\CatatanRaporWaliPolicy;
use App\Services\Admin\DataMaster\BankKomentarService;
use App\Services\WaliKelas\RaporCatatanWaliService;
use App\Support\Sqid;
use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rules\Exists;
use Illuminate\Validation\Rules\Unique;
use ReflectionMethod;
use Spatie\Permission\Models\Role;
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
        $this->assertStringContainsString('Catatan Guru T2Q:', $pdfHtml);
        $this->assertStringContainsString('Tetap semangat murojaah bersama orang tua.', $pdfHtml);

        $this->assertStringContainsString('Catatan Wali:', $adminRaporHtml);
        $this->assertStringContainsString('Catatan Wali ✓', $adminRaporHtml);

        $this->assertStringContainsString('Catatan Wali Kelas / Pendamping (Mid Semester)', $perkembanganShowHtml);
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
