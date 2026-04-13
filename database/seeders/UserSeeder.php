<?php

namespace Database\Seeders;

use App\Models\User;
// IMPORTANTE
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        User::firstOrCreate(
            ['email' => 'admin@test.com'],
            ['name' => 'Admin', 'password' => Hash::make('12345678')]
        );

        User::firstOrCreate(
            ['email' => 'user@test.com'],
            ['name' => 'Usuario Prueba', 'password' => Hash::make('12345678')]
        );

        User::factory(5)->create();
    }
}
