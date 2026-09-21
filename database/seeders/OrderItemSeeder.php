<?php

namespace Database\Seeders;

use App\Models\OrderItem;
use Illuminate\Database\Seeder;

class OrderItemSeeder extends Seeder
{
    public function run(): void
    {
        try {

            OrderItem::create([
                'order_id' => 1,
                'product_id' => 1,
                'quantity' => 1,
                'unit_price' => 25000,
                'subtotal' => 25000,
            ]);

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}
