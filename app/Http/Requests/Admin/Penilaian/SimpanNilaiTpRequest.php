<?php

namespace App\Http\Requests\Admin\Penilaian;

use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PeriodeTp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SimpanNilaiTpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawUjian = $this->input('ujian', []);
        $normalizedUjian = [];

        if (is_array($rawUjian)) {
            foreach ($rawUjian as $anggotaId => $val) {
                $trimmed = is_string($val) ? trim(str_replace(',', '.', $val)) : $val;
                $normalizedUjian[$anggotaId] = ($trimmed === '' || $trimmed === null) ? null : $trimmed;
            }
        }

        $this->merge([
            'periode' => strtolower(trim((string) $this->input('periode', PeriodeTp::PERIODE_MID))),
            'nilai' => $this->normalizeScoreMatrix($this->input('nilai', [])),
            'sumatif' => $this->normalizeScoreMatrix($this->input('sumatif', [])),
            'ujian' => $normalizedUjian,
        ]);
    }

    /**
     * Menormalkan matriks input nilai [anggota_kelas_id => [kolom_id => nilai|null]].
     *
     * @return array<int|string, array<int|string, mixed>>
     */
    protected function normalizeScoreMatrix(mixed $rawMatrix): array
    {
        $normalized = [];

        if (! is_array($rawMatrix)) {
            return $normalized;
        }

        foreach ($rawMatrix as $anggotaId => $colValues) {
            if (! is_array($colValues)) {
                continue;
            }
            foreach ($colValues as $colId => $val) {
                $trimmed = is_string($val) ? trim(str_replace(',', '.', $val)) : $val;
                $normalized[$anggotaId][$colId] = ($trimmed === '' || $trimmed === null) ? null : $trimmed;
            }
        }

        return $normalized;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tahun_ajaran_id' => ['nullable', 'integer', Rule::exists('akademik_master_tahun_ajaran', 'id')],
            'kelas_id' => [
                'required',
                'integer',
                Rule::exists('akademik_master_kelas', 'id')->where('jenjang_kelas', Kkm::TARGET_UNIT),
            ],
            'mata_pelajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_pelajaran_mata_pelajaran', 'id')->where('unit', Kkm::TARGET_UNIT),
            ],
            'periode' => ['required', 'string', Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])],
            'nilai' => ['nullable', 'array'],
            'nilai.*' => ['array'],
            'nilai.*.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'sumatif' => ['nullable', 'array'],
            'sumatif.*' => ['array'],
            'sumatif.*.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'ujian' => ['nullable', 'array'],
            'ujian.*' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kelas_id.exists' => 'Kelas yang dipilih harus merupakan kelas SD.',
            'mata_pelajaran_id.exists' => 'Mata pelajaran yang dipilih harus berasal dari unit SD.',
            'nilai.*.*.numeric' => 'Nilai harus berupa angka valid.',
            'nilai.*.*.min' => 'Nilai minimal adalah 0.',
            'nilai.*.*.max' => 'Nilai maksimal adalah 100.',
            'sumatif.*.*.numeric' => 'Nilai Sumatif Bab harus berupa angka valid.',
            'sumatif.*.*.min' => 'Nilai Sumatif Bab minimal adalah 0.',
            'sumatif.*.*.max' => 'Nilai Sumatif Bab maksimal adalah 100.',
            'ujian.*.numeric' => 'Nilai ujian harus berupa angka valid.',
            'ujian.*.min' => 'Nilai ujian minimal adalah 0.',
            'ujian.*.max' => 'Nilai ujian maksimal adalah 100.',
        ];
    }
}
