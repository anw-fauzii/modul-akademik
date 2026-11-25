<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Models\Admin\DataMaster\KomentarT2q;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class KomentarT2qRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'komentar' => trim((string) preg_replace('/\s+/', ' ', (string) ($this->input('komentar') ?? ''))),
        ]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var KomentarT2q|null $komentarT2q */
        $komentarT2q = $this->route('komentar_t2q');

        return [
            'komentar' => [
                'required',
                'string',
                'max:255',
                Rule::unique('akademik_master_t2q_komentar', 'komentar')->ignore($komentarT2q?->getKey()),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'komentar.required' => 'Teks poin komentar T2Q wajib diisi.',
            'komentar.max' => 'Poin komentar T2Q maksimal 255 karakter.',
            'komentar.unique' => 'Poin komentar T2Q tersebut sudah terdaftar.',
        ];
    }
}
