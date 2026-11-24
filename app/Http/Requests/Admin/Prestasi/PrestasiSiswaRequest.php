<?php

namespace App\Http\Requests\Admin\Prestasi;

use App\Models\Admin\DataMaster\Siswa;
use App\Models\Admin\DataMaster\TahunAjaran;
use App\Models\Admin\Prestasi\PrestasiSiswa;
use App\Support\Sqid;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PrestasiSiswaRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi dan trim input sebelum divalidasi (termasuk daftar siswa_nis[] beregu/individu).
     */
    protected function prepareForValidation(): void
    {
        $rawSiswaInput = $this->input('siswa_nis', []);
        if (! is_array($rawSiswaInput)) {
            $rawSiswaInput = [$rawSiswaInput];
        }

        $normalizedSiswaList = [];
        foreach ($rawSiswaInput as $rawItem) {
            if (! is_string($rawItem) && ! is_int($rawItem)) {
                continue;
            }
            $trimmed = trim((string) $rawItem);
            if ($trimmed === '') {
                continue;
            }
            $decodedNis = Sqid::decodeFor(Siswa::class, $trimmed);
            $normalizedSiswaList[] = $decodedNis !== null ? (string) $decodedNis : $trimmed;
        }
        $normalizedSiswaList = array_values(array_unique($normalizedSiswaList));

        $rawTa = $this->input('akademik_master_tahun_ajaran_id');
        $normalizedTa = null;
        if ($rawTa !== null && trim((string) $rawTa) !== '') {
            $taStr = trim((string) $rawTa);
            $decodedTa = Sqid::decodeFor(TahunAjaran::class, $taStr);
            if ($decodedTa !== null) {
                $normalizedTa = (int) $decodedTa;
            } elseif (ctype_digit($taStr) && (int) $taStr > 0) {
                $normalizedTa = (int) $taStr;
            } else {
                $normalizedTa = $taStr;
            }
        }

        $collapseLine = static fn (mixed $v): string => trim(preg_replace('/\s+/', ' ', (string) ($v ?? '')) ?? '');
        $keterangan = trim((string) ($this->input('keterangan') ?? ''));

        $this->merge([
            'siswa_nis' => $normalizedSiswaList,
            'akademik_master_tahun_ajaran_id' => $normalizedTa,
            'nama_prestasi' => $collapseLine($this->input('nama_prestasi')),
            'kategori' => strtolower($collapseLine($this->input('kategori'))),
            'tingkat' => $collapseLine($this->input('tingkat')),
            'peringkat' => $collapseLine($this->input('peringkat')),
            'penyelenggara' => $collapseLine($this->input('penyelenggara')),
            'tanggal' => trim((string) ($this->input('tanggal') ?? '')),
            'keterangan' => $keterangan === '' ? null : $keterangan,
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
            'akademik_master_tahun_ajaran_id' => [
                'nullable',
                'integer',
                'exists:akademik_master_tahun_ajaran,id',
            ],
            'nama_prestasi' => ['required', 'string', 'max:255'],
            'kategori' => [
                'required',
                'string',
                Rule::in(array_keys(PrestasiSiswa::KATEGORI_OPTIONS)),
            ],
            'tingkat' => ['required', 'string', 'max:255'],
            'peringkat' => ['required', 'string', 'max:255'],
            'penyelenggara' => ['required', 'string', 'max:255'],
            'tanggal' => ['required', 'date'],
            'keterangan' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_foto' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Pesan validasi kustom yang ringkas dan jelas.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'siswa_nis.required' => 'Pilih minimal satu siswa penerima prestasi.',
            'siswa_nis.min' => 'Pilih minimal satu siswa penerima prestasi.',
            'siswa_nis.*.exists' => 'Terdapat siswa yang tidak terdaftar di data master.',
        ];
    }
}
