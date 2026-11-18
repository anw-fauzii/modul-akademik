<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel master daftar jilid T2Q (nama jilid latin, arab, dan jenisnya).
     */
    public function up(): void
    {
        Schema::create('akademik_master_t2q_daftar_jilid', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('jilid_latin', 100);
            $table->string('jilid_arab', 150)->nullable();
            $table->string('jenis', 100)->index('idx_t2q_daftar_jilid_jenis');
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel master daftar jilid T2Q.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_t2q_daftar_jilid');
    }
};

