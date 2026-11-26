<?php

namespace App\Http\Requests\WaliKelas;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\KomentarRapor;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Support\Sqid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCatatanRaporWaliRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menormalisasi SQID kelas, kunci anggota kelas, dan ID template komentar sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $rawKelas = $this->input('kelas_id');
        $kelasId = $this->decodeInt($rawKelas, Kelas::class);

        $rawCatatan = $this->input('catatan', []);
        $catatanMap = [];
        if (is_array($rawCatatan)) {
            foreach ($rawCatatan as $rawKey => $text) {
                $id = $this->decodeInt($rawKey, AnggotaKelas::class);
                if (is_int($id) && $id > 0) {
                    $catatanMap[$id] = is_string($text) ? trim($text) : null;
                }
            }
        }

        $rawTemplateIds = $this->input('komentar_rapor_id', []);
        $templateMap = [];
        if (is_array($rawTemplateIds)) {
            foreach ($rawTemplateIds as $rawKey => $rawTemplateId) {
                $anggotaId = $this->decodeInt($rawKey, AnggotaKelas::class);
                if (! is_int($anggotaId) || $anggotaId <= 0) {
                    continue;
                }

                $decodedTemplate = $this->decodeInt($rawTemplateId, KomentarRapor::class);
                $templateMap[$anggotaId] = $decodedTemplate;
            }
        }

        $this->merge([
            'kelas_id' => $kelasId,
            'catatan' => $catatanMap,
            'komentar_rapor_id' => $templateMap,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'kelas_id' => ['required', 'integer', 'exists:akademik_master_kelas,id'],
            'periode' => ['required', 'string', Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])],
            'catatan' => ['nullable', 'array'],
            'catatan.*' => ['nullable', 'string', 'max:2000'],
            'komentar_rapor_id' => ['nullable', 'array'],
            'komentar_rapor_id.*' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kelas_id.required' => 'Kelas binaan wajib dipilih.',
            'kelas_id.exists' => 'Kelas binaan tidak ditemukan.',
            'periode.required' => 'Periode rapor wajib dipilih.',
            'periode.in' => 'Periode harus Mid Semester atau Semester.',
            'catatan.*.max' => 'Catatan wali kelas maksimal 2.000 karakter per siswa.',
        ];
    }

    private function decodeInt(mixed $rawValue, string $modelClass): mixed
    {
        if ($rawValue === null || $rawValue === '') {
            return null;
        }

        $str = trim((string) $rawValue);
        if ($str === '') {
            return null;
        }

        $decoded = Sqid::decodeFor($modelClass, $str);
        if ($decoded !== null) {
            return (int) $decoded;
        }

        return ctype_digit($str) && (int) $str > 0 ? (int) $str : $str;
    }
}
