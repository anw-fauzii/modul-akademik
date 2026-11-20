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
        Schema::table('akademik_master_ekstrakuler', function (Blueprint $table) {
            $table->unsignedTinyInteger('kkm')->default(75)->after('unit')->comment('KKM Ekstrakurikuler (1-100)');
            $table->text('materi')->nullable()->after('kkm')->comment('Materi / Kompetensi Ekstrakurikuler untuk deskripsi rapor');
        });

        Schema::table('akademik_master_anggota_ekstrakuler', function (Blueprint $table) {
            $table->unsignedTinyInteger('nilai')->nullable()->after('pendaftaran')->comment('Nilai angka ekstrakurikuler (0-100)');
            $table->string('predikat', 20)->nullable()->after('nilai')->comment('Predikat rapor: SAB, BSH, SB, MB');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akademik_master_anggota_ekstrakuler', function (Blueprint $table) {
            $table->dropColumn(['nilai', 'predikat']);
        });

        Schema::table('akademik_master_ekstrakuler', function (Blueprint $table) {
            $table->dropColumn(['kkm', 'materi']);
        });
    }
};
