<style>
    @page {
        margin: 14mm 16mm 14mm 16mm;
    }

    body {
        font-family: DejaVu Sans, sans-serif;
        font-size: 9pt;
        color: #111827;
        line-height: 1.4;
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
        letter-spacing: 0.3px;
        margin: 10px 0 10px 0;
        color: #111827;
    }

    /* Info Siswa Rapor Nilai (Mengikuti Format Referensi Sekolah) */
    .meta-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 8.6pt;
        margin-bottom: 8px;
    }

    .meta-table td {
        padding: 1.5px 3px;
        vertical-align: top;
    }

    /* Subjudul Bagian */
    .section-heading {
        font-size: 9pt;
        font-weight: bold;
        color: #111827;
        margin: 10px 0 4px 0;
    }

    /* Tabel Rapor Standar Rapi */
    .rapor-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
        font-size: 8.2pt;
    }

    .rapor-table th,
    .rapor-table td {
        border: 0.8px solid #4b5563;
        padding: 5px 7px;
        vertical-align: top;
    }

    .rapor-table th {
        background-color: #f3f4f6;
        font-weight: bold;
        color: #111827;
        font-size: 8pt;
        text-align: center;
    }

    .rapor-table tr {
        page-break-inside: avoid;
    }

    .desc-text {
        font-size: 8pt;
        line-height: 1.4;
        color: #1f2937;
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
        border: 0.8px solid #4b5563;
        min-height: 58px;
        padding: 6px 8px;
        font-size: 8.2pt;
        color: #374151;
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
