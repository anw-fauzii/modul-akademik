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
        Schema::create('akademik_master_anggota_jemputan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('anggota_kelas_id');
            $table->unsignedBigInteger('akademik_master_jemputan_id');
            $table->string('pendaftaran', 100);
            $table->string('keterangan', 255)->nullable();
            $table->timestamps();

            $table->unique(
                ['anggota_kelas_id', 'akademik_master_jemputan_id'],
                'uq_anggota_jemputan_ak_jemputan'
            );
            $table->index(
                ['akademik_master_jemputan_id', 'pendaftaran'],
                'idx_anggota_jemputan_pendaftaran'
            );

            $table->foreign('anggota_kelas_id', 'fk_anggota_jemputan_anggota_kelas')
                ->references('id')
                ->on('akademik_master_anggota_kelas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('akademik_master_jemputan_id', 'fk_anggota_jemputan_jemputan')
                ->references('id')
                ->on('akademik_master_jemputan')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_anggota_jemputan');
    }
};

