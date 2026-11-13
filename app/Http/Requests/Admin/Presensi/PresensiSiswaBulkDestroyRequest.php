<?php

namespace App\Http\Requests\Admin\Presensi;

use Illuminate\Foundation\Http\FormRequest;

class PresensiSiswaBulkDestroyRequest extends FormRequest
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
            'kelas_id' => ['required', 'integer', 'exists:akademik_master_kelas,id'],
            'tanggal' => ['required', 'date'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'kelas_id.required' => 'Kelas wajib dipilih.',
            'kelas_id.exists' => 'Kelas yang dipilih tidak ditemukan.',
            'tanggal.required' => 'Tanggal presensi wajib diisi.',
            'tanggal.date' => 'Format tanggal presensi tidak valid.',
        ];
    }
}
