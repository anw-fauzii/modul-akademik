<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel master bank template komentar rapor untuk Wali Kelas & Guru Pendamping.
     */
    public function up(): void
    {
        Schema::create('akademik_master_komentar_rapor', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('judul', 120)->unique('uq_komentar_rapor_judul');
            $table->enum('kategori', ['positif', 'motivasi', 'perhatian', 'umum'])->default('umum');
            $table->string('jenjang', 20)->nullable();
            $table->text('isi_komentar');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->index(['is_active', 'kategori', 'urutan'], 'idx_komentar_rapor_aktif_kat');
        });
    }

    /**
     * Menghapus tabel master bank template komentar rapor.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_komentar_rapor');
    }
};
