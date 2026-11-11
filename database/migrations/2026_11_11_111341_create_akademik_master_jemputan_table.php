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
        Schema::create('akademik_master_jemputan', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->string('karyawan_nipy')->comment('Petugas / Driver Jemputan');
            $table->unsignedBigInteger('harga_pp')->default(0)->comment('Tarif Pulang-Pergi (PP)');
            $table->unsignedBigInteger('harga_setengah')->default(0)->comment('Tarif Sekali Jalan / Setengah');
            $table->timestamps();

            $table->index(
                ['akademik_master_tahun_ajaran_id', 'karyawan_nipy'],
                'jemputan_tahun_ajaran_karyawan_index'
            );

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_jemputan_tahun_ajaran')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->onDelete('cascade');

            $table->foreign('karyawan_nipy', 'fk_jemputan_karyawan')
                ->references('nipy')
                ->on('kepegawaian_master_karyawan')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_jemputan');
    }
};
