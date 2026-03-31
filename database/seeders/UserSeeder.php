<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

// IMPORTANTE
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Usuario administrador
        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('12345678'),
        ]);

        // Usuario de prueba
        User::create([
            'name' => 'Usuario Prueba',
            'email' => 'user@test.com',
            'password' => Hash::make('12345678'),
        ]);

        // Usuarios aleatorios (PRO)
        User::factory(5)->create();
    }
}