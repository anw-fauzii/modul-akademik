<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel master titimangsa & pejabat penandatangan rapor per tahun ajaran, jenjang, tingkat, dan periode.
     */
    public function up(): void
    {
        Schema::create('akademik_master_titimangsa', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->string('jenjang_kelas', 20)->comment('Jenjang pendidikan: PG, TK, SD, SMP, dll.');
            $table->string('tingkatan_kelas', 20)->default('*')->comment('* untuk semua tingkat dalam jenjang, atau tingkat spesifik seperti 6');
            $table->enum('periode', ['mid', 'semester']);
            $table->string('tempat', 100);
            $table->date('tanggal_titimangsa');
            $table->string('kepala_sekolah_nipy')->nullable();
            $table->string('nama_kepala_sekolah_snapshot', 150)->nullable();
            $table->string('jabatan_kepala_sekolah', 100)->default('Kepala Sekolah');
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(
                ['akademik_master_tahun_ajaran_id', 'jenjang_kelas', 'tingkatan_kelas', 'periode'],
                'uq_titimangsa_ta_jenjang_tingkat_per'
            );
            $table->index(
                ['akademik_master_tahun_ajaran_id', 'jenjang_kelas', 'periode', 'status'],
                'idx_titimangsa_lookup'
            );

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_titimangsa_ta')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('cascade');

            $table->foreign('kepala_sekolah_nipy', 'fk_titimangsa_kepsek')
                ->references('nipy')
                ->on('kepegawaian_master_karyawan')
                ->onUpdate('cascade')
                ->onDelete('set null');
        });
    }

    /**
     * Menghapus tabel master titimangsa rapor.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_titimangsa');
    }
};

