<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TunjanganSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tunjangan')->insert([
            [
                'nama_tunjangan' => 'Transport',
                'keterangan' => 'Tunjangan transportasi bulanan',
            ],
            [
                'nama_tunjangan' => 'Makan',
                'keterangan' => 'Tunjangan makan bulanan',
            ],
            [
                'nama_tunjangan' => 'Kesehatan',
                'keterangan' => 'Tunjangan kesehatan bulanan',
            ],
            [
                'nama_tunjangan' => 'Jabatan',
                'keterangan' => 'Kompensasi tambahan atas tanggung jawab struktural',
            ],
        ]);
    }
}