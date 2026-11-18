<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DaftarJilidSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();
        
        $data = [
            // Kategori Jilid Standar
            ['jilid_latin' => 'Jilid 1', 'jilid_arab' => 'المجلد ١', 'jenis' => 'halaman', 'urutan' => 1],
            ['jilid_latin' => 'Jilid 2', 'jilid_arab' => 'المجلد ٢', 'jenis' => 'halaman', 'urutan' => 2],
            ['jilid_latin' => 'Jilid 3', 'jilid_arab' => 'المجلد ٣', 'jenis' => 'halaman', 'urutan' => 3],
            ['jilid_latin' => 'Jilid 4', 'jilid_arab' => 'المجلد ٤', 'jenis' => 'halaman', 'urutan' => 4],

            // Kategori Talaqqi
            ['jilid_latin' => 'Talaqqi Juz 30', 'jilid_arab' => 'تلقي جوز ٣٠', 'jenis' => 'quran', 'urutan' => 5],
            ['jilid_latin' => 'Talaqqi Juz 1-5', 'jilid_arab' => 'تلقي جوز ١-٥', 'jenis' => 'quran', 'urutan' => 6],

            // Kategori Gharib
            ['jilid_latin' => 'Gharib 1', 'jilid_arab' => 'غريب ١', 'jenis' => 'halaman', 'urutan' => 7],
            ['jilid_latin' => 'Gharib 2', 'jilid_arab' => 'غريب ٢', 'jenis' => 'halaman', 'urutan' => 8],

            // Kategori Tajwid
            ['jilid_latin' => 'Tajwid 1', 'jilid_arab' => 'تجويد ١', 'jenis' => 'halaman', 'urutan' => 9],
            ['jilid_latin' => 'Tajwid 2', 'jilid_arab' => 'تجويد ٢', 'jenis' => 'halaman', 'urutan' => 10],

            // Kategori Tilawah
            ['jilid_latin' => 'Tilawah', 'jilid_arab' => 'تلاوة', 'jenis' => 'quran', 'urutan' => 11],

            // Kategori Review
            ['jilid_latin' => 'Review Jilid 1', 'jilid_arab' => 'مراجعة المجلد ١', 'jenis' => 'halaman', 'urutan' => 1],
            ['jilid_latin' => 'Review Jilid 2', 'jilid_arab' => 'مراجعة المجلد ٢', 'jenis' => 'halaman', 'urutan' => 2],
            ['jilid_latin' => 'Review Jilid 3', 'jilid_arab' => 'مراجعة المجلد ٣', 'jenis' => 'halaman', 'urutan' => 3],
            ['jilid_latin' => 'Review Jilid 4', 'jilid_arab' => 'مراجعة المجلد ٤', 'jenis' => 'halaman', 'urutan' => 4],
        ];

        $insertData = array_map(function ($item) use ($now) {
            return array_merge($item, [
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }, $data);

        DB::table('akademik_master_t2q_daftar_jilid')->insert($insertData);
    }
}