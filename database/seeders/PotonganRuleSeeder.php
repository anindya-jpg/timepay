<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PotonganRuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('potongan_rule')->insert([
            [
                'nama_potongan' => 'Telat',
                'jenis' => 'per_menit',
                'nominal' => 500,
            ],
            [
                'nama_potongan' => 'Pulang Cepat',
                'jenis' => 'nominal_tetap',
                'nominal' => 50000,
            ],
            [
                'nama_potongan' => 'Alpha',
                'jenis' => 'persen',
                'nominal' => 9.10,
            ],
            [
                'nama_potongan' => 'Izin',
                'jenis' => 'persen',
                'nominal' => 4.55,
            ],
            [
                'nama_potongan' => 'Sakit',
                'jenis' => 'per_hari',
                'nominal' => 0,
            ],
            [
                'nama_potongan' => 'BPJS JHT',
                'jenis' => 'persen',
                'nominal' => 2,
            ],
            [
                'nama_potongan' => 'BPJS JP',
                'jenis' => 'persen',
                'nominal' => 1,
            ],
        ]);
    }
}