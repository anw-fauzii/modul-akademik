<?php

namespace Tests\Unit;

use App\Models\Admin\DataMaster\Ekstrakurikuler;
use App\Models\Admin\DataMaster\Karyawan;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ViewErrorBag;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class EkstrakurikulerModelAndViewTest extends TestCase
{
    #[TestWith([0, 'Gratis'])]
    #[TestWith([-500, 'Gratis'])]
    #[TestWith([50000, 'Rp 50.000'])]
    #[TestWith([1250000, 'Rp 1.250.000'])]
    public function test_biaya_formatted_formats_zero_and_positive_amounts(int $biaya, string $expected): void
    {
        $ekstrakurikuler = Ekstrakurikuler::make(['biaya' => $biaya]);

        $this->assertSame($expected, $ekstrakurikuler->biaya_formatted);
    }

    #[TestWith(['SD', 'badge-success'])]
    #[TestWith(['smp', 'badge-primary'])]
    #[TestWith(['TK', 'badge-warning text-dark'])]
    #[TestWith(['PAUD', 'badge-warning text-dark'])]
    #[TestWith(['SMA', 'badge-info'])]
    #[TestWith(['SMK', 'badge-info'])]
    #[TestWith(['Yayasan', 'badge-secondary'])]
    public function test_unit_badge_class_maps_unit_names(string $unit, string $expectedClass): void
    {
        $ekstrakurikuler = Ekstrakurikuler::make(['unit' => $unit]);

        $this->assertSame($expectedClass, $ekstrakurikuler->unit_badge_class);
    }

    public function test_index_view_renders_preloaded_relations_without_lazy_loading(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);

        $pembina = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Budi Santoso',
            'gelar' => 'S.Pd',
        ]);

        $paid = Ekstrakurikuler::make([
            'nama_ekstrakurikuler' => 'Panahan',
            'unit' => 'SD',
            'biaya' => 75000,
        ]);
        $paid->id = 1;
        $paid->setRelation('tahunAjaran', $tahunAjaran);
        $paid->setRelation('pembina', $pembina);

        $free = Ekstrakurikuler::make([
            'nama_ekstrakurikuler' => 'Pramuka',
            'unit' => 'SMP',
            'biaya' => 0,
        ]);
        $free->id = 2;
        $free->setRelation('tahunAjaran', $tahunAjaran);
        $free->setRelation('pembina', null);

        Model::preventLazyLoading(true);

        try {
            $rendered = view('admin.dataMaster.ekstrakurikuler.index', [
                'ekstrakurikuler' => new Collection([$paid, $free]),
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Panahan', $rendered);
        $this->assertStringContainsString('Budi Santoso, S.Pd', $rendered);
        $this->assertStringContainsString('Rp 75.000', $rendered);
        $this->assertStringContainsString('Pramuka', $rendered);
        $this->assertStringContainsString('Gratis', $rendered);
        $this->assertStringContainsString('Pembina belum ditentukan', $rendered);
        $this->assertStringContainsString(route('ekstrakurikuler.index'), view('layouts.sidebar')->render());
    }

    public function test_index_view_does_not_render_colspan_row_when_empty_for_datatables_compatibility(): void
    {
        $rendered = view('admin.dataMaster.ekstrakurikuler.index', [
            'ekstrakurikuler' => new Collection,
        ])->render();

        $this->assertStringNotContainsString('colspan="7"', $rendered);
        $this->assertStringContainsString('Belum ada data tersedia', view('layouts.appui')->render());
    }

    public function test_create_and_edit_views_render_expected_form_fields_and_actions(): void
    {
        view()->share('errors', new ViewErrorBag);

        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 10;

        $pembina = Karyawan::make([
            'nipy' => '19900101-1',
            'nama_lengkap' => 'Siti Aminah',
            'gelar' => 'M.Pd',
        ]);

        $createHtml = view('admin.dataMaster.ekstrakurikuler.create', [
            'tahunAjaran' => new Collection([$tahunAjaran]),
            'karyawan' => new Collection([$pembina]),
        ])->render();

        $this->assertStringContainsString(route('ekstrakurikuler.store'), $createHtml);
        $this->assertStringContainsString('Siti Aminah, M.Pd (19900101-1)', $createHtml);

        $ekstrakurikuler = Ekstrakurikuler::make([
            'akademik_master_tahun_ajaran_id' => 10,
            'karyawan_nipy' => '19900101-1',
            'nama_ekstrakurikuler' => 'Robotik',
            'biaya' => 120000,
            'unit' => 'SMP',
        ]);
        $ekstrakurikuler->id = 5;

        $editHtml = view('admin.dataMaster.ekstrakurikuler.edit', [
            'ekstrakurikuler' => $ekstrakurikuler,
            'tahunAjaran' => new Collection([$tahunAjaran]),
            'karyawan' => new Collection([$pembina]),
        ])->render();

        $this->assertStringContainsString(route('ekstrakurikuler.update', 5), $editHtml);
        $this->assertStringContainsString('name="_method" value="PUT"', $editHtml);
        $this->assertStringContainsString('Robotik', $editHtml);
        $this->assertStringContainsString('120000', $editHtml);
    }
}
