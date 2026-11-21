<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('akademik_penilaian_finalisasi');

        Schema::create('akademik_penilaian_finalisasi', function (Blueprint $table): void {
            $table->id();
            $table->string('scope_key', 140)->unique('uq_fin_scope_key');
            $table->unsignedBigInteger('akademik_master_tahun_ajaran_id');
            $table->string('jenis_komponen', 30);
            $table->unsignedBigInteger('akademik_master_kelas_id')->nullable();
            $table->unsignedBigInteger('akademik_pelajaran_mata_pelajaran_id')->nullable();
            $table->unsignedBigInteger('akademik_master_ekstrakuler_id')->nullable();
            $table->string('periode', 20);
            $table->string('status', 20)->default('draft');
            $table->unsignedBigInteger('finalized_by_user_id')->nullable();
            $table->string('finalized_by_name', 120)->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->unsignedBigInteger('unlocked_by_user_id')->nullable();
            $table->string('unlocked_by_name', 120)->nullable();
            $table->timestamp('unlocked_at')->nullable();
            $table->string('unlock_reason', 500)->nullable();
            $table->timestamps();

            $table->foreign('akademik_master_tahun_ajaran_id', 'fk_fin_ta')
                ->references('id')->on('akademik_master_tahun_ajaran')
                ->cascadeOnDelete();
            $table->foreign('akademik_master_kelas_id', 'fk_fin_kelas')
                ->references('id')->on('akademik_master_kelas')
                ->cascadeOnDelete();
            $table->foreign('akademik_pelajaran_mata_pelajaran_id', 'fk_fin_mapel')
                ->references('id')->on('akademik_pelajaran_mata_pelajaran')
                ->cascadeOnDelete();
            $table->foreign('akademik_master_ekstrakuler_id', 'fk_fin_ekskul')
                ->references('id')->on('akademik_master_ekstrakuler')
                ->cascadeOnDelete();
            $table->foreign('finalized_by_user_id', 'fk_fin_fin_user')
                ->references('id')->on('users')
                ->nullOnDelete();
            $table->foreign('unlocked_by_user_id', 'fk_fin_unl_user')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index(['akademik_master_tahun_ajaran_id', 'periode', 'status'], 'idx_finalisasi_ta_periode_status');
            $table->index(['akademik_master_kelas_id', 'periode', 'jenis_komponen'], 'idx_finalisasi_kelas_periode_jenis');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akademik_penilaian_finalisasi');
    }
};
