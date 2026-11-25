<?php

namespace App\Http\Requests\Admin\DataMaster\Concerns;

use App\Models\Admin\DataMaster\Siswa;
use App\Support\Sqid;
use Illuminate\Database\Eloquent\Model;

trait NormalizesMutasiRequestInputs
{
    /**
     * Mendekode input array/skalar NIS siswa (SQID atau NIS mentah) menjadi daftar NIS unik bersih.
     *
     * @return list<string>
     */
    protected function normalizeSiswaNisList(mixed $rawInput): array
    {
        if (! is_array($rawInput)) {
            $rawInput = $rawInput !== null && $rawInput !== '' ? [$rawInput] : [];
        }

        $result = [];
        foreach ($rawInput as $item) {
            if (! is_string($item) && ! is_int($item)) {
                continue;
            }
            $trimmed = trim((string) $item);
            if ($trimmed === '') {
                continue;
            }
            $decoded = Sqid::decodeFor(Siswa::class, $trimmed);
            $result[] = $decoded !== null ? (string) $decoded : $trimmed;
        }

        return array_values(array_unique($result));
    }

    /**
     * Mendekode kunci ID model (SQID atau integer mentah) secara aman sebelum validasi.
     *
     * @param  class-string<Model>  $modelClass
     */
    protected function decodeIntegerIdOrRaw(mixed $rawValue, string $modelClass): mixed
    {
        if ($rawValue === null) {
            return null;
        }

        $trimmed = trim((string) $rawValue);
        if ($trimmed === '') {
            return null;
        }

        $decoded = Sqid::decodeFor($modelClass, $trimmed);
        if ($decoded !== null) {
            return (int) $decoded;
        }

        if (ctype_digit($trimmed) && (int) $trimmed > 0) {
            return (int) $trimmed;
        }

        return $trimmed;
    }

    /**
     * Merapikan spasi berlebih pada teks satu baris.
     */
    protected function collapseWhitespace(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if ($trimmed === '') {
            return null;
        }

        return trim((string) preg_replace('/\s+/', ' ', $trimmed));
    }
}

