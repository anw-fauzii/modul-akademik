<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\T2q\YaumiyahTahfizhController;
use App\Http\Controllers\Admin\T2q\YaumiyahTahsinController;
use App\Http\Requests\Admin\T2q\YaumiyahTahfizhRequest;
use App\Http\Requests\Admin\T2q\YaumiyahTahsinRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\DataMaster\AngkaArab;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\Surah;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\DataMaster\TargetCapaian;
use App\Models\Admin\T2q\YaumiyahTahfizh;
use App\Models\Admin\T2q\YaumiyahTahsin;
use App\Services\Admin\T2q\YaumiyahTahfizhService;
use App\Services\Admin\T2q\YaumiyahTahsinService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCase;

class T2qYaumiyahTest extends TestCase
{
    public function test_models_relations_casts_and_accessors_follow_dry_and_anti_n_plus_one(): void
    {
        $tahsin = new YaumiyahTahsin;
        $this->assertSame('akademik_t2q_yaumiyah_tahsin', $tahsin->getTable());
        $this->assertSame([
            'anggota_t2q_id',
            'akademik_master_t2q_daftar_jilid_id',
            'akademik_master_t2q_surah_id',
            'akademik_master_t2q_angka_arab_id',
            'tanggal',
            'nilai',
        ], $tahsin->getFillable());
        $this->assertSame('integer', $tahsin->getCasts()['akademik_master_t2q_daftar_jilid_id'] ?? null);
        $this->assertSame('date', $tahsin->getCasts()['tanggal'] ?? null);
        $this->assertSame('float', $tahsin->getCasts()['nilai'] ?? null);

        $tahfizh = new YaumiyahTahfizh;
        $this->assertSame('akademik_t2q_yaumiyah_tahfizh', $tahfizh->getTable());
        $this->assertSame([
            'anggota_t2q_id',
            'akademik_master_t2q_surah_id',
            'akademik_master_t2q_angka_arab_id',
            'tanggal',
            'nilai',
        ], $tahfizh->getFillable());
        $this->assertArrayNotHasKey('akademik_master_t2q_daftar_jilid_id', $tahfizh->getCasts());

        $anggota = new AnggotaT2q;
        $this->assertSame('anggota_t2q_id', $anggota->yaumiyahTahsin()->getForeignKeyName());
        $this->assertSame('anggota_t2q_id', $anggota->yaumiyahTahfizh()->getForeignKeyName());

        $jilid = DaftarJilid::make(['jilid_latin' => 'Jilid 2', 'jenis' => 'halaman']);
        $angka = AngkaArab::make(['angka_latin' => '14', 'angka_arab' => '١٤']);
        $surah = Surah::make(['nama_surah' => 'An-Naba', 'jumlah_ayat' => 40]);

        $tahsinSample = YaumiyahTahsin::make(['nilai' => 87.50, 'tanggal' => '2026-11-18']);
        $tahsinSample->setRelation('daftarJilid', $jilid);
        $tahsinSample->setRelation('angkaArab', $angka);

        $tahfizhSample = YaumiyahTahfizh::make(['nilai' => 90.00, 'tanggal' => '2026-11-18']);
        $tahfizhSample->setRelation('surah', $surah);
        $tahfizhSample->setRelation('angkaArab', $angka);

        Model::preventLazyLoading(true);
        try {
            $this->assertSame('87.5', $tahsinSample->nilai_formatted);
            $this->assertSame('badge-success', $tahsinSample->nilai_badge_class);
            $this->assertSame('Jilid 2 • Hal. 14 (١٤)', $tahsinSample->ringkasan_capaian);

            $this->assertSame('90', $tahfizhSample->nilai_formatted);
            $this->assertSame('An-Naba • Ayat 14 (١٤)', $tahfizhSample->ringkasan_capaian);
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    #[TestWith([85.0, 'badge-success', 'excellent', 'Sangat Baik'])]
    #[TestWith([78.0, 'badge-primary', 'good', 'Baik'])]
    #[TestWith([65.0, 'badge-warning text-dark', 'fair', 'Cukup'])]
    #[TestWith([50.0, 'badge-danger', 'poor', 'Perlu Bimbingan'])]
    public function test_nilai_badge_class_and_tier_ranges(
        float $score,
        string $expectedBadge,
        string $expectedTier,
        string $expectedLabel
    ): void {
        $row = YaumiyahTahfizh::make(['nilai' => $score]);
        $this->assertSame($expectedBadge, $row->nilai_badge_class);
        $this->assertSame($expectedTier, $row->nilai_tier);
        $this->assertSame($expectedLabel, $row->nilai_label);
    }

    public function test_services_build_distinct_payloads_respecting_tahsin_jilid_jenis(): void
    {
        $tahsinService = new YaumiyahTahsinService;
        $tahfizhService = new YaumiyahTahfizhService;

        $this->assertSame('tahsin', $tahsinService->modeKey());
        $this->assertTrue($tahsinService->usesDaftarJilid());
        $this->assertSame('tahfizh', $tahfizhService->modeKey());
        $this->assertFalse($tahfizhService->usesDaftarJilid());
        $this->assertSame('2026-11-18', $tahsinService->resolveDate('2026-11-18'));

        $tahsinBuild = new ReflectionMethod($tahsinService, 'buildRowPayload');
        $lookupMaps = [
            'jilidJenisMap' => [1 => 'halaman', 5 => 'quran'],
            'surahAyatMap' => [78 => 40],
            'angkaLatinMap' => [10 => '10'],
        ];

        $halamanPayload = $tahsinBuild->invoke($tahsinService, [
            'akademik_master_t2q_daftar_jilid_id' => 1,
            'akademik_master_t2q_surah_id' => 78,
            'akademik_master_t2q_angka_arab_id' => 10,
            'nilai' => '90',
        ], $lookupMaps);

        $this->assertSame([
            'akademik_master_t2q_daftar_jilid_id' => 1,
            'akademik_master_t2q_surah_id' => null,
            'akademik_master_t2q_angka_arab_id' => 10,
            'nilai' => 90.0,
        ], $halamanPayload);

        $quranPayload = $tahsinBuild->invoke($tahsinService, [
            'akademik_master_t2q_daftar_jilid_id' => 5,
            'akademik_master_t2q_surah_id' => 78,
            'akademik_master_t2q_angka_arab_id' => 10,
            'nilai' => '88.5',
        ], $lookupMaps);

        $this->assertSame(78, $quranPayload['akademik_master_t2q_surah_id']);

        $tahfizhBuild = new ReflectionMethod($tahfizhService, 'buildRowPayload');
        $tahfizhPayload = $tahfizhBuild->invoke($tahfizhService, [
            'akademik_master_t2q_surah_id' => 78,
            'akademik_master_t2q_angka_arab_id' => 10,
            'nilai' => '95',
        ], $lookupMaps);

        $this->assertSame([
            'akademik_master_t2q_surah_id' => 78,
            'akademik_master_t2q_angka_arab_id' => 10,
            'nilai' => 95.0,
        ], $tahfizhPayload);
    }

    public function test_requests_normalize_decimals_and_skip_validation_for_blank_rows(): void
    {
        $tahsinRequest = YaumiyahTahsinRequest::create('/t2q/yaumiyah-tahsin', 'POST', [
            'tanggal' => '2026-11-18',
            'rows' => [
                101 => [
                    'akademik_master_t2q_daftar_jilid_id' => '',
                    'akademik_master_t2q_surah_id' => '',
                    'akademik_master_t2q_angka_arab_id' => '',
                    'nilai' => '',
                ],
                102 => [
                    'akademik_master_t2q_daftar_jilid_id' => '',
                    'akademik_master_t2q_surah_id' => '',
                    'akademik_master_t2q_angka_arab_id' => '5',
                    'nilai' => '88,5',
                ],
            ],
        ]);

        $prepareTahsin = \Closure::bind(function (): void {
            $this->prepareForValidation();
        }, $tahsinRequest, YaumiyahTahsinRequest::class);
        $prepareTahsin();

        $this->assertNull($tahsinRequest->input('rows.101.nilai'));
        $this->assertSame('88.5', $tahsinRequest->input('rows.102.nilai'));

        $tahsinRules = $tahsinRequest->rules();
        $tahsinRules['rows.*.akademik_master_t2q_daftar_jilid_id'] = ['nullable', 'required_with:rows.*.nilai', 'integer'];
        $tahsinRules['rows.*.akademik_master_t2q_surah_id'] = ['nullable', 'integer'];
        $tahsinRules['rows.*.akademik_master_t2q_angka_arab_id'] = ['nullable', 'integer'];

        $tahsinValidator = Validator::make($tahsinRequest->all(), $tahsinRules, $tahsinRequest->messages());
        $this->assertTrue($tahsinValidator->fails());
        $this->assertFalse($tahsinValidator->errors()->has('rows.101.akademik_master_t2q_daftar_jilid_id'));
        $this->assertTrue($tahsinValidator->errors()->has('rows.102.akademik_master_t2q_daftar_jilid_id'));

        $tahfizhRequest = YaumiyahTahfizhRequest::create('/t2q/yaumiyah-tahfizh', 'POST', [
            'tanggal' => '2026-11-18',
            'rows' => [
                102 => [
                    'akademik_master_t2q_surah_id' => '',
                    'akademik_master_t2q_angka_arab_id' => '5',
                    'nilai' => '90',
                ],
            ],
        ]);
        $tahfizhRules = $tahfizhRequest->rules();
        $tahfizhRules['rows.*.akademik_master_t2q_surah_id'] = ['nullable', 'required_with:rows.*.nilai', 'integer'];
        $tahfizhRules['rows.*.akademik_master_t2q_angka_arab_id'] = ['nullable', 'integer'];

        $tahfizhValidator = Validator::make($tahfizhRequest->all(), $tahfizhRules, $tahfizhRequest->messages());
        $this->assertTrue($tahfizhValidator->fails());
        $this->assertTrue($tahfizhValidator->errors()->has('rows.102.akademik_master_t2q_surah_id'));
    }

    public function test_controllers_routes_and_both_workspaces_render_cleanly_without_lazy_loading(): void
    {
        $this->assertTrue(Route::has('t2q.yaumiyah-tahsin.index'));
        $this->assertTrue(Route::has('t2q.yaumiyah-tahsin.store'));
        $this->assertTrue(Route::has('t2q.yaumiyah-tahsin.destroy'));
        $this->assertTrue(Route::has('t2q.yaumiyah-tahfizh.index'));
        $this->assertTrue(Route::has('t2q.yaumiyah-tahfizh.store'));
        $this->assertTrue(Route::has('t2q.yaumiyah-tahfizh.destroy'));

        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;

        $guru = Karyawan::make(['nipy' => 'NIPY-T2Q', 'nama_lengkap' => 'Ustadz Ahmad', 'gelar' => 'S.Pd.I']);
        $kelas = Kelas::make(['nama_kelas' => '1 Al-Fatihah', 'jenjang_kelas' => 'SD', 'tingkatan_kelas' => '1']);
        $kelas->setRelation('tahunAjaran', $ta);
        $siswa = Siswa::make(['nis' => '2026001', 'nama_lengkap' => 'Zaid Al-Khair']);
        $ak = AnggotaKelas::make(['siswa_nis' => '2026001', 'akademik_master_kelas_id' => 10]);
        $ak->setRelation('siswa', $siswa);
        $ak->setRelation('kelas', $kelas);

        $anggota = AnggotaT2q::make(['anggota_kelas_id' => 10, 'karyawan_nipy' => 'NIPY-T2Q', 'tingkat' => '1']);
        $anggota->id = 501;
        $anggota->setRelation('anggotaKelas', $ak);
        $anggota->setRelation('guruT2q', $guru);

        $jilid = DaftarJilid::make(['jilid_latin' => 'Jilid 2', 'jilid_arab' => 'المجلد ٢', 'jenis' => 'halaman', 'urutan' => 2]);
        $jilid->id = 2;
        $surah = Surah::make(['nama_surah' => 'An-Naba', 'nama_surah_arab' => 'النبأ', 'jumlah_ayat' => 40]);
        $surah->id = 78;
        $angka = AngkaArab::make(['angka_latin' => '15', 'angka_arab' => '١٥']);
        $angka->id = 15;

        $target = TargetCapaian::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'tingkat' => '1',
            'akademik_master_t2q_daftar_jilid_id' => 2,
            'akademik_master_t2q_surah_id' => 78,
        ]);
        $target->setRelation('daftarJilid', $jilid);
        $target->setRelation('surah', $surah);

        $todayTahsin = YaumiyahTahsin::make([
            'anggota_t2q_id' => 501,
            'akademik_master_t2q_daftar_jilid_id' => 2,
            'akademik_master_t2q_angka_arab_id' => 15,
            'tanggal' => '2026-11-18',
            'nilai' => 92,
        ]);
        $todayTahsin->id = 88;
        $todayTahsin->setRelation('daftarJilid', $jilid);
        $todayTahsin->setRelation('angkaArab', $angka);

        $prevTahfizh = YaumiyahTahfizh::make([
            'anggota_t2q_id' => 501,
            'akademik_master_t2q_surah_id' => 78,
            'akademik_master_t2q_angka_arab_id' => 15,
            'tanggal' => '2026-11-17',
            'nilai' => 89,
        ]);
        $prevTahfizh->setRelation('surah', $surah);
        $prevTahfizh->setRelation('angkaArab', $angka);

        $sharedPayload = [
            'activeTahunAjaran' => $ta,
            'tanggal' => '2026-11-18',
            'tanggalFormatted' => 'Rabu, 18 November 2026',
            'guruOptions' => new Collection([$guru]),
            'selectedGuruNipy' => 'NIPY-T2Q',
            'tingkatTabs' => ['1' => 12, '2' => 10, '3' => 9, '4' => 8],
            'selectedTingkat' => '1',
            'students' => new Collection([$anggota]),
            'targetCapaian' => new Collection([$target]),
            'daftarJilidList' => new Collection([$jilid]),
            'surahList' => new Collection([$surah]),
            'angkaArabList' => new Collection([$angka]),
        ];

        $tahsinService = $this->createMock(YaumiyahTahsinService::class);
        $tahsinService->expects($this->once())->method('getWorkspaceData')->willReturn([
            ...$sharedPayload,
            'mode' => 'tahsin',
            'usesDaftarJilid' => true,
            'todayEntries' => new Collection([501 => $todayTahsin]),
            'previousEntries' => new Collection,
            'savedCount' => 1,
        ]);
        $tahsinService->expects($this->once())->method('saveBatch')->willReturn(1);
        $tahsinService->expects($this->once())->method('delete')->with($todayTahsin)->willReturn(true);

        $tahfizhService = $this->createMock(YaumiyahTahfizhService::class);
        $tahfizhService->expects($this->once())->method('getWorkspaceData')->willReturn([
            ...$sharedPayload,
            'mode' => 'tahfizh',
            'usesDaftarJilid' => false,
            'todayEntries' => new Collection,
            'previousEntries' => new Collection([501 => $prevTahfizh]),
            'savedCount' => 0,
        ]);
        $tahfizhService->expects($this->once())->method('saveBatch')->willReturn(1);
        $tahfizhService->expects($this->once())->method('delete')->with($prevTahfizh)->willReturn(true);

        $tahsinCtrl = new YaumiyahTahsinController($tahsinService);
        $tahfizhCtrl = new YaumiyahTahfizhController($tahfizhService);

        $tahsinView = $tahsinCtrl->index(Request::create('/t2q/yaumiyah-tahsin', 'GET'));
        $tahfizhView = $tahfizhCtrl->index(Request::create('/t2q/yaumiyah-tahfizh', 'GET'));

        $tahsinPost = YaumiyahTahsinRequest::create('/t2q/yaumiyah-tahsin', 'POST', [
            'tanggal' => '2026-11-18',
            'karyawan_nipy' => 'NIPY-T2Q',
            'tingkat' => '1',
            'rows' => [501 => ['akademik_master_t2q_daftar_jilid_id' => 2, 'nilai' => 92]],
        ]);
        $tahsinPost->setValidator(Validator::make($tahsinPost->all(), [
            'tanggal' => ['required'],
            'rows' => ['required', 'array'],
        ]));

        $tahfizhPost = YaumiyahTahfizhRequest::create('/t2q/yaumiyah-tahfizh', 'POST', [
            'tanggal' => '2026-11-18',
            'karyawan_nipy' => 'NIPY-T2Q',
            'tingkat' => '1',
            'rows' => [501 => ['akademik_master_t2q_surah_id' => 78, 'nilai' => 89]],
        ]);
        $tahfizhPost->setValidator(Validator::make($tahfizhPost->all(), [
            'tanggal' => ['required'],
            'rows' => ['required', 'array'],
        ]));

        $this->assertInstanceOf(RedirectResponse::class, $tahsinCtrl->store($tahsinPost));
        $this->assertInstanceOf(RedirectResponse::class, $tahsinCtrl->destroy(Request::create('/t2q/yaumiyah-tahsin/88', 'DELETE'), $todayTahsin));
        $this->assertInstanceOf(RedirectResponse::class, $tahfizhCtrl->store($tahfizhPost));
        $this->assertInstanceOf(RedirectResponse::class, $tahfizhCtrl->destroy(Request::create('/t2q/yaumiyah-tahfizh/1', 'DELETE'), $prevTahfizh));

        $jilidQuran = DaftarJilid::make(['jilid_latin' => 'Tilawah', 'jilid_arab' => 'تلاوة', 'jenis' => 'quran', 'urutan' => 11]);
        $jilidQuran->id = 11;
        $todayTahsinQuran = YaumiyahTahsin::make([
            'anggota_t2q_id' => 501,
            'akademik_master_t2q_daftar_jilid_id' => 11,
            'akademik_master_t2q_surah_id' => 78,
            'akademik_master_t2q_angka_arab_id' => 15,
            'tanggal' => '2026-11-18',
            'nilai' => 94,
        ]);
        $todayTahsinQuran->id = 89;

        Model::preventLazyLoading(true);
        try {
            $tahsinHtml = $tahsinView->with('errors', new ViewErrorBag)->render();
            $tahsinQuranHtml = view('admin.t2q.yaumiyahTahsin.index', [
                ...$sharedPayload,
                'mode' => 'tahsin',
                'usesDaftarJilid' => true,
                'daftarJilidList' => new Collection([$jilid, $jilidQuran]),
                'todayEntries' => new Collection([501 => $todayTahsinQuran]),
                'previousEntries' => new Collection,
                'savedCount' => 1,
            ])->with('errors', new ViewErrorBag)->render();
            $tahfizhHtml = $tahfizhView->with('errors', new ViewErrorBag)->render();
            $sidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Yaumiyah Tahsin', $tahsinHtml);
        $this->assertStringContainsString('Tingkat 4', $tahsinHtml);
        $this->assertStringContainsString('Target: Jilid 2', $tahsinHtml);
        $this->assertStringContainsString('Bacaan / Jilid', $tahsinHtml);
        $this->assertStringNotContainsString('>Surah Al-Qur\'an<', $tahsinHtml);
        $this->assertStringContainsString('js-t2q-surah-wrap d-none mt-2', $tahsinHtml);
        $this->assertStringContainsString('Zaid Al-Khair', $tahsinHtml);
        $this->assertStringContainsString('15 (١٥)', $tahsinHtml);
        $this->assertStringContainsString('akademik_master_t2q_daftar_jilid_id', $tahsinHtml);
        $this->assertStringContainsString('data-select2="true"', $tahsinHtml);
        $this->assertStringContainsString('window.PIONSelect.init', $tahsinHtml);
        $this->assertStringContainsString(route('t2q.yaumiyah-tahsin.destroy', sqidFor('t2q.yaumiyah-tahsin.destroy', 88)), $tahsinHtml);

        $this->assertStringContainsString('js-t2q-surah-wrap  mt-2', $tahsinQuranHtml);
        $this->assertStringNotContainsString('js-t2q-surah-wrap d-none', $tahsinQuranHtml);

        $this->assertStringContainsString('Yaumiyah Tahfizh', $tahfizhHtml);
        $this->assertStringContainsString('>Surah Al-Qur\'an<', $tahfizhHtml);
        $this->assertStringNotContainsString('akademik_master_t2q_daftar_jilid_id', $tahfizhHtml);
        $this->assertStringContainsString('An-Naba • Ayat 15 (١٥)', $tahfizhHtml);

        $this->assertStringContainsString('Yaumiyah T2Q', $sidebarHtml);
        $this->assertStringContainsString(route('t2q.yaumiyah-tahsin.index'), $sidebarHtml);
        $this->assertStringContainsString(route('t2q.yaumiyah-tahfizh.index'), $sidebarHtml);
        $this->assertStringContainsString(route('t2q.yaumiyah-tahsin.matriks'), $sidebarHtml);
        $this->assertStringContainsString('Matriks (Sen–Kam)', $tahsinHtml);
        $this->assertStringContainsString('Matriks (Sen–Kam)', $tahfizhHtml);
    }

    public function test_weekly_matrix_builder_snaps_monday_to_thursday_and_prevents_lazy_loading(): void
    {
        $tahsinService = new YaumiyahTahsinService;
        $tahfizhService = new YaumiyahTahfizhService;

        // Wednesday 2026-11-18 and Sunday 2026-11-22 must both snap to Mon 2026-11-16 .. Thu 2026-11-19
        $midWeek = $tahsinService->resolveWeekPeriod('2026-11-18');
        $endWeek = $tahsinService->resolveWeekPeriod('2026-11-22');

        $this->assertSame('2026-11-16', $midWeek['week_start']);
        $this->assertSame('2026-11-19', $midWeek['week_end']);
        $this->assertSame($midWeek['days'], $endWeek['days']);
        $this->assertCount(4, $midWeek['days']);
        $this->assertSame(['2026-11-16', '2026-11-17', '2026-11-18', '2026-11-19'], array_column($midWeek['days'], 'date'));
        $this->assertSame('16 – 19 November 2026', $midWeek['range_label']);
        $this->assertSame('2026-11-09', $midWeek['prev_week_date']);
        $this->assertSame('2026-11-23', $midWeek['next_week_date']);

        $guru = Karyawan::make(['nipy' => 'NIPY-T2Q', 'nama_lengkap' => 'Ustadz Ahmad', 'gelar' => 'S.Pd.I']);
        $siswa = Siswa::make(['nis' => '2026001', 'nama_lengkap' => 'Zaid Al-Khair']);
        $kelas = Kelas::make(['nama_kelas' => '1 Al-Fatihah']);
        $ak = AnggotaKelas::make(['siswa_nis' => '2026001']);
        $ak->setRelation('siswa', $siswa);
        $ak->setRelation('kelas', $kelas);

        $anggota = AnggotaT2q::make(['tingkat' => '1', 'karyawan_nipy' => 'NIPY-T2Q']);
        $anggota->id = 501;
        $anggota->setRelation('anggotaKelas', $ak);
        $anggota->setRelation('guruT2q', $guru);

        $jilidHalaman = DaftarJilid::make(['jilid_latin' => 'Jilid 2', 'jilid_arab' => '٢', 'jenis' => 'halaman']);
        $jilidQuran = DaftarJilid::make(['jilid_latin' => 'Tilawah', 'jilid_arab' => 'تلاوة', 'jenis' => 'quran']);
        $surah = Surah::make(['nama_surah' => 'An-Naba', 'nama_surah_arab' => 'النبأ', 'jumlah_ayat' => 40]);
        $angka14 = AngkaArab::make(['angka_latin' => '14', 'angka_arab' => '١٤']);
        $angka15 = AngkaArab::make(['angka_latin' => '15', 'angka_arab' => '١٥']);

        $tahsinSenin = YaumiyahTahsin::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-16', 'nilai' => 86]);
        $tahsinSenin->id = 1;
        $tahsinSenin->setRelation('daftarJilid', $jilidHalaman);
        $tahsinSenin->setRelation('angkaArab', $angka14);

        $tahsinRabu = YaumiyahTahsin::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-18', 'nilai' => 94]);
        $tahsinRabu->id = 2;
        $tahsinRabu->setRelation('daftarJilid', $jilidQuran);
        $tahsinRabu->setRelation('surah', $surah);
        $tahsinRabu->setRelation('angkaArab', $angka15);

        $tahfizhSelasa = YaumiyahTahfizh::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-17', 'nilai' => 90]);
        $tahfizhSelasa->id = 3;
        $tahfizhSelasa->setRelation('surah', $surah);
        $tahfizhSelasa->setRelation('angkaArab', $angka15);

        $tahsinBuildMatrix = new ReflectionMethod($tahsinService, 'buildWeeklyMatrix');
        $tahfizhBuildMatrix = new ReflectionMethod($tahfizhService, 'buildWeeklyMatrix');

        Model::preventLazyLoading(true);
        try {
            $tahsinMatrix = $tahsinBuildMatrix->invoke(
                $tahsinService,
                new Collection([$anggota]),
                [501 => ['2026-11-16' => $tahsinSenin, '2026-11-18' => $tahsinRabu]],
                $midWeek
            );
            $tahfizhMatrix = $tahfizhBuildMatrix->invoke(
                $tahfizhService,
                new Collection([$anggota]),
                [501 => ['2026-11-17' => $tahfizhSelasa]],
                $midWeek
            );
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame(2, $tahsinMatrix['summary']['total_recorded']);
        $this->assertSame(4, $tahsinMatrix['summary']['possible_slots']);
        $this->assertSame(50.0, $tahsinMatrix['summary']['completion_pct']);
        $this->assertSame('90', $tahsinMatrix['summary']['overall_avg_formatted']);
        $this->assertSame(2, $tahsinMatrix['rows'][0]['recorded_count']);
        $this->assertSame('Jilid 2', $tahsinMatrix['rows'][0]['cells']['2026-11-16']['primary_label']);
        $this->assertSame('Hal. 14', $tahsinMatrix['rows'][0]['cells']['2026-11-16']['secondary_label']);
        $this->assertSame('Tilawah', $tahsinMatrix['rows'][0]['cells']['2026-11-18']['context_label']);
        $this->assertSame('An-Naba', $tahsinMatrix['rows'][0]['cells']['2026-11-18']['primary_label']);
        $this->assertSame('Ayat 15', $tahsinMatrix['rows'][0]['cells']['2026-11-18']['secondary_label']);

        $this->assertSame(1, $tahfizhMatrix['summary']['total_recorded']);
        $this->assertSame('An-Naba', $tahfizhMatrix['rows'][0]['cells']['2026-11-17']['primary_label']);
        $this->assertSame('النبأ', $tahfizhMatrix['rows'][0]['cells']['2026-11-17']['arab_title']);
        $this->assertSame('90', $tahfizhMatrix['rows'][0]['avg_formatted']);
    }

    public function test_matriks_routes_controllers_and_views_render_cleanly(): void
    {
        $this->assertTrue(Route::has('t2q.yaumiyah-tahsin.matriks'));
        $this->assertTrue(Route::has('t2q.yaumiyah-tahfizh.matriks'));

        $ta = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $ta->id = 1;
        $guru = Karyawan::make(['nipy' => 'NIPY-T2Q', 'nama_lengkap' => 'Ustadz Ahmad', 'gelar' => 'S.Pd.I']);
        $kelas = Kelas::make(['nama_kelas' => '1 Al-Fatihah', 'jenjang_kelas' => 'SD', 'tingkatan_kelas' => '1']);
        $kelas->setRelation('tahunAjaran', $ta);
        $siswa = Siswa::make(['nis' => '2026001', 'nama_lengkap' => 'Zaid Al-Khair']);
        $ak = AnggotaKelas::make(['siswa_nis' => '2026001', 'akademik_master_kelas_id' => 10]);
        $ak->setRelation('siswa', $siswa);
        $ak->setRelation('kelas', $kelas);

        $anggota = AnggotaT2q::make(['anggota_kelas_id' => 10, 'karyawan_nipy' => 'NIPY-T2Q', 'tingkat' => '1']);
        $anggota->id = 501;
        $anggota->setRelation('anggotaKelas', $ak);
        $anggota->setRelation('guruT2q', $guru);

        $jilid = DaftarJilid::make(['jilid_latin' => 'Jilid 2', 'jilid_arab' => '٢', 'jenis' => 'halaman']);
        $surah = Surah::make(['nama_surah' => 'An-Naba', 'nama_surah_arab' => 'النبأ', 'jumlah_ayat' => 40]);
        $angka = AngkaArab::make(['angka_latin' => '15', 'angka_arab' => '١٥']);
        $target = TargetCapaian::make(['tingkat' => '1']);
        $target->setRelation('daftarJilid', $jilid);
        $target->setRelation('surah', $surah);

        $realTahsinService = new YaumiyahTahsinService;
        $period = $realTahsinService->resolveWeekPeriod('2026-11-18');

        $tahsinEntry = YaumiyahTahsin::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-16', 'nilai' => 88]);
        $tahsinEntry->id = 71;
        $tahsinEntry->setRelation('daftarJilid', $jilid);
        $tahsinEntry->setRelation('angkaArab', $angka);

        $tahfizhEntry = YaumiyahTahfizh::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-18', 'nilai' => 95]);
        $tahfizhEntry->id = 72;
        $tahfizhEntry->setRelation('surah', $surah);
        $tahfizhEntry->setRelation('angkaArab', $angka);

        $tahsinMatrix = (new ReflectionMethod($realTahsinService, 'buildWeeklyMatrix'))
            ->invoke($realTahsinService, new Collection([$anggota]), [501 => ['2026-11-16' => $tahsinEntry]], $period);

        $realTahfizhService = new YaumiyahTahfizhService;
        $tahfizhMatrix = (new ReflectionMethod($realTahfizhService, 'buildWeeklyMatrix'))
            ->invoke($realTahfizhService, new Collection([$anggota]), [501 => ['2026-11-18' => $tahfizhEntry]], $period);

        $baseMatrixPayload = [
            'activeTahunAjaran' => $ta,
            'tanggal' => '2026-11-18',
            'tanggalFormatted' => 'Rabu, 18 November 2026',
            'guruOptions' => new Collection([$guru]),
            'selectedGuruNipy' => 'NIPY-T2Q',
            'tingkatTabs' => ['1' => 12, '2' => 10],
            'selectedTingkat' => '1',
            'students' => new Collection([$anggota]),
            'targetCapaian' => new Collection([$target]),
            'period' => $period,
        ];

        $tahsinServiceMock = $this->createMock(YaumiyahTahsinService::class);
        $tahsinServiceMock->expects($this->once())->method('getMatrixData')->willReturn([
            ...$baseMatrixPayload,
            'mode' => 'tahsin',
            'usesDaftarJilid' => true,
            'matrix' => $tahsinMatrix,
        ]);

        $tahfizhServiceMock = $this->createMock(YaumiyahTahfizhService::class);
        $tahfizhServiceMock->expects($this->once())->method('getMatrixData')->willReturn([
            ...$baseMatrixPayload,
            'mode' => 'tahfizh',
            'usesDaftarJilid' => false,
            'matrix' => $tahfizhMatrix,
        ]);

        $tahsinView = (new YaumiyahTahsinController($tahsinServiceMock))->matriks(Request::create('/t2q/yaumiyah-tahsin/matriks', 'GET'));
        $tahfizhView = (new YaumiyahTahfizhController($tahfizhServiceMock))->matriks(Request::create('/t2q/yaumiyah-tahfizh/matriks', 'GET'));

        Model::preventLazyLoading(true);
        try {
            $tahsinMatrixHtml = $tahsinView->with('errors', new ViewErrorBag)->render();
            $tahfizhMatrixHtml = $tahfizhView->with('errors', new ViewErrorBag)->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Matriks Catatan Tahsin', $tahsinMatrixHtml);
        $this->assertStringContainsString('16 – 19 November 2026', $tahsinMatrixHtml);
        $this->assertStringContainsString('Jilid 2', $tahsinMatrixHtml);
        $this->assertStringContainsString('Hal. 15', $tahsinMatrixHtml);
        $this->assertStringContainsString('1/4', $tahsinMatrixHtml);
        $this->assertStringContainsString('t2qMatrixDetailModal', $tahsinMatrixHtml);
        $this->assertStringContainsString(route('t2q.yaumiyah-tahsin.statistik'), $tahsinMatrixHtml);

        $this->assertStringContainsString('Matriks Catatan Tahfizh', $tahfizhMatrixHtml);
        $this->assertStringContainsString('An-Naba', $tahfizhMatrixHtml);
        $this->assertStringContainsString('النبأ', $tahfizhMatrixHtml);
        $this->assertStringContainsString('Ayat 15', $tahfizhMatrixHtml);
        $this->assertStringContainsString('95', $tahfizhMatrixHtml);
        $this->assertStringContainsString(route('t2q.yaumiyah-tahfizh.statistik'), $tahfizhMatrixHtml);
    }

    public function test_tahun_ajaran_academic_months_and_statistik_aggregator_prevent_lazy_loading(): void
    {
        $taSem1 = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $taSem1->id = 1;
        $taSem2 = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '2', 'is_active' => false]);
        $taSem2->id = 2;

        $this->assertSame(['2026-07', '2026-08', '2026-09', '2026-10', '2026-11', '2026-12'], $taSem1->academicMonthKeys());
        $this->assertSame(['2027-01', '2027-02', '2027-03', '2027-04', '2027-05', '2027-06'], $taSem2->academicMonthKeys());

        $guru = Karyawan::make(['nipy' => 'NIPY-T2Q', 'nama_lengkap' => 'Ustadz Ahmad', 'gelar' => 'S.Pd.I']);
        $kelas = Kelas::make(['nama_kelas' => '1 Al-Fatihah']);
        $siswa1 = Siswa::make(['nis' => '2026001', 'nama_lengkap' => 'Zaid Al-Khair']);
        $siswa2 = Siswa::make(['nis' => '2026002', 'nama_lengkap' => 'Bilal Ar-Rasyid']);

        $ak1 = AnggotaKelas::make(['siswa_nis' => '2026001']);
        $ak1->setRelation('siswa', $siswa1);
        $ak1->setRelation('kelas', $kelas);
        $ak2 = AnggotaKelas::make(['siswa_nis' => '2026002']);
        $ak2->setRelation('siswa', $siswa2);
        $ak2->setRelation('kelas', $kelas);

        $anggota1 = AnggotaT2q::make(['tingkat' => '1', 'karyawan_nipy' => 'NIPY-T2Q']);
        $anggota1->id = 501;
        $anggota1->setRelation('anggotaKelas', $ak1);
        $anggota1->setRelation('guruT2q', $guru);

        $anggota2 = AnggotaT2q::make(['tingkat' => '2', 'karyawan_nipy' => 'NIPY-T2Q']);
        $anggota2->id = 502;
        $anggota2->setRelation('anggotaKelas', $ak2);
        $anggota2->setRelation('guruT2q', $guru);

        $jilid1 = DaftarJilid::make(['jilid_latin' => 'Jilid 1', 'jilid_arab' => '١', 'jenis' => 'halaman', 'urutan' => 1]);
        $jilid1->id = 1;
        $jilid2 = DaftarJilid::make(['jilid_latin' => 'Jilid 2', 'jilid_arab' => '٢', 'jenis' => 'halaman', 'urutan' => 2]);
        $jilid2->id = 2;

        $surah = Surah::make(['nama_surah' => 'An-Naba', 'nama_surah_arab' => 'النبأ', 'jumlah_ayat' => 40]);
        $surah->id = 78;
        $surah2 = Surah::make(['nama_surah' => 'An-Nazi\'at', 'nama_surah_arab' => 'النازعات', 'jumlah_ayat' => 46]);
        $surah2->id = 79;
        $angka = AngkaArab::make(['angka_latin' => '15', 'angka_arab' => '١٥']);

        $target1 = TargetCapaian::make(['tingkat' => '1']);
        $target1->setRelation('daftarJilid', $jilid2);
        $target1->setRelation('surah', $surah);

        $target2 = TargetCapaian::make(['tingkat' => '1']);
        $target2->setRelation('daftarJilid', $jilid2);
        $target2->setRelation('surah', $surah2);

        // Anggota 3 (Tingkat 1) baru mencapai Jilid 1 padahal target tingkat 1 adalah Jilid 2
        $siswa3 = Siswa::make(['nis' => '2026003', 'nama_lengkap' => 'Umar Al-Faruq']);
        $ak3 = AnggotaKelas::make(['siswa_nis' => '2026003']);
        $ak3->setRelation('siswa', $siswa3);
        $ak3->setRelation('kelas', $kelas);
        $anggota3 = AnggotaT2q::make(['tingkat' => '1', 'karyawan_nipy' => 'NIPY-T2Q']);
        $anggota3->id = 503;
        $anggota3->setRelation('anggotaKelas', $ak3);
        $anggota3->setRelation('guruT2q', $guru);

        $tEntry1 = YaumiyahTahsin::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-10-12', 'nilai' => 90]);
        $tEntry1->id = 10;
        $tEntry1->setRelation('daftarJilid', $jilid1);
        $tEntry1->setRelation('angkaArab', $angka);

        $tEntry2 = YaumiyahTahsin::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-18', 'nilai' => 70]);
        $tEntry2->id = 11;
        $tEntry2->setRelation('daftarJilid', $jilid2);
        $tEntry2->setRelation('angkaArab', $angka);

        $tEntry3 = YaumiyahTahsin::make(['anggota_t2q_id' => 503, 'tanggal' => '2026-11-18', 'nilai' => 88]);
        $tEntry3->id = 12;
        $tEntry3->setRelation('daftarJilid', $jilid1);
        $tEntry3->setRelation('angkaArab', $angka);

        $fEntry1 = YaumiyahTahfizh::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-18', 'nilai' => 95]);
        $fEntry1->id = 20;
        $fEntry1->setRelation('surah', $surah);
        $fEntry1->setRelation('angkaArab', $angka);

        $fEntry2 = YaumiyahTahfizh::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-19', 'nilai' => 92]);
        $fEntry2->id = 21;
        $fEntry2->setRelation('surah', $surah2);
        $fEntry2->setRelation('angkaArab', $angka);

        $fEntry3 = YaumiyahTahfizh::make(['anggota_t2q_id' => 503, 'tanggal' => '2026-11-18', 'nilai' => 84]);
        $fEntry3->id = 22;
        $fEntry3->setRelation('surah', $surah);
        $fEntry3->setRelation('angkaArab', $angka);

        $tahsinService = new YaumiyahTahsinService;
        $tahfizhService = new YaumiyahTahfizhService;
        $buildTahsinStat = new ReflectionMethod($tahsinService, 'buildAnnualStatistik');
        $buildTahfizhStat = new ReflectionMethod($tahfizhService, 'buildAnnualStatistik');

        Model::preventLazyLoading(true);
        try {
            $tahsinStat = $buildTahsinStat->invoke(
                $tahsinService,
                $taSem1,
                new Collection([$anggota1, $anggota3, $anggota2]),
                new Collection([$tEntry1, $tEntry2, $tEntry3]),
                collect(['1' => collect([$target1])])
            );
            $tahfizhStat = $buildTahfizhStat->invoke(
                $tahfizhService,
                $taSem1,
                new Collection([$anggota1, $anggota3]),
                new Collection([$fEntry1, $fEntry2, $fEntry3]),
                collect(['1' => collect([$target1, $target2])])
            );
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertSame(3, $tahsinStat['summary']['total_students']);
        $this->assertSame(2, $tahsinStat['summary']['active_students']);
        $this->assertSame(2, $tahsinStat['summary']['scored_students']);
        $this->assertSame(1, $tahsinStat['summary']['inactive_students']);
        $this->assertSame(66.7, $tahsinStat['summary']['student_activity_pct']);
        $this->assertSame(3, $tahsinStat['summary']['total_recorded']);
        // Zaid avg (90+70)/2 = 80 (Baik), Umar avg 88 (Sangat Baik) => rata-rata siswa = (80+88)/2 = 84
        $this->assertSame('84', $tahsinStat['summary']['overall_avg_formatted']);
        $this->assertSame('good', $tahsinStat['summary']['overall_avg_tier']);
        $this->assertSame(1, $tahsinStat['summary']['excellent_count']);
        $this->assertSame(50.0, $tahsinStat['summary']['excellent_pct']);
        $this->assertSame(1, $tahsinStat['tier_rows'][0]['count']);
        $this->assertSame(50.0, $tahsinStat['tier_rows'][0]['pct']);
        $this->assertSame(1, $tahsinStat['tier_rows'][1]['count']);
        $this->assertSame(50.0, $tahsinStat['tier_rows'][1]['pct']);
        $this->assertSame(0, $tahsinStat['tier_rows'][2]['count']);
        $this->assertCount(6, $tahsinStat['monthly_rows']);
        $this->assertCount(2, $tahsinStat['tingkat_rows']);
        $this->assertSame(['Jilid 2 • An-Naba (40 Ayat)'], $tahsinStat['tingkat_rows'][0]['target_labels']);
        $this->assertSame(1, $tahsinStat['tingkat_rows'][0]['achieved_count']);
        $this->assertSame(1, $tahsinStat['tingkat_rows'][0]['unachieved_count']);
        $this->assertSame(2, $tahsinStat['material_rows'][0]['unique_students_count']);
        $this->assertSame('89', $tahsinStat['material_rows'][0]['avg_formatted']);
        $this->assertSame('Jilid 2', $tahsinStat['material_rows'][1]['label']);
        $this->assertSame(1, $tahsinStat['material_rows'][1]['unique_students_count']);
        $this->assertSame(1, $tahsinStat['material_rows'][1]['latest_student_count']);
        $this->assertSame('70 – 90', $tahsinStat['student_rows'][0]['min_formatted'].' – '.$tahsinStat['student_rows'][0]['max_formatted']);

        // Evaluasi target Tahsin: Zaid (501) Tercapai, Umar (503) Belum Tercapai (50%), Bilal (502) Tanpa Target
        $this->assertSame(1, $tahsinStat['target_achievement']['achieved_count']);
        $this->assertSame(1, $tahsinStat['target_achievement']['unachieved_count']);
        $this->assertSame(1, $tahsinStat['target_achievement']['no_target_count']);
        $this->assertSame(50.0, $tahsinStat['target_achievement']['evaluated_achieved_pct']);
        $this->assertSame('tercapai', $tahsinStat['student_rows'][0]['target_status']);
        $this->assertSame('belum_tercapai', $tahsinStat['student_rows'][1]['target_status']);
        $this->assertSame(50, $tahsinStat['student_rows'][1]['target_progress_pct']);
        $this->assertSame('tanpa_target', $tahsinStat['student_rows'][2]['target_status']);
        $this->assertCount(1, $tahsinStat['target_achievement']['achieved_students']);
        $this->assertCount(1, $tahsinStat['target_achievement']['unachieved_students']);
        $this->assertSame(501, $tahsinStat['target_achievement']['achieved_students'][0]['anggota']->id);
        $this->assertSame(503, $tahsinStat['target_achievement']['unachieved_students'][0]['anggota']->id);

        // Evaluasi target Tahfizh: Zaid (501) hafal 2/2 surah = Tercapai, Umar (503) 1/2 surah = Belum Tercapai (50%)
        $this->assertSame('An-Naba', $tahfizhStat['material_rows'][0]['label']);
        $this->assertSame(1, $tahfizhStat['target_achievement']['achieved_count']);
        $this->assertSame(1, $tahfizhStat['target_achievement']['unachieved_count']);
        $this->assertSame(50.0, $tahfizhStat['target_achievement']['achieved_pct']);
        $this->assertSame(100, $tahfizhStat['target_achievement']['achieved_students'][0]['target_progress_pct']);
        $this->assertSame(50, $tahfizhStat['target_achievement']['unachieved_students'][0]['target_progress_pct']);
    }

    public function test_statistik_routes_controllers_and_views_render_cleanly(): void
    {
        $this->assertTrue(Route::has('t2q.yaumiyah-tahsin.statistik'));
        $this->assertTrue(Route::has('t2q.yaumiyah-tahfizh.statistik'));

        $taActive = TahunAjaran::make(['nama_tahun_ajaran' => '2026/2027', 'semester' => '1', 'is_active' => true]);
        $taActive->id = 1;
        $taPast = TahunAjaran::make(['nama_tahun_ajaran' => '2025/2026', 'semester' => '2', 'is_active' => false]);
        $taPast->id = 2;

        $guru = Karyawan::make(['nipy' => 'NIPY-T2Q', 'nama_lengkap' => 'Ustadz Ahmad', 'gelar' => 'S.Pd.I']);
        $siswa = Siswa::make(['nis' => '2026001', 'nama_lengkap' => 'Zaid Al-Khair']);
        $kelas = Kelas::make(['nama_kelas' => '1 Al-Fatihah']);
        $ak = AnggotaKelas::make(['siswa_nis' => '2026001']);
        $ak->setRelation('siswa', $siswa);
        $ak->setRelation('kelas', $kelas);
        $anggota = AnggotaT2q::make(['tingkat' => '1', 'karyawan_nipy' => 'NIPY-T2Q']);
        $anggota->id = 501;
        $anggota->setRelation('anggotaKelas', $ak);
        $anggota->setRelation('guruT2q', $guru);

        $jilid = DaftarJilid::make(['jilid_latin' => 'Jilid 2', 'jilid_arab' => '٢', 'jenis' => 'halaman', 'urutan' => 2]);
        $jilid->id = 2;
        $surah = Surah::make(['nama_surah' => 'An-Naba', 'nama_surah_arab' => 'النبأ', 'jumlah_ayat' => 40]);
        $surah->id = 78;
        $angka = AngkaArab::make(['angka_latin' => '15', 'angka_arab' => '١٥']);

        $tahsinEntry = YaumiyahTahsin::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-18', 'nilai' => 92]);
        $tahsinEntry->id = 11;
        $tahsinEntry->setRelation('daftarJilid', $jilid);
        $tahsinEntry->setRelation('angkaArab', $angka);

        $tahfizhEntry = YaumiyahTahfizh::make(['anggota_t2q_id' => 501, 'tanggal' => '2026-11-18', 'nilai' => 96]);
        $tahfizhEntry->id = 21;
        $tahfizhEntry->setRelation('surah', $surah);
        $tahfizhEntry->setRelation('angkaArab', $angka);

        $targetMock = TargetCapaian::make(['tingkat' => '1']);
        $targetMock->setRelation('daftarJilid', $jilid);
        $targetMock->setRelation('surah', $surah);

        $realTahsinService = new YaumiyahTahsinService;
        $realTahfizhService = new YaumiyahTahfizhService;
        $tahsinStats = (new ReflectionMethod($realTahsinService, 'buildAnnualStatistik'))
            ->invoke($realTahsinService, $taPast, new Collection([$anggota]), new Collection([$tahsinEntry]), collect(['1' => collect([$targetMock])]));
        $tahfizhStats = (new ReflectionMethod($realTahfizhService, 'buildAnnualStatistik'))
            ->invoke($realTahfizhService, $taActive, new Collection([$anggota]), new Collection([$tahfizhEntry]), collect(['1' => collect([$targetMock])]));

        $tahsinMock = $this->createMock(YaumiyahTahsinService::class);
        $tahsinMock->expects($this->once())
            ->method('getStatistikData')
            ->with(['tahun_ajaran_id' => '2', 'karyawan_nipy' => 'NIPY-T2Q'])
            ->willReturn([
                'mode' => 'tahsin',
                'usesDaftarJilid' => true,
                'tahunAjaranOptions' => new Collection([$taActive, $taPast]),
                'selectedTahunAjaran' => $taPast,
                'selectedTahunAjaranId' => 2,
                'guruOptions' => new Collection([$guru]),
                'selectedGuruNipy' => 'NIPY-T2Q',
                'tingkatOptions' => ['1' => 1],
                'selectedTingkat' => '',
                'stats' => $tahsinStats,
            ]);

        $tahfizhMock = $this->createMock(YaumiyahTahfizhService::class);
        $tahfizhMock->expects($this->once())
            ->method('getStatistikData')
            ->willReturn([
                'mode' => 'tahfizh',
                'usesDaftarJilid' => false,
                'tahunAjaranOptions' => new Collection([$taActive, $taPast]),
                'selectedTahunAjaran' => $taActive,
                'selectedTahunAjaranId' => 1,
                'guruOptions' => new Collection([$guru]),
                'selectedGuruNipy' => '',
                'tingkatOptions' => ['1' => 1],
                'selectedTingkat' => '',
                'stats' => $tahfizhStats,
            ]);

        $tahsinView = (new YaumiyahTahsinController($tahsinMock))->statistik(Request::create('/t2q/yaumiyah-tahsin/statistik', 'GET', [
            'tahun_ajaran_id' => '2',
            'karyawan_nipy' => 'NIPY-T2Q',
        ]));
        $tahfizhView = (new YaumiyahTahfizhController($tahfizhMock))->statistik(Request::create('/t2q/yaumiyah-tahfizh/statistik', 'GET'));

        Model::preventLazyLoading(true);
        try {
            $tahsinHtml = $tahsinView->with('errors', new ViewErrorBag)->render();
            $tahfizhHtml = $tahfizhView->with('errors', new ViewErrorBag)->render();
            $sidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $normalizedTahsinHtml = preg_replace('/\s+/', ' ', $tahsinHtml) ?: $tahsinHtml;
        $this->assertStringContainsString('Statistik Yaumiyah Tahsin', $tahsinHtml);
        $this->assertStringContainsString('Distribusi Nilai Siswa &amp; Jilid Bacaan', $tahsinHtml);
        $this->assertStringContainsString('Dihitung berdasarkan rata-rata nilai per siswa', $tahsinHtml);
        $this->assertStringContainsString('Diagram Pencapaian Target', $tahsinHtml);
        $this->assertStringContainsString('Grafik Pencapaian Target per Tingkat', $tahsinHtml);
        $this->assertStringContainsString('Daftar Pencapaian Target Siswa (Siapa Saja)', $tahsinHtml);
        $this->assertStringContainsString('Sudah Mencapai Target', $tahsinHtml);
        $this->assertStringContainsString('Belum Mencapai Target', $tahsinHtml);
        $this->assertStringContainsString('t2q-st-donut-svg', $tahsinHtml);
        $this->assertStringContainsString('Zaid Al-Khair', $tahsinHtml);
        $this->assertStringContainsString('2025/2026 - Semester 2', $tahsinHtml);
        $this->assertStringContainsString('value="'.sqidParam('tahun_ajaran_id', 2).'" selected> 2025/2026 - Semester 2', $normalizedTahsinHtml);
        $this->assertStringContainsString('Jilid 2', $tahsinHtml);
        $this->assertStringContainsString('92', $tahsinHtml);

        $this->assertStringContainsString('Statistik Yaumiyah Tahfizh', $tahfizhHtml);
        $this->assertStringContainsString('Diagram Pencapaian Target', $tahfizhHtml);
        $this->assertStringContainsString('Surah Paling Sering Disetor', $tahfizhHtml);
        $this->assertStringContainsString('An-Naba', $tahfizhHtml);
        $this->assertStringContainsString('96', $tahfizhHtml);

        $this->assertStringContainsString(route('t2q.yaumiyah-tahsin.statistik'), $sidebarHtml);
        $this->assertStringContainsString('Statistik Tahun Ajaran', $sidebarHtml);
    }
}
