<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel master komentar T2Q untuk pilihan kelebihan & peningkatan pada deskripsi rapor.
     */
    public function up(): void
    {
        Schema::create('akademik_master_t2q_komentar', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('komentar', 255)->unique('uq_t2q_komentar_teks');
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel master komentar T2Q.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_t2q_komentar');
    }
};
