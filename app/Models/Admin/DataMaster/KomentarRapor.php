<?php

namespace App\Models\Admin\DataMaster;

use App\Models\Concerns\HasSqidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'judul',
    'kategori',
    'jenjang',
    'isi_komentar',
    'is_active',
    'urutan',
])]
#[Table('akademik_master_komentar_rapor')]
class KomentarRapor extends Model
{
    use HasSqidRouteKey;

    public const string KATEGORI_POSITIF = 'positif';

    public const string KATEGORI_MOTIVASI = 'motivasi';

    public const string KATEGORI_PERHATIAN = 'perhatian';

    public const string KATEGORI_UMUM = 'umum';

    /**
     * Pilihan kategori bank template komentar rapor Wali Kelas & Pendamping.
     *
     * @var array<string, string>
     */
    public const array KATEGORI_OPTIONS = [
        self::KATEGORI_POSITIF => 'Apresiasi & Capaian Baik',
        self::KATEGORI_MOTIVASI => 'Dorongan & Motivasi Belajar',
        self::KATEGORI_PERHATIAN => 'Perlu Perhatian & Pendampingan',
        self::KATEGORI_UMUM => 'Catatan Umum / Mid Semester',
    ];

    /**
     * Daftar template bawaan untuk seeder maupun fallback offline.
     *
     * @var list<array{judul: string, kategori: string, jenjang: ?string, isi_komentar: string, urutan: int}>
     */
    public const array DEFAULT_TEMPLATES = [
        [
            'judul' => 'Apresiasi Capaian & Kemandirian',
            'kategori' => self::KATEGORI_POSITIF,
            'jenjang' => null,
            'isi_komentar' => 'Alhamdulillah, {nama} menunjukkan perkembangan belajar yang sangat baik, aktif dalam kegiatan kelas, dan bertanggung jawab menyelesaikan tugas tepat waktu. Pertahankan semangat belajarnya.',
            'urutan' => 1,
        ],
        [
            'judul' => 'Sikap Santun & Kerja Sama Baik',
            'kategori' => self::KATEGORI_POSITIF,
            'jenjang' => null,
            'isi_komentar' => '{nama} memiliki akhlak yang baik, mudah bekerja sama dengan teman, serta selalu berusaha mengikuti arahan guru dengan antusias. Semoga terus tumbuh menjadi pribadi yang percaya diri dan gemar belajar.',
            'urutan' => 2,
        ],
        [
            'judul' => 'Dorongan Konsistensi Belajar (Mid)',
            'kategori' => self::KATEGORI_MOTIVASI,
            'jenjang' => null,
            'isi_komentar' => 'Pada tengah semester ini, {nama} menunjukkan progres yang cukup baik. Diharapkan dapat terus menjaga fokus di kelas serta rutin mengulang materi pelajaran di rumah agar capaian akhir semester semakin optimal.',
            'urutan' => 3,
        ],
        [
            'judul' => 'Motivasi Keberanian & Partisipasi',
            'kategori' => self::KATEGORI_MOTIVASI,
            'jenjang' => null,
            'isi_komentar' => '{nama} mulai terbiasa mengikuti pembelajaran dengan tertib. Terus latih keberanian untuk bertanya maupun menyampaikan pendapat di kelas agar potensi terbaik {nama} semakin terlihat.',
            'urutan' => 4,
        ],
        [
            'judul' => 'Perhatian Ketekunan & Kedisiplinan Tugas',
            'kategori' => self::KATEGORI_PERHATIAN,
            'jenjang' => null,
            'isi_komentar' => '{nama} perlu lebih teliti dan konsisten dalam menyelesaikan tugas belajar maupun manajemen waktu di kelas. Sinergi pendampingan antara guru dan orang tua di rumah akan sangat membantu perkembangan {nama}.',
            'urutan' => 5,
        ],
        [
            'judul' => 'Perhatian Kehadiran & Kesehatan',
            'kategori' => self::KATEGORI_PERHATIAN,
            'jenjang' => null,
            'isi_komentar' => 'Kehadiran {nama} pada periode ini perlu menjadi perhatian bersama. Mohon dukungan ayah/bunda untuk menjaga rutinitas istirahat dan kehadiran agar {nama} tidak tertinggal materi pembelajaran.',
            'urutan' => 6,
        ],
    ];

    /**
     * Atribut yang di-cast secara otomatis.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * Label kategori template yang ramah dibaca.
     */
    protected function kategoriLabel(): Attribute
    {
        return Attribute::get(fn (): string => self::KATEGORI_OPTIONS[(string) $this->kategori] ?? ucfirst((string) $this->kategori));
    }

    /**
     * Kelas badge Bootstrap untuk kategori template komentar.
     */
    protected function kategoriBadgeClass(): Attribute
    {
        return Attribute::get(fn (): string => match ((string) $this->kategori) {
            self::KATEGORI_POSITIF => 'badge-success',
            self::KATEGORI_MOTIVASI => 'badge-info',
            self::KATEGORI_PERHATIAN => 'badge-warning text-dark',
            default => 'badge-secondary',
        });
    }

    /**
     * Mengganti placeholder {nama} dengan nama siswa terkait.
     */
    public function renderForSiswa(string $namaSiswa): string
    {
        $cleanNama = trim(preg_replace('/\s+/', ' ', $namaSiswa) ?? '');

        return str_replace(['{nama}', '{NAMA}'], $cleanNama !== '' ? $cleanNama : 'Ananda', (string) $this->isi_komentar);
    }

    /**
     * Mengurutkan template berdasarkan urutan tampilan lalu ID.
     */
    #[Scope]
    protected function ordered(Builder $query): Builder
    {
        return $query->orderBy($query->qualifyColumn('urutan'))
            ->orderBy($query->qualifyColumn('id'));
    }

    /**
     * Menyaring hanya template yang aktif digunakan.
     */
    #[Scope]
    protected function active(Builder $query): Builder
    {
        return $query->where($query->qualifyColumn('is_active'), true);
    }
}
