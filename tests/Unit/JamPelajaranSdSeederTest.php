<?php

namespace Tests\Unit;

use App\Models\Admin\Pelajaran\JamPelajaran;
use Database\Seeders\JamPelajaranSdSeeder;
use Tests\TestCase;

class JamPelajaranSdSeederTest extends TestCase
{
    public function test_sd_slots_match_exact_contiguous_schedule_and_model_contract(): void
    {
        $slots = JamPelajaranSdSeeder::sdSlots();
        $fillable = (new JamPelajaran)->getFillable();

        $this->assertCount(17, $slots);

        $expectedRanges = [
            1 => ['07:15 - 07:30', 15],
            2 => ['07:30 - 08:05', 35],
            3 => ['08:05 - 08:40', 35],
            4 => ['08:40 - 08:45', 5],
            5 => ['08:45 - 09:15', 30],
            6 => ['09:15 - 09:50', 35],
            7 => ['09:50 - 10:25', 35],
            8 => ['10:25 - 11:00', 35],
            9 => ['11:00 - 11:35', 35],
            10 => ['11:35 - 12:10', 35],
            11 => ['12:10 - 12:45', 35],
            12 => ['12:45 - 13:20', 35],
            13 => ['13:20 - 13:55', 35],
            14 => ['13:55 - 14:00', 5],
            15 => ['14:00 - 14:35', 35],
            16 => ['14:35 - 15:10', 35],
            17 => ['15:10 - 15:45', 35],
        ];

        $previousEnd = null;

        foreach ($slots as $index => $slot) {
            foreach (array_keys($slot) as $column) {
                $this->assertContains($column, $fillable);
            }

            $expectedJamKe = $index + 1;
            $this->assertSame('SD', $slot['unit']);
            $this->assertSame($expectedJamKe, $slot['jam_ke']);

            if ($previousEnd !== null) {
                $this->assertSame($previousEnd, $slot['jam_mulai']);
            }
            $previousEnd = $slot['jam_selesai'];

            $model = JamPelajaran::make($slot);
            $this->assertSame($expectedRanges[$expectedJamKe][0], $model->rentang_waktu);
            $this->assertSame($expectedRanges[$expectedJamKe][1], $model->durasi_menit);
        }

        $databaseSeederSource = (string) file_get_contents(database_path('seeders/DatabaseSeeder.php'));
        $this->assertStringContainsString('JamPelajaranSdSeeder::class', $databaseSeederSource);
    }
}
