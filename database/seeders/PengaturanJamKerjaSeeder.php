<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PengaturanJamKerjaSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('pengaturan_jam_kerja')->insert([
            'jam_masuk_standar' => '09:00:00',
            'jam_pulang_standar' => '17:00:00',
        ]);
    }
}