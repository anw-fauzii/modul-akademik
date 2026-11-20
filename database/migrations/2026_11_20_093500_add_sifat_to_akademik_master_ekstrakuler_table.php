<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('akademik_master_ekstrakuler', function (Blueprint $table) {
            $table->string('sifat', 20)
                ->default('Pilihan')
                ->after('unit')
                ->comment('Sifat ekstrakurikuler: Wajib (Pramuka) atau Pilihan (Minat)');
        });

        DB::table('akademik_master_ekstrakuler')
            ->whereRaw('LOWER(nama_ekstrakurikuler) LIKE ?', ['%pramuka%'])
            ->update(['sifat' => 'Wajib']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('akademik_master_ekstrakuler', function (Blueprint $table) {
            $table->dropColumn('sifat');
        });
    }
};
