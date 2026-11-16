<?php

namespace App\Http\Requests\Admin\Penilaian;

use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PeriodeTp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateBobotRaporRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $bobotTp = filled($this->input('bobot_tp')) ? (int) $this->input('bobot_tp') : Kkm::DEFAULT_BOBOT_TP;
        $bobotUjian = filled($this->input('bobot_ujian')) ? (int) $this->input('bobot_ujian') : Kkm::DEFAULT_BOBOT_UJIAN;

        $this->merge([
            'periode' => strtolower(trim((string) $this->input('periode', PeriodeTp::PERIODE_MID))),
            'bobot_tp' => $bobotTp,
            'bobot_ujian' => $bobotUjian,
        ]);
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
            'bobot_tp' => ['required', 'integer', 'min:0', 'max:100'],
            'bobot_ujian' => ['required', 'integer', 'min:0', 'max:100'],
        ];
    }

    /**
     * Memastikan jumlah bobot TP dan bobot ujian tepat 100%.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $error = Kkm::validateBobotPair(
                    (int) $this->input('bobot_tp', 0),
                    (int) $this->input('bobot_ujian', 0)
                );

                if ($error !== null) {
                    $validator->errors()->add('bobot_ujian', $error);
                }
            },
        ];
    }
}
