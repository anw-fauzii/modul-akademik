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
        Schema::create('akademik_pelajaran_mata_pelajaran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('akademik_pelajaran_kelompok_pelajaran_id');
            $table->string('singkatan', 30);
            $table->string('nama_mata_pelajaran');
            $table->enum('sifat', ['wajib', 'tambahan'])->default('wajib');
            $table->string('unit', 50);
            $table->unsignedInteger('urutan');
            $table->text('keterangan')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();

            $table->unique(['unit', 'singkatan'], 'uq_mata_pelajaran_unit_singkatan');
            $table->unique(['unit', 'nama_mata_pelajaran'], 'uq_mata_pelajaran_unit_nama');
            $table->index(
                ['unit', 'akademik_pelajaran_kelompok_pelajaran_id', 'urutan'],
                'idx_mp_unit_kelompok_urutan'
            );
            $table->index('status', 'idx_mp_status');

            $table->foreign('akademik_pelajaran_kelompok_pelajaran_id', 'fk_mp_kelompok_pelajaran')
                ->references('id')
                ->on('akademik_pelajaran_kelompok_pelajaran')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_pelajaran_mata_pelajaran');
    }
};
