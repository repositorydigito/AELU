<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

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
