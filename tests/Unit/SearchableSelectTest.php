<?php

namespace Tests\Unit;

use Tests\TestCase;

class SearchableSelectTest extends TestCase
{
    public function test_appui_layout_loads_vite_assets_with_compiled_searchable_select(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/appui.blade.php'));
        $this->assertIsString($layout);
        $this->assertStringContainsString("@vite(['resources/css/app.css', 'resources/js/app.js'])", $layout);

        $jsSource = file_get_contents(resource_path('js/components/searchableSelect.js'));
        $this->assertIsString($jsSource);
        $this->assertStringContainsString('initSearchableSelects', $jsSource);
        $this->assertStringContainsString('.dataTables_length', $jsSource);
        $this->assertStringContainsString('.swal2-container', $jsSource);
        $this->assertStringContainsString('swal2-select', $jsSource);
        $this->assertStringContainsString("closest('table')", $jsSource);
        $this->assertStringContainsString('MutationObserver', $jsSource);
        $this->assertStringContainsString("new Event('change', { bubbles: true })", $jsSource);

        $cssSource = file_get_contents(resource_path('css/app.css'));
        $this->assertIsString($cssSource);
        $this->assertStringContainsString('.s2-dropdown', $cssSource);
        $this->assertStringContainsString('max-height: 240px;', $cssSource);
        $this->assertStringContainsString('.s2-search-input', $cssSource);

        $manifestPath = public_path('build/manifest.json');
        $this->assertFileExists($manifestPath);

        /** @var array<string, array{file: string}> $manifest */
        $manifest = json_decode((string) file_get_contents($manifestPath), true);
        $this->assertArrayHasKey('resources/js/app.js', $manifest);
        $this->assertArrayHasKey('resources/css/app.css', $manifest);

        $builtJs = file_get_contents(public_path('build/'.$manifest['resources/js/app.js']['file']));
        $builtCss = file_get_contents(public_path('build/'.$manifest['resources/css/app.css']['file']));
        $this->assertStringContainsString('s2-search-input', (string) $builtJs);
        $this->assertStringContainsString('swal2-select', (string) $builtJs);
        $this->assertStringContainsString('.s2-dropdown', (string) $builtCss);
    }
}
