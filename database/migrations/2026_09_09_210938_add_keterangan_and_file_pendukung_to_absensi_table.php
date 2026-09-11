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
        Schema::table('absensi', function (Blueprint $table) {
            // Keterangan izin/sakit yang diisi karyawan
            $table->string('keterangan', 500)
                  ->nullable()
                  ->after('status_approval_lembur');

            // Path file surat/keterangan izin/sakit (foto/pdf)
            $table->string('file_pendukung', 255)
                  ->nullable()
                  ->after('keterangan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn(['keterangan', 'file_pendukung']);
        });
    }
};