<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_lokasi', function (Blueprint $table) {
            $table->id('id_lokasi');
            $table->string('nama_lokasi')->default('Kantor Pusat');
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->unsignedInteger('radius_meter')->default(150);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_lokasi');
    }
};