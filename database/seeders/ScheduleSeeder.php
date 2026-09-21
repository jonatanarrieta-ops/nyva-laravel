<?php

namespace Database\Seeders;

use App\Models\Schedule;
use Illuminate\Database\Seeder;

class ScheduleSeeder extends Seeder
{
    public function run(): void
    {
        try {

            Schedule::create([
                'establishment_id' => 1,
                'day_of_week' => 'Monday',
                'opens_at' => '08:00:00',
                'closes_at' => '18:00:00',
                'status' => true,
            ]);

        } catch (\Throwable $th) {

            dd($th->getMessage());

        }
    }
}