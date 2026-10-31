<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemohons', function (Blueprint $table) {
            $table->string('nomor', 30)->nullable()->after('id');          // nomor ID dari kolom A
            $table->date('tanggal_lahir')->nullable()->after('nama');      // kolom C
            $table->string('jenis', 2)->nullable()->after('tanggal_lahir'); // P = Prioritas, MP = Non prioritas

            // Kunci impor: mengimpor file yang sama dua kali memperbarui baris, bukan menggandakannya.
            $table->unique(['nomor', 'tahun', 'bulan', 'jenis'], 'pemohons_kunci_impor_unique');
        });
    }

    public function down(): void
    {
        Schema::table('pemohons', function (Blueprint $table) {
            $table->dropUnique('pemohons_kunci_impor_unique');
            $table->dropColumn(['nomor', 'tanggal_lahir', 'jenis']);
        });
    }
};
