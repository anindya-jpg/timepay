<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            JabatanSeeder::class,
            TunjanganSeeder::class,
            JabatanTunjanganSeeder::class,
            PengaturanJamKerjaSeeder::class,
            HariLiburSeeder::class,
            TarifLemburSeeder::class,
            PotonganRuleSeeder::class,

            // Dummy data untuk testing lokal — JANGAN diaktifkan di production.
            // Aktifkan lagi manual kalau butuh data contoh saat development.
            // KaryawanSeeder::class,
            // AbsensiSeeder::class,
        ]);
    }
}