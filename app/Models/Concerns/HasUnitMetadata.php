<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Casts\Attribute;

trait HasUnitMetadata
{
    /**
     * Membandingkan urutan unit sesuai prioritas UNIT_OPTIONS pada model.
     */
    public static function compareUnitOrder(string $a, string $b): int
    {
        $unitOptions = defined(static::class.'::UNIT_OPTIONS') ? static::UNIT_OPTIONS : [];
        $order = array_map('strtoupper', $unitOptions);
        $normA = strtoupper(trim($a));
        $normB = strtoupper(trim($b));

        $posA = array_search($normA, $order, true);
        $posB = array_search($normB, $order, true);

        if ($posA !== false && $posB !== false) {
            return $posA <=> $posB;
        }

        if ($posA !== false) {
            return -1;
        }

        if ($posB !== false) {
            return 1;
        }

        return $normA <=> $normB;
    }

    /**
     * Kelas badge Bootstrap untuk label unit pendidikan.
     */
    protected function unitBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match (strtoupper(trim((string) $this->unit))) {
            'SEMUA UNIT', 'UMUM' => 'badge-dark',
            'PG' => 'badge-info',
            'SD' => 'badge-success',
            'SMP' => 'badge-primary',
            'TK', 'PAUD' => 'badge-warning text-dark',
            'SMA', 'SMK' => 'badge-info',
            default => 'badge-secondary',
        });
    }
}
