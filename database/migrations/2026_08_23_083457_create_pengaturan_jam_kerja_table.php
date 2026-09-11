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
    Schema::create('pengaturan_jam_kerja', function (Blueprint $table) {
        $table->id('id_jamkerja');
        $table->time('jam_masuk_standar');
        $table->time('jam_pulang_standar');
    });
}

public function down(): void
{
    Schema::dropIfExists('pengaturan_jam_kerja');
}
};
