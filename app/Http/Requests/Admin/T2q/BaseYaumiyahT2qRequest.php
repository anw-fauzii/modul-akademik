<?php

namespace App\Http\Requests\Admin\T2q;

use App\Models\Admin\DataMaster\AngkaArab;
use App\Models\Admin\DataMaster\DaftarJilid;
use App\Models\Admin\DataMaster\Surah;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

abstract class BaseYaumiyahT2qRequest extends FormRequest
{
    abstract protected function requiresDaftarJilid(): bool;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menormalisasi input batch baris yaumiyah sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $rawRows = $this->input('rows', []);
        if (! is_array($rawRows)) {
            return;
        }

        $normalizedRows = [];
        foreach ($rawRows as $anggotaId => $row) {
            if (! is_array($row)) {
                continue;
            }

            $nilai = trim((string) ($row['nilai'] ?? ''));
            $jilidId = trim((string) ($row['akademik_master_t2q_daftar_jilid_id'] ?? ''));
            $surahId = trim((string) ($row['akademik_master_t2q_surah_id'] ?? ''));
            $angkaId = trim((string) ($row['akademik_master_t2q_angka_arab_id'] ?? ''));

            $normalizedRows[$anggotaId] = [
                'akademik_master_t2q_daftar_jilid_id' => $jilidId !== '' ? $jilidId : null,
                'akademik_master_t2q_surah_id' => $surahId !== '' ? $surahId : null,
                'akademik_master_t2q_angka_arab_id' => $angkaId !== '' ? $angkaId : null,
                'nilai' => $nilai !== '' ? str_replace(',', '.', $nilai) : null,
            ];
        }

        $this->merge([
            'rows' => $normalizedRows,
        ]);
    }

    /**
     * Aturan validasi dasar untuk batch yaumiyah T2Q.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'tanggal' => ['required', 'date'],
            'karyawan_nipy' => ['nullable', 'string', 'max:255'],
            'tingkat' => ['nullable', 'string', 'max:50'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.nilai' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'rows.*.akademik_master_t2q_angka_arab_id' => [
                'nullable',
                'integer',
                'exists:akademik_master_t2q_angka_arab,id',
            ],
        ];

        if ($this->requiresDaftarJilid()) {
            $rules['rows.*.akademik_master_t2q_daftar_jilid_id'] = [
                'nullable',
                'required_with:rows.*.nilai',
                'integer',
                'exists:akademik_master_t2q_daftar_jilid,id',
            ];
            $rules['rows.*.akademik_master_t2q_surah_id'] = [
                'nullable',
                'integer',
                'exists:akademik_master_t2q_surah,id',
            ];
        } else {
            $rules['rows.*.akademik_master_t2q_surah_id'] = [
                'nullable',
                'required_with:rows.*.nilai',
                'integer',
                'exists:akademik_master_t2q_surah,id',
            ];
        }

        return $rules;
    }

    /**
     * Validasi lanjutan anti-N+1: pastikan minimal 1 siswa diisi nilai, jilid jenis quran memiliki surah, dan nomor ayat tidak melampaui jumlah ayat surah.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $rows = $this->input('rows', []);
            if (! is_array($rows)) {
                return;
            }

            $filledRows = array_filter($rows, static fn ($row): bool => is_array($row) && ($row['nilai'] ?? null) !== null);
            if ($filledRows === []) {
                $validator->errors()->add('rows', 'Isi minimal satu nilai setoran siswa sebelum menyimpan.');

                return;
            }

            $this->validateQuranAyatBounds($validator, $filledRows);
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'tanggal.required' => 'Tanggal setoran wajib dipilih.',
            'tanggal.date' => 'Format tanggal setoran tidak valid.',
            'rows.required' => 'Tidak ada daftar siswa untuk disimpan.',
            'rows.*.nilai.numeric' => 'Nilai setoran harus berupa angka.',
            'rows.*.nilai.min' => 'Nilai setoran minimal 0.',
            'rows.*.nilai.max' => 'Nilai setoran maksimal 100.',
            'rows.*.akademik_master_t2q_daftar_jilid_id.required_with' => 'Daftar jilid wajib dipilih pada baris yang diberi nilai.',
            'rows.*.akademik_master_t2q_daftar_jilid_id.exists' => 'Daftar jilid tidak ditemukan di database.',
            'rows.*.akademik_master_t2q_surah_id.required_with' => 'Surah Al-Qur\'an wajib dipilih pada baris yang diberi nilai.',
            'rows.*.akademik_master_t2q_surah_id.exists' => 'Surah Al-Qur\'an tidak ditemukan di database.',
            'rows.*.akademik_master_t2q_angka_arab_id.exists' => 'Nomor halaman/ayat tidak ditemukan di database.',
        ];
    }

    /**
     * Memvalidasi kesesuaian jilid jenis Al-Qur'an dan batas maksimal ayat surah dalam 1 query batch.
     *
     * @param  array<int|string, array<string, mixed>>  $filledRows
     */
    private function validateQuranAyatBounds(Validator $validator, array $filledRows): void
    {
        $jilidIds = [];
        $surahIds = [];
        $angkaIds = [];

        foreach ($filledRows as $row) {
            if (! empty($row['akademik_master_t2q_daftar_jilid_id'])) {
                $jilidIds[] = (int) $row['akademik_master_t2q_daftar_jilid_id'];
            }
            if (! empty($row['akademik_master_t2q_surah_id'])) {
                $surahIds[] = (int) $row['akademik_master_t2q_surah_id'];
            }
            if (! empty($row['akademik_master_t2q_angka_arab_id'])) {
                $angkaIds[] = (int) $row['akademik_master_t2q_angka_arab_id'];
            }
        }

        $jilidJenisMap = ($this->requiresDaftarJilid() && $jilidIds !== [])
            ? DaftarJilid::query()->whereIn('id', array_unique($jilidIds))->pluck('jenis', 'id')->all()
            : [];

        $surahMap = $surahIds !== []
            ? Surah::query()->whereIn('id', array_unique($surahIds))->get(['id', 'nama_surah', 'jumlah_ayat'])->keyBy('id')
            : collect();

        $angkaMap = $angkaIds !== []
            ? AngkaArab::query()->whereIn('id', array_unique($angkaIds))->pluck('angka_latin', 'id')->all()
            : [];

        foreach ($filledRows as $rowKey => $row) {
            $jilidId = (int) ($row['akademik_master_t2q_daftar_jilid_id'] ?? 0);
            $surahId = (int) ($row['akademik_master_t2q_surah_id'] ?? 0);
            $angkaId = (int) ($row['akademik_master_t2q_angka_arab_id'] ?? 0);

            if ($this->requiresDaftarJilid() && $jilidId > 0) {
                $jenis = strtolower(trim((string) ($jilidJenisMap[$jilidId] ?? '')));
                if ($jenis === 'quran' && $surahId <= 0) {
                    $validator->errors()->add(
                        "rows.{$rowKey}.akademik_master_t2q_surah_id",
                        'Surah Al-Qur\'an wajib dipilih untuk jilid kategori Al-Qur\'an.'
                    );

                    continue;
                }

                if ($jenis !== 'quran') {
                    continue;
                }
            }

            if ($surahId > 0 && $angkaId > 0) {
                $surah = $surahMap->get($surahId);
                $rawAngka = trim((string) ($angkaMap[$angkaId] ?? ''));
                if ($surah !== null && ctype_digit($rawAngka) && (int) $rawAngka > (int) $surah->jumlah_ayat) {
                    $validator->errors()->add(
                        "rows.{$rowKey}.akademik_master_t2q_angka_arab_id",
                        "Ayat {$rawAngka} melebihi jumlah ayat Surah {$surah->nama_surah} ({$surah->jumlah_ayat} ayat)."
                    );
                }
            }
        }
    }
}
