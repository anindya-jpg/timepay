<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResetDataTransaksi extends Command
{
    /**
     * Nama & signature command.
     * Jalankan dengan: php artisan reset:data-transaksi
     */
    protected $signature = 'reset:data-transaksi
                            {--force : Lewati konfirmasi, langsung eksekusi}';

    /**
     * Deskripsi command (muncul di php artisan list).
     */
    protected $description = 'Kosongkan data penggajian, absensi, karyawan, dan hari_libur (reset ID balik ke 1). Tabel master lain (jabatan, tunjangan, dll) TIDAK ikut terhapus.';

    public function handle(): int
    {
        $this->warn('Command ini akan MENGHAPUS SEMUA data di tabel:');
        $this->line('  - penggajian');
        $this->line('  - absensi');
        $this->line('  - karyawan');
        $this->line('  - hari_libur');
        $this->newLine();
        $this->line('Tabel master lain (jabatan, tunjangan, tarif_lembur, potongan_rule, admin) TIDAK akan disentuh.');

        if (! $this->option('force') && ! $this->confirm('Yakin mau lanjut?')) {
            $this->info('Dibatalkan.');
            return self::SUCCESS;
        }

        Schema::disableForeignKeyConstraints();

        DB::table('penggajian')->truncate();
        $this->info('✓ Tabel penggajian dikosongkan.');

        DB::table('absensi')->truncate();
        $this->info('✓ Tabel absensi dikosongkan.');

        DB::table('karyawan')->truncate();
        $this->info('✓ Tabel karyawan dikosongkan.');

        DB::table('hari_libur')->truncate();
        $this->info('✓ Tabel hari_libur dikosongkan.');

        Schema::enableForeignKeyConstraints();

        $this->newLine();
        $this->info('Selesai. Semua ID sudah reset balik ke 1.');

        return self::SUCCESS;
    }
}