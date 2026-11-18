<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel master target capaian T2Q per tahun ajaran dan tingkat.
     */
    public function up(): void
    {
        Schema::create('akademik_master_t2q_target_capaian', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->string('tingkat', 50);
            $table->unsignedBigInteger('akademik_master_t2q_daftar_jilid_id')->nullable();
            $table->unsignedBigInteger('akademik_master_t2q_surah_id')->nullable();
            $table->timestamps();

            $table->index(
                ['akademik_master_tahun_ajaran_id', 'tingkat'],
                'idx_t2q_tc_ta_tingkat'
            );
            $table->index('akademik_master_t2q_daftar_jilid_id', 'idx_t2q_tc_jilid');
            $table->index('akademik_master_t2q_surah_id', 'idx_t2q_tc_surah');

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_t2q_tc_ta')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('akademik_master_t2q_daftar_jilid_id', 'fk_t2q_tc_jilid')
                ->references('id')
                ->on('akademik_master_t2q_daftar_jilid')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('akademik_master_t2q_surah_id', 'fk_t2q_tc_surah')
                ->references('id')
                ->on('akademik_master_t2q_surah')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Menghapus tabel master target capaian T2Q.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_t2q_target_capaian');
    }
};

