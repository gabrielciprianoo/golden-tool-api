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

        User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => Hash::make('12345678'),
        ]);


        User::create([
            'name' => 'Usuario Prueba',
            'email' => 'user@test.com',
            'password' => Hash::make('12345678'),
        ]);

     
        User::factory(5)->create();
    }
}
