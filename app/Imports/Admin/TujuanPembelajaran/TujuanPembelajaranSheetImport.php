<?php

namespace App\Imports\Admin\TujuanPembelajaran;

use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Pelajaran\MataPelajaran;
use App\Models\Admin\TujuanPembelajaran\TujuanPembelajaran;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class TujuanPembelajaranSheetImport implements SkipsEmptyRows, ToCollection, WithHeadingRow
{
    public int $createdCount = 0;

    public int $updatedCount = 0;

    /**
     * @var list<string>
     */
    public array $errors = [];

    public function __construct(
        protected ?int $defaultTahunAjaranId = null
    ) {}

    /**
     * Memproses baris Excel secara batch dengan preload master untuk mencegah N+1.
     *
     * @param  Collection<array-key, mixed>  $collection
     */
    public function collection(Collection $collection): void
    {
        $this->createdCount = 0;
        $this->updatedCount = 0;
        $this->errors = [];

        if ($collection->isEmpty()) {
            $this->errors[] = 'File Excel tidak memiliki baris data TP untuk diimpor.';

            return;
        }

        $tahunAjaranLookup = $this->buildTahunAjaranLookup();
        $mapelLookup = $this->buildMapelLookup();

        $preparedRows = [];
        $seenCompositeKeys = [];

        foreach ($collection as $index => $rawRow) {
            $rowNumber = (int) $index + 2;
            $row = $this->normalizeRowKeys($rawRow);

            if ($this->isCompletelyEmptyRow($row)) {
                continue;
            }

            $resolvedTa = $this->resolveTahunAjaran($row, $tahunAjaranLookup);
            if ($resolvedTa === null) {
                $this->errors[] = "Baris {$rowNumber}: Tahun Ajaran / Semester tidak valid atau tidak ditemukan.";

                continue;
            }

            $resolvedMapel = $this->resolveMataPelajaran($row, $mapelLookup);
            if ($resolvedMapel === null) {
                $this->errors[] = "Baris {$rowNumber}: Mata Pelajaran (Unit + Kode Mapel / Nama) tidak ditemukan.";

                continue;
            }

            $tingkatan = $this->collapseString($this->pick($row, ['tingkatan_kelas', 'tingkat', 'kelas']));
            if ($tingkatan === '') {
                $this->errors[] = "Baris {$rowNumber}: Tingkatan Kelas wajib diisi.";

                continue;
            }
            if (mb_strlen($tingkatan) > 20) {
                $this->errors[] = "Baris {$rowNumber}: Tingkatan Kelas maksimal 20 karakter.";

                continue;
            }

            $kodeTp = strtoupper(trim($this->pick($row, ['kode_tp', 'kode'])));
            if ($kodeTp === '') {
                $this->errors[] = "Baris {$rowNumber}: Kode TP wajib diisi.";

                continue;
            }
            if (mb_strlen($kodeTp) > 30) {
                $this->errors[] = "Baris {$rowNumber}: Kode TP maksimal 30 karakter.";

                continue;
            }

            $tujuanPembelajaran = trim($this->pick($row, ['tujuan_pembelajaran', 'rumusan_tp', 'tp']));
            if ($tujuanPembelajaran === '') {
                $this->errors[] = "Baris {$rowNumber}: Rumusan Tujuan Pembelajaran wajib diisi.";

                continue;
            }

            $rawFase = trim($this->pick($row, ['fase']));
            $fase = $this->resolveFase($rawFase, $resolvedMapel['unit'], $tingkatan);
            if ($fase === false) {
                $this->errors[] = "Baris {$rowNumber}: Fase harus bernilai Fondasi, A, B, atau C.";

                continue;
            }

            $rawUrutan = trim($this->pick($row, ['urutan']));
            $urutan = $rawUrutan === '' ? 1 : (ctype_digit($rawUrutan) ? (int) $rawUrutan : 0);
            if ($urutan < 1 || $urutan > 999) {
                $this->errors[] = "Baris {$rowNumber}: Urutan harus berupa angka antara 1 sampai 999.";

                continue;
            }

            $elemen = $this->nullableCollapse($this->pick($row, ['elemen']));
            $lingkupMateri = $this->nullableCollapse($this->pick($row, ['lingkup_materi', 'materi']));
            $status = $this->parseStatus($this->pick($row, ['status']));

            $compositeKey = implode('|', [
                $resolvedTa,
                $resolvedMapel['id'],
                $tingkatan,
                $kodeTp,
            ]);

            if (isset($seenCompositeKeys[$compositeKey])) {
                $this->errors[] = "Baris {$rowNumber}: Duplikat Kode TP \"{$kodeTp}\" untuk semester, mapel, dan tingkatan yang sama di dalam file.";

                continue;
            }
            $seenCompositeKeys[$compositeKey] = true;

            $now = now();
            $preparedRows[] = [
                'akademik_master_tahun_ajaran_id' => $resolvedTa,
                'akademik_pelajaran_mata_pelajaran_id' => $resolvedMapel['id'],
                'tingkatan_kelas' => $tingkatan,
                'fase' => $fase,
                'elemen' => $elemen !== null ? mb_substr($elemen, 0, 150) : null,
                'kode_tp' => $kodeTp,
                'tujuan_pembelajaran' => mb_substr($tujuanPembelajaran, 0, 2000),
                'lingkup_materi' => $lingkupMateri !== null ? mb_substr($lingkupMateri, 0, 255) : null,
                'urutan' => $urutan,
                'status' => $status,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($this->errors !== []) {
            return;
        }

        if ($preparedRows === []) {
            $this->errors[] = 'Tidak ada data valid yang ditemukan untuk diimpor.';

            return;
        }

        $taIds = array_values(array_unique(array_column($preparedRows, 'akademik_master_tahun_ajaran_id')));
        $existingRows = TujuanPembelajaran::query()
            ->select([
                'akademik_master_tahun_ajaran_id',
                'akademik_pelajaran_mata_pelajaran_id',
                'tingkatan_kelas',
                'kode_tp',
            ])
            ->whereIn('akademik_master_tahun_ajaran_id', $taIds)
            ->get();

        $existingKeys = [];
        foreach ($existingRows as $existing) {
            $existingKey = implode('|', [
                $existing->akademik_master_tahun_ajaran_id,
                $existing->akademik_pelajaran_mata_pelajaran_id,
                $existing->tingkatan_kelas,
                strtoupper((string) $existing->kode_tp),
            ]);
            $existingKeys[$existingKey] = true;
        }

        $newCount = 0;
        $updateCount = 0;
        foreach ($preparedRows as $payload) {
            $key = implode('|', [
                $payload['akademik_master_tahun_ajaran_id'],
                $payload['akademik_pelajaran_mata_pelajaran_id'],
                $payload['tingkatan_kelas'],
                $payload['kode_tp'],
            ]);

            if (isset($existingKeys[$key])) {
                $updateCount++;
            } else {
                $newCount++;
            }
        }

        DB::transaction(function () use ($preparedRows): void {
            foreach (array_chunk($preparedRows, 250) as $chunk) {
                TujuanPembelajaran::upsert(
                    $chunk,
                    [
                        'akademik_master_tahun_ajaran_id',
                        'akademik_pelajaran_mata_pelajaran_id',
                        'tingkatan_kelas',
                        'kode_tp',
                    ],
                    [
                        'fase',
                        'elemen',
                        'tujuan_pembelajaran',
                        'lingkup_materi',
                        'urutan',
                        'status',
                        'updated_at',
                    ]
                );
            }
        });

        $this->createdCount = $newCount;
        $this->updatedCount = $updateCount;
    }

    /**
     * @return array{byKey: array<string, int>, byId: array<int, int>}
     */
    private function buildTahunAjaranLookup(): array
    {
        $items = TahunAjaran::query()
            ->select(['id', 'nama_tahun_ajaran', 'semester'])
            ->get();

        $byKey = [];
        $byId = [];

        foreach ($items as $ta) {
            $byId[(int) $ta->id] = (int) $ta->id;
            $nama = strtolower(trim((string) $ta->nama_tahun_ajaran));
            $semester = strtolower(trim((string) $ta->semester));
            $byKey["{$nama}|{$semester}"] = (int) $ta->id;
        }

        return ['byKey' => $byKey, 'byId' => $byId];
    }

    /**
     * @return array{
     *     byUnitAndCode: array<string, array{id: int, unit: string}>,
     *     byUnitAndName: array<string, array{id: int, unit: string}>,
     *     byUniqueCode: array<string, array{id: int, unit: string}|null>
     * }
     */
    private function buildMapelLookup(): array
    {
        $items = MataPelajaran::query()
            ->select(['id', 'unit', 'singkatan', 'nama_mata_pelajaran'])
            ->get();

        $byUnitAndCode = [];
        $byUnitAndName = [];
        $byUniqueCode = [];

        foreach ($items as $mp) {
            $unit = strtoupper(trim((string) $mp->unit));
            $code = strtoupper(trim((string) $mp->singkatan));
            $name = strtolower(trim((string) $mp->nama_mata_pelajaran));
            $entry = ['id' => (int) $mp->id, 'unit' => $unit];

            if ($code !== '') {
                $byUnitAndCode["{$unit}|{$code}"] = $entry;

                if (! array_key_exists($code, $byUniqueCode)) {
                    $byUniqueCode[$code] = $entry;
                } else {
                    $byUniqueCode[$code] = null;
                }
            }

            if ($name !== '') {
                $byUnitAndName["{$unit}|{$name}"] = $entry;
            }
        }

        return [
            'byUnitAndCode' => $byUnitAndCode,
            'byUnitAndName' => $byUnitAndName,
            'byUniqueCode' => $byUniqueCode,
        ];
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{byKey: array<string, int>, byId: array<int, int>}  $lookup
     */
    private function resolveTahunAjaran(array $row, array $lookup): ?int
    {
        $rawTa = strtolower(trim($this->pick($row, ['tahun_ajaran'])));
        $rawSemester = strtolower(trim($this->pick($row, ['semester'])));

        if ($rawTa === '' && $rawSemester === '') {
            if ($this->defaultTahunAjaranId !== null && isset($lookup['byId'][$this->defaultTahunAjaranId])) {
                return $this->defaultTahunAjaranId;
            }

            return null;
        }

        if ($rawSemester !== '') {
            $rawSemester = trim((string) preg_replace('/^semester\s+/i', '', $rawSemester));
            $rawSemester = match ($rawSemester) {
                'ganjil' => '1',
                'genap' => '2',
                default => $rawSemester,
            };
        } elseif (str_contains($rawTa, '-')) {
            $parts = explode('-', $rawTa, 2);
            $rawTa = trim($parts[0]);
            $rawSemester = trim((string) preg_replace('/^semester\s+/i', '', $parts[1]));
        }

        return $lookup['byKey']["{$rawTa}|{$rawSemester}"] ?? null;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  array{
     *     byUnitAndCode: array<string, array{id: int, unit: string}>,
     *     byUnitAndName: array<string, array{id: int, unit: string}>,
     *     byUniqueCode: array<string, array{id: int, unit: string}|null>
     * }  $lookup
     * @return array{id: int, unit: string}|null
     */
    private function resolveMataPelajaran(array $row, array $lookup): ?array
    {
        $unit = strtoupper(trim($this->pick($row, ['unit'])));
        $code = strtoupper(trim($this->pick($row, ['kode_mapel', 'singkatan', 'mapel'])));
        $name = strtolower(trim($this->pick($row, ['mata_pelajaran', 'nama_mata_pelajaran'])));

        if ($unit !== '') {
            if ($code !== '' && isset($lookup['byUnitAndCode']["{$unit}|{$code}"])) {
                return $lookup['byUnitAndCode']["{$unit}|{$code}"];
            }
            if ($name !== '' && isset($lookup['byUnitAndName']["{$unit}|{$name}"])) {
                return $lookup['byUnitAndName']["{$unit}|{$name}"];
            }

            return null;
        }

        if ($code !== '' && isset($lookup['byUniqueCode'][$code]) && $lookup['byUniqueCode'][$code] !== null) {
            return $lookup['byUniqueCode'][$code];
        }

        return null;
    }

    private function resolveFase(string $rawFase, string $unit, string $tingkatan): string|false
    {
        if ($rawFase === '') {
            return TujuanPembelajaran::resolveDefaultFase($unit, $tingkatan);
        }

        $clean = trim((string) preg_replace('/^fase\s+/i', '', $rawFase));
        if (strcasecmp($clean, 'Fondasi') === 0) {
            return 'Fondasi';
        }

        $upper = strtoupper($clean);

        return array_key_exists($upper, TujuanPembelajaran::FASE_OPTIONS) ? $upper : false;
    }

    private function parseStatus(string $rawStatus): bool
    {
        $normalized = strtolower(trim($rawStatus));
        if ($normalized === '') {
            return true;
        }

        return ! in_array($normalized, ['0', 'nonaktif', 'tidak', 'false', 'no', 'arsip'], true);
    }

    /**
     * @return array<string, mixed>
     */
    private function normalizeRowKeys(mixed $rawRow): array
    {
        $items = $rawRow instanceof Collection ? $rawRow->all() : (array) $rawRow;
        $normalized = [];

        foreach ($items as $key => $value) {
            $slug = Str::slug((string) $key, '_');
            if ($slug !== '') {
                $normalized[$slug] = $value;
            }
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $keys
     */
    private function pick(array $row, array $keys): string
    {
        foreach ($keys as $key) {
            if (array_key_exists($key, $row) && $row[$key] !== null) {
                return (string) $row[$key];
            }
        }

        return '';
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isCompletelyEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (trim((string) ($value ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private function collapseString(string $value): string
    {
        return (string) preg_replace('/\s+/', ' ', trim($value));
    }

    private function nullableCollapse(string $value): ?string
    {
        $collapsed = $this->collapseString($value);

        return $collapsed === '' ? null : $collapsed;
    }
}
