<?php

namespace Database\Seeders;

use App\Models\Admin\DataMaster\KomentarRapor;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class KomentarRaporSeeder extends Seeder
{
    /**
     * Mengisi template bank komentar rapor Wali Kelas / Pendamping tanpa duplikasi berdasarkan judul.
     */
    public function run(): void
    {
        $now = now();
        $rows = [];

        foreach (KomentarRapor::DEFAULT_TEMPLATES as $template) {
            $rows[] = [
                'judul' => $template['judul'],
                'kategori' => $template['kategori'],
                'jenjang' => $template['jenjang'],
                'isi_komentar' => $template['isi_komentar'],
                'is_active' => true,
                'urutan' => $template['urutan'],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows !== []) {
            DB::table('akademik_master_komentar_rapor')->upsert(
                $rows,
                ['judul'],
                ['kategori', 'jenjang', 'isi_komentar', 'is_active', 'urutan', 'updated_at']
            );
        }
    }
}
