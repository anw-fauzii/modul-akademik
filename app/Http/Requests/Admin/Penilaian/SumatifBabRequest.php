<?php

namespace App\Http\Requests\Admin\Penilaian;

use App\Models\Admin\Penilaian\Kkm;
use App\Models\Admin\Penilaian\PeriodeTp;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SumatifBabRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawKeterangan = trim((string) preg_replace('/\s+/', ' ', (string) $this->input('keterangan', '')));

        $this->merge([
            'tingkatan_kelas' => trim((string) $this->input('tingkatan_kelas', '')),
            'periode' => strtolower(trim((string) $this->input('periode', PeriodeTp::PERIODE_MID))),
            'nama_bab' => trim((string) preg_replace('/\s+/', ' ', (string) $this->input('nama_bab', ''))),
            'keterangan' => $rawKeterangan !== '' ? $rawKeterangan : null,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'tahun_ajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_master_tahun_ajaran', 'id'),
            ],
            'mata_pelajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_pelajaran_mata_pelajaran', 'id')
                    ->where('unit', Kkm::TARGET_UNIT),
            ],
            'tingkatan_kelas' => [
                'required',
                'string',
                Rule::in(Kkm::SD_TINGKATAN_OPTIONS),
            ],
            'periode' => [
                'required',
                'string',
                Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER]),
            ],
            'nama_bab' => ['required', 'string', 'max:100'],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'urutan' => ['nullable', 'integer', 'min:1', 'max:999'],
            'kelas_id' => ['nullable', 'integer'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nama_bab.required' => 'Nama kolom Sumatif Bab wajib diisi (mis. Sumatif Bab 1).',
            'nama_bab.max' => 'Nama kolom Sumatif Bab maksimal 100 karakter.',
            'keterangan.max' => 'Keterangan materi maksimal 255 karakter.',
        ];
    }
}
