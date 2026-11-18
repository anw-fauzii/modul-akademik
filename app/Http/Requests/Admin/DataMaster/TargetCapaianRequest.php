<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Models\Admin\DataMaster\TargetCapaian;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class TargetCapaianRequest extends FormRequest
{
    /**
     * Menentukan apakah user berwenang mengirim request ini.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menormalisasi input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $rawJilid = trim((string) $this->input('akademik_master_t2q_daftar_jilid_id', ''));
        $rawSurah = trim((string) $this->input('akademik_master_t2q_surah_id', ''));

        $this->merge([
            'tingkat' => TargetCapaian::normalizeTingkat($this->input('tingkat', '')),
            'akademik_master_t2q_daftar_jilid_id' => $rawJilid !== '' ? $rawJilid : null,
            'akademik_master_t2q_surah_id' => $rawSurah !== '' ? $rawSurah : null,
        ]);
    }

    /**
     * Aturan validasi data target capaian T2Q.
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
            'tingkat' => [
                'required',
                'string',
                'max:50',
            ],
            'akademik_master_t2q_daftar_jilid_id' => [
                'nullable',
                'integer',
                'required_without:akademik_master_t2q_surah_id',
                'exists:akademik_master_t2q_daftar_jilid,id',
            ],
            'akademik_master_t2q_surah_id' => [
                'nullable',
                'integer',
                'required_without:akademik_master_t2q_daftar_jilid_id',
                'exists:akademik_master_t2q_surah,id',
            ],
        ];
    }

    /**
     * Mencegah duplikasi kombinasi Tahun Ajaran, Tingkat, Jilid, dan Surah (termasuk saat salah satu null).
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $current = $this->route('target_capaian');
            $currentId = $current instanceof TargetCapaian ? $current->getKey() : $current;

            $taId = $this->input('akademik_master_tahun_ajaran_id');
            $tingkat = $this->input('tingkat');
            $jilidId = $this->input('akademik_master_t2q_daftar_jilid_id');
            $surahId = $this->input('akademik_master_t2q_surah_id');

            $duplicateExists = TargetCapaian::query()
                ->where('akademik_master_tahun_ajaran_id', $taId)
                ->where('tingkat', $tingkat)
                ->when(
                    $jilidId !== null,
                    static fn ($query) => $query->where('akademik_master_t2q_daftar_jilid_id', $jilidId),
                    static fn ($query) => $query->whereNull('akademik_master_t2q_daftar_jilid_id')
                )
                ->when(
                    $surahId !== null,
                    static fn ($query) => $query->where('akademik_master_t2q_surah_id', $surahId),
                    static fn ($query) => $query->whereNull('akademik_master_t2q_surah_id')
                )
                ->when($currentId !== null, static fn ($query) => $query->whereKeyNot($currentId))
                ->exists();

            if ($duplicateExists) {
                $validator->errors()->add(
                    'tingkat',
                    'Kombinasi Tahun Ajaran, Tingkat, Daftar Jilid, dan Surah ini sudah terdaftar.'
                );
            }
        });
    }

    /**
     * Label atribut yang ramah dibaca saat validasi gagal.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'akademik_master_tahun_ajaran_id' => 'Tahun Ajaran',
            'tingkat' => 'Tingkat',
            'akademik_master_t2q_daftar_jilid_id' => 'Daftar Jilid',
            'akademik_master_t2q_surah_id' => 'Surah Al-Qur\'an',
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
            'akademik_master_tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.',
            'akademik_master_tahun_ajaran_id.exists' => 'Tahun ajaran tidak ditemukan di database.',
            'tingkat.required' => 'Tingkat wajib dipilih.',
            'tingkat.max' => 'Tingkat maksimal 50 karakter.',
            'akademik_master_t2q_daftar_jilid_id.required_without' => 'Pilih minimal Daftar Jilid atau Surah Al-Qur\'an.',
            'akademik_master_t2q_daftar_jilid_id.exists' => 'Daftar jilid tidak ditemukan di database.',
            'akademik_master_t2q_surah_id.required_without' => 'Pilih minimal Surah Al-Qur\'an atau Daftar Jilid.',
            'akademik_master_t2q_surah_id.exists' => 'Surah Al-Qur\'an tidak ditemukan di database.',
        ];
    }
}

