<?php

namespace App\Http\Requests\Admin\DataMaster;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class JemputanRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                'exists:akademik_master_tahun_ajaran,id',
            ],
            'karyawan_nipy' => [
                'required',
                'string',
                'max:255',
                'exists:kepegawaian_master_karyawan,nipy',
            ],
            'harga_pp' => [
                'required',
                'integer',
                'min:0',
            ],
            'harga_setengah' => [
                'required',
                'integer',
                'min:0',
            ],
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
            'akademik_master_tahun_ajaran_id.required' => 'Tahun Ajaran wajib dipilih.',
            'akademik_master_tahun_ajaran_id.integer' => 'Tahun Ajaran tidak valid.',
            'akademik_master_tahun_ajaran_id.exists' => 'Tahun Ajaran yang dipilih tidak valid.',
            'karyawan_nipy.required' => 'Petugas / Driver Jemputan wajib dipilih.',
            'karyawan_nipy.string' => 'Petugas / Driver Jemputan tidak valid.',
            'karyawan_nipy.max' => 'NIPY Petugas / Driver maksimal 255 karakter.',
            'karyawan_nipy.exists' => 'Petugas / Driver Jemputan tidak ditemukan dalam database.',
            'harga_pp.required' => 'Harga PP wajib diisi.',
            'harga_pp.integer' => 'Harga PP harus berupa angka bulat.',
            'harga_pp.min' => 'Harga PP tidak boleh bernilai negatif.',
            'harga_setengah.required' => 'Harga Setengah wajib diisi.',
            'harga_setengah.integer' => 'Harga Setengah harus berupa angka bulat.',
            'harga_setengah.min' => 'Harga Setengah tidak boleh bernilai negatif.',
        ];
    }
}
