<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class HariLiburSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('hari_libur')->insert([
            [
                'tanggal' => '2026-01-01',
                'keterangan' => 'Tahun Baru Masehi',
            ],
            [
                'tanggal' => '2026-08-17',
                'keterangan' => 'Hari Kemerdekaan RI',
            ],
        ]);
    }
}