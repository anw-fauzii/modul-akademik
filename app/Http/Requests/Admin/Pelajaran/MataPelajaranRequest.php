<?php

namespace App\Http\Requests\Admin\Pelajaran;

use App\Models\Admin\Pelajaran\MataPelajaran;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MataPelajaranRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $rawSingkatan = $this->input('singkatan');
        $rawNama = $this->input('nama_mata_pelajaran');
        $rawSifat = $this->input('sifat');
        $rawUnit = $this->input('unit');
        $rawUrutan = $this->input('urutan');
        $rawKeterangan = $this->input('keterangan');

        $normalized = [
            'singkatan' => is_string($rawSingkatan)
                ? mb_strtoupper(trim((string) preg_replace('/\s+/u', ' ', $rawSingkatan)), 'UTF-8')
                : $rawSingkatan,
            'nama_mata_pelajaran' => is_string($rawNama)
                ? trim((string) preg_replace('/\s+/u', ' ', $rawNama))
                : $rawNama,
            'sifat' => is_string($rawSifat) ? strtolower(trim($rawSifat)) : $rawSifat,
            'unit' => is_string($rawUnit) ? strtoupper(trim($rawUnit)) : $rawUnit,
            'urutan' => is_string($rawUrutan) ? trim($rawUrutan) : $rawUrutan,
            'keterangan' => is_string($rawKeterangan)
                ? (trim($rawKeterangan) === '' ? null : trim($rawKeterangan))
                : $rawKeterangan,
        ];

        if ($this->has('status')) {
            $rawStatus = $this->input('status');
            $parsedStatus = filter_var($rawStatus, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            $normalized['status'] = $parsedStatus ?? $rawStatus;
        }

        $this->merge($normalized);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $mataPelajaran = $this->route('mata_pelajaran');
        $mataPelajaranId = $mataPelajaran instanceof MataPelajaran
            ? $mataPelajaran->getKey()
            : $mataPelajaran;

        return [
            'akademik_pelajaran_kelompok_pelajaran_id' => [
                'required',
                'integer',
                Rule::exists('akademik_pelajaran_kelompok_pelajaran', 'id'),
            ],
            'singkatan' => [
                'required',
                'string',
                'max:30',
                Rule::unique('akademik_pelajaran_mata_pelajaran', 'singkatan')
                    ->where(fn ($query) => $query->where('unit', $this->input('unit')))
                    ->ignore($mataPelajaranId),
            ],
            'nama_mata_pelajaran' => [
                'required',
                'string',
                'max:255',
                Rule::unique('akademik_pelajaran_mata_pelajaran', 'nama_mata_pelajaran')
                    ->where(fn ($query) => $query->where('unit', $this->input('unit')))
                    ->ignore($mataPelajaranId),
            ],
            'sifat' => [
                'required',
                'string',
                Rule::in(MataPelajaran::SIFAT_OPTIONS),
            ],
            'unit' => [
                'required',
                'string',
                Rule::in(MataPelajaran::UNIT_OPTIONS),
            ],
            'urutan' => [
                'required',
                'integer',
                'min:1',
                'max:9999',
            ],
            'keterangan' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'status' => [
                'required',
                'boolean',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'akademik_pelajaran_kelompok_pelajaran_id' => 'Kelompok Pelajaran',
            'singkatan' => 'Singkatan',
            'nama_mata_pelajaran' => 'Nama Mata Pelajaran',
            'sifat' => 'Sifat Mata Pelajaran',
            'unit' => 'Unit',
            'urutan' => 'Urutan',
            'keterangan' => 'Keterangan',
            'status' => 'Status',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'akademik_pelajaran_kelompok_pelajaran_id.required' => 'Kelompok pelajaran wajib dipilih.',
            'akademik_pelajaran_kelompok_pelajaran_id.integer' => 'Kelompok pelajaran tidak valid.',
            'akademik_pelajaran_kelompok_pelajaran_id.exists' => 'Kelompok pelajaran yang dipilih tidak ditemukan.',
            'singkatan.required' => 'Singkatan mata pelajaran wajib diisi.',
            'singkatan.max' => 'Singkatan mata pelajaran maksimal 30 karakter.',
            'singkatan.unique' => 'Singkatan mata pelajaran ini sudah terdaftar pada unit yang dipilih.',
            'nama_mata_pelajaran.required' => 'Nama mata pelajaran wajib diisi.',
            'nama_mata_pelajaran.max' => 'Nama mata pelajaran maksimal 255 karakter.',
            'nama_mata_pelajaran.unique' => 'Nama mata pelajaran ini sudah terdaftar pada unit yang dipilih.',
            'sifat.required' => 'Sifat mata pelajaran wajib dipilih.',
            'sifat.in' => 'Sifat mata pelajaran harus wajib atau tambahan.',
            'unit.required' => 'Unit wajib dipilih.',
            'unit.in' => 'Unit yang dipilih tidak valid.',
            'urutan.required' => 'Urutan mata pelajaran wajib diisi.',
            'urutan.integer' => 'Urutan mata pelajaran harus berupa angka bulat.',
            'urutan.min' => 'Urutan mata pelajaran minimal 1.',
            'urutan.max' => 'Urutan mata pelajaran maksimal 9999.',
            'keterangan.max' => 'Keterangan maksimal 1000 karakter.',
            'status.required' => 'Status mata pelajaran wajib dipilih.',
            'status.boolean' => 'Status mata pelajaran harus bernilai aktif atau nonaktif.',
        ];
    }
}
