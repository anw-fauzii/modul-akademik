<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel master angka arab T2Q (konversi angka latin ke angka arab).
     */
    public function up(): void
    {
        Schema::create('akademik_master_t2q_angka_arab', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('angka_latin', 50)->index('idx_t2q_angka_arab_latin');
            $table->string('angka_arab', 50);
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel master angka arab T2Q.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_t2q_angka_arab');
    }
};

