<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat 7 tabel modul Penilaian Tujuan Pembelajaran (SD), KKM, dan Rentang Predikat.
     */
    public function up(): void
    {
        Schema::create('akademik_penilaian_kkm', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->unsignedBigInteger('akademik_pelajaran_mata_pelajaran_id');
            $table->string('tingkatan_kelas', 20);
            $table->decimal('nilai_kkm', 5, 2)->default(75.00);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(
                [
                    'akademik_master_tahun_ajaran_id',
                    'akademik_pelajaran_mata_pelajaran_id',
                    'tingkatan_kelas',
                ],
                'uq_penilaian_kkm_ta_mp_tingkat'
            );
            $table->index(
                ['akademik_master_tahun_ajaran_id', 'tingkatan_kelas', 'status'],
                'idx_penilaian_kkm_ta_tingkat_status'
            );

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_penilaian_kkm_ta')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('cascade');

            $table->foreign('akademik_pelajaran_mata_pelajaran_id', 'fk_penilaian_kkm_mp')
                ->references('id')
                ->on('akademik_pelajaran_mata_pelajaran')
                ->onDelete('cascade');
        });

        Schema::create('akademik_penilaian_kkm_predikat', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('kkm_id');
            $table->string('predikat', 20);
            $table->string('keterangan', 100);
            $table->decimal('nilai_min', 5, 2);
            $table->decimal('nilai_max', 5, 2);
            $table->boolean('is_tuntas')->default(true);
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->timestamps();

            $table->unique(['kkm_id', 'predikat'], 'uq_penilaian_kkm_predikat_kode');
            $table->index(['kkm_id', 'nilai_min', 'nilai_max'], 'idx_penilaian_kkm_predikat_range');
            $table->index(['kkm_id', 'urutan'], 'idx_penilaian_kkm_predikat_urutan');

            $table->foreign('kkm_id', 'fk_penilaian_kkm_predikat_kkm')
                ->references('id')
                ->on('akademik_penilaian_kkm')
                ->onDelete('cascade');
        });

        Schema::create('akademik_penilaian_periode_tp', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tujuan_pembelajaran_id');
            $table->enum('periode', ['mid', 'semester']);
            $table->boolean('masuk_rapor')->default(true);
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->timestamps();

            $table->unique(['tujuan_pembelajaran_id', 'periode'], 'uq_penilaian_periode_tp_tp_per');
            $table->index(['periode', 'masuk_rapor'], 'idx_penilaian_periode_tp_rapor');

            $table->foreign('tujuan_pembelajaran_id', 'fk_penilaian_periode_tp_tp')
                ->references('id')
                ->on('akademik_pelajaran_tujuan_pembelajaran')
                ->onDelete('cascade');
        });

        Schema::create('akademik_penilaian_aspek_tp', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tujuan_pembelajaran_id');
            $table->enum('periode', ['mid', 'semester']);
            $table->string('nama_aspek', 100);
            $table->enum('teknik_penilaian', ['tulis', 'lisan', 'praktik', 'proyek', 'portofolio', 'observasi'])
                ->nullable();
            $table->unsignedSmallInteger('bobot')->default(100);
            $table->boolean('masuk_hitung_na_tp')->default(true);
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->timestamps();

            $table->unique(
                ['tujuan_pembelajaran_id', 'periode', 'nama_aspek'],
                'uq_penilaian_aspek_tp_nama'
            );
            $table->index(
                ['tujuan_pembelajaran_id', 'periode', 'urutan'],
                'idx_penilaian_aspek_tp_urutan'
            );

            $table->foreign('tujuan_pembelajaran_id', 'fk_penilaian_aspek_tp_tp')
                ->references('id')
                ->on('akademik_pelajaran_tujuan_pembelajaran')
                ->onDelete('cascade');
        });

        Schema::create('akademik_penilaian_nilai_aspek', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('aspek_tp_id');
            $table->unsignedBigInteger('anggota_kelas_id');
            $table->decimal('nilai', 5, 2)->nullable();
            $table->boolean('is_remedial')->default(false);
            $table->string('catatan', 255)->nullable();
            $table->string('dinilai_oleh_nipy', 50)->nullable();
            $table->timestamps();

            $table->unique(['aspek_tp_id', 'anggota_kelas_id'], 'uq_penilaian_nilai_aspek_siswa');
            $table->index('anggota_kelas_id', 'idx_penilaian_nilai_aspek_anggota');

            $table->foreign('aspek_tp_id', 'fk_penilaian_nilai_aspek_aspek')
                ->references('id')
                ->on('akademik_penilaian_aspek_tp')
                ->onDelete('cascade');

            $table->foreign('anggota_kelas_id', 'fk_penilaian_nilai_aspek_anggota')
                ->references('id')
                ->on('akademik_master_anggota_kelas')
                ->onDelete('cascade');
        });

        Schema::create('akademik_penilaian_rekap_tp', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('tujuan_pembelajaran_id');
            $table->unsignedBigInteger('anggota_kelas_id');
            $table->enum('periode', ['mid', 'semester']);
            $table->decimal('nilai_akhir_tp', 5, 2)->nullable();
            $table->string('predikat', 20)->nullable();
            $table->boolean('tuntas_kkm')->nullable();
            $table->boolean('is_manual_override')->default(false);
            $table->timestamps();

            $table->unique(
                ['tujuan_pembelajaran_id', 'anggota_kelas_id', 'periode'],
                'uq_penilaian_rekap_tp_siswa_per'
            );
            $table->index(['anggota_kelas_id', 'periode'], 'idx_penilaian_rekap_tp_ang_per');

            $table->foreign('tujuan_pembelajaran_id', 'fk_penilaian_rekap_tp_tp')
                ->references('id')
                ->on('akademik_pelajaran_tujuan_pembelajaran')
                ->onDelete('cascade');

            $table->foreign('anggota_kelas_id', 'fk_penilaian_rekap_tp_anggota')
                ->references('id')
                ->on('akademik_master_anggota_kelas')
                ->onDelete('cascade');
        });

        Schema::create('akademik_penilaian_rekap_mapel', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->unsignedBigInteger('akademik_pelajaran_mata_pelajaran_id');
            $table->unsignedBigInteger('anggota_kelas_id');
            $table->enum('periode', ['mid', 'semester']);
            $table->decimal('rata_rata_tp', 5, 2)->nullable();
            $table->decimal('nilai_akhir_rapor', 5, 2)->nullable();
            $table->unsignedSmallInteger('jumlah_tp_dinilai')->default(0);
            $table->decimal('nilai_kkm_snapshot', 5, 2)->nullable();
            $table->string('predikat_rapor', 20)->nullable();
            $table->string('keterangan_predikat', 100)->nullable();
            $table->boolean('tuntas_kkm')->nullable();
            $table->text('deskripsi_capaian')->nullable();
            $table->timestamps();

            $table->unique(
                ['akademik_pelajaran_mata_pelajaran_id', 'anggota_kelas_id', 'periode'],
                'uq_penilaian_rekap_mp_siswa_per'
            );
            $table->index(
                ['akademik_master_tahun_ajaran_id', 'akademik_pelajaran_mata_pelajaran_id', 'periode'],
                'idx_penilaian_rekap_mp_ta_mp_per'
            );

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_penilaian_rekap_mp_ta')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('cascade');

            $table->foreign('akademik_pelajaran_mata_pelajaran_id', 'fk_penilaian_rekap_mp_mp')
                ->references('id')
                ->on('akademik_pelajaran_mata_pelajaran')
                ->onDelete('cascade');

            $table->foreign('anggota_kelas_id', 'fk_penilaian_rekap_mp_anggota')
                ->references('id')
                ->on('akademik_master_anggota_kelas')
                ->onDelete('cascade');
        });
    }

    /**
     * Menghapus seluruh tabel modul Penilaian TP dalam urutan balik foreign key.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_penilaian_rekap_mapel');
        Schema::dropIfExists('akademik_penilaian_rekap_tp');
        Schema::dropIfExists('akademik_penilaian_nilai_aspek');
        Schema::dropIfExists('akademik_penilaian_aspek_tp');
        Schema::dropIfExists('akademik_penilaian_periode_tp');
        Schema::dropIfExists('akademik_penilaian_kkm_predikat');
        Schema::dropIfExists('akademik_penilaian_kkm');
    }
};
