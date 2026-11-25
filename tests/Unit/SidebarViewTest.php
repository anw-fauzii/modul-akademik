<?php

namespace Tests\Unit;

use App\Models\User;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\TestWith;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SidebarViewTest extends TestCase
{
    /**
     * @var array<int, string>
     */
    private const ACTIVE_ITEM_CLASSES = [
        'bg-gradient-to-r',
        'from-orange-600',
        'to-amber-600',
        'text-white',
        'shadow-md',
        'shadow-orange-600/30',
    ];

    /**
     * @var array<int, string>
     */
    private const INACTIVE_ITEM_CLASSES = [
        'text-slate-300',
        'hover:text-white',
        'hover:bg-slate-800/80',
    ];

    #[TestWith(['/home', 'dashboard'])]
    #[TestWith(['/data-master/sekolah', 'master-sekolah'])]
    #[TestWith(['/data-master/tahun-ajaran', 'master-sekolah'])]
    #[TestWith(['/data-master/titimangsa', 'master-sekolah'])]
    #[TestWith(['/data-master/siswa', 'master-sdm'])]
    #[TestWith(['/data-master/siswa-mutasi', 'master-sdm'])]
    #[TestWith(['/data-master/siswa-mutasi/kelulusan', 'master-sdm'])]
    #[TestWith(['/data-master/karyawan', 'master-sdm'])]
    #[TestWith(['/data-master/kelas', 'master-program'])]
    #[TestWith(['/data-master/anggota-kelas', 'master-program'])]
    #[TestWith(['/data-master/ekstrakurikuler', 'master-program'])]
    #[TestWith(['/data-master/anggota-ekstrakurikuler', 'master-program'])]
    #[TestWith(['/data-master/anggota-jemputan', 'master-program'])]
    #[TestWith(['/data-master/anggota-t2q', 'master-t2q'])]
    #[TestWith(['/data-master/daftar-jilid', 'master-t2q'])]
    #[TestWith(['/data-master/target-capaian', 'master-t2q'])]
    #[TestWith(['/data-master/bank-komentar', 'master-t2q'])]
    #[TestWith(['/data-pelengkap/pekerjaan', 'data-pelengkap'])]
    #[TestWith(['/informasi/agenda-sekolah', 'informasi'])]
    #[TestWith(['/informasi/pengumuman', 'informasi'])]
    #[TestWith(['/pelajaran/kelompok-pelajaran', 'pelajaran'])]
    #[TestWith(['/pelajaran/mata-pelajaran', 'pelajaran'])]
    #[TestWith(['/pelajaran/jam-pelajaran', 'pelajaran'])]
    #[TestWith(['/pelajaran/jadwal-pelajaran', 'pelajaran'])]
    #[TestWith(['/tujuan-pembelajaran/tp', 'tujuan-pembelajaran'])]
    #[TestWith(['/penilaian/kkm', 'penilaian'])]
    #[TestWith(['/penilaian/nilai-tp', 'penilaian'])]
    #[TestWith(['/penilaian/rekap', 'penilaian'])]
    #[TestWith(['/penilaian/rapor', 'penilaian'])]
    #[TestWith(['/t2q/yaumiyah-tahsin', 't2q'])]
    #[TestWith(['/t2q/yaumiyah-tahfizh', 't2q'])]
    #[TestWith(['/prestasi/prestasi-siswa', 'prestasi'])]
    #[TestWith(['/prestasi/prestasi-siswa/create', 'prestasi'])]
    public function test_parent_dropdown_menus_match_dashboard_active_colors_when_child_route_is_active(
        string $uri,
        string $expectedActiveMenu
    ): void {
        $xpath = $this->renderSidebarForUri($uri);

        $dashboardLink = $this->querySingleElement(
            $xpath,
            sprintf('//nav//a[@href="%s"]', route('home'))
        );
        $masterSekolahButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-master-sekolah"]'
        );
        $masterSdmButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-master-sdm"]'
        );
        $masterProgramButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-master-program"]'
        );
        $masterT2qButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-master-t2q"]'
        );
        $dataPelengkapButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-data-pelengkap"]'
        );
        $informasiButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-informasi"]'
        );
        $pelajaranButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-pelajaran"]'
        );
        $tujuanPembelajaranButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-tujuan-pembelajaran"]'
        );
        $penilaianButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-penilaian"]'
        );
        $t2qButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-t2q"]'
        );
        $prestasiLink = $this->querySingleElement(
            $xpath,
            sprintf('//nav//a[@href="%s"]', route('prestasi-siswa.index'))
        );

        $this->assertSame(0, $xpath->query(sprintf('//nav//a[@href="%s"]', route('prestasi-siswa.create')))->length);
        $this->assertSame(0, $xpath->query('//nav//*[@id="submenu-prestasi"]')->length);

        $this->assertMenuState($xpath, $dashboardLink, $expectedActiveMenu === 'dashboard', false);
        $this->assertMenuState($xpath, $masterSekolahButton, $expectedActiveMenu === 'master-sekolah', true);
        $this->assertMenuState($xpath, $masterSdmButton, $expectedActiveMenu === 'master-sdm', true);
        $this->assertMenuState($xpath, $masterProgramButton, $expectedActiveMenu === 'master-program', true);
        $this->assertMenuState($xpath, $masterT2qButton, $expectedActiveMenu === 'master-t2q', true);
        $this->assertMenuState($xpath, $dataPelengkapButton, $expectedActiveMenu === 'data-pelengkap', true);
        $this->assertMenuState($xpath, $informasiButton, $expectedActiveMenu === 'informasi', true);
        $this->assertMenuState($xpath, $pelajaranButton, $expectedActiveMenu === 'pelajaran', true);
        $this->assertMenuState($xpath, $tujuanPembelajaranButton, $expectedActiveMenu === 'tujuan-pembelajaran', true);
        $this->assertMenuState($xpath, $penilaianButton, $expectedActiveMenu === 'penilaian', true);
        $this->assertMenuState($xpath, $t2qButton, $expectedActiveMenu === 't2q', true);
        $this->assertMenuState($xpath, $prestasiLink, $expectedActiveMenu === 'prestasi', false);
    }

    public function test_siswa_mutasi_submenu_active_state_does_not_collide_with_siswa_menu(): void
    {
        $xpath = $this->renderSidebarForUri('/data-master/siswa-mutasi');

        $siswaLink = $this->querySingleElement(
            $xpath,
            sprintf('//nav//a[@href="%s"]', route('siswa.index'))
        );
        $mutasiLink = $this->querySingleElement(
            $xpath,
            sprintf('//nav//a[@href="%s"]', route('siswa-mutasi.index'))
        );

        $siswaClasses = $this->splitClasses($siswaLink->getAttribute('class'));
        $mutasiClasses = $this->splitClasses($mutasiLink->getAttribute('class'));

        $this->assertContains('text-orange-400', $mutasiClasses);
        $this->assertContains('bg-orange-500/10', $mutasiClasses);
        $this->assertNotContains('text-orange-400', $siswaClasses);
        $this->assertContains('text-slate-400', $siswaClasses);
    }

    public function test_wali_kelas_sidebar_renders_and_highlights_catatan_rapor_menu(): void
    {
        $role = new Role;
        $role->setRawAttributes(['id' => 2, 'name' => 'wali kelas', 'guard_name' => 'web']);

        $waliUser = new User;
        $waliUser->setRawAttributes(['id' => 99, 'name' => 'Wali Test', 'email' => 'wali@primainsani.sch.id']);
        $waliUser->setRelation('roles', new Collection([$role]));
        $waliUser->setRelation('permissions', new Collection);

        Auth::setUser($waliUser);

        try {
            $xpath = $this->renderSidebarForUri('/wali-kelas/catatan-rapor');
        } finally {
            Auth::logout();
        }

        $catatanLink = $this->querySingleElement(
            $xpath,
            sprintf('//nav//a[@href="%s"]', route('wali-kelas.catatan-rapor.index'))
        );

        $this->assertMenuState($xpath, $catatanLink, true, false);
    }

    /**
     * Render the sidebar under a simulated GET request with resolved routing.
     */
    private function renderSidebarForUri(string $uri): DOMXPath
    {
        $request = Request::create($uri, 'GET');
        $request->setRouteResolver(fn () => app('router')->getRoutes()->match($request));

        $this->app->instance('request', $request);
        Facade::clearResolvedInstance('request');

        $html = view('layouts.sidebar')->render();

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        return new DOMXPath($document);
    }

    private function querySingleElement(DOMXPath $xpath, string $query, ?DOMElement $context = null): DOMElement
    {
        $nodes = $xpath->query($query, $context);
        $this->assertNotFalse($nodes);
        $this->assertSame(1, $nodes->length, "Expected 1 element for XPath query: {$query}");

        $element = $nodes->item(0);
        $this->assertInstanceOf(DOMElement::class, $element);

        return $element;
    }

    private function assertMenuState(DOMXPath $xpath, DOMElement $menuElement, bool $isActive, bool $hasChevron): void
    {
        $classes = $this->splitClasses($menuElement->getAttribute('class'));
        $icon = $this->querySingleElement($xpath, '(.//svg)[1]', $menuElement);
        $iconClasses = $this->splitClasses($icon->getAttribute('class'));

        if ($isActive) {
            $this->assertContainsAllClasses(self::ACTIVE_ITEM_CLASSES, $classes);
            $this->assertNotContains('bg-slate-800', $classes);
            $this->assertContains('text-white', $iconClasses);
            $this->assertNotContains('text-slate-400', $iconClasses);
        } else {
            $this->assertContainsAllClasses(self::INACTIVE_ITEM_CLASSES, $classes);
            $this->assertNotContains('from-orange-600', $classes);
            $this->assertContains('text-slate-400', $iconClasses);
            $this->assertContains('group-hover:text-orange-400', $iconClasses);
            $this->assertNotContains('text-white', $iconClasses);
        }

        if (! $hasChevron) {
            return;
        }

        $chevron = $this->querySingleElement($xpath, '(.//svg)[2]', $menuElement);
        $chevronClasses = $this->splitClasses($chevron->getAttribute('class'));
        $this->assertSame($isActive ? 'true' : 'false', $menuElement->getAttribute('aria-expanded'));

        if ($isActive) {
            $this->assertContainsAllClasses(['text-white', 'rotate-180'], $chevronClasses);
            $this->assertNotContains('text-slate-400', $chevronClasses);
        } else {
            $this->assertContainsAllClasses(['text-slate-400', 'group-hover:text-white'], $chevronClasses);
            $this->assertNotContains('text-white', $chevronClasses);
            $this->assertNotContains('rotate-180', $chevronClasses);
        }
    }

    /**
     * @return array<int, string>
     */
    private function splitClasses(string $classAttribute): array
    {
        $parts = preg_split('/\s+/', trim($classAttribute));

        return is_array($parts) ? array_values(array_filter($parts)) : [];
    }

    /**
     * @param  array<int, string>  $expectedClasses
     * @param  array<int, string>  $actualClasses
     */
    private function assertContainsAllClasses(array $expectedClasses, array $actualClasses): void
    {
        foreach ($expectedClasses as $expectedClass) {
            $this->assertContains($expectedClass, $actualClasses);
        }
    }
}
