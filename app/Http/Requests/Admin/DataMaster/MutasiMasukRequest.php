<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Http\Requests\Admin\DataMaster\Concerns\NormalizesMutasiRequestInputs;
use App\Models\Admin\DataMaster\Kelas;
use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MutasiMasukRequest extends FormRequest
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
     * Menormalisasi input Mutasi Masuk dan memastikan status default siswa baru Aktif.
     */
    protected function prepareForValidation(): void
    {
        $kelasTujuanId = $this->decodeIntegerIdOrRaw(
            $this->input('kelas_tujuan_id'),
            Kelas::class
        );

        $this->merge([
            'akademik_master_tahun_ajaran_id' => $this->decodeIntegerIdOrRaw(
                $this->input('akademik_master_tahun_ajaran_id'),
                TahunAjaran::class
            ),
            'kelas_tujuan_id' => $kelasTujuanId,
            'akademik_master_kelas_id' => $kelasTujuanId,
            'nis' => trim((string) ($this->input('nis') ?? '')),
            'nama_lengkap' => $this->collapseWhitespace($this->input('nama_lengkap')) ?? '',
            'sekolah_asal_tujuan' => $this->collapseWhitespace($this->input('sekolah_asal_tujuan')) ?? '',
            'nomor_surat' => $this->collapseWhitespace($this->input('nomor_surat')),
            'alasan' => $this->collapseWhitespace($this->input('alasan')),
            'jenis_pendaftaran' => $this->collapseWhitespace($this->input('jenis_pendaftaran')) ?? 'Pindahan',
            'status' => Siswa::STATUS_AKTIF,
        ]);
    }

    /**
     * Mewarisi aturan biodata dari SiswaRequest agar DRY dan menambahkan aturan dokumen Mutasi Masuk.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $siswaRules = (new SiswaRequest)->rules();

        return array_merge($siswaRules, [
            'akademik_master_tahun_ajaran_id' => [
                'required',
                'integer',
                'exists:akademik_master_tahun_ajaran,id',
            ],
            'kelas_tujuan_id' => [
                'nullable',
                'integer',
                'exists:akademik_master_kelas,id',
            ],
            'tanggal_efektif' => ['required', 'date'],
            'sekolah_asal_tujuan' => ['required', 'string', 'max:255'],
            'nomor_surat' => ['nullable', 'string', 'max:100'],
            'alasan' => ['nullable', 'string', 'max:1000'],
        ]);
    }

    /**
     * Pesan validasi kustom dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return array_merge((new SiswaRequest)->messages(), [
            'akademik_master_tahun_ajaran_id.required' => 'Tahun ajaran masuk wajib dipilih.',
            'akademik_master_tahun_ajaran_id.exists' => 'Tahun ajaran masuk tidak valid.',
            'kelas_tujuan_id.exists' => 'Kelas tujuan tidak valid.',
            'tanggal_efektif.required' => 'Tanggal efektif mutasi masuk wajib diisi.',
            'tanggal_efektif.date' => 'Format tanggal efektif tidak valid.',
            'sekolah_asal_tujuan.required' => 'Nama sekolah asal wajib diisi.',
            'sekolah_asal_tujuan.max' => 'Nama sekolah asal maksimal 255 karakter.',
            'nomor_surat.max' => 'Nomor surat pindah maksimal 100 karakter.',
        ]);
    }
}

