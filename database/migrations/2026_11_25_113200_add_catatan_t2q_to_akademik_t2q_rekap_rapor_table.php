<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom komentar/catatan pembinaan personal Guru T2Q dan snapshot nama penyunting pada rekap rapor T2Q.
     */
    public function up(): void
    {
        Schema::table('akademik_t2q_rekap_rapor', function (Blueprint $table): void {
            $table->text('catatan_t2q')->nullable()->after('tahfizh_deskripsi');
            $table->string('updated_by_name')->nullable()->after('updated_by_user_id');
        });
    }

    /**
     * Menghapus kolom tambahan catatan Guru T2Q.
     */
    public function down(): void
    {
        Schema::table('akademik_t2q_rekap_rapor', function (Blueprint $table): void {
            $table->dropColumn(['catatan_t2q', 'updated_by_name']);
        });
    }
};
