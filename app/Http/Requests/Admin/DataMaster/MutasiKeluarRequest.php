<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Http\Requests\Admin\DataMaster\Concerns\NormalizesMutasiRequestInputs;
use App\Models\Admin\DataMaster\SiswaMutasi;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MutasiKeluarRequest extends FormRequest
{
    use NormalizesMutasiRequestInputs;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Menormalisasi daftar NIS siswa dan ID tahun ajaran sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'siswa_nis' => $this->normalizeSiswaNisList($this->input('siswa_nis', [])),
            'akademik_master_tahun_ajaran_id' => $this->decodeIntegerIdOrRaw(
                $this->input('akademik_master_tahun_ajaran_id'),
                TahunAjaran::class
            ),
            'sekolah_asal_tujuan' => $this->collapseWhitespace($this->input('sekolah_asal_tujuan')),
            'nomor_surat' => $this->collapseWhitespace($this->input('nomor_surat')),
            'alasan' => trim((string) ($this->input('alasan') ?? '')),
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
            'siswa_nis' => ['required', 'array', 'min:1'],
            'siswa_nis.*' => [
                'required',
                'string',
                'max:50',
                'distinct',
                'exists:akademik_master_siswa,nis',
            ],
            'kategori' => [
                'required',
                'string',
                Rule::in(array_keys(SiswaMutasi::KATEGORI_KELUAR_OPTIONS)),
            ],
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                'exists:akademik_master_tahun_ajaran,id',
            ],
            'tanggal_efektif' => ['required', 'date'],
            'sekolah_asal_tujuan' => [
                Rule::requiredIf(fn (): bool => $this->input('kategori') === SiswaMutasi::KATEGORI_PINDAH),
                'nullable',
                'string',
                'max:255',
            ],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'alasan' => ['required', 'string', 'min:3', 'max:1000'],
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
            'siswa_nis.required' => 'Pilih minimal satu siswa aktif yang akan dimutasi keluar.',
            'siswa_nis.min' => 'Pilih minimal satu siswa aktif.',
            'siswa_nis.*.exists' => 'Salah satu NIS siswa tidak ditemukan di database.',
            'kategori.required' => 'Jenis mutasi keluar wajib dipilih.',
            'kategori.in' => 'Jenis mutasi keluar tidak valid.',
            'akademik_master_tahun_ajaran_id.required' => 'Tahun ajaran wajib dipilih.',
            'akademik_master_tahun_ajaran_id.exists' => 'Tahun ajaran tidak ditemukan.',
            'tanggal_efektif.required' => 'Tanggal efektif mutasi wajib diisi.',
            'tanggal_efektif.date' => 'Format tanggal efektif tidak valid.',
            'sekolah_asal_tujuan.required' => 'Nama sekolah tujuan wajib diisi untuk kategori Pindah Sekolah.',
            'sekolah_asal_tujuan.max' => 'Nama sekolah tujuan maksimal 255 karakter.',
            'nomor_surat.max' => 'Nomor surat maksimal 100 karakter.',
            'alasan.required' => 'Alasan mutasi keluar wajib diisi untuk dokumentasi audit.',
            'alasan.min' => 'Alasan mutasi keluar minimal 3 karakter.',
        ];
    }
}

