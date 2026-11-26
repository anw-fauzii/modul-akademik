<?php

namespace App\Http\Requests\GuruT2q;

use App\Models\Admin\DataMaster\AnggotaT2q;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Support\Sqid;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveCatatanRaporT2qRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'periode' => ['required', Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])],
            'tingkat' => ['nullable', 'string', 'max:32'],
            'catatan_t2q' => ['nullable', 'array'],
            'catatan_t2q.*' => ['nullable', 'string', 'max:1500'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periode.required' => 'Periode rapor wajib dipilih.',
            'periode.in' => 'Periode rapor harus Mid Semester atau Semester.',
            'catatan_t2q.*.max' => 'Catatan Guru T2Q maksimal 1500 karakter per siswa.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $rawCatatan = $this->input('catatan_t2q');
        $normalized = [];

        if (is_array($rawCatatan)) {
            foreach ($rawCatatan as $key => $text) {
                $decodedId = $this->decodeAnggotaKey($key);
                if ($decodedId === null) {
                    continue;
                }

                $normalized[$decodedId] = is_string($text) ? $this->cleanText($text) : null;
            }
        }

        $tingkatRaw = trim((string) $this->input('tingkat', ''));

        $this->merge([
            'periode' => strtolower(trim((string) $this->input('periode', PeriodeTp::PERIODE_SEMESTER))),
            'tingkat' => $tingkatRaw !== '' ? AnggotaT2q::normalizeTingkat($tingkatRaw) : '',
            'catatan_t2q' => $normalized,
        ]);
    }

    private function decodeAnggotaKey(int|string $key): ?int
    {
        if (is_int($key)) {
            return $key > 0 ? $key : null;
        }

        $trimmed = trim($key);
        if ($trimmed === '') {
            return null;
        }

        if (ctype_digit($trimmed)) {
            $intVal = (int) $trimmed;

            return $intVal > 0 ? $intVal : null;
        }

        return Sqid::decodeFor(AnggotaT2q::class, $trimmed);
    }

    private function cleanText(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim(str_replace(["\r\n", "\r"], "\n", $value));

        return $trimmed !== '' ? $trimmed : null;
    }
}
