<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('admin')->insert([
            'nama' => 'anindya',
            'username' => 'admin1',
            'password' => Hash::make(env('ADMIN_PASSWORD', 'ganti-ini-di-env')),
            'foto' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}