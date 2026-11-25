<?php

namespace App\Http\Requests\WaliKelas;

use App\Models\Admin\DataMaster\KomentarRapor;
use App\Models\Admin\Penilaian\PeriodeTp;
use App\Support\Sqid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveSingleCatatanRaporWaliRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawTemplate = $this->input('komentar_rapor_id');
        $templateId = null;
        if ($rawTemplate !== null && trim((string) $rawTemplate) !== '') {
            $str = trim((string) $rawTemplate);
            $decoded = Sqid::decodeFor(KomentarRapor::class, $str);
            $templateId = $decoded !== null
                ? (int) $decoded
                : (ctype_digit($str) && (int) $str > 0 ? (int) $str : $str);
        }

        $this->merge([
            'catatan' => is_string($this->input('catatan')) ? trim((string) $this->input('catatan')) : null,
            'komentar_rapor_id' => $templateId,
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'periode' => ['required', 'string', Rule::in([PeriodeTp::PERIODE_MID, PeriodeTp::PERIODE_SEMESTER])],
            'catatan' => ['nullable', 'string', 'max:2000'],
            'komentar_rapor_id' => ['nullable', 'integer', 'exists:akademik_master_komentar_rapor,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'periode.in' => 'Periode harus Mid Semester atau Semester.',
            'catatan.max' => 'Catatan wali kelas maksimal 2.000 karakter.',
        ];
    }
}
