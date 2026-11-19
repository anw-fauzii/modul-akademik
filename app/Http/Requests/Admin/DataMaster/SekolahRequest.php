<?php

namespace App\Http\Requests\Admin\DataMaster;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SekolahRequest extends FormRequest
{
    /**
     * Menentukan apakah user berwenang mengirim request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menormalisasi input jenjang dan membersihkan spasi kolom teks.
     */
    protected function prepareForValidation(): void
    {
        $nullableStrings = [
            'nama_yayasan',
            'npsn',
            'nss',
            'rt',
            'rw',
            'kelurahan',
            'kecamatan',
            'kabupaten',
            'provinsi',
            'kode_pos',
            'telepon',
            'email',
            'website',
        ];

        $normalized = [
            'jenjang_kelas' => strtoupper(trim((string) $this->input('jenjang_kelas', ''))),
            'nama_sekolah' => trim((string) $this->input('nama_sekolah', '')),
            'alamat' => trim((string) $this->input('alamat', '')),
            'status' => $this->boolean('status', true),
        ];

        foreach ($nullableStrings as $field) {
            $value = trim((string) $this->input($field, ''));
            $normalized[$field] = $value !== '' ? $value : null;
        }

        $this->merge($normalized);
    }

    /**
     * Aturan validasi data master identitas sekolah.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $sekolah = $this->route('sekolah');

        return [
            'jenjang_kelas' => [
                'required',
                'string',
                'max:20',
                Rule::unique('akademik_master_sekolah', 'jenjang_kelas')->ignore($sekolah),
            ],
            'nama_yayasan' => ['nullable', 'string', 'max:150'],
            'nama_sekolah' => ['required', 'string', 'max:150'],
            'npsn' => ['nullable', 'string', 'max:30'],
            'nss' => ['nullable', 'string', 'max:30'],
            'alamat' => ['required', 'string', 'max:1000'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'kelurahan' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kabupaten' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:20'],
            'telepon' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'string', 'max:150'],
            'status' => ['nullable', 'boolean'],
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
            'jenjang_kelas.required' => 'Jenjang sekolah wajib dipilih.',
            'jenjang_kelas.unique' => 'Identitas sekolah untuk jenjang tersebut sudah terdaftar.',
            'nama_sekolah.required' => 'Nama resmi sekolah wajib diisi.',
            'nama_sekolah.max' => 'Nama sekolah maksimal 150 karakter.',
            'alamat.required' => 'Alamat sekolah wajib diisi.',
            'email.email' => 'Format email sekolah tidak valid.',
        ];
    }
}
