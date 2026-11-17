<?php

namespace App\Http\Requests\Admin\Penilaian;

use App\Models\Admin\Penilaian\Kkm;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class KkmRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawPredikats = $this->input('predikats', []);
        $normalizedPredikats = [];

        if (is_array($rawPredikats)) {
            foreach ($rawPredikats as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $normalizedPredikats[] = [
                    'predikat' => strtoupper(trim((string) ($row['predikat'] ?? ''))),
                    'keterangan' => trim((string) ($row['keterangan'] ?? '')),
                    'nilai_min' => $row['nilai_min'] ?? null,
                    'nilai_max' => $row['nilai_max'] ?? null,
                    'is_tuntas' => filter_var($row['is_tuntas'] ?? false, FILTER_VALIDATE_BOOLEAN),
                ];
            }
        }

        $rawMapelIds = $this->input('mata_pelajaran_ids', []);
        $normalizedMapelIds = [];

        if (is_array($rawMapelIds)) {
            foreach ($rawMapelIds as $rawId) {
                if (is_numeric($rawId) && (int) $rawId > 0) {
                    $normalizedMapelIds[] = (int) $rawId;
                }
            }
        }

        $singleMapelId = $this->input('akademik_pelajaran_mata_pelajaran_id');
        if (is_numeric($singleMapelId) && (int) $singleMapelId > 0) {
            $normalizedMapelIds[] = (int) $singleMapelId;
        }

        $normalizedMapelIds = array_values(array_unique($normalizedMapelIds));
        $primaryMapelId = is_numeric($singleMapelId) && (int) $singleMapelId > 0
            ? (int) $singleMapelId
            : ($normalizedMapelIds[0] ?? null);

        $this->merge([
            'akademik_pelajaran_mata_pelajaran_id' => $primaryMapelId,
            'mata_pelajaran_ids' => $normalizedMapelIds,
            'tingkatan_kelas' => trim((string) $this->input('tingkatan_kelas', '')),
            'bobot_tp' => filled($this->input('bobot_tp')) ? (int) $this->input('bobot_tp') : Kkm::DEFAULT_BOBOT_TP,
            'bobot_sumatif' => filled($this->input('bobot_sumatif')) ? (int) $this->input('bobot_sumatif') : Kkm::DEFAULT_BOBOT_SUMATIF,
            'bobot_ujian' => filled($this->input('bobot_ujian')) ? (int) $this->input('bobot_ujian') : Kkm::DEFAULT_BOBOT_UJIAN,
            'status' => $this->has('status') ? $this->boolean('status') : true,
            'predikats' => $normalizedPredikats,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeModel = $this->route('kkm');
        $ignoreId = is_object($routeModel) ? $routeModel->getKey() : $routeModel;

        $taId = $this->input('akademik_master_tahun_ajaran_id');
        $mapelId = $this->input('akademik_pelajaran_mata_pelajaran_id');
        $isBatchMapel = $ignoreId === null && count((array) $this->input('mata_pelajaran_ids', [])) > 1;

        $tingkatanRules = [
            'required',
            'string',
            Rule::in(Kkm::SD_TINGKATAN_OPTIONS),
        ];

        if (! $isBatchMapel) {
            $tingkatanRules[] = Rule::unique('akademik_penilaian_kkm', 'tingkatan_kelas')
                ->where(static fn ($query) => $query
                    ->where('akademik_master_tahun_ajaran_id', $taId)
                    ->where('akademik_pelajaran_mata_pelajaran_id', $mapelId))
                ->ignore($ignoreId);
        }

        return [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_master_tahun_ajaran', 'id'),
            ],
            'akademik_pelajaran_mata_pelajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_pelajaran_mata_pelajaran', 'id')
                    ->where('unit', Kkm::TARGET_UNIT),
            ],
            'mata_pelajaran_ids' => ['nullable', 'array', 'min:1'],
            'mata_pelajaran_ids.*' => [
                'integer',
                Rule::exists('akademik_pelajaran_mata_pelajaran', 'id')
                    ->where('unit', Kkm::TARGET_UNIT),
            ],
            'tingkatan_kelas' => $tingkatanRules,
            'nilai_kkm' => ['required', 'numeric', 'min:1', 'max:100'],
            'bobot_tp' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot_sumatif' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot_ujian' => ['required', 'integer', 'min:0', 'max:100'],
            'status' => ['required', 'boolean'],
            'copy_to_tingkatan' => ['nullable', 'array'],
            'copy_to_tingkatan.*' => ['string', Rule::in(Kkm::SD_TINGKATAN_OPTIONS)],
            'predikats' => ['required', 'array', 'min:2', 'max:10'],
            'predikats.*.predikat' => ['required', 'string', 'max:20'],
            'predikats.*.keterangan' => ['required', 'string', 'max:100'],
            'predikats.*.nilai_min' => ['required', 'numeric', 'min:0', 'max:100'],
            'predikats.*.nilai_max' => ['required', 'numeric', 'min:0', 'max:100'],
            'predikats.*.is_tuntas' => ['required', 'boolean'],
        ];
    }

    /**
     * Memastikan jumlah bobot NA TP, Sumatif Bab, dan Nilai Ujian tepat 100%.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $error = Kkm::validateBobotPair(
                    (int) $this->input('bobot_tp', Kkm::DEFAULT_BOBOT_TP),
                    (int) $this->input('bobot_ujian', Kkm::DEFAULT_BOBOT_UJIAN),
                    (int) $this->input('bobot_sumatif', Kkm::DEFAULT_BOBOT_SUMATIF)
                );

                if ($error !== null) {
                    $validator->errors()->add('bobot_ujian', $error);
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'akademik_master_tahun_ajaran_id.required' => 'Tahun ajaran / semester wajib dipilih.',
            'akademik_pelajaran_mata_pelajaran_id.required' => 'Minimal satu mata pelajaran SD wajib dipilih.',
            'akademik_pelajaran_mata_pelajaran_id.exists' => 'Mata pelajaran harus berasal dari unit SD.',
            'mata_pelajaran_ids.*.exists' => 'Semua mata pelajaran yang dipilih harus berasal dari unit SD.',
            'tingkatan_kelas.required' => 'Tingkatan kelas SD wajib dipilih.',
            'tingkatan_kelas.in' => 'Tingkatan kelas hanya mendukung Kelas 1 sampai Kelas 6 SD.',
            'tingkatan_kelas.unique' => 'KKM untuk kombinasi Tahun Ajaran, Mata Pelajaran, dan Tingkatan Kelas tersebut sudah ada.',
            'nilai_kkm.required' => 'Nilai KKM wajib diisi.',
            'predikats.required' => 'Rentang predikat wajib diisi.',
            'predikats.min' => 'Minimal harus ada 2 rentang predikat (tuntas dan belum tuntas).',
        ];
    }
}
