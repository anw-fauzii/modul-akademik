<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel master identitas satuan pendidikan per jenjang (PG, TK, SD, SMP, dll.) untuk kop & halaman identitas rapor.
     */
    public function up(): void
    {
        Schema::create('akademik_master_sekolah', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('jenjang_kelas', 20)->unique('uq_sekolah_jenjang');
            $table->string('nama_yayasan', 150)->nullable();
            $table->string('nama_sekolah', 150);
            $table->string('npsn', 30)->nullable();
            $table->string('nss', 30)->nullable();
            $table->text('alamat');
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->string('kelurahan', 100)->nullable();
            $table->string('kecamatan', 100)->nullable();
            $table->string('kabupaten', 100)->nullable();
            $table->string('provinsi', 100)->nullable();
            $table->string('kode_pos', 20)->nullable();
            $table->string('telepon', 50)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('website', 150)->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel master identitas sekolah.
     */
    public function down(): void
    {
        Schema::dropIfExists('akademik_master_sekolah');
    }
};

