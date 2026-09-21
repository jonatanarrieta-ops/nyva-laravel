<?php

namespace Database\Seeders;

use App\Models\Establishment;
use Illuminate\Database\Seeder;

class EstablishmentSeeder extends Seeder
{
    public function run(): void
    {
        try {

            Establishment::create([
               'user_id' => 1,
                'name' => 'NYVA Restaurante',
                'type' => 'restaurant',
                'description' => 'Establecimiento de prueba del sistema NYVA',
                'address' => 'Sincelejo, Sucre',
                'phone' => '3000000000',
                'email' => 'contacto@nyva.com',
                'status' => true,
                'registered_at' => now(),
            ]);

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}