<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel master surah Al-Qur'an untuk modul T2Q.
     */
    public function up(): void
    {
        Schema::create('akademik_master_t2q_surah', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nama_surah', 100)->index('idx_t2q_surah_nama');
            $table->string('nama_surah_arab', 150)->nullable();
            $table->unsignedInteger('jumlah_ayat')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel master surah Al-Qur'an T2Q.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_t2q_surah');
    }
};

