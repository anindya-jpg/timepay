<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class JabatanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('jabatan')->insert([
            [
                'nama_jabatan' => 'Staff',
                'gaji_pokok' => 4000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nama_jabatan' => 'Manager',
                'gaji_pokok' => 6000000,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}