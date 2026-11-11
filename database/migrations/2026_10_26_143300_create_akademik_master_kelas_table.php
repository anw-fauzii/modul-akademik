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
        Schema::create('akademik_master_kelas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->string('karyawan_nipy')->nullable()->comment('Wali Kelas / Guru Utama');
            $table->string('pendamping_nipy')->nullable()->comment('Guru Pendamping');
            $table->string('tingkatan_kelas');
            $table->string('nama_kelas');
            $table->string('jenjang_kelas');
            $table->string('romawi');
            $table->timestamps();

            $table->foreign('akademik_master_tahun_ajaran_id')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('cascade');

            $table->foreign('karyawan_nipy')
                ->references('nipy')
                ->on('kepegawaian_master_karyawan')
                ->onDelete('set null');

            $table->foreign('pendamping_nipy')
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
        Schema::dropIfExists('akademik_master_kelas');
    }
};
