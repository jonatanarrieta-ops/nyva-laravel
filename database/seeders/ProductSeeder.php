<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        try {

            Product::insert([
                [
                    'category_id' => 1,
                    'name' => 'Hamburguesa NYVA',
                    'description' => 'Hamburguesa especial de la casa',
                    'price' => 25000,
                    'available' => true,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'category_id' => 2,
                    'name' => 'Gaseosa',
                    'description' => 'Bebida fría',
                    'price' => 5000,
                    'available' => true,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}
