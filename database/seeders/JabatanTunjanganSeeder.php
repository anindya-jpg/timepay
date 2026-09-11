<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanTunjanganSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('jabatan_tunjangan')->insert([
            // Staff
            [
                'id_jabatan' => 1,
                'id_tunjangan' => 1,
                'nominal' => 300000,
            ],
            [
                'id_jabatan' => 1,
                'id_tunjangan' => 2,
                'nominal' => 350000,
            ],
            [
                'id_jabatan' => 1,
                'id_tunjangan' => 3,
                'nominal' => 200000,
            ],

            // Manager
            [
                'id_jabatan' => 2,
                'id_tunjangan' => 1,
                'nominal' => 350000,
            ],
            [
                'id_jabatan' => 2,
                'id_tunjangan' => 2,
                'nominal' => 400000,
            ],
            [
                'id_jabatan' => 2,
                'id_tunjangan' => 3,
                'nominal' => 300000,
            ],
            [
                'id_jabatan' => 2,
                'id_tunjangan' => 4,
                'nominal' => 500000,
            ],
        ]);
    }
}