<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\Presensi\PresensiSiswaController;
use App\Http\Requests\Admin\Presensi\PresensiSiswaBulkDestroyRequest;
use App\Http\Requests\Admin\Presensi\PresensiSiswaBulkRequest;
use App\Http\Requests\Admin\Presensi\PresensiSiswaRequest;
use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Presensi\PresensiSiswa;
use App\Services\Admin\Presensi\PresensiSiswaService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PresensiSiswaModelAndViewTest extends TestCase
{
    public function test_model_casts_and_status_accessors(): void
    {
        $presensi = PresensiSiswa::make([
            'anggota_kelas_id' => '12',
            'tanggal' => '2026-11-13',
            'jam_masuk' => '07:25',
            'status' => 'terlambat',
            'keterlambatan_menit' => '25',
        ]);

        $this->assertSame(12, $presensi->anggota_kelas_id);
        $this->assertSame('2026-11-13', $presensi->tanggal?->format('Y-m-d'));
        $this->assertSame(25, $presensi->keterlambatan_menit);
        $this->assertSame('Terlambat', $presensi->status_label);
        $this->assertSame('Terlambat', $presensi->status_badge['label']);
        $this->assertSame(
            'Terlambat 25 menit pada pukul 07:25.',
            $presensi->status_keterangan
        );

        $hadir = PresensiSiswa::make(['jam_masuk' => '07:10', 'status' => 'hadir', 'keterlambatan_menit' => 0]);
        $izin = PresensiSiswa::make(['status' => 'izin', 'keterlambatan_menit' => 0]);
        $sakit = PresensiSiswa::make(['status' => 'sakit', 'keterlambatan_menit' => 0]);
        $alpa = PresensiSiswa::make(['status' => 'alpa', 'keterlambatan_menit' => 0]);

        $this->assertSame(
            'Siswa hadir tepat waktu pada pukul 07:10 (batas maksimal 07:35).',
            $hadir->status_keterangan
        );
        $this->assertSame('Siswa tidak masuk karena izin resmi.', $izin->status_keterangan);
        $this->assertSame('Siswa tidak masuk karena sakit.', $sakit->status_keterangan);
        $this->assertSame('Siswa tidak hadir tanpa keterangan (Alpa).', $alpa->status_keterangan);
    }

    public function test_index_view_renders_without_lazy_loading_or_colspan_in_tbody(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'id' => 1,
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => 1,
        ]);
        $kelas->id = 10;
        $kelas->exists = true;
        $kelas->setRelation('tahunAjaran', $tahunAjaran);

        $siswa = Siswa::make([
            'nis' => '2026001',
            'nama_lengkap' => 'Ahmad Fauzan',
        ]);

        $anggota = AnggotaKelas::make([
            'id' => 5,
            'siswa_nis' => '2026001',
            'akademik_master_kelas_id' => 10,
        ]);
        $anggota->setRelation('siswa', $siswa);
        $anggota->setRelation('kelas', $kelas);

        $presensi = PresensiSiswa::make([
            'anggota_kelas_id' => 5,
            'tanggal' => '2026-11-13',
            'jam_masuk' => '07:15:00',
            'status' => 'terlambat',
            'keterlambatan_menit' => 15,
        ]);
        $presensi->id = 1;
        $presensi->exists = true;
        $presensi->setRelation('anggotaKelas', $anggota);

        Model::preventLazyLoading(true);

        try {
            $htmlEmpty = view('admin.presensi.presensiSiswa.index', [
                'hasFilter' => true,
                'hasExplicitFilter' => false,
                'presensi' => collect(),
                'summary' => [
                    'total' => 0,
                    'hadir' => 0,
                    'terlambat' => 0,
                    'izin' => 0,
                    'sakit' => 0,
                    'alpa' => 0,
                ],
                'matrix' => [
                    'kelas' => null,
                    'bulan' => '2026-11',
                    'bulan_label' => 'November 2026',
                    'days' => [],
                    'rows' => [],
                    'daily_totals' => [],
                    'summary' => [
                        'siswa' => 0,
                        'total_catatan' => 0,
                        'hadir' => 0,
                        'terlambat' => 0,
                        'izin' => 0,
                        'sakit' => 0,
                        'alpa' => 0,
                        'persentase_hadir' => 0,
                    ],
                ],
                'kelasOptions' => collect([$kelas]),
                'tahunAjaranOptions' => collect([$tahunAjaran]),
                'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
                'filters' => [
                    'tanggal' => '',
                    'status' => '',
                    'kelas_id' => null,
                    'tahun_ajaran_id' => 1,
                    'bulan' => '2026-11',
                ],
            ])->render();

            $html = view('admin.presensi.presensiSiswa.index', [
                'hasFilter' => true,
                'hasExplicitFilter' => true,
                'presensi' => collect([$presensi]),
                'summary' => [
                    'total' => 1,
                    'hadir' => 0,
                    'terlambat' => 1,
                    'izin' => 0,
                    'sakit' => 0,
                    'alpa' => 0,
                ],
                'matrix' => [
                    'kelas' => $kelas,
                    'bulan' => '2026-11',
                    'bulan_label' => 'November 2026',
                    'days' => [
                        [
                            'day' => 12,
                            'label' => '12',
                            'day_name' => 'Kam',
                            'date' => '2026-11-12',
                            'is_weekend' => false,
                            'is_today' => false,
                        ],
                        [
                            'day' => 13,
                            'label' => '13',
                            'day_name' => 'Jum',
                            'date' => '2026-11-13',
                            'is_weekend' => false,
                            'is_today' => true,
                        ],
                    ],
                    'rows' => [
                        [
                            'anggota' => $anggota,
                            'cells' => [13 => $presensi],
                            'counts' => [
                                'hadir' => 0,
                                'terlambat' => 1,
                                'izin' => 0,
                                'sakit' => 0,
                                'alpa' => 0,
                                'total_telat_menit' => 15,
                                'total_catatan' => 1,
                                'persentase_hadir' => 100,
                            ],
                        ],
                    ],
                    'daily_totals' => [12 => 0, 13 => 1],
                    'summary' => [
                        'siswa' => 1,
                        'total_catatan' => 1,
                        'hadir' => 0,
                        'terlambat' => 1,
                        'izin' => 0,
                        'sakit' => 0,
                        'alpa' => 0,
                        'persentase_hadir' => 100,
                    ],
                ],
                'kelasOptions' => collect([$kelas]),
                'tahunAjaranOptions' => collect([$tahunAjaran]),
                'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
                'filters' => [
                    'tanggal' => '',
                    'status' => '',
                    'kelas_id' => 10,
                    'tahun_ajaran_id' => null,
                    'bulan' => '2026-11',
                ],
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringNotContainsString('Pilih Filter Data Terlebih Dahulu', $htmlEmpty);
        $this->assertStringNotContainsString('Filter Aktif', $htmlEmpty);
        $this->assertStringContainsString('<div id="tab-panel-matrix"', $htmlEmpty);
        $this->assertStringContainsString('Matriks Presensi Siswa', $htmlEmpty);
        $this->assertStringContainsString('(Aktif)', $htmlEmpty);
        $this->assertStringContainsString('<div id="tab-panel-matrix"', $html);
        $this->assertStringContainsString('Filter Aktif', $html);
        $this->assertStringContainsString('Matriks Presensi — 1A', $html);
        $this->assertStringContainsString('presensi-kpi-card presensi-kpi--emerald', $html);
        $this->assertStringContainsString('presensi-rate-card presensi-rate--orange', $html);
        $this->assertStringContainsString('% Kehadiran', $html);
        $this->assertStringContainsString('% Keterlambatan', $html);
        $this->assertStringContainsString('Ahmad Fauzan', $html);
        $this->assertStringContainsString('15 menit', $html);
        $this->assertStringContainsString('07:15', $html);
        $this->assertStringContainsString('js-status-detail-trigger', $html);
        $this->assertStringContainsString('data-tooltip="Terlambat 15 menit pada pukul 07:15."', $html);
        $this->assertStringNotContainsString('title="Terlambat 15 menit', $html);
        $this->assertStringContainsString('id="presensiInstantTooltip"', $html);
        $this->assertStringContainsString('id="presensiStatusModal"', $html);
        $this->assertStringContainsString('id="presensiDetailDeleteForm"', $html);
        $this->assertStringContainsString('id="presensiDetailDeleteBtn"', $html);
        $this->assertStringContainsString('id="openBulkDeleteModalBtn"', $html);
        $this->assertStringContainsString('id="presensiBulkDeleteModal"', $html);
        $this->assertStringContainsString('id="presensiBulkDeleteForm"', $html);
        $this->assertStringContainsString(route('presensi-siswa.bulk.destroy'), $html);
        $this->assertStringContainsString(e(route('presensi-siswa.bulk', ['kelas_id' => 10, 'tanggal' => '2026-11-12'])), $html);
        $this->assertStringContainsString('13 Jum', $html);
        $this->assertMatchesRegularExpression('/<span class="matrix-cell-empty"[^>]*>\s*-\s*<\/span>/', $html);
        $this->assertStringContainsString('Input massal tanggal 2026-11-12', $html);
        $this->assertStringContainsString(route('presensi-siswa.destroy', $presensi), $html);
        $this->assertStringContainsString('Rekap &amp; Persentase', $html);
        $this->assertStringNotContainsString('id="tab-panel-log"', $html);
        $this->assertStringNotContainsString('id="presensiSiswaTable"', $html);
        $this->assertStringNotContainsString('Log Riwayat', $html);
        $this->assertStringNotContainsString('atau sel (<strong>+</strong>)', $html);
        $this->assertStringNotContainsString('Input massal presensi kelas', $html);
        $this->assertStringNotContainsString('<th class="bg-emerald-50 text-emerald-700">H</th>', $html);
        $this->assertStringNotContainsString('colspan=', $html);

        $heroPos = strpos($html, 'class="presensi-hero');
        $kpiPos = strpos($html, 'class="presensi-kpi-card');
        $filterPos = strpos($html, 'Filter Data &amp; Matriks Presensi Siswa');
        $matrixPos = strpos($html, 'Matriks Per Kelas');
        $this->assertNotFalse($heroPos);
        $this->assertNotFalse($kpiPos);
        $this->assertNotFalse($filterPos);
        $this->assertNotFalse($matrixPos);
        $this->assertLessThan($kpiPos, $heroPos);
        $this->assertLessThan($filterPos, $kpiPos);
        $this->assertLessThan($matrixPos, $filterPos);
    }

    public function test_request_rejects_invalid_status_and_negative_keterlambatan(): void
    {
        $request = new PresensiSiswaRequest;

        $validator = Validator::make([
            'anggota_kelas_id' => '',
            'tanggal' => 'bukan-tanggal',
            'jam_masuk' => '99:99',
            'status' => 'tidak-valid',
            'keterlambatan_menit' => -5,
        ], [
            'anggota_kelas_id' => ['required', 'integer'],
            'tanggal' => ['required', 'date'],
            'jam_masuk' => ['nullable', 'date_format:H:i'],
            'status' => ['required', 'in:hadir,terlambat,izin,sakit,alpa'],
            'keterlambatan_menit' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ], $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('anggota_kelas_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('tanggal', $validator->errors()->toArray());
        $this->assertArrayHasKey('jam_masuk', $validator->errors()->toArray());
        $this->assertArrayHasKey('status', $validator->errors()->toArray());
        $this->assertArrayHasKey('keterlambatan_menit', $validator->errors()->toArray());
    }

    public function test_bulk_view_renders_empty_and_loaded_states_without_lazy_loading(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 1;
        $tahunAjaran->exists = true;

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => 1,
        ]);
        $kelas->id = 10;
        $kelas->exists = true;
        $kelas->setRelation('tahunAjaran', $tahunAjaran);
        $kelas->setRelation('waliKelas', null);

        $siswa = Siswa::make([
            'nis' => '2026001',
            'nama_lengkap' => 'Ahmad Fauzan',
            'jenis_kelamin' => 'L',
        ]);
        $siswa->exists = true;

        $anggota = AnggotaKelas::make([
            'siswa_nis' => '2026001',
            'akademik_master_kelas_id' => 10,
        ]);
        $anggota->id = 5;
        $anggota->exists = true;
        $anggota->setRelation('siswa', $siswa);

        $existing = PresensiSiswa::make([
            'anggota_kelas_id' => 5,
            'tanggal' => '2026-11-13',
            'jam_masuk' => '07:15:00',
            'status' => 'terlambat',
            'keterlambatan_menit' => 15,
        ]);
        $existing->id = 1;
        $existing->exists = true;

        Model::preventLazyLoading(true);

        try {
            $htmlEmpty = view('admin.presensi.presensiSiswa.bulk', [
                'kelasOptions' => collect([$kelas]),
                'selectedKelas' => null,
                'selectedKelasId' => 0,
                'tanggal' => '2026-11-13',
                'tanggalLabel' => 'Jumat, 13 November 2026',
                'rows' => [],
                'existingCount' => 0,
                'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
            ])->render();

            $htmlLoaded = view('admin.presensi.presensiSiswa.bulk', [
                'kelasOptions' => collect([$kelas]),
                'selectedKelas' => $kelas,
                'selectedKelasId' => 10,
                'tanggal' => '2026-11-13',
                'tanggalLabel' => 'Jumat, 13 November 2026',
                'rows' => [
                    [
                        'anggota' => $anggota,
                        'existing' => $existing,
                    ],
                ],
                'existingCount' => 1,
                'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('Pilih Kelas &amp; Tanggal Terlebih Dahulu', $htmlEmpty);
        $this->assertStringNotContainsString('id="bulkAttendanceForm"', $htmlEmpty);
        $this->assertStringContainsString('id="bulkAttendanceForm"', $htmlLoaded);
        $this->assertStringContainsString('Ahmad Fauzan', $htmlLoaded);
        $this->assertStringContainsString('Tercatat', $htmlLoaded);
        $this->assertStringContainsString('items[0][anggota_kelas_id]', $htmlLoaded);
        $this->assertStringContainsString('07:15', $htmlLoaded);
        $this->assertStringContainsString('value="15"', $htmlLoaded);
        $this->assertStringContainsString('<table class="table table-hover align-middle mb-0" id="bulkStudentsTable">', $htmlLoaded);
        $this->assertStringNotContainsString('id="liveCountHadir"', $htmlLoaded);
        $this->assertStringNotContainsString('presensi-kpi-grid', $htmlLoaded);
        $this->assertStringNotContainsString('presensi-kpi-card', $htmlLoaded);
        $this->assertStringContainsString('id="bulkDestroyCurrentForm"', $htmlLoaded);
        $this->assertStringContainsString('Hapus Semua (1)', $htmlLoaded);
        $this->assertStringContainsString('data-has-existing="1"', $htmlLoaded);
        $this->assertStringContainsString('tidak akan berubah saat memakai <em>Aksi Cepat</em>', $htmlLoaded);
        $this->assertStringContainsString('applyToNewRowsOnly', $htmlLoaded);
        $this->assertStringContainsString(route('presensi-siswa.bulk.destroy'), $htmlLoaded);
    }

    public function test_bulk_request_normalizes_items_and_rejects_invalid_entries(): void
    {
        $request = PresensiSiswaBulkRequest::create('/presensi/presensi-siswa/bulk', 'POST', [
            'kelas_id' => 10,
            'tanggal' => '2026-11-13',
            'items' => [
                [
                    'anggota_kelas_id' => 5,
                    'status' => 'terlambat',
                    'jam_masuk' => '07:15:00',
                    'keterlambatan_menit' => '15',
                ],
                [
                    'anggota_kelas_id' => 6,
                    'status' => 'izin',
                    'jam_masuk' => '   ',
                    'keterlambatan_menit' => '',
                ],
            ],
        ]);

        $prepareMethod = new \ReflectionMethod($request, 'prepareForValidation');
        $prepareMethod->invoke($request);

        $normalizedItems = $request->input('items');
        $this->assertSame('07:15', $normalizedItems[0]['jam_masuk']);
        $this->assertSame(15, $normalizedItems[0]['keterlambatan_menit']);
        $this->assertNull($normalizedItems[1]['jam_masuk']);
        $this->assertSame(0, $normalizedItems[1]['keterlambatan_menit']);

        $validator = Validator::make([
            'kelas_id' => '',
            'tanggal' => 'bukan-tanggal',
            'items' => [
                [
                    'anggota_kelas_id' => '',
                    'status' => 'tidak-valid',
                    'jam_masuk' => '25:99',
                    'keterlambatan_menit' => -1,
                ],
            ],
        ], [
            'kelas_id' => ['required', 'integer'],
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.anggota_kelas_id' => ['required', 'integer'],
            'items.*.status' => ['required', 'in:hadir,terlambat,izin,sakit,alpa'],
            'items.*.jam_masuk' => ['nullable', 'date_format:H:i'],
            'items.*.keterlambatan_menit' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ], $request->messages());

        $this->assertTrue($validator->fails());
        $errors = $validator->errors()->toArray();
        $this->assertArrayHasKey('kelas_id', $errors);
        $this->assertArrayHasKey('tanggal', $errors);
        $this->assertArrayHasKey('items.0.anggota_kelas_id', $errors);
        $this->assertArrayHasKey('items.0.status', $errors);
        $this->assertArrayHasKey('items.0.jam_masuk', $errors);
        $this->assertArrayHasKey('items.0.keterlambatan_menit', $errors);
    }

    public function test_matrix_days_only_include_monday_to_friday_and_exclude_weekends(): void
    {
        $service = new PresensiSiswaService;
        $method = new \ReflectionMethod($service, 'buildClassMatrix');

        $matrix = $method->invoke($service, null, '2026-11', '');

        $this->assertCount(21, $matrix['days']);
        foreach ($matrix['days'] as $dayInfo) {
            $date = Carbon::createFromFormat('Y-m-d', $dayInfo['date']);
            $this->assertTrue($date->isWeekday());
            $this->assertFalse($date->isWeekend());
        }
    }

    public function test_attendance_percentage_and_predikat_calculations(): void
    {
        $service = new PresensiSiswaService;
        $emptyMethod = new \ReflectionMethod($service, 'emptyAttendanceCounts');
        $accumulateMethod = new \ReflectionMethod($service, 'accumulateRecordIntoCounts');
        $finalizeMethod = new \ReflectionMethod($service, 'finalizeAttendanceCounts');

        $counts = $emptyMethod->invoke($service);

        $samples = [
            ['status' => 'hadir', 'keterlambatan_menit' => 0],
            ['status' => 'hadir', 'keterlambatan_menit' => 0],
            ['status' => 'terlambat', 'keterlambatan_menit' => 12],
            ['status' => 'alpa', 'keterlambatan_menit' => 0],
        ];

        foreach ($samples as $sample) {
            $accumulateMethod->invokeArgs($service, [&$counts, PresensiSiswa::make($sample)]);
        }

        $result = $finalizeMethod->invoke($service, $counts);

        $this->assertSame(2, $result['hadir']);
        $this->assertSame(1, $result['terlambat']);
        $this->assertSame(1, $result['alpa']);
        $this->assertSame(4, $result['total_catatan']);
        $this->assertSame(3, $result['hadir_efektif']);
        $this->assertSame(12, $result['total_telat_menit']);
        $this->assertSame(75.0, $result['persentase_hadir']);
        $this->assertSame(50.0, $result['persentase_tepat_waktu']);
        $this->assertSame(25.0, $result['persentase_terlambat']);
        $this->assertSame(25.0, $result['persentase_alpa']);
        $this->assertSame('Perlu Perhatian', $result['predikat']['label']);
    }

    public function test_rekap_view_renders_empty_class_detail_and_all_classes_without_lazy_loading(): void
    {
        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 1;
        $tahunAjaran->exists = true;

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => 1,
        ]);
        $kelas->id = 10;
        $kelas->exists = true;
        $kelas->setRelation('tahunAjaran', $tahunAjaran);
        $kelas->setRelation('waliKelas', null);

        $siswa = Siswa::make([
            'nis' => '2026001',
            'nama_lengkap' => 'Ahmad Fauzan',
            'jenis_kelamin' => 'L',
        ]);
        $siswa->exists = true;

        $anggota = AnggotaKelas::make([
            'siswa_nis' => '2026001',
            'akademik_master_kelas_id' => 10,
        ]);
        $anggota->id = 5;
        $anggota->exists = true;
        $anggota->setRelation('siswa', $siswa);

        $counts = [
            'hadir' => 8,
            'terlambat' => 1,
            'izin' => 1,
            'sakit' => 0,
            'alpa' => 0,
            'total_telat_menit' => 10,
            'total_catatan' => 10,
            'hadir_efektif' => 9,
            'persentase_hadir' => 90.0,
            'persentase_tepat_waktu' => 80.0,
            'persentase_terlambat' => 10.0,
            'persentase_alpa' => 0.0,
            'predikat' => [
                'label' => 'Baik',
                'class' => 'bg-sky-50 text-sky-700 border-sky-200',
            ],
        ];

        $period = [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-30',
            'bulan' => '2026-11',
            'label' => 'November 2026',
            'weekdays_count' => 21,
        ];

        Model::preventLazyLoading(true);

        try {
            $htmlEmpty = view('admin.presensi.presensiSiswa.rekap', [
                'hasFilter' => true,
                'hasExplicitFilter' => false,
                'mode' => 'all_classes',
                'selectedKelas' => null,
                'studentRows' => [],
                'classRows' => [],
                'summary' => array_merge(['siswa' => 0, 'jumlah_kelas' => 0], $counts, ['total_catatan' => 0]),
                'period' => $period,
                'kelasOptions' => collect([$kelas]),
                'tahunAjaranOptions' => collect([$tahunAjaran]),
                'filters' => [
                    'tahun_ajaran_id' => 1,
                    'kelas_id' => null,
                    'bulan' => '2026-11',
                    'include_weekend' => false,
                ],
            ])->render();

            $htmlStudent = view('admin.presensi.presensiSiswa.rekap', [
                'hasFilter' => true,
                'hasExplicitFilter' => true,
                'mode' => 'class_detail',
                'selectedKelas' => $kelas,
                'studentRows' => [
                    ['anggota' => $anggota, 'counts' => $counts],
                ],
                'classRows' => [],
                'summary' => array_merge(['siswa' => 1, 'jumlah_kelas' => 1], $counts),
                'period' => $period,
                'kelasOptions' => collect([$kelas]),
                'tahunAjaranOptions' => collect([$tahunAjaran]),
                'filters' => [
                    'tahun_ajaran_id' => 1,
                    'kelas_id' => 10,
                    'bulan' => '2026-11',
                    'include_weekend' => false,
                ],
            ])->render();

            $htmlClasses = view('admin.presensi.presensiSiswa.rekap', [
                'hasFilter' => true,
                'hasExplicitFilter' => true,
                'mode' => 'all_classes',
                'selectedKelas' => null,
                'studentRows' => [],
                'classRows' => [
                    ['kelas' => $kelas, 'siswa_count' => 1, 'counts' => $counts],
                ],
                'summary' => array_merge(['siswa' => 1, 'jumlah_kelas' => 1], $counts),
                'period' => $period,
                'kelasOptions' => collect([$kelas]),
                'tahunAjaranOptions' => collect([$tahunAjaran]),
                'filters' => [
                    'tahun_ajaran_id' => 1,
                    'kelas_id' => null,
                    'bulan' => '2026-11',
                    'include_weekend' => false,
                ],
            ])->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringNotContainsString('Pilih Filter Rekap Terlebih Dahulu', $htmlEmpty);
        $this->assertStringNotContainsString('Filter Aktif', $htmlEmpty);
        $this->assertStringContainsString('Rincian Rekap &amp; Persentase Per Kelas', $htmlEmpty);
        $this->assertStringContainsString('(Aktif)', $htmlEmpty);
        $this->assertStringNotContainsString('tanggal_mulai', $htmlEmpty);
        $this->assertStringContainsString('Filter Aktif', $htmlStudent);
        $this->assertStringContainsString('Ahmad Fauzan', $htmlStudent);
        $this->assertStringContainsString('90%', $htmlStudent);
        $this->assertStringContainsString('10%', $htmlStudent);
        $this->assertStringContainsString('% Keterlambatan', $htmlStudent);
        $this->assertStringContainsString('presensi-kpi-card presensi-kpi--emerald', $htmlStudent);
        $this->assertStringContainsString('presensi-rate-card presensi-rate--indigo', $htmlStudent);
        $this->assertStringContainsString('Baik', $htmlStudent);
        $this->assertStringContainsString('id="rekapStudentTable"', $htmlStudent);
        $this->assertStringContainsString('id="rekapClassTable"', $htmlClasses);
        $this->assertStringContainsString('SD - 1A', $htmlClasses);
        $this->assertStringContainsString('90%', $htmlClasses);
        $this->assertStringContainsString('10%', $htmlClasses);

        $rekapHeroPos = strpos($htmlStudent, 'class="rekap-hero');
        $rekapKpiPos = strpos($htmlStudent, 'class="presensi-kpi-card');
        $rekapFilterPos = strpos($htmlStudent, 'Filter Rekap &amp; Persentase');
        $rekapTablePos = strpos($htmlStudent, 'Rincian Rekap &amp; Persentase');
        $this->assertNotFalse($rekapHeroPos);
        $this->assertNotFalse($rekapKpiPos);
        $this->assertNotFalse($rekapFilterPos);
        $this->assertNotFalse($rekapTablePos);
        $this->assertLessThan($rekapKpiPos, $rekapHeroPos);
        $this->assertLessThan($rekapFilterPos, $rekapKpiPos);
        $this->assertLessThan($rekapTablePos, $rekapFilterPos);
    }

    public function test_keterlambatan_auto_calculation_with_cutoff_0735(): void
    {
        $this->assertSame('07:35', PresensiSiswa::BATAS_JAM_MASUK);
        $this->assertSame(0, PresensiSiswa::calculateKeterlambatanMenit(null));
        $this->assertSame(0, PresensiSiswa::calculateKeterlambatanMenit('07:00'));
        $this->assertSame(0, PresensiSiswa::calculateKeterlambatanMenit('07:35'));
        $this->assertSame(1, PresensiSiswa::calculateKeterlambatanMenit('07:36'));
        $this->assertSame(15, PresensiSiswa::calculateKeterlambatanMenit('07:50:45'));
        $this->assertSame(30, PresensiSiswa::calculateKeterlambatanMenit('08:05'));
        $this->assertSame(0, PresensiSiswa::calculateKeterlambatanMenit('bukan-jam'));
    }

    public function test_service_normalize_payload_auto_fills_terlambat_past_0735(): void
    {
        $service = new PresensiSiswaService;
        $method = new \ReflectionMethod($service, 'normalizePayload');

        $autoLate = $method->invoke($service, [
            'anggota_kelas_id' => 5,
            'tanggal' => '2026-11-13',
            'jam_masuk' => '07:50',
            'status' => 'hadir',
            'keterlambatan_menit' => 0,
        ]);
        $this->assertSame('terlambat', $autoLate['status']);
        $this->assertSame(15, $autoLate['keterlambatan_menit']);

        $onTime = $method->invoke($service, [
            'anggota_kelas_id' => 5,
            'tanggal' => '2026-11-13',
            'jam_masuk' => '07:35',
            'status' => 'hadir',
            'keterlambatan_menit' => 0,
        ]);
        $this->assertSame('hadir', $onTime['status']);
        $this->assertSame(0, $onTime['keterlambatan_menit']);

        $manualOverride = $method->invoke($service, [
            'anggota_kelas_id' => 5,
            'tanggal' => '2026-11-13',
            'jam_masuk' => '07:50',
            'status' => 'terlambat',
            'keterlambatan_menit' => 20,
        ]);
        $this->assertSame('terlambat', $manualOverride['status']);
        $this->assertSame(20, $manualOverride['keterlambatan_menit']);

        $izinCleared = $method->invoke($service, [
            'anggota_kelas_id' => 5,
            'tanggal' => '2026-11-13',
            'jam_masuk' => '08:10',
            'status' => 'izin',
            'keterlambatan_menit' => 35,
        ]);
        $this->assertSame('izin', $izinCleared['status']);
        $this->assertNull($izinCleared['jam_masuk']);
        $this->assertSame(0, $izinCleared['keterlambatan_menit']);
    }

    public function test_edit_form_and_bulk_views_render_without_lazy_loading_and_single_create_routes_are_removed(): void
    {
        $this->assertFalse(Route::has('presensi-siswa.create'));
        $this->assertFalse(Route::has('presensi-siswa.store'));
        $this->assertTrue(Route::has('presensi-siswa.bulk'));
        $this->assertTrue(Route::has('presensi-siswa.bulk.store'));
        $this->assertTrue(Route::has('presensi-siswa.bulk.destroy'));
        $this->assertTrue(Route::has('presensi-siswa.edit'));
        $this->assertTrue(Route::has('presensi-siswa.rekap'));

        $tahunAjaran = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);
        $tahunAjaran->id = 1;
        $tahunAjaran->exists = true;

        $kelas = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => 1,
        ]);
        $kelas->id = 10;
        $kelas->exists = true;
        $kelas->setRelation('tahunAjaran', $tahunAjaran);
        $kelas->setRelation('waliKelas', null);

        $siswa = Siswa::make([
            'nis' => '2026001',
            'nama_lengkap' => 'Ahmad Fauzan',
        ]);
        $siswa->exists = true;

        $anggota = AnggotaKelas::make([
            'siswa_nis' => '2026001',
            'akademik_master_kelas_id' => 10,
        ]);
        $anggota->id = 5;
        $anggota->exists = true;
        $anggota->setRelation('siswa', $siswa);
        $anggota->setRelation('kelas', $kelas);

        $presensiSiswa = PresensiSiswa::make([
            'anggota_kelas_id' => 5,
            'tanggal' => '2026-11-13',
            'jam_masuk' => '07:45',
            'status' => 'terlambat',
            'keterlambatan_menit' => 10,
        ]);
        $presensiSiswa->id = 99;
        $presensiSiswa->exists = true;
        $presensiSiswa->setRelation('anggotaKelas', $anggota);

        Model::preventLazyLoading(true);

        try {
            $editHtml = view('admin.presensi.presensiSiswa.edit', [
                'presensiSiswa' => $presensiSiswa,
                'anggotaKelasOptions' => collect([$anggota]),
                'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
            ])->with('errors', new ViewErrorBag)->render();

            $bulkHtml = view('admin.presensi.presensiSiswa.bulk', [
                'kelasOptions' => collect([$kelas]),
                'selectedKelas' => $kelas,
                'selectedKelasId' => 10,
                'tanggal' => '2026-11-13',
                'tanggalLabel' => 'Jumat, 13 November 2026',
                'rows' => [
                    ['anggota' => $anggota, 'existing' => null],
                ],
                'existingCount' => 0,
                'statusOptions' => PresensiSiswa::STATUS_OPTIONS,
            ])->with('errors', new ViewErrorBag)->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringContainsString('07:35', $editHtml);
        $this->assertStringContainsString('applyAutoLateFromJam', $editHtml);
        $this->assertStringContainsString('Aksi Cepat', $bulkHtml);
        $this->assertStringContainsString('applyMassJamBtn', $bulkHtml);
        $this->assertStringContainsString('bulkStudentSearch', $bulkHtml);
        $this->assertStringContainsString('07:35', $bulkHtml);
        $this->assertStringContainsString('syncRowFromJam', $bulkHtml);
        $this->assertStringContainsString('data-has-existing="0"', $bulkHtml);
        $this->assertStringNotContainsString('id="bulkDestroyCurrentForm"', $bulkHtml);
    }

    public function test_tahun_ajaran_kelas_anggota_and_presensi_relations_accessors_and_scopes(): void
    {
        $ta = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => 'mid 1',
            'is_active' => 1,
        ]);

        $this->assertIsBool($ta->is_active);
        $this->assertTrue($ta->is_active);
        $this->assertSame('Mid Semester 1', $ta->semester_label);
        $this->assertSame('2026/2027 - Mid Semester 1', $ta->full_label);

        $this->assertSame([2026, 2027], TahunAjaran::parseYearRange('2026/2027'));
        $this->assertSame([2025, 2026], TahunAjaran::parseYearRange('2025 - 2026'));
        $this->assertNull(TahunAjaran::parseYearRange(''));
        $this->assertNull(TahunAjaran::parseYearRange(null));

        $this->assertInstanceOf(HasMany::class, (new TahunAjaran)->kelas());
        $this->assertInstanceOf(HasManyThrough::class, (new TahunAjaran)->anggotaKelas());
        $this->assertInstanceOf(HasManyThrough::class, (new Kelas)->presensiSiswa());
        $this->assertInstanceOf(HasMany::class, (new AnggotaKelas)->presensiSiswa());
        $this->assertInstanceOf(HasOneThrough::class, (new PresensiSiswa)->kelas());

        $activeQuery = TahunAjaran::query()->active();
        $this->assertStringContainsString('is_active', $activeQuery->toSql());
        $this->assertSame([true], $activeQuery->getBindings());

        $namaTaQuery = TahunAjaran::query()->forNamaTahunAjaran('2026/2027');
        $this->assertStringContainsString('nama_tahun_ajaran', $namaTaQuery->toSql());
        $this->assertSame(['2026/2027'], $namaTaQuery->getBindings());

        $kelasTaQuery = Kelas::query()->forTahunAjaran(5)->forNamaTahunAjaran('2026/2027', '1');
        $this->assertStringContainsString('akademik_master_tahun_ajaran_id', $kelasTaQuery->toSql());
        $this->assertSame([5, '2026/2027', '1'], $kelasTaQuery->getBindings());

        $anggotaQuery = AnggotaKelas::query()->forNamaTahunAjaran('2026/2027', '2');
        $this->assertSame(['2026/2027', '2'], $anggotaQuery->getBindings());

        $presensiQuery = PresensiSiswa::query()
            ->withMasterRelations()
            ->forKelas(10)
            ->forTahunAjaran(5)
            ->forNamaTahunAjaran('2026/2027', '1');

        $this->assertSame([10, 5, '2026/2027', '1'], $presensiQuery->getBindings());
        $this->assertArrayHasKey('anggotaKelas', $presensiQuery->getEagerLoads());
    }

    public function test_annual_report_aggregation_across_academic_year_semesters_excludes_weekends(): void
    {
        $taSem1 = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $taSem1->id = 1;
        $taSem1->exists = true;

        $taSem2 = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '2',
            'is_active' => false,
        ]);
        $taSem2->id = 2;
        $taSem2->exists = true;

        $kelasSd = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => 1,
        ]);
        $kelasSd->id = 10;
        $kelasSd->exists = true;
        $kelasSd->setRelation('tahunAjaran', $taSem1);
        $kelasSd->setRelation('waliKelas', null);

        $kelasSmp = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 2,
            'nama_kelas' => '7A',
            'jenjang_kelas' => 'SMP',
            'tingkatan_kelas' => 7,
        ]);
        $kelasSmp->id = 20;
        $kelasSmp->exists = true;
        $kelasSmp->setRelation('tahunAjaran', $taSem2);
        $kelasSmp->setRelation('waliKelas', null);

        $makeRecord = function (int $anggotaId, int $kelasId, string $tanggal, string $status, int $telat): PresensiSiswa {
            $rec = PresensiSiswa::make([
                'anggota_kelas_id' => $anggotaId,
                'tanggal' => $tanggal,
                'status' => $status,
                'keterlambatan_menit' => $telat,
            ]);
            $rec->kelas_id = $kelasId;

            return $rec;
        };

        $records = collect([
            // Semester 1 - Agustus: 1 hadir, 1 alpa => 50.0%
            $makeRecord(1, 10, '2026-08-10', 'hadir', 0),
            $makeRecord(1, 10, '2026-08-11', 'alpa', 0),
            // Semester 1 - November: 1 hadir (Jumat) + 1 Sabtu (harus di-skip!) => 100.0% (0% telat -> bulan terbaik)
            $makeRecord(1, 10, '2026-11-13', 'hadir', 0),
            $makeRecord(1, 10, '2026-11-14', 'alpa', 0), // Sabtu -> excluded
            // Semester 2 - Februari 2027: 1 terlambat (20 mnt), 1 hadir => 100.0% hadir efektif, 50.0% telat
            $makeRecord(2, 20, '2027-02-11', 'terlambat', 20),
            $makeRecord(2, 20, '2027-02-12', 'hadir', 0),
        ]);

        $service = new PresensiSiswaService;

        $monthResolver = new \ReflectionMethod($service, 'resolveAcademicMonthKeys');
        $sem1Months = $monthResolver->invoke($service, '2026/2027', '1');
        $sem2Months = $monthResolver->invoke($service, '2026/2027', '2');
        $this->assertSame(['2026-07', '2026-08', '2026-09', '2026-10', '2026-11', '2026-12'], $sem1Months);
        $this->assertSame(['2027-01', '2027-02', '2027-03', '2027-04', '2027-05', '2027-06'], $sem2Months);

        $method = new \ReflectionMethod($service, 'assembleAnnualReport');

        $report = $method->invoke(
            $service,
            '2026/2027',
            '',
            null,
            collect([$kelasSd, $kelasSmp]),
            $records,
            collect([10 => 20, 20 => 25]),
            collect(),
            false,
        );

        $this->assertSame('all_classes', $report['mode']);
        $this->assertSame('2026/2027', $report['period']['nama_tahun_ajaran']);
        $this->assertSame('Tahun Ajaran 2026/2027 • Semua Semester', $report['period']['label']);
        $this->assertCount(12, $report['monthlyRows']);
        $this->assertSame('2026-07', $report['monthlyRows'][0]['bulan']);
        $this->assertSame('2027-06', $report['monthlyRows'][11]['bulan']);

        $this->assertCount(2, $report['semesterRows']);
        $this->assertSame('1', $report['semesterRows'][0]['semester']);
        $this->assertSame('Semester 1', $report['semesterRows'][0]['semester_label']);
        $this->assertSame(3, $report['semesterRows'][0]['counts']['total_catatan']);
        $this->assertSame('2', $report['semesterRows'][1]['semester']);
        $this->assertSame('Semester 2', $report['semesterRows'][1]['semester_label']);
        $this->assertSame(2, $report['semesterRows'][1]['counts']['total_catatan']);
        $this->assertSame(50.0, $report['semesterRows'][1]['counts']['persentase_terlambat']);

        $this->assertSame(5, $report['summary']['total_catatan']);
        $this->assertSame(4, $report['summary']['hadir_efektif']);
        $this->assertSame(80.0, $report['summary']['persentase_hadir']);
        $this->assertSame(20.0, $report['summary']['persentase_terlambat']);
        $this->assertSame(3, $report['highlights']['bulan_aktif_count']);
        $this->assertSame('2026-11', $report['highlights']['bulan_terbaik']['bulan']);
        $this->assertSame('2027-02', $report['highlights']['bulan_telat_tertinggi']['bulan']);
        $this->assertSame(83.3, $report['highlights']['rata_kehadiran_bulanan']);
        $this->assertSame(20.0, $report['highlights']['rata_menit_telat']);
        $this->assertCount(2, $report['jenjangRows']);
        $this->assertSame('SD', $report['jenjangRows'][0]['jenjang']);
        $this->assertSame('SMP', $report['jenjangRows'][1]['jenjang']);
    }

    public function test_tahunan_view_renders_empty_all_classes_and_class_detail_without_lazy_loading(): void
    {
        $this->assertTrue(Route::has('presensi-siswa.tahunan'));
        $this->assertStringContainsString(route('presensi-siswa.tahunan'), view('layouts.sidebar')->render());

        $taSem1 = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '1',
            'is_active' => true,
        ]);
        $taSem1->id = 1;
        $taSem1->exists = true;

        $taSem2 = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => '2',
            'is_active' => false,
        ]);
        $taSem2->id = 2;
        $taSem2->exists = true;

        $kelasSd = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => 1,
        ]);
        $kelasSd->id = 10;
        $kelasSd->exists = true;
        $kelasSd->setRelation('tahunAjaran', $taSem1);
        $kelasSd->setRelation('waliKelas', null);

        $kelasSmp = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 2,
            'nama_kelas' => '7A',
            'jenjang_kelas' => 'SMP',
            'tingkatan_kelas' => 7,
        ]);
        $kelasSmp->id = 20;
        $kelasSmp->exists = true;
        $kelasSmp->setRelation('tahunAjaran', $taSem2);
        $kelasSmp->setRelation('waliKelas', null);

        $siswa = Siswa::make([
            'nis' => '2026001',
            'nama_lengkap' => 'Ahmad Fauzan',
        ]);
        $siswa->exists = true;

        $anggota = AnggotaKelas::make([
            'siswa_nis' => '2026001',
            'akademik_master_kelas_id' => 10,
        ]);
        $anggota->id = 5;
        $anggota->exists = true;
        $anggota->setRelation('siswa', $siswa);

        $counts = [
            'hadir' => 9,
            'terlambat' => 1,
            'izin' => 0,
            'sakit' => 0,
            'alpa' => 0,
            'total_telat_menit' => 15,
            'total_catatan' => 10,
            'hadir_efektif' => 10,
            'persentase_hadir' => 100.0,
            'persentase_tepat_waktu' => 90.0,
            'persentase_terlambat' => 10.0,
            'persentase_alpa' => 0.0,
            'predikat' => [
                'label' => 'Sangat Baik',
                'class' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            ],
        ];

        $monthlyRows = [
            [
                'bulan' => '2026-11',
                'label' => 'November 2026',
                'short_label' => 'Nov',
                'weekdays_count' => 21,
                'counts' => $counts,
                'shares' => ['hadir' => 90.0, 'terlambat' => 10.0, 'izin' => 0.0, 'sakit' => 0.0, 'alpa' => 0.0],
            ],
        ];

        $semesterRows = [
            [
                'tahun_ajaran_id' => 1,
                'semester' => '1',
                'semester_label' => 'Semester 1',
                'nama_tahun_ajaran' => '2026/2027',
                'kelas_count' => 1,
                'siswa_count' => 15,
                'counts' => $counts,
            ],
            [
                'tahun_ajaran_id' => 2,
                'semester' => '2',
                'semester_label' => 'Semester 2',
                'nama_tahun_ajaran' => '2026/2027',
                'kelas_count' => 1,
                'siswa_count' => 20,
                'counts' => $counts,
            ],
        ];

        $baseProps = [
            'namaTahunAjaranOptions' => collect(['2026/2027']),
            'activeTahunAjaran' => $taSem1,
            'semesterOptions' => TahunAjaran::SEMESTER_OPTIONS,
            'tahunAjaranOptions' => collect([$taSem1, $taSem2]),
            'jenjangOptions' => collect(['SD', 'SMP']),
            'kelasOptions' => collect([$kelasSd, $kelasSmp]),
            'period' => [
                'nama_tahun_ajaran' => '2026/2027',
                'semester' => '',
                'label' => 'Tahun Ajaran 2026/2027 • Semua Semester',
                'weekdays_count' => 261,
            ],
            'highlights' => [
                'total_hari_sekolah' => 261,
                'bulan_aktif_count' => 1,
                'rata_kehadiran_bulanan' => 100.0,
                'rata_menit_telat' => 15.0,
                'bulan_terbaik' => [
                    'bulan' => '2026-11',
                    'label' => 'November 2026',
                    'persentase_hadir' => 100.0,
                    'total_catatan' => 10,
                ],
                'bulan_telat_tertinggi' => [
                    'bulan' => '2026-11',
                    'label' => 'November 2026',
                    'terlambat' => 1,
                    'total_telat_menit' => 15,
                    'persentase_terlambat' => 10.0,
                ],
            ],
            'monthlyRows' => $monthlyRows,
        ];

        Model::preventLazyLoading(true);

        try {
            $htmlEmpty = view('admin.presensi.presensiSiswa.tahunan', array_merge($baseProps, [
                'hasFilter' => true,
                'hasExplicitFilter' => false,
                'mode' => 'all_classes',
                'selectedKelas' => null,
                'semesterRows' => [],
                'jenjangRows' => [],
                'classRows' => [],
                'studentRows' => [],
                'monthlyRows' => [],
                'summary' => array_merge(['siswa' => 0, 'jumlah_kelas' => 0], $counts, ['total_catatan' => 0]),
                'filters' => [
                    'nama_tahun_ajaran' => '2026/2027',
                    'semester' => '',
                    'jenjang' => null,
                    'kelas_id' => null,
                    'include_weekend' => false,
                ],
            ]))->render();

            $htmlAll = view('admin.presensi.presensiSiswa.tahunan', array_merge($baseProps, [
                'hasFilter' => true,
                'hasExplicitFilter' => true,
                'mode' => 'all_classes',
                'selectedKelas' => null,
                'semesterRows' => $semesterRows,
                'jenjangRows' => [
                    ['jenjang' => 'SD', 'kelas_count' => 1, 'siswa_count' => 15, 'counts' => $counts],
                    ['jenjang' => 'SMP', 'kelas_count' => 1, 'siswa_count' => 20, 'counts' => $counts],
                ],
                'classRows' => [
                    ['kelas' => $kelasSd, 'siswa_count' => 15, 'counts' => $counts],
                    ['kelas' => $kelasSmp, 'siswa_count' => 20, 'counts' => $counts],
                ],
                'studentRows' => [],
                'summary' => array_merge(['siswa' => 35, 'jumlah_kelas' => 2], $counts),
                'filters' => [
                    'nama_tahun_ajaran' => '2026/2027',
                    'semester' => '',
                    'jenjang' => null,
                    'kelas_id' => null,
                    'include_weekend' => false,
                ],
            ]))->render();

            $htmlStudent = view('admin.presensi.presensiSiswa.tahunan', array_merge($baseProps, [
                'hasFilter' => true,
                'hasExplicitFilter' => true,
                'mode' => 'class_detail',
                'selectedKelas' => $kelasSd,
                'semesterRows' => [],
                'jenjangRows' => [],
                'classRows' => [],
                'studentRows' => [
                    ['anggota' => $anggota, 'counts' => $counts],
                ],
                'summary' => array_merge(['siswa' => 1, 'jumlah_kelas' => 1], $counts),
                'filters' => [
                    'nama_tahun_ajaran' => '2026/2027',
                    'semester' => '1',
                    'jenjang' => 'SD',
                    'kelas_id' => 10,
                    'include_weekend' => false,
                ],
            ]))->render();
        } finally {
            Model::preventLazyLoading(false);
        }

        $this->assertStringNotContainsString('Pilih Filter Statistik Tahun Ajaran Terlebih Dahulu', $htmlEmpty);
        $this->assertStringNotContainsString('Filter Aktif', $htmlEmpty);
        $this->assertStringContainsString('tahunan-bento-top', $htmlEmpty);
        $this->assertStringContainsString('id="tahunanPanelRekap"', $htmlEmpty);
        $this->assertStringContainsString('Filter Aktif', $htmlAll);
        $this->assertStringContainsString('Perbandingan Per Semester', $htmlAll);
        $this->assertStringContainsString('Semester 1', $htmlAll);
        $this->assertStringContainsString('Semester 2', $htmlAll);
        $this->assertStringContainsString('% Kehadiran Tahunan', $htmlAll);
        $this->assertStringContainsString('% Keterlambatan Tahunan', $htmlAll);
        $this->assertStringNotContainsString('presensi-kpi-grid', $htmlAll);
        $this->assertStringNotContainsString('presensi-kpi-grid', $htmlStudent);
        $this->assertStringContainsString('Jenjang SD', $htmlAll);
        $this->assertStringContainsString('Jenjang SMP', $htmlAll);
        $this->assertStringContainsString('id="tahunanMonthlyTable"', $htmlAll);
        $this->assertStringContainsString('id="tahunanClassTable"', $htmlAll);
        $this->assertStringContainsString('tahunan-bento-top', $htmlAll);
        $this->assertStringContainsString('tahunan-bento-analytics', $htmlAll);
        $this->assertStringContainsString('id="tahunanPanelRekap"', $htmlAll);
        $this->assertStringContainsString('id="tahunanPanelBulanan"', $htmlAll);
        $this->assertStringContainsString('Ringkasan Performa', $htmlStudent);
        $this->assertStringContainsString('id="tahunanStudentTable"', $htmlStudent);
        $this->assertStringContainsString('Ahmad Fauzan', $htmlStudent);

        $tahunanHeroPos = strpos($htmlAll, 'class="tahunan-bento-top"');
        $tahunanFilterPos = strpos($htmlAll, 'Filter Statistik Presensi Tahun Ajaran');
        $tahunanAnalyticsPos = strpos($htmlAll, 'class="tahunan-bento-analytics"');
        $this->assertNotFalse($tahunanHeroPos);
        $this->assertNotFalse($tahunanFilterPos);
        $this->assertNotFalse($tahunanAnalyticsPos);
        $this->assertLessThan($tahunanFilterPos, $tahunanHeroPos);
        $this->assertLessThan($tahunanAnalyticsPos, $tahunanFilterPos);
    }

    public function test_service_defaults_to_active_tahun_ajaran_unless_explicitly_filtered(): void
    {
        $taInactive = TahunAjaran::make([
            'nama_tahun_ajaran' => '2025/2026',
            'semester' => 'Genap',
            'is_active' => false,
        ]);
        $taInactive->id = 1;

        $taActive = TahunAjaran::make([
            'nama_tahun_ajaran' => '2026/2027',
            'semester' => 'Ganjil',
            'is_active' => true,
        ]);
        $taActive->id = 2;

        $kelasLama = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 1,
            'nama_kelas' => '6A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => 6,
        ]);
        $kelasLama->id = 10;

        $kelasAktif = Kelas::make([
            'akademik_master_tahun_ajaran_id' => 2,
            'nama_kelas' => '1A',
            'jenjang_kelas' => 'SD',
            'tingkatan_kelas' => 1,
        ]);
        $kelasAktif->id = 20;

        $tahunAjaranOptions = collect([$taInactive, $taActive]);
        $allKelasOptions = collect([$kelasLama, $kelasAktif]);

        $service = new PresensiSiswaService;
        $resolveTa = new \ReflectionMethod($service, 'resolveDefaultTahunAjaranId');
        $resolveTa->setAccessible(true);
        $scopeKelas = new \ReflectionMethod($service, 'scopeKelasByTahunAjaran');
        $scopeKelas->setAccessible(true);

        $this->assertSame(
            2,
            $resolveTa->invoke($service, [], 0, $tahunAjaranOptions, $allKelasOptions)
        );
        $this->assertSame(
            0,
            $resolveTa->invoke($service, ['has_explicit_tahun_ajaran' => true, 'tahun_ajaran_id' => ''], 0, $tahunAjaranOptions, $allKelasOptions)
        );
        $this->assertSame(
            1,
            $resolveTa->invoke($service, ['has_explicit_tahun_ajaran' => false], 10, $tahunAjaranOptions, $allKelasOptions)
        );
        $this->assertSame(
            1,
            $resolveTa->invoke($service, [], 0, collect([$taInactive]), $allKelasOptions)
        );

        $scopedActive = $scopeKelas->invoke($service, $allKelasOptions, 2);
        $this->assertCount(1, $scopedActive);
        $this->assertSame(20, $scopedActive->first()->id);

        $scopedAll = $scopeKelas->invoke($service, $allKelasOptions, 0);
        $this->assertCount(2, $scopedAll);
    }

    public function test_bulk_destroy_validates_and_redirects_for_index_and_bulk_contexts(): void
    {
        $requestSchema = new PresensiSiswaBulkDestroyRequest;
        $rules = $requestSchema->rules();
        $this->assertContains('exists:akademik_master_kelas,id', $rules['kelas_id']);

        $validator = Validator::make([
            'kelas_id' => '',
            'tanggal' => 'bukan-tanggal',
        ], [
            'kelas_id' => ['required', 'integer'],
            'tanggal' => ['required', 'date'],
        ], $requestSchema->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('kelas_id', $validator->errors()->toArray());
        $this->assertArrayHasKey('tanggal', $validator->errors()->toArray());

        $service = new PresensiSiswaService;
        $this->assertSame(0, $service->deleteBulkByClassAndDate(0, '2026-11-13'));

        $mockService = $this->createMock(PresensiSiswaService::class);
        $mockService->expects($this->exactly(2))
            ->method('deleteBulkByClassAndDate')
            ->willReturnOnConsecutiveCalls(12, 0);

        $controller = new PresensiSiswaController($mockService);

        $indexReq = PresensiSiswaBulkDestroyRequest::create('/presensi/presensi-siswa/bulk', 'DELETE', [
            'kelas_id' => 10,
            'tanggal' => '2026-11-13',
            'redirect_tahun_ajaran_id' => '2',
        ]);
        $indexReq->setValidator(Validator::make($indexReq->all(), [
            'kelas_id' => ['required', 'integer'],
            'tanggal' => ['required', 'date'],
        ]));

        $indexResp = $controller->bulkDestroy($indexReq);
        $this->assertTrue($indexResp->isRedirect(route('presensi-siswa.index', [
            'kelas_id' => 10,
            'bulan' => '2026-11',
            'tahun_ajaran_id' => '2',
        ])));
        $this->assertStringContainsString('12 data presensi', (string) $indexResp->getSession()->get('success'));

        $bulkReq = PresensiSiswaBulkDestroyRequest::create('/presensi/presensi-siswa/bulk', 'DELETE', [
            'kelas_id' => 10,
            'tanggal' => '2026-11-13',
            'redirect_to' => 'bulk',
        ]);
        $bulkReq->setValidator(Validator::make($bulkReq->all(), [
            'kelas_id' => ['required', 'integer'],
            'tanggal' => ['required', 'date'],
        ]));

        $bulkResp = $controller->bulkDestroy($bulkReq);
        $this->assertTrue($bulkResp->isRedirect(route('presensi-siswa.bulk', [
            'kelas_id' => 10,
            'tanggal' => '2026-11-13',
        ])));
        $this->assertStringContainsString('Tidak ada data presensi', (string) $bulkResp->getSession()->get('warning'));
    }
}
