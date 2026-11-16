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
        Schema::create('akademik_pelajaran_tujuan_pembelajaran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->unsignedBigInteger('akademik_pelajaran_mata_pelajaran_id');
            $table->string('tingkatan_kelas', 20);
            $table->string('fase', 20)->nullable();
            $table->string('elemen', 150)->nullable();
            $table->string('kode_tp', 30);
            $table->text('tujuan_pembelajaran');
            $table->string('lingkup_materi', 255)->nullable();
            $table->unsignedSmallInteger('urutan')->default(1);
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(
                [
                    'akademik_master_tahun_ajaran_id',
                    'akademik_pelajaran_mata_pelajaran_id',
                    'tingkatan_kelas',
                    'kode_tp',
                ],
                'uq_jp_tp_ta_mapel_tingkat_kode'
            );
            $table->index(
                ['akademik_master_tahun_ajaran_id', 'tingkatan_kelas', 'status'],
                'idx_jp_tp_ta_tingkat_status'
            );
            $table->index(
                ['akademik_pelajaran_mata_pelajaran_id', 'tingkatan_kelas', 'urutan'],
                'idx_jp_tp_mapel_tingkat_urutan'
            );
            $table->index('fase', 'idx_jp_tp_fase');

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_jp_tp_tahun_ajaran')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('cascade');

            $table->foreign('akademik_pelajaran_mata_pelajaran_id', 'fk_jp_tp_mata_pelajaran')
                ->references('id')
                ->on('akademik_pelajaran_mata_pelajaran')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_pelajaran_tujuan_pembelajaran');
    }
};
