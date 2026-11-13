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
        Schema::create('akademik_pelajaran_kelompok_pelajaran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('kode', 50)->unique('uq_kelompok_pelajaran_kode');
            $table->string('kelompok');
            $table->unsignedInteger('urutan');
            $table->timestamps();

            $table->index('urutan', 'idx_kelompok_pelajaran_urutan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_pelajaran_kelompok_pelajaran');
    }
};
