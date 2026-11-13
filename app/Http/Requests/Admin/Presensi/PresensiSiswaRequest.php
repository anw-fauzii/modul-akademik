<?php

namespace App\Http\Requests\Admin\Presensi;

use App\Models\Admin\Presensi\PresensiSiswa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PresensiSiswaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $jamMasuk = $this->input('jam_masuk');
        if (is_string($jamMasuk)) {
            $jamMasuk = trim($jamMasuk);
            if ($jamMasuk === '') {
                $jamMasuk = null;
            } elseif (preg_match('/^\d{2}:\d{2}:\d{2}$/', $jamMasuk) === 1) {
                $jamMasuk = substr($jamMasuk, 0, 5);
            }
        }

        $this->merge([
            'jam_masuk' => $jamMasuk,
            'keterlambatan_menit' => $this->input('keterlambatan_menit') !== null && $this->input('keterlambatan_menit') !== ''
                ? (int) $this->input('keterlambatan_menit')
                : 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $presensi = $this->route('presensi_siswa');
        $presensiId = $presensi instanceof PresensiSiswa ? $presensi->id : $presensi;

        return [
            'anggota_kelas_id' => ['required', 'integer', 'exists:akademik_master_anggota_kelas,id'],
            'tanggal' => [
                'required',
                'date',
                Rule::unique('akademik_presensi_siswa', 'tanggal')
                    ->where(fn ($query) => $query->where('anggota_kelas_id', $this->input('anggota_kelas_id')))
                    ->ignore($presensiId),
            ],
            'jam_masuk' => ['nullable', 'date_format:H:i'],
            'status' => ['required', Rule::in(array_keys(PresensiSiswa::STATUS_OPTIONS))],
            'keterlambatan_menit' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'anggota_kelas_id.required' => 'Siswa anggota kelas wajib dipilih.',
            'anggota_kelas_id.exists' => 'Anggota kelas yang dipilih tidak ditemukan.',
            'tanggal.required' => 'Tanggal presensi wajib diisi.',
            'tanggal.date' => 'Format tanggal presensi tidak valid.',
            'tanggal.unique' => 'Presensi untuk siswa ini pada tanggal tersebut sudah tercatat.',
            'jam_masuk.date_format' => 'Format jam masuk harus JJ:MM (contoh: 07:15).',
            'status.required' => 'Status presensi wajib dipilih.',
            'status.in' => 'Status presensi tidak valid.',
            'keterlambatan_menit.integer' => 'Keterlambatan harus berupa angka menit.',
            'keterlambatan_menit.min' => 'Keterlambatan minimal 0 menit.',
            'keterlambatan_menit.max' => 'Keterlambatan maksimal 1440 menit.',
        ];
    }
}
