<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\Sekolah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SekolahService
{
    private const array INDEX_COLUMNS = [
        'id',
        'jenjang_kelas',
        'nama_yayasan',
        'nama_sekolah',
        'npsn',
        'nss',
        'alamat',
        'rt',
        'rw',
        'kelurahan',
        'kecamatan',
        'kabupaten',
        'provinsi',
        'kode_pos',
        'telepon',
        'email',
        'website',
        'status',
    ];

    /**
     * Membangun query daftar identitas sekolah terurut berdasarkan jenjang pendidikan.
     *
     * @return Builder<Sekolah>
     */
    public function buildIndexQuery(): Builder
    {
        return Sekolah::query()
            ->select(self::INDEX_COLUMNS)
            ->orderByRaw("CASE UPPER(jenjang_kelas) WHEN 'PG' THEN 1 WHEN 'PAUD' THEN 2 WHEN 'TK' THEN 3 WHEN 'SD' THEN 4 WHEN 'SMP' THEN 5 ELSE 6 END")
            ->orderBy('jenjang_kelas')
            ->orderBy('id');
    }

    /**
     * Mengambil seluruh data identitas sekolah.
     *
     * @return Collection<int, Sekolah>
     */
    public function getAll(): Collection
    {
        return $this->buildIndexQuery()->get();
    }

    /**
     * Mengambil opsi dropdown untuk form tambah/edit identitas sekolah.
     *
     * @return array{jenjangOptions: list<string>}
     */
    public function getFormOptions(): array
    {
        return [
            'jenjangOptions' => Sekolah::JENJANG_OPTIONS,
        ];
    }

    /**
     * Mengambil profil sekolah aktif untuk jenjang tertentu (siap dipakai kop & halaman identitas PDF rapor).
     */
    public function resolveByJenjang(string $jenjangKelas): ?Sekolah
    {
        $normJenjang = strtoupper(trim($jenjangKelas));
        if ($normJenjang === '') {
            return null;
        }

        return Sekolah::query()
            ->select(self::INDEX_COLUMNS)
            ->where('jenjang_kelas', $normJenjang)
            ->where('status', true)
            ->first();
    }

    /**
     * Menyimpan identitas sekolah baru.
     *
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Sekolah
    {
        return Sekolah::create($data);
    }

    /**
     * Memperbarui identitas sekolah.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Sekolah $sekolah, array $data): bool
    {
        return $sekolah->update($data);
    }

    /**
     * Menghapus data identitas sekolah.
     */
    public function delete(Sekolah $sekolah): bool
    {
        return (bool) $sekolah->delete();
    }

    /**
     * Mengubah status aktif/nonaktif profil sekolah.
     */
    public function toggleStatus(Sekolah $sekolah): bool
    {
        return $sekolah->update([
            'status' => ! (bool) $sekolah->status,
        ]);
    }
}

