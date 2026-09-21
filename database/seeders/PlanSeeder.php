<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        try {

            Plan::insert([
                [
                    'name' => 'Basic',
                    'description' => 'Plan básico para establecimientos pequeños',
                    'monthly_price' => 29000,
                    'annual_price' => 290000,
                    'status' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'name' => 'Premium',
                    'description' => 'Plan avanzado para establecimientos',
                    'monthly_price' => 59000,
                    'annual_price' => 590000,
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