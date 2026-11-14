<?php

namespace Tests\Unit;

use DOMDocument;
use DOMXPath;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class DataTablesEmptyStateViewTest extends TestCase
{
    #[TestWith(['admin.dataMaster.ekstrakurikuler.index', 'ekstrakurikuler', 8])]
    #[TestWith(['admin.dataMaster.jemputan.index', 'jemputan', 7])]
    #[TestWith(['admin.dataMaster.anggotaT2q.index', 'anggotaT2q', 8])]
    #[TestWith(['admin.dataMaster.kelas.index', 'kelas', 7])]
    #[TestWith(['admin.dataMaster.tahunAjaran.index', 'tahunAjaran', 5])]
    #[TestWith(['admin.dataMaster.karyawan.index', 'karyawan', 7])]
    #[TestWith(['admin.informasi.agendaSekolah.index', 'agendaSekolah', 7])]
    #[TestWith(['admin.informasi.pengumuman.index', 'pengumuman', 5])]
    #[TestWith(['admin.pelajaran.kelompokPelajaran.index', 'kelompokPelajaran', 5])]
    #[TestWith(['admin.pelajaran.mataPelajaran.index', 'mataPelajaran', 9])]
    #[TestWith(['admin.pelajaran.jamPelajaran.index', 'jamPelajaran', 7])]
    #[TestWith(['admin.pelajaran.jadwalPelajaran.index', 'jadwalPelajaran', 8])]
    #[TestWith(['admin.dataPelengkap.penghasilan.index', 'penghasilan', 3])]
    #[TestWith(['admin.dataPelengkap.pekerjaan.index', 'pekerjaan', 3])]
    #[TestWith(['admin.dataPelengkap.transportasi.index', 'transportasi', 3])]
    #[TestWith(['admin.dataPelengkap.jenjangPendidikan.index', 'jenjangPendidikan', 3])]
    #[TestWith(['admin.dataPelengkap.berkebutuhanKhusus.index', 'berkebutuhanKhusus', 3])]
    public function test_empty_datatable_views_render_without_colspan_rows_in_tbody(
        string $viewName,
        string $variableName,
        int $expectedColumnCount
    ): void {
        $html = view($viewName, [
            $variableName => new Collection,
        ])->render();

        $document = new DOMDocument;
        libxml_use_internal_errors(true);
        $document->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($document);
        $headerCells = $xpath->query('//table[@id="myTable3"]/thead/tr/th');
        $bodyRows = $xpath->query('//table[@id="myTable3"]/tbody/tr');
        $colspanCells = $xpath->query('//table[@id="myTable3"]/tbody//*[@colspan]');

        $this->assertNotFalse($headerCells);
        $this->assertNotFalse($bodyRows);
        $this->assertNotFalse($colspanCells);
        $this->assertSame($expectedColumnCount, $headerCells->length);
        $this->assertSame(0, $bodyRows->length);
        $this->assertSame(0, $colspanCells->length);
    }

    public function test_layout_configures_indonesian_empty_table_and_colspan_guard(): void
    {
        $layout = view('layouts.appui')->render();

        $this->assertStringContainsString('emptyTable: "Belum ada data tersedia"', $layout);
        $this->assertStringContainsString('function initDataTables(selector, options)', $layout);
        $this->assertStringContainsString("\$cells.is('[colspan]')", $layout);
    }
}
