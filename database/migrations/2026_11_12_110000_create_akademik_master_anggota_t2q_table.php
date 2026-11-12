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
        Schema::create('akademik_master_anggota_t2q', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_kelas_id');
            $table->string('karyawan_nipy')->comment('Guru Pembimbing T2Q');
            $table->string('tingkat', 50)->comment('Tingkat / Jenjang Kelas T2Q (misal PG, A, B, 1..9)');
            $table->string('pendaftaran', 100);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique('anggota_kelas_id', 'uq_anggota_t2q_anggota_kelas');
            $table->index(['karyawan_nipy', 'tingkat'], 'idx_anggota_t2q_karyawan_tingkat');
            $table->index('tingkat', 'idx_anggota_t2q_tingkat');
            $table->index('pendaftaran', 'idx_anggota_t2q_pendaftaran');

            $table->foreign('anggota_kelas_id', 'fk_anggota_t2q_anggota_kelas')
                ->references('id')
                ->on('akademik_master_anggota_kelas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('karyawan_nipy', 'fk_anggota_t2q_karyawan')
                ->references('nipy')
                ->on('kepegawaian_master_karyawan')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_anggota_t2q');
    }
};
