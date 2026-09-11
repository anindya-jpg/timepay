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
       Schema::create('penggajian', function (Blueprint $table) {
    $table->id('id_gaji');
    $table->foreignId('id_karyawan')->constrained('karyawan', 'id_karyawan')
          ->cascadeOnUpdate()->cascadeOnDelete();
    $table->tinyInteger('bulan');
    $table->smallInteger('tahun');
    $table->decimal('gaji_pokok', 15, 2)->default(0);
    $table->decimal('total_tunjangan', 15, 2)->default(0);
    $table->decimal('total_jam_lembur', 6, 2)->default(0);
    $table->decimal('total_tunjangan_lembur', 15, 2)->default(0);
    $table->decimal('total_potongan', 15, 2)->default(0);
    $table->integer('jumlah_hadir')->default(0);
    $table->integer('jumlah_telat')->default(0);
    $table->integer('jumlah_alpha')->default(0);
    $table->decimal('gaji_bersih', 15, 2)->default(0);
    $table->dateTime('tanggal_generate')->nullable();
    $table->enum('status', ['draft', 'final'])->default('draft');
    $table->timestamps();
    $table->unique(['id_karyawan', 'bulan', 'tahun']);
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penggajian');
    }
};
