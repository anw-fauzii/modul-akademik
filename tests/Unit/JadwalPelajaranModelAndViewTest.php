<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Pelajaran\JadwalPelajaranController;
use App\Http\Requests\Admin\Pelajaran\JadwalPelajaranRequest;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\JadwalPelajaran;
use App\Models\Admin\Pelajaran\JamPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Services\Admin\Pelajaran\JadwalPelajaranService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Validation\Rules\RequiredIf;
use Illuminate\Validation\Rules\Unique;
use PHPUnit\Framework\Attributes\TestWith;
use ReflectionMethod;
use Tests\TestCase;

class JadwalPelajaranModelAndViewTest extends TestCase
{
    public function test_model_metadata_fillable_casts_hari_options_and_relations_are_configured(): void
    {
        $jadwal = new JadwalPelajaran;

        $this->assertSame('akademik_pelajaran_jadwal_pelajaran', $jadwal->getTable());
        $this->assertSame([
            'akademik_master_tahun_ajaran_id',
            'akademik_master_kelas_id',
            'akademik_pelajaran_jam_pelajaran_id',
            'akademik_pelajaran_mata_pelajaran_id',
            'karyawan_nipy',
            'hari',
            'ruang',
            'keterangan',
            'status',
        ], $jadwal->getFillable());
        $this->assertTrue($jadwal->status);
        $this->assertSame([
            1 => 'Senin',
            2 => 'Selasa',
            3 => 'Rabu',
            4 => 'Kamis',
            5 => 'Jumat',
        ], JadwalPelajaran::HARI_OPTIONS);

        $casts = $jadwal->getCasts();
        $this->assertSame('integer', $casts['akademik_master_tahun_ajaran_id']);
        $this->assertSame('integer', $casts['akademik_master_kelas_id']);
        $this->assertSame('integer', $casts['akademik_pelajaran_jam_pelajaran_id']);
        $this->assertSame('integer', $casts['akademik_pelajaran_mata_pelajaran_id']);
        $this->assertSame('integer', $casts['hari']);
        $this->assertSame('boolean', $casts['status']);

        $this->assertSame('akademik_master_tahun_ajaran_id', $jadwal->tahunAjaran()->getForeignKeyName());
        $this->assertSame('akademik_master_kelas_id', $jadwal->kelas()->getForeignKeyName());
        $this->assertSame('akademik_pelajaran_jam_pelajaran_id', $jadwal->jamPelajaran()->getForeignKeyName());
        $this->assertSame('akademik_pelajaran_mata_pelajaran_id', $jadwal->mataPelajaran()->getForeignKeyName());
        $this->assertSame('karyawan_nipy', $jadwal->guru()->getForeignKeyName());
        $this->assertSame('nipy', $jadwal->guru()->getOwnerKeyName());

        $this->assertSame('akademik_pelajaran_jam_pelajaran_id', (new JamPelajaran)->jadwalPelajaran()->getForeignKeyName());
        $this->assertSame('akademik_pelajaran_mata_pelajaran_id', (new MataPelajaran)->jadwalPelajaran()->getForeignKeyName());
    }

    #[TestWith([1, 'Senin', 'badge-primary', true, 'Aktif', 'badge-success'])]
    #[TestWith([5, 'Jumat', 'badge-dark', false, 'Nonaktif', 'badge-secondary'])]
    public function test_accessors_format_hari_status_and_non_mapel_safely_without_lazy_loading(
        int $hari,
        string $expectedHariLabel,
        string $expectedHariBadge,
        bool $status,
        string $expectedStatusLabel,
        string $expectedStatusBadge
    ): void {
        Model::preventLazyLoading(true);

        try {
            $nonMapel = JadwalPelajaran::make([
                'hari' => $hari,
                'akademik_pelajaran_mata_pelajaran_id' => null,
                'keterangan' => 'Istirahat & Makan Siang',
                'status' => $status,
            ]);

            $this->assertSame($expectedHariLabel, $nonMapel->hari_label);
            $this->assertSame($expectedHariBadge, $nonMapel->hari_badge_class);
            $this->assertTrue($nonMapel->is_non_mapel);
            $this->assertSame('Istirahat & Makan Siang', $nonMapel->judul_tampil);
            $this->assertSame($expectedStatusLabel, $nonMapel->status_label);
            $this->assertSame($expectedStatusBadge, $nonMapel->status_badge_class);

            $mapel = MataPelajaran::make([
                'singkatan' => 'MTK',
                'nama_mata_pelajaran' => 'Matematika',
                'unit' => 'SD',
            ]);
            $mapel->id = 10;

            $slotMapel = JadwalPelajaran::make([
                'hari' => $hari,
                'akademik_pelajaran_mata_pelajaran_id' => 10,
                'status' => $status,
            ]);
            $slotMapel->setRelation('mataPelajaran', $mapel);

            $this->assertFalse($slotMapel->is_non_mapel);
            $this->assertSame('Matematika', $slotMapel->judul_tampil);
        } finally {
            Model::preventLazyLoading(false);
        }
    }

    #[TestWith(['07:30', '08:00', '07:35', '08:10', true])]
    #[TestWith(['07:00', '07:35', '07:35', '08:10', false])]
    #[TestWith(['08:10', '08:45', '07:35', '08:10', false])]
    #[TestWith(['07:30', '08:30', '07:45', '08:00', true])]
    public function test_time_overlap_helper_detects_exact_overlapping_and_back_to_back_windows(
        string $startA,
        string $endA,
        string $startB,
        string $endB,
        bool $expected
    ): void {
        $this->assertSame($expected, JadwalPelajaranService::hasTimeOverlap($startA, $endA, $startB, $endB));
    }

    public function test_service_index_query_selects_minimal_columns_and_eager_loads_all_relations(): void
    {
        $service = new JadwalPelajaranService;
        $builder = $service->buildIndexQuery([
            'tahun_ajaran_id' => '3',
            'unit' => 'sd',
            'kelas_id' => '9',
            'hari' => '2',
            'status' => '1',
            'search' => '100%_Istirahat',
        ]);

        $query = $builder->getQuery();
        $this->assertSame([
            'id',
            'akademik_master_tahun_ajaran_id',
            'akademik_master_kelas_id',
            'akademik_pelajaran_jam_pelajaran_id',
            'akademik_pelajaran_mata_pelajaran_id',
            'karyawan_nipy',
            'hari',
            'ruang',
            'keterangan',
            'status',
            'created_at',
        ], $query->columns);

        $sql = strtolower($builder->toSql());
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $sql);
        $this->assertStringContainsString('akademik_master_kelas_id', $sql);
        $this->assertStringContainsString('hari', $sql);
        $this->assertStringContainsString('status', $sql);
        $this->assertStringContainsString('order by', $sql);

        $eagerLoads = $builder->getEagerLoads();
        $this->assertArrayHasKey('tahunAjaran', $eagerLoads);
        $this->assertArrayHasKey('kelas', $eagerLoads);
        $this->assertArrayHasKey('jamPelajaran', $eagerLoads);
        $this->assertArrayHasKey('mataPelajaran', $eagerLoads);
        $this->assertArrayHasKey('guru', $eagerLoads);
    }

    public function test_request_normalizes_nullable_fields_and_requires_keterangan_for_non_mapel(): void
    {
        $request = JadwalPelajaranRequest::create('/pelajaran/jadwal-pelajaran', 'POST', [
            'akademik_master_tahun_ajaran_id' => ' 1 ',
            'akademik_master_kelas_id' => ' 4 ',
            'akademik_pelajaran_jam_pelajaran_id' => ' 2 ',
            'akademik_pelajaran_mata_pelajaran_id' => '   ',
            'karyawan_nipy' => '',
            'hari' => ' 5 ',
            'ruang' => '  Lapangan   Utama ',
            'keterangan' => '  Upacara   Bendera  ',
            'status' => '0',
        ]);

        $prepareMethod = new ReflectionMethod(JadwalPelajaranRequest::class, 'prepareForValidation');
        $prepareMethod->invoke($request);

        $this->assertSame('1', $request->input('akademik_master_tahun_ajaran_id'));
        $this->assertSame('4', $request->input('akademik_master_kelas_id'));
        $this->assertSame('2', $request->input('akademik_pelajaran_jam_pelajaran_id'));
        $this->assertNull($request->input('akademik_pelajaran_mata_pelajaran_id'));
        $this->assertNull($request->input('karyawan_nipy'));
        $this->assertSame('5', $request->input('hari'));
        $this->assertSame('Lapangan Utama', $request->input('ruang'));
        $this->assertSame('Upacara Bendera', $request->input('keterangan'));
        $this->assertFalse($request->input('status'));

        $rules = $request->rules();
        $hasUniqueClassSlot = false;
        foreach ($rules['akademik_pelajaran_jam_pelajaran_id'] as $rule) {
            if ($rule instanceof Unique) {
                $hasUniqueClassSlot = true;
            }
        }
        $this->assertTrue($hasUniqueClassSlot);

        $hasRequiredIfKeterangan = false;
        foreach ($rules['keterangan'] as $rule) {
            if ($rule instanceof RequiredIf) {
                $hasRequiredIfKeterangan = true;
            }
        }
        $this->assertTrue($hasRequiredIfKeterangan);
    }

    public function test_thin_controller_delegates_crud_and_toggle_status_to_service(): void
    {
        $ta = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $ta->id = 1;

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '1',
        ]);
        $kelas->id = 2;

        $jam = JamPelajaran::make([
            'jam_ke' => 1,
            'unit' => 'SD',
            'jam_mulai' => '07:00',
            'jam_selesai' => '07:35',
        ]);
        $jam->id = 3;

        $jadwal = JadwalPelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_master_kelas_id' => 2,
            'akademik_pelajaran_jam_pelajaran_id' => 3,
            'akademik_pelajaran_mata_pelajaran_id' => null,
            'karyawan_nipy' => null,
            'hari' => 1,
            'keterangan' => 'Pembiasaan Pagi',
            'status' => true,
        ]);
        $jadwal->id = 15;

        $service = $this->createMock(JadwalPelajaranService::class);
        $service->expects($this->once())
            ->method('getIndexData')
            ->willReturn([
                'jadwalPelajaran' => new Collection([$jadwal]),
                'summary' => ['total' => 1, 'aktif' => 1, 'nonaktif' => 0, 'per_hari' => [1 => 1, 2 => 0, 3 => 0, 4 => 0, 5 => 0]],
                'filters' => ['tahun_ajaran_id' => '1', 'unit' => '', 'kelas_id' => '', 'hari' => '', 'status' => 'all', 'search' => ''],
                'tahunAjaranOptions' => new Collection([$ta]),
                'kelasOptions' => new Collection([$kelas]),
                'unitOptions' => JamPelajaran::UNIT_OPTIONS,
                'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
                'selectedKelas' => null,
                'weeklyMatrix' => null,
            ]);
        $service->expects($this->exactly(2))
            ->method('getFormOptions')
            ->willReturn([
                'tahunAjaranOptions' => new Collection([$ta]),
                'kelasOptions' => new Collection([$kelas]),
                'jamPelajaranOptions' => new Collection([$jam]),
                'mataPelajaranOptions' => new Collection,
                'guruOptions' => new Collection,
                'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
                'nonMapelPresets' => JadwalPelajaran::NON_MAPEL_PRESETS,
                'defaults' => ['tahun_ajaran_id' => '1', 'kelas_id' => '', 'jam_pelajaran_id' => '', 'hari' => ''],
            ]);
        $service->expects($this->once())->method('store')->willReturn($jadwal);
        $service->expects($this->once())->method('update')->with($jadwal, $this->isArray())->willReturn(true);
        $service->expects($this->once())->method('delete')->with($jadwal)->willReturn(true);
        $service->expects($this->once())->method('toggleStatus')->with($jadwal)->willReturn(true);

        $controller = new JadwalPelajaranController($service);

        $this->assertSame('admin.pelajaran.jadwalPelajaran.index', $controller->index(Request::create('/pelajaran/jadwal-pelajaran', 'GET'))->name());
        $this->assertSame('admin.pelajaran.jadwalPelajaran.create', $controller->create(Request::create('/pelajaran/jadwal-pelajaran/create', 'GET'))->name());
        $this->assertSame('admin.pelajaran.jadwalPelajaran.edit', $controller->edit($jadwal)->name());

        $formRequest = JadwalPelajaranRequest::create('/pelajaran/jadwal-pelajaran', 'POST', [
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_master_kelas_id' => 2,
            'akademik_pelajaran_jam_pelajaran_id' => 3,
            'hari' => 1,
            'keterangan' => 'Pembiasaan Pagi',
            'status' => true,
        ]);
        $validator = app('validator')->make($formRequest->all(), [
            'akademik_master_tahun_ajaran_id' => 'required|integer',
            'akademik_master_kelas_id' => 'required|integer',
            'akademik_pelajaran_jam_pelajaran_id' => 'required|integer',
            'hari' => 'required|integer',
            'keterangan' => 'nullable|string',
            'status' => 'required|boolean',
        ]);
        $validator->validate();
        $formRequest->setValidator($validator);

        $this->assertStringContainsString(
            route('jadwal-pelajaran.index'),
            $controller->store($formRequest)->getTargetUrl()
        );
        $this->assertStringContainsString(
            route('jadwal-pelajaran.index'),
            $controller->update($formRequest, $jadwal)->getTargetUrl()
        );
        $this->assertTrue($controller->destroy($jadwal)->isRedirect(route('jadwal-pelajaran.index')));
        $this->assertTrue($controller->toggleStatus($jadwal)->isRedirect(route('jadwal-pelajaran.index')));
    }

    public function test_index_matrix_and_form_views_render_without_lazy_loading(): void
    {
        view()->share('errors', new ViewErrorBag);

        $ta = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $ta->id = 1;

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => '1',
        ]);
        $kelas->id = 2;

        $jam = JamPelajaran::make([
            'jam_ke' => 1,
            'unit' => 'SD',
            'jam_mulai' => '07:00',
            'jam_selesai' => '07:35',
        ]);
        $jam->id = 3;

        $mapel = MataPelajaran::make([
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'unit' => 'SD',
            'status' => true,
        ]);
        $mapel->id = 4;

        $guru = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $jadwalMapel = JadwalPelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_master_kelas_id' => 2,
            'akademik_pelajaran_jam_pelajaran_id' => 3,
            'akademik_pelajaran_mata_pelajaran_id' => 4,
            'karyawan_nipy' => '19900101-1',
            'hari' => 1,
            'ruang' => 'R. 1A',
            'status' => true,
        ]);
        $jadwalMapel->id = 101;
        $jadwalMapel->setRelation('tahunAjaran', $ta);
        $jadwalMapel->setRelation('kelas', $kelas);
        $jadwalMapel->setRelation('jamPelajaran', $jam);
        $jadwalMapel->setRelation('mataPelajaran', $mapel);
        $jadwalMapel->setRelation('guru', $guru);

        $jadwalNonMapel = JadwalPelajaran::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'akademik_master_kelas_id' => 2,
            'akademik_pelajaran_jam_pelajaran_id' => 3,
            'akademik_pelajaran_mata_pelajaran_id' => null,
            'karyawan_nipy' => null,
            'hari' => 2,
            'keterangan' => 'Istirahat Pertama',
            'status' => true,
        ]);
        $jadwalNonMapel->id = 102;
        $jadwalNonMapel->setRelation('tahunAjaran', $ta);
        $jadwalNonMapel->setRelation('kelas', $kelas);
        $jadwalNonMapel->setRelation('jamPelajaran', $jam);
        $jadwalNonMapel->setRelation('mataPelajaran', null);
        $jadwalNonMapel->setRelation('guru', null);

        Model::preventLazyLoading(true);

        try {
            $indexHtml = view('admin.pelajaran.jadwalPelajaran.index', [
                'jadwalPelajaran' => new Collection([$jadwalMapel, $jadwalNonMapel]),
                'summary' => ['total' => 2, 'aktif' => 2, 'nonaktif' => 0, 'per_hari' => [1 => 1, 2 => 1, 3 => 0, 4 => 0, 5 => 0]],
                'filters' => ['tahun_ajaran_id' => '1', 'unit' => '', 'kelas_id' => '2', 'hari' => '', 'status' => 'all', 'search' => ''],
                'tahunAjaranOptions' => new Collection([$ta]),
                'kelasOptions' => new Collection([$kelas]),
                'unitOptions' => JamPelajaran::UNIT_OPTIONS,
                'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
                'selectedKelas' => $kelas,
                'weeklyMatrix' => [
                    'jamSlots' => new Collection([$jam]),
                    'grid' => [
                        3 => [
                            1 => $jadwalMapel,
                            2 => $jadwalNonMapel,
                        ],
                    ],
                ],
            ])->render();

            $createHtml = view('admin.pelajaran.jadwalPelajaran.create', [
                'tahunAjaranOptions' => new Collection([$ta]),
                'kelasOptions' => new Collection([$kelas]),
                'jamPelajaranOptions' => new Collection([$jam]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'guruOptions' => new Collection([$guru]),
                'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
                'nonMapelPresets' => JadwalPelajaran::NON_MAPEL_PRESETS,
                'defaults' => ['tahun_ajaran_id' => '1', 'kelas_id' => '2', 'jam_pelajaran_id' => '3', 'hari' => '1'],
            ])->render();

            $editHtml = view('admin.pelajaran.jadwalPelajaran.edit', [
                'jadwalPelajaran' => $jadwalMapel,
                'tahunAjaranOptions' => new Collection([$ta]),
                'kelasOptions' => new Collection([$kelas]),
                'jamPelajaranOptions' => new Collection([$jam]),
                'mataPelajaranOptions' => new Collection([$mapel]),
                'guruOptions' => new Collection([$guru]),
                'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
                'nonMapelPresets' => JadwalPelajaran::NON_MAPEL_PRESETS,
                'defaults' => ['tahun_ajaran_id' => '', 'kelas_id' => '', 'jam_pelajaran_id' => '', 'hari' => ''],
            ])->render();

            $classPickerHtml = view('admin.pelajaran.jadwalPelajaran.index', [
                'jadwalPelajaran' => new Collection([$jadwalMapel]),
                'summary' => ['total' => 1, 'aktif' => 1, 'nonaktif' => 0, 'per_hari' => [1 => 1, 2 => 0, 3 => 0, 4 => 0, 5 => 0]],
                'filters' => ['tahun_ajaran_id' => '1', 'unit' => '', 'kelas_id' => '', 'hari' => '', 'status' => 'all', 'search' => ''],
                'tahunAjaranOptions' => new Collection([$ta]),
                'kelasOptions' => new Collection([$kelas]),
                'unitOptions' => JamPelajaran::UNIT_OPTIONS,
                'hariOptions' => JadwalPelajaran::HARI_OPTIONS,
                'selectedKelas' => null,
                'weeklyMatrix' => null,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Matriks Jadwal Mingguan', $indexHtml);
        $this->assertStringContainsString('Matematika', $indexHtml);
        $this->assertStringContainsString('Istirahat Pertama', $indexHtml);
        $this->assertStringContainsString('Budi Santoso, S.Pd', $indexHtml);
        $this->assertStringContainsString('Isi Slot', $indexHtml);
        $this->assertStringContainsString('jp-matrix-table', $indexHtml);
        $this->assertStringContainsString(route('jadwal-pelajaran.toggle-status', 101), $indexHtml);
        $this->assertStringContainsString(route('jadwal-pelajaran.edit', 101), $indexHtml);
        $this->assertStringContainsString(route('jadwal-pelajaran.destroy', 101), $indexHtml);
        $this->assertStringContainsString('Pilih kelas untuk menampilkan tampilan grid Senin', $classPickerHtml);
        $this->assertStringContainsString('1A', $classPickerHtml);

        $this->assertStringContainsString(route('jadwal-pelajaran.store'), $createHtml);
        $this->assertStringContainsString('name="akademik_master_tahun_ajaran_id"', $createHtml);
        $this->assertStringContainsString('name="akademik_master_kelas_id"', $createHtml);
        $this->assertStringContainsString('name="akademik_pelajaran_jam_pelajaran_id"', $createHtml);
        $this->assertStringContainsString('name="akademik_pelajaran_mata_pelajaran_id"', $createHtml);
        $this->assertStringContainsString('name="karyawan_nipy"', $createHtml);
        $this->assertStringContainsString('name="hari"', $createHtml);
        $this->assertStringContainsString('Istirahat', $createHtml);

        $this->assertStringContainsString(route('jadwal-pelajaran.update', 101), $editHtml);
        $this->assertStringContainsString('R. 1A', $editHtml);
        $this->assertStringContainsString(route('jadwal-pelajaran.index'), view('layouts.sidebar')->render());
    }
}
