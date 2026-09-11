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
    Schema::create('tunjangan', function (Blueprint $table) {
        $table->id('id_tunjangan');
        $table->string('nama_tunjangan', 100);
        $table->string('keterangan', 255)->nullable();
    });
}

public function down(): void
{
    Schema::dropIfExists('tunjangan');
}
};
