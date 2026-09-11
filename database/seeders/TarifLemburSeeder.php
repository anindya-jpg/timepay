<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TarifLemburSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tarif_lembur')->insert([
            'tarif_per_jam' => 50000,
            'created_at' => now(),
        ]);
    }
}