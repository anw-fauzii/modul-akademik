<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel rekap nilai & deskripsi rapor T2Q (Tahsin & Tahfidz) per anggota T2Q dan periode rapor.
     */
    public function up(): void
    {
        Schema::create('akademik_t2q_rekap_rapor', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->unsignedBigInteger('anggota_t2q_id');
            $table->string('periode', 20);

            $table->unsignedBigInteger('tahsin_yaumiyah_id')->nullable();
            $table->string('tahsin_materi', 255)->nullable();
            $table->decimal('tahsin_nilai', 5, 2)->nullable();
            $table->string('tahsin_predikat', 10)->nullable();
            $table->string('tahsin_keunggulan', 255)->nullable();
            $table->string('tahsin_peningkatan', 255)->nullable();
            $table->text('tahsin_deskripsi')->nullable();

            $table->unsignedBigInteger('tahfizh_yaumiyah_id')->nullable();
            $table->string('tahfizh_materi', 255)->nullable();
            $table->decimal('tahfizh_nilai', 5, 2)->nullable();
            $table->string('tahfizh_predikat', 10)->nullable();
            $table->string('tahfizh_keunggulan', 255)->nullable();
            $table->string('tahfizh_peningkatan', 255)->nullable();
            $table->text('tahfizh_deskripsi')->nullable();

            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->timestamps();

            $table->unique(['anggota_t2q_id', 'periode'], 'uq_t2q_rekap_anggota_periode');
            $table->index(['akademik_master_tahun_ajaran_id', 'periode'], 'idx_t2q_rekap_ta_periode');

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_t2q_rekap_ta')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('anggota_t2q_id', 'fk_t2q_rekap_anggota')
                ->references('id')
                ->on('akademik_master_anggota_t2q')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('tahsin_yaumiyah_id', 'fk_t2q_rekap_tahsin_y')
                ->references('id')
                ->on('akademik_t2q_yaumiyah_tahsin')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('tahfizh_yaumiyah_id', 'fk_t2q_rekap_tahfizh_y')
                ->references('id')
                ->on('akademik_t2q_yaumiyah_tahfizh')
                ->cascadeOnUpdate()
                ->nullOnDelete();

            $table->foreign('updated_by_user_id', 'fk_t2q_rekap_user')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Menghapus tabel rekap rapor T2Q.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_t2q_rekap_rapor');
    }
};
