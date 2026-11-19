<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel pencatatan harian (yaumiyah) Tahfizh T2Q (1 hari maksimal 1 setoran per anggota T2Q).
     */
    public function up(): void
    {
        Schema::create('akademik_t2q_yaumiyah_tahfizh', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('anggota_t2q_id');
            $table->unsignedBigInteger('akademik_master_t2q_surah_id');
            $table->unsignedBigInteger('akademik_master_t2q_angka_arab_id')->nullable();
            $table->date('tanggal');
            $table->decimal('nilai', 5, 2);
            $table->timestamps();

            $table->unique(['anggota_t2q_id', 'tanggal'], 'uq_t2q_tahfizh_anggota_tgl');
            $table->index('tanggal', 'idx_t2q_tahfizh_tanggal');
            $table->index('akademik_master_t2q_surah_id', 'idx_t2q_tahfizh_surah');
            $table->index('akademik_master_t2q_angka_arab_id', 'idx_t2q_tahfizh_angka');

            $table->foreign('anggota_t2q_id', 'fk_t2q_tahfizh_anggota')
                ->references('id')
                ->on('akademik_master_anggota_t2q')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('akademik_master_t2q_surah_id', 'fk_t2q_tahfizh_surah')
                ->references('id')
                ->on('akademik_master_t2q_surah')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreign('akademik_master_t2q_angka_arab_id', 'fk_t2q_tahfizh_angka')
                ->references('id')
                ->on('akademik_master_t2q_angka_arab')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    /**
     * Menghapus tabel yaumiyah Tahfizh T2Q.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_t2q_yaumiyah_tahfizh');
    }
};

