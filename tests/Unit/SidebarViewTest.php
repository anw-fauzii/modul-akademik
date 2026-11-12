<?php

namespace Tests\Unit;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\Attributes\TestWith;
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
    #[TestWith(['/data-master/kelas', 'data-master'])]
    #[TestWith(['/data-master/anggota-jemputan', 'data-master'])]
    #[TestWith(['/data-master/anggota-t2q', 'data-master'])]
    #[TestWith(['/data-pelengkap/pekerjaan', 'data-pelengkap'])]
    #[TestWith(['/informasi/agenda-sekolah', 'informasi'])]
    #[TestWith(['/informasi/pengumuman', 'informasi'])]
    public function test_parent_dropdown_menus_match_dashboard_active_colors_when_child_route_is_active(
        string $uri,
        string $expectedActiveMenu
    ): void {
        $xpath = $this->renderSidebarForUri($uri);

        $dashboardLink = $this->querySingleElement(
            $xpath,
            sprintf('//nav//a[@href="%s"]', route('home'))
        );
        $dataMasterButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-data-master"]'
        );
        $dataPelengkapButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-data-pelengkap"]'
        );
        $informasiButton = $this->querySingleElement(
            $xpath,
            '//nav//button[@data-sidebar-toggle="submenu-informasi"]'
        );

        $this->assertMenuState($xpath, $dashboardLink, $expectedActiveMenu === 'dashboard', false);
        $this->assertMenuState($xpath, $dataMasterButton, $expectedActiveMenu === 'data-master', true);
        $this->assertMenuState($xpath, $dataPelengkapButton, $expectedActiveMenu === 'data-pelengkap', true);
        $this->assertMenuState($xpath, $informasiButton, $expectedActiveMenu === 'informasi', true);
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
