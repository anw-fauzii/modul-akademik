<?php

namespace App\Http\Requests\Admin\DataMaster;

use App\Models\Admin\DataMaster\Siswa;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SiswaRequest extends FormRequest
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
        /** @var Siswa|null $siswa */
        $siswa = $this->route('siswa');

        $rules = [
            'nis' => [
                'required',
                'string',
                'max:50',
                'not_regex:/[\/\\\\]/',
                Rule::unique('akademik_master_siswa', 'nis')->ignore($siswa?->getKey(), 'nis'),
            ],
            'akademik_master_kelas_id' => [
                'nullable',
                'integer',
                'exists:akademik_master_kelas,id',
            ],
            'karyawan_nipy' => [
                'nullable',
                'string',
                'max:255',
                'exists:kepegawaian_master_karyawan,nipy',
            ],
            'akademik_master_ekstrakuler_id' => [
                'nullable',
                'integer',
                'exists:akademik_master_ekstrakuler,id',
            ],
            'akademik_master_jemputan_id' => [
                'nullable',
                'integer',
                'exists:akademik_master_jemputan,id',
            ],
            'jenis_pendaftaran' => ['nullable', 'string', 'max:100'],
            'nama_lengkap' => ['required', 'string', 'max:255'],
            'jenis_kelamin' => [
                'required',
                'string',
                Rule::in(array_keys(Siswa::JENIS_KELAMIN_OPTIONS)),
            ],
            'nisn' => ['nullable', 'string', 'max:30'],
            'nik' => ['nullable', 'string', 'max:30'],
            'no_kk' => ['nullable', 'string', 'max:30'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'akta_lahir' => ['nullable', 'string', 'max:100'],
            'agama' => ['nullable', 'string', 'max:50'],
            'kewarganegaraan' => ['nullable', 'string', 'max:50'],
            'nama_negara' => ['nullable', 'string', 'max:100'],
            'akademik_pelengkap_berkebutuhan_khusus_id' => [
                'nullable',
                'integer',
                'exists:akademik_pelengkap_berkebutuhan_khusus,id',
            ],
            'alamat' => ['nullable', 'string'],
            'rt' => ['nullable', 'string', 'max:10'],
            'rw' => ['nullable', 'string', 'max:10'],
            'desa' => ['nullable', 'string', 'max:100'],
            'kecamatan' => ['nullable', 'string', 'max:100'],
            'kabupaten' => ['nullable', 'string', 'max:100'],
            'provinsi' => ['nullable', 'string', 'max:100'],
            'kode_pos' => ['nullable', 'string', 'max:20'],
            'lintang' => ['nullable', 'string', 'max:50'],
            'bujur' => ['nullable', 'string', 'max:50'],
            'tempat_tinggal' => ['nullable', 'string', 'max:100'],
            'akademik_pelengkap_transportasi_id' => [
                'nullable',
                'integer',
                'exists:akademik_pelengkap_transportasi,id',
            ],
            'anak_ke' => ['nullable', 'integer', 'min:1'],
            'jumlah_saudara' => ['nullable', 'integer', 'min:0'],
            'nomor_hp' => ['nullable', 'string', 'max:30'],
            'whatsapp' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'tinggi_badan' => ['nullable', 'integer', 'min:0'],
            'berat_badan' => ['nullable', 'integer', 'min:0'],
            'jarak' => ['nullable', 'numeric', 'min:0'],
            'lingkar_kepala' => ['nullable', 'integer', 'min:0'],
            'waktu_tempuh' => ['nullable', 'integer', 'min:0'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
            'status' => [
                'required',
                'string',
                Rule::in(Siswa::STATUS_OPTIONS),
            ],
        ];

        foreach (['ayah', 'ibu', 'wali'] as $peran) {
            $rules["nik_{$peran}"] = ['nullable', 'string', 'max:30'];
            $rules["nama_{$peran}"] = ['nullable', 'string', 'max:255'];
            $rules["lahir_{$peran}"] = ['nullable', 'string', 'max:50'];
            $rules["akademik_pelengkap_jenjang_pendidikan_{$peran}_id"] = [
                'nullable',
                'integer',
                'exists:akademik_pelengkap_jenjang_pendidikan,id',
            ];
            $rules["akademik_pelengkap_pekerjaan_{$peran}_id"] = [
                'nullable',
                'integer',
                'exists:akademik_pelengkap_pekerjaan,id',
            ];
            $rules["akademik_pelengkap_penghasilan_{$peran}_id"] = [
                'nullable',
                'integer',
                'exists:akademik_pelengkap_penghasilan,id',
            ];
            $rules["akademik_pelengkap_berkebutuhan_khusus_{$peran}_id"] = [
                'nullable',
                'integer',
                'exists:akademik_pelengkap_berkebutuhan_khusus,id',
            ];
        }

        return $rules;
    }

    /**
     * Pesan validasi kustom dalam Bahasa Indonesia.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nis.required' => 'NIS wajib diisi.',
            'nis.max' => 'NIS maksimal 50 karakter.',
            'nis.not_regex' => 'NIS tidak boleh mengandung karakter slash (/ atau \\).',
            'nis.unique' => 'NIS tersebut sudah terdaftar pada siswa lain.',
            'akademik_master_kelas_id.exists' => 'Kelas yang dipilih tidak valid.',
            'karyawan_nipy.exists' => 'Guru Pembimbing / Wali tidak ditemukan dalam database.',
            'akademik_master_ekstrakuler_id.exists' => 'Ekstrakurikuler yang dipilih tidak valid.',
            'akademik_master_jemputan_id.exists' => 'Layanan jemputan yang dipilih tidak valid.',
            'nama_lengkap.required' => 'Nama lengkap siswa wajib diisi.',
            'nama_lengkap.max' => 'Nama lengkap maksimal 255 karakter.',
            'jenis_kelamin.required' => 'Jenis kelamin wajib dipilih.',
            'jenis_kelamin.in' => 'Jenis kelamin tidak valid.',
            'tanggal_lahir.date' => 'Format tanggal lahir tidak valid.',
            'email.email' => 'Format alamat email tidak valid.',
            'akademik_pelengkap_berkebutuhan_khusus_id.exists' => 'Data berkebutuhan khusus tidak valid.',
            'akademik_pelengkap_transportasi_id.exists' => 'Data transportasi tidak valid.',
            'anak_ke.min' => 'Anak ke minimal bernilai 1.',
            'jumlah_saudara.min' => 'Jumlah saudara tidak boleh bernilai negatif.',
            'tinggi_badan.min' => 'Tinggi badan tidak boleh bernilai negatif.',
            'berat_badan.min' => 'Berat badan tidak boleh bernilai negatif.',
            'jarak.min' => 'Jarak tempuh tidak boleh bernilai negatif.',
            'lingkar_kepala.min' => 'Lingkar kepala tidak boleh bernilai negatif.',
            'waktu_tempuh.min' => 'Waktu tempuh tidak boleh bernilai negatif.',
            'avatar.image' => 'Avatar harus berupa file gambar.',
            'avatar.mimes' => 'Avatar harus berformat JPG, JPEG, PNG, atau WEBP.',
            'avatar.max' => 'Ukuran avatar maksimal 2 MB.',
            'status.required' => 'Status siswa wajib dipilih.',
            'status.in' => 'Status siswa tidak valid.',
        ];
    }
}
