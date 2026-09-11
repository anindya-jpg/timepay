<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->decimal('lokasi_masuk_lat', 10, 7)->nullable()->after('foto_masuk');
            $table->decimal('lokasi_masuk_lng', 10, 7)->nullable()->after('lokasi_masuk_lat');
            $table->decimal('jarak_masuk_meter', 10, 2)->nullable()->after('lokasi_masuk_lng');

            $table->decimal('lokasi_pulang_lat', 10, 7)->nullable()->after('foto_pulang');
            $table->decimal('lokasi_pulang_lng', 10, 7)->nullable()->after('lokasi_pulang_lat');
            $table->decimal('jarak_pulang_meter', 10, 2)->nullable()->after('lokasi_pulang_lng');
        });
    }

    public function down(): void
    {
        Schema::table('absensi', function (Blueprint $table) {
            $table->dropColumn([
                'lokasi_masuk_lat', 'lokasi_masuk_lng', 'jarak_masuk_meter',
                'lokasi_pulang_lat', 'lokasi_pulang_lng', 'jarak_pulang_meter',
            ]);
        });
    }
};