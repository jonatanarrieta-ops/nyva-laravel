<?php

namespace Database\Seeders;

use App\Models\Subscription;
use Illuminate\Database\Seeder;

class SubscriptionSeeder extends Seeder
{
    public function run(): void
    {
        try {

            Subscription::create([
                'establishment_id' => 1,
                'plan_id' => 1,
                'billing_period' => 'monthly',
                'start_date' => now(),
                'end_date' => now()->addMonth(),
                'status' => true,
            ]);

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}
