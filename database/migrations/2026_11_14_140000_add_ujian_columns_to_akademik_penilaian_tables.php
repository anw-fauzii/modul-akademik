<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan dukungan bobot & nilai ujian (STS/SAS) pada konfigurasi KKM dan rekap mapel.
     */
    public function up(): void
    {
        Schema::table('akademik_penilaian_kkm', function (Blueprint $table) {
            $table->unsignedTinyInteger('bobot_tp')->default(100)->after('nilai_kkm');
            $table->unsignedTinyInteger('bobot_ujian')->default(0)->after('bobot_tp');
        });

        Schema::table('akademik_penilaian_rekap_mapel', function (Blueprint $table) {
            $table->decimal('nilai_ujian', 5, 2)->nullable()->after('rata_rata_tp');
            $table->unsignedTinyInteger('bobot_tp_snapshot')->nullable()->after('nilai_kkm_snapshot');
            $table->unsignedTinyInteger('bobot_ujian_snapshot')->nullable()->after('bobot_tp_snapshot');
        });
    }

    /**
     * Menghapus kolom bobot & nilai ujian jika rollback dilakukan.
     */
    public function down(): void
    {
        Schema::table('akademik_penilaian_rekap_mapel', function (Blueprint $table) {
            $table->dropColumn(['nilai_ujian', 'bobot_tp_snapshot', 'bobot_ujian_snapshot']);
        });

        Schema::table('akademik_penilaian_kkm', function (Blueprint $table) {
            $table->dropColumn(['bobot_tp', 'bobot_ujian']);
        });
    }
};
