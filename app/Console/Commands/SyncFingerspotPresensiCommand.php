<?php

namespace App\Console\Commands;

use App\Services\Admin\Presensi\FingerspotSyncService;
use Illuminate\Console\Command;
use Illuminate\Validation\ValidationException;

class SyncFingerspotPresensiCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'presensi:sync-fingerspot
        {--start-date= : Tanggal mulai (YYYY-MM-DD), default hari ini}
        {--end-date= : Tanggal akhir (YYYY-MM-DD), default mengikuti tanggal mulai}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sinkronisasi log kehadiran siswa dari API Fingerspot ke tabel presensi siswa.';

    public function handle(FingerspotSyncService $syncService): int
    {
        try {
            $startDate = $this->option('start-date');
            $endDate = $this->option('end-date');

            $result = $syncService->syncDateRange(
                is_string($startDate) && trim($startDate) !== '' ? trim($startDate) : null,
                is_string($endDate) && trim($endDate) !== '' ? trim($endDate) : null,
            );
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $messages) {
                foreach ($messages as $message) {
                    $this->error($message);
                }
            }

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Sinkronisasi Fingerspot (%s s/d %s): %d disimpan (%d hadir, %d terlambat), %d sudah tercatat, %d terkunci, %d mesin sukses dari %d.',
            $result['start_date'],
            $result['end_date'],
            $result['inserted'],
            $result['hadir'],
            $result['terlambat'],
            $result['skipped_existing'],
            $result['skipped_locked'],
            $result['clouds_success'],
            $result['clouds_total']
        ));

        if ($result['failed_clouds'] !== []) {
            $failedList = implode(', ', array_column($result['failed_clouds'], 'cloud_id'));
            $this->warn("Mesin gagal diambil datanya: {$failedList}");
        }

        if ($result['unmatched_pins'] !== []) {
            $this->warn('PIN/NIS belum terpetakan ke anggota kelas aktif: '.count($result['unmatched_pins']));
        }

        return $result['clouds_success'] === 0 ? self::FAILURE : self::SUCCESS;
    }
}

