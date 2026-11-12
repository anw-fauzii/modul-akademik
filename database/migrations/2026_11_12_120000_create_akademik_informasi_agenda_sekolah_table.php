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
        Schema::create('akademik_informasi_agenda_sekolah', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->string('nama_agenda');
            $table->date('tanggal');
            $table->time('jam');
            $table->string('unit', 100);
            $table->enum('jenis', ['siswa', 'pegawai'])->default('siswa');
            $table->timestamps();

            $table->index(
                ['akademik_master_tahun_ajaran_id', 'jenis', 'tanggal', 'jam'],
                'idx_agenda_sekolah_ta_jenis_waktu'
            );
            $table->index(
                ['jenis', 'unit', 'tanggal'],
                'idx_agenda_sekolah_jenis_unit_tanggal'
            );

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_agenda_sekolah_tahun_ajaran')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_informasi_agenda_sekolah');
    }
};
