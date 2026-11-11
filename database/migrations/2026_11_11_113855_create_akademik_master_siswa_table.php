<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('akademik_master_siswa', function (Blueprint $table) {
            $table->string('nis', 50)->primary();

            // Relasi Data Master & Kepegawaian
            $table->unsignedBigInteger('akademik_master_kelas_id')->nullable();
            $table->string('karyawan_nipy')->nullable()->comment('Guru Pembimbing / Wali');
            $table->unsignedBigInteger('akademik_master_ekstrakuler_id')->nullable();
            $table->unsignedBigInteger('akademik_master_jemputan_id')->nullable();

            // Biodata & Identitas Siswa
            $table->string('jenis_pendaftaran', 100)->nullable();
            $table->string('nama_lengkap');
            $table->string('jenis_kelamin', 20);
            $table->string('nisn', 30)->nullable();
            $table->string('nik', 30)->nullable();
            $table->string('no_kk', 30)->nullable();
            $table->string('tempat_lahir', 100)->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('akta_lahir', 100)->nullable();
            $table->string('agama', 50)->nullable();
            $table->string('kewarganegaraan', 50)->nullable()->default('WNI');
            $table->string('nama_negara', 100)->nullable();
            $table->unsignedBigInteger('akademik_pelengkap_berkebutuhan_khusus_id')->nullable();

            // Alamat, Domisili & Transportasi
            $table->text('alamat')->nullable();
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('desa', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('kode_pos', 20)->nullable();
            $table->string('lintang', 50)->nullable();
            $table->string('bujur', 50)->nullable();
            $table->string('tempat_tinggal', 100)->nullable();
            $table->unsignedBigInteger('akademik_pelengkap_transportasi_id')->nullable();
            $table->unsignedSmallInteger('anak_ke')->nullable();
            $table->unsignedSmallInteger('jumlah_saudara')->nullable();

            // Data Orang Tua & Wali (Ayah, Ibu, Wali)
            foreach (['ayah', 'ibu', 'wali'] as $peran) {
                $table->string("nik_{$peran}", 30)->nullable();
                $table->string("nama_{$peran}")->nullable();
                $table->string("lahir_{$peran}", 50)->nullable();
                $table->unsignedBigInteger("akademik_pelengkap_jenjang_pendidikan_{$peran}_id")->nullable();
                $table->unsignedBigInteger("akademik_pelengkap_pekerjaan_{$peran}_id")->nullable();
                $table->unsignedBigInteger("akademik_pelengkap_penghasilan_{$peran}_id")->nullable();
                $table->unsignedBigInteger("akademik_pelengkap_berkebutuhan_khusus_{$peran}_id")->nullable();
            }

            // Kontak
            $table->string('nomor_hp', 30)->nullable();
            $table->string('whatsapp', 30)->nullable();
            $table->string('email')->nullable();

            // Data Periodik & Kesehatan
            $table->unsignedSmallInteger('tinggi_badan')->nullable();
            $table->unsignedSmallInteger('berat_badan')->nullable();
            $table->decimal('jarak', 8, 2)->nullable();
            $table->unsignedSmallInteger('lingkar_kepala')->nullable();
            $table->unsignedSmallInteger('waktu_tempuh')->nullable();

            // Media & Status
            $table->string('avatar')->nullable();
            $table->string('status', 30)->default('Aktif');
            $table->timestamps();

            // Indexes untuk pencarian & filter umum
            $table->index(['akademik_master_kelas_id', 'status', 'nama_lengkap'], 'idx_siswa_kelas_status_nama');
            $table->index('nama_lengkap', 'idx_siswa_nama_lengkap');
            $table->index('nisn', 'idx_siswa_nisn');

            // Foreign Keys Data Master & Kepegawaian
            $table->foreign('akademik_master_kelas_id', 'fk_siswa_kelas')
                ->references('id')
                ->on('akademik_master_kelas')
                ->onDelete('set null');

            $table->foreign('karyawan_nipy', 'fk_siswa_karyawan')
                ->references('nipy')
                ->on('kepegawaian_master_karyawan')
                ->onDelete('set null')
                ->onUpdate('cascade');

            $table->foreign('akademik_master_ekstrakuler_id', 'fk_siswa_ekstrakuler')
                ->references('id')
                ->on('akademik_master_ekstrakuler')
                ->onDelete('set null');

            $table->foreign('akademik_master_jemputan_id', 'fk_siswa_jemputan')
                ->references('id')
                ->on('akademik_master_jemputan')
                ->onDelete('set null');

            // Foreign Keys Data Pelengkap Siswa
            $table->foreign('akademik_pelengkap_berkebutuhan_khusus_id', 'fk_siswa_berkebutuhan_khusus')
                ->references('id')
                ->on('akademik_pelengkap_berkebutuhan_khusus')
                ->onDelete('set null');

            $table->foreign('akademik_pelengkap_transportasi_id', 'fk_siswa_transportasi')
                ->references('id')
                ->on('akademik_pelengkap_transportasi')
                ->onDelete('set null');

            // Foreign Keys Data Pelengkap Orang Tua & Wali
            foreach (['ayah', 'ibu', 'wali'] as $peran) {
                $table->foreign("akademik_pelengkap_jenjang_pendidikan_{$peran}_id", "fk_siswa_jenjang_{$peran}")
                    ->references('id')
                    ->on('akademik_pelengkap_jenjang_pendidikan')
                    ->onDelete('set null');

                $table->foreign("akademik_pelengkap_pekerjaan_{$peran}_id", "fk_siswa_pekerjaan_{$peran}")
                    ->references('id')
                    ->on('akademik_pelengkap_pekerjaan')
                    ->onDelete('set null');

                $table->foreign("akademik_pelengkap_penghasilan_{$peran}_id", "fk_siswa_penghasilan_{$peran}")
                    ->references('id')
                    ->on('akademik_pelengkap_penghasilan')
                    ->onDelete('set null');

                $table->foreign("akademik_pelengkap_berkebutuhan_khusus_{$peran}_id", "fk_siswa_khusus_{$peran}")
                    ->references('id')
                    ->on('akademik_pelengkap_berkebutuhan_khusus')
                    ->onDelete('set null');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_siswa');
    }
};
