<?php

namespace Database\Seeders;

use App\Models\Admin\Pelajaran\JamPelajaran;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JamPelajaranSdSeeder extends Seeder
{
    /**
     * Daftar 17 slot jam pelajaran standar unit SD (07:15 - 15:45).
     *
     * @return list<array{
     *     unit: string,
     *     jam_ke: int,
     *     jam_mulai: string,
     *     jam_selesai: string,
     *     keterangan: ?string
     * }>
     */
    public static function sdSlots(): array
    {
        return [
            ['unit' => 'SD', 'jam_ke' => 1, 'jam_mulai' => '07:15:00', 'jam_selesai' => '07:30:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 2, 'jam_mulai' => '07:30:00', 'jam_selesai' => '08:05:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 3, 'jam_mulai' => '08:05:00', 'jam_selesai' => '08:40:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 4, 'jam_mulai' => '08:40:00', 'jam_selesai' => '08:45:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 5, 'jam_mulai' => '08:45:00', 'jam_selesai' => '09:15:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 6, 'jam_mulai' => '09:15:00', 'jam_selesai' => '09:50:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 7, 'jam_mulai' => '09:50:00', 'jam_selesai' => '10:25:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 8, 'jam_mulai' => '10:25:00', 'jam_selesai' => '11:00:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 9, 'jam_mulai' => '11:00:00', 'jam_selesai' => '11:35:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 10, 'jam_mulai' => '11:35:00', 'jam_selesai' => '12:10:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 11, 'jam_mulai' => '12:10:00', 'jam_selesai' => '12:45:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 12, 'jam_mulai' => '12:45:00', 'jam_selesai' => '13:20:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 13, 'jam_mulai' => '13:20:00', 'jam_selesai' => '13:55:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 14, 'jam_mulai' => '13:55:00', 'jam_selesai' => '14:00:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 15, 'jam_mulai' => '14:00:00', 'jam_selesai' => '14:35:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 16, 'jam_mulai' => '14:35:00', 'jam_selesai' => '15:10:00', 'keterangan' => null],
            ['unit' => 'SD', 'jam_ke' => 17, 'jam_mulai' => '15:10:00', 'jam_selesai' => '15:45:00', 'keterangan' => null],
        ];
    }

    /**
     * Menyemai slot jam pelajaran unit SD secara idempoten dalam satu query batch.
     */
    public function run(): void
    {
        $now = now();
        $rows = array_map(
            static fn (array $slot): array => [
                ...$slot,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            self::sdSlots()
        );

        DB::transaction(static function () use ($rows): void {
            JamPelajaran::query()->upsert(
                $rows,
                ['unit', 'jam_ke'],
                ['jam_mulai', 'jam_selesai', 'keterangan', 'updated_at']
            );
        });
    }
}
