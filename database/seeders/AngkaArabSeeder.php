<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AngkaArabSeeder extends Seeder
{
    public function run(): void
    {
        $western = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
        $eastern = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
        $now = Carbon::now();
        $data = [];

        for ($i = 1; $i <= 300; $i++) {
            $data[] = [
                'angka_latin' => $i,
                'angka_arab'  => str_replace($western, $eastern, (string)$i),
                'created_at'  => $now,
                'updated_at'  => $now,
            ];
        }
        foreach (array_chunk($data, 100) as $chunk) {
            DB::table('akademik_master_t2q_angka_arab')->insert($chunk);
        }
    }
}