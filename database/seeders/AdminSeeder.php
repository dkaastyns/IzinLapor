<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    // Jalankan seeder database
    // Aturan domain:
    // - Admin: @sman11.sch.id (password: Admin.3669)
    // - Siswa: @student.sman11.sch.id (password: Password.123)
    public function run(): void
    {
        // Langkah 1: Hapus akun dengan domain email yang tidak valid
        User::where(function ($query) {
            $query->where('is_admin', false)
                  ->where('email', 'NOT LIKE', '%@student.sman11.sch.id');
        })->orWhere(function ($query) {
            $query->where('is_admin', true)
                  ->where('email', 'NOT LIKE', '%@sman11.sch.id');
        })->delete();

        // Akun Admin — domain: @sman11.sch.id | password: Admin.3669

        // Admin 1 — Sarpras
        User::updateOrCreate(
        ['email' => 'admin@sman11.sch.id'],
        [
            'name' => 'Admin Sarpras SMAN 11',
            'phone' => '081234567890',
            'password' => Hash::make('Admin.3669'),
            'is_admin' => true,
        ]
        );

        // Admin 2 — Wakil Sarpras
        User::updateOrCreate(
        ['email' => 'sarpras2@sman11.sch.id'],
        [
            'name' => 'Admin Sarpras Wakil',
            'phone' => '081234567891',
            'password' => Hash::make('Admin.3669'),
            'is_admin' => true,
        ]
        );

        // Admin 3 — Kurikulum
        User::updateOrCreate(
        ['email' => 'kurikulum@sman11.sch.id'],
        [
            'name' => 'Admin Kurikulum',
            'phone' => '081234567892',
            'password' => Hash::make('Admin.3669'),
            'is_admin' => true,
        ]
        );

        // Admin 4 — Kesiswaan
        User::updateOrCreate(
        ['email' => 'kesiswaan@sman11.sch.id'],
        [
            'name' => 'Admin Kesiswaan',
            'phone' => '081234567893',
            'password' => Hash::make('Admin.3669'),
            'is_admin' => true,
        ]
        );

        // Admin 5 — IT
        User::updateOrCreate(
        ['email' => 'it@sman11.sch.id'],
        [
            'name' => 'Admin IT',
            'phone' => '081234567894',
            'password' => Hash::make('Admin.3669'),
            'is_admin' => true,
        ]
        );

        // Akun Pengguna (Siswa) — domain: @student.sman11.sch.id | password: Password.123

        // Siswa 1
        User::updateOrCreate(
        ['email' => 'Nafari@student.sman11.sch.id'],
        [
            'name' => 'Azam Nafari',
            'phone' => '082111111111',
            'password' => Hash::make('Password.123'),
            'is_admin' => false,
        ]
        );

        // Siswa 2
        User::updateOrCreate(
        ['email' => 'Kairi@student.sman11.sch.id'],
        [
            'name' => 'Kairi Risolmayo',
            'phone' => '082222222222',
            'password' => Hash::make('Password.123'),
            'is_admin' => false,
        ]
        );

        // Siswa 3
        User::updateOrCreate(
        ['email' => 'Dikaatzy@student.sman11.sch.id'],
        [
            'name' => 'Dikastyn',
            'phone' => '083333333333',
            'password' => Hash::make('Password.123'),
            'is_admin' => false,
        ]
        );
    }
}