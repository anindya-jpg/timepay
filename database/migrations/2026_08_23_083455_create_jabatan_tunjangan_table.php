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
    Schema::create('jabatan_tunjangan', function (Blueprint $table) {
        $table->id('id_jabatantunjangan');
        $table->foreignId('id_jabatan')->constrained('jabatan', 'id_jabatan')
              ->cascadeOnUpdate()->cascadeOnDelete();
        $table->foreignId('id_tunjangan')->constrained('tunjangan', 'id_tunjangan')
              ->cascadeOnUpdate()->cascadeOnDelete();
        $table->decimal('nominal', 15, 2)->default(0);
        $table->unique(['id_jabatan', 'id_tunjangan']);
    });
}

public function down(): void
{
    Schema::dropIfExists('jabatan_tunjangan');
}
};
