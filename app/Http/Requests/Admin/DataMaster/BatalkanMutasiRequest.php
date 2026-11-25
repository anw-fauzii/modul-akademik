<?php

namespace App\Http\Requests\Admin\DataMaster;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class BatalkanMutasiRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Trim alasan pembatalan sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'alasan_batal' => trim((string) ($this->input('alasan_batal') ?? '')),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'alasan_batal' => ['required', 'string', 'min:5', 'max:1000'],
        ];
    }

    /**
     * Pesan validasi kustom dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'alasan_batal.required' => 'Alasan pembatalan wajib diisi untuk menjaga jejak audit.',
            'alasan_batal.min' => 'Alasan pembatalan minimal 5 karakter.',
            'alasan_batal.max' => 'Alasan pembatalan maksimal 1000 karakter.',
        ];
    }
}

