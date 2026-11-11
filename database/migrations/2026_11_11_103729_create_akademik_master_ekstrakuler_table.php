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
        Schema::create('akademik_master_ekstrakuler', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->string('karyawan_nipy')->nullable()->comment('Pembina / Pelatih Ekstrakurikuler');
            $table->string('nama_ekstrakurikuler');
            $table->unsignedBigInteger('biaya')->default(0);
            $table->string('unit', 100);
            $table->timestamps();

            $table->index(['unit', 'nama_ekstrakurikuler'], 'ekstrakuler_unit_nama_index');

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_ekstrakuler_tahun_ajaran')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('cascade');

            $table->foreign('karyawan_nipy', 'fk_ekstrakuler_karyawan')
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
        Schema::dropIfExists('akademik_master_ekstrakuler');
    }
};
