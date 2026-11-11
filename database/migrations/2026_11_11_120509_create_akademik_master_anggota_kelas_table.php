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
        Schema::create('akademik_master_anggota_kelas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('siswa_nis', 50);
            $table->unsignedBigInteger('akademik_master_kelas_id');
            $table->string('pendaftaran', 100);
            $table->timestamps();

            $table->unique(
                ['siswa_nis', 'akademik_master_kelas_id'],
                'uq_anggota_kelas_siswa_kelas'
            );
            $table->index(
                ['akademik_master_kelas_id', 'siswa_nis'],
                'idx_anggota_kelas_kelas_siswa'
            );
            $table->index('pendaftaran', 'idx_anggota_kelas_pendaftaran');

            $table->foreign('siswa_nis', 'fk_anggota_kelas_siswa')
                ->references('nis')
                ->on('akademik_master_siswa')
                ->onDelete('cascade')
                ->onUpdate('cascade');

            $table->foreign('akademik_master_kelas_id', 'fk_anggota_kelas_kelas')
                ->references('id')
                ->on('akademik_master_kelas')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_anggota_kelas');
    }
};
