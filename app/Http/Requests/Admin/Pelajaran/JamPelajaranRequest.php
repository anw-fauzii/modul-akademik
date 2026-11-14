<?php

namespace App\Http\Requests\Admin\Pelajaran;

use App\Models\Admin\Pelajaran\JamPelajaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class JamPelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawJamKe = $this->input('jam_ke');
        $rawUnit = $this->input('unit');
        $rawJamMulai = $this->input('jam_mulai');
        $rawJamSelesai = $this->input('jam_selesai');
        $rawKeterangan = $this->input('keterangan');

        $this->merge([
            'jam_ke' => is_string($rawJamKe) ? trim($rawJamKe) : $rawJamKe,
            'unit' => is_string($rawUnit) ? strtoupper(trim($rawUnit)) : $rawUnit,
            'jam_mulai' => $this->normalizeTimeInput($rawJamMulai),
            'jam_selesai' => $this->normalizeTimeInput($rawJamSelesai),
            'keterangan' => is_string($rawKeterangan)
                ? (trim($rawKeterangan) === '' ? null : trim((string) preg_replace('/\s+/u', ' ', $rawKeterangan)))
                : $rawKeterangan,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $jamPelajaran = $this->route('jam_pelajaran');
        $jamPelajaranId = $jamPelajaran instanceof JamPelajaran
            ? $jamPelajaran->getKey()
            : $jamPelajaran;

        return [
            'jam_ke' => [
                'required',
                'integer',
                'min:1',
                'max:99',
                Rule::unique('akademik_pelajaran_jam_pelajaran', 'jam_ke')
                    ->where(fn ($query) => $query->where('unit', $this->input('unit')))
                    ->ignore($jamPelajaranId),
            ],
            'unit' => [
                'required',
                'string',
                Rule::in(JamPelajaran::UNIT_OPTIONS),
            ],
            'jam_mulai' => [
                'required',
                'date_format:H:i',
            ],
            'jam_selesai' => [
                'required',
                'date_format:H:i',
                'after:jam_mulai',
            ],
            'keterangan' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'jam_ke' => 'Jam Ke',
            'unit' => 'Unit Pendidikan',
            'jam_mulai' => 'Jam Mulai',
            'jam_selesai' => 'Jam Selesai',
            'keterangan' => 'Keterangan',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'jam_ke.required' => 'Jam ke wajib diisi.',
            'jam_ke.integer' => 'Jam ke harus berupa angka bulat.',
            'jam_ke.min' => 'Jam ke minimal 1.',
            'jam_ke.max' => 'Jam ke maksimal 99.',
            'jam_ke.unique' => 'Jam ke ini sudah terdaftar pada unit yang dipilih.',
            'unit.required' => 'Unit wajib dipilih.',
            'unit.in' => 'Unit yang dipilih tidak valid.',
            'jam_mulai.required' => 'Jam mulai wajib diisi.',
            'jam_mulai.date_format' => 'Format jam mulai harus HH:MM (contoh: 07:30).',
            'jam_selesai.required' => 'Jam selesai wajib diisi.',
            'jam_selesai.date_format' => 'Format jam selesai harus HH:MM (contoh: 08:05).',
            'jam_selesai.after' => 'Jam selesai harus lebih besar dari jam mulai.',
            'keterangan.max' => 'Keterangan maksimal 1000 karakter.',
        ];
    }

    private function normalizeTimeInput(mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $trimmed = trim($value);
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $trimmed, $matches) === 1) {
            return sprintf('%02d:%s', (int) $matches[1], $matches[2]);
        }

        return $trimmed;
    }
}
