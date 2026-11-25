<?php

namespace App\Services\Admin\DataMaster;

use App\Models\Admin\DataMaster\KomentarRapor;
use App\Models\Admin\DataMaster\KomentarT2q;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Schema;

class BankKomentarService
{
    public const string JENJANG_UMUM = '__umum__';

    /**
     * Menormalisasi input filter halaman Bank Komentar Rapor & T2Q.
     *
     * @param  array<string, mixed>  $input
     * @return array{
     *     tab: string,
     *     kategori: string,
     *     jenjang: string,
     *     status: string,
     *     search: string,
     *     sort: string,
     *     hasActiveFilter: bool
     * }
     */
    public function normalizeFilters(array $input): array
    {
        $tab = trim((string) ($input['tab'] ?? 'wali')) === 't2q' ? 't2q' : 'wali';

        $kategori = trim((string) ($input['kategori'] ?? ''));
        if ($kategori !== '' && ! array_key_exists($kategori, KomentarRapor::KATEGORI_OPTIONS)) {
            $kategori = '';
        }

        $rawJenjang = trim((string) ($input['jenjang'] ?? ''));
        $jenjang = '';
        if ($rawJenjang === self::JENJANG_UMUM) {
            $jenjang = self::JENJANG_UMUM;
        } elseif ($rawJenjang !== '') {
            $upper = strtoupper($rawJenjang);
            if (in_array($upper, ['PG', 'TK', 'SD'], true)) {
                $jenjang = $upper;
            }
        }

        $status = trim((string) ($input['status'] ?? 'all'));
        if (! in_array($status, ['all', '1', '0'], true)) {
            $status = 'all';
        }

        $search = trim((string) ($input['search'] ?? ''));

        $sort = trim((string) ($input['sort'] ?? 'urutan'));
        if (! in_array($sort, ['urutan', 'az', 'za', 'terbaru'], true)) {
            $sort = 'urutan';
        }

        $hasActiveFilter = $tab === 't2q'
            ? ($search !== '' || $sort !== 'urutan')
            : ($kategori !== '' || $jenjang !== '' || $status !== 'all' || $search !== '');

        return [
            'tab' => $tab,
            'kategori' => $kategori,
            'jenjang' => $jenjang,
            'status' => $status,
            'search' => $search,
            'sort' => $sort,
            'hasActiveFilter' => $hasActiveFilter,
        ];
    }

    /**
     * Mengambil data lengkap halaman Bank Komentar Rapor (Template Wali Kelas & Poin T2Q) secara terfilter & anti-N+1.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function getIndexData(array $input = []): array
    {
        $filters = $this->normalizeFilters($input);
        $hasWaliTable = Schema::hasTable('akademik_master_komentar_rapor');
        $hasT2qTable = Schema::hasTable('akademik_master_t2q_komentar');

        $waliStats = [
            'total' => 0,
            'aktif' => 0,
            'nonaktif' => 0,
        ];
        if ($hasWaliTable) {
            $agg = KomentarRapor::query()
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as aktif')
                ->toBase()
                ->first();
            $waliStats['total'] = (int) ($agg->total ?? 0);
            $waliStats['aktif'] = (int) ($agg->aktif ?? 0);
            $waliStats['nonaktif'] = max(0, $waliStats['total'] - $waliStats['aktif']);
        }

        $t2qTotal = $hasT2qTable ? KomentarT2q::query()->count() : 0;

        $templates = new Collection;
        if ($filters['tab'] === 'wali' && $hasWaliTable) {
            $query = KomentarRapor::query()
                ->select(['id', 'judul', 'kategori', 'jenjang', 'isi_komentar', 'is_active', 'urutan']);

            if ($filters['kategori'] !== '') {
                $query->where('kategori', $filters['kategori']);
            }

            if ($filters['jenjang'] === self::JENJANG_UMUM) {
                $query->where(static function ($sub): void {
                    $sub->whereNull('jenjang')->orWhere('jenjang', '');
                });
            } elseif ($filters['jenjang'] !== '') {
                $query->where('jenjang', $filters['jenjang']);
            }

            if ($filters['status'] === '1') {
                $query->where('is_active', true);
            } elseif ($filters['status'] === '0') {
                $query->where('is_active', false);
            }

            if ($filters['search'] !== '') {
                $kw = $filters['search'];
                $query->where(static function ($sub) use ($kw): void {
                    $sub->where('judul', 'like', "%{$kw}%")
                        ->orWhere('isi_komentar', 'like', "%{$kw}%");
                });
            }

            $templates = $query->ordered()->get();
        }

        $t2qComments = new Collection;
        if ($filters['tab'] === 't2q' && $hasT2qTable) {
            $query = KomentarT2q::query()->select(['id', 'komentar']);

            if ($filters['search'] !== '') {
                $query->where('komentar', 'like', "%{$filters['search']}%");
            }

            match ($filters['sort']) {
                'az' => $query->orderBy('komentar'),
                'za' => $query->orderByDesc('komentar'),
                'terbaru' => $query->orderByDesc('id'),
                default => $query->ordered(),
            };

            $t2qComments = $query->get();
        }

        return [
            'tab' => $filters['tab'],
            'filters' => $filters,
            'hasActiveFilter' => $filters['hasActiveFilter'],
            'templates' => $templates,
            't2qComments' => $t2qComments,
            'waliStats' => $waliStats,
            't2qTotal' => $t2qTotal,
            'kategoriOptions' => KomentarRapor::KATEGORI_OPTIONS,
            'jenjangOptions' => ['PG', 'TK', 'SD'],
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeTemplateWali(array $data): KomentarRapor
    {
        return KomentarRapor::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTemplateWali(KomentarRapor $template, array $data): bool
    {
        return $template->update($data);
    }

    public function deleteTemplateWali(KomentarRapor $template): ?bool
    {
        return $template->delete();
    }

    public function toggleTemplateWaliStatus(KomentarRapor $template): bool
    {
        return $template->update(['is_active' => ! $template->is_active]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeKomentarT2q(array $data): KomentarT2q
    {
        return KomentarT2q::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateKomentarT2q(KomentarT2q $komentar, array $data): bool
    {
        return $komentar->update($data);
    }

    public function deleteKomentarT2q(KomentarT2q $komentar): ?bool
    {
        return $komentar->delete();
    }
}
