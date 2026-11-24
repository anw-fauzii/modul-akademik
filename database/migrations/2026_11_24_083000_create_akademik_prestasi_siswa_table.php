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
        Schema::create('akademik_prestasi_siswa', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id')->nullable();
            $table->string('nama_prestasi', 255);
            $table->enum('kategori', ['akademik', 'non_akademik']);
            $table->string('tingkat', 255);
            $table->string('peringkat', 255);
            $table->string('penyelenggara', 255);
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->string('foto', 255)->nullable();
            $table->timestamps();

            $table->index(
                ['akademik_master_tahun_ajaran_id', 'kategori', 'tanggal'],
                'idx_prestasi_siswa_ta_kategori_tanggal'
            );
            $table->index(['tingkat', 'tanggal'], 'idx_prestasi_siswa_tingkat_tanggal');
            $table->index('tanggal', 'idx_prestasi_siswa_tanggal');

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_prestasi_siswa_tahun_ajaran')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('set null');
        });

        Schema::create('akademik_prestasi_siswa_anggota', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('prestasi_siswa_id');
            $table->string('siswa_nis', 50);
            $table->timestamps();

            $table->unique(
                ['prestasi_siswa_id', 'siswa_nis'],
                'uq_prestasi_anggota_prestasi_siswa'
            );
            $table->index(
                ['siswa_nis', 'prestasi_siswa_id'],
                'idx_prestasi_anggota_siswa_prestasi'
            );

            $table->foreign('prestasi_siswa_id', 'fk_prestasi_anggota_prestasi')
                ->references('id')
                ->on('akademik_prestasi_siswa')
                ->onDelete('cascade');

            $table->foreign('siswa_nis', 'fk_prestasi_anggota_siswa')
                ->references('nis')
                ->on('akademik_master_siswa')
                ->onDelete('cascade')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_prestasi_siswa_anggota');
        Schema::dropIfExists('akademik_prestasi_siswa');
    }
};
