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
        Schema::create('akademik_pelajaran_jam_pelajaran', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedInteger('jam_ke');
            $table->string('unit', 50);
            $table->time('jam_mulai');
            $table->time('jam_selesai');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->unique(['unit', 'jam_ke'], 'uq_jam_pelajaran_unit_jam_ke');
            $table->index(['unit', 'jam_ke', 'jam_mulai'], 'idx_jp_unit_jam_ke_mulai');
            $table->index(['jam_mulai', 'jam_selesai'], 'idx_jp_waktu');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_pelajaran_jam_pelajaran');
    }
};
