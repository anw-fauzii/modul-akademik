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
        Schema::create('akademik_master_anggota_ekstrakuler', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_kelas_id');
            $table->unsignedBigInteger('akademik_master_ekstrakuler_id');
            $table->string('pendaftaran', 100);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(
                ['anggota_kelas_id', 'akademik_master_ekstrakuler_id'],
                'uq_anggota_ekskul_anggota_kelas_ekskul'
            );
            $table->index(
                ['akademik_master_ekstrakuler_id', 'pendaftaran'],
                'idx_anggota_ekskul_ekskul_pendaftaran'
            );

            $table->foreign('anggota_kelas_id', 'fk_anggota_ekskul_anggota_kelas')
                ->references('id')
                ->on('akademik_master_anggota_kelas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('akademik_master_ekstrakuler_id', 'fk_anggota_ekskul_ekskul')
                ->references('id')
                ->on('akademik_master_ekstrakuler')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_anggota_ekstrakuler');
    }
};
