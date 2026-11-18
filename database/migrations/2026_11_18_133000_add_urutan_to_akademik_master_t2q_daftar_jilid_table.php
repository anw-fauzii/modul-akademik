<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom urutan pada tabel master daftar jilid T2Q.
     */
    public function up(): void
    {
        Schema::table('akademik_master_t2q_daftar_jilid', function (Blueprint $table) {
            $table->unsignedInteger('urutan')->default(0)->after('jenis')->index('idx_t2q_daftar_jilid_urutan');
        });
    }

    /**
     * Menghapus kolom urutan dari tabel master daftar jilid T2Q.
     */
    public function down(): void
    {
        Schema::table('akademik_master_t2q_daftar_jilid', function (Blueprint $table) {
            $table->dropIndex('idx_t2q_daftar_jilid_urutan');
            $table->dropColumn('urutan');
        });
    }
};

