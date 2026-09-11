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
        Schema::create('absensi', function (Blueprint $table) {
            $table->id('id_absensi');
            $table->foreignId('id_karyawan')->constrained('karyawan', 'id_karyawan')
                  ->cascadeOnUpdate()->cascadeOnDelete();
            $table->date('tanggal');
            $table->time('jam_masuk')->nullable();
            $table->time('jam_pulang')->nullable();
            $table->string('foto_masuk', 255)->nullable();
            $table->string('foto_pulang', 255)->nullable();
            $table->enum('status', ['Hadir', 'Telat', 'Pulang Cepat', 'Alpha', 'Sakit', 'Izin'])->default('Hadir');
            $table->integer('menit_telat')->default(0);
            $table->decimal('jam_lembur', 5, 2)->default(0);
            $table->string('keterangan_lembur', 255)->nullable();
            $table->enum('status_approval_lembur', ['pending', 'approved', 'rejected'])->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['id_karyawan', 'tanggal']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('absensi');
    }
};