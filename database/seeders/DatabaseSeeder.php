<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    // Isi database aplikasi dengan data awal (seed)
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            CategorySeeder::class,
        ]);
    }
}
