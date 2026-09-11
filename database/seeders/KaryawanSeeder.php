<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class KaryawanSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('karyawan')->insert([
            [
                'nip' => 'KRY001',
                'nama' => 'Dirga Putra',
                'jenis_kelamin' => 'L',
                'alamat' => 'Bandung',
                'no_hp' => '081234567801',
                'email' => 'dirga@gmail.com',
                'no_rekening' => '123456789001',
                'username' => 'dirgaputra',
                'password' => Hash::make('12345'),
                'foto' => null,
                'id_jabatan' => 1,
                'tanggal_masuk' => '2025-01-10',
                'status_aktif' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nip' => 'KRY002',
                'nama' => 'Aisha Hagya',
                'jenis_kelamin' => 'P',
                'alamat' => 'Bandung',
                'no_hp' => '081234567802',
                'email' => 'aisha@gmail.com.com',
                'no_rekening' => '123456789002',
                'username' => 'aishahagya',
                'password' => Hash::make('12345'),
                'foto' => null,
                'id_jabatan' => 1,
                'tanggal_masuk' => '2025-02-15',
                'status_aktif' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nip' => 'KRY003',
                'nama' => 'Azran Fatur',
                'jenis_kelamin' => 'L',
                'alamat' => 'Cimahi',
                'no_hp' => '081234567803',
                'email' => 'azran@example.com',
                'no_rekening' => '123456789003',
                'username' => 'azranfatur',
                'password' => Hash::make('01234'),
                'foto' => null,
                'id_jabatan' => 1,
                'tanggal_masuk' => '2025-03-20',
                'status_aktif' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nip' => 'KRY004',
                'nama' => 'Yasmine Sayla',
                'jenis_kelamin' => 'P',
                'alamat' => 'Sumedang',
                'no_hp' => '081234567804',
                'email' => 'yasmine@example.com',
                'no_rekening' => '123456789004',
                'username' => 'yasminesayla',
                'password' => Hash::make('01234'),
                'foto' => null,
                'id_jabatan' => 2,
                'tanggal_masuk' => '2024-06-01',
                'status_aktif' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nip' => 'KRY005',
                'nama' => 'Rayden Kazka',
                'jenis_kelamin' => 'L',
                'alamat' => 'Garut',
                'no_hp' => '081234567805',
                'email' => 'raydem@example.com',
                'no_rekening' => '123456789005',
                'username' => 'raydenkazka',
                'password' => Hash::make('246810'),
                'foto' => null,
                'id_jabatan' => 2,
                'tanggal_masuk' => '2025-05-12',
                'status_aktif' => 'aktif',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }
}