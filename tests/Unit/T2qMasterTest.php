<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\DataMaster\DaftarJilidController;
use App\Models\Admin\DataMaster\AngkaArab;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Surah;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class T2qMasterTest extends TestCase
{
    public function test_daftar_jilid_and_angka_arab_models_match_schema_and_helpers(): void
    {
        $daftarJilidModel = new DaftarJilid;
        $this->assertSame('akademik_master_t2q_daftar_jilid', $daftarJilidModel->getTable());
        $this->assertSame(['jilid_latin', 'jilid_arab', 'jenis', 'urutan'], $daftarJilidModel->getFillable());
        $this->assertSame(0, $daftarJilidModel->urutan);

        $angkaArabModel = new AngkaArab;
        $this->assertSame('akademik_master_t2q_angka_arab', $angkaArabModel->getTable());
        $this->assertSame(['angka_latin', 'angka_arab'], $angkaArabModel->getFillable());

        $jilid1 = DaftarJilid::make([
            'jilid_latin' => 'Jilid 1',
            'jilid_arab' => 'المجلد ١',
            'jenis' => 'halaman',
            'urutan' => '1',
        ]);
        $quran = DaftarJilid::make([
            'jilid_latin' => 'Talaqqi Juz 30',
            'jilid_arab' => 'تلقي جوز ٣٠',
            'jenis' => 'quran',
            'urutan' => 5,
        ]);
        $tahfidz = DaftarJilid::make([
            'jilid_latin' => 'Juz 30',
            'jilid_arab' => 'جُزْءُ عَمَّ',
            'jenis' => 'Tahfidz',
            'urutan' => 6,
        ]);

        $this->assertSame(1, $jilid1->urutan);
        $this->assertSame('badge-primary', $jilid1->jenis_badge_class);
        $this->assertSame('badge-success', $quran->jenis_badge_class);
        $this->assertSame('badge-info', $tahfidz->jenis_badge_class);

        $angka1 = AngkaArab::make(['angka_latin' => '1', 'angka_arab' => '١']);
        $angka10 = AngkaArab::make(['angka_latin' => '10', 'angka_arab' => '١٠']);
        $pool = new Collection([$angka1, $angka10]);

        $this->assertSame($angka1, AngkaArab::resolveFromCollection($pool, 1));
        $this->assertSame('١٠', AngkaArab::formatToArabFromCollection($pool, '10'));
        $this->assertSame('99', AngkaArab::formatToArabFromCollection($pool, 99));
        $this->assertSame('-', AngkaArab::formatToArabFromCollection($pool, ''));

        $surahModel = new Surah;
        $this->assertSame('akademik_master_t2q_surah', $surahModel->getTable());
        $this->assertSame(['nama_surah', 'nama_surah_arab', 'jumlah_ayat'], $surahModel->getFillable());
        $this->assertSame(0, $surahModel->jumlah_ayat);
        $this->assertSame('-', $surahModel->label_tampilan);

        $anNaba = Surah::factory()->make([
            'nama_surah' => 'An-Naba',
            'nama_surah_arab' => 'النبإ',
            'jumlah_ayat' => '40',
        ]);
        $this->assertSame(40, $anNaba->jumlah_ayat);
        $this->assertSame('An-Naba (40 Ayat)', $anNaba->label_tampilan);
    }

    public function test_daftar_jilid_controller_view_sidebar_and_index_only_routes(): void
    {
        $this->assertTrue(Route::has('daftar-jilid.index'));
        $this->assertFalse(Route::has('daftar-jilid.create'));
        $this->assertFalse(Route::has('daftar-jilid.store'));
        $this->assertFalse(Route::has('daftar-jilid.edit'));
        $this->assertFalse(Route::has('daftar-jilid.update'));
        $this->assertFalse(Route::has('daftar-jilid.destroy'));
        $this->assertFalse(Route::has('angka-arab.index'));
        $this->assertFalse(Route::has('surah.index'));

        $controller = new DaftarJilidController;
        $middlewares = collect($controller->getMiddleware())->pluck('middleware')->all();
        $this->assertContains('role:super admin', $middlewares);

        $jilidSatu = DaftarJilid::make([
            'jilid_latin' => 'Jilid 1',
            'jilid_arab' => 'المجلد ١',
            'jenis' => 'halaman',
            'urutan' => 1,
        ]);
        $jilidSatu->id = 1;

        $alquran = DaftarJilid::make([
            'jilid_latin' => 'Talaqqi Juz 30',
            'jilid_arab' => 'تلقي جوز ٣٠',
            'jenis' => 'quran',
            'urutan' => 5,
        ]);
        $alquran->id = 2;

        Model::preventLazyLoading(true);
        try {
            $indexHtml = view('admin.dataMaster.daftarJilid.index', [
                'daftarJilid' => new Collection([$jilidSatu, $alquran]),
                'jenisOptions' => collect(['halaman', 'quran']),
                'selectedJenis' => 'halaman',
            ])->render();
            $emptyHtml = view('admin.dataMaster.daftarJilid.index', [
                'daftarJilid' => new Collection,
                'jenisOptions' => collect(),
                'selectedJenis' => '',
            ])->render();
            $sidebarHtml = view('layouts.sidebar')->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Daftar Jilid T2Q', $indexHtml);
        $this->assertStringContainsString('Urutan', $indexHtml);
        $this->assertStringContainsString('Jilid 1', $indexHtml);
        $this->assertStringContainsString('المجلد ١', $indexHtml);
        $this->assertStringContainsString('Halaman', $indexHtml);
        $this->assertStringContainsString('Quran', $indexHtml);
        $this->assertStringContainsString("font-family: 'Traditional Arabic',", $indexHtml);
        $this->assertStringContainsString('lang="ar"', $indexHtml);
        $this->assertStringContainsString('id="myTable3"', $indexHtml);

        $this->assertStringContainsString('id="myTable3"', $emptyHtml);
        $this->assertStringNotContainsString('<td colspan=', $emptyHtml);

        $this->assertStringContainsString('submenu-master-t2q', $sidebarHtml);
        $this->assertStringContainsString('Daftar Jilid', $sidebarHtml);
        $this->assertStringContainsString('Anggota T2Q', $sidebarHtml);
        $this->assertStringContainsString(route('daftar-jilid.index'), $sidebarHtml);
        $this->assertStringNotContainsString('Angka Arab', $sidebarHtml);
    }
}
