<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel catatan/komentar rapor Wali Kelas & Pendamping per anggota kelas dan periode (Mid / Semester).
     */
    public function up(): void
    {
        Schema::create('akademik_rapor_catatan_wali', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->unsignedBigInteger('anggota_kelas_id');
            $table->string('periode', 20);
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('komentar_rapor_id')->nullable();
            $table->unsignedBigInteger('updated_by_user_id')->nullable();
            $table->string('updated_by_name')->nullable();
            $table->string('updated_by_role_label', 50)->nullable();
            $table->timestamps();

            $table->unique(['anggota_kelas_id', 'periode'], 'uq_catatan_wali_anggota_periode');
            $table->index(['akademik_master_tahun_ajaran_id', 'periode'], 'idx_catatan_wali_ta_periode');

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_catatan_wali_ta')
                ->references('id')
                ->on('akademik_master_tahun_ajaran')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('anggota_kelas_id', 'fk_catatan_wali_anggota')
                ->references('id')
                ->on('akademik_master_anggota_kelas')
                ->cascadeOnUpdate()
                ->cascadeOnDelete();

            $table->foreign('komentar_rapor_id', 'fk_catatan_wali_template')
                ->references('id')
                ->on('akademik_master_komentar_rapor')
                ->nullOnDelete();

            $table->foreign('updated_by_user_id', 'fk_catatan_wali_user')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Menghapus tabel catatan rapor wali kelas & pendamping.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_rapor_catatan_wali');
    }
};
