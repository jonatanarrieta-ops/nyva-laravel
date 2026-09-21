<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        try {

            Schema::disableForeignKeyConstraints();

            DB::beginTransaction();

            $this->call([
                UserSeeder::class,
                PlanSeeder::class,
                EstablishmentSeeder::class,
                SubscriptionSeeder::class,
                CategorySeeder::class,
                ProductSeeder::class,
                ScheduleSeeder::class,
                FrequentlyAskedQuestionSeeder::class,
                OrderSeeder::class,
                OrderItemSeeder::class,
                ConversationSeeder::class,
            ]);

            DB::commit();

        } catch (\Throwable $th) {

            DB::rollBack();

            dd($th->getMessage());

        } finally {

            Schema::enableForeignKeyConstraints();

        }
    }
}
