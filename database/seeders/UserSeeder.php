<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User; // <-- Tambahkan ini
use Illuminate\Support\Facades\Hash; // <-- Tambahkan ini

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'API Tester',
            'email' => 'tester@kolabora.com',
            'password' => Hash::make('password123'),
        ]);
    }
}
