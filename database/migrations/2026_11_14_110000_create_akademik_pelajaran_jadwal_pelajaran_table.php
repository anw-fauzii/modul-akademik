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
        Schema::create('akademik_pelajaran_jadwal_pelajaran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->unsignedBigInteger('akademik_master_kelas_id');
            $table->unsignedBigInteger('akademik_pelajaran_jam_pelajaran_id');
            $table->unsignedBigInteger('akademik_pelajaran_mata_pelajaran_id')->nullable();
            $table->string('karyawan_nipy')->nullable();
            $table->unsignedTinyInteger('hari');
            $table->string('ruang', 50)->nullable();
            $table->text('keterangan')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(
                [
                    'akademik_master_tahun_ajaran_id',
                    'akademik_master_kelas_id',
                    'hari',
                    'akademik_pelajaran_jam_pelajaran_id',
                ],
                'uq_jp_jadwal_kelas_hari_jam'
            );
            $table->index(
                ['akademik_master_tahun_ajaran_id', 'akademik_master_kelas_id', 'hari'],
                'idx_jp_jadwal_ta_kelas_hari'
            );
            $table->index(
                ['akademik_master_tahun_ajaran_id', 'hari', 'karyawan_nipy'],
                'idx_jp_jadwal_ta_hari_guru'
            );
            $table->index(
                ['akademik_pelajaran_jam_pelajaran_id', 'hari'],
                'idx_jp_jadwal_jam_hari'
            );
            $table->index('status', 'idx_jp_jadwal_status');

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_jp_jadwal_tahun_ajaran')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('cascade');

            $table->foreign('akademik_master_kelas_id', 'fk_jp_jadwal_kelas')
                ->references('id')
                ->on('akademik_master_kelas')
                ->onDelete('cascade');

            $table->foreign('akademik_pelajaran_jam_pelajaran_id', 'fk_jp_jadwal_jam_pelajaran')
                ->references('id')
                ->on('akademik_pelajaran_jam_pelajaran')
                ->onDelete('cascade');

            $table->foreign('akademik_pelajaran_mata_pelajaran_id', 'fk_jp_jadwal_mata_pelajaran')
                ->references('id')
                ->on('akademik_pelajaran_mata_pelajaran')
                ->onDelete('set null');

            $table->foreign('karyawan_nipy', 'fk_jp_jadwal_karyawan')
                ->references('nipy')
                ->on('kepegawaian_master_karyawan')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_pelajaran_jadwal_pelajaran');
    }
};
