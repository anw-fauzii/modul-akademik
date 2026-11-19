<?php

namespace Tests\Feature;

use App\Exports\Admin\TujuanPembelajaran\TujuanPembelajaranExport;
use App\Exports\Admin\TujuanPembelajaran\TujuanPembelajaranTemplateExport;
use App\Imports\Admin\TujuanPembelajaran\TujuanPembelajaranImport;
use App\Imports\Admin\TujuanPembelajaran\TujuanPembelajaranSheetImport;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\KelompokPelajaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class TujuanPembelajaranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['auth.host' => 'https://sso.test']);
        Http::preventStrayRequests();
    }

    private function authenticateUser(): User
    {
        Http::fake([
            'https://sso.test/api/user' => Http::response([
                'name' => 'Admin Akademik',
                'email' => 'admin@primainsani.sch.id',
            ], 200),
        ]);

        return User::create([
            'name' => 'Admin Akademik',
            'email' => 'admin@primainsani.sch.id',
        ]);
    }

    public function test_index_loads_tujuan_pembelajaran_without_n_plus_one(): void
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
            'singkatan' => 'MTK',
            'nama_mata_pelajaran' => 'Matematika',
            'unit' => 'SD',
        ]);

        foreach (range(1, 5) as $idx) {
            TujuanPembelajaran::create([
                'akademik_master_tahun_ajaran_id' => $ta->id,
                'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
                'tingkatan_kelas' => '1',
                'fase' => 'A',
                'elemen' => 'Bilangan',
                'kode_tp' => "MTK.1.{$idx}",
                'tujuan_pembelajaran' => "Tujuan ke-{$idx}",
                'lingkup_materi' => "Materi {$idx}",
                'urutan' => $idx,
                'status' => true,
            ]);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->actingAs($user)->get(route('tujuan-pembelajaran.index', [
            'tahun_ajaran_id' => $ta->sqid,
        ]));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $response->assertOk();
        $response->assertSeeText('MTK.1.1');
        $response->assertSeeText('Tujuan ke-5');
        $this->assertLessThanOrEqual(10, count($queries));
    }

    public function test_crud_and_semester_scoped_uniqueness_work_properly(): void
    {
        $user = $this->authenticateUser();

        $taGanjil = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $taGenap = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '2',
            'is_active' => false,
        ]);

        $kelompok = KelompokPelajaran::factory()->create();
        $mapel = MataPelajaran::factory()->create([
            'akademik_pelajaran_kelompok_pelajaran_id' => $kelompok->id,
            'singkatan' => 'BIN',
            'nama_mata_pelajaran' => 'Bahasa Indonesia',
            'unit' => 'SD',
        ]);

        // Store TP Semester 1 (fase auto-resolved to A)
        $this->actingAs($user)->post(route('tujuan-pembelajaran.store'), [
            'akademik_master_tahun_ajaran_id' => $taGanjil->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
            'tingkatan_kelas' => '1',
            'elemen' => 'Menyimak',
            'kode_tp' => 'tp-01',
            'tujuan_pembelajaran' => 'Siswa menyimak teks cerita sederhana.',
            'lingkup_materi' => 'Teks Cerita',
            'urutan' => 1,
            'status' => 1,
        ])->assertRedirect();

        $tpGanjil = TujuanPembelajaran::firstOrFail();
        $this->assertSame('TP-01', $tpGanjil->kode_tp);
        $this->assertSame('A', $tpGanjil->fase);

        // Duplicate kode_tp in same semester, mapel, tingkatan should fail
        $this->actingAs($user)->post(route('tujuan-pembelajaran.store'), [
            'akademik_master_tahun_ajaran_id' => $taGanjil->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
            'tingkatan_kelas' => '1',
            'kode_tp' => 'TP-01',
            'tujuan_pembelajaran' => 'Duplikat TP.',
            'urutan' => 2,
            'status' => 1,
        ])->assertSessionHasErrors('kode_tp');

        // Same kode_tp in Semester 2 is allowed
        $this->actingAs($user)->post(route('tujuan-pembelajaran.store'), [
            'akademik_master_tahun_ajaran_id' => $taGenap->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
            'tingkatan_kelas' => '1',
            'kode_tp' => 'TP-01',
            'tujuan_pembelajaran' => 'Siswa menulis kalimat sederhana semester genap.',
            'urutan' => 1,
            'status' => 1,
        ])->assertRedirect();

        $this->assertSame(2, TujuanPembelajaran::count());

        // Toggle status & Delete
        $this->actingAs($user)->post(route('tujuan-pembelajaran.toggle-status', $tpGanjil))
            ->assertRedirect(route('tujuan-pembelajaran.index'));
        $this->assertFalse($tpGanjil->fresh()->status);

        $this->actingAs($user)->delete(route('tujuan-pembelajaran.destroy', $tpGanjil))
            ->assertRedirect(route('tujuan-pembelajaran.index'));
        $this->assertDatabaseMissing('akademik_pelajaran_tujuan_pembelajaran', ['id' => $tpGanjil->id]);
    }

    public function test_sheet_import_batch_upserts_without_n_plus_1_and_rolls_back_on_validation_errors(): void
    {
        $ta = TahunAjaran::create([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'tanggal_mulai' => '2026-07-13',
            'tanggal_selesai' => '2026-12-18',
            'is_active' => true,
        ]);

        $kelompok = KelompokPelajaran::create([
            'kode_kelompok' => 'UMUM',
            'nama_kelompok' => 'Mata Pelajaran Umum',
            'unit' => 'SD',
            'urutan' => 1,
            'status' => true,
        ]);

        $mapel = MataPelajaran::create([
            'kelompok_pelajaran_id' => $kelompok->sqid,
            'unit' => 'SD',
            'nama_mata_pelajaran' => 'Matematika',
            'singkatan' => 'MTK',
            'status' => true,
        ]);

        TujuanPembelajaran::create([
            'akademik_master_tahun_ajaran_id' => $ta->id,
            'akademik_pelajaran_mata_pelajaran_id' => $mapel->id,
            'tingkatan_kelas' => '1',
            'fase' => 'A',
            'elemen' => 'Bilangan',
            'kode_tp' => 'MTK.1.1',
            'tujuan_pembelajaran' => 'Versi lama sebelum import.',
            'lingkup_materi' => 'Bilangan Lama',
            'urutan' => 1,
            'status' => true,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $sheetImport = new TujuanPembelajaranSheetImport;
        $sheetImport->collection(new Collection([
            [
                'tahun_ajaran' => '2026/2027',
                'semester' => '1',
                'unit' => 'SD',
                'kode_mapel' => 'MTK',
                'mata_pelajaran' => 'Matematika',
                'tingkatan_kelas' => '1',
                'fase' => 'A',
                'elemen' => 'Bilangan',
                'kode_tp' => 'MTK.1.1',
                'tujuan_pembelajaran' => 'Peserta didik mampu membilang cacah hingga 20.',
                'lingkup_materi' => 'Bilangan Cacah 1-20',
                'urutan' => 1,
                'status' => 'Aktif',
            ],
            [
                'tahun_ajaran' => '2026/2027',
                'semester' => '1',
                'unit' => 'SD',
                'kode_mapel' => 'MTK',
                'mata_pelajaran' => 'Matematika',
                'tingkatan_kelas' => '1',
                'fase' => 'A',
                'elemen' => 'Aljabar',
                'kode_tp' => 'MTK.1.2',
                'tujuan_pembelajaran' => 'Peserta didik mengenal pola bilangan sederhana.',
                'lingkup_materi' => 'Pola Bilangan',
                'urutan' => 2,
                'status' => 'Aktif',
            ],
        ]));

        $queryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertSame([], $sheetImport->errors);
        $this->assertSame(1, $sheetImport->createdCount);
        $this->assertSame(1, $sheetImport->updatedCount);
        $this->assertLessThanOrEqual(8, $queryCount);

        $this->assertDatabaseHas('akademik_pelajaran_tujuan_pembelajaran', [
            'kode_tp' => 'MTK.1.1',
            'tujuan_pembelajaran' => 'Peserta didik mampu membilang cacah hingga 20.',
        ]);
        $this->assertDatabaseHas('akademik_pelajaran_tujuan_pembelajaran', [
            'kode_tp' => 'MTK.1.2',
            'elemen' => 'Aljabar',
        ]);

        // Baris invalid tidak boleh melakukan partial write
        $invalidImport = new TujuanPembelajaranSheetImport;
        $invalidImport->collection(new Collection([
            [
                'tahun_ajaran' => '2099/2100',
                'semester' => '1',
                'unit' => 'SD',
                'kode_mapel' => 'MTK',
                'tingkatan_kelas' => '1',
                'kode_tp' => 'MTK.1.3',
                'tujuan_pembelajaran' => 'Harusnya gagal karena TA tidak ada.',
            ],
        ]));

        $this->assertNotEmpty($invalidImport->errors);
        $this->assertDatabaseMissing('akademik_pelajaran_tujuan_pembelajaran', [
            'kode_tp' => 'MTK.1.3',
        ]);
    }

    public function test_export_template_and_import_endpoints_work_with_excel_facade(): void
    {
        Http::fake(['*/api/user*' => Http::response([], 200)]);
        Excel::fake();

        $user = User::factory()->create();

        $this->actingAs($user)->get(route('tujuan-pembelajaran.export', ['unit' => 'SD']))
            ->assertOk();
        Excel::assertDownloaded(
            fn (string $filename): bool => str_starts_with($filename, 'tujuan-pembelajaran-'),
            fn ($export): bool => $export instanceof TujuanPembelajaranExport
        );

        $this->actingAs($user)->get(route('tujuan-pembelajaran.template'))
            ->assertOk();
        Excel::assertDownloaded(
            'template-import-tujuan-pembelajaran.xlsx',
            fn ($export): bool => $export instanceof TujuanPembelajaranTemplateExport
        );

        $file = UploadedFile::fake()->create(
            'tp-import.xlsx',
            12,
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        );

        $this->actingAs($user)->post(route('tujuan-pembelajaran.import'), [
            'file' => $file,
        ])->assertRedirect(route('tujuan-pembelajaran.index'));

        Excel::assertImported(
            'tp-import.xlsx',
            fn ($import): bool => $import instanceof TujuanPembelajaranImport
        );
    }
}
