<?php

namespace Database\Seeders;

use App\Models\Order;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    public function run(): void
    {
        try {

            Order::create([
                'establishment_id' => 1,
                'customer_name' => 'Cliente prueba',
                'customer_phone' => '3000000000',
                'delivery_address' => 'Sincelejo, Sucre',
                'total' => 30000,
                'status' => 'pending',
                'ordered_at' => now(),
            ]);

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}