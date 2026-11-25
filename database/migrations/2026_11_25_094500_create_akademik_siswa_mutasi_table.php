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
        Schema::create('akademik_siswa_mutasi', function (Blueprint $table) {
            $table->id();
            $table->string('siswa_nis', 50);
            $table->foreign('siswa_nis')
                ->references('nis')
                ->on('akademik_master_siswa')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
            $table->foreignId('akademik_master_tahun_ajaran_id')
                ->nullable()
                ->constrained('akademik_master_tahun_ajaran')
                ->nullOnDelete();
            $table->foreignId('kelas_asal_id')
                ->nullable()
                ->constrained('akademik_master_kelas')
                ->nullOnDelete();
            $table->foreignId('kelas_tujuan_id')
                ->nullable()
                ->constrained('akademik_master_kelas')
                ->nullOnDelete();
            $table->string('unit', 20)->nullable();
            $table->enum('kategori', ['masuk', 'lulus', 'pindah', 'keluar', 'nonaktif']);
            $table->string('status_sebelum', 30)->nullable();
            $table->string('status_sesudah', 30);
            $table->date('tanggal_efektif');
            $table->string('nomor_surat', 100)->nullable();
            $table->string('nomor_ijazah', 100)->nullable();
            $table->string('sekolah_asal_tujuan', 255)->nullable();
            $table->text('alasan')->nullable();
            $table->timestamp('dibatalkan_at')->nullable();
            $table->foreignId('dibatalkan_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('dibatalkan_by_name')->nullable();
            $table->text('alasan_batal')->nullable();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('created_by_name')->nullable();
            $table->timestamps();

            $table->index(['kategori', 'dibatalkan_at', 'tanggal_efektif'], 'idx_mutasi_kategori_aktif_tgl');
            $table->index(['akademik_master_tahun_ajaran_id', 'kategori', 'dibatalkan_at'], 'idx_mutasi_ta_kategori_aktif');
            $table->index(['unit', 'kategori', 'dibatalkan_at'], 'idx_mutasi_unit_kategori_aktif');
            $table->index(['siswa_nis', 'tanggal_efektif'], 'idx_mutasi_siswa_tgl');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_siswa_mutasi');
    }
};

