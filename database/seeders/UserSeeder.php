<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Administrador AELU',
            'email' => 'admin@aelu.pe',
            'password' => bcrypt('admin'),
            'email_verified_at' => now(),
        ]);
    }
}
