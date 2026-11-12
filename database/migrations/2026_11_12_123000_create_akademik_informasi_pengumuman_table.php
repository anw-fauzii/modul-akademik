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
        Schema::create('akademik_informasi_pengumuman', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->string('judul');
            $table->text('isi');
            $table->enum('jenis', ['siswa', 'pegawai'])->default('siswa');
            $table->timestamps();

            $table->index(
                ['akademik_master_tahun_ajaran_id', 'jenis', 'created_at'],
                'idx_pengumuman_ta_jenis_created'
            );

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_pengumuman_tahun_ajaran')
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
        Schema::dropIfExists('akademik_informasi_pengumuman');
    }
};
