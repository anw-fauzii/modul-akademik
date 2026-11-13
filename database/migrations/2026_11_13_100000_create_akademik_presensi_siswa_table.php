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
        Schema::create('akademik_presensi_siswa', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('anggota_kelas_id');
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->enum('status', ['hadir', 'terlambat', 'izin', 'sakit', 'alpa'])->default('hadir');
            $table->unsignedSmallInteger('keterlambatan_menit')->default(0);
            $table->timestamps();

            $table->unique(
                ['anggota_kelas_id', 'tanggal'],
                'uq_presensi_siswa_anggota_tanggal'
            );
            $table->index('tanggal', 'idx_presensi_siswa_tanggal');
            $table->index('status', 'idx_presensi_siswa_status');

            $table->foreign('anggota_kelas_id', 'fk_presensi_siswa_anggota_kelas')
                ->references('id')
                ->on('akademik_master_anggota_kelas')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_presensi_siswa');
    }
};
