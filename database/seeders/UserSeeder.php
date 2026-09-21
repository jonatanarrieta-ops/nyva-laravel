<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        try {

            User::create([
                'first_name' => 'Administrador',
                'last_name' => 'NYVA',
                'email' => 'admin@nyva.com',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'status' => true,
                'registered_at' => now(),
            ]);

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}