<style>
    @page {
        size: A4 portrait;
        margin: 8mm 10mm 8mm 10mm;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 9pt;
        color: #1e293b;
        line-height: 1.42;
        margin: 0;
        padding: 0;
    }

    .page-break {
        page-break-after: always;
    }

    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .text-left { text-align: left; }
    .font-bold { font-weight: bold; }
    .uppercase { text-transform: uppercase; }
    .muted { color: #4b5563; }
    .small { font-size: 8pt; }

    /* Kop Rapor Resmi Sederhana */
    .kop-table {
        width: 100%;
        border-collapse: collapse;
    }

    .kop-table td {
        vertical-align: middle;
        padding-bottom: 6px;
    }

    .kop-line-top {
        border-top: 1.5px solid #111827;
        margin-top: 2px;
    }

    .kop-line-bottom {
        border-top: 0.6px solid #6b7280;
        margin-top: 1.5px;
        margin-bottom: 12px;
    }

    .kop-logo {
        height: 44px;
        width: auto;
    }

    .kop-yayasan {
        font-size: 8pt;
        font-weight: bold;
        letter-spacing: 0.4px;
        color: #374151;
    }

    .kop-sekolah {
        font-size: 12.5pt;
        font-weight: bold;
        color: #111827;
        margin: 1px 0 2px 0;
    }

    .kop-alamat {
        font-size: 7.6pt;
        color: #4b5563;
        line-height: 1.3;
    }

    .doc-title {
        text-align: center;
        font-size: 10.8pt;
        font-weight: bold;
        letter-spacing: 0.4px;
        margin: 10px 0 10px 0;
        color: #0f172a;
    }

    .doc-title--nilai {
        margin: 8px 0 10px 0;
        padding-bottom: 5px;
        border-bottom: 1.2px solid #cbd5e1;
    }

    /* Info Siswa Rapor Nilai */
    .meta-box {
        background-color: #f8fafc;
        border-top: 1.2px solid #334155;
        border-bottom: 1px solid #cbd5e1;
        border-left: none;
        border-right: none;
        padding: 7px 10px;
        margin-bottom: 10px;
    }

    .meta-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.4pt;
        color: #1e293b;
    }

    .meta-table td {
        padding: 2px 3px;
        vertical-align: top;
    }

    .meta-label {
        color: #475569;
    }

    /* Subjudul Bagian */
    .section-heading {
        font-size: 8.7pt;
        font-weight: bold;
        color: #0f172a;
        letter-spacing: 0.25px;
        margin: 11px 0 4px 0;
        padding: 0;
        page-break-after: avoid;
    }

    /* Tabel Rapor Modern Tanpa Garis Vertikal (Clean Horizontal Rules) */
    .rapor-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 9px;
        font-size: 8.2pt;
    }

    .rapor-table--mapel {
        table-layout: fixed;
    }

    .rapor-table--headless {
        border-top: 1.2px solid #334155;
    }

    .rapor-table thead {
        display: table-header-group;
    }

    .rapor-table th,
    .rapor-table td {
        border-top: none;
        border-left: none;
        border-right: none;
        border-bottom: 0.75px solid #e2e8f0;
        padding: 6px 8px;
        vertical-align: top;
    }

    .rapor-table--mapel th,
    .rapor-table--mapel td {
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .rapor-table th {
        background-color: #eef2f6;
        border-top: 1.2px solid #334155;
        border-bottom: 1.2px solid #334155;
        font-weight: bold;
        color: #0f172a;
        font-size: 7.7pt;
        letter-spacing: 0.35px;
        text-align: center;
        vertical-align: middle;
        padding-top: 6px;
        padding-bottom: 6px;
    }

    .rapor-table th.text-left {
        text-align: left;
    }

    .rapor-table tbody tr:nth-child(even) td {
        background-color: #f8fafc;
    }

    .rapor-table tbody tr:last-child td {
        border-bottom: 1px solid #cbd5e1;
    }

    .rapor-table tr {
        page-break-inside: avoid;
    }

    .desc-text {
        font-size: 8pt;
        line-height: 1.44;
        color: #1e293b;
        text-align: justify;
    }

    /* Tabel Biodata Halaman Identitas */
    .bio-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.8pt;
    }

    .bio-table td {
        padding: 4.5px 4px;
        vertical-align: top;
    }

    .bio-sub {
        padding-left: 12px !important;
    }

    /* Ketidakhadiran & Catatan Wali Kelas */
    .split-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 4px;
        page-break-inside: avoid;
    }

    .split-table > tbody > tr > td {
        vertical-align: top;
    }

    .notes-box {
        border-top: 1.2px solid #334155;
        border-bottom: 1px solid #cbd5e1;
        border-left: none;
        border-right: none;
        background-color: #f8fafc;
        min-height: 60px;
        padding: 6px 8px;
        font-size: 8.2pt;
        color: #334155;
    }

    /* Tanda Tangan & Titimangsa */
    .ttd-wrap {
        width: 100%;
        margin-top: 16px;
        page-break-inside: avoid;
        font-size: 8.5pt;
        color: #111827;
    }

    .ttd-table {
        width: 100%;
        border-collapse: collapse;
    }

    .ttd-table td {
        vertical-align: top;
        text-align: center;
        padding: 2px 8px;
    }

    .ttd-space {
        height: 48px;
    }

    .ttd-name {
        font-weight: bold;
        text-decoration: underline;
    }

    .ttd-blank {
        display: inline-block;
        width: 145px;
        border-bottom: 0.8px solid #374151;
    }

    /* Sampul Buku Rapor Klasik Rapi */
    .cover-frame {
        border: 1px solid #1f2937;
        padding: 28mm 18mm 20mm 18mm;
        text-align: center;
        min-height: 214mm;
    }

    .cover-title {
        font-size: 16pt;
        font-weight: bold;
        letter-spacing: 0.6px;
        color: #111827;
        margin-bottom: 6px;
    }

    .cover-school {
        font-size: 12pt;
        font-weight: bold;
        color: #374151;
        margin-bottom: 44px;
    }

    .cover-box {
        border: 1px solid #374151;
        width: 78%;
        margin: 6px auto 18px auto;
        padding: 10px 14px;
        font-size: 11.5pt;
        font-weight: bold;
        color: #111827;
    }

    .photo-box {
        width: 82px;
        height: 108px;
        border: 0.8px solid #4b5563;
        text-align: center;
        vertical-align: middle;
        font-size: 7.5pt;
        color: #6b7280;
    }

    .photo-box img {
        max-width: 78px;
        max-height: 104px;
    }
</style>
