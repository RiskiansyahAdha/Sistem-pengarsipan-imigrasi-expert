<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pemohons', function (Blueprint $table) {
            // Tanggal data dimasukkan (kolom D Excel). Boleh kosong: data lama
            // yang belum punya kolom ini ditampilkan sebagai "Tidak ada data masuk".
            $table->date('tanggal_masuk')->nullable()->after('tanggal_lahir');
        });
    }

    public function down(): void
    {
        Schema::table('pemohons', function (Blueprint $table) {
            $table->dropColumn('tanggal_masuk');
        });
    }
};
