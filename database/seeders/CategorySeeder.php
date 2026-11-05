<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        try {

            Category::insert([
                [
                    'establishment_id' => 1,
                    'name' => 'Hamburguesas',
                    'description' => 'Productos de hamburguesería',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'establishment_id' => 1,
                    'name' => 'Bebidas',
                    'description' => 'Bebidas disponibles',
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}
