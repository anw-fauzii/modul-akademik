<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan tabel kolom & nilai Sumatif Bab dinamis per mapel + dukungan bobot 3 komponen (NA TP, Sumatif Bab, STS/SAS).
     */
    public function up(): void
    {
        Schema::create('akademik_penilaian_sumatif_bab', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->unsignedBigInteger('akademik_pelajaran_mata_pelajaran_id');
            $table->string('tingkatan_kelas', 20);
            $table->enum('periode', ['mid', 'semester']);
            $table->string('nama_bab', 100);
            $table->string('keterangan', 255)->nullable();
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->timestamps();

            $table->unique(
                [
                    'akademik_master_tahun_ajaran_id',
                    'akademik_pelajaran_mata_pelajaran_id',
                    'tingkatan_kelas',
                    'periode',
                    'nama_bab',
                ],
                'uq_penilaian_sumatif_bab_scope'
            );

            $table->index(
                [
                    'akademik_master_tahun_ajaran_id',
                    'akademik_pelajaran_mata_pelajaran_id',
                    'tingkatan_kelas',
                    'periode',
                    'urutan',
                ],
                'idx_penilaian_sumatif_bab_lookup'
            );

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_penilaian_sumatif_bab_ta')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('cascade');

            $table->foreign('akademik_pelajaran_mata_pelajaran_id', 'fk_penilaian_sumatif_bab_mp')
                ->references('id')
                ->on('akademik_pelajaran_mata_pelajaran')
                ->onDelete('cascade');
        });

        Schema::create('akademik_penilaian_nilai_sumatif_bab', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('sumatif_bab_id');
            $table->unsignedBigInteger('anggota_kelas_id');
            $table->decimal('nilai', 5, 2)->nullable();
            $table->string('dinilai_oleh_nipy', 50)->nullable();
            $table->timestamps();

            $table->unique(['sumatif_bab_id', 'anggota_kelas_id'], 'uq_penilaian_nilai_sumatif_siswa');
            $table->index('anggota_kelas_id', 'idx_penilaian_nilai_sumatif_anggota');

            $table->foreign('sumatif_bab_id', 'fk_penilaian_nilai_sumatif_bab')
                ->references('id')
                ->on('akademik_penilaian_sumatif_bab')
                ->onDelete('cascade');

            $table->foreign('anggota_kelas_id', 'fk_penilaian_nilai_sumatif_anggota')
                ->references('id')
                ->on('akademik_master_anggota_kelas')
                ->onDelete('cascade');
        });

        Schema::table('akademik_penilaian_kkm', function (Blueprint $table) {
            $table->unsignedTinyInteger('bobot_sumatif')->default(0)->after('bobot_tp');
        });

        Schema::table('akademik_penilaian_rekap_mapel', function (Blueprint $table) {
            $table->decimal('rata_rata_sumatif', 5, 2)->nullable()->after('rata_rata_tp');
            $table->unsignedTinyInteger('bobot_sumatif_snapshot')->nullable()->after('bobot_tp_snapshot');
        });
    }

    /**
     * Menghapus tabel & kolom Sumatif Bab jika rollback dilakukan.
     */
    public function down(): void
    {
        Schema::table('akademik_penilaian_rekap_mapel', function (Blueprint $table) {
            $table->dropColumn(['rata_rata_sumatif', 'bobot_sumatif_snapshot']);
        });

        Schema::table('akademik_penilaian_kkm', function (Blueprint $table) {
            $table->dropColumn(['bobot_sumatif']);
        });

        Schema::dropIfExists('akademik_penilaian_nilai_sumatif_bab');
        Schema::dropIfExists('akademik_penilaian_sumatif_bab');
    }
};

