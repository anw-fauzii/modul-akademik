<?php

namespace App\Models\Admin\DataMaster;

use Database\Factories\Admin\DataMaster\AngkaArabFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Attributes\UseFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

#[Fillable([
    'angka_latin',
    'angka_arab',
])]
#[Table('akademik_master_t2q_angka_arab')]
#[UseFactory(AngkaArabFactory::class)]
class AngkaArab extends Model
{
    /** @use HasFactory<AngkaArabFactory> */
    use HasFactory;

    /**
     * Mencari entri angka Arab dari koleksi di memori berdasarkan angka Latin (anti-N+1).
     *
     * @param  Collection<int, self>  $angkaList
     */
    public static function resolveFromCollection(Collection $angkaList, int|string|null $angkaLatin): ?self
    {
        $needle = trim((string) ($angkaLatin ?? ''));
        if ($needle === '') {
            return null;
        }

        return $angkaList->first(
            static fn (self $item): bool => trim((string) $item->angka_latin) === $needle
        );
    }

    /**
     * Mengonversi angka Latin menjadi padanan angka Arab dari koleksi master.
     *
     * @param  Collection<int, self>  $angkaList
     */
    public static function formatToArabFromCollection(Collection $angkaList, int|string|null $angkaLatin): string
    {
        $needle = trim((string) ($angkaLatin ?? ''));
        if ($needle === '') {
            return '-';
        }

        $matched = self::resolveFromCollection($angkaList, $needle);

        return filled($matched?->angka_arab) ? (string) $matched->angka_arab : $needle;
    }
}
