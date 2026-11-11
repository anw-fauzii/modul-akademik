<?php

namespace App\View\Composers;

use App\Models\Admin\DataMaster\Kategori;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SidebarComposer
{
    public const CACHE_KEY = 'sidebar_kategori_v3';

    public function compose(View $view): void
    {
        $view->with('sidebarKategori', Cache::remember(self::CACHE_KEY, 3600, function () {
            $categories = Kategori::query()
                ->withCount('karyawan')
                ->orderBy('nama_kategori')
                ->get();

            $grouped = [];

            foreach ($categories as $k) {
                $unitName = self::extractUnitName($k->nama_kategori);

                if (! isset($grouped[$unitName])) {
                    $grouped[$unitName] = [
                        'nama'         => $unitName,
                        'jumlah'       => 0,
                        'kategori_ids' => [],
                    ];
                }

                $grouped[$unitName]['jumlah'] += $k->karyawan_count;
                $grouped[$unitName]['kategori_ids'][] = $k->id;
            }

            return array_values($grouped);
        }));
    }

    public static function extractUnitName(string $nama): string
    {
        $cleaned = preg_replace('/\s*[-–—]?\s*(?:pegawai\s+)?(?:tetap|kontrak|honor(?:er)?)\s*$/i', '', trim($nama));
        return $cleaned ?: trim($nama);
    }
}
