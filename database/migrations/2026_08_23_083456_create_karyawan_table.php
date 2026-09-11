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
    Schema::create('karyawan', function (Blueprint $table) {
        $table->id('id_karyawan');
        $table->string('nip', 30)->unique();
        $table->string('nama', 100);
        $table->enum('jenis_kelamin', ['L', 'P']);
        $table->text('alamat')->nullable();
        $table->string('no_hp', 20)->nullable();
        $table->string('email', 100)->nullable();
        $table->string('no_rekening', 30)->nullable();
        $table->string('username', 50)->unique();
        $table->string('password', 255);
        $table->string('foto', 255)->nullable();
        $table->foreignId('id_jabatan')->constrained('jabatan', 'id_jabatan')
              ->cascadeOnUpdate()->restrictOnDelete();
        $table->date('tanggal_masuk')->nullable();
        $table->enum('status_aktif', ['aktif', 'nonaktif'])->default('aktif');
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('karyawan');
}
};
