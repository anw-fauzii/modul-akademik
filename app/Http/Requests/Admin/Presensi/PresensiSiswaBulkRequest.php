<?php

namespace App\Http\Requests\Admin\Presensi;

use App\Models\Admin\DataMaster\AnggotaKelas;
use App\Models\Admin\Presensi\PresensiSiswa;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PresensiSiswaBulkRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        if (! is_array($items)) {
            return;
        }

        $normalized = [];
        foreach ($items as $key => $item) {
            if (! is_array($item)) {
                continue;
            }

            $jamMasuk = $item['jam_masuk'] ?? null;
            if (is_string($jamMasuk)) {
                $jamMasuk = trim($jamMasuk);
                if ($jamMasuk === '') {
                    $jamMasuk = null;
                } elseif (preg_match('/^\d{2}:\d{2}:\d{2}$/', $jamMasuk) === 1) {
                    $jamMasuk = substr($jamMasuk, 0, 5);
                }
            }

            $normalized[$key] = [
                'anggota_kelas_id' => $item['anggota_kelas_id'] ?? null,
                'status' => $item['status'] ?? 'hadir',
                'jam_masuk' => $jamMasuk,
                'keterlambatan_menit' => isset($item['keterlambatan_menit']) && $item['keterlambatan_menit'] !== ''
                    ? (int) $item['keterlambatan_menit']
                    : 0,
            ];
        }

        $this->merge(['items' => $normalized]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'kelas_id' => ['required', 'integer', 'exists:akademik_master_kelas,id'],
            'tanggal' => ['required', 'date'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.anggota_kelas_id' => ['required', 'integer', 'exists:akademik_master_anggota_kelas,id'],
            'items.*.status' => ['required', Rule::in(array_keys(PresensiSiswa::STATUS_OPTIONS))],
            'items.*.jam_masuk' => ['nullable', 'date_format:H:i'],
            'items.*.keterlambatan_menit' => ['nullable', 'integer', 'min:0', 'max:1440'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $kelasId = (int) $this->input('kelas_id');
                $items = $this->input('items', []);
                $submittedIds = collect($items)
                    ->pluck('anggota_kelas_id')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->values();

                $validIds = AnggotaKelas::query()
                    ->where('akademik_master_kelas_id', $kelasId)
                    ->whereIn('id', $submittedIds->all())
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                foreach ($items as $index => $item) {
                    if (! in_array((int) $item['anggota_kelas_id'], $validIds, true)) {
                        $validator->errors()->add(
                            "items.{$index}.anggota_kelas_id",
                            'Siswa tersebut tidak terdaftar pada kelas yang dipilih.'
                        );
                    }
                }
            },
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
            'items.required' => 'Daftar presensi siswa tidak boleh kosong.',
            'items.min' => 'Minimal harus ada satu siswa.',
            'items.*.status.required' => 'Status presensi setiap siswa wajib dipilih.',
            'items.*.status.in' => 'Status presensi tidak valid.',
            'items.*.jam_masuk.date_format' => 'Format jam masuk harus JJ:MM.',
            'items.*.keterlambatan_menit.integer' => 'Keterlambatan harus berupa angka menit.',
            'items.*.keterlambatan_menit.min' => 'Keterlambatan minimal 0 menit.',
            'items.*.keterlambatan_menit.max' => 'Keterlambatan maksimal 1440 menit.',
        ];
    }
}
